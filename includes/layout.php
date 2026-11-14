<?php
/*
 * Shared page chrome: the logo, the navigation bar, the framed page and the
 * footer. The frame is the classic cmtextbg artwork, sliced into pieces
 * (images/frame/) so pages can be the classic width, wide, or phone-sized.
 */

/** Pages in the navigation bar: [label, path from the site root]. */
function cm_nav_items(): array
{
	return [
		['Home', ''],
		['Live', 'live.php'],
		['Shop', 'shop.php'],
		['Stats', 'statistics.php'],
		['Leaderboards', 'leaderboard.php'],
		['Commands', 'cmds.php'],
		['Props', 'props/'],
		['Emblems', 'emblem-editor/'],
		['Updates', 'updates.php'],
	];
}

/** Path of the current page from the site root, e.g. "shop.php" or "props/c.php". */
function cm_current_path(): string
{
	$script = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '';
	return ltrim(str_replace('\\', '/', substr($script, strlen(CM_ROOT))), '/');
}

/**
 * @param array $opts 'wide' => true for the wider frame (tables, editors, long lists).
 */
function cm_head(string $title, string $extraHead = '', array $opts = []): void
{
	$b = cm_base();
	$GLOBALS['CM_PAGE_OPTS'] = $opts;
	$season = cm_current_season();
	$classes = [!empty($opts['wide']) ? 'wide' : 'classic'];
	if ($season) {
		$classes[] = 'season';
		$classes[] = 'season-' . $season[0];
	}
	?>
<!DOCTYPE html>
<html lang="en-US">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#222222">

<title><?= h($title) ?></title>
<link rel=stylesheet href="<?= $b ?>style2014.css?v=<?= (int)@filemtime(CM_ROOT . '/style2014.css') ?>"/>
<link rel="shortcut icon" type="image/png" href="<?= $b ?>favicon.ico"/>
<meta property="og:title" content="<?= h($title) ?>">
<meta property="og:image" content="<?= $b ?>images/logo-og.png">
<?= $extraHead ?>
</head><body class="<?= h(implode(' ', $classes)) ?>"<?= $season ? ' data-season="' . h($season[0]) . '"' : '' ?> alink="#FFB200" bgcolor="#222222" vlink="#FFB200" link="#FFB200" text="#FFB200" background="<?= $b ?>images/grassfloor002a.png">
<?php
}

/** The logo and the navigation bar. ($centered is kept for older pages; everything is centered now.) */
function cm_logo(bool $centered = true): void
{
	$b = cm_base();
	$season = cm_current_season();
	echo '<div id="logo"><a href="' . ($b ?: './') . '"><img src="' . $b . 'images/logo.png" width="675" height="60" alt="|CelMod| - A Living Project"></a>'
		. ($season ? '<span class="season-badge">' . h($season[1]['greeting']) . '</span>' : '') . '</div>' . "\n";
	cm_navbar();
}

function cm_navbar(): void
{
	$b = cm_base();
	$user = cm_user();
	$server = cm_db() ? cm_server_status() : null;
	$here = $_SERVER['REQUEST_URI'] ?? '/';
	$current = cm_current_path();

	echo '<div id="navbar" class="plate">' . "\n" . '<div class="nav-links">';
	foreach (cm_nav_items() as [$label, $path]) {
		// "props/" is active on props/c.php too; "" (home) only on index.php
		$active = $path === ''
			? $current === 'index.php'
			: ($current === $path || (substr($path, -1) === '/' && strpos($current, $path) === 0));
		$href = ($b . $path) === '' ? './' : $b . $path; // "" would reload the current page
		echo '<a href="' . $href . '"' . ($active ? ' class="active"' : '') . '>' . h($label) . '</a>';
	}
	echo "</div>\n" . '<div class="nav-status">';

	if ($server && $server['online']) {
		echo '<a href="' . $b . 'live.php"><span class="grassy">&#9679;</span> <b>' . (int)$server['players'] . '/' . (int)$server['max_players'] . '</b> on <span class="h">' . h($server['map']) . '</span></a>';
	} elseif ($server) {
		echo '<a href="' . $b . 'live.php"><span class="bloody">&#9679;</span> Server offline</a>';
	} else {
		echo '<a href="' . $b . 'live.php">Live Server</a>';
	}

	echo ' <span class="splitter">|</span> ';

	if ($user) {
		echo '<a href="' . $b . 'player.php?id=' . h($user['steamid64']) . '"><b>' . h($user['name']) . '</b></a>';
		echo ' <span class="h">' . h(cm_cl($user['balance'])) . '</span>';
		echo ' <span class="splitter">|</span> <a href="' . $b . 'logout.php">Sign out</a>';
	} else {
		echo '<a href="' . $b . 'login.php?return=' . urlencode($here) . '"><b>Sign in through Steam</b></a>';
	}

	echo "</div>\n";

	// The holiday banner, while a seasonal theme is on.
	$season = cm_current_season();
	if ($season) {
		echo '<div class="season-banner"><b>' . h($season[1]['greeting']) . '</b> ' . h($season[1]['line'])
			. ' <a href="' . $b . 'shop.php?c=' . urlencode($season[1]['shop']) . '"><b>Visit the ' . h($season[1]['shop']) . '</b></a></div>' . "\n";
	}

	echo "</div>\n";
}

function cm_footer_bar(): void
{
	$b = cm_base();
	$address = cm_config('server')['address'];
	echo '<div id="footer"></div>' . "\n";
	echo '<div id="sitefoot"><b>|CelMod|</b> - A Living Project'
		. ' <span class="splitter">|</span> <a href="https://github.com/rockzehh/celmod">GitHub</a>'
		. ' <span class="splitter">|</span> <a href="steam://connect/' . h($address) . '">Connect to the server</a></div>' . "\n";
}

function cm_bg_script(): void
{
	echo '<script type="text/javascript" src="' . cm_base() . 'scripts/dynamicbg.js"></script>' . "\n";
	// Falling snow, bats, confetti or clocks while a seasonal theme is on.
	if (cm_current_season()) {
		echo '<script type="text/javascript" src="' . cm_base() . 'scripts/season.js?v=' . (int)@filemtime(CM_ROOT . '/scripts/season.js') . '"></script>' . "\n";
	}
}

/**
 * Start a standard framed page.
 * @param array $opts 'wide' => true for the wider frame.
 */
function cm_page_start(string $title, string $extraHead = '', array $opts = []): void
{
	cm_head($title, $extraHead, $opts);
	cm_logo(true);
	echo "<div id=\"main\">\n<div id=\"content\">\n";
	$flash = cm_flash();
	if ($flash) {
		echo '<p class="flash ' . h($flash['class']) . '"><b>' . h($flash['message']) . '</b></p>' . "\n";
	}
}

function cm_page_end(string $extraScripts = ''): void
{
	echo "</div>\n</div>\n";
	cm_footer_bar();
	cm_bg_script();
	echo $extraScripts;
	echo "</body>\n</html>\n";
}

function cm_db_offline_notice(): void
{
	echo '<p class="notice"><span class="bloody"><b>The |CelMod| database is offline right now.</b></span><br>Live stats will be back shortly.';
	if (cm_is_admin() || !CM_CONFIG_FOUND) {
		$err = cm_db_error();
		echo '<br><br><span class="muted">' . (!CM_CONFIG_FOUND ? 'config.php is missing.' : h('[' . ($err['code'] ?? '?') . '] ' . ($err['message'] ?? ''))) . '</span>'
			. '<br><a href="' . cm_base() . 'check.php"><b>Open the setup check</b></a>';
	}
	echo '</p>';
}
