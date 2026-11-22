<?php
/*
 * Steam profile pictures. Every player's picture is their Steam avatar, kept in cm_players.avatar_url.
 *
 * The website fills them in itself, in the background after a page has been sent: at most once every
 * 10 minutes it asks Steam for the pictures of the players who don't have one yet, then of those seen most
 * recently (so changed avatars follow too). With 'steam_api_key' in config.php it asks for 100 players at a
 * time through the Steam Web API; without one it reads up to 10 public Steam profiles at a time.
 * The website's database user needs UPDATE (avatar_url) on cm_players and INSERT, UPDATE on cm_site_settings.
 */

const CM_AVATAR_EVERY = 600;     // seconds between refreshes
const CM_AVATAR_BATCH_API = 100; // players per refresh with an API key (Steam's own limit per call)
const CM_AVATAR_BATCH_XML = 10;  // players per refresh without one

/** A player's Steam picture, or their first letter on a colored circle when there isn't one yet. */
function cm_avatar(array $p, string $class = 'face'): string
{
	$url = (string)($p['avatar_url'] ?? '');
	if ($url !== '') {
		return '<img class="' . h($class) . '" src="' . h($url) . '" alt="" loading="lazy" referrerpolicy="no-referrer">';
	}
	$name = (string)($p['name'] ?? '');
	$letter = mb_strtoupper(mb_substr(preg_replace('/[^\p{L}\p{N}]/u', '', $name) ?: '?', 0, 1));
	$hue = crc32((string)($p['steamid64'] ?? $name)) % 360;
	return '<span class="' . h($class) . ' letter" style="background:hsl(' . $hue . ',45%,32%)" aria-hidden="true">' . h($letter) . '</span>';
}

/** Only Steam's own image servers are stored, never whatever a profile page happens to contain. */
function cm_avatar_url_ok(string $url): bool
{
	return (bool)preg_match('#^https://[a-z0-9.-]+\.(steamstatic\.com|akamaihd\.net)/[A-Za-z0-9_./-]+\.(jpg|jpeg|png|gif)$#', $url);
}

/**
 * The full-size avatar URL of each of these SteamID64s that Steam answered for.
 * @param string[] $ids
 * @return array<string,string> steamid64 => URL
 */
function cm_steam_avatars(array $ids, float $timeout = 5.0): array
{
	$ids = array_values(array_filter($ids, 'cm_is_steamid64'));
	if (!$ids || !function_exists('curl_init')) {
		return [];
	}
	$out = [];
	$key = (string)cm_config('steam_api_key', '');
	if ($key !== '') {
		foreach (array_chunk($ids, 100) as $chunk) {
			$ch = curl_init('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?' . http_build_query(['key' => $key, 'steamids' => implode(',', $chunk)]));
			curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => (int)ceil($timeout), CURLOPT_CONNECTTIMEOUT => 3]);
			$data = json_decode((string)curl_exec($ch), true);
			curl_close($ch);
			foreach ($data['response']['players'] ?? [] as $pl) {
				$url = (string)($pl['avatarfull'] ?? '');
				if (cm_is_steamid64($pl['steamid'] ?? '') && cm_avatar_url_ok($url)) {
					$out[(string)$pl['steamid']] = $url;
				}
			}
		}
		return $out;
	}

	// No API key: each player's public profile, all at once.
	$multi = curl_multi_init();
	$handles = [];
	foreach ($ids as $id) {
		$ch = curl_init('https://steamcommunity.com/profiles/' . $id . '/?xml=1');
		curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => (int)ceil($timeout), CURLOPT_CONNECTTIMEOUT => 3,
			CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 2, CURLOPT_USERAGENT => 'CelMod website']);
		curl_multi_add_handle($multi, $ch);
		$handles[$id] = $ch;
	}
	do {
		$status = curl_multi_exec($multi, $running);
		if ($running) {
			curl_multi_select($multi, 0.5);
		}
	} while ($running && $status === CURLM_OK);
	foreach ($handles as $id => $ch) {
		$url = cm_avatar_from_xml((string)curl_multi_getcontent($ch));
		if ($url !== null) {
			$out[$id] = $url;
		}
		curl_multi_remove_handle($multi, $ch);
		curl_close($ch);
	}
	curl_multi_close($multi);
	return $out;
}

/** The avatarFull URL in a steamcommunity.com/profiles/<id>/?xml=1 answer. */
function cm_avatar_from_xml(string $xml): ?string
{
	if (!preg_match('#<avatarFull>\s*(?:<!\[CDATA\[)?\s*(https://[^\s<\]]+)\s*(?:\]\]>)?\s*</avatarFull>#', $xml, $m)) {
		return null;
	}
	return cm_avatar_url_ok($m[1]) ? $m[1] : null;
}

/** Saves avatars Steam gave us. Returns how many changed. */
function cm_save_avatars(array $avatars): int
{
	$db = cm_db();
	if (!$db || !$avatars) {
		return 0;
	}
	$changed = 0;
	try {
		$st = $db->prepare('UPDATE cm_players SET avatar_url = ? WHERE steamid64 = ? AND avatar_url <> ?');
		foreach ($avatars as $id => $url) {
			$st->execute([$url, (string)$id, $url]);
			$changed += $st->rowCount();
		}
	} catch (Throwable $e) {
		// The website's database user can't write avatar_url (see the README's GRANTs).
	}
	return $changed;
}

/**
 * Refreshes a batch of avatars, if it's time. Only one request in 10 minutes gets to do it: the time is claimed
 * in cm_site_settings first, so two visitors at once don't both ask Steam.
 */
function cm_avatars_refresh(bool $force = false): int
{
	$db = cm_db();
	if (!$db) {
		return 0;
	}
	try {
		$now = time();
		$db->exec("INSERT IGNORE INTO cm_site_settings (name, value) VALUES ('avatars_refreshed', '0')");
		$st = $db->prepare("UPDATE cm_site_settings SET value = ?, updated_at = NOW() WHERE name = 'avatars_refreshed' AND CAST(value AS UNSIGNED) < ?");
		$st->execute([(string)$now, $force ? $now + 1 : $now - CM_AVATAR_EVERY]);
		if ($st->rowCount() !== 1) {
			return 0;
		}
		$batch = (string)cm_config('steam_api_key', '') !== '' ? CM_AVATAR_BATCH_API : CM_AVATAR_BATCH_XML;
		$ids = $db->query("SELECT steamid64 FROM cm_players ORDER BY (avatar_url = '') DESC, is_online DESC, last_seen DESC LIMIT $batch")->fetchAll(PDO::FETCH_COLUMN);
	} catch (Throwable $e) {
		return 0; // no cm_site_settings yet, or no permission to write it
	}
	return cm_save_avatars(cm_steam_avatars($ids));
}

/** Runs the refresh after the page has gone to the visitor, so nobody waits on Steam. */
function cm_avatars_after_page(): void
{
	if (PHP_SAPI === 'cli' || !cm_config('steam_avatars', true)) {
		return;
	}
	register_shutdown_function(static function (): void {
		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		} elseif (function_exists('litespeed_finish_request')) {
			litespeed_finish_request();
		}
		ignore_user_abort(true);
		cm_avatars_refresh();
	});
}
