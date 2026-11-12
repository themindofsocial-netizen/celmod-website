<?php
/*
 * Shared database connection. Returns null (instead of dying) when the
 * database is unreachable so static pages keep working. The reason is kept
 * in cm_db_error() and explained on check.php.
 */

/** Last connection/query error: ['code' => '1045', 'message' => '...'] or null. */
function cm_db_error(?array $set = null): ?array
{
	static $error = null;
	if ($set !== null) {
		$error = $set;
	}
	return $error;
}

function cm_db_dsn(array $c): string
{
	$dsn = 'mysql:dbname=' . $c['name'] . ';charset=utf8mb4';
	if (!empty($c['socket'])) {
		return $dsn . ';unix_socket=' . $c['socket'];
	}
	return $dsn . ';host=' . $c['host'] . ';port=' . (int)($c['port'] ?? 3306);
}

/** Pull the MySQL error number out of a PDO message like "SQLSTATE[HY000] [1045] Access denied...". */
function cm_db_error_from(Throwable $e): array
{
	$code = preg_match('/\[(\d{4})\]/', $e->getMessage(), $m) ? $m[1] : (string)$e->getCode();
	return ['code' => $code, 'message' => $e->getMessage()];
}

function cm_db(): ?PDO
{
	static $pdo = false;

	if ($pdo !== false) {
		return $pdo;
	}

	$c = cm_config('db');

	if (!extension_loaded('pdo_mysql')) {
		cm_db_error(['code' => 'driver', 'message' => 'The pdo_mysql PHP extension is not installed.']);
		error_log('|CelMod| database connection failed: pdo_mysql is not installed');
		return $pdo = null;
	}

	try {
		$pdo = new PDO(
			cm_db_dsn($c),
			$c['user'],
			$c['pass'],
			[
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES   => false,
				PDO::ATTR_TIMEOUT            => 5,
			]
		);
	} catch (PDOException $e) {
		cm_db_error(cm_db_error_from($e));
		error_log('|CelMod| database connection failed: ' . $e->getMessage());
		$pdo = null;
	}

	return $pdo;
}

/** Run a query and return all rows ([] if the database is down). */
function cm_rows(string $sql, array $params = []): array
{
	$db = cm_db();
	if (!$db) {
		return [];
	}
	try {
		$st = $db->prepare($sql);
		$st->execute($params);
		return $st->fetchAll();
	} catch (PDOException $e) {
		// e.g. a table the game server has not created yet: show the page without data
		cm_db_error(cm_db_error_from($e));
		error_log('|CelMod| query failed: ' . $e->getMessage());
		if ($db->inTransaction()) {
			throw $e;
		}
		return [];
	}
}

/** Run a query and return the first row (null if none / database down). */
function cm_row(string $sql, array $params = []): ?array
{
	$rows = cm_rows($sql, $params);
	return $rows[0] ?? null;
}

/** Run a query and return the first column of the first row. */
function cm_value(string $sql, array $params = [], $default = 0)
{
	$row = cm_row($sql, $params);
	return $row ? array_values($row)[0] ?? $default : $default;
}

/**
 * Plain-English explanation of a connection error, for check.php.
 * MySQL error numbers: https://dev.mysql.com/doc/mysql-errors/8.0/en/
 */
function cm_db_hint(array $error): string
{
	$c = cm_config('db');
	$msg = $error['message'];

	if (!CM_CONFIG_FOUND) {
		return 'config.php was not found, so the site is using config.sample.php and its placeholder password. '
			. 'Copy config.sample.php to config.php in the website folder (next to index.php) and fill in the database login.';
	}

	switch ($error['code']) {
		case 'driver':
			return 'PHP cannot talk to MySQL. Install the extension (e.g. "apt install php-mysql") and restart PHP-FPM or Apache.';
		case '1045':
			$who = preg_match("/for user '([^']*)'@'([^']*)'/", $msg, $m) ? "'{$m[1]}'@'{$m[2]}'" : 'this user';
			return "MySQL rejected the login for $who. Either the password in config.php is wrong, or no account exists for that exact user and host. "
				. "MySQL treats 'user'@'localhost' (socket) and 'user'@'127.0.0.1' or 'user'@'%' (network) as different accounts, so create the user "
				. "for the host shown in the error: CREATE USER $who IDENTIFIED BY '...'; then run the GRANT lines from the README for it.";
		case '1044':
			return "The login works but has no access to a database called '{$c['name']}'. Check the name matches the game server's databases.cfg exactly, "
				. 'then run the GRANT lines from the README for this user.';
		case '1049':
			return "There is no database called '{$c['name']}' on that server. It must be the same database name as in the game server's databases.cfg.";
		case '1130':
			return 'MySQL does not allow this web server\'s address to connect for that user. Create the user for the web server\'s IP '
				. "(or '%'), e.g. CREATE USER 'celmod_web'@'<web server IP>' ..., and grant it access.";
		case '2002':
			if (stripos($msg, 'No such file') !== false) {
				return "PHP looked for MySQL's local socket file and it isn't there. Set 'host' to '127.0.0.1' in config.php, "
					. "or set 'socket' to the path MySQL actually uses (run: mysql -e \"SELECT @@socket\").";
			}
			return "Nothing answered at {$c['host']}:{$c['port']}. If the database is on another machine (e.g. the game server box), "
				. "use that machine's IP in config.php, make MySQL listen on the network (bind-address = 0.0.0.0 in my.cnf, then restart it) "
				. 'and open port ' . (int)$c['port'] . ' in its firewall for this web server only.';
		case '2005':
			return "The host name '{$c['host']}' could not be resolved. Use the database server's IP address.";
		case '2006':
		case '2013':
			return 'The connection was cut off or timed out, usually a firewall between this web server and MySQL.';
		case '2054':
			return 'MySQL asked for an authentication method this PHP build does not support. Update PHP, or switch the user to '
				. "mysql_native_password: ALTER USER 'celmod_web'@'host' IDENTIFIED WITH mysql_native_password BY '...';";
		case '1146':
			return 'Connected, but a CelMod table is missing. The game server creates them the first time celmod-database connects, '
				. 'so check both point at the same database, or import sql/schema.sql.';
	}
	return 'See the error message above.';
}
