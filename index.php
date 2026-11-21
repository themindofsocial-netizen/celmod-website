<?php
// Some hosts send every missing address here (with "200 OK"). For FastDL that's harmful: the game saves this page
// as the texture or model it asked for, and the player keeps a broken file. Anything that isn't the home page itself
// gets a real "404 Not Found" instead.
$cmHome = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$cmPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
if (!in_array($cmPath, [$cmHome, $cmHome . '/', $cmHome . '/index.php'], true)) {
	http_response_code(404);
	header('Content-Type: text/plain; charset=utf-8');
	echo 'Not found.';
	exit;
}
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/updates.php';

$server = cm_db() ? cm_server_status() : null;
$address = cm_server_address();

function cm_tile(string $href, string $icon, string $label, string $sub = ''): void
{
	echo '<a class="tile" href="' . $href . '"><img src="images/' . $icon . '" alt=""><span>' . $label
		. ($sub !== '' ? '<small>' . $sub . '</small>' : '') . "</span></a>\n";
}

cm_page_start('|CelMod| - A Living Project');
?>

<h2><span class="celColor">|CelMod| Website</span><br>
</h2>
<?php $online = $server && $server['online']; ?>
<div class="join-card<?= $online ? '' : ' offline' ?>">
	<div class="status">
		<?php if ($online): ?>
		<span class="pulse"></span><?= (int)$server['players'] ?>/<?= (int)$server['max_players'] ?> building on <span class="h"><?= h($server['map']) ?></span>
		<small>Claim a land, spawn props and build anything. Join the ride!</small>
		<?php elseif ($server): ?>
		<span class="pulse"></span>The server is offline right now
		<small>It'll be back soon. Meanwhile, read the guides or plan a build.</small>
		<?php else: ?>
		<span class="pulse" style="background:var(--gold);animation:none"></span>A Half-Life 2: Deathmatch build server
		<small>Claim a land, spawn props and build anything. Join the ride!</small>
		<?php endif; ?>
	</div>
	<div class="actions">
		<span class="addr" data-copy="connect <?= h($address) ?>" data-copy-label="Copied! Paste it into the game's console." title="Click to copy the console command"><?= h($address) ?></span>
		<button type="button" class="cmbutton copy-btn" data-copy="connect <?= h($address) ?>" data-copy-label="Copied! Paste it into the game's console.">Copy</button>
		<a class="cmbutton go" href="steam://connect/<?= h($address) ?>">Join the server</a>
	</div>
</div>

<?php $latest = cm_all_updates()[0] ?? null; if ($latest): ?>
<div class="panel" style="text-align:left">
	<b class="celColor">Latest update</b> <span class="muted"><?= h(date('F j, Y', strtotime($latest['date']))) ?></span><br>
	<?php
	// The items of an update are separated by an empty indent marker: shown as a list.
	$items = array_filter(array_map('trim', explode('<span class="indent"></span>', $latest['html'])), 'strlen');
	if (count($items) > 1): ?>
	<ul class="update-list foldable"><?php foreach ($items as $item): ?><li><?= $item ?></li><?php endforeach; ?></ul>
	<?php else: ?>
	<?= $latest['html'] ?><br>
	<?php endif; ?>
	<a href="updates.php"><b>All updates</b></a>
</div>
<?php endif; ?>

<div class="group-title">- On The Server -</div>
<div class="tiles">
<?php
cm_tile('live.php', 'icn_updates.png', 'Live Server', 'Who is building right now');
cm_tile('shop.php', 'icn_shop.png', 'The Shop', 'Unlock commands and props');
cm_tile('statistics.php', 'icn_orgupdates.png', 'Statistics', 'Players, props and maps');
cm_tile('leaderboard.php', 'icn_props.png', 'Leaderboards', 'Richest, busiest and most hated');
?>
</div>

<div class="group-title">- Building Guides -</div>
<div class="tiles">
<?php
cm_tile('cmds.php', 'icn_cmds.png', 'Commands List', 'Every chat command');
cm_tile('props/', 'icn_props.png', 'Props List', 'Spawnable props A - Z');
cm_tile('colors.php', 'icn_colors.png', 'Colors List', 'For !color and !paint');
cm_tile('effects.php', 'icn_fx.png', 'Effects List', 'For !effect');
cm_tile('renderfx.php', 'icn_fx.png', 'Render Effects', 'For !renderfx');
cm_tile('sounds.php', 'icn_cmds.png', 'Sounds List', 'For !sound, searchable');
cm_tile('music.php', 'icn_updates.png', 'Music List', 'For !music');
cm_tile('emblems.php', 'icn_fx.png', 'Emblems', 'Every emblem, power and upgrade');
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
<a href="lists.php"><b>All the lists</b></a><br>
<!--<a href="top5.php"><b>Top 5 Builders</b></a><br>
<a href="embstats.php"><b>Emblem Stats.</b></a><br>
<a href="anims.php"><b>Animation Help</b></a><br>
-->
<a href="steam://connect/<?= h($address) ?>"><b>Connect to <?= h(cm_config('server')['name']) ?></b></a></p>
<?php cm_page_end(); ?>
