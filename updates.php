<?php require __DIR__ . '/includes/bootstrap.php'; cm_head('|CelMod| - Updates'); ?>
    <?php cm_logo(false); ?>
    <h2><span class="celColor">|CelMod|</span> Updates<br>
</h2>
<p><span class="headColor">Latest Updates</span><br>
New commands in <b><i>bold italics</i></b>, existing commands in <i>normal italics</i>.<br>
Chat commands in <span class="highlight">green italics</span>.<br>

<table border="0" bordercolor="#000000" style="background-color:#333333" width="1100" cellpadding="10" cellspacing="3">
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
<script type="text/javascript" src="<?= cm_base() ?>scripts/updates.js"></script>
<script type="text/javascript">
disableSelection(document.body)
</script>
</html>
