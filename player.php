<?php
require __DIR__ . '/includes/bootstrap.php';

$id = (string)($_GET['id'] ?? (cm_user()['steamid64'] ?? ''));
$player = cm_is_steamid64($id) ? cm_row('SELECT * FROM cm_players WHERE steamid64 = ?', [$id]) : null;
$isMe = cm_user() && cm_user()['steamid64'] === $id;

if ($player) {
	$owned = cm_rows('SELECT p.item, p.source, p.purchased_at, s.display_name FROM cm_purchases p LEFT JOIN cm_shop_items s ON s.item = p.item WHERE p.steamid64 = ? ORDER BY p.item', [$id]);
	$sessions = cm_rows('SELECT map, started_at, ended_at, TIMESTAMPDIFF(SECOND, started_at, COALESCE(ended_at, NOW())) AS secs FROM cm_sessions WHERE steamid64 = ? ORDER BY id DESC LIMIT 8', [$id]);
	$rank = 1 + (int)cm_value('SELECT COUNT(*) FROM cm_players WHERE balance > ?', [$player['balance']]);
}

cm_page_start('|CelMod| - ' . ($player ? $player['name'] : 'Player'));
?>

<?php if (!cm_db()): cm_db_offline_notice(); elseif (!$player): ?>
<h2><span class="celColor">|CelMod| Player</span><br>
</h2>
<p><?php if ($isMe): ?>You haven't joined the server yet.<br>Join once and your stats will show up here!<?php else: ?>We couldn't find that player.<?php endif; ?></p>
<?php else: ?>
<h2><span class="celColor"><?= h($player['name']) ?></span><br>
</h2>
<?php if ($player['avatar_url']): ?>
<p><img src="<?= h($player['avatar_url']) ?>" width="92" height="92" style="border: 1px solid black;"></p>
<?php endif; ?>
<p><?php if ($player['is_online']): ?><span class="grassy">&#9679;</span> <b>Building on the server right now</b><?php else: ?>Last seen <span class="h"><?= h(cm_ago($player['last_seen'])) ?></span><?php endif; ?><br>
<a href="https://steamcommunity.com/profiles/<?= h($player['steamid64']) ?>"><span class="muted">Steam Profile</span></a></p>
<p>
	<span class="stat"><b><?= h(cm_cl($player['balance'])) ?></b>Balance (#<?= $rank ?>)</span>
	<span class="stat"><b><?= h(cm_duration((int)$player['playtime_seconds'])) ?></b>Time Played</span>
	<span class="stat"><b><?= number_format((int)$player['connections']) ?></b>Visits</span>
	<br>
	<span class="stat"><b><?= number_format((int)$player['props_spawned']) ?></b>Props Spawned</span>
	<span class="stat"><b><?= number_format((int)$player['cels_spawned']) ?></b>Cels Spawned</span>
	<span class="stat"><b><?= number_format((int)$player['effects_spawned']) ?></b>Effects Spawned</span>
</p>
<p>First joined: <span class="highlight"><?= h(date('F j, Y', strtotime($player['first_seen']))) ?></span></p>

<p><b>:Unlocked:</b></p>
<p><?php if (!$owned): ?><span class="muted">Nothing yet.</span> <?php if ($isMe): ?><a href="shop.php"><b>Visit the Shop</b></a><?php endif; ?><?php endif; ?>
<?php foreach ($owned as $o): ?>
<b><span class="h"><?= h($o['item']) ?></span></b> <span class="muted"><?= h($o['display_name'] ?? '') ?><?= $o['source'] === 'web' ? ' (web)' : '' ?></span><br>
<?php endforeach; ?></p>

<?php if ($sessions): ?>
<p><b>:Recent Sessions:</b></p>
<table class="cmtable">
<tr><th>Map</th><th>When</th><th class="num">Length</th></tr>
<?php foreach ($sessions as $s): ?>
<tr><td><span class="h"><?= h($s['map']) ?></span></td><td><?= h(cm_ago($s['started_at'])) ?></td><td class="num"><?= $s['ended_at'] ? h(cm_duration((int)$s['secs'])) : '<span class="grassy">Playing</span>' ?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php endif; ?>
<p><a href="leaderboard.php"><b>Leaderboards</b></a></p>
<?php cm_page_end(); ?>
