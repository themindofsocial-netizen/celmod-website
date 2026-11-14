<?php
require __DIR__ . '/includes/bootstrap.php';

/*
 * Every board: [title, column or SQL expression, value label, how to show it, one-line blurb].
 * The columns come from this list, never from the request. Boards whose stats the game server
 * hasn't recorded yet (an older celmod-database) just show "No players yet".
 */
$groups = [
	'Wealth & Time' => [
		'balance'  => ['Richest Players', 'balance', 'Balance', 'cl', 'Who has the most cleers in the bank.'],
		'playtime' => ['Most Time Played', 'playtime_seconds', 'Time', 'time', 'Hours spent on the server.'],
		'visits'   => ['Most Visits', 'connections', 'Visits', 'num', 'Who keeps coming back.'],
	],
	'Building' => [
		'props'   => ['Most Props Spawned', 'props_spawned', 'Props', 'num', 'Every prop ever spawned.'],
		'cels'    => ['Most Cels Spawned', 'cels_spawned', 'Cels', 'num', 'Doors, lights, music, buttons and every other cel.'],
		'effects' => ['Most Effects Spawned', 'effects_spawned', 'Effects', 'num', 'Fire, smoke, sparks and the rest.'],
		'removed' => ['Biggest Cleaners', 'entities_removed', 'Removed', 'num', 'Who deletes the most. Tidy, or indecisive?'],
	],
	'Combat' => [
		'kills'   => ['Most Kills', 'kills', 'Kills', 'num', 'Players killed in deathmatch.'],
		'kd'      => ['Best K/D', 'ROUND(kills / GREATEST(deaths, 1), 2)', 'K/D', 'ratio', 'Kills for every death, with at least 25 kills.'],
		'deaths'  => ['Most Deaths', 'deaths', 'Deaths', 'num', 'Somebody has to lose.'],
		'enemies' => ['Monster Hunters', 'enemy_kills', 'Enemies', 'num', 'Zombies, antlions and other enemies killed in the dungeons.'],
	],
	'Social' => [
		'hated'    => ['Most Hated', 'land_bans', 'Land Bans', 'num', 'Banned from the most lands. Wear it with pride.'],
		'generous' => ['Most Generous', 'presents_given', 'Presents', 'num', 'Emblems, hats and celweps wrapped up for other players.'],
		'emblems'  => ['Biggest Collectors', 'emblems_owned', 'Emblems', 'num', 'The most emblems owned.'],
	],
];

$boards = [];
foreach ($groups as $list) {
	$boards += $list;
}

$board = isset($boards[$_GET['by'] ?? '']) ? $_GET['by'] : 'balance';
[$title, $expr, $label, $format, $blurb] = $boards[$board];

$where = "$expr > 0" . ($board === 'kd' ? ' AND kills >= 25' : '');
$rows = cm_rows("SELECT steamid64, name, is_online, $expr AS v FROM cm_players WHERE $where ORDER BY v DESC, name LIMIT 25");

function cm_board_value($v, string $format): string
{
	switch ($format) {
		case 'cl':
			return cm_cl($v);
		case 'time':
			return cm_duration((int)$v);
		case 'ratio':
			return number_format((float)$v, 2);
		default:
			return number_format((int)$v);
	}
}

// Where the signed-in player stands on this board.
$me = cm_user();
$myPlace = null;
if ($me && empty($me['_new']) && $rows !== []) {
	$mine = cm_row("SELECT $expr AS v FROM cm_players WHERE steamid64 = ? AND $where", [$me['steamid64']]);
	if ($mine) {
		$myPlace = [1 + (int)cm_value("SELECT COUNT(*) FROM cm_players WHERE $where AND $expr > ?", [$mine['v']]), $mine['v']];
	}
}

cm_page_start('|CelMod| - Leaderboards');
?>

<h2><span class="celColor">|CelMod| Leaderboards</span><br>
</h2>

<div class="board-groups">
<?php foreach ($groups as $group => $list): ?>
	<div class="board-group">
		<b><?= h($group) ?></b>
	<?php foreach ($list as $key => $b): ?>
		<a href="?by=<?= $key ?>"<?= $key === $board ? ' class="active"' : '' ?>><?= h($b[0]) ?></a>
	<?php endforeach; ?>
	</div>
<?php endforeach; ?>
</div>

<?php if (!cm_db()): cm_db_offline_notice(); else: ?>
<p><b>:<?= h($title) ?>:</b><br><span class="board-blurb"><?= h($blurb) ?></span></p>

<?php if (count($rows) >= 3): ?>
<div class="podium">
<?php foreach ([1, 0, 2] as $i): $r = $rows[$i]; ?>
	<div class="place p<?= $i + 1 ?>">
		<span class="rank"><?= $i + 1 ?></span>
		<a href="player.php?id=<?= h($r['steamid64']) ?>"><b><?= h($r['name']) ?></b></a><?php if ($r['is_online']): ?> <span class="grassy" title="Online now">&#9679;</span><?php endif; ?>
		<span class="v"><?= h(cm_board_value($r['v'], $format)) ?></span>
	</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<table class="cmtable">
<tr><th class="num" style="width: 30px;">#</th><th>Player</th><th class="num"><?= h($label) ?></th></tr>
<?php if (!$rows): ?>
<tr><td colspan="3"><span class="muted">No players yet - be the first!</span></td></tr>
<?php endif; ?>
<?php foreach ($rows as $i => $r): if (count($rows) >= 3 && $i < 3) continue; ?>
<tr<?= $me && $me['steamid64'] === $r['steamid64'] ? ' class="me"' : '' ?>>
	<td class="num"><b><?= $i + 1 ?></b></td>
	<td><a href="player.php?id=<?= h($r['steamid64']) ?>"><?= h($r['name']) ?></a><?php if ($r['is_online']): ?> <span class="grassy" title="Online now">&#9679;</span><?php endif; ?></td>
	<td class="num"><span class="h"><?= h(cm_board_value($r['v'], $format)) ?></span></td>
</tr>
<?php endforeach; ?>
</table>

<?php if ($myPlace): ?>
<p>You are <b class="h">#<?= (int)$myPlace[0] ?></b> on this board with <b><?= h(cm_board_value($myPlace[1], $format)) ?></b>.</p>
<?php endif; ?>
<?php endif; ?>
<p><a href="top5.php"><b>Classic Top 5 Builders</b></a></p>
<?php cm_page_end(); ?>
