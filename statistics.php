<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/charts.php';

$db = cm_db();
$sid = (int)cm_config('server')['id'];

if ($db) {
	$totals = cm_row('SELECT COUNT(*) AS players, COALESCE(SUM(playtime_seconds),0) AS playtime, COALESCE(SUM(props_spawned),0) AS props,
		COALESCE(SUM(cels_spawned),0) AS cels, COALESCE(SUM(effects_spawned),0) AS effects, COALESCE(SUM(connections),0) AS connections,
		COALESCE(SUM(balance),0) AS economy FROM cm_players');
	$purchases = (int)cm_value('SELECT COUNT(*) FROM cm_purchases');
	$webOrders = (int)cm_value("SELECT COUNT(*) FROM cm_web_orders WHERE status = 'completed'");
	$newToday = (int)cm_value('SELECT COUNT(*) FROM cm_players WHERE first_seen >= CURDATE()');
	$activeWeek = (int)cm_value('SELECT COUNT(*) FROM cm_players WHERE last_seen >= NOW() - INTERVAL 7 DAY');

	// Peak players per hour, last 24 hours
	$hourly = [];
	for ($i = 23; $i >= 0; $i--) {
		$hourly[date('ga', strtotime("-$i hours"))] = 0;
	}
	foreach (cm_rows("SELECT DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00:00') AS hr, MAX(players) AS p FROM cm_player_samples
		WHERE server_id = ? AND recorded_at >= NOW() - INTERVAL 24 HOUR GROUP BY hr", [$sid]) as $r) {
		$k = date('ga', strtotime($r['hr']));
		if (isset($hourly[$k])) $hourly[$k] = (int)$r['p'];
	}

	// Joins + props per day, last 14 days
	$daily = $dailyProps = [];
	for ($i = 13; $i >= 0; $i--) {
		$d = date('M j', strtotime("-$i days"));
		$daily[$d] = 0;
		$dailyProps[$d] = 0;
	}
	foreach (cm_rows('SELECT day, connections, props_spawned FROM cm_stats_daily WHERE server_id = ? AND day >= CURDATE() - INTERVAL 13 DAY', [$sid]) as $r) {
		$k = date('M j', strtotime($r['day']));
		if (isset($daily[$k])) {
			$daily[$k] = (int)$r['connections'];
			$dailyProps[$k] = (int)$r['props_spawned'];
		}
	}

	$maps = cm_rows('SELECT map, times_played, last_played FROM cm_maps ORDER BY times_played DESC LIMIT 8');
	$popular = cm_rows('SELECT s.item, COUNT(*) AS n FROM cm_purchases p JOIN cm_shop_items s ON s.item = p.item GROUP BY s.item ORDER BY n DESC LIMIT 5');
}

cm_page_start('|CelMod| - Statistics');
?>

<h2><span class="celColor">|CelMod| Statistics</span><br>
</h2>
<?php if (!$db): cm_db_offline_notice(); else: ?>
<p><b>All statistics are live from the server.</b></p>
<p>
	<span class="stat"><b><?= number_format((int)$totals['players']) ?></b>Players</span>
	<span class="stat"><b><?= number_format($activeWeek) ?></b>Active This Week</span>
	<span class="stat"><b><?= number_format($newToday) ?></b>New Today</span>
	<br>
	<span class="stat"><b><?= number_format((int)$totals['props']) ?></b>Props Spawned</span>
	<span class="stat"><b><?= number_format((int)$totals['cels']) ?></b>Cels Spawned</span>
	<span class="stat"><b><?= number_format((int)$totals['effects']) ?></b>Effects Spawned</span>
</p>
<p>Total Connections: <span class='highlight'><?= number_format((int)$totals['connections']) ?></span><br>
Total Server Time (all players): <span class='highlight'><?= h(cm_duration((int)$totals['playtime'])) ?></span><br>
Total Cleers in Circulation: <span class='highlight'><?= h(cm_cl($totals['economy'])) ?></span><br>
Total Shop Purchases: <span class='highlight'><?= number_format($purchases) ?></span> (<span class='highlight'><?= number_format($webOrders) ?></span> from the website)<br>
</p>

<p><b>:Players Online (Last 24 Hours):</b></p>
<?= cm_bar_chart($hourly, 'players at peak') ?>

<p><b>:Joins Per Day:</b></p>
<?= cm_bar_chart($daily, 'joins') ?>

<p><b>:Props Spawned Per Day:</b></p>
<?= cm_bar_chart($dailyProps, 'props') ?>

<?php if ($maps): ?>
<p><b>:Most Played Maps:</b></p>
<table class="cmtable">
<tr><th>Map</th><th class="num">Times Played</th><th class="num">Last Played</th></tr>
<?php foreach ($maps as $m): ?>
<tr><td><span class="h"><?= h($m['map']) ?></span></td><td class="num"><?= number_format((int)$m['times_played']) ?></td><td class="num"><?= h(cm_ago($m['last_played'])) ?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<?php if ($popular): ?>
<p><b>:Most Popular Shop Items:</b></p>
<table class="cmtable">
<?php foreach ($popular as $p): ?>
<tr><td><span class="h"><?= h($p['item']) ?></span></td><td class="num"><?= number_format((int)$p['n']) ?> owner<?= (int)$p['n'] === 1 ? '' : 's' ?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<p><a href="leaderboard.php"><b>View the Leaderboards</b></a> - <a href="live.php"><b>Live Server</b></a></p>
<?php endif; ?>

<p><b><a href="javascript:void(0);" onclick="var s=document.getElementById('legacy');s.style.display=s.style.display=='none'?'block':'none';"><span class="h">Classic Statistics (2011 - 2014)</span></a></b></p>
<div id="legacy" style="display: none;">
<p>Total Players Spawned (Since January 2011): <span class='highlight'>10642</span><br>
Total Players to Save a Build: <span class='highlight'>1607</span><br>
Total Builds: <span class='highlight'>14153</span><br>
Total Blastdoors: <span class='highlight'>147039</span><br>
Total PHX: <span class='highlight'>129384</span><br>
Total Walls: <span class='highlight'>199622</span><br>
Total Explosive Drums: <span class='highlight'>5822</span><br>
Total Melons: <span class='highlight'>2726</span><br>
Total Dolls: <span class='highlight'>23444</span><br>
Total Cels: <span class='highlight'>41335</span><br>
Total Bits: <span class='highlight'>50050</span><br>
Total Big Rocks: <span class='highlight'>54</span><br>
<a href="embstats.php">Total Rocks</a>: <span class='highlight'>1677</span><br>
Total File Size(builds): <span class='highlight'>90.1 MB</span><br>
Total Server Time: <span class='highlight'>777 days (2.13 years)</span><br>
Total |CelMod| Time: <span class='highlight'>1074 days (2.94 years)</span><br>
<br>
<b>These stats are based on build team and land bans.</b><br>
<br>
Nicest Player: <span class='highlight'>Deti_Partii_doberman</span><br>
Most Liked Player: <span class='highlight'>MrChaos</span><br>
Meanest Player: <span class='highlight'>Sanek</span><br>
Most Hated Player: <span class='highlight'>Sanek</span><br>
</p>
</div>
<?php cm_page_end(); ?>
