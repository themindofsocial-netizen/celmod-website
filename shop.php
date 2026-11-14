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
// Seasonal shops (Christmas Shop, Halloween Shop, ...) are only synced while they are open: show them first.
$seasonal = array_values(array_filter(array_keys($byCategory), static fn ($c) => preg_match('/ Shop$/', $c)));
if ($seasonal) {
	$byCategory = array_merge(array_intersect_key($byCategory, array_flip($seasonal)), $byCategory);
}
$filter = (string)($_GET['c'] ?? '');
if ($filter !== '' && !isset($byCategory[$filter])) {
	$filter = '';
}

/** A short label for an item's kind. */
function cm_shop_kind(string $kind): string
{
	return ['prop' => 'Prop', 'model' => 'Player Model', 'emblem' => 'Emblem', 'hat' => 'Hat', 'weapon' => 'Celwep'][$kind] ?? 'Command';
}

$season = cm_current_season();
$seasonShop = $season ? $season[1]['shop'] : '';
$counts = array_map('count', $byCategory);

cm_page_start('|CelMod| - Shop', '', ['wide' => true]);
?>

<h2><span class="celColor">|CelMod| Shop</span><br>
</h2>
<p>Buy in game with <span class="h">!buy name</span> (see everything with <span class="h">!catalog</span>), or right here.<br>
<span class="muted">Web orders are delivered in game within a few seconds if you are on the server, or the next time you join.</span></p>

<?php if (!$db): ?>
<?php cm_db_offline_notice(); ?>
<?php endif; ?>

<div class="shop-top">
<?php if ($user): ?>
	<span class="stat"><b><?= h(cm_cl($spendable)) ?></b>Ready to spend<?php if ($reserved): ?><br><span class="muted"><?= h(cm_cl($reserved)) ?> held for orders</span><?php endif; ?></span>
	<span class="stat"><b><?= count(array_filter($owned, static fn ($v) => $v === true)) ?></b>Things you own here</span>
<?php else: ?>
	<span class="stat"><b><a href="login.php?return=shop.php">Sign in through Steam</a></b>to buy from the website</span>
<?php endif; ?>
	<span class="stat"><b><?= count($items) ?></b>For sale right now</span>
</div>

<?php if ($seasonal): ?>
<p class="season-callout">The <span class="h"><?= h(implode(' and the ', $seasonal)) ?></span> <?= count($seasonal) > 1 ? 'are' : 'is' ?> open<?php
	if ($season && in_array($seasonShop, $seasonal, true) && cm_season_in_dates($season[1])):
		$left = cm_season_ends($season[1]) - time();
		echo ' for <b>' . h($left > 86400 ? ceil($left / 86400) . ' more days' : 'less than a day') . '</b>';
	endif; ?>! Seasonal items are only sold for a few days, but once bought they are yours all year.</p>
<?php elseif ($db): $next = cm_upcoming_seasons()[0] ?? null; ?>
<?php if ($next): ?><p class="muted">Next seasonal shop: the <b><?= h($next[1]['shop']) ?></b>, opening <?= h(date('F j', $next[2])) ?>.</p><?php endif; ?>
<?php endif; ?>

<?php if ($items): ?>
<div class="tabs">
	<a href="shop.php" class="<?= $filter === '' ? 'active' : '' ?>">Everything <span class="count"><?= count($items) ?></span></a>
<?php foreach (array_keys($byCategory) as $cat): ?>
	<a href="shop.php?c=<?= h(urlencode($cat)) ?>" class="<?= $filter === $cat ? 'active' : '' ?>"><?= h($cat) ?> <span class="count"><?= (int)$counts[$cat] ?></span></a>
<?php endforeach; ?>
</div>

<div class="shop-tools">
	<input type="search" id="shop-search" placeholder="Search the shop..." aria-label="Search the shop">
	<select id="shop-sort" aria-label="Sort">
		<option value="">Shop order</option>
		<option value="price-asc">Cheapest first</option>
		<option value="price-desc">Priciest first</option>
		<option value="name">A to Z</option>
	</select>
<?php if ($user): ?>
	<label class="muted"><input type="checkbox" id="shop-afford"> Only what I can afford</label>
	<label class="muted"><input type="checkbox" id="shop-hide-owned"> Hide what I own</label>
<?php endif; ?>
</div>
<p class="notice shop-empty" id="shop-empty">Nothing matches that search.</p>

<?php foreach ($byCategory as $category => $list): if ($filter !== '' && $filter !== $category) continue; ?>
<div class="shop-section">
<p class="title">- <?= h($category) ?> -</p>
<div class="shop-grid">
<?php foreach ($list as $n => $it):
	$state = $owned[$it['item']] ?? false;
	$left = cm_shop_available($it, $pendingByItem);
	$repeat = (bool)$it['repeatable'];
	$price = (int)$it['price'];
	$afford = $user && $spendable >= $price;
	$isOwned = $state === true && !$repeat;
	$classes = ['shop-card'];
	if ($isOwned) $classes[] = 'owned';
	if ($left === 0 && $state !== true) $classes[] = 'soldout';
	if (in_array($category, $seasonal, true)) $classes[] = 'seasonal';
?>
	<div class="<?= implode(' ', $classes) ?>" data-name="<?= h(mb_strtolower($it['display_name'] . ' ' . $it['item'] . ' ' . $it['description'])) ?>" data-price="<?= $price ?>" data-order="<?= $n ?>" data-afford="<?= $afford ? 1 : 0 ?>" data-owned="<?= $isOwned ? 1 : 0 ?>">
		<span class="name"><?= h($it['display_name']) ?></span>
		<span class="price<?= $user && !$afford && !$isOwned ? ' short' : '' ?>" title="<?= h(number_format($price)) ?> CL"><?= h(cm_cl($price)) ?></span>
		<span><span class="pill"><?= h(cm_shop_kind((string)$it['kind'])) ?></span><?php if ($repeat): ?> <span class="pill gold" title="Can be bought more than once">Stacks</span><?php endif; ?><?php if ($left !== null && $left > 0): ?> <span class="pill <?= $left <= 5 ? 'red' : 'green' ?>"><?= $left <= 5 ? 'Only ' . $left : $left ?> left</span><?php endif; ?></span>
		<span class="usage"><?= h(cm_shop_usage($it)) ?></span>
		<?php if ($it['description'] !== ''): ?><span class="desc"><?= h($it['description']) ?></span><?php endif; ?>
		<div class="buy">
			<span class="cmd">!buy <?= h($it['item']) ?></span>
		<?php if ($isOwned): ?>
			<span class="grassy"><b>Yours</b></span>
		<?php elseif ($state === 'pending' || isset($owned[$it['item'] . '#pending'])): ?>
			<span class="highlight"><b>On the way</b></span>
		<?php elseif ($left === 0): ?>
			<span class="bloody"><b>Sold out</b></span>
		<?php elseif ($user): ?>
			<form method="post" class="buyform">
				<input type="hidden" name="csrf" value="<?= h(cm_csrf_token()) ?>">
				<input type="hidden" name="action" value="buy">
				<input type="hidden" name="item" value="<?= h($it['item']) ?>">
				<input type="hidden" name="c" value="<?= h($filter) ?>">
				<button class="cmbutton<?= $afford ? ' go' : '' ?>" data-confirm="Buy for <?= h(cm_cl($price)) ?>?" <?= $afford ? '' : 'disabled title="You need ' . h(cm_cl($price - $spendable)) . ' more"' ?>><?= $state === true ? 'Buy another' : 'Buy' ?></button>
			</form>
		<?php else: ?>
			<a href="login.php?return=shop.php" class="muted">Sign in to buy</a>
		<?php endif; ?>
		</div>
	</div>
<?php endforeach; ?>
</div>
</div>
<?php endforeach; ?>
<?php elseif ($db): ?>
<p class="notice">The shop is empty right now. It fills in as soon as the game server is online.</p>
<?php endif; ?>

<?php if ($orders): ?>
<p class="title">:Your Web Orders:</p>
<table class="cmtable">
<tr><th>Item</th><th class="num">Price</th><th>Status</th><th>Placed</th><th></th></tr>
<?php foreach ($orders as $o): ?>
<tr>
	<td><span class="h"><?= h($o['display_name'] ?? $o['item']) ?></span></td>
	<td class="num"><?= h(cm_cl($o['price'])) ?></td>
	<td><?php
		$cls = ['completed' => 'grassy', 'failed' => 'bloody', 'cancelled' => 'muted'][$o['status']] ?? 'highlight';
		echo '<span class="' . $cls . '"><b>' . h(ucfirst($o['status'])) . '</b></span>';
		if ($o['message'] !== '') echo '<br><span class="muted">' . h($o['message']) . '</span>';
	?></td>
	<td><span class="muted"><?= h(cm_ago($o['created_at'])) ?></span></td>
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
<p><a href="buyhelp.php"><b>Bank Help</b></a> <span class="splitter">|</span> <a href="player.php"><b>Your Profile</b></a></p>
<?php cm_page_end('<script type="text/javascript" src="' . cm_base() . 'scripts/shop.js?v=' . (int)@filemtime(CM_ROOT . '/scripts/shop.js') . '"></script>' . "\n"); ?>
