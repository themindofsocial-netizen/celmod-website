<?php
/*
 * Seasonal themes. Each holiday CelMod celebrates gets its own look around the
 * site while its seasonal shop is open: the same frame and colours, dressed up.
 *
 * Which theme shows, first match wins:
 *   1. An admin's choice on the admin panel (cm_site_settings "season"): a season, "none" or "auto".
 *   2. A seasonal shop the game server says is open (its "<Name> Shop" category in cm_shop_items),
 *      so an admin's !forceseason in game shows here too.
 *   3. The calendar, with the same dates as the server's shop.txt.
 */

/** The seasons, in the same order and with the same dates as "Seasons" in the server's shop.txt. */
function cm_seasons(): array
{
	return [
		'halloween' => [
			'name'     => 'Halloween',
			'shop'     => 'Halloween Shop',
			'start'    => '10/24',
			'end'      => '11/01',
			'greeting' => 'Happy Halloween!',
			'line'     => 'The Halloween Shop is open: pumpkins, Jack-o\'-Lanterns and the Skeleton.',
		],
		'christmas' => [
			'name'     => 'Christmas',
			'shop'     => 'Christmas Shop',
			'start'    => '12/18',
			'end'      => '12/26',
			'greeting' => 'Merry Christmas!',
			'line'     => 'The Christmas Shop is open, and you can wrap your items as presents with !wrap.',
		],
		'newyears' => [
			'name'     => 'New Year',
			'shop'     => 'New Year Shop',
			'start'    => '12/31',
			'end'      => '01/02',
			'greeting' => 'Happy New Year!',
			'line'     => 'The New Year Shop is open. Here\'s to another year of building.',
		],
		'clockday' => [
			'name'     => 'Clock Day',
			'shop'     => 'Clock Day Shop',
			'start'    => '08/14',
			'end'      => '08/16',
			'greeting' => 'Happy Clock Day!',
			'line'     => 'The Clock Day Shop is open, and the Strawberry Clock is back.',
		],
	];
}

/** "12/25" -> 1225. */
function cm_season_md(string $date): int
{
	[$m, $d] = array_map('intval', explode('/', $date) + [0, 0]);
	return $m * 100 + $d;
}

/** Whether a season's dates include a day (default today). Seasons can run over New Year. */
function cm_season_in_dates(array $season, ?int $time = null): bool
{
	$today = (int)date('nd', $time ?? time());
	$start = cm_season_md($season['start']);
	$end = cm_season_md($season['end']);
	return $start <= $end ? ($today >= $start && $today <= $end) : ($today >= $start || $today <= $end);
}

/** A site setting from cm_site_settings, or $default when it is missing (or the table isn't there yet). */
function cm_setting(string $name, string $default = ''): string
{
	static $cache = null;
	if ($cache === null) {
		$cache = [];
		foreach (cm_db() ? cm_rows('SELECT name, value FROM cm_site_settings') : [] as $r) {
			$cache[$r['name']] = (string)$r['value'];
		}
	}
	return $cache[$name] ?? $default;
}

/** The season the site is dressed for right now, as [key, season] or null. */
function cm_current_season(): ?array
{
	static $current = false;
	if ($current !== false) {
		return $current;
	}

	$seasons = cm_seasons();
	$choice = cm_setting('season', 'auto');

	// An admin previewing a theme on the admin panel sees it only for themselves.
	$preview = $_SESSION['season_preview'] ?? '';
	if ($preview !== '' && (isset($seasons[$preview]) || $preview === 'none') && cm_is_admin()) {
		$choice = $preview;
	}

	if ($choice === 'none') {
		return $current = null;
	}
	if (isset($seasons[$choice])) {
		return $current = [$choice, $seasons[$choice]];
	}

	// A seasonal shop the game server has open (this follows !forceseason in game).
	if (cm_db()) {
		$open = array_column(cm_rows("SELECT DISTINCT category FROM cm_shop_items WHERE enabled = 1 AND category LIKE '% Shop'"), 'category');
		foreach ($seasons as $key => $s) {
			if (in_array($s['shop'], $open, true)) {
				return $current = [$key, $s];
			}
		}
	}

	foreach ($seasons as $key => $s) {
		if (cm_season_in_dates($s)) {
			return $current = [$key, $s];
		}
	}

	return $current = null;
}

/** When a season's shop closes, as a timestamp (the end of its last day). */
function cm_season_ends(array $season): int
{
	[$m, $d] = array_map('intval', explode('/', $season['end']));
	$end = mktime(23, 59, 59, $m, $d, (int)date('Y'));
	// A season that runs over New Year ends next year when we are still in December.
	if ($end < time() - 86400 * 300) {
		$end = mktime(23, 59, 59, $m, $d, (int)date('Y') + 1);
	} elseif ($end < time() && cm_season_md($season['start']) > cm_season_md($season['end'])) {
		$end = mktime(23, 59, 59, $m, $d, (int)date('Y') + 1);
	}
	return $end;
}

/** The next time each season opens, soonest first: [[key, season, timestamp], ...]. */
function cm_upcoming_seasons(): array
{
	$list = [];
	foreach (cm_seasons() as $key => $s) {
		[$m, $d] = array_map('intval', explode('/', $s['start']));
		$t = mktime(0, 0, 0, $m, $d, (int)date('Y'));
		if ($t < strtotime('today')) {
			$t = mktime(0, 0, 0, $m, $d, (int)date('Y') + 1);
		}
		$list[] = [$key, $s, $t];
	}
	usort($list, static fn ($a, $b) => $a[2] <=> $b[2]);
	return $list;
}
