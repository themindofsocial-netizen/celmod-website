<?php
require __DIR__ . '/includes/bootstrap.php';

$server = cm_db() ? cm_server_status() : null;
$address = cm_config('server')['address'];

function cm_tile(string $href, string $icon, string $label, string $sub = ''): void
{
	echo '<a class="tile" href="' . $href . '"><img src="images/' . $icon . '" alt=""><span>' . $label
		. ($sub !== '' ? '<small>' . $sub . '</small>' : '') . "</span></a>\n";
}

cm_page_start('|CelMod| - A Living Project');
?>

<h2><span class="celColor">|CelMod| Website</span><br>
</h2>
<?php if ($server && $server['online']): ?>
<p><span class="grassy">&#9679;</span> <b><?= (int)$server['players'] ?>/<?= (int)$server['max_players'] ?></b> players building on <span class="h"><?= h($server['map']) ?></span> right now.<br>
<a href="steam://connect/<?= h($address) ?>"><b>Join them!</b></a></p>
<?php else: ?>
<p>Choose a page to go to:</p>
<?php endif; ?>

<div class="group-title">- On The Server -</div>
<div class="tiles">
<?php
cm_tile('live.php', 'icn_updates.png', 'Live Server', 'Who is building right now');
cm_tile('shop.php', 'icn_shop.png', 'The Shop', 'Unlock commands and props');
cm_tile('statistics.php', 'icn_orgupdates.png', 'Statistics', 'Players, props and maps');
cm_tile('leaderboard.php', 'icn_props.png', 'Leaderboards', 'Richest and busiest builders');
?>
</div>

<div class="group-title">- Building Guides -</div>
<div class="tiles">
<?php
cm_tile('cmds.php', 'icn_cmds.png', 'Commands List', 'Every chat command');
cm_tile('props/', 'icn_props.png', 'Props List', 'Spawnable props A - Z');
cm_tile('colors.php', 'icn_colors.png', 'Colors List', 'For !color and !paint');
cm_tile('effects.php', 'icn_fx.png', 'Effects List', 'For !effect');
cm_tile('emblem-editor/', 'icn_fx.png', 'Emblem Editor', 'Design emblems in 3D');
cm_tile('buyhelp.php', 'icn_shop.png', 'Bank Help', 'Earning and spending CL');
?>
</div>

<div class="group-title">- The Project -</div>
<div class="tiles">
<?php
cm_tile('updates.php', 'icn_updates.png', 'Updates', 'What changed lately');
cm_tile('originalupdates.php', 'icn_orgupdates.png', 'Original Updates', 'The 2009 - 2014 changelog');
cm_tile('maphistory.php', 'icn_props.png', 'Map History', 'Park and friends');
cm_tile('https://github.com/rockzehh/celmod', 'icn_github-code.png', 'GitHub Repository', 'The plugin source');
?>
</div>

<p>Extras:<br>
<a href="emblems.php"><b>Emblems Info.</b></a><br>
<!--<a href="top5.php"><b>Top 5 Builders</b></a><br>
<a href="embstats.php"><b>Emblem Stats.</b></a><br>
<a href="anims.php"><b>Animation Help</b></a><br>
<a href="music.php"><b>Music List</b></a><br>
<a href="sounds.php"><b>Sounds List</b></a><br>-->
<a href="steam://connect/<?= h($address) ?>"><b>Connect to <?= h(cm_config('server')['name']) ?></b></a></p>
<?php cm_page_end(); ?>
