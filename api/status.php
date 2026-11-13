<?php
/*
 * Live server snapshot, polled by live.php (and handy for Discord bots etc).
 * GET api/status.php[?server=1]
 */
require __DIR__ . '/../includes/bootstrap.php';

if (!cm_db()) {
	cm_json(['ok' => false, 'error' => 'database offline'], 503);
}

$serverId = isset($_GET['server']) ? (int)$_GET['server'] : (int)cm_config('server')['id'];
$server = cm_server_status($serverId);

if (!$server) {
	cm_json(['ok' => false, 'error' => 'unknown server'], 404);
}

$players = cm_rows(
	'SELECT o.steamid64, o.name, o.props, o.cels, o.balance, TIMESTAMPDIFF(SECOND, o.joined_at, NOW()) AS seconds
	 FROM cm_online_players o WHERE o.server_id = ? ORDER BY o.joined_at',
	[$serverId]
);

$events = cm_rows(
	'SELECT steamid64, name, type, detail, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS ago
	 FROM cm_events WHERE server_id = ? ORDER BY id DESC LIMIT 15',
	[$serverId]
);

$today = cm_row('SELECT connections, peak_players, props_spawned, cels_spawned FROM cm_stats_daily WHERE server_id = ? AND day = CURDATE()', [$serverId]);

cm_json([
	'ok'      => true,
	'server'  => [
		'id'          => (int)$server['server_id'],
		'online'      => $server['online'],
		'hostname'    => $server['hostname'],
		'address'     => $server['address'],
		'map'         => $server['map'],
		'players'     => $server['online'] ? (int)$server['players'] : 0,
		'max_players' => (int)$server['max_players'],
		'total_props' => (int)$server['total_props'],
		'total_cels'  => (int)$server['total_cels'],
		'version'     => $server['version'],
		'map_seconds' => $server['map_started'] ? max(0, time() - strtotime($server['map_started'])) : null,
		'updated_ago' => (int)$server['age'],
	],
	'players' => $server['online'] ? array_map(static function ($p) {
		return [
			'steamid64' => $p['steamid64'],
			'name'      => $p['name'],
			'props'     => (int)$p['props'],
			'cels'      => (int)$p['cels'],
			'balance'   => cm_cl($p['balance']),
			'time'      => cm_duration((int)$p['seconds']),
		];
	}, $players) : [],
	'events'  => array_map(static function ($e) {
		return [
			'steamid64' => $e['steamid64'],
			'name'      => $e['name'],
			'type'      => $e['type'],
			'detail'    => $e['detail'],
			'ago'       => (int)$e['ago'] < 60 ? 'just now' : cm_duration((int)$e['ago']) . ' ago',
		];
	}, $events),
	'today'   => array_map('intval', $today ?: ['connections' => 0, 'peak_players' => 0, 'props_spawned' => 0, 'cels_spawned' => 0]),
]);
