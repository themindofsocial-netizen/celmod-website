<?php
require __DIR__ . '/includes/bootstrap.php';

$server = cm_db() ? cm_server_status() : null;
$address = cm_config('server')['address'];

cm_page_start('|CelMod| - A Living Project');
?>

<h2><span class="celColor">|CelMod| Website</span><br>
</h2>
<?php if ($server && $server['online']): ?>
<p><span class="grassy">&#9679;</span> <b><?= (int)$server['players'] ?>/<?= (int)$server['max_players'] ?></b> players building on <span class="h"><?= h($server['map']) ?></span> right now.</p>
<?php endif; ?>
<p>Choose a page to go to:</p>
<p><a href="live.php"><div id="listview"><img id="listimg" src="images/icn_updates.png"/> Live Server <img id="listimg" src="images/icn_updates.png"/></div></a></p>
<p><a href="shop.php"><div id="listview"><img id="listimg" src="images/icn_shop.png"/> The Shop <img id="listimg" src="images/icn_shop.png"/></div></a></p>
<p><a href="statistics.php"><div id="listview"><img id="listimg" src="images/icn_orgupdates.png"/> Statistics <img id="listimg" src="images/icn_orgupdates.png"/></div></a></p>
<p><a href="leaderboard.php"><div id="listview"><img id="listimg" src="images/icn_props.png"/> Leaderboards <img id="listimg" src="images/icn_props.png"/></div></a></p>
<p><a href="emblem-editor/"><div id="listview"><img id="listimg" src="images/icn_fx.png"/> Emblem Editor <img id="listimg" src="images/icn_fx.png"/></div></a></p>
<p><a href="cmds.php"><div id="listview"><img id="listimg" src="images/icn_cmds.png"/> Commands List <img id="listimg" src="images/icn_cmds.png"/></div></a></p>
<p><a href="props/"><div id="listview"><img id="listimg" src="images/icn_props.png"/> Props List <img id="listimg" src="images/icn_props.png"/></div></a></p>
<p><a href="colors.php"><div id="listview"><img id="listimg" src="images/icn_colors.png"/> Colors List <img id="listimg" src="images/icn_colors.png"/></div></a></p>
<!--<p><a href="music.php"><div id="listview"><img id="listimg" src="images/icn_music.png"/> Music List <img id="listimg" src="images/icn_music.png"/></div></a></p>
<!--<p><a href="sounds.php"><div id="listview"><img id="listimg" src="images/icn_sounds.png"/> Sounds List <img id="listimg" src="images/icn_sounds.png"/></div></a></p>-->
<p><a href="effects.php"><div id="listview"><img id="listimg" src="images/icn_fx.png"/> Effects List <img id="listimg" src="images/icn_fx.png"/></div></a></p>
<p><a href="updates.php"><div id="listview"><img id="listimg" src="images/icn_updates.png"/> Updates <img id="listimg" src="images/icn_updates.png"/></div></a></p>
<p><a href="originalupdates.php"><div id="listview"><img id="listimg" src="./images/icn_orgupdates.png"/> Original Updates <img id="listimg" src="./images/icn_orgupdates.png"/></div></a></p>
<p><a href="https://github.com/rockzehh/celmod"><div id="listview"><img id="listimg" src="./images/icn_github-code.png"/> GitHub Repository <img id="listimg" src="./images/icn_github-code.png"/></div></a></p>
<br>
Extras:<br>
<!--<a href="top5.php"><b>Top 5 Builders</b></a><br>-->
<a href="emblems.php"><b>Emblems Info.</b></a><br>
<!--<a href="embstats.php"><b>Emblem Stats.</b></a><br>
<a href="anims.php"><b>Animation Help</b></a><br>-->
<a href="buyhelp.php"><b>Bank Help</b></a><br>
<a href="maphistory.php"><b>Map History</b></a><br>
<!--<a href="maparchive.php"><b>Map Archive</b></a><br>-->
<a href="steam://connect/<?= h($address) ?>"><b>Connect to <?= h(cm_config('server')['name']) ?></b></a><br>
<?php cm_page_end(); ?>
