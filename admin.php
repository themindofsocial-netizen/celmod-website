<?php
require __DIR__ . '/includes/bootstrap.php';

if (!cm_is_admin()) {
	http_response_code(403);
	cm_page_start('|CelMod| - Admin');
	echo '<h2><span class="celColor">|CelMod| Admin</span><br></h2><p><span class="bloody"><b>Admins only.</b></span></p>';
	cm_page_end();
	exit;
}

$tab = in_array($_GET['tab'] ?? '', ['orders', 'suggestions', 'announcements'], true) ? $_GET['tab'] : 'orders';
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

<?php elseif ($tab === 'announcements'):
	$announcements = $db ? cm_rows('SELECT a.*, p.name FROM cm_announcements a LEFT JOIN cm_players p ON p.steamid64 = a.created_by ORDER BY a.active DESC, a.id DESC LIMIT 100') : [];
	$servers = $db ? cm_rows('SELECT server_id, hostname FROM cm_servers ORDER BY server_id') : [];
?>
<p class="notice">Announcements show in chat on the game server as <span class="h">|CelMod| Announcement: ...</span>
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
