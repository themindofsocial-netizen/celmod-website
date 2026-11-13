<?php
/*
 * Emblem suggestions from the editor (JSON).
 *
 *   POST {action: "submit", kv: "<one emblem block>", note: "..."}
 *   POST {action: "withdraw", id: 12}
 *
 * Signed-in players who have joined the server only. Suggestions are stored in
 * cm_emblem_suggestions for admins to review; nothing is ever written to the server's
 * collectibles.txt from here.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/emblems.php';

const CM_SUGGEST_MAX_PENDING = 3;   // open suggestions per player
const CM_SUGGEST_MAX_PER_DAY = 10;
const CM_SUGGEST_MIN_GAP = 60;      // seconds between suggestions

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	cm_json(['ok' => false, 'error' => 'POST only.'], 405);
}

$in = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($in)) {
	cm_json(['ok' => false, 'error' => 'Bad request.'], 400);
}

$user = cm_user();
if (!$user) {
	cm_json(['ok' => false, 'error' => 'Sign in through Steam to suggest emblems.'], 401);
}
if (!isset($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($_SESSION['csrf'], (string)$_SERVER['HTTP_X_CSRF_TOKEN'])) {
	cm_json(['ok' => false, 'error' => 'Your session expired. Reload the page and try again.'], 403);
}
$db = cm_db();
if (!$db) {
	cm_json(['ok' => false, 'error' => 'The database is offline right now.'], 503);
}
if (!empty($user['_new'])) {
	cm_json(['ok' => false, 'error' => 'Join the server at least once before suggesting emblems.'], 403);
}

$sid = $user['steamid64'];

if (($in['action'] ?? '') === 'withdraw') {
	$st = $db->prepare("UPDATE cm_emblem_suggestions SET status = 'withdrawn' WHERE id = ? AND steamid64 = ? AND status = 'pending'");
	$st->execute([(int)($in['id'] ?? 0), $sid]);
	cm_json(['ok' => (bool)$st->rowCount(), 'error' => $st->rowCount() ? null : 'That suggestion has already been reviewed.']);
}

if (($in['action'] ?? '') !== 'submit') {
	cm_json(['ok' => false, 'error' => 'Unknown action.'], 400);
}

// Rate limits
$lock = 'celmod_suggest_' . $sid;
if (!cm_value('SELECT GET_LOCK(?, 5)', [$lock])) {
	cm_json(['ok' => false, 'error' => 'Busy, try again.'], 429);
}
try {
	$stats = cm_row("SELECT SUM(status = 'pending') AS pending, SUM(created_at >= NOW() - INTERVAL 1 DAY) AS today,
		TIMESTAMPDIFF(SECOND, MAX(created_at), NOW()) AS since FROM cm_emblem_suggestions WHERE steamid64 = ?", [$sid]);
	if ((int)($stats['pending'] ?? 0) >= CM_SUGGEST_MAX_PENDING) {
		cm_json(['ok' => false, 'error' => 'You already have ' . CM_SUGGEST_MAX_PENDING . ' suggestions waiting for review. Withdraw one or wait for an answer.'], 429);
	}
	if ((int)($stats['today'] ?? 0) >= CM_SUGGEST_MAX_PER_DAY) {
		cm_json(['ok' => false, 'error' => 'That is enough suggestions for today, thank you! Try again tomorrow.'], 429);
	}
	if ($stats['since'] !== null && (int)$stats['since'] < CM_SUGGEST_MIN_GAP) {
		cm_json(['ok' => false, 'error' => 'Please wait a minute between suggestions.'], 429);
	}

	try {
		$emblem = cm_emblem_clean((string)($in['kv'] ?? ''));
	} catch (RuntimeException $e) {
		cm_json(['ok' => false, 'error' => $e->getMessage()], 422);
	}

	$note = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string)($in['note'] ?? '')) ?? '');
	$note = mb_substr($note, 0, 500);

	$db->prepare('INSERT INTO cm_emblem_suggestions (steamid64, player_name, emblem_id, title, kv, note) VALUES (?, ?, ?, ?, ?, ?)')
		->execute([$sid, mb_substr((string)$user['name'], 0, 128), $emblem['id'], mb_substr($emblem['name'], 0, 64), $emblem['kv'], $note]);

	cm_json([
		'ok'      => true,
		'id'      => (int)$db->lastInsertId(),
		'message' => 'Thanks! "' . $emblem['name'] . '" was sent to the admins.' . ($emblem['dropped'] ? ' Left out keys the server does not use: ' . implode(', ', $emblem['dropped']) . '.' : ''),
	]);
} finally {
	cm_value('SELECT RELEASE_LOCK(?)', [$lock]);
}
