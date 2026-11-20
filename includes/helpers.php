<?php

function cm_config(string $key, $default = null)
{
	global $CM_CONFIG;
	return $CM_CONFIG[$key] ?? $default;
}

/** HTML-escape. */
function h($value): string
{
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format cleers the same way the in-game HUD does (Cel_GetClientBalanceTranslated). */
function cm_cl($amount): string
{
	$n = (float)$amount;
	$units = [[1e15, 'Q'], [1e12, 'T'], [1e9, 'B'], [1e6, 'M'], [1e5, 'K']];
	foreach ($units as [$size, $suffix]) {
		if (abs($n) >= $size) {
			$div = $suffix === 'K' ? 1e3 : $size;
			return rtrim(rtrim(number_format($n / $div, 1, '.', ''), '0'), '.') . $suffix . ' CL';
		}
	}
	return (string)(int)$n . ' CL';
}

function cm_duration(int $seconds): string
{
	if ($seconds < 60) {
		return $seconds . 's';
	}
	$d = intdiv($seconds, 86400);
	$h = intdiv($seconds % 86400, 3600);
	$m = intdiv($seconds % 3600, 60);
	if ($d > 0) {
		return $d . 'd ' . $h . 'h';
	}
	if ($h > 0) {
		return $h . 'h ' . $m . 'm';
	}
	return $m . 'm';
}

function cm_ago(?string $datetime): string
{
	if (!$datetime) {
		return 'never';
	}
	$diff = time() - strtotime($datetime);
	if ($diff < 60) {
		return 'just now';
	}
	return cm_duration($diff) . ' ago';
}

function cm_is_steamid64($id): bool
{
	return is_string($id) && preg_match('/^7656119\d{10}$/', $id) === 1;
}

/** CSRF token for forms. */
function cm_csrf_token(): string
{
	if (empty($_SESSION['csrf'])) {
		$_SESSION['csrf'] = bin2hex(random_bytes(16));
	}
	return $_SESSION['csrf'];
}

function cm_csrf_check(): bool
{
	return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

function cm_flash(?string $message = null, string $class = 'h'): ?array
{
	if ($message !== null) {
		$_SESSION['flash'] = ['message' => $message, 'class' => $class];
		return null;
	}
	$f = $_SESSION['flash'] ?? null;
	unset($_SESSION['flash']);
	return $f;
}

function cm_json($data, int $status = 200): void
{
	http_response_code($status);
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	echo json_encode($data, JSON_UNESCAPED_SLASHES);
	exit;
}

/** Relative path from the current script back to the site root, e.g. "" or "../". */
function cm_base(): string
{
	$script = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '';
	$rel = ltrim(str_replace('\\', '/', substr(dirname($script), strlen(CM_ROOT))), '/');
	return $rel === '' ? '' : str_repeat('../', substr_count($rel, '/') + 1);
}

/** Live server row, plus an "online" flag based on the heartbeat age. */
function cm_server_status(?int $serverId = null): ?array
{
	$serverId = $serverId ?? (int)cm_config('server')['id'];
	$row = cm_row('SELECT *, TIMESTAMPDIFF(SECOND, updated_at, NOW()) AS age FROM cm_servers WHERE server_id = ?', [$serverId]);
	if ($row) {
		$row['online'] = (int)$row['age'] <= (int)cm_config('offline_after', 90);
	}
	return $row;
}

/**
 * The game server's address ("ip:port") for the Connect links. The website and the game server share one
 * network, so they share a public IP: it is looked up from the web server (and remembered for 10 minutes), so the
 * links follow a home connection's IP when it changes. The port comes from config.php's server address.
 * Set 'public_ip' => false in config.php's 'server' section to always use the configured address instead.
 */
function cm_server_address(): string
{
	$server = cm_config('server', []);
	$configured = (string)($server['address'] ?? '');
	$port = '27015';
	if (preg_match('/:(\d+)$/', $configured, $m)) {
		$port = $m[1];
	}
	if (($server['public_ip'] ?? true) === false) {
		return $configured;
	}
	$ip = cm_public_ip();
	if ($ip === null) {
		return $configured;
	}
	return $ip . ':' . $port;
}

/** This network's public IPv4 address, or null when it can't be found. Cached in the temp folder. */
function cm_public_ip(): ?string
{
	static $ip = false;
	if ($ip !== false) {
		return $ip;
	}

	$file = rtrim(sys_get_temp_dir(), '/\\') . '/celmod-public-ip.json';
	$cached = is_file($file) ? json_decode((string)@file_get_contents($file), true) : null;
	$now = time();

	// Fresh enough: 10 minutes after a good lookup, 2 minutes after a failed one (so a slow lookup isn't retried on
	// every page view).
	if (is_array($cached) && isset($cached['checked'])) {
		$wait = !empty($cached['ip']) ? 600 : 120;
		if ($now - (int)$cached['checked'] < $wait) {
			return $ip = (!empty($cached['ip']) ? (string)$cached['ip'] : null);
		}
	}

	$found = null;
	$context = stream_context_create(['http' => ['timeout' => 2, 'header' => "User-Agent: CelMod-Website\r\n"]]);
	foreach (['https://api.ipify.org', 'https://ipv4.icanhazip.com', 'https://checkip.amazonaws.com'] as $url) {
		$answer = trim((string)@file_get_contents($url, false, $context));
		if (filter_var($answer, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			$found = $answer;
			break;
		}
	}

	// A failed lookup keeps the last good IP (marked as checked, so it's tried again in 2 minutes).
	$keep = $found ?? (is_array($cached) && !empty($cached['ip']) ? (string)$cached['ip'] : null);
	$checked = ($found === null && $keep !== null) ? $now - 480 : $now;
	@file_put_contents($file, json_encode(['ip' => $keep ?? '', 'checked' => $checked]), LOCK_EX);

	return $ip = $keep;
}
