<?php
/*
 * "Sign in through Steam" using Steam's OpenID 2.0 endpoint.
 * No API key is needed to log in; the SteamID64 Steam returns is the same
 * ID the game server stores players under (AuthId_SteamID64).
 */

const CM_STEAM_OPENID = 'https://steamcommunity.com/openid/login';

function cm_user(): ?array
{
	$id = $_SESSION['steamid64'] ?? null;
	if (!$id) {
		return null;
	}
	static $user = null;
	if ($user === null) {
		$user = cm_row('SELECT * FROM cm_players WHERE steamid64 = ?', [$id]) ?? [
			'steamid64' => $id,
			'name'      => $_SESSION['steam_name'] ?? 'New Player',
			'balance'   => 0,
			'_new'      => true,
		];
	}
	return $user;
}

function cm_is_admin(): bool
{
	$u = cm_user();
	return $u && in_array($u['steamid64'], cm_config('admins', []), true);
}

function cm_steam_login_url(string $returnTo): string
{
	$site = rtrim(cm_config('site_url'), '/');
	return CM_STEAM_OPENID . '?' . http_build_query([
		'openid.ns'         => 'http://specs.openid.net/auth/2.0',
		'openid.mode'       => 'checkid_setup',
		'openid.return_to'  => $returnTo,
		'openid.realm'      => $site . '/',
		'openid.identity'   => 'http://specs.openid.net/auth/2.0/identifier_select',
		'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
	]);
}

/**
 * Verify the OpenID response with Steam. Returns the SteamID64 or null.
 */
function cm_steam_verify(array $query): ?string
{
	if (($query['openid_mode'] ?? '') !== 'id_res') {
		return null;
	}

	// The return_to Steam signed must be our own login page.
	$site = rtrim(cm_config('site_url'), '/');
	if (strpos($query['openid_return_to'] ?? '', $site . '/login.php') !== 0) {
		return null;
	}

	$params = ['openid.mode' => 'check_authentication'];
	foreach (explode(',', $query['openid_signed'] ?? '') as $field) {
		$key = 'openid_' . str_replace('.', '_', $field);
		if (!isset($query[$key])) {
			return null;
		}
		$params['openid.' . $field] = $query[$key];
	}
	$params['openid.ns']     = 'http://specs.openid.net/auth/2.0';
	$params['openid.sig']    = $query['openid_sig'] ?? '';
	$params['openid.assoc_handle'] = $query['openid_assoc_handle'] ?? '';
	$params['openid.signed'] = $query['openid_signed'] ?? '';

	$ch = curl_init(CM_STEAM_OPENID);
	curl_setopt_array($ch, [
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => http_build_query($params),
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT        => 10,
	]);
	$response = (string)curl_exec($ch);
	curl_close($ch);

	if (strpos($response, 'is_valid:true') === false) {
		return null;
	}

	if (!preg_match('#^https?://steamcommunity\.com/openid/id/(7656119\d{10})/?$#', $query['openid_claimed_id'] ?? '', $m)) {
		return null;
	}

	return $m[1];
}

/** Optional: fetch name + avatar from the Steam Web API. */
function cm_steam_profile(string $steamid64): ?array
{
	$key = cm_config('steam_api_key');
	if (!$key) {
		return null;
	}
	$url = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?' . http_build_query(['key' => $key, 'steamids' => $steamid64]);
	$ch = curl_init($url);
	curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
	$data = json_decode((string)curl_exec($ch), true);
	curl_close($ch);
	return $data['response']['players'][0] ?? null;
}
