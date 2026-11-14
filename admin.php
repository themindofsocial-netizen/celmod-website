<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/updates.php';

if (!cm_is_admin()) {
	http_response_code(403);
	cm_page_start('|CelMod| - Admin');
	echo '<h2><span class="celColor">|CelMod| Admin</span><br></h2><p><span class="bloody"><b>Admins only.</b></span></p>';
	cm_page_end();
	exit;
}

$tab = in_array($_GET['tab'] ?? '', ['orders', 'suggestions', 'announcements', 'updates', 'season'], true) ? $_GET['tab'] : 'orders';
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

<?php elseif ($tab === 'announcements'):
	$announcements = $db ? cm_rows('SELECT a.*, p.name FROM cm_announcements a LEFT JOIN cm_players p ON p.steamid64 = a.created_by ORDER BY a.active DESC, a.id DESC LIMIT 100') : [];
	$servers = $db ? cm_rows('SELECT server_id, hostname FROM cm_servers ORDER BY server_id') : [];
?>
<p class="notice">Announcements show in chat on the game server as <span class="h">|CelMod| your message</span>
within about 30 seconds. A new one is shown to everyone once; set <span class="h">Repeat</span> to show it again every few
minutes, and <span class="h">Show on join</span> to show it to players as they join. Color tags such as
<span class="h">{green}</span> and <span class="h">{default}</span> work. Times use the database server's clock.</p>

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
