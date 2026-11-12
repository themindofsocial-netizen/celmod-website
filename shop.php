<?php
require __DIR__ . '/includes/bootstrap.php';

$db = cm_db();
$user = cm_user();

/*
 * Orders are queued here and delivered by the game server (celmod-database
 * plugin), which re-checks the price and balance in-game before charging.
 * The website never edits balances directly, so it can't race the server.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!$user || !$db || !cm_csrf_check()) {
		cm_flash('Your session expired, please try again.', 'bloody');
		header('Location: shop.php');
		exit;
	}

	$action = $_POST['action'] ?? '';

	if ($action === 'cancel') {
		$st = $db->prepare("UPDATE cm_web_orders SET status = 'cancelled', processed_at = NOW(), message = 'Cancelled on the website.' WHERE id = ? AND steamid64 = ? AND status = 'pending'");
		$st->execute([(int)($_POST['order'] ?? 0), $user['steamid64']]);
		cm_flash($st->rowCount() ? 'Order cancelled.' : 'That order is already being delivered.', $st->rowCount() ? 'h' : 'bloody');
		header('Location: shop.php');
		exit;
	}

	if ($action === 'buy') {
		$itemKey = (string)($_POST['item'] ?? '');
		try {
			// One order at a time per player so double-clicks can't over-spend.
			$lock = 'celmod_order_' . $user['steamid64'];
			if (!cm_value('SELECT GET_LOCK(?, 5)', [$lock])) {
				throw new RuntimeException('The shop is busy, please try again.');
			}

			$player = cm_row('SELECT steamid64, balance FROM cm_players WHERE steamid64 = ?', [$user['steamid64']]);
			$item = cm_row('SELECT * FROM cm_shop_items WHERE item = ? AND enabled = 1', [$itemKey]);
			$owned = (bool)cm_value('SELECT 1 FROM cm_purchases WHERE steamid64 = ? AND item = ?', [$user['steamid64'], $itemKey]);
			$queued = (bool)cm_value("SELECT 1 FROM cm_web_orders WHERE steamid64 = ? AND item = ? AND status IN ('pending','processing')", [$user['steamid64'], $itemKey]);
			$reserved = (int)cm_value("SELECT COALESCE(SUM(price),0) FROM cm_web_orders WHERE steamid64 = ? AND status IN ('pending','processing')", [$user['steamid64']]);

			if (!$player) {
				throw new RuntimeException('Join the server at least once before using the web shop.');
			}
			if (!$item) {
				throw new RuntimeException('That item isn\'t in the shop.');
			}
			if ($owned) {
				throw new RuntimeException('You already own ' . $item['item'] . '.');
			}
			if ($queued) {
				throw new RuntimeException('You already have an order for ' . $item['item'] . ' on the way.');
			}
			if ((int)$player['balance'] - $reserved < (int)$item['price']) {
				throw new RuntimeException('You don\'t have enough money.');
			}

			$db->prepare('INSERT INTO cm_web_orders (steamid64, item, price) VALUES (?, ?, ?)')
				->execute([$user['steamid64'], $item['item'], $item['price']]);

			cm_flash('Order placed for ' . $item['item'] . '! It is delivered in-game within a few seconds if you are on the server, or the next time you join.');
		} catch (RuntimeException $e) {
			cm_flash($e->getMessage(), 'bloody');
		} finally {
			cm_value('SELECT RELEASE_LOCK(?)', ['celmod_order_' . $user['steamid64']]);
		}
		header('Location: shop.php');
		exit;
	}
}

$items = $db ? cm_rows('SELECT * FROM cm_shop_items WHERE enabled = 1 ORDER BY category, price, item') : [];
$owned = [];
$orders = [];
$reserved = 0;
if ($user && $db) {
	foreach (cm_rows('SELECT item FROM cm_purchases WHERE steamid64 = ?', [$user['steamid64']]) as $r) {
		$owned[$r['item']] = true;
	}
	$orders = cm_rows('SELECT o.*, s.display_name FROM cm_web_orders o LEFT JOIN cm_shop_items s ON s.item = o.item WHERE o.steamid64 = ? ORDER BY o.id DESC LIMIT 10', [$user['steamid64']]);
	foreach ($orders as $o) {
		if (in_array($o['status'], ['pending', 'processing'], true)) {
			$reserved += (int)$o['price'];
			$owned[$o['item']] = $owned[$o['item']] ?? 'pending';
		}
	}
}

$byCategory = [];
foreach ($items as $it) {
	$byCategory[$it['category']][] = $it;
}

cm_page_start('|CelMod| - Shop');
?>

<h2><span class="celColor">|CelMod| Shop</span><br>
</h2>
<p>Buy these in-game using <span class="h">!buy</span> (<span class="h">name</span>), or right here on the website.</p>
<?php if ($user): ?>
<p>Your balance: <span class="h"><b><?= h(cm_cl((int)$user['balance'] - $reserved)) ?></b></span><?php if ($reserved): ?> <span class="muted">(<?= h(cm_cl($reserved)) ?> reserved for pending orders)</span><?php endif; ?><br>
<span class="muted">Balances update every few seconds while you are on the server.</span></p>
<?php else: ?>
<p><a href="login.php?return=shop.php"><b>Sign in through Steam</b></a> to buy from the website.</p>
<?php endif; ?>

<?php if (!$db): ?>
<?php cm_db_offline_notice(); ?>
<?php endif; ?>

<?php if ($items): ?>
<?php foreach ($byCategory as $category => $list): ?>
<p>- Unlockable <?= h($category) ?> -</p>
<p>Note: You only have to pay for these <b>ONCE</b>.</p>
<table class="cmtable">
<?php foreach ($list as $it): $state = $owned[$it['item']] ?? false; ?>
<tr>
	<td><b><span class="h"><?= h($it['item']) ?></span></b> <span class="muted"><?= h($it['display_name']) ?></span></td>
	<td class="num"><b><?= h(cm_cl($it['price'])) ?></b></td>
	<td class="num" style="width: 110px;">
	<?php if ($state === true): ?>
		<span class="grassy"><b>Owned</b></span>
	<?php elseif ($state === 'pending'): ?>
		<span class="highlight">On the way</span>
	<?php elseif ($user): ?>
		<form method="post" style="margin:0" onsubmit="return confirm('Buy <?= h($it['item']) ?> for <?= h(cm_cl($it['price'])) ?>?');">
			<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
			<input type="hidden" name="action" value="buy">
			<input type="hidden" name="item" value="<?= h($it['item']) ?>">
			<button class="cmbutton" <?= ((int)$user['balance'] - $reserved) < (int)$it['price'] ? 'disabled title="Not enough CL"' : '' ?>>Buy</button>
		</form>
	<?php endif; ?>
	</td>
</tr>
<?php endforeach; ?>
</table>
<?php endforeach; ?>
<?php else: ?>
<p>- Unlockable Props -</p>
<p>Note: You only have to pay for these <b>ONCE</b>.<br>
  <b><span class="h">combineprops</span> - 100K CL</b><br>
</p>
<p>- Unlockable Commands -</p>
<p>Note: You only have to pay for these <b>ONCE</b>.<br>
  <b><span class="h">gravity</span> - 10000 CL</b><br>
  <b><span class="h">weld</span> - 20000 CL</b><br>
  <b><span class="h">light</span> - 30000 CL</b><br>
  <b><span class="h">text</span> - 50000 CL</b><br>
  <b><span class="h">anim</span> - 50000 CL</b><br>
  <b><span class="h">turret</span> - 200K CL</b><br>
  <b><span class="h">landcolor</span> - 200K CL</b><br>
  <b><span class="h">internet</span> - 200K CL</b><br>
  <b><span class="h">seturl</span> - 500K CL</b><br>
</p>
<?php endif; ?>

<?php if ($orders): ?>
<p><b>:Your Web Orders:</b></p>
<table class="cmtable">
<tr><th>Item</th><th class="num">Price</th><th>Status</th><th></th></tr>
<?php foreach ($orders as $o): ?>
<tr>
	<td><span class="h"><?= h($o['item']) ?></span></td>
	<td class="num"><?= h(cm_cl($o['price'])) ?></td>
	<td><?php
		$cls = ['completed' => 'grassy', 'failed' => 'bloody', 'cancelled' => 'muted'][$o['status']] ?? 'highlight';
		echo '<span class="' . $cls . '">' . h(ucfirst($o['status'])) . '</span>';
		if ($o['message'] !== '') echo '<br><span class="muted">' . h($o['message']) . '</span>';
	?></td>
	<td class="num"><?php if ($o['status'] === 'pending'): ?>
		<form method="post" style="margin:0">
			<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
			<input type="hidden" name="action" value="cancel">
			<input type="hidden" name="order" value="<?= (int)$o['id'] ?>">
			<button class="cmbutton">Cancel</button>
		</form>
	<?php endif; ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<br>
<a href="buyhelp.php"><b>Bank Help</b></a><br>
<?php cm_page_end(); ?>
