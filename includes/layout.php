<?php
/*
 * Shared page chrome. These render exactly what the old scripts/header*.js
 * and scripts/footer.js used to inject, plus a small account/server bar.
 */

function cm_head(string $title, string $extraHead = ''): void
{
	$b = cm_base();
	?>
<!DOCTYPE html>
<html lang="en-US">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">

<title><?= h($title) ?></title>
<link rel=stylesheet href="<?= $b ?>style2014.css"/>
<link rel="shortcut icon" type="image/png" href="<?= $b ?>favicon.ico"/>
<?= $extraHead ?>
</head><body alink="#FFB200" bgcolor="#222222" vlink="#FFB200" link="#FFB200" text="#FFB200" background="<?= $b ?>images/grassfloor002a.png">
<?php
}

/** The logo (centered like header_centered.js, or left like header.js) + the user bar. */
function cm_logo(bool $centered = true): void
{
	$b = cm_base();
	$img = '<img src="' . $b . 'images/logo.png" style="border: solid 1px #000;"></img>';
	if ($centered) {
		echo '<a href="' . ($b ?: './') . '"><div id="logo" style="text-align: center; margin-left: auto; margin-right: auto;">' . $img . '</div></a>' . "\n";
	} else {
		echo '<a href="' . ($b ?: './') . '">' . $img . '</a>' . "\n";
	}
	cm_userbar($centered);
}

function cm_userbar(bool $centered = true): void
{
	$b = cm_base();
	$user = cm_user();
	$server = cm_db() ? cm_server_status() : null;
	$here = $_SERVER['REQUEST_URI'] ?? '/';

	echo '<div id="userbar"' . ($centered ? '' : ' class="left"') . '>';

	if ($server && $server['online']) {
		echo '<a href="' . $b . 'live.php"><span class="grassy">&#9679;</span> <b>' . (int)$server['players'] . '/' . (int)$server['max_players'] . '</b> on <span class="h">' . h($server['map']) . '</span></a>';
	} elseif ($server) {
		echo '<a href="' . $b . 'live.php"><span class="bloody">&#9679;</span> Server offline</a>';
	} else {
		echo '<a href="' . $b . 'live.php">Live Server</a>';
	}

	echo ' <span class="splitter">|</span> ';

	if ($user) {
		echo 'Signed in as <a href="' . $b . 'player.php?id=' . h($user['steamid64']) . '"><b>' . h($user['name']) . '</b></a>';
		echo ' - <span class="h">' . h(cm_cl($user['balance'])) . '</span>';
		echo ' <span class="splitter">|</span> <a href="' . $b . 'logout.php">Sign out</a>';
	} else {
		echo '<a href="' . $b . 'login.php?return=' . urlencode($here) . '"><b>Sign in through Steam</b></a>';
	}

	echo "</div>\n";
}

function cm_footer_bar(): void
{
	$b = cm_base();
	echo '<div id="footer" style="text-align: center; margin-left: auto; margin-right: auto; background-image: url(' . $b . 'images/cmtextbg_bottom.png); height: 25px; width: 677px;"></div>' . "\n";
}

function cm_bg_script(): void
{
	echo '<script type="text/javascript" src="' . cm_base() . 'scripts/dynamicbg.js"></script>' . "\n";
}

/** Start a standard boxed page (the look used by almost every page). */
function cm_page_start(string $title, string $extraHead = ''): void
{
	cm_head($title, $extraHead);
	cm_logo(true);
	echo "<div id=\"main\">\n<div id=\"content\">\n";
	$flash = cm_flash();
	if ($flash) {
		echo '<p class="flash"><b><span class="' . h($flash['class']) . '">' . h($flash['message']) . '</span></b></p>' . "\n";
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
	echo '<p><span class="bloody"><b>The |CelMod| database is offline right now.</b></span><br>Live stats will be back shortly.</p>';
}
