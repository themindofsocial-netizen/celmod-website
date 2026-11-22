<?php require __DIR__ . '/includes/bootstrap.php'; cm_page_start('|CelMod| - Effects'); ?>
<!-- Written by tools/export_reference.py (celmod repo) from the server's files: edit those, not this. -->
<h2><span class="celColor">|CelMod| Effects</span><br>
</h2>
<p>Aim where it should go and type <span class="h">!effect &lt;effect&gt;</span>: it spawns an effect cel. Press <b>E</b> on it (or <span class="h">!link</span> a button to it) to turn it on and off, and <span class="h">!color</span> it to change its color. Click an effect to copy its command.</p>
<div class="table-scroll"><table class="cmtable">
<tr><th>Type</th><th>Effect</th><th>What it does</th></tr>
<tr><td><b data-copy="!effect core" title="Copy !effect core">core</b></td><td>Citadel energy core</td><td>A crackling ball of Combine energy.</td></tr>
<tr><td><b data-copy="!effect fire" title="Copy !effect fire">fire</b></td><td>Fire</td><td>A fire that burns until you put it out.</td></tr>
<tr><td><b data-copy="!effect shake" title="Copy !effect shake">shake</b></td><td>Earthquake</td><td>Shakes the screen of everyone nearby.</td></tr>
<tr><td><b data-copy="!effect smokestack" title="Copy !effect smokestack">smokestack</b></td><td>Smoke stack</td><td>A column of rising smoke.</td></tr>
<tr><td><b data-copy="!effect spotlight" title="Copy !effect spotlight">spotlight</b></td><td>Spotlight</td><td>A beam of light pointing the way the cel faces.</td></tr>
<tr><td><b data-copy="!effect steam" title="Copy !effect steam">steam</b></td><td>Steam</td><td>A jet of steam.</td></tr>
</table></div>
<div class="btngrid"><a href="renderfx.php">Render Effects</a> <a href="colors.php">Colors</a> <a href="lists.php">All Lists</a></div>
<?php cm_page_end(); ?>
