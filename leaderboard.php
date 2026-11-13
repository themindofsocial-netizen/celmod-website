<?php
require __DIR__ . '/includes/bootstrap.php';

$boards = [
	'balance'  => ['Richest Players', 'balance', 'Balance'],
	'playtime' => ['Most Time Played', 'playtime_seconds', 'Time'],
	'props'    => ['Most Props Spawned', 'props_spawned', 'Props'],
	'cels'     => ['Most Cels Spawned', 'cels_spawned', 'Cels'],
];
$board = isset($boards[$_GET['by'] ?? '']) ? $_GET['by'] : 'balance';
[$title, $column, $label] = $boards[$board];

// $column comes from the whitelist above, never from the request.
$rows = cm_rows("SELECT steamid64, name, is_online, $column AS v FROM cm_players WHERE $column > 0 ORDER BY $column DESC, name LIMIT 25");

cm_page_start('|CelMod| - Leaderboards');
?>

<h2><span class="celColor">|CelMod| Leaderboards</span><br>
</h2>
<p class="tabs"><?php foreach ($boards as $key => $b): ?>
<a href="?by=<?= $key ?>" class="<?= $key === $board ? 'active' : '' ?>"><b><?= h($b[0]) ?></b></a>
<?php endforeach; ?></p>
<?php if (!cm_db()): cm_db_offline_notice(); else: ?>
<p><b>:<?= h($title) ?>:</b></p>
<table class="cmtable">
<tr><th class="num" style="width: 30px;">#</th><th>Player</th><th class="num"><?= h($label) ?></th></tr>
<?php if (!$rows): ?>
<tr><td colspan="3"><span class="muted">No players yet - be the first!</span></td></tr>
<?php endif; ?>
<?php foreach ($rows as $i => $r): ?>
<tr>
	<td class="num"><b><?= $i === 0 ? '<FONT style="COLOR: #FFDD22">1</FONT>' : $i + 1 ?></b></td>
	<td><a href="player.php?id=<?= h($r['steamid64']) ?>"><?= $i === 0 ? '<span class="big">' . h($r['name']) . '</span>' : h($r['name']) ?></a><?php if ($r['is_online']): ?> <span class="grassy" title="Online now">&#9679;</span><?php endif; ?></td>
	<td class="num"><span class="h"><?php
		echo h($board === 'balance' ? cm_cl($r['v']) : ($board === 'playtime' ? cm_duration((int)$r['v']) : number_format((int)$r['v'])));
	?></span></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<p><a href="top5.php"><b>Classic Top 5 Builders</b></a></p>
<?php cm_page_end(); ?>
