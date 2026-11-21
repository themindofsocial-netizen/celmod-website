<?php
require __DIR__ . '/includes/bootstrap.php';

// Every reference list on the site, in one place.
function cm_list_tile(string $href, string $icon, string $label, string $sub): void
{
	echo '<a class="tile" href="' . $href . '"><img src="images/' . $icon . '" alt=""><span>' . h($label)
		. '<small>' . h($sub) . '</small></span></a>' . "\n";
}

cm_page_start('|CelMod| - Lists');
?>
<h2><span class="celColor">|CelMod| Lists</span><br>
</h2>
<p>Everything you can spawn, play and paint, each list searchable (press <kbd>/</kbd>). Click an entry on any list to
copy its command, ready to paste into the game's chat.</p>

<div class="group-title">- Building -</div>
<div class="tiles">
<?php
cm_list_tile('props/', 'icn_props.png', 'Props', 'Everything !spawn knows, A to Z');
cm_list_tile('props/shop.php', 'icn_shop.png', 'Shop Props', 'Rare props you buy one at a time');
cm_list_tile('colors.php', 'icn_colors.png', 'Colors', 'For !color and !paint');
cm_list_tile('renderfx.php', 'icn_fx.png', 'Render Effects', 'For !renderfx');
cm_list_tile('effects.php', 'icn_fx.png', 'Effects', 'For !effect');
cm_list_tile('cmds.php', 'icn_cmds.png', 'Commands', 'Every chat command');
?>
</div>

<div class="group-title">- Sound -</div>
<div class="tiles">
<?php
cm_list_tile('sounds.php', 'icn_cmds.png', 'Sounds', 'Every Half-Life 2 sound, for !sound');
cm_list_tile('music.php', 'icn_updates.png', 'Music', 'Songs for !music');
?>
</div>

<div class="group-title">- Collecting -</div>
<div class="tiles">
<?php
cm_list_tile('emblems.php', 'icn_fx.png', 'Emblems', 'Every emblem, its power and upgrade');
cm_list_tile('emblem-editor/', 'icn_fx.png', 'Emblem Editor', 'Design an emblem in 3D');
?>
</div>
<?php cm_page_end(); ?>
