<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/updates.php';

$updates = cm_all_updates();

// Years for the filter, newest first.
$years = [];
foreach ($updates as $u) {
	$years[(int)substr($u['date'], 0, 4)] = true;
}
krsort($years);

cm_page_start('|CelMod| - Updates', '', ['wide' => true]);
?>
<h2><span class="celColor">|CelMod| Updates</span><br>
</h2>
<p>What changed on the server, newest first. <span class="highlight">Green</span> words are chat commands.
Looking for something? Search, or pick a year.</p>

<div class="searchbar"><input type="search" id="updfind" placeholder="Search the updates... (press /)" aria-label="Search the updates" autocomplete="off">
<span class="count" id="updcount" aria-live="polite"></span></div>
<div class="tabs" id="updyears">
	<a href="#" data-y="all" class="active">All <span class="count"><?= count($updates) ?></span></a>
	<?php foreach (array_keys($years) as $y): ?><a href="#" data-y="<?= $y ?>"><?= $y ?></a><?php endforeach; ?>
</div>

<div class="timeline">
<?php foreach ($updates as $u):
	$t = strtotime($u['date']);
	$fresh = $t >= strtotime('-14 days') && $t <= time();
	// The items of an update are separated by an empty indent marker: each one is its own line.
	$items = array_values(array_filter(array_map('trim', explode('<span class="indent"></span>', $u['html'])), 'strlen'));
	$items = array_map(static fn ($i) => preg_replace('~^(<br>\s*)+|(\s*<br>)+$~', '', $i), $items);
?>
	<article class="update-card<?= $fresh ? ' fresh' : '' ?>" data-y="<?= h(date('Y', $t)) ?>" id="u-<?= h($u['date']) ?>">
		<a class="update-date" href="#u-<?= h($u['date']) ?>" title="Link to this update">
			<span class="d"><?= h(date('j', $t)) ?></span><span class="m"><?= h(date('M', $t)) ?></span><span class="y"><?= h(date('Y', $t)) ?></span>
		</a>
		<div class="update-body">
			<?php if ($fresh): ?><span class="pill green">New</span><?php endif; ?>
			<?php if (count($items) > 1): ?>
			<ul class="update-list"><?php foreach ($items as $item): ?><li><?= $item ?></li><?php endforeach; ?></ul>
			<?php else: ?>
			<p><?= $items[0] ?? $u['html'] ?></p>
			<?php endif; ?>
		</div>
	</article>
<?php endforeach; ?>
</div>
<p class="muted" id="updnone" hidden>No update mentions that.</p>
<div class="btngrid"><a href="originalupdates.php">The original 2009 - 2014 updates</a></div>
<?php cm_page_end(<<<'JS'
<script>
(function () {
	var box = document.getElementById('updfind'), count = document.getElementById('updcount'), none = document.getElementById('updnone');
	var cards = document.querySelectorAll('.update-card'), tabs = document.querySelectorAll('#updyears a'), year = 'all';
	cards.forEach(function (c) { c.setAttribute('data-find', c.textContent.toLowerCase()); });
	function apply() {
		var q = box.value.trim().toLowerCase(), shown = 0;
		cards.forEach(function (c) {
			var ok = (year === 'all' || c.getAttribute('data-y') === year) && (q === '' || c.getAttribute('data-find').indexOf(q) !== -1);
			c.style.display = ok ? '' : 'none';
			if (ok) { shown++; }
		});
		count.textContent = (q === '' && year === 'all') ? '' : shown + ' update' + (shown === 1 ? '' : 's');
		none.hidden = shown > 0;
	}
	tabs.forEach(function (t) {
		t.addEventListener('click', function (e) {
			e.preventDefault();
			tabs.forEach(function (x) { x.classList.remove('active'); });
			t.classList.add('active');
			year = t.getAttribute('data-y');
			apply();
		});
	});
	box.addEventListener('input', apply);
})();
</script>
JS
); ?>
