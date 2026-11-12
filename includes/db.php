<?php
/*
 * Shared database connection. Returns null (instead of dying) when the
 * database is unreachable so static pages keep working.
 */

function cm_db(): ?PDO
{
	static $pdo = false;

	if ($pdo !== false) {
		return $pdo;
	}

	$c = cm_config('db');

	try {
		$pdo = new PDO(
			sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int)($c['port'] ?? 3306), $c['name']),
			$c['user'],
			$c['pass'],
			[
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES   => false,
				PDO::ATTR_TIMEOUT            => 3,
			]
		);
	} catch (PDOException $e) {
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
	$st = $db->prepare($sql);
	$st->execute($params);
	return $st->fetchAll();
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
