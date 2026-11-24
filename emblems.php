<?php
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/drops.php';

// Every emblem on the server, read live from its collectibles.txt (the same list the game uses).
$emblems = cm_drop_emblems();

// What each power does, in a few words.
$powers = [
	'none'         => 'Looks only',
	'shot'         => 'Left click shoots',
	'flamethrower' => 'Flamethrower',
	'fire'         => 'Sets targets alight',
	'blizzard'     => 'Hurts and slows',
	'shock'        => 'Lightning',
	'push'         => 'Shoves players away',
	'blast'        => 'Explosion where you aim',
	'spray'        => 'Harmless spray',
	'medic'        => 'Heals players',
	'magnitude'    => 'Throws things',
	'portal'       => 'Linked portals',
	'chaos'        => 'Fiery eruption',
	'demon'        => 'Demon towers',
	'blink'        => 'Teleport where you aim',
	'flash'        => 'Blinds players facing you',
	'smog'         => 'Choking smoke cloud',
	'nuke'         => 'Headcrab canister strike',
	'speed'        => 'Run faster',
	'sprint'       => 'Endless sprint',
	'gravity'      => 'Lighter gravity',
	'jump'         => 'Jump higher',
	'feather'      => 'Fall slowly',
	'heal'         => 'Regenerate health',
	'armor'        => 'Regenerate suit power',
	'resist'       => 'Take less damage',
	'cloak'        => 'Turn see-through',
	'drain'        => 'Hurts its holder',
	'gold'         => 'Earn money',
	'phase'        => 'Pass through walls',
	'time'         => 'Fast-forward and rewind',
	'boss'         => "Wide Eyes' blood laser and Bloodletting",
	'gas'          => 'Sprays a gas',
	'waterwalk'    => 'Walk on water',
];

// Where an emblem comes from: upgrades, events, digging...
$sources = [
	'upgrade' => 'Power Booth upgrade', 'event' => 'Events', 'dig' => 'Digging', 'christmas' => 'Christmas',
	'shop' => 'The shop', 'halloween' => 'Halloween',
];

// Which emblem each one is upgraded from.
$from = [];
foreach ($emblems as $id => $e) {
	$to = strtolower($e['kv']['upgradesto'] ?? '');
	if ($to !== '') {
		$from[$to][] = $id;
	}
}

cm_page_start('|CelMod| - Emblems', '', ['wide' => true]);
?>
<h2><span class="celColor">|CelMod| Emblems</span><br>
</h2>
<div class="prose guide-intro">
<p>Emblems are rare: hold one out with the gravity gun and it gives you its power (left and right click), or just its
look. You find them at <b>emblem drops</b> (an admin's or the server's), now and then in a build you made, by
<b>digging</b>, and at events. Keep yours in <span class="h">!bank</span>, and take one out with
<span class="h">!emblem &lt;name&gt;</span>.</p>
<p>At the <b>Power Booth</b>, a few copies of an emblem turn into its upgrade. Upgraded basic emblems use the second
skin and have <b>(S)</b> in their name. Any emblem also has a <b>big</b> version, made the same way, that's about half
again as strong. The red <b>Breaker Booth</b>, which some maps get instead, does the opposite: it takes a big or
upgraded emblem apart into the emblems it was made from. Powers don't work in event areas.</p>
</div>

<?php if (!$emblems): ?>
<p class="notice">The emblem list isn't available right now.</p>
<?php else: ?>
<div class="searchbar"><input type="search" id="embfind" placeholder="Find an emblem or power... (press /)" aria-label="Find an emblem" autocomplete="off">
<span class="count" id="embcount" aria-live="polite"></span></div>
<div class="tabs" id="embtabs">
	<a href="#" data-r="all" class="active">All <span class="count"><?= count($emblems) ?></span></a>
	<a href="#" data-r="special">Special</a>
</div>

<div class="drop-grid emb-grid">
<?php foreach ($emblems as $id => $e):
	$kv = $e['kv'];
	$power = strtolower($kv['power'] ?? 'none');
	$special = !$e['default'];
	$source = $e['exclusive'] !== '' ? ($sources[strtolower($e['exclusive'])] ?? ucfirst($e['exclusive'])) : ($e['weight'] > 0 ? 'Drops' : 'Not dropped');
	$swatch = cm_drop_swatch($e['color']);
	$find = strtolower($id . ' ' . $e['name'] . ' ' . $power . ' ' . ($powers[$power] ?? '') . ' ' . $source);
?>
	<div class="drop-card emb-card" data-r="<?= $special ? 'special' : 'standard' ?>" data-find="<?= h($find) ?>" id="e-<?= h($id) ?>">
		<span class="swatch" style="<?= $swatch !== '' ? 'background:' . h($swatch) : '' ?>"></span>
		<div class="drop-name">
			<b data-copy="!emblem <?= h($id) ?>" title="Copy !emblem <?= h($id) ?>"><?= h($e['name']) ?></b>
<br>
			<span class="muted"><?= h($powers[$power] ?? ucfirst($power)) ?> &middot; <?= h($source) ?></span>
			<?php if (!empty($kv['upgradesto']) && isset($emblems[strtolower($kv['upgradesto'])])): ?>
			<br><span class="muted">Upgrades to <a href="#e-<?= h(strtolower($kv['upgradesto'])) ?>"><?= h($emblems[strtolower($kv['upgradesto'])]['name']) ?></a> (<?= (int)($kv['upgradecost'] ?? 3) ?> copies)</span>
			<?php endif; ?>
			<?php if (!empty($from[$id])): ?>
			<br><span class="muted">Upgraded from <?= implode(', ', array_map(static fn ($f) => '<a href="#e-' . h($f) . '">' . h($emblems[$f]['name']) . '</a>', $from[$id])) ?></span>
			<?php endif; ?>
		</div>
	</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="btngrid"><a href="emblem-editor/">Design your own in the Emblem Editor</a> <a href="guides#emblems">Emblems guide</a></div>
<?php cm_page_end(<<<'JS'
<script>
(function () {
	var box = document.getElementById('embfind'), count = document.getElementById('embcount'), rarity = 'all';
	if (!box) { return; }
	var cards = document.querySelectorAll('.emb-card'), tabs = document.querySelectorAll('#embtabs a');
	function apply() {
		var q = box.value.trim().toLowerCase(), shown = 0;
		cards.forEach(function (c) {
			var ok = (rarity === 'all' || c.getAttribute('data-r') === rarity) && (q === '' || c.getAttribute('data-find').indexOf(q) !== -1);
			c.style.display = ok ? '' : 'none';
			if (ok) { shown++; }
		});
		count.textContent = (q === '' && rarity === 'all') ? '' : (shown === 0 ? 'No emblems' : shown + ' emblem' + (shown === 1 ? '' : 's'));
	}
	tabs.forEach(function (t) {
		t.addEventListener('click', function (e) {
			e.preventDefault();
			tabs.forEach(function (x) { x.classList.remove('active'); });
			t.classList.add('active');
			rarity = t.getAttribute('data-r');
			apply();
		});
	});
	box.addEventListener('input', apply);
})();
</script>
JS
); ?>
