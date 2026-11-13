<?php
require __DIR__ . '/../includes/bootstrap.php';

$head = <<<'HEAD'
<style type="text/css">
#editor { text-align: left; }
#editor .title { text-align: center; margin: 14px 0 6px; }
#editor .hint { color: #AA8; font-size: 0.8em; margin: 6px 0 0; }
#editor code { color: #71E100; }
#list { list-style: none; margin: 0; padding: 0; max-height: 190px; overflow: auto; columns: 3; column-gap: 6px; }
.ed-cols { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 12px; align-items: start; }
@media (max-width: 860px) { .ed-cols { grid-template-columns: 1fr; } #list { columns: 2; } }
#list li { padding: 3px 6px; cursor: pointer; display: flex; gap: 6px; align-items: center; break-inside: avoid; }
#list li:nth-child(odd) { background-color: #444444; }
#list li:hover { color: #FFCD5B; }
#list li.sel { background-color: #222222; color: #71E100; font-weight: bold; }
#list .dot { width: 11px; height: 11px; border-radius: 50%; border: 1px solid black; flex: none; }
#stage { width: 100%; aspect-ratio: 4 / 3; background-color: #222222; border: 1px solid black; display: block; touch-action: none; cursor: grab; }
.skins { display: grid; grid-template-columns: repeat(9, 1fr); gap: 4px; margin-top: 8px; }
.skins button { height: 36px; border: 1px solid black; cursor: pointer; color: #fff; text-shadow: 0 0 3px #000, 0 0 3px #000; font-family: Pu; font-weight: bold; background-size: cover; background-position: center; padding: 0; }
.skins button:hover { outline: 1px solid #FFCD5B; }
.skins button.sel { outline: 2px solid #71E100; }
#form { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 0; }
#form label { display: flex; flex-direction: column; gap: 2px; font-size: 0.8em; color: #9CF; min-width: 0; }
#form label.wide { grid-column: 1 / -1; }
#form label[hidden] { display: none; }
#editor input, #editor select, #editor textarea { font-family: Pu; font-size: 13px; color: #FFB200; background-color: #222222; border: 1px solid black; padding: 4px 6px; width: 100%; min-width: 0; box-sizing: border-box; }
#editor input:focus, #editor select:focus, #editor textarea:focus { outline: 1px solid #FFCD5B; }
#editor input[type=color] { padding: 1px; height: 28px; }
#editor .row { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; align-items: center; }
#editor .cmbutton { font-size: 13px; }
#editor label.cmbutton { display: inline-block; color: #FFB200; }
#editor textarea { font: 12px/1.4 Consolas, monospace; color: #71E100; min-height: 220px; resize: vertical; white-space: pre; tab-size: 4; }
#status { color: #71E100; font-size: 0.85em; }
</style>
HEAD;

$user = cm_user();
$mine = ($user && cm_db()) ? cm_rows('SELECT id, emblem_id, title, status, admin_note, created_at FROM cm_emblem_suggestions WHERE steamid64 = ? ORDER BY id DESC LIMIT 10', [$user['steamid64']]) : [];
$open = null;
if (isset($_GET['suggestion']) && $user && cm_db()) {
	$open = cm_row('SELECT id, steamid64, player_name, kv FROM cm_emblem_suggestions WHERE id = ?', [(int)$_GET['suggestion']]);
	if ($open && !cm_is_admin() && $open['steamid64'] !== $user['steamid64']) {
		$open = null;
	}
}
$head .= '<meta name="csrf" content="' . h(cm_csrf_token()) . '">';

cm_page_start('|CelMod| - Emblem Editor', $head, ['wide' => true]);
?>

<h2><span class="celColor">|CelMod| Emblem Editor</span><br>
</h2>
<p>Design your own emblem, see it in 3D, and suggest it to the admins.</p>
<p class="notice">Everything you change here stays in <b>your browser</b>. New, Delete and edits only change your copy:<br>
the emblems on the server can't be changed or removed from this page.</p>
<?php if ($open): ?>
<p class="flash h"><b>Opened suggestion #<?= (int)$open['id'] ?> by <?= h($open['player_name']) ?>.</b></p>
<script type="application/json" id="preload"><?= json_encode($open['kv'], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php else: ?>
<p class="muted">The editor opens with every emblem on the server. Pick one to start from and press Duplicate, or press New.</p>
<?php endif; ?>

<div id="editor">
	<p class="title"><b>:Emblems:</b></p>
	<div class="panel">
		<ul id="list"></ul>
		<div class="row">
			<button class="cmbutton" id="add">New</button>
			<button class="cmbutton" id="dup">Duplicate</button>
			<button class="cmbutton" id="del">Delete</button>
			<button class="cmbutton" id="server">Load the server's emblems</button>
		</div>
	</div>

	<div class="ed-cols">
	<div>
	<p class="title"><b>:Preview:</b></p>
	<div class="panel">
		<canvas id="stage"></canvas>
		<p class="hint">Drag to rotate, scroll to zoom. The selected skin plays its animation. Skins that use stock Half-Life 2 textures are shown as a flat stand-in color.</p>
		<div class="skins" id="skins"></div>
	</div>

	</div>
	<div>
	<p class="title"><b>:Settings:</b></p>
	<div class="panel">
		<form id="form" autocomplete="off">
			<label>Id (what players type)<input name="id" pattern="[a-z0-9_]+" required></label>
			<label>Name<input name="name"></label>
			<label>Color<input name="color" type="color"></label>
			<label>Drop weight (0 = never drops)<input name="dropweight" type="number" min="0" step="1"></label>
			<label class="wide">Power<select name="power"></select></label>
			<label data-f="value"><span id="valueLabel">Value</span><input name="value" type="number" step="any"></label>
			<label data-f="cooldown">Cooldown (seconds)<input name="cooldown" type="number" min="0.05" step="any"></label>
			<label data-f="range">Range (units)<input name="range" type="number" min="1" step="any"></label>
			<label data-f="duration">Duration (seconds)<input name="duration" type="number" min="0" step="any"></label>
			<label data-f="dmgtype">Damage type<select name="dmgtype"><option>generic</option><option>burn</option><option>drown</option><option>poison</option><option>shock</option><option>dissolve</option><option>energy</option></select></label>
			<label data-f="ignite">Ignite target (seconds)<input name="ignite" type="number" min="0" step="any"></label>
			<label data-f="slow">Slow target (seconds)<input name="slow" type="number" min="0" step="any"></label>
			<label data-f="knockback">Knockback force<input name="knockback" type="number" min="0" step="any"></label>
			<label>Speed multiplier while held<input name="speed" type="number" min="0.1" step="any"></label>
			<label class="wide">Right click attack<select name="secondary"><option value="none">None</option><option value="fireball">Fireball: flies and explodes</option><option value="icestorm">Ice storm: burst that hurts and slows</option><option value="bigshot">Big shot: heavy bolt with recoil</option></select></label>
			<label data-f="secondaryvalue">Right click damage / blast size<input name="secondaryvalue" type="number" min="0" step="any"></label>
			<label data-f="secondarycooldown">Right click cooldown (seconds)<input name="secondarycooldown" type="number" min="0.1" step="any"></label>
			<label data-f="fx">Shot effect<select name="fx"></select></label>
			<label data-f="sprite" class="wide">Effect sprite<input name="sprite" list="sprites"></label>
			<label data-f="beamcolor">Effect color<input name="beamcolor" type="color"></label>
			<label data-f="beamcolor">Use emblem color for effect<select name="beamauto"><option value="1">Yes</option><option value="0">No, use effect color</option></select></label>
			<label class="wide">Model<input name="model"></label>
			<label>Hand scale<input name="handscale" type="number" min="0.01" step="any"></label>
			<label>World scale<input name="scale" type="number" min="0.01" step="any"></label>
			<label>Hand offset (forward left up)<input name="handoffset"></label>
			<label>Hand angles (pitch yaw roll)<input name="handangles"></label>
		</form>
		<datalist id="sprites">
			<option value="sprites/flamelet1.vmt"><option value="sprites/glow04_noz.vmt"><option value="sprites/plasmaember.vmt">
			<option value="sprites/steam1.vmt"><option value="sprites/purpleglow1.vmt"><option value="sprites/blueglow2.vmt">
			<option value="sprites/orangeglow1.vmt"><option value="sprites/redglow1.vmt"><option value="sprites/light_glow03.vmt">
		</datalist>
	</div>

	</div>
	</div>

	<p class="title"><b>:Suggest This Emblem:</b></p>
	<div class="panel">
<?php if (!$user): ?>
		<p><a href="../login.php?return=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/emblem-editor/') ?>"><b>Sign in through Steam</b></a> to suggest your emblem to the admins.</p>
<?php else: ?>
		<p class="hint" style="margin-top:0">Sends the selected emblem to the admins. It needs a new id (not one already on the server) and a name.
		Admins add the ones they like to the server by hand.</p>
		<label class="wide" style="display:flex;flex-direction:column;gap:2px;font-size:0.8em;color:#9CF">Note for the admins (optional)
			<textarea id="note" maxlength="500" style="min-height:60px;white-space:normal;font-family:Pu;color:#FFB200" placeholder="What is it for? How should it drop?"></textarea></label>
		<div class="row">
			<button class="cmbutton" id="suggest">Suggest this emblem</button>
			<span id="suggestStatus"></span>
		</div>
<?php if ($mine): ?>
		<p class="title" style="margin-top:12px">Your suggestions</p>
		<table class="cmtable">
<?php foreach ($mine as $m): ?>
			<tr>
				<td><a href="?suggestion=<?= (int)$m['id'] ?>"><b><?= h($m['title']) ?></b></a> <span class="muted"><?= h($m['emblem_id']) ?></span></td>
				<td><span class="<?= ['accepted' => 'grassy', 'rejected' => 'bloody', 'withdrawn' => 'muted'][$m['status']] ?? 'highlight' ?>"><?= h(ucfirst($m['status'])) ?></span><?= $m['admin_note'] !== '' ? '<br><span class="muted">' . h($m['admin_note']) . '</span>' : '' ?></td>
				<td class="num"><?= h(cm_ago($m['created_at'])) ?></td>
				<td class="num"><?php if ($m['status'] === 'pending'): ?><button class="cmbutton" data-withdraw="<?= (int)$m['id'] ?>">Withdraw</button><?php endif; ?></td>
			</tr>
<?php endforeach; ?>
		</table>
<?php endif; ?>
<?php endif; ?>
	</div>

	<p class="title"><b>:Export / Import:</b></p>
	<div class="panel">
		<textarea id="text" spellcheck="false"></textarea>
		<div class="row">
			<button class="cmbutton" id="copy">Copy this emblem</button>
			<button class="cmbutton" id="showAll">Show all emblems</button>
			<button class="cmbutton" id="download">Download emblems.txt</button>
			<button class="cmbutton" id="import">Import from the box above</button>
			<label class="cmbutton">Import a file<input type="file" id="file" accept=".txt" hidden></label>
		</div>
		<p class="row"><span id="status"></span></p>
		<p class="hint">Import accepts a whole <code>collectibles.txt</code>, just its "Emblems" section, or one or more emblem blocks. Keys this editor does not know are kept as they are.</p>
	</div>
</div>
<p><a href="../emblems.php"><b>About Emblems</b></a></p>
<?php cm_page_end('<script type="module" src="editor.js"></script>' . "\n"); ?>
