<?php
/*
 * The emblem list the game server uses, for the Emblem Editor.
 * Reads `collectibles_file` from config.php (point it at the server's
 * addons/sourcemod/data/celmod/collectibles.txt if the site runs on the same
 * machine), otherwise the copy shipped in emblem-editor/collectibles.txt.
 */
require __DIR__ . '/../includes/bootstrap.php';

$file = cm_config('collectibles_file') ?: CM_ROOT . '/emblem-editor/collectibles.txt';

if (!is_file($file) || !is_readable($file)) {
	http_response_code(404);
	header('Content-Type: text/plain; charset=utf-8');
	echo 'No emblem list found.';
	exit;
}

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT');
readfile($file);
