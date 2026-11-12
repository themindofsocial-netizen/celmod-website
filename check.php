<?php
/*
 * Setup check: why can't the website reach the shared database?
 *
 *   php check.php                  (on the web server, always allowed)
 *   https://your-site/check.php    (signed-in admins from config.php, or from the server itself)
 *
 * Never prints the database password.
 */
require __DIR__ . '/includes/bootstrap.php';

$cli = PHP_SAPI === 'cli';
$local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

if (!$cli && !$local && !cm_is_admin()) {
	http_response_code(403);
	cm_page_start('|CelMod| - Setup Check');
	echo '<h2><span class="celColor">|CelMod| Setup Check</span><br></h2>';
	echo '<p>This page is for admins.<br>Sign in with a SteamID listed under <span class="h">admins</span> in config.php,<br>or run <span class="h">php check.php</span> on the web server.</p>';
	cm_page_end();
	exit;
}

$c = cm_config('db');
$checks = []; // [status ok|warn|fail, label, detail]
$add = static function (string $status, string $label, string $detail = '') use (&$checks) {
	$checks[] = [$status, $label, $detail];
};

// ---- PHP ----
$add(PHP_VERSION_ID >= 80000 ? 'ok' : 'fail', 'PHP version', PHP_VERSION . (PHP_VERSION_ID >= 80000 ? '' : ' (8.0 or newer is required)'));
$add(extension_loaded('pdo_mysql') ? 'ok' : 'fail', 'pdo_mysql extension', extension_loaded('pdo_mysql') ? 'installed' : 'missing: install php-mysql and restart PHP');
$add(extension_loaded('curl') ? 'ok' : 'warn', 'curl extension', extension_loaded('curl') ? 'installed' : 'missing: Steam sign in will not work');

// ---- Config ----
$add(CM_CONFIG_FOUND ? 'ok' : 'fail', 'config.php', CM_CONFIG_FOUND ? CM_ROOT . '/config.php' : 'NOT FOUND - using config.sample.php. Copy it to ' . CM_ROOT . '/config.php and fill it in.');
$target = !empty($c['socket']) ? 'socket ' . $c['socket'] : $c['host'] . ':' . (int)($c['port'] ?? 3306);
$add('ok', 'Connecting to', $target . ', database "' . $c['name'] . '" as user "' . $c['user'] . '"');
if (($c['pass'] ?? '') === '' || $c['pass'] === 'change-me') {
	$add('fail', 'Database password', 'still the placeholder from config.sample.php');
}

// ---- Connection ----
$db = cm_db();
if (!$db) {
	$err = cm_db_error() ?? ['code' => '?', 'message' => 'unknown error'];
	$add('fail', 'Database connection', '[' . $err['code'] . '] ' . $err['message']);
	$add('fail', 'What to do', cm_db_hint($err));
} else {
	$info = cm_row('SELECT CURRENT_USER() AS who, VERSION() AS version, @@hostname AS host, DATABASE() AS db');
	$add('ok', 'Database connection', 'connected as ' . $info['who'] . ' to ' . $info['version'] . ' on ' . $info['host'] . ', database "' . $info['db'] . '"');

	$tables = array_map(static fn ($r) => array_values($r)[0], cm_rows('SHOW TABLES'));
	$needed = ['cm_players', 'cm_purchases', 'cm_shop_items', 'cm_web_orders', 'cm_servers', 'cm_online_players', 'cm_sessions', 'cm_events', 'cm_stats_daily', 'cm_player_samples', 'cm_maps'];
	$missing = array_diff($needed, $tables);
	if ($missing) {
		$add('fail', 'Tables', 'missing: ' . implode(', ', $missing) . '. The game server creates these when celmod-database connects, so if it is connected it is probably using a different database name or server. Compare with its databases.cfg, or import sql/schema.sql.');
	} else {
		$add('ok', 'Tables', 'all ' . count($needed) . ' present');

		$servers = cm_rows('SELECT server_id, hostname, map, players, TIMESTAMPDIFF(SECOND, updated_at, NOW()) AS age FROM cm_servers ORDER BY server_id');
		$wanted = (int)cm_config('server')['id'];
		if (!$servers) {
			$add('fail', 'Game server data', 'cm_servers is empty: the game server has never written to THIS database. Check it uses the same host and database name (cm_db_status in the server console).');
		} else {
			$mine = null;
			foreach ($servers as $s) {
				$add('ok', 'Game server #' . $s['server_id'], $s['hostname'] . ' on ' . $s['map'] . ', ' . $s['players'] . ' players, last update ' . cm_duration((int)$s['age']) . ' ago');
				if ((int)$s['server_id'] === $wanted) {
					$mine = $s;
				}
			}
			if (!$mine) {
				$add('fail', 'Server id', 'config.php shows server id ' . $wanted . ' but the game server writes as ' . implode(', ', array_column($servers, 'server_id')) . '. Set server.id in config.php to match cm_db_server_id.');
			} elseif ((int)$mine['age'] > (int)cm_config('offline_after', 90)) {
				$add('warn', 'Heartbeat', 'the last update from server #' . $wanted . ' is ' . cm_duration((int)$mine['age']) . ' old, so the site shows it as offline. '
					. 'If the game server is running, its plugin has lost the database connection: check its error log and run cm_db_status.');
			} else {
				$add('ok', 'Heartbeat', 'server #' . $wanted . ' is live');
			}
		}

		$players = (int)cm_value('SELECT COUNT(*) FROM cm_players');
		$add($players ? 'ok' : 'warn', 'Players recorded', (string)$players);
	}

	// Privileges the site needs (SELECT everywhere, INSERT/UPDATE on orders)
	$grants = implode("\n", array_map(static fn ($r) => array_values($r)[0], cm_rows('SHOW GRANTS')));
	$canSelect = preg_match('/GRANT (ALL|.*SELECT)/i', $grants);
	$canOrder = preg_match('/GRANT (ALL|.*INSERT.*UPDATE|.*UPDATE.*INSERT)[^\n]*ON (\*|`?' . preg_quote($c['name'], '/') . '`?)\.(\*|`?cm_web_orders`?)/i', $grants);
	$add($canSelect ? 'ok' : 'fail', 'Read access', $canSelect ? 'yes' : 'the user cannot SELECT; run the GRANT lines from the README');
	$add($canOrder ? 'ok' : 'warn', 'Web shop orders', $canOrder ? 'can place orders' : 'cannot INSERT/UPDATE cm_web_orders, so buying on the website will fail');
}

$fails = count(array_filter($checks, static fn ($x) => $x[0] === 'fail'));

// ---- Output ----
if ($cli) {
	foreach ($checks as [$status, $label, $detail]) {
		printf("%-5s %-22s %s\n", strtoupper($status), $label, $detail);
	}
	echo $fails ? "\n$fails problem(s) found.\n" : "\nEverything looks good.\n";
	exit($fails ? 1 : 0);
}

cm_page_start('|CelMod| - Setup Check', '', ['wide' => true]);
?>
<h2><span class="celColor">|CelMod| Setup Check</span><br>
</h2>
<p><?= $fails ? '<span class="bloody"><b>' . $fails . ' problem' . ($fails === 1 ? '' : 's') . ' found.</b></span>' : '<span class="h"><b>Everything looks good.</b></span>' ?></p>
<table class="cmtable">
<?php foreach ($checks as [$status, $label, $detail]): ?>
<tr>
	<td style="width: 60px;"><b class="<?= ['ok' => 'h', 'warn' => 'splitter', 'fail' => 'bloody'][$status] ?>"><?= strtoupper($status) ?></b></td>
	<td style="width: 170px;"><b><?= h($label) ?></b></td>
	<td><?= h($detail) ?></td>
</tr>
<?php endforeach; ?>
</table>
<p class="muted">Run <span class="h">php check.php</span> on the web server for the same report. The password is never shown.</p>
<?php cm_page_end(); ?>
