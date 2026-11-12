<?php
require __DIR__ . '/includes/bootstrap.php';

if (!cm_is_admin()) {
	http_response_code(403);
	cm_page_start('|CelMod| - Admin');
	echo '<h2><span class="celColor">|CelMod| Admin</span><br></h2><p><span class="bloody"><b>Admins only.</b></span></p>';
	cm_page_end();
	exit;
}

$orders = cm_rows('SELECT o.*, p.name FROM cm_web_orders o LEFT JOIN cm_players p ON p.steamid64 = o.steamid64 ORDER BY o.id DESC LIMIT 50');

cm_page_start('|CelMod| - Admin');
?>
<h2><span class="celColor">|CelMod| Web Orders</span><br>
</h2>
<table class="cmtable">
<tr><th>#</th><th>Player</th><th>Item</th><th class="num">Price</th><th>Status</th><th>Placed</th></tr>
<?php foreach ($orders as $o): ?>
<tr>
	<td><?= (int)$o['id'] ?></td>
	<td><a href="player.php?id=<?= h($o['steamid64']) ?>"><?= h($o['name'] ?? $o['steamid64']) ?></a></td>
	<td><span class="h"><?= h($o['item']) ?></span></td>
	<td class="num"><?= h(cm_cl($o['price'])) ?></td>
	<td><?= h($o['status']) ?><?php if ($o['message'] !== ''): ?><br><span class="muted"><?= h($o['message']) ?></span><?php endif; ?></td>
	<td><?= h(cm_ago($o['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php cm_page_end(); ?>
