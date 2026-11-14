<?php require __DIR__ . '/includes/bootstrap.php'; cm_head('|CelMod| - Updates', '', ['wide' => true]); ?>
<?php cm_logo(); ?>
<div id="main">
<div id="content" class="left">
    <div style="text-align:center"><h2><span class="celColor">|CelMod|</span> Updates<br>
</h2></div>
<p><span class="headColor">Latest Updates</span><br>
New commands in <b><i>bold italics</i></b>, existing commands in <i>normal italics</i>.<br>
Chat commands in <span class="highlight">green italics</span>.<br>

<table class="changelog" border="0" bordercolor="#000000" style="background-color:#333333" cellpadding="10" cellspacing="3">
    <tr><td><b>-=- 12/18/26: </b>Emblem overhaul! Emblems made of something real now look like it and leave their mark where they hit.<br>
    <span class="indent"></span>Water, Tsunami and Geyser splash and soak the ground; Acid and Venom splatter, hiss and stain; Magma, Ember and Comet scorch and leave fires burning.<br>
    <span class="indent"></span>Frost, Glacier and Blizzard shatter into ice; Sandstorm and Tornado kick up dust and rubble; lasers and lightning leave burn marks; Nova and Supernova leave craters.<br>
    <span class="indent"></span>Auras are now saved for a few special emblems: <span class="highlight">Demon</span>, <span class="highlight">Phantom</span>, <span class="highlight">Nuke</span> and <span class="highlight">Solar</span>.</td></tr>
    <tr><td><b>-=- 12/05/26: </b>New top tier emblems, made at the Power Booth: <span class="highlight">Volcano</span>, <span class="highlight">Permafrost</span>, <span class="highlight">Maelstrom</span>, <span class="highlight">Tempest</span> and <span class="highlight">Plague</span>.<br>
    <span class="indent"></span>Upgrade paths (3 of an emblem): Ember &gt; Magma &gt; Volcano, Frost &gt; Glacier &gt; Permafrost, Water &gt; Tsunami &gt; Maelstrom, Spark &gt; Thunder &gt; Tempest, Acid &gt; Venom &gt; Plague.<br>
    <span class="indent"></span>The metals upgrade too: Bronze &gt; Gold &gt; Platinum &gt; Diamond. And Nova &gt; Supernova, Pulse &gt; Ion.</td></tr>
    <tr><td><b>-=- 11/21/26: </b>Celweps reworked: the <span class="highlight">Melon Launcher</span> lobs melons that roll and explode, the <span class="highlight">Saw Gun</span> fires spinning saw blades, the <span class="highlight">Blade Bow</span> fires swords that stick where they land.<br>
    <span class="indent"></span>The <span class="highlight">Fire Sword</span> sets players alight, the <span class="highlight">Ice Blade</span> freezes them solid and the <span class="highlight">Power Blade</span> hits hard.<br>
    <span class="indent"></span>Admins can resize hats and held items with <span class="highlight">!nudge hat scale</span>/<i>v_nudge</i> and <span class="highlight">!tune</span>/<i>v_tune</i>.</td></tr>
    <tr><td><b>-=- 11/07/26: </b>The shop now sells CelMod's own props all year: albums, cake, core balls, Delaware, Hoopie, the piano, pineapple, signs, Sotora and TVs.<br>
    <span class="indent"></span>Seasonal shops open around each holiday: the <span class="highlight">Halloween Shop</span>, <span class="highlight">Christmas Shop</span>, <span class="highlight">New Year Shop</span> and <span class="highlight">Clock Day Shop</span>. See them with <span class="highlight">!seasons</span>/<b><i>v_seasons</i></b>.<br>
    <span class="indent"></span>Bought seasonal props are yours to spawn all year.</td></tr>
    <tr><td><b>-=- 10/24/26: </b>The <span class="highlight">Halloween Shop</span> is open! Pumpkins, Jack-o'-Lanterns and Halloween gifts, until November 1st.</td></tr>
    <tr><td><b>-=- 10/10/26: </b>Event items: admins can put an emblem, hat or celwep at the end of a maze or parkour course with <span class="highlight">!eventitem</span>/<b><i>v_eventitem</i></b>.<br>
    <span class="indent"></span>Press E on it to claim it. Everyone can claim each event item once, and only once.<br>
    <span class="indent"></span>Event areas no longer let you phase, blink, open portals or use teleport pads to get in, and movement emblems rest while you are inside.</td></tr>
    <tr><td><b>-=- 09/19/26: </b>Build teams are back: <span class="highlight">!addteam</span>/<b><i>v_addteam</i></b> lets a friend build in your land and edit your props there; <span class="highlight">!delteam</span>/<b><i>v_delteam</i></b> removes them.<br>
    <span class="indent"></span>The land owner saves and loads the whole team build with <i>!save</i>/<i>!load</i>.<br>
    <span class="indent"></span>Added <span class="highlight">!block &lt;player&gt;</span>/<b><i>v_block</i></b> to keep yourself out of a player's land.</td></tr>
    <tr><td><b>-=- 08/29/26: </b>Music cels now play full songs: spawn one with <span class="highlight">!music</span>/<i>v_music</i> and press E to play or stop it. See the songs with <span class="highlight">!celmusic</span>/<b><i>v_celmusic</i></b>.</td></tr>
    <tr><td><b>-=- 08/15/26: </b>Happy Clock Day 2026! The <span class="highlight">Strawberry Clock</span> is back in the Clock Day Shop.</td></tr>
    <tr><td><b>-=- 08/01/26: </b>Tap your walk key (left Alt) to change your walking speed: normal, slow, very slow, super slow and fast.<br>
    <span class="indent"></span>Annoying when you Alt-Tab? Turn it off with <span class="highlight">!speed</span>/<b><i>v_speed</i></b>.</td></tr>
    <tr><td><b>-=- 07/11/26: </b>Land modes are fixed: <span class="highlight">!coop</span>/<i>v_coop</i> keeps players in your land from hurting each other, and <span class="highlight">!gravity</span>/<i>v_gravity</i> takes sensible values again.<br>
    <span class="indent"></span>Players in a co-op land show a co-op skull over their head, and players in a deathmatch land a deathmatch skull.</td></tr>
    <tr><td><b>-=- 06/20/26: </b>Cover your land's floor with one of 26 land skins using <span class="highlight">!landskin</span>/<b><i>v_landskin</i></b>, and recolor its border with <span class="highlight">!landcolor</span>/<b><i>v_landcolor</i></b>.<br>
    <span class="indent"></span><span class="strikeout">!landskin has been added but is currently broken.</span> Fixed at last!</td></tr>
    <tr><td><b>-=- 06/06/26: </b>The website is live again: see who is online, the shop, statistics, the leaderboard and player profiles, and buy from the shop straight from the site.<br>
    <span class="indent"></span>Design your own emblems in 3D in the <span class="highlight">Emblem Editor</span> and suggest them to the admins.<br>
    <span class="indent"></span>Server announcements now show in chat.</td></tr>
    <tr><td><b>-=- 05/16/26: </b>Hats! Press E on a hat you own to wear it, or use <span class="highlight">!hat</span>/<b><i>v_hat</i></b>. See yourself with <span class="highlight">!thirdperson</span>/<b><i>v_thirdperson</i></b>.<br>
    <span class="indent"></span>Player models: browse them with <span class="highlight">!playermodels</span>/<b><i>v_playermodels</i></b>, buy one with <span class="highlight">!buymodel</span>/<b><i>v_buymodel</i></b> and wear it with <span class="highlight">!playermodel</span>/<b><i>v_playermodel</i></b>.</td></tr>
    <tr><td><b>-=- 04/25/26: </b>The Guess Booth is open! Dr. Kleiner (cone and all) shows three props; pick the winner with <span class="highlight">!guess</span>/<b><i>v_guess</i></b> for a prize, once per map.<br>
    <span class="indent"></span>Admins can auction items above the booth with <span class="highlight">!auction</span>/<b><i>v_auction</i></b>, and everyone can <span class="highlight">!bid</span>/<b><i>v_bid</i></b> on them.</td></tr>
    <tr><td><b>-=- 04/04/26: </b>The Power Booth is back, hidden somewhere different on every map. Bring your emblems to it and press E to upgrade them; 5 of one emblem make a big one.<br>
    <span class="indent"></span>It only works once per map before it blows itself up, so get there first.</td></tr>
    <tr><td><b>-=- 03/14/26: </b>Celweps: the axe, swords, pickaxe, shovel, sunflower and guns are real weapons now. Press E on a celwep you own to take it, or spawn one with <span class="highlight">!weapon</span>/<b><i>v_weapon</i></b>.<br>
    <span class="indent"></span>Dig with the shovel for junk, cleers boxes and the rare Black and White emblems, and drop junk in trash cans for a little money.</td></tr>
    <tr><td><b>-=- 02/21/26: </b>Emblem drops: emblem boxes fall from the sky every so often. Hold one with your gravity gun when the map changes, or get it into your land, to keep the emblem inside.</td></tr>
    <tr><td><b>-=- 02/07/26: </b>More economy: <span class="highlight">!give</span>/<b><i>v_give</i></b> and <span class="highlight">!trade</span>/<b><i>v_trade</i></b> hand entities over, and <span class="highlight">!cl</span>/<b><i>v_cl</i></b> drops money as a cleers box only you can open (or give away).<br>
    <span class="indent"></span>Your tactics level now grows as you play, build and fight. Check it with <span class="highlight">!tacticlvl</span>/<b><i>v_tacticlvl</i></b>.</td></tr>
    <tr><td><b>-=- 01/24/26: </b>The emblem pool grows to 90 emblems, from Magma and Glacier to Rocket, Ghost, Vampire and Diamond, each with its own power and look.</td></tr>
    <tr><td><b>-=- 01/10/26: </b>Emblems are back! Press E on an emblem you own to enable it, then hold your gravity gun to use its power. Others see it in your hands.<br>
    <span class="indent"></span>See what you own with <span class="highlight">!inventory</span>/<b><i>v_inventory</i></b>, and spawn one with <span class="highlight">!emblem</span>/<b><i>v_emblem</i></b>.</td></tr>
    <tr><td><b>-=- 06/30/25: </b>Added cels <span class="highlight">!music</span>/<b><i>v_music</i></b> and <span class="highlight">!sound</span>/<b><i>v_sound</i></b> to spawn music and sound cels.<br>
    <tr><td><b>-=- 06/09/25: </b>Added commands <span class="highlight">!replace</span>/<b><i>v_replace</i></b> to replace the model of a prop and <span class="highlight">!owner</span>/<b><i>v_owner</i></b> to print the owner of the entity.<br>
    <span class="indent"></span>Updated <span class="highlight">!lock</span>/<b><i>v_lock</i></b> and <span class="highlight">!unlock</span>/<b><i>v_unlock</i></b> to be their own commands instead of being hidden in <i>!freeze</i>/<i>!unfreeze</i>.<br>
    <span class="indent"></span>Added command <span class="highlight">!drop</span>/<b><i>v_drop</i></b> to drop entities to the floor.<br>
    <tr><td><b>-=- 06/03/25: </b>Started work on control bits, spawn one with <span class="highlight">!button</span>/<b><i>v_button</i></b> and link it to a cel using <span class="highlight">!link</span>/<b><i>v_link</i></b>.<br>
    <span class="indent"></span>Fixed !skin next/prev to automatically scroll through all skins on a prop.<br>
    <tr><td><b>-=- 05/30/25: </b>Added <span class="highlight">!ladder</span>/<b><i>v_ladder</i></b> and <span class="highlight">!light</span>/<b><i>v_light</i></b> to spawn in ladder and light cels. Updated <span class="strikeout">+grab</span> to <span class="highlight">+move</span>.<br>
    <span class="indent"></span>Added bits for weapons, ammo, and chargers.<br>
    <span class="indent"></span><span class="highlight">!ammo</span>/<b><i>v_ammo</i></b><br>
    <span class="indent"></span><span class="highlight">!ammocrate</span>/<b><i>v_ammocrate</i></b><br>
    <span class="indent"></span><span class="highlight">!charger</span>/<b><i>v_charger</i></b><br>
    <span class="indent"></span><span class="highlight">!wep</span>/<b><i>v_wep</i></b></td></tr>
    <tr><td><b>-=- 05/08/25: </b>Added a blacklist to blacklist props without altering the prop list.<br>
    <tr><td><b>-=- 05/01/25: </b>Added <span class="highlight">!copy</span>/<b><i>v_copy</i></b>, <span class="highlight">!paste</span>/<b><i>v_paste</i></b>, <span class="highlight">+copy</span>, <span class="strikeout">+grab</span>, <span class="highlight">!skin</span>.<br>
    <span class="indent"></span>Updated <span class="highlight">!color rainbow</span> and <i>!fadecolor</i> to use <span class="highlight">HSV</span> calculations on the colors.</td></tr>
    <tr><td><b>-=- 04/27/25: </b>Added <span class="highlight">!fadecolor</span>/<b><i>v_fadecolor</i></b> to fade between two colors.<br>
    <span class="indent"></span>Added the <span class="highlight">rainbow</span> color to !color.</td></tr>
    <tr><td><b>-=- 04/26/25: </b>Added <span class="highlight">!emitter</span>/<b><i>v_emitter</i></b> as an alias for <i>!effect</i>. Added <span class="highlight">!switch</span>/<b><i>v_switch</i></b> to switch the side of the screen the HUD is on.<br>
    <span class="indent"></span>Added <span class="highlight">!stack</span>/<b><i>v_stack</i></b> and <span class="highlight">!stackinfo</span>/<b><i>v_stackinfo</i></b> for easier building.</td></tr>
    <tr><td><b>-=- 04/24/25: </b>Added <span class="highlight">!balance</span>/<b><i>v_balance</i></b>, <span class="highlight">!buy</span>/<b><i>v_buy</i></b>, and <span class="highlight">!sell</span>/<b><i>v_sell</i></b> to the server.<br>
    <span class="indent"></span>More economy features are being worked on. <span class="strikeout">!landskin has been added but is currently broken.</span></td></tr>
    <tr><td><b>-=- 08/11/24: </b>Added command <span class="highlight">!renderfx</span>/<b><i>v_renderfx</i></b> to change the RenderFX on entities.<br>
    <span class="indent"></span>Added command history HUD and the ablitiy to spawn a prop without the <span class="highlight">!spawn</span>/<b><i>v_spawn</i></b> command. Ex. <span class="highlight">!blastdoor3</span>, <span class="highlight">/blastdoor3</span></td></tr>
    <tr><td><b>-=- 04/23/24: </b>Added commands <span class="highlight">!dm</span>/<b><i>v_dm</i></b> and <span class="highlight">!gravity</span>/<b><i>v_gravity</i></b> to change the respective settings for your land.<br>
    <span class="indent"></span>Added more command options for <span class="highlight">!alpha (all)</span>, <span class="highlight">!color (all, hud)</span> and <span class="highlight">!delete (all, land)</span>.</td></tr>
</td></table>
</div>
</div>
<?php cm_footer_bar(); ?>
<script type="text/javascript" src="<?= cm_base() ?>scripts/updates.js"></script>
<?php cm_bg_script(); ?>
</html>
