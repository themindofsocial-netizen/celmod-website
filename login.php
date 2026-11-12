<?php
require __DIR__ . '/includes/bootstrap.php';

$site = rtrim(cm_config('site_url'), '/');

// Only allow returning to a page on this site.
$return = (string)($_GET['return'] ?? '');
if ($return === '' || preg_match('#^(?:[a-z]+:)?//#i', $return) || strpos($return, "\\") !== false) {
	$return = './';
}

if (isset($_GET['openid_mode'])) {
	$steamid = cm_steam_verify($_GET);

	if (!$steamid) {
		cm_flash('Steam sign in failed, please try again.', 'bloody');
		header('Location: ./');
		exit;
	}

	session_regenerate_id(true);
	$_SESSION['steamid64'] = $steamid;

	// Fill in name/avatar if we have an API key (the game server fills these in too).
	$profile = cm_steam_profile($steamid);
	if ($profile && cm_db()) {
		$_SESSION['steam_name'] = $profile['personaname'] ?? null;
		cm_db()->prepare('UPDATE cm_players SET avatar_url = ? WHERE steamid64 = ?')
			->execute([$profile['avatarfull'] ?? '', $steamid]);
	}

	$known = cm_db() && cm_value('SELECT 1 FROM cm_players WHERE steamid64 = ?', [$steamid]);
	cm_flash($known ? 'Signed in. Welcome back!' : 'Signed in! Join the server once to link your in-game stats.');
	header('Location: ' . $return);
	exit;
}

header('Location: ' . cm_steam_login_url($site . '/login.php?return=' . urlencode($return)));
