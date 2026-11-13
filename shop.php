<?php
require __DIR__ . '/includes/bootstrap.php';

$db = cm_db();
$user = cm_user();

/*
 * Orders are queued here and delivered by the game server (celmod-shop through celmod-database),
 * which re-checks the item, stock, ownership and balance in game before charging, and never
 * charges more than the price shown here. The website never edits balances itself.
 */

/** How to use an item once bought, from its kind and target. */
function cm_shop_usage(array $it): string
{
	$t = $it['target'] !== '' ? $it['target'] : $it['item'];
	switch ($it['kind']) {
		case 'prop':
			return 'Spawn it with !' . $t;
		case 'model':
			return 'Wear it with !playermodel ' . $t;
		case 'emblem':
			return 'Spawn it with !emblem ' . $t;
		case 'hat':
			return 'Wear it with !hat ' . $t;
		case 'weapon':
			return 'Use it with !weapon ' . $t;
		default:
			return 'Unlocks !' . $t;
	}
}

/** Units still for sale after other players' pending orders, or null for no limit. */
function cm_shop_available(array $it, array $pendingByItem): ?int
{
	if ($it['stock_left'] === null) {
		return null;
	}
	return max(0, (int)$it['stock_left'] - (int)($pendingByItem[$it['item']] ?? 0));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$back = 'shop.php' . (isset($_POST['c']) && $_POST['c'] !== '' ? '?c=' . urlencode((string)$_POST['c']) : '');

	if (!$user || !$db || !cm_csrf_check()) {
		cm_flash('Your session expired, please try again.', 'bloody');
		header('Location: ' . $back);
		exit;
	}

	$action = $_POST['action'] ?? '';

	if ($action === 'cancel') {
		$st = $db->prepare("UPDATE cm_web_orders SET status = 'cancelled', processed_at = NOW(), message = 'Cancelled on the website.' WHERE id = ? AND steamid64 = ? AND status = 'pending'");
		$st->execute([(int)($_POST['order'] ?? 0), $user['steamid64']]);
		cm_flash($st->rowCount() ? 'Order cancelled.' : 'That order is already being delivered.', $st->rowCount() ? 'h' : 'bloody');
		header('Location: ' . $back);
		exit;
	}

	if ($action === 'buy') {
		$itemKey = (string)($_POST['item'] ?? '');
		// One order at a time across the whole shop, so the last item in stock can't be ordered twice.
		$lock = 'celmod_shop_orders';
		try {
			if (!cm_value('SELECT GET_LOCK(?, 5)', [$lock])) {
				throw new RuntimeException('The shop is busy, please try again.');
			}

			$player = cm_row('SELECT steamid64, balance FROM cm_players WHERE steamid64 = ?', [$user['steamid64']]);
			$item = cm_row('SELECT * FROM cm_shop_items WHERE item = ? AND enabled = 1', [$itemKey]);

			if (!$player) {
				throw new RuntimeException('Join the server at least once before using the web shop.');
			}
			if (!$item) {
				throw new RuntimeException('That item isn\'t in the shop.');
			}

			$owned = (bool)cm_value('SELECT 1 FROM cm_purchases WHERE steamid64 = ? AND item = ?', [$user['steamid64'], $itemKey]);
			$queued = (bool)cm_value("SELECT 1 FROM cm_web_orders WHERE steamid64 = ? AND item = ? AND status IN ('pending','processing')", [$user['steamid64'], $itemKey]);
			$reserved = (int)cm_value("SELECT COALESCE(SUM(price),0) FROM cm_web_orders WHERE steamid64 = ? AND status IN ('pending','processing')", [$user['steamid64']]);
			$pending = [$itemKey => (int)cm_value("SELECT COUNT(*) FROM cm_web_orders WHERE item = ? AND status IN ('pending','processing')", [$itemKey])];

			if ($owned && !$item['repeatable']) {
				throw new RuntimeException('You already own ' . $item['display_name'] . '.');
			}
			if ($queued) {
				throw new RuntimeException('You already have an order for ' . $item['display_name'] . ' on the way.');
			}
			if (cm_shop_available($item, $pending) === 0) {
				throw new RuntimeException($item['display_name'] . ' is sold out.');
			}
			if ((int)$player['balance'] - $reserved < (int)$item['price']) {
				throw new RuntimeException('You don\'t have enough money.');
			}

			$db->prepare('INSERT INTO cm_web_orders (steamid64, item, price) VALUES (?, ?, ?)')
				->execute([$user['steamid64'], $item['item'], $item['price']]);

			cm_flash('Order placed for ' . $item['display_name'] . '! It is delivered in game within a few seconds if you are on the server, or the next time you join.');
		} catch (RuntimeException $e) {
			cm_flash($e->getMessage(), 'bloody');
		} finally {
			cm_value('SELECT RELEASE_LOCK(?)', [$lock]);
		}
		header('Location: ' . $back);
		exit;
	}
}

$items = $db ? cm_rows('SELECT * FROM cm_shop_items WHERE enabled = 1 ORDER BY sort_order, price, item') : [];

$pendingByItem = [];
foreach ($db ? cm_rows("SELECT item, COUNT(*) AS n FROM cm_web_orders WHERE status IN ('pending','processing') GROUP BY item") : [] as $r) {
	$pendingByItem[$r['item']] = (int)$r['n'];
}

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
			if (($owned[$o['item']] ?? null) === true) {
				$owned[$o['item'] . '#pending'] = true; // repeatable item: owned and another on the way
			}
		}
	}
}
$spendable = $user ? (int)$user['balance'] - $reserved : 0;

// Categories in catalog order
$byCategory = [];
foreach ($items as $it) {
	$byCategory[$it['category']][] = $it;
}
$filter = (string)($_GET['c'] ?? '');
if ($filter !== '' && !isset($byCategory[$filter])) {
	$filter = '';
}

cm_page_start('|CelMod| - Shop');
?>

<h2><span class="celColor">|CelMod| Shop</span><br>
</h2>
<p>Buy in game with <span class="h">!buy</span> (<span class="h">name</span>), list everything with <span class="h">!catalog</span>,<br>or buy right here on the website.</p>
<?php if ($user): ?>
<p>Your balance: <span class="h"><b><?= h(cm_cl($spendable)) ?></b></span><?php if ($reserved): ?> <span class="muted">(<?= h(cm_cl($reserved)) ?> reserved for pending orders)</span><?php endif; ?><br>
<span class="muted">Balances update every few seconds while you are on the server.</span></p>
<?php else: ?>
<p><a href="login.php?return=shop.php"><b>Sign in through Steam</b></a> to buy from the website.</p>
<?php endif; ?>

<?php if (!$db): ?>
<?php cm_db_offline_notice(); ?>
<?php endif; ?>

<?php if ($items): ?>
<div class="tabs">
	<a href="shop.php" class="<?= $filter === '' ? 'active' : '' ?>">Everything</a>
<?php foreach (array_keys($byCategory) as $cat): ?>
	<a href="shop.php?c=<?= h(urlencode($cat)) ?>" class="<?= $filter === $cat ? 'active' : '' ?>"><?= h($cat) ?></a>
<?php endforeach; ?>
</div>

<?php foreach ($byCategory as $category => $list): if ($filter !== '' && $filter !== $category) continue; ?>
<p class="title">- <?= h($category) ?> -</p>
<table class="cmtable shoptable">
<?php foreach ($list as $it):
	$state = $owned[$it['item']] ?? false;
	$left = cm_shop_available($it, $pendingByItem);
	$repeat = (bool)$it['repeatable'];
?>
<tr class="<?= $left === 0 && $state !== true ? 'soldout' : '' ?>">
	<td>
		<b><?= h($it['display_name']) ?></b> <span class="muted">!buy <?= h($it['item']) ?></span><br>
		<span class="usage"><?= h(cm_shop_usage($it)) ?><?= $repeat ? ' - can be bought more than once' : '' ?></span>
		<?php if ($it['description'] !== ''): ?><br><span class="muted"><?= h($it['description']) ?></span><?php endif; ?>
	</td>
	<td class="num">
		<b><?= h(cm_cl($it['price'])) ?></b>
		<?php if ($left !== null): ?><br><span class="<?= $left === 0 ? 'bloody' : ($left <= 5 ? 'splitter' : 'muted') ?>"><?= $left === 0 ? 'Sold out' : $left . ' left' ?></span><?php endif; ?>
	</td>
	<td class="num" style="width: 120px;">
	<?php if ($state === true && !$repeat): ?>
		<span class="grassy"><b>Owned</b></span>
	<?php elseif ($state === 'pending' || isset($owned[$it['item'] . '#pending'])): ?>
		<span class="highlight">On the way</span>
	<?php elseif ($left === 0): ?>
		<span class="muted">Sold out</span>
	<?php elseif ($user): ?>
		<form method="post" style="margin:0" onsubmit="return confirm('Buy <?= h(addslashes($it['display_name'])) ?> for <?= h(cm_cl($it['price'])) ?>?');">
			<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
			<input type="hidden" name="action" value="buy">
			<input type="hidden" name="item" value="<?= h($it['item']) ?>">
			<input type="hidden" name="c" value="<?= h($filter) ?>">
			<button class="cmbutton" <?= $spendable < (int)$it['price'] ? 'disabled title="Not enough CL"' : '' ?>><?= $state === true ? 'Buy another' : 'Buy' ?></button>
		</form>
		<?php if ($state === true): ?><span class="muted">You own 1+</span><?php endif; ?>
	<?php endif; ?>
	</td>
</tr>
<?php endforeach; ?>
</table>
<?php endforeach; ?>
<?php elseif ($db): ?>
<p class="notice">The shop is empty right now. It fills in as soon as the game server is online.</p>
<?php endif; ?>

<?php if ($orders): ?>
<p class="title">:Your Web Orders:</p>
<table class="cmtable">
<tr><th>Item</th><th class="num">Price</th><th>Status</th><th></th></tr>
<?php foreach ($orders as $o): ?>
<tr>
	<td><span class="h"><?= h($o['display_name'] ?? $o['item']) ?></span></td>
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
			<input type="hidden" name="c" value="<?= h($filter) ?>">
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
