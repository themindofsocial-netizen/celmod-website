<?php
require __DIR__ . '/includes/bootstrap.php';

if (!cm_is_admin()) {
	http_response_code(403);
	cm_page_start('|CelMod| - Admin');
	echo '<h2><span class="celColor">|CelMod| Admin</span><br></h2><p><span class="bloody"><b>Admins only.</b></span></p>';
	cm_page_end();
	exit;
}

$tab = ($_GET['tab'] ?? 'orders') === 'suggestions' ? 'suggestions' : 'orders';
$db = cm_db();

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
