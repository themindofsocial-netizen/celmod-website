<?php require __DIR__ . '/includes/bootstrap.php'; cm_page_start('|CelMod| - Guides'); ?>

<h2><span class="celColor">|CelMod| Guides</span><br>
</h2>
<p class="guide-intro">Everything you need to build, earn, level up and collect on the server. Commands go in chat (press <span class="h">Y</span>);
in the console type <b>v_</b> instead of <b>!</b>. Every command is on the <a href="cmds.php"><b>Commands</b></a> page, and every prop on the <a href="props/"><b>Props</b></a> list.</p>

<div class="btngrid">
<a href="#start">Quick Start</a>
<a href="#build">Building</a>
<a href="#protect">Protecting</a>
<a href="#land">Your Land</a>
<a href="#saves">Saving</a>
<a href="#money">Money</a>
<a href="#bank">Bank</a>
<a href="#xp">Tactics</a>
<a href="#emblems">Emblems</a>
<a href="#digging">Digging</a>
<a href="#npcs">Dungeons</a>
<a href="#rules">Areas &amp; Rules</a>
</div>

<div class="guide" id="start">
<h3><span class="celColor">Quick Start</span></h3>
<ol>
<li><b>Claim a land.</b> Aim at one corner of the ground and type <span class="h">!land</span>, then aim at the opposite corner and type it again. Only you and your build team can build in it.</li>
<li><b>Spawn props.</b> Type a prop's name: <span class="h">!woodcrate1</span> (or <span class="h">!spawn woodcrate1</span>). Add <b>drop</b>, <b>unfrozen</b> or <b>nogod</b> after the name for options.</li>
<li><b>Move them.</b> Props spawn frozen. Bind <span class="h">+move</span> to a key and hold it to carry a prop, or <span class="h">!unfreeze</span> it and use the gravity gun.</li>
<li><b>Save your work.</b> <span class="h">!save mybuild</span> keeps it forever; <span class="h">!load mybuild</span> brings it back on any map.</li>
<li><b>Earn and collect.</b> Dig, play events, hunt dungeon enemies and trade. Your cleers (CL) and Tactics level are on your HUD.</li>
</ol>
<p><b>Handy keys:</b> <span class="h">!cmds</span> all commands &middot; <span class="h">!undo</span> remove the last thing you spawned &middot; <span class="h">!stuck</span> respawn &middot;
<span class="h">!fly</span> noclip &middot; <span class="h">!thirdperson</span> see yourself &middot; <span class="h">!switch</span> move the HUD &middot; <span class="h">!hudcolor red</span> recolour it.</p>
</div>

<div class="guide" id="build">
<h3><span class="celColor">Building</span></h3>
<p><b>Placing:</b> <span class="h">+move</span> carries a prop; <span class="h">!smove &lt;x&gt; &lt;y&gt; &lt;z&gt;</span> nudges it along its own axes; <span class="h">!drop</span> puts it on the floor;
<span class="h">!moveto</span> (on a prop, then on another) puts the first on the second.</p>
<p><b>Turning:</b> <span class="h">!rotate &lt;x&gt; &lt;y&gt; &lt;z&gt;</span>, or one axis at a time with <span class="h">!flip</span>, <span class="h">!r</span> and <span class="h">!roll</span>. Props turn around their own axes, so <span class="h">!roll 45</span> then <span class="h">!roll -45</span> always puts it back. <span class="h">!straight</span> resets the angles.</p>
<p><b>Repeating:</b> <span class="h">!stack &lt;x&gt; &lt;y&gt; &lt;z&gt; [copies]</span> makes up to 10 copies in a row (<span class="h">!stack 0 0 50 5</span> is a tower), and <span class="h">!stackinfo</span> on two props gives you the offset between them.
<span class="h">+copy</span> copies a prop and carries the copy; <span class="h">!copy</span> and <span class="h">!paste</span> keep it for later.</p>
<p><b>Joining:</b> <span class="h">!weld</span> on one prop, then on another, makes the first move with the second. <span class="h">!release</span> undoes it.</p>
<p><b>Looks:</b> <span class="h">!color</span> (see <a href="colors.php">colors</a>), <span class="h">!skin</span> (on its own it tells you how many skins a prop has), <span class="h">!alpha</span>, <span class="h">!renderfx</span>, <span class="h">!fadecolor</span>,
<span class="h">!effect</span> (see <a href="effects.php">effects</a>), and <span class="h">!text &lt;words&gt;</span> to write on a wall.</p>
<p><b>Cels:</b> working pieces like <span class="h">!door</span>, <span class="h">!ladder</span>, <span class="h">!light</span>, <span class="h">!internet</span> (a web page), <span class="h">!music</span>, <span class="h">!sound</span>, <span class="h">!message</span>,
<span class="h">!teleport</span>, <span class="h">!spawnpoint</span>, buttons, chargers and ammo crates. A <span class="h">!turret</span> fights enemies and never shoots players.</p>
<p><b>Scenes:</b> NPC props such as <span class="h">!alyx</span> or <span class="h">!combine1</span> are posable: shoot one to change its pose, press <span class="h">E</span> to pause it. Saved builds keep each pose.</p>
<div class="table-scroll"><table class="cmtable">
<tr><th>Limit</th><th>Each player</th></tr>
<tr><td>Props</td><td>200</td></tr>
<tr><td>Cels</td><td>20</td></tr>
<tr><td>Bank items out (emblems, hats, celweps, shop props, hobos)</td><td>50</td></tr>
<tr><td>Turrets</td><td>2</td></tr>
</table></div>
</div>

<div class="guide" id="protect">
<h3><span class="celColor">Protecting Your Build</span></h3>
<p><span class="h">!perm</span> locks a prop: it stays frozen and can't be moved, turned or deleted until <span class="h">!unperm</span>. Perm the walls of your house before you rearrange the furniture.</p>
<p><span class="h">!freezeall</span> and <span class="h">!unfreezeall</span> freeze or unfreeze everything you own (perm and welded props stay frozen). <span class="h">!delall</span> removes everything you own.</p>
<p>Nobody can build in your land unless you add them with <span class="h">!addteam &lt;player&gt;</span> (<span class="h">!delteam</span> removes them), and props can't be carried into it.</p>
</div>

<div class="guide" id="land">
<h3><span class="celColor">Your Land</span></h3>
<ul>
<li><span class="h">!coop</span>: nobody in your land can hurt each other. <span class="h">!deathmatch</span>: players can fight in it (players only hurt each other inside deathmatch lands).</li>
<li><span class="h">!gravity &lt;amount&gt;</span> changes the gravity inside, <span class="h">!landskin 1-26</span> paints the floor, <span class="h">!landcolor</span> recolours the border.</li>
<li><span class="h">!weps</span> turns visitors' weapons off, and <span class="h">!nofly</span> turns their noclip off.</li>
<li><span class="h">!fee &lt;amount&gt;</span> charges visitors to come in; they pay with <span class="h">!fee pay</span> and the money goes to you.</li>
<li><span class="h">!buildmessage &lt;text&gt;</span> greets visitors. <span class="h">!landban</span>, <span class="h">!landkick</span> and <span class="h">!landslay</span> deal with troublemakers.</li>
<li><span class="h">!land #clear</span> removes your land.</li>
</ul>
</div>

<div class="guide" id="saves">
<h3><span class="celColor">Saving Builds</span></h3>
<p><span class="h">!save &lt;name&gt;</span> saves everything in your land, including your build team's props. <span class="h">!load &lt;name&gt;</span> rebuilds it in the middle of your land on any map,
<span class="h">!saves</span> lists your saves (newest first) and <span class="h">!clear &lt;name&gt;</span> deletes one.</p>
<p>Your land has to be big enough, and the build has to fit in your limits. Saves keep colours, skins, welds, perm, poses and cels. Builds vanish when the map changes; saves don't, so save often.</p>
</div>

<div class="guide" id="money">
<h3><span class="celColor">Making Money</span></h3>
<div class="table-scroll"><table class="cmtable">
<tr><th>How</th><th>What you get</th></tr>
<tr><td><b>Digging</b> (<a href="#digging">see below</a>)</td><td>Junk 1-3 CL in a trash can, money boxes 100-1,500 CL, ores up to 5,000 CL</td></tr>
<tr><td><b>Guess Booth</b>: <span class="h">!guess &lt;1/2/3 or the prop's name&gt;</span>, once per map</td><td>5,000 CL for the right prop</td></tr>
<tr><td><b>Selling</b>: <span class="h">!sell &lt;price&gt;</span> on something you own; others <span class="h">!buy</span> it</td><td>Your price</td></tr>
<tr><td><b>Land fee</b>: <span class="h">!fee &lt;amount&gt;</span></td><td>What visitors pay to enter</td></tr>
<tr><td><b>Gold emblem</b> enabled</td><td>A little every minute</td></tr>
</table></div>
<p><span class="h">!balance</span> shows your money. <span class="h">!cl &lt;amount&gt;</span> drops cleers in a box only you can open; look at it and <span class="h">!give &lt;player&gt;</span> to pay someone.
Spend it in the <a href="shop.php"><b>shop</b></a> (in game: <span class="h">!buy</span>) on commands, props, emblems and player models.</p>
</div>

<div class="guide" id="bank">
<h3><span class="celColor">Bank &amp; CelItems</span></h3>
<p><span class="h">!bank</span> lists everything you own, numbered, and opens the bank menu (press ESC): <b>CelItems</b> (shop and event props, dug-up finds), <b>celweps</b> and <b>emblems</b>.
<span class="h">!bank &lt;number&gt;</span> takes one out where you aim; <span class="h">!bank add</span> puts back the item you're looking at.</p>
<p>Everything in the bank is finite: buying a <a href="props/shop.php">shop prop</a> gives you one copy, and only as many copies as you own can be out at once.
Giving, trading or selling one moves it to the other player for good. <span class="h">!stack</span> or <span class="h">+copy</span> on an emblem takes spare copies out of your bank.</p>
</div>

<div class="guide" id="xp">
<h3><span class="celColor">Tactics Levels</span></h3>
<p>Experience fills your Tactics level; each level needs 10 more than the last (50 for the first). <span class="h">!tacticlvl</span> shows your progress.
Every level adds <b>2 to your full health</b>, and at <b>level 75</b> other players see <b>- Veteran -</b> above your name, at <b>level 100</b> <b>-= Legend =-</b>.</p>
<div class="table-scroll"><table class="cmtable">
<tr><th>Doing</th><th>XP</th></tr>
<tr><td>Playing</td><td>3 every minute</td></tr>
<tr><td>Spawning a prop or cel</td><td>1 (at most every 3 seconds)</td></tr>
<tr><td>Killing a player (in a deathmatch land)</td><td>5</td></tr>
<tr><td>Killing an enemy</td><td>1 for every 50 of its health (none from spawn pads)</td></tr>
<tr><td>Finishing an event (maze, parkour)</td><td>50</td></tr>
<tr><td>Other reward pickups</td><td>15</td></tr>
<tr><td>Keeping an emblem box</td><td>10</td></tr>
<tr><td>Upgrading at the Power Booth</td><td>15</td></tr>
<tr><td>A right guess at the Guess Booth</td><td>15</td></tr>
</table></div>
</div>

<div class="guide" id="emblems">
<h3><span class="celColor">Emblems</span></h3>
<p><b>Using them:</b> <span class="h">!emblems</span> lists yours and <span class="h">!emblem &lt;name&gt;</span> enables one; it shows on your gravity gun. Most have a power on left or right click,
for example <b>Time</b>: left click fast-forwards you, right click rewinds you 3 seconds while your shadow trails behind.</p>
<p><b>Getting them:</b></p>
<ul>
<li><b>Emblem drops:</b> at set times (or when an admin starts one) emblem boxes fall from the sky. Grab one with the gravity gun and hold it until the map changes: whoever holds a box then keeps its emblem.</li>
<li><b>Events:</b> mazes and parkour courses often have an emblem at the end.</li>
<li><b>Your builds:</b> keep loading builds and, one day, a free emblem may be waiting in one. There's no message, so look around.</li>
<li><b>Digging:</b> the Black and White emblems can only be dug up (1 in 10,000 finds). A Vial tells you when one is buried nearby.</li>
<li><b>The shop</b>, trading with other players, and Christmas presents (<span class="h">!wrap</span>).</li>
</ul>
<p><b>Power Booth:</b> it stands somewhere on the map. Bring several copies of the same emblem and press <span class="h">E</span> to upgrade them (for example Red Error, then Green, then Blue Error).</p>
<p><b>Hats:</b> <span class="h">!hat &lt;name&gt;</span> puts one on; <span class="h">!thirdperson</span> lets you see your hat and emblem.
Design your own emblem in the <a href="emblem-editor/"><b>Emblem Editor</b></a>.</p>
</div>

<div class="guide" id="digging">
<h3><span class="celColor">Digging</span></h3>
<p>Swing a digging celwep (the shovel or pickaxe) at open ground. Each swing shows how it's going; about 1 swing in 7 finds something.</p>
<div class="table-scroll"><table class="cmtable">
<tr><th>Find</th><th>How often</th><th>What to do</th></tr>
<tr><td>Junk</td><td>Most finds</td><td>Trash can: 1-3 CL</td></tr>
<tr><td>Money box</td><td>2%</td><td>Press E: 100-1,500 CL</td></tr>
<tr><td>Vial</td><td>2.5%</td><td>Press E: glows green when a rare emblem is buried right there. Dig inside the ring.</td></tr>
<tr><td>Hobo</td><td>2%</td><td>Keep it, or bank it</td></tr>
<tr><td>Black or White emblem</td><td>0.01%</td><td>Yours to keep</td></tr>
</table></div>
<p><b>Ores</b> are rare junk finds; sell them in a trash can:</p>
<div class="table-scroll"><table class="cmtable">
<tr><th>Ore</th><th>Chance (of junk)</th><th>Sells for</th></tr>
<tr><td>Turd</td><td>1%</td><td>Nothing</td></tr>
<tr><td>Coal</td><td>0.6%</td><td>30-50 CL</td></tr>
<tr><td>Iron</td><td>0.4%</td><td>50-80 CL</td></tr>
<tr><td>Copper</td><td>0.25%</td><td>80-120 CL</td></tr>
<tr><td>Silver</td><td>0.15%</td><td>150-220 CL</td></tr>
<tr><td>Gold</td><td>0.08%</td><td>300-450 CL</td></tr>
<tr><td>Emerald</td><td>0.04%</td><td>700-1,000 CL</td></tr>
<tr><td>Ruby</td><td>0.03%</td><td>1,000-1,500 CL</td></tr>
<tr><td>Diamond</td><td>0.01%</td><td>3,000-5,000 CL</td></tr>
</table></div>
<p>Keep finds in your bank with <span class="h">!bank add</span>; <span class="h">!del</span> removes one you don't want.</p>
</div>

<div class="guide" id="npcs">
<h3><span class="celColor">Dungeons &amp; Spawn Pads</span></h3>
<p>Some maps have dungeons full of enemies. Every kill gives Tactics XP and a little ammo back for the guns you carry. <span class="h">!npckills</span> lists everything you've killed
(<span class="h">!npckills zombie</span> shows each kind of zombie).</p>
<p>Kill enough of one kind and you earn its <b>spawn pad</b>: <span class="h">!spawnpads</span> shows your progress and <span class="h">!spawnpad &lt;enemy&gt;</span> places one in your land. Pad enemies don't count toward kills.</p>
</div>

<div class="guide" id="rules">
<h3><span class="celColor">Areas &amp; Rules</span></h3>
<p><b>Event areas</b> (mazes, parkour courses): walked, not flown. No building, digging, <span class="h">+move</span> or speed changes inside, and emblem powers rest.
<b>Restricted areas</b> (like dungeons): no flying or <span class="h">+move</span>.</p>
<p><b>Fighting:</b> players only hurt each other inside deathmatch lands. Everyone spawns with the gravity gun and a crowbar, and chargers never run out.</p>
<p><b>Rules:</b> be friendly; don't wreck, steal from or block other players' builds; no exploits, crash props or lag machines; listen to the admins.
Report bugs with <span class="h">!reportbug</span> and problems with players with <span class="h">!dispute</span>.</p>
</div>

<?php cm_page_end(); ?>
