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

/**
 * Which route this machine (or container) would use for $ip, from /proc/net/route.
 * Returns ['iface' => 'eth1', 'net' => '192.168.0.0/20', 'via' => null|'gateway IP'] or null.
 */
function cm_route_for(string $ip, string $table = '/proc/net/route'): ?array
{
	$lines = @file($table, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$target = ip2long($ip);
	if (!$lines || $target === false) {
		return null;
	}
	$hex = static fn (string $h) => unpack('V', pack('H*', $h))[1]; // little-endian hex -> int
	$best = null;
	foreach (array_slice($lines, 1) as $line) {
		$f = preg_split('/\s+/', trim($line));
		if (count($f) < 8) {
			continue;
		}
		$dest = $hex($f[1]);
		$mask = $hex($f[7]);
		if (($target & $mask) !== ($dest & $mask)) {
			continue;
		}
		$bits = substr_count(decbin($mask), '1');
		if ($best === null || $bits > $best['bits']) {
			$gw = $hex($f[2]);
			$best = ['iface' => $f[0], 'net' => long2ip($dest) . '/' . $bits, 'via' => $gw ? long2ip($gw) : null, 'bits' => $bits];
		}
	}
	return $best;
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

// ---- Network: can this web server reach the database port at all? ----
if (empty($c['socket'])) {
	$host = (string)$c['host'];
	$port = (int)($c['port'] ?? 3306);
	$ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
	$loopback = $host === 'localhost' || strpos($ip, '127.') === 0 || $ip === '::1';

	if ($loopback) {
		$add('warn', 'Database host', "'$host' means THIS web server. If MySQL runs on another machine, set 'host' in config.php to that machine's IP address.");
	} elseif ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
		$add('fail', 'Database host', "'$host' could not be resolved to an IP address. Use the database server's IP.");
	}

	if ($host !== 'localhost') {
		$route = null;
		$start = microtime(true);
		$sock = @fsockopen($ip, $port, $errno, $errstr, 4);
		$ms = (int)round((microtime(true) - $start) * 1000);
		if ($sock) {
			fclose($sock);
			$add('ok', 'Network', "port $port on $ip is reachable ({$ms} ms)");
		} elseif ($errno === 111 || stripos($errstr, 'refused') !== false) {
			$add('fail', 'Network', "$ip refused the connection on port $port. Either MySQL is only listening on 127.0.0.1 "
				. "(set bind-address = 0.0.0.0 or its LAN IP in my.cnf / 50-server.cnf, then restart MySQL), or a firewall on it is rejecting this web server "
				. "(allow it, e.g. \"ufw allow from <web server IP> to any port $port\").");
		} elseif ($errno === 113 || stripos($errstr, 'unreachable') !== false || stripos($errstr, 'no route') !== false) {
			$route = cm_route_for($ip);
			$docker = is_file('/.dockerenv') || is_file('/run/.containerenv');
			$direct = $route && $route['via'] === null && $route['bits'] > 0;
			// This container's own address: if it sits inside the same subnet as the database, the
			// container is on a macvlan/ipvlan LAN network (Unraid br0) rather than a Docker bridge.
			$self = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
			[$netIp, $bits] = $direct ? explode('/', $route['net']) : ['', 0];
			$mask = $bits ? (-1 << (32 - (int)$bits)) & 0xFFFFFFFF : 0;
			$sameLan = $direct && ip2long($self) !== false && ((ip2long($self) & $mask) === (ip2long($netIp) & $mask));
			if ($docker && $sameLan) {
				$add('fail', 'Network', "$ip is unreachable. This site runs in a container with its own LAN address ($self, a macvlan/ipvlan network "
					. "such as Unraid's br0). Containers on those networks cannot reach their own Docker host, so if the database runs on that host "
					. "(e.g. MariaDB in Docker on the same Unraid server) every connection fails like this, while other machines connect fine. "
					. "On Unraid: Settings > Docker > stop Docker > Advanced View > \"Host access to custom networks\" = Enabled > apply and start Docker. "
					. "See README: Troubleshooting for other options.");
			} elseif ($docker && $direct) {
				$add('fail', 'Network', "$ip is unreachable. This site runs in a container, and the container thinks $ip is on its own network "
					. "{$route['net']} ({$route['iface']}), so it looks for it there instead of going out to your LAN. A Docker network on the host "
					. "is using an address range that overlaps your LAN. Fix: give that Docker network a different subnet (see README: Troubleshooting).");
			} elseif ($direct) {
				$add('fail', 'Network', "$ip is unreachable ({$ms} ms). This machine expects it directly on {$route['net']} via {$route['iface']} but nothing answered. "
					. "Check the database server is on and on the same network, or that a Docker/VPN interface on this machine isn't claiming that range (ip route).");
			} else {
				$add('fail', 'Network', "$ip is unreachable ({$ms} ms, $errstr). A router or a firewall on the database server is rejecting this machine "
					. "(firewalld rejects this way: firewall-cmd --permanent --add-rich-rule='rule family=ipv4 source address=<web server IP> port port=$port protocol=tcp accept' && firewall-cmd --reload).");
			}
		} else {
			$add('fail', 'Network', "no answer from $ip:$port after {$ms} ms ($errstr). A firewall is dropping this web server's traffic, or the IP is wrong. "
				. "On the database server allow port $port from this web server, e.g. \"ufw allow from <web server IP> to any port $port\".");
		}

		$route = $route ?? ($loopback ? null : cm_route_for($ip));
		if ($route) {
			$add('ok', 'Route to database', "$ip goes out " . $route['iface'] . ($route['via'] ? ' via gateway ' . $route['via'] : ' directly') . ' (route ' . $route['net'] . ')'
				. ((is_file('/.dockerenv') || is_file('/run/.containerenv')) ? ', inside a container' : ''));
		}
	}
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
