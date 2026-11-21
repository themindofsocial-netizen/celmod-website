<?php require __DIR__ . '/includes/bootstrap.php'; cm_page_start('|CelMod| - Render Effects'); ?>
<!-- Written by tools/export_renderfx.py (celmod repo) from celmod-entity.sp: edit the list there. -->
<h2><span class="celColor">|CelMod| Render Effects</span><br>
</h2>
<p>Look at one of your props and type <span class="h">!renderfx &lt;effect&gt;</span>. In game, <span class="h">!renderfx</span> on its own lists them. <span class="h">!renderfx default</span> takes the effect off again.<br>
Render effects can be part of <span class="h">!animate</span> frames too. Click an effect to copy its command.</p>
<div class="table-scroll"><table class="cmtable">
<tr><th>Effect</th><th>What it looks like</th></tr>
<tr><td><b data-copy="!renderfx default" title="Copy !renderfx default">default</b></td><td>No effect (back to normal)</td></tr>
<tr><td><b data-copy="!renderfx pulse" title="Copy !renderfx pulse">pulse</b></td><td>Fades in and out quickly</td></tr>
<tr><td><b data-copy="!renderfx fade" title="Copy !renderfx fade">fade</b></td><td>Fades away</td></tr>
<tr><td><b data-copy="!renderfx strobe" title="Copy !renderfx strobe">strobe</b></td><td>Flashes on and off</td></tr>
<tr><td><b data-copy="!renderfx flicker" title="Copy !renderfx flicker">flicker</b></td><td>Flickers like a bad light</td></tr>
<tr><td><b data-copy="!renderfx distort" title="Copy !renderfx distort">distort</b></td><td>Wobbles and flickers</td></tr>
<tr><td><b data-copy="!renderfx hologram" title="Copy !renderfx hologram">hologram</b></td><td>Looks like a fading hologram</td></tr>
</table></div>
<div class="btngrid"><a href="colors.php">Colors List</a> <a href="effects.php">Effects List</a> <a href="cmds.php">Commands</a></div>
<?php cm_page_end(); ?>
