<?php
/*
 * The emblem list the game server uses, for the Emblem Editor: the server's collectibles.txt
 * (see cm_collectibles(): config.php's collectibles_file, the copy the game server sends to the
 * database every map, or the copy shipped in emblem-editor/collectibles.txt).
 */
require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/emblems.php';

$list = cm_collectibles();

if ($list === null) {
	http_response_code(404);
	header('Content-Type: text/plain; charset=utf-8');
	echo 'No emblem list found.';
	exit;
}

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $list['updated']) . ' GMT');
header('X-CelMod-Source: ' . $list['source']);
echo $list['text'];
