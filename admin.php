<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/updates.php';
require __DIR__ . '/includes/drops.php';

if (!cm_is_admin()) {
	http_response_code(403);
	cm_page_start('|CelMod| - Admin');
	echo '<h2><span class="celColor">|CelMod| Admin</span><br></h2><p><span class="bloody"><b>Admins only.</b></span></p>';
	cm_page_end();
	exit;
}

$tab = in_array($_GET['tab'] ?? '', ['orders', 'suggestions', 'announcements', 'updates', 'season', 'drops', 'bugs', 'disputes'], true) ? $_GET['tab'] : 'orders';
$db = cm_db();

// Announcements: written here, shown in chat by the game server (celmod-database polls cm_announcements).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db && isset($_POST['announce'])) {
	if (!cm_csrf_check()) {
		cm_flash('Your session expired, please try again.', 'bloody');
	} else {
		$action = (string)$_POST['announce'];
		$id = (int)($_POST['id'] ?? 0);
		try {
			if ($action === 'create') {
				// One line of chat text. Color tags such as {green} and {default} work in game.
				$message = trim(preg_replace('/\s+/u', ' ', (string)($_POST['message'] ?? '')));
				$message = mb_substr($message, 0, 255);
				$toTime = static function ($v): ?string {
					$v = trim((string)$v);
					if ($v === '') {
						return null;
					}
					$t = strtotime($v);
					return $t ? date('Y-m-d H:i:s', $t) : null;
				};
				$server = (string)($_POST['server_id'] ?? '');
				if ($message === '') {
					cm_flash('Write a message first.', 'bloody');
				} else {
					$st = $db->prepare('INSERT INTO cm_announcements (message, server_id, repeat_minutes, show_on_join, active, starts_at, ends_at, created_by) VALUES (?, ?, ?, ?, 1, ?, ?, ?)');
					$st->execute([
						$message,
						$server === '' ? null : (int)$server,
						max(0, min(1440, (int)($_POST['repeat_minutes'] ?? 0))),
						empty($_POST['show_on_join']) ? 0 : 1,
						$toTime($_POST['starts_at'] ?? ''),
						$toTime($_POST['ends_at'] ?? ''),
						cm_user()['steamid64'],
					]);
					cm_flash('Announcement posted. The server shows it within about 30 seconds.', 'h');
				}
			} elseif ($action === 'toggle') {
				$db->prepare('UPDATE cm_announcements SET active = 1 - active WHERE id = ?')->execute([$id]);
				cm_flash('Announcement updated.', 'h');
			} elseif ($action === 'delete') {
				$db->prepare('DELETE FROM cm_announcements WHERE id = ?')->execute([$id]);
				cm_flash('Announcement deleted.', 'h');
			}
		} catch (Throwable $e) {
			cm_flash('The database refused that: the website needs INSERT, UPDATE and DELETE on cm_announcements (see the README).', 'bloody');
		}
	}
	header('Location: admin.php?tab=announcements');
	exit;
}

// Updates: posted here, shown on updates.php (cm_updates, created by the game server's celmod-database).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db && isset($_POST['update'])) {
	if (!cm_csrf_check()) {
		cm_flash('Your session expired, please try again.', 'bloody');
	} else {
		$action = (string)$_POST['update'];
		$id = (int)($_POST['id'] ?? 0);
		$body = trim(str_replace("\r", '', (string)($_POST['body'] ?? '')));
		$date = (string)($_POST['posted_on'] ?? '');
		$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date) ? $date : date('Y-m-d');
		try {
			if ($action === 'create' || $action === 'save') {
				if ($body === '') {
					cm_flash('Write the update first.', 'bloody');
				} elseif ($action === 'create') {
					$db->prepare('INSERT INTO cm_updates (posted_on, body, published, created_by) VALUES (?, ?, ?, ?)')
						->execute([$date, mb_substr($body, 0, 8000), empty($_POST['draft']) ? 1 : 0, cm_user()['steamid64']]);
					cm_flash(empty($_POST['draft']) ? 'Update posted.' : 'Update saved as a draft.', 'h');
				} else {
					$db->prepare('UPDATE cm_updates SET posted_on = ?, body = ? WHERE id = ?')->execute([$date, mb_substr($body, 0, 8000), $id]);
					cm_flash('Update saved.', 'h');
				}
			} elseif ($action === 'toggle') {
				$db->prepare('UPDATE cm_updates SET published = 1 - published WHERE id = ?')->execute([$id]);
				cm_flash('Update ' . ((int)cm_value('SELECT published FROM cm_updates WHERE id = ?', [$id]) ? 'published.' : 'hidden.'), 'h');
			} elseif ($action === 'delete') {
				$db->prepare('DELETE FROM cm_updates WHERE id = ?')->execute([$id]);
				cm_flash('Update deleted.', 'h');
			}
		} catch (Throwable $e) {
			cm_flash('The database refused that: the website needs INSERT, UPDATE and DELETE on cm_updates, and the game server must have created the table (see the README).', 'bloody');
		}
	}
	header('Location: admin.php?tab=updates');
	exit;
}

// Emblem drops: which emblems can drop (cm_drop_pool) and scheduled drops (cm_drop_schedule).
// The game server's celmod-database reads both about every 30 seconds.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db && isset($_POST['drops'])) {
	$action = (string)$_POST['drops'];
	if (!cm_csrf_check()) {
		cm_flash('Your session expired, please try again.', 'bloody');
	} else {
		try {
			if ($action === 'pool' || $action === 'reset') {
				// Only emblems that differ from collectibles.txt get a row, so a later change to the file still counts.
				$emblems = cm_drop_emblems();
				$on = array_flip(array_map('strtolower', array_map('strval', (array)($_POST['drop'] ?? []))));
				$set = $db->prepare('INSERT INTO cm_drop_pool (emblem_id, droppable, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE droppable = VALUES(droppable), updated_by = VALUES(updated_by), updated_at = NOW()');
				$clear = $db->prepare('DELETE FROM cm_drop_pool WHERE emblem_id = ?');
				$changed = 0;
				$db->beginTransaction();
				$saveGifts = static function (string $value) use ($db): void {
					$db->prepare("INSERT INTO cm_site_settings (name, value) VALUES ('gift_list', ?) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()")->execute([$value]);
				};
				if ($action === 'reset') {
					$db->exec('DELETE FROM cm_drop_pool');
					$saveGifts('');
				} else {
					// Build emblems: kept as the server's own list (empty) until it differs from it.
					$gifts = array_values(array_filter(array_keys($emblems), static fn ($id) => in_array($id, array_map('strtolower', array_map('strval', (array)($_POST['gift'] ?? []))), true)));
					$default = array_values(array_filter(cm_gift_default(), static fn ($id) => isset($emblems[$id])));
					$same = count($gifts) === count($default) && !array_diff($gifts, $default);
					$saveGifts($same ? '' : implode(',', $gifts));
					if (!$same) {
						$changed += count(array_diff($gifts, $default)) + count(array_diff($default, $gifts));
					}
					foreach ($emblems as $id => $e) {
						$want = isset($on[$id]);
						if ($want === $e['default']) {
							$clear->execute([$id]);
						} else {
							$set->execute([$id, $want ? 1 : 0, cm_user()['steamid64']]);
							$changed++;
						}
					}
				}
				$db->commit();
				cm_flash($action === 'reset' ? 'Every emblem is back to the server\'s own settings.' : 'Saved (' . $changed . ' change' . ($changed === 1 ? '' : 's') . ' from the server\'s own settings). The server picks it up within about 30 seconds.', 'h');
			} elseif ($action === 'schedule' || $action === 'now') {
				$boxes = max(1, min(30, (int)($_POST['boxes'] ?? 5)));
				$repeat = $action === 'now' ? 0 : max(0, min(10080, (int)($_POST['repeat_minutes'] ?? 0)));
				$server = (string)($_POST['server_id'] ?? '');
				$note = mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($_POST['note'] ?? ''))), 0, 128);
				$when = trim((string)($_POST['run_at'] ?? ''));
				$t = $when === '' ? false : strtotime($when);
				if ($action === 'schedule' && $when !== '' && !$t) {
					cm_flash('That date and time could not be read.', 'bloody');
				} else {
					// No time (or Drop now) means as soon as the server next checks.
					$runAt = ($action === 'now' || !$t) ? null : date('Y-m-d H:i:s', $t);
					$st = $db->prepare('INSERT INTO cm_drop_schedule (run_at, boxes, repeat_minutes, server_id, note, created_by) VALUES (COALESCE(?, NOW()), ?, ?, ?, ?, ?)');
					$st->execute([$runAt, $boxes, $repeat, $server === '' ? null : (int)$server, $note, cm_user()['steamid64']]);
					cm_flash($runAt === null ? 'Drop of ' . $boxes . ' box' . ($boxes === 1 ? '' : 'es') . ' sent: it starts within about 30 seconds.' : 'Drop scheduled.', 'h');
				}
			} elseif ($action === 'toggle') {
				$db->prepare('UPDATE cm_drop_schedule SET active = 1 - active WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
				cm_flash('Scheduled drop updated.', 'h');
			} elseif ($action === 'delete') {
				$db->prepare('DELETE FROM cm_drop_schedule WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
				cm_flash('Scheduled drop deleted.', 'h');
			}
		} catch (Throwable $e) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			cm_flash('The database refused that: the website needs INSERT, UPDATE and DELETE on cm_drop_pool and cm_drop_schedule (and INSERT, UPDATE on cm_site_settings), and the game server must have created the tables (see the README).', 'bloody');
		}
	}
	header('Location: admin.php?tab=drops');
	exit;
}

// Announcement rotation: every N minutes the server shows one announcement, shuffled through the whole list.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db && isset($_POST['rotation'])) {
	if (!cm_csrf_check()) {
		cm_flash('Your session expired, please try again.', 'bloody');
	} else {
		$minutes = max(0, min(1440, (int)$_POST['rotation']));
		try {
			$db->prepare("INSERT INTO cm_site_settings (name, value) VALUES ('announce_rotation', ?) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()")->execute([(string)$minutes]);
			cm_flash($minutes ? 'Rotation on: one announcement every ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ', in random order.' : 'Rotation off.', 'h');
		} catch (Throwable $e) {
			cm_flash('The database refused that: the website needs INSERT and UPDATE on cm_site_settings (see the README).', 'bloody');
		}
	}
	header('Location: admin.php?tab=announcements');
	exit;
}

// Bug reports: sent in game with !reportbug (cm_bug_reports, created by celmod-database).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db && isset($_POST['bug'])) {
	$show = in_array($_POST['show'] ?? '', ['open', 'fixed', 'closed', 'all'], true) ? $_POST['show'] : 'open';
	if (!cm_csrf_check()) {
		cm_flash('Your session expired, please try again.', 'bloody');
	} else {
		$action = (string)$_POST['bug'];
		$id = (int)($_POST['id'] ?? 0);
		try {
			if (in_array($action, ['open', 'fixed', 'closed'], true)) {
				$db->prepare('UPDATE cm_bug_reports SET status = ?, admin_note = ? WHERE id = ?')->execute([$action, mb_substr(trim((string)($_POST['admin_note'] ?? '')), 0, 255), $id]);
				cm_flash('Bug report #' . $id . ' marked ' . $action . '.', 'h');
			} elseif ($action === 'delete') {
				$db->prepare('DELETE FROM cm_bug_reports WHERE id = ?')->execute([$id]);
				cm_flash('Bug report #' . $id . ' deleted.', 'h');
			}
		} catch (Throwable $e) {
			cm_flash('The database refused that: the website needs UPDATE and DELETE on cm_bug_reports, and the game server must have created the table (see the README).', 'bloody');
		}
	}
	header('Location: admin.php?tab=bugs&show=' . $show);
	exit;
}

// Disputes: a player reporting another with !dispute in game (cm_disputes, created by celmod-database).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db && isset($_POST['dispute'])) {
	$show = in_array($_POST['show'] ?? '', ['open', 'resolved', 'closed', 'all'], true) ? $_POST['show'] : 'open';
	if (!cm_csrf_check()) {
		cm_flash('Your session expired, please try again.', 'bloody');
	} else {
		$action = (string)$_POST['dispute'];
		$id = (int)($_POST['id'] ?? 0);
		try {
			if (in_array($action, ['open', 'resolved', 'closed'], true)) {
				$db->prepare('UPDATE cm_disputes SET status = ?, admin_note = ? WHERE id = ?')->execute([$action, mb_substr(trim((string)($_POST['admin_note'] ?? '')), 0, 255), $id]);
				cm_flash('Dispute #' . $id . ' marked ' . $action . '.', 'h');
			} elseif ($action === 'delete') {
				$db->prepare('DELETE FROM cm_disputes WHERE id = ?')->execute([$id]);
				cm_flash('Dispute #' . $id . ' deleted.', 'h');
			}
		} catch (Throwable $e) {
			cm_flash('The database refused that: the website needs UPDATE and DELETE on cm_disputes, and the game server must have created the table (see the README).', 'bloody');
		}
	}
	header('Location: admin.php?tab=disputes&show=' . $show);
	exit;
}

// Seasonal theme: for everyone (stored in cm_site_settings), or a preview only the admin sees.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['season'])) {
	$choice = (string)$_POST['season'];
	$valid = $choice === 'auto' || $choice === 'none' || isset(cm_seasons()[$choice]);
	if (!cm_csrf_check() || !$valid) {
		cm_flash('Your session expired, please try again.', 'bloody');
	} elseif (!empty($_POST['preview'])) {
		$_SESSION['season_preview'] = $choice === 'auto' ? '' : $choice;
		cm_flash($choice === 'auto' ? 'Preview off: you see what everyone sees.' : 'Previewing the ' . ($choice === 'none' ? 'plain' : cm_seasons()[$choice]['name']) . ' look. Only you see it.', 'h');
	} elseif ($db) {
		try {
			$db->prepare('INSERT INTO cm_site_settings (name, value) VALUES (\'season\', ?) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()')->execute([$choice]);
			unset($_SESSION['season_preview']);
			cm_flash('Seasonal theme set to ' . ($choice === 'auto' ? 'automatic' : ($choice === 'none' ? 'off' : cm_seasons()[$choice]['name'])) . ' for everyone.', 'h');
		} catch (Throwable $e) {
			cm_flash('The database refused that: the website needs INSERT and UPDATE on cm_site_settings (see the README).', 'bloody');
		}
	}
	header('Location: admin.php?tab=season');
	exit;
}

// Review a suggestion. This only records the decision; adding an emblem to the server is done by
// hand (copy the block into collectibles.txt), so nothing here can change the server's emblems.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
	$status = ['accept' => 'accepted', 'reject' => 'rejected'][$_POST['decision'] ?? ''] ?? null;
	if (!cm_csrf_check() || !$status) {
		cm_flash('Your session expired, please try again.', 'bloody');
	} else {
		$st = $db->prepare("UPDATE cm_emblem_suggestions SET status = ?, admin_note = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ? AND status = 'pending'");
		$st->execute([$status, mb_substr(trim((string)($_POST['admin_note'] ?? '')), 0, 255), cm_user()['steamid64'], (int)($_POST['id'] ?? 0)]);
		cm_flash($st->rowCount() ? 'Suggestion ' . $status . '.' : 'That suggestion was already reviewed or withdrawn.', $st->rowCount() ? 'h' : 'bloody');
	}
	header('Location: admin.php?tab=suggestions&show=' . urlencode((string)($_POST['show'] ?? 'pending')));
	exit;
}

$show = in_array($_GET['show'] ?? '', ['pending', 'accepted', 'rejected', 'all'], true) ? $_GET['show'] : 'pending';
$pendingCount = (int)cm_value("SELECT COUNT(*) FROM cm_emblem_suggestions WHERE status = 'pending'");
try {
	$openBugs = $db ? (int)$db->query("SELECT COUNT(*) FROM cm_bug_reports WHERE status = 'open'")->fetchColumn() : 0;
} catch (Throwable $e) {
	$openBugs = 0; // the game server hasn't created the table yet
}
try {
	$openDisputes = $db ? (int)$db->query("SELECT COUNT(*) FROM cm_disputes WHERE status = 'open'")->fetchColumn() : 0;
} catch (Throwable $e) {
	$openDisputes = 0;
}

cm_page_start('|CelMod| - Admin', '', ['wide' => true]);
?>
<h2><span class="celColor">|CelMod| Admin</span><br>
</h2>
<div class="tabs">
	<a href="?tab=orders" class="<?= $tab === 'orders' ? 'active' : '' ?>">Web Orders</a>
	<a href="?tab=suggestions" class="<?= $tab === 'suggestions' ? 'active' : '' ?>">Emblem Suggestions<?= $pendingCount ? ' (' . $pendingCount . ')' : '' ?></a>
	<a href="?tab=announcements" class="<?= $tab === 'announcements' ? 'active' : '' ?>">Announcements</a>
	<a href="?tab=updates" class="<?= $tab === 'updates' ? 'active' : '' ?>">Updates</a>
	<a href="?tab=season" class="<?= $tab === 'season' ? 'active' : '' ?>">Seasonal Theme</a>
	<a href="?tab=drops" class="<?= $tab === 'drops' ? 'active' : '' ?>">Emblem Drops</a>
	<a href="?tab=bugs" class="<?= $tab === 'bugs' ? 'active' : '' ?>">Bug Reports<?= $openBugs ? ' (' . $openBugs . ')' : '' ?></a>
	<a href="?tab=disputes" class="<?= $tab === 'disputes' ? 'active' : '' ?>">Disputes<?= $openDisputes ? ' (' . $openDisputes . ')' : '' ?></a>
	<a href="check.php">Setup Check</a>
</div>

<?php if ($tab === 'orders'):
	$orders = cm_rows('SELECT o.*, p.name, s.display_name FROM cm_web_orders o LEFT JOIN cm_players p ON p.steamid64 = o.steamid64 LEFT JOIN cm_shop_items s ON s.item = o.item ORDER BY o.id DESC LIMIT 50');
?>
<p class="title">:Latest Web Orders:</p>
<table class="cmtable">
<tr><th>#</th><th>Player</th><th>Item</th><th class="num">Price</th><th>Status</th><th>Placed</th></tr>
<?php foreach ($orders as $o): ?>
<tr>
	<td><?= (int)$o['id'] ?></td>
	<td><a href="player.php?id=<?= h($o['steamid64']) ?>"><?= h($o['name'] ?? $o['steamid64']) ?></a></td>
	<td><span class="h"><?= h($o['display_name'] ?? $o['item']) ?></span> <span class="muted"><?= h($o['item']) ?></span></td>
	<td class="num"><?= h(cm_cl($o['price'])) ?></td>
	<td><?= h($o['status']) ?><?php if ($o['message'] !== ''): ?><br><span class="muted"><?= h($o['message']) ?></span><?php endif; ?></td>
	<td><?= h(cm_ago($o['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
</table>

<?php elseif ($tab === 'updates'):
	$posted = $db ? cm_rows('SELECT u.*, p.name FROM cm_updates u LEFT JOIN cm_players p ON p.steamid64 = u.created_by ORDER BY u.posted_on DESC, u.id DESC LIMIT 100') : [];
	$editId = (int)($_GET['edit'] ?? 0);
	$editing = null;
	foreach ($posted as $u) {
		if ((int)$u['id'] === $editId) $editing = $u;
	}
?>
<p class="notice">Updates posted here show on the <a href="updates.php"><b>Updates</b></a> page with the older ones, newest first.
Write plain text: each new line starts an indented line under the date. <span class="h">!commands</span> and
<span class="h">/commands</span> turn green by themselves, and <span class="h">**bold**</span>, <span class="h">*italics*</span>
and <span class="h">~~struck out~~</span> work.</p>

<div class="admin-grid">
<form method="post" class="panel" style="text-align:left">
	<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
	<input type="hidden" name="update" value="<?= $editing ? 'save' : 'create' ?>">
	<?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>
	<p style="margin:0 0 6px"><b class="celColor"><?= $editing ? 'Edit update #' . (int)$editing['id'] : 'New update' ?></b></p>
	<label>Date <input type="date" name="posted_on" value="<?= h($editing['posted_on'] ?? date('Y-m-d')) ?>"></label>
	<textarea name="body" id="update-body" rows="8" required maxlength="8000" class="kvbox" style="color:var(--gold);font-family:Pu,sans-serif;margin-top:6px" placeholder="Land skins are back! Pick one with !landskin.&#10;Hoopies bounce players again."><?= h($editing['body'] ?? '') ?></textarea>
	<div style="display:flex;gap:8px;align-items:center;margin-top:6px;flex-wrap:wrap">
		<button class="cmbutton go"><?= $editing ? 'Save' : 'Post' ?></button>
		<?php if (!$editing): ?><label class="muted"><input type="checkbox" name="draft" value="1"> Save as a hidden draft</label><?php else: ?><a href="admin.php?tab=updates" class="muted">Cancel</a><?php endif; ?>
	</div>
</form>
<div class="panel" style="text-align:left">
	<p style="margin:0 0 6px"><b class="celColor">Preview</b></p>
	<table class="changelog cmtable"><tr><td><b>-=- <span id="preview-date"><?= h(date('m/d/y')) ?></span>: </b><span id="update-preview" class="muted">Start typing...</span></td></tr></table>
</div>
</div>

<table class="cmtable">
<tr><th>Date</th><th>Update</th><th>By</th><th></th></tr>
<?php if (!$posted): ?>
<tr><td colspan="4" class="muted">Nothing posted from here yet. The older updates are in includes/updates_static.php.</td></tr>
<?php endif; ?>
<?php foreach ($posted as $u): ?>
<tr<?= $u['published'] ? '' : ' class="muted"' ?>>
	<td style="white-space:nowrap"><?= h(date('m/d/y', strtotime($u['posted_on']))) ?><?= $u['published'] ? '' : '<br><span class="pill red">Hidden</span>' ?></td>
	<td style="text-align:left"><?= cm_update_format($u['body']) ?></td>
	<td><?= h($u['name'] ?? ($u['created_by'] ?? '')) ?></td>
	<td style="white-space:nowrap">
		<a class="cmbutton" href="admin.php?tab=updates&amp;edit=<?= (int)$u['id'] ?>">Edit</a>
		<form method="post" style="display:inline;margin:0">
			<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
			<input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
			<button class="cmbutton" name="update" value="toggle"><?= $u['published'] ? 'Hide' : 'Publish' ?></button>
			<button class="cmbutton" name="update" value="delete" onclick="return confirm('Delete this update?')">Delete</button>
		</form>
	</td>
</tr>
<?php endforeach; ?>
</table>
<script>
(function () {
	var body = document.getElementById('update-body'), out = document.getElementById('update-preview');
	var date = document.querySelector('input[name=posted_on]'), outDate = document.getElementById('preview-date');
	function esc(s) { return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
	function render() {
		var t = esc(body.value.replace(/\r/g, '').trim());
		t = t.replace(/\*\*(.+?)\*\*/g, '<b>$1</b>').replace(/(^|[^\w*])\*(?!\s)(.+?)(?!\s)\*(?![\w*])/g, '$1<i>$2</i>')
			.replace(/~~(.+?)~~/g, '<span class="strikeout">$1</span>').replace(/(^|[\s(>])([!\/][a-z][a-z0-9_]*)/gi, '$1<span class="highlight">$2</span>');
		var lines = t.split('\n').map(function (l) { return l.trim(); }).filter(Boolean);
		out.className = lines.length ? '' : 'muted';
		out.innerHTML = lines.length ? lines.join('<br><span class="indent"></span>') : 'Start typing...';
		if (date.value) { var p = date.value.split('-'); outDate.textContent = p[1] + '/' + p[2] + '/' + p[0].slice(2); }
	}
	body.addEventListener('input', render);
	date.addEventListener('change', render);
	render();
})();
</script>

<?php elseif ($tab === 'season'):
	$setting = cm_setting('season', 'auto');
	$active = cm_current_season();
	$preview = $_SESSION['season_preview'] ?? '';
?>
<p class="notice">Each holiday gets its own look on the website while its seasonal shop is open: the same frame,
with the holiday's colours, a banner and a little falling snow, bats, confetti or clocks behind the page.
On <span class="h">automatic</span> the look follows the game server: when a seasonal shop is open there (by date, or with
<span class="h">!forceseason</span>), the site dresses up for it. Otherwise it goes by the shop's dates.</p>

<p>Showing now: <b class="h"><?= $active ? h($active[1]['name']) : 'no seasonal theme' ?></b><?= $preview !== '' ? ' <span class="pill gold">Your preview</span>' : '' ?>
<br><span class="muted">Setting for everyone: <?= h($setting === 'auto' ? 'automatic' : ($setting === 'none' ? 'off' : (cm_seasons()[$setting]['name'] ?? $setting))) ?></span></p>

<div class="admin-grid">
<form method="post" class="panel">
	<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
	<input type="hidden" name="preview" value="1">
	<p style="margin:0 0 6px"><b class="celColor">Preview a look (only you see it)</b></p>
	<div class="btngrid" style="margin:4px 0">
	<?php foreach (cm_seasons() as $key => $s): ?>
		<button class="cmbutton" name="season" value="<?= h($key) ?>"><?= h($s['name']) ?></button>
	<?php endforeach; ?>
		<button class="cmbutton" name="season" value="none">Plain</button>
		<button class="cmbutton" name="season" value="auto">Stop previewing</button>
	</div>
</form>
<form method="post" class="panel">
	<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
	<p style="margin:0 0 6px"><b class="celColor">Set the look for everyone</b></p>
	<div class="btngrid" style="margin:4px 0">
		<button class="cmbutton<?= $setting === 'auto' ? ' go' : '' ?>" name="season" value="auto">Automatic</button>
	<?php foreach (cm_seasons() as $key => $s): ?>
		<button class="cmbutton<?= $setting === $key ? ' go' : '' ?>" name="season" value="<?= h($key) ?>"><?= h($s['name']) ?></button>
	<?php endforeach; ?>
		<button class="cmbutton<?= $setting === 'none' ? ' go' : '' ?>" name="season" value="none">Off</button>
	</div>
</form>
</div>

<table class="cmtable">
<tr><th>Season</th><th>Shop dates</th><th>Next opens</th></tr>
<?php foreach (cm_upcoming_seasons() as [$key, $s, $t]): ?>
<tr><td><b><?= h($s['name']) ?></b></td><td><?= h($s['start'] . ' - ' . $s['end']) ?></td><td><?= cm_season_in_dates($s) ? '<span class="grassy"><b>Open now</b></span>' : h(date('F j, Y', $t)) ?></td></tr>
<?php endforeach; ?>
</table>

<?php elseif ($tab === 'drops'):
	$emblems = cm_drop_emblems();
	$overrides = [];
	$poolOk = true;
	try {
		foreach ($db ? $db->query('SELECT emblem_id, droppable FROM cm_drop_pool')->fetchAll(PDO::FETCH_ASSOC) : [] as $r) {
			$overrides[strtolower($r['emblem_id'])] = (int)$r['droppable'] === 1;
		}
		$schedule = $db ? $db->query('SELECT d.*, p.name FROM cm_drop_schedule d LEFT JOIN cm_players p ON p.steamid64 = d.created_by ORDER BY d.active DESC, d.run_at, d.id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC) : [];
	} catch (Throwable $e) {
		$poolOk = false;
		$schedule = [];
	}
	$servers = $db ? cm_rows('SELECT server_id, hostname FROM cm_servers ORDER BY server_id') : [];
	$giftDefault = array_flip(cm_gift_default());
	$giftNow = array_flip(cm_gift_current());
	$tiers = array_fill(0, count(CM_RARITIES), []);
	foreach ($emblems as $id => $e) {
		$e['on'] = $overrides[$id] ?? $e['default'];
		$e['gift'] = isset($giftNow[$id]);
		$e['giftDefault'] = isset($giftDefault[$id]);
		$e['changed'] = (isset($overrides[$id]) && $overrides[$id] !== $e['default']) || $e['gift'] !== $e['giftDefault'];
		$tiers[$e['rarity']][$id] = $e;
	}
	foreach ($tiers as &$list) {
		uasort($list, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));
	}
	unset($list);
	$tierChances = ['60%', '25%', '10%', '4%', '1%'];
?>
<p class="notice">Emblem boxes fall from the sky during a drop. Each box first rolls a rarity
(<?= implode(', ', array_map(static fn ($n, $c) => h($n) . ' ' . $c, CM_RARITIES, $tierChances)) ?> by default,
<span class="h">cm_drop_tier_chances</span>), then picks one of the emblems ticked <span class="h">Drops</span> of that rarity.
Untick an emblem to keep it out of drops (for event exclusives), or tick one that collectibles.txt leaves out.
<span class="h">Build</span> picks the emblems the one-time free emblem that appears in a player's build can be
(the server's <span class="h">cm_emblem_gift_list</span> until you change it). The server picks up changes within
about 30 seconds. Times use the database server's clock.</p>

<?php if (!$poolOk): ?>
<p class="flash bloody">The drop tables aren't there yet. They are created by the game server's <span class="h">celmod-database</span>
the next time it connects (or run <span class="h">sql/schema.sql</span>), and the website needs the grants in the README.</p>
<?php endif; ?>

<div class="admin-grid">
<form method="post" class="panel" style="text-align:left">
	<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
	<p style="margin:0 0 6px"><b class="celColor">Schedule a drop</b></p>
	<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
		<label>Boxes <input type="number" name="boxes" min="1" max="30" value="5" style="width:55px"></label>
		<label>At <input type="datetime-local" name="run_at"></label>
		<label>Repeat every <input type="number" name="repeat_minutes" min="0" max="10080" value="0" style="width:65px"> min <span class="muted">(0 = once)</span></label>
		<label>Server <select name="server_id">
			<option value="">All servers</option>
			<?php foreach ($servers as $sv): ?>
			<option value="<?= (int)$sv['server_id'] ?>"><?= h('#' . $sv['server_id'] . ' ' . $sv['hostname']) ?></option>
			<?php endforeach; ?>
		</select></label>
		<input name="note" maxlength="128" placeholder="Note (only admins see it)" class="kvnote" style="flex:1;min-width:160px">
	</div>
	<div style="margin-top:8px">
		<button class="cmbutton go" name="drops" value="schedule">Schedule</button>
		<button class="cmbutton" name="drops" value="now" title="Starts within about 30 seconds">Drop now</button>
		<span class="muted">No time = as soon as the server checks. A drop needs a living player under open sky.</span>
	</div>
</form>
<div class="panel" style="text-align:left">
	<p style="margin:0 0 6px"><b class="celColor">In the drop pool</b></p>
	<table class="cmtable" style="margin:0">
	<tr><th>Rarity</th><th class="num">Can drop</th><th class="num">Chance</th></tr>
	<?php foreach ($tiers as $r => $list): $n = count(array_filter($list, static fn ($e) => $e['on'])); ?>
	<tr><td><span class="rarity r<?= $r ?>"><?= h(CM_RARITIES[$r]) ?></span></td><td class="num"><?= $n ?> / <?= count($list) ?></td><td class="num"><?= $n ? $tierChances[$r] : '<span class="muted" title="Nothing of this rarity can drop, so its rolls fall to the next rarity down">-</span>' ?></td></tr>
	<?php endforeach; ?>
	</table>
	<p class="muted" style="margin:6px 0 0">Build emblems: <b class="h"><?= count(array_intersect_key($giftNow, $emblems)) ?></b> to pick from<?= cm_setting('gift_list', '') === '' ? ' (the server\'s own list)' : ' (set here)' ?>.</p>
</div>
</div>

<div class="table-scroll">
<table class="cmtable">
<tr><th>#</th><th>When</th><th class="num">Boxes</th><th>Repeats</th><th>Last run</th><th>By</th><th></th></tr>
<?php if (!$schedule): ?>
<tr><td colspan="7" class="muted">No drops scheduled.</td></tr>
<?php endif; ?>
<?php foreach ($schedule as $d): ?>
<tr<?= $d['active'] ? '' : ' class="muted"' ?>>
	<td><?= (int)$d['id'] ?></td>
	<td style="text-align:left"><?= $d['active'] ? h($d['run_at']) : ($d['repeat_minutes'] ? 'paused' : 'done') ?><?= $d['note'] !== '' ? '<br><span class="muted">' . h($d['note']) . '</span>' : '' ?></td>
	<td class="num"><?= (int)$d['boxes'] ?></td>
	<td><?= $d['repeat_minutes'] ? 'every ' . h(cm_duration((int)$d['repeat_minutes'] * 60)) : 'once' ?><br><span class="muted"><?= $d['server_id'] === null ? 'all servers' : 'server #' . (int)$d['server_id'] ?></span></td>
	<td><?= $d['last_run'] ? h(cm_ago($d['last_run'])) : '<span class="muted">never</span>' ?></td>
	<td><?= h($d['name'] ?? ($d['created_by'] ?? '')) ?></td>
	<td>
		<form method="post" style="display:inline;margin:0">
			<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
			<input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
			<?php if ($d['repeat_minutes'] || $d['active']): ?><button class="cmbutton" name="drops" value="toggle"><?= $d['active'] ? 'Pause' : 'Resume' ?></button><?php endif; ?>
			<button class="cmbutton" name="drops" value="delete" onclick="return confirm('Delete this scheduled drop?')">Delete</button>
		</form>
	</td>
</tr>
<?php endforeach; ?>
</table>
</div>

<?php if (!$emblems): ?>
<p class="flash bloody">The emblem list couldn't be read: the game server hasn't sent its collectibles.txt yet, and there's no copy in emblem-editor/collectibles.txt.</p>
<?php else: $src = cm_collectibles(); ?>
<p class="muted" style="margin:10px 0 0">Emblem list: <?= [
	'file'   => 'the game server\'s collectibles.txt (collectibles_file in config.php)',
	'server' => 'sent by the game server',
	'copy'   => '<span class="bloody">the copy saved with the website</span> (the game server hasn\'t sent its own yet: it does when celmod-database connects)',
][$src['source']] ?>, updated <?= h(cm_ago(date('Y-m-d H:i:s', $src['updated']))) ?>.</p>
<form method="post" id="droppool">
	<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
	<div class="drop-tools">
		<input type="search" id="dropfind" placeholder="Find an emblem..." class="kvnote">
		<label class="muted"><input type="checkbox" id="droponly"> Only changed</label>
		<span class="spacer"></span>
		<button class="cmbutton go" name="drops" value="pool">Save</button>
		<button class="cmbutton" name="drops" value="reset" onclick="return confirm('Put every emblem back to the server\'s own settings (collectibles.txt and cm_emblem_gift_list)?')">Reset to the server's settings</button>
	</div>
	<?php foreach ($tiers as $r => $list): if (!$list) continue; ?>
	<div class="drop-tier">
		<p class="drop-head"><span class="rarity r<?= $r ?>"><?= h(CM_RARITIES[$r]) ?></span>
			<span class="muted"><?= count($list) ?> emblem<?= count($list) === 1 ? '' : 's' ?></span>
			<span class="muted">Drops:</span><a href="#" class="muted" data-tier="<?= $r ?>" data-box="drop" data-set="1">all</a> <a href="#" class="muted" data-tier="<?= $r ?>" data-box="drop" data-set="0">none</a>
			<span class="muted" style="margin-left:8px">Build:</span><a href="#" class="muted" data-tier="<?= $r ?>" data-box="gift" data-set="1">all</a> <a href="#" class="muted" data-tier="<?= $r ?>" data-box="gift" data-set="0">none</a></p>
		<div class="drop-grid">
		<?php foreach ($list as $id => $e): $sw = cm_drop_swatch($e['color']); ?>
			<div class="drop-card<?= $e['changed'] ? ' changed' : '' ?><?= $e['on'] || $e['gift'] ? '' : ' off' ?>" data-tier="<?= $r ?>" data-find="<?= h(strtolower($e['name'] . ' ' . $id . ' ' . $e['exclusive'])) ?>">
				<span class="swatch"<?= $sw ? ' style="background:' . h($sw) . '"' : '' ?>></span>
				<span class="drop-name"><b><?= h($e['name']) ?></b><br><span class="muted"><?= h($id) ?> &middot; weight <?= (int)$e['weight'] ?></span>
				<span class="drop-ticks">
					<label title="Can come from emblem drops"><input type="checkbox" name="drop[]" value="<?= h($id) ?>"<?= $e['on'] ? ' checked' : '' ?> data-default="<?= $e['default'] ? 1 : 0 ?>"> Drops</label>
					<label title="Can be the free emblem that appears in a player's build"><input type="checkbox" name="gift[]" value="<?= h($id) ?>"<?= $e['gift'] ? ' checked' : '' ?> data-default="<?= $e['giftDefault'] ? 1 : 0 ?>"> Build</label>
				</span>
				<?php if ($e['exclusive'] !== ''): ?><br><span class="pill gold" title="exclusive in collectibles.txt"><?= h($e['exclusive']) ?></span><?php endif; ?>
				<?php if ($e['changed']): ?><span class="pill green">changed</span><?php endif; ?></span>
			</div>
		<?php endforeach; ?>
		</div>
	</div>
	<?php endforeach; ?>
</form>
<script>
(function () {
	var form = document.getElementById('droppool'), find = document.getElementById('dropfind'), only = document.getElementById('droponly');
	function mark(box) {
		var card = box.closest('.drop-card'), changed = false, on = false;
		card.querySelectorAll('input').forEach(function (b) {
			changed = changed || (b.checked ? '1' : '0') !== b.getAttribute('data-default');
			on = on || b.checked;
		});
		card.classList.toggle('changed', changed);
		card.classList.toggle('off', !on);
	}
	function filter() {
		var q = find.value.trim().toLowerCase();
		form.querySelectorAll('.drop-card').forEach(function (c) {
			c.style.display = (q === '' || c.getAttribute('data-find').indexOf(q) !== -1) && (!only.checked || c.classList.contains('changed')) ? '' : 'none';
		});
	}
	form.addEventListener('change', function (e) {
		if (e.target.name === 'drop[]' || e.target.name === 'gift[]') { mark(e.target); }
	});
	form.querySelectorAll('a[data-tier]').forEach(function (a) {
		a.addEventListener('click', function (e) {
			e.preventDefault();
			form.querySelectorAll('.drop-card[data-tier="' + a.getAttribute('data-tier') + '"] input[name="' + a.getAttribute('data-box') + '[]"]').forEach(function (b) {
				if (b.closest('.drop-card').style.display !== 'none') { b.checked = a.getAttribute('data-set') === '1'; mark(b); }
			});
		});
	});
	find.addEventListener('input', filter);
	only.addEventListener('change', filter);
})();
</script>
<?php endif; ?>

<?php elseif ($tab === 'bugs'):
	$bugShow = in_array($_GET['show'] ?? '', ['open', 'fixed', 'closed', 'all'], true) ? $_GET['show'] : 'open';
	try {
		$bugs = $db ? cm_rows('SELECT * FROM cm_bug_reports' . ($bugShow === 'all' ? '' : ' WHERE status = ' . $db->quote($bugShow)) . ' ORDER BY id DESC LIMIT 200') : [];
		$bugsOk = true;
	} catch (Throwable $e) {
		$bugs = [];
		$bugsOk = false;
	}
?>
<p class="notice">Players report bugs in game with <span class="h">!reportbug &lt;what happened&gt;</span>. Each report keeps
the map and where they were standing. Admins on the server see new reports in chat, and every report is also written to
<span class="h">data/celmod/bugreports.txt</span> on the game server.</p>

<?php if (!$bugsOk): ?>
<p class="flash bloody">The bug report table isn't there yet: it's created by the game server's <span class="h">celmod-database</span>
the next time it connects (or run <span class="h">sql/schema.sql</span>).</p>
<?php endif; ?>

<div class="btngrid">
<?php foreach (['open', 'fixed', 'closed', 'all'] as $f): ?>
	<a href="?tab=bugs&amp;show=<?= $f ?>"<?= $bugShow === $f ? ' style="color:#71E100"' : '' ?>><?= ucfirst($f) ?></a>
<?php endforeach; ?>
</div>

<div class="table-scroll">
<table class="cmtable">
<tr><th>#</th><th>Report</th><th>Player</th><th>Where</th><th></th></tr>
<?php if (!$bugs): ?>
<tr><td colspan="5" class="muted">No <?= $bugShow === 'all' ? '' : h($bugShow) . ' ' ?>bug reports.</td></tr>
<?php endif; ?>
<?php foreach ($bugs as $b): ?>
<tr<?= $b['status'] === 'open' ? '' : ' class="muted"' ?>>
	<td><?= (int)$b['id'] ?></td>
	<td style="text-align:left"><?= h($b['message']) ?>
		<?php if ($b['status'] !== 'open'): ?><br><span class="pill <?= $b['status'] === 'fixed' ? 'green' : '' ?>"><?= h($b['status']) ?></span><?php endif; ?>
		<?php if ($b['admin_note'] !== ''): ?><br><span class="muted">Note: <?= h($b['admin_note']) ?></span><?php endif; ?>
		<br><span class="muted"><?= h(cm_ago($b['created_at'])) ?></span></td>
	<td><a href="player.php?id=<?= h($b['steamid64']) ?>"><?= h($b['name']) ?></a></td>
	<td><?= h($b['map']) ?><br><span class="muted" title="setpos <?= h(sprintf('%.0f %.0f %.0f', $b['pos_x'], $b['pos_y'], $b['pos_z'])) ?>"><?= h(sprintf('%.0f %.0f %.0f', $b['pos_x'], $b['pos_y'], $b['pos_z'])) ?></span>
		<?= $b['server_id'] !== null ? '<br><span class="muted">server #' . (int)$b['server_id'] . '</span>' : '' ?></td>
	<td>
		<form method="post" style="margin:0">
			<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
			<input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
			<input type="hidden" name="show" value="<?= h($bugShow) ?>">
			<input name="admin_note" maxlength="255" value="<?= h($b['admin_note']) ?>" placeholder="Note (optional)" class="kvnote" style="width:140px">
			<div style="margin-top:4px">
			<?php if ($b['status'] !== 'fixed'): ?><button class="cmbutton go" name="bug" value="fixed">Fixed</button><?php endif; ?>
			<?php if ($b['status'] !== 'closed'): ?><button class="cmbutton" name="bug" value="closed" title="Not a bug, or won't fix">Close</button><?php endif; ?>
			<?php if ($b['status'] !== 'open'): ?><button class="cmbutton" name="bug" value="open">Reopen</button><?php endif; ?>
			<button class="cmbutton" name="bug" value="delete" onclick="return confirm('Delete this bug report?')">Delete</button>
			</div>
		</form>
	</td>
</tr>
<?php endforeach; ?>
</table>
</div>

<?php elseif ($tab === 'disputes'):
	$dShow = in_array($_GET['show'] ?? '', ['open', 'resolved', 'closed', 'all'], true) ? $_GET['show'] : 'open';
	try {
		$disputes = $db ? cm_rows('SELECT * FROM cm_disputes' . ($dShow === 'all' ? '' : ' WHERE status = ' . $db->quote($dShow)) . ' ORDER BY id DESC LIMIT 200') : [];
		$disputesOk = true;
	} catch (Throwable $e) {
		$disputes = [];
		$disputesOk = false;
	}
	// How many disputes each reported player has had, to spot repeat trouble.
	$against = [];
	try {
		foreach ($db ? cm_rows('SELECT target_steamid64, COUNT(*) AS n FROM cm_disputes GROUP BY target_steamid64') : [] as $r) {
			$against[$r['target_steamid64']] = (int)$r['n'];
		}
	} catch (Throwable $e) {
	}
?>
<p class="notice">Players report someone causing them problems in game with <span class="h">!dispute</span>: they pick the
player, then what they're doing (or <span class="h">Other</span> and type it). Admins on the server see new disputes in chat,
and each one is also written to <span class="h">data/celmod/disputes.txt</span> on the game server.</p>

<?php if (!$disputesOk): ?>
<p class="flash bloody">The disputes table isn't there yet: it's created by the game server's <span class="h">celmod-database</span>
the next time it connects (or run <span class="h">sql/schema.sql</span>).</p>
<?php endif; ?>

<div class="btngrid">
<?php foreach (['open', 'resolved', 'closed', 'all'] as $f): ?>
	<a href="?tab=disputes&amp;show=<?= $f ?>"<?= $dShow === $f ? ' style="color:#71E100"' : '' ?>><?= ucfirst($f) ?></a>
<?php endforeach; ?>
</div>

<div class="table-scroll">
<table class="cmtable">
<tr><th>#</th><th>Problem</th><th>Reported player</th><th>From</th><th></th></tr>
<?php if (!$disputes): ?>
<tr><td colspan="5" class="muted">No <?= $dShow === 'all' ? '' : h($dShow) . ' ' ?>disputes.</td></tr>
<?php endif; ?>
<?php foreach ($disputes as $d): $n = $against[$d['target_steamid64']] ?? 0; ?>
<tr<?= $d['status'] === 'open' ? '' : ' class="muted"' ?>>
	<td><?= (int)$d['id'] ?></td>
	<td style="text-align:left"><b><?= h($d['reason']) ?></b><?= $d['message'] !== '' ? '<br>' . h($d['message']) : '' ?>
		<?php if ($d['status'] !== 'open'): ?><br><span class="pill <?= $d['status'] === 'resolved' ? 'green' : '' ?>"><?= h($d['status']) ?></span><?php endif; ?>
		<?php if ($d['admin_note'] !== ''): ?><br><span class="muted">Note: <?= h($d['admin_note']) ?></span><?php endif; ?>
		<br><span class="muted"><?= h($d['map']) ?> &middot; <?= h(cm_ago($d['created_at'])) ?></span></td>
	<td><?= $d['target_steamid64'] !== '' ? '<a href="player.php?id=' . h($d['target_steamid64']) . '">' . h($d['target_name']) . '</a>' : h($d['target_name']) ?>
		<?php if ($n > 1): ?><br><span class="pill red" title="Disputes against this player"><?= $n ?> disputes</span><?php endif; ?></td>
	<td><a href="player.php?id=<?= h($d['steamid64']) ?>"><?= h($d['name']) ?></a></td>
	<td>
		<form method="post" style="margin:0">
			<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
			<input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
			<input type="hidden" name="show" value="<?= h($dShow) ?>">
			<input name="admin_note" maxlength="255" value="<?= h($d['admin_note']) ?>" placeholder="Note (optional)" class="kvnote" style="width:140px">
			<div style="margin-top:4px">
			<?php if ($d['status'] !== 'resolved'): ?><button class="cmbutton go" name="dispute" value="resolved">Resolved</button><?php endif; ?>
			<?php if ($d['status'] !== 'closed'): ?><button class="cmbutton" name="dispute" value="closed" title="Nothing to act on">Close</button><?php endif; ?>
			<?php if ($d['status'] !== 'open'): ?><button class="cmbutton" name="dispute" value="open">Reopen</button><?php endif; ?>
			<button class="cmbutton" name="dispute" value="delete" onclick="return confirm('Delete this dispute?')">Delete</button>
			</div>
		</form>
	</td>
</tr>
<?php endforeach; ?>
</table>
</div>

<?php elseif ($tab === 'announcements'):
	$announcements = $db ? cm_rows('SELECT a.*, p.name FROM cm_announcements a LEFT JOIN cm_players p ON p.steamid64 = a.created_by ORDER BY a.active DESC, a.id DESC LIMIT 100') : [];
	$servers = $db ? cm_rows('SELECT server_id, hostname FROM cm_servers ORDER BY server_id') : [];
?>
<p class="notice">Announcements show in chat on the game server as <span class="h">|CM| your message</span>
within about 30 seconds. A new one is shown to everyone once; set <span class="h">Repeat</span> to show it again every few
minutes, and <span class="h">Show on join</span> to show it to players as they join. Color tags such as
<span class="h">{green}</span> and <span class="h">{default}</span> work. Times use the database server's clock.</p>

<?php $rotation = (int)cm_setting('announce_rotation', '0'); ?>
<form method="post" class="panel" style="text-align:left">
	<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
	<p style="margin:0 0 6px"><b class="celColor">Rotation</b>
	<?= $rotation ? '<span class="pill green">On: every ' . $rotation . ' min</span>' : '<span class="pill">Off</span>' ?></p>
	<p class="muted" style="margin:0 0 6px">Shows one announcement every few minutes, going through the whole list of active
	announcements in random order before any is shown again. Their own Repeat settings still work as well.</p>
	<label>One announcement every <input type="number" name="rotation" min="0" max="1440" value="<?= $rotation ?: 10 ?>" style="width:65px"> min</label>
	<button class="cmbutton go">Save</button>
	<?php if ($rotation): ?><button class="cmbutton" name="rotation" value="0">Turn off</button><?php endif; ?>
</form>

<form method="post" class="panel" style="text-align:left">
	<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
	<input type="hidden" name="announce" value="create">
	<p style="margin:0 0 6px"><b class="celColor">New announcement</b></p>
	<input name="message" maxlength="255" required placeholder="e.g. Update 3.1 is out: build teams, music cels and the Christmas Shop!" class="kvnote" style="width:100%;box-sizing:border-box">
	<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-top:8px">
		<label>Repeat every <input type="number" name="repeat_minutes" min="0" max="1440" value="0" style="width:60px"> min <span class="muted">(0 = once)</span></label>
		<label><input type="checkbox" name="show_on_join" value="1"> Show on join</label>
		<label>Server <select name="server_id">
			<option value="">All servers</option>
			<?php foreach ($servers as $sv): ?>
			<option value="<?= (int)$sv['server_id'] ?>"><?= h('#' . $sv['server_id'] . ' ' . $sv['hostname']) ?></option>
			<?php endforeach; ?>
		</select></label>
		<label>From <input type="datetime-local" name="starts_at"></label>
		<label>Until <input type="datetime-local" name="ends_at"></label>
		<button class="cmbutton">Post</button>
	</div>
</form>

<table class="cmtable">
<tr><th>#</th><th>Message</th><th>Shown</th><th>When</th><th>By</th><th></th></tr>
<?php if (!$announcements): ?>
<tr><td colspan="6" class="muted">No announcements yet.</td></tr>
<?php endif; ?>
<?php foreach ($announcements as $a): ?>
<tr<?= $a['active'] ? '' : ' class="muted"' ?>>
	<td><?= (int)$a['id'] ?></td>
	<td style="text-align:left"><?= h($a['message']) ?></td>
	<td><?= $a['repeat_minutes'] ? 'every ' . (int)$a['repeat_minutes'] . ' min' : 'once' ?><?= $a['show_on_join'] ? '<br>+ on join' : '' ?><br><span class="muted"><?= $a['server_id'] === null ? 'all servers' : 'server #' . (int)$a['server_id'] ?></span></td>
	<td><?= $a['starts_at'] ? 'from ' . h($a['starts_at']) . '<br>' : '' ?><?= $a['ends_at'] ? 'until ' . h($a['ends_at']) : ($a['starts_at'] ? '' : 'now') ?></td>
	<td><?= h($a['name'] ?? ($a['created_by'] ?? '')) ?><br><span class="muted"><?= h(cm_ago($a['created_at'])) ?></span></td>
	<td>
		<form method="post" style="display:inline;margin:0">
			<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
			<input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
			<button class="cmbutton" name="announce" value="toggle"><?= $a['active'] ? 'Pause' : 'Resume' ?></button>
			<button class="cmbutton" name="announce" value="delete" onclick="return confirm('Delete this announcement?')">Delete</button>
		</form>
	</td>
</tr>
<?php endforeach; ?>
</table>

<?php else:
	$where = $show === 'all' ? '1' : 's.status = ' . $db->quote($show);
	$suggestions = $db ? cm_rows("SELECT s.* FROM cm_emblem_suggestions s WHERE $where ORDER BY s.id DESC LIMIT 50") : [];
?>
<p class="notice">Suggestions are never added to the server automatically. To add one, copy its block into the
<span class="h">"Emblems"</span> section of <span class="h">collectibles.txt</span> on the game server. Every block here was
rebuilt from checked values, uses a new id, and can't replace an existing emblem.</p>
<div class="btngrid">
<?php foreach (['pending', 'accepted', 'rejected', 'all'] as $f): ?>
	<a href="?tab=suggestions&amp;show=<?= $f ?>"<?= $show === $f ? ' style="color:#71E100"' : '' ?>><?= ucfirst($f) ?></a>
<?php endforeach; ?>
</div>
<?php if (!$suggestions): ?>
<p class="muted">Nothing here.</p>
<?php endif; ?>
<?php foreach ($suggestions as $sg): ?>
<div class="panel" style="text-align:left">
	<p style="margin:0 0 6px"><b class="celColor"><?= h($sg['title']) ?></b> <span class="muted"><?= h($sg['emblem_id']) ?> - #<?= (int)$sg['id'] ?></span><br>
	by <a href="player.php?id=<?= h($sg['steamid64']) ?>"><b><?= h($sg['player_name']) ?></b></a>, <?= h(cm_ago($sg['created_at'])) ?>
	- <span class="<?= ['accepted' => 'grassy', 'rejected' => 'bloody', 'withdrawn' => 'muted'][$sg['status']] ?? 'highlight' ?>"><?= h(ucfirst($sg['status'])) ?></span>
	<?php if ($sg['admin_note'] !== ''): ?><span class="muted">(<?= h($sg['admin_note']) ?>)</span><?php endif; ?></p>
	<?php if ($sg['note'] !== ''): ?><p style="margin:0 0 6px">"<?= nl2br(h($sg['note'])) ?>"</p><?php endif; ?>
	<textarea readonly class="kvbox" rows="<?= min(16, substr_count($sg['kv'], "\n") + 1) ?>"><?= h($sg['kv']) ?></textarea>
	<div class="row" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-top:6px">
		<button class="cmbutton" type="button" onclick="var t=this.closest('.panel').querySelector('textarea');t.select();navigator.clipboard&&navigator.clipboard.writeText(t.value);this.textContent='Copied';">Copy block</button>
		<a class="cmbutton" href="emblem-editor/?suggestion=<?= (int)$sg['id'] ?>">Open in the editor</a>
	<?php if ($sg['status'] === 'pending'): ?>
		<form method="post" style="display:flex;gap:6px;flex:1;min-width:280px;margin:0">
			<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
			<input type="hidden" name="id" value="<?= (int)$sg['id'] ?>">
			<input type="hidden" name="show" value="<?= h($show) ?>">
			<input name="admin_note" maxlength="255" placeholder="Note for the player (optional)" class="kvnote">
			<button class="cmbutton" name="decision" value="accept">Accept</button>
			<button class="cmbutton" name="decision" value="reject">Reject</button>
		</form>
	<?php endif; ?>
	</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php cm_page_end(); ?>
