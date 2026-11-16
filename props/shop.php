<?php require __DIR__ . '/../includes/bootstrap.php'; cm_page_start('|CelMod| - Shop Props'); ?>
<!-- Written by tools/export_props.py from the server's shop.txt. -->
<h2><span class="celColor">|CelMod| Shop Props</span><br>
</h2>
<p>These props are CelItems: each one you buy is one copy, kept in your <b>!bank</b>. You can only have as many out
as you own, and !give, !trade or selling one makes it the other player's for good. Buy them in the
<a href="../shop.php">shop</a> or in game with <span class="highlight">!buy prop_&lt;name&gt;</span>.
Seasonal props are only sold in their season.</p>
<table class="cmtable">
<tr><th>Prop</th><th>Spawn with</th><th>Category</th><th>Price</th><th>Sold</th></tr>
<tr><td><b>Album</b></td><td>!spawn album</td><td>CelMod Props</td><td><?= h(cm_cl(25000)) ?></td><td>All year</td></tr>
<tr><td><b>Album (Volume 3)</b></td><td>!spawn album3</td><td>CelMod Props</td><td><?= h(cm_cl(25000)) ?></td><td>All year</td></tr>
<tr><td><b>Antlion Guard</b></td><td>!spawn antlionguard</td><td>Rare Cels</td><td><?= h(cm_cl(500000)) ?></td><td>All year</td></tr>
<tr><td><b>Cake</b></td><td>!spawn cake</td><td>CelMod Props</td><td><?= h(cm_cl(30000)) ?></td><td>All year</td></tr>
<tr><td><b>Candy Cane</b></td><td>!spawn candycane</td><td>Christmas Shop</td><td><?= h(cm_cl(40000)) ?></td><td>Christmas</td></tr>
<tr><td><b>Core Ball</b></td><td>!spawn coreball</td><td>CelMod Props</td><td><?= h(cm_cl(60000)) ?></td><td>All year</td></tr>
<tr><td><b>Small Core Ball</b></td><td>!spawn coreballsmall</td><td>CelMod Props</td><td><?= h(cm_cl(40000)) ?></td><td>All year</td></tr>
<tr><td><b>Delaware</b></td><td>!spawn delaware</td><td>CelMod Props</td><td><?= h(cm_cl(75000)) ?></td><td>All year</td></tr>
<tr><td><b>Dog</b></td><td>!spawn dog</td><td>Discontinued Props</td><td><?= h(cm_cl(150000)) ?></td><td>All year</td></tr>
<tr><td><b>Flatscreen TV</b></td><td>!spawn flatscreen</td><td>CelMod Props</td><td><?= h(cm_cl(60000)) ?></td><td>All year</td></tr>
<tr><td><b>Halloween Gift</b></td><td>!spawn gift</td><td>Halloween Shop</td><td><?= h(cm_cl(50000)) ?></td><td>Halloween</td></tr>
<tr><td><b>G-Man</b></td><td>!spawn gmanstatue</td><td>Rare Cels</td><td><?= h(cm_cl(750000)) ?></td><td>All year</td></tr>
<tr><td><b>Hoopie</b></td><td>!spawn hoopie</td><td>Shop Exclusives</td><td><?= h(cm_cl(30000)) ?></td><td>All year</td></tr>
<tr><td><b>Hula Doll</b></td><td>!spawn huladoll</td><td>Discontinued Props</td><td><?= h(cm_cl(60000)) ?></td><td>All year</td></tr>
<tr><td><b>Jack-o&#x27;-Lantern</b></td><td>!spawn jackolantern</td><td>Halloween Shop</td><td><?= h(cm_cl(50000)) ?></td><td>Halloween</td></tr>
<tr><td><b>Big Jack-o&#x27;-Lantern</b></td><td>!spawn jackolanternbig</td><td>Halloween Shop</td><td><?= h(cm_cl(80000)) ?></td><td>Halloween</td></tr>
<tr><td><b>Manhack</b></td><td>!spawn manhack</td><td>Rare Cels</td><td><?= h(cm_cl(120000)) ?></td><td>All year</td></tr>
<tr><td><b>Party Hat</b></td><td>!spawn partyhat</td><td>New Year Shop</td><td><?= h(cm_cl(30000)) ?></td><td>Newyears</td></tr>
<tr><td><b>Piano</b></td><td>!spawn piano</td><td>CelMod Props</td><td><?= h(cm_cl(120000)) ?></td><td>All year</td></tr>
<tr><td><b>Pineapple</b></td><td>!spawn pineapple</td><td>CelMod Props</td><td><?= h(cm_cl(20000)) ?></td><td>All year</td></tr>
<tr><td><b>Present</b></td><td>!spawn present</td><td>Christmas Shop</td><td><?= h(cm_cl(50000)) ?></td><td>Christmas</td></tr>
<tr><td><b>Pumpkin</b></td><td>!spawn pumpkin1</td><td>Halloween Shop</td><td><?= h(cm_cl(30000)) ?></td><td>Halloween</td></tr>
<tr><td><b>Pumpkin (Tall)</b></td><td>!spawn pumpkin2</td><td>Halloween Shop</td><td><?= h(cm_cl(30000)) ?></td><td>Halloween</td></tr>
<tr><td><b>Pumpkin (Small)</b></td><td>!spawn pumpkin3</td><td>Halloween Shop</td><td><?= h(cm_cl(30000)) ?></td><td>Halloween</td></tr>
<tr><td><b>Rollermine</b></td><td>!spawn rollermine</td><td>Rare Cels</td><td><?= h(cm_cl(90000)) ?></td><td>All year</td></tr>
<tr><td><b>Santa Hat</b></td><td>!spawn santahat</td><td>Christmas Shop</td><td><?= h(cm_cl(40000)) ?></td><td>Christmas</td></tr>
<tr><td><b>Strawberry Clock</b></td><td>!spawn sbc</td><td>Clock Day Shop</td><td><?= h(cm_cl(100000)) ?></td><td>Clockday</td></tr>
<tr><td><b>Sign</b></td><td>!spawn sign</td><td>CelMod Props</td><td><?= h(cm_cl(15000)) ?></td><td>All year</td></tr>
<tr><td><b>Wooden Sign</b></td><td>!spawn sign1</td><td>CelMod Props</td><td><?= h(cm_cl(15000)) ?></td><td>All year</td></tr>
<tr><td><b>SMG</b></td><td>!spawn smg</td><td>Discontinued Props</td><td><?= h(cm_cl(40000)) ?></td><td>All year</td></tr>
<tr><td><b>Sotora</b></td><td>!spawn sotora</td><td>CelMod Props</td><td><?= h(cm_cl(50000)) ?></td><td>All year</td></tr>
<tr><td><b>SP Prop</b></td><td>!spawn spprop</td><td>CelMod Props</td><td><?= h(cm_cl(40000)) ?></td><td>All year</td></tr>
<tr><td><b>Stalker</b></td><td>!spawn stalker</td><td>Rare Cels</td><td><?= h(cm_cl(250000)) ?></td><td>All year</td></tr>
<tr><td><b>Small TV</b></td><td>!spawn tvscreen</td><td>CelMod Props</td><td><?= h(cm_cl(35000)) ?></td><td>All year</td></tr>
<tr><td><b>Christmas Tree</b></td><td>!spawn xmastree</td><td>Christmas Shop</td><td><?= h(cm_cl(100000)) ?></td><td>Christmas</td></tr>
<tr><td><b>Year Hat</b></td><td>!spawn yearhat</td><td>New Year Shop</td><td><?= h(cm_cl(40000)) ?></td><td>Newyears</td></tr>
</table>
<div class="btngrid"><a href="./">Back to Props List</a></div>
<?php cm_page_end(); ?>
