<?php require __DIR__ . '/includes/bootstrap.php'; cm_page_start('|CelMod| - Guides'); ?>

<h2><span class="celColor">|CelMod| Guides</span><br>
</h2>
<p class="guide-intro">How to build, make money, level up and collect on the server. Every command goes in chat (press <span class="h">Y</span>);
in the console type <b>v_</b> instead of <b>!</b>. The full list is on the <a href="cmds.php"><b>Commands</b></a> page.</p>

<div class="btngrid">
<a href="#start">Getting Started</a>
<a href="#build">Building</a>
<a href="#saves">Saving Builds</a>
<a href="#money">Making Money</a>
<a href="#bank">Bank &amp; CelItems</a>
<a href="#xp">Tactics XP</a>
<a href="#emblems">Emblems</a>
<a href="#digging">Digging</a>
<a href="#npcs">NPCs &amp; Spawn Pads</a>
</div>

<div class="guide" id="start">
<h3><span class="celColor">Getting Started</span></h3>
<ol>
<li>Claim a land: aim at one corner of the ground and type <span class="h">!land</span>, then aim at the other corner and type it again. Everything you build inside is yours, and only you and your build team (<span class="h">!addteam &lt;player&gt;</span>) can change it.</li>
<li>Spawn props by name: <span class="h">!spawn woodcrate1</span> or just <span class="h">!woodcrate1</span>. Browse them on the <a href="props/"><b>Props List</b></a>.</li>
<li>Props spawn frozen and unbreakable. Pick one up with the gravity gun after <span class="h">!unfreeze</span>, and <span class="h">!freeze</span> it again where you want it.</li>
<li>Your money and Tactics level show on the HUD. <span class="h">!balance</span> shows your cleers (CL).</li>
</ol>
</div>

<div class="guide" id="build">
<h3><span class="celColor">Building</span></h3>
<p><b>Placing:</b> <span class="h">+move</span> (bind it to a key) carries the prop you look at; <span class="h">!smove &lt;x&gt; &lt;y&gt; &lt;z&gt;</span> nudges it along its own axes; <span class="h">!rotate &lt;x&gt; &lt;y&gt; &lt;z&gt;</span> and <span class="h">!stand</span> set its angle; <span class="h">!drop</span> puts it on the floor. <span class="h">!freezeall</span> and <span class="h">!unfreezeall</span> freeze or unfreeze everything you own.</p>
<p><b>Repeating:</b> <span class="h">!stack &lt;x&gt; &lt;y&gt; &lt;z&gt; [copies]</span> makes up to 10 copies in a row (<span class="h">!stack 0 0 50 5</span> builds a tower); <span class="h">!stackinfo</span> on two props tells you the offset between them. <span class="h">+copy</span> copies a prop and carries the copy.</p>
<p><b>Joining:</b> <span class="h">!weld</span> on one prop, then on another, makes the first move with the second. <span class="h">!release</span> undoes it.</p>
<p><b>Protecting:</b> <span class="h">!perm</span> locks a prop in place: it can't be moved, turned, unfrozen or deleted until <span class="h">!unperm</span>. Perm the walls of your house before you rearrange the furniture.</p>
<p><b>Looks:</b> <span class="h">!color</span> (<a href="colors.php">colors</a>), <span class="h">!skin</span>, <span class="h">!alpha</span>, <span class="h">!renderfx</span>, <span class="h">!effect</span> (<a href="effects.php">effects</a>), <span class="h">!landskin 1-26</span> for your land's floor, and <span class="h">!text &lt;words&gt;</span> to write on a wall.</p>
<p><b>Cels:</b> <span class="h">!door</span>, <span class="h">!ladder</span>, <span class="h">!light</span>, <span class="h">!internet</span> (a web page cel), <span class="h">!music</span>, <span class="h">!sound</span>, <span class="h">!message</span>, <span class="h">!teleport</span>, <span class="h">!spawnpoint</span>.</p>
<p><b>Scenes:</b> NPC props such as <span class="h">!alyx</span> or <span class="h">!combine1</span> are cyclers: shoot one to change its pose, press <span class="h">E</span> on it to pause or play. Saved builds keep each pose.</p>
<p><b>Your land:</b> <span class="h">!coop</span>, <span class="h">!deathmatch</span>, <span class="h">!gravity</span>, <span class="h">!weps</span> (weapons off for visitors), <span class="h">!nofly</span> (noclip off for visitors), <span class="h">!landban</span>, <span class="h">!landkick</span>, <span class="h">!fee &lt;amount&gt;</span> (visitors pay to come in) and <span class="h">!buildmessage</span>.</p>
<p><b>Limits:</b> 200 props and 20 cels each by default, plus 50 bank items (emblems, hats, celweps, shop props and hobos you have out), which don't count toward the other two. <span class="h">!delall</span> removes everything you own; <span class="h">!undo</span> removes the last thing you spawned.</p>
</div>

<div class="guide" id="saves">
<h3><span class="celColor">Saving Builds</span></h3>
<p><span class="h">!save &lt;name&gt;</span> saves everything in your land, <span class="h">!load &lt;name&gt;</span> rebuilds it in your land on any map, and <span class="h">!saves</span> lists your saves, newest first. <span class="h">!clear &lt;name&gt;</span> deletes one. Your land has to be big enough, and the build has to fit in your prop limit.</p>
<p>Save often: builds vanish when the map changes, saves don't.</p>
</div>

<div class="guide" id="money">
<h3><span class="celColor">Making Money</span></h3>
<p><b>Dig:</b> swing a shovel or pickaxe celwep at the ground. Most finds are junk worth 1-3 CL in a trash can, but you can also dig up money boxes (100-1,500 CL), ores worth up to 45 CL (diamond), hobos, Vials and rare emblems. See <a href="#digging">Digging</a>.</p>
<p><b>Guess Booth:</b> <span class="h">!guess &lt;1/2/3 or the prop's name&gt;</span> once per map. A right guess pays 5,000 CL. Items auctioned above it go to the top <span class="h">!bid</span>.</p>
<p><b>Sell and trade:</b> <span class="h">!sell &lt;price&gt;</span> on something you own puts it up for sale; another player buys it with <span class="h">!buy</span>. <span class="h">!trade &lt;player&gt;</span> swaps items, and <span class="h">!give &lt;player&gt;</span> hands one over.</p>
<p><b>Your land:</b> <span class="h">!fee &lt;amount&gt;</span> charges visitors to come in, and the money goes straight to you.</p>
<p><b>Gold emblem:</b> while it's enabled it pays you every minute.</p>
<p><b>Cleers boxes:</b> <span class="h">!cl &lt;amount&gt;</span> drops your money in a box only you can open; look at it and <span class="h">!give &lt;player&gt;</span> to pay someone.</p>
</div>

<div class="guide" id="bank">
<h3><span class="celColor">Bank &amp; CelItems</span></h3>
<p><span class="h">!bank</span> lists everything you own, numbered, and opens the bank menu (press ESC): CelItems (shop and event props, dug-up finds), celweps and emblems. <span class="h">!bank &lt;number&gt;</span> takes one out where you aim; <span class="h">!bank add</span> puts the CelItem, emblem or celwep you're looking at back.</p>
<p>Everything in the bank is finite. Buying a <a href="props/shop.php">shop prop</a> gives you one copy, and only as many copies as you own can be out at once. Giving, trading or selling one moves that copy to the other player for good.</p>
</div>

<div class="guide" id="xp">
<h3><span class="celColor">Tactics XP</span></h3>
<p>Your Tactics level goes up with experience; each level needs a little more than the last (50 for the first, then 10 more each level). <span class="h">!tacticlvl</span> shows your level and progress. Experience comes from:</p>
<ul>
<li><b>Playing:</b> 3 XP every minute.</li>
<li><b>Building:</b> 1 XP for spawning a prop or cel (once every 3 seconds at most).</li>
<li><b>Fighting:</b> 5 XP per player kill, and 1 XP for every 50 health of an NPC you kill (enemies from spawn pads give none).</li>
<li><b>Events:</b> 50 XP for reaching the end of a maze or parkour course, 15 XP for other reward pickups.</li>
<li><b>Emblems:</b> 10 XP for keeping an emblem box, 15 XP for upgrading at the Power Booth.</li>
<li><b>Guess Booth:</b> 15 XP for a right guess.</li>
</ul>
<p>Fastest way up: play events and hunt NPCs in the dungeons, and keep building.</p>
</div>

<div class="guide" id="emblems">
<h3><span class="celColor">Emblems</span></h3>
<p><b>Emblem drops:</b> now and then emblem boxes fall from the sky. Grab one with the gravity gun and hold on to it until the map changes: whoever is holding a box when the map ends keeps its emblem. Boxes get rarer near the end of a map.</p>
<p><b>Using them:</b> <span class="h">!emblems</span> lists yours, <span class="h">!emblem &lt;name&gt;</span> spawns or enables one, and the enabled emblem shows on your gravity gun. Most emblems have a power on left or right click. Powers don't work in event areas, though the emblem still shows.</p>
<p><b>Power Booth:</b> it appears somewhere on the map. Bring copies of the same emblem to it (5 by default) and press <span class="h">E</span> to upgrade them into a bigger one.</p>
<p><b>Digging:</b> the Black and White emblems can only be dug up. A Vial (see below) tells you when one is buried close by.</p>
<p><b>Hats:</b> <span class="h">!hat &lt;name&gt;</span> puts one on; <span class="h">!thirdperson</span> lets you see your hat and emblem. Design your own emblem in the <a href="emblem-editor/"><b>Emblem Editor</b></a>.</p>
</div>

<div class="guide" id="digging">
<h3><span class="celColor">Digging</span></h3>
<p>Swing a digging celwep (the shovel or pickaxe) at open ground. What comes up:</p>
<ul>
<li><b>Junk</b> (most finds): carry it to a trash can for 1-3 CL.</li>
<li><b>Money boxes</b> (5%): press E for 100-1,500 CL.</li>
<li><b>Black or White emblem</b> (0.01%: 1 in 10,000 finds).</li>
<li><b>Vial</b> (2.5%): press E on it and it glows green when a rare emblem is buried right around it. Dig inside the ring. It needs 20 seconds to recharge.</li>
<li><b>Hobos</b>, and very rarely <b>turds and ores</b>: coal, iron, copper, silver, gold, emerald, ruby and diamond, each rarer and worth more in a trash can.</li>
</ul>
<p>Put finds you want to keep in your bank with <span class="h">!bank add</span>; <span class="h">!del</span> removes one you don't.</p>
</div>

<div class="guide" id="npcs">
<h3><span class="celColor">NPCs &amp; Spawn Pads</span></h3>
<p>NPCs live in the dungeons and other spots on some maps. <span class="h">!npckills</span> lists everything you've killed; <span class="h">!npckills zombie</span> shows each kind of zombie, and <span class="h">!npckills &lt;type&gt; &lt;player&gt;</span> shows someone else's.</p>
<p>Kill enough of one kind and you earn a spawn pad for it: <span class="h">!spawnpads</span> shows your progress, and <span class="h">!spawnpad &lt;enemy&gt;</span> places one in your land.</p>
</div>

<?php cm_page_end(); ?>
