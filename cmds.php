<?php
require __DIR__ . '/includes/bootstrap.php';

// Every command on the server, read from the plugins by tools/export_commands.py (celmod repo).
$categories = require __DIR__ . '/includes/commands.php';

cm_page_start('|CelMod| - Command List');
?>
<h2><span class="celColor">|CelMod| Commands</span><br>
</h2>
<p>These commands go into the chat. To open chat press <span class="h">Y</span> on your keyboard.<br>
In the console, type <b>v_</b> instead of <b>!</b> (<span class="h">v_land</span> is the same as <span class="h">!land</span>).</p>
<p>Spawn any prop by typing its name: <span class="h">!blastdoor3</span> &middot; <a href="props/"><b>Props List</b></a> &middot;
<a href="colors"><b>Colors List</b></a> &middot; <a href="effects"><b>Effects List</b></a> &middot; <a href="renderfx"><b>Render Effects</b></a> &middot; <a href="sounds"><b>Sounds List</b></a> &middot; <a href="music"><b>Music List</b></a></p>

<div class="searchbar"><input type="search" id="cmdfind" placeholder="Find a command... (press /)" aria-label="Find a command">
<span class="count" id="cmdcount" aria-live="polite"></span> <a href="#" id="cmdall" class="muted">show all</a></div>
<p class="muted">Click a command to copy it, ready to paste into the game's chat.</p>

<?php $n = 0; foreach ($categories as $title => $commands): $n++; ?>
<p class="cat"><a id="cat<?= $n ?>" href="#" data-cat="<?= $n ?>"><?= h($title) ?> <span class="muted">(<?= count($commands) ?>)</span></a></p>
<div id="say<?= $n ?>" class="cmdlist"<?= $title === 'Admin' ? ' data-admin="1"' : '' ?>>
<?php if ($title === 'Admin'): ?><p class="muted">Only server admins can use these.</p><?php endif; ?>
<p>
<?php foreach ($commands as [$names, $usage, $desc]): ?>
	<span class="cmd" id="cmd-<?= h(preg_replace('/[^a-z0-9_]/', '', strtolower($names[0]))) ?>" data-name="<?= h($names[0][0] === '+' ? $names[0] : '!' . $names[0]) ?>" data-find="<?= h(strtolower(implode(' ', $names) . ' ' . $desc)) ?>"><a><?php
		$shown = [];
		foreach ($names as $i => $name) {
			$tag = $name[0] === '+' ? $name : '!' . $name;
			$shown[] = $i === 0 ? '<b><span class="h">' . h($tag) . '</span></b>' : '<span class="h">' . h($tag) . '</span>';
		}
		echo implode(' / ', $shown);
	?><?= $usage !== '' ? ' ' . h($usage) : '' ?></a> &mdash; <?= h($desc) ?><br></span>
<?php endforeach; ?>
</p>
</div>
<?php endforeach; ?>
<?php cm_page_end(<<<'JS'
<script>
(function () {
	var lists = document.querySelectorAll('.cmdlist'), heads = document.querySelectorAll('.cat a[data-cat]');
	var find = document.getElementById('cmdfind');

	function show(n, open) {
		document.getElementById('say' + n).style.display = open ? 'block' : 'none';
		document.getElementById('cat' + n).className = open ? 'open' : '';
	}

	lists.forEach(function (l, i) { show(i + 1, false); });

	heads.forEach(function (a) {
		a.addEventListener('click', function (e) {
			e.preventDefault();
			var n = a.getAttribute('data-cat'), open = document.getElementById('say' + n).style.display === 'none';
			lists.forEach(function (l, i) { show(i + 1, false); });
			show(n, open);
		});
	});

	document.getElementById('cmdall').addEventListener('click', function (e) {
		e.preventDefault();
		find.value = '';
		filter();
		lists.forEach(function (l, i) { show(i + 1, true); });
	});

	// Typing filters every category at once and opens the ones with a match.
	function filter() {
		var q = find.value.trim().toLowerCase().replace(/^[!\/]|^v_/, ''), total = 0;
		lists.forEach(function (l, i) {
			var hits = 0;
			l.querySelectorAll('.cmd').forEach(function (c) {
				var ok = q === '' || c.getAttribute('data-find').indexOf(q) !== -1;
				c.style.display = ok ? '' : 'none';
				if (ok) { hits++; }
			});
			if (q !== '') { show(i + 1, hits > 0); }
			total += hits;
		});
		document.getElementById('cmdcount').textContent = q === '' ? '' : (total === 0 ? 'No matches' : total + ' match' + (total === 1 ? '' : 'es'));
		if (q === '') { lists.forEach(function (l, i) { show(i + 1, false); }); }
	}

	find.addEventListener('input', filter);

	// Clicking a command copies it ("!land"). The link to it (cmds.php#cmd-land) opens its category.
	document.querySelectorAll('.cmd > a').forEach(function (a) {
		a.addEventListener('click', function () {
			var c = a.parentNode, name = c.getAttribute('data-name');
			if (window.cmCopy) { window.cmCopy(name, 'Copied ' + name); }
			if (history.replaceState) { history.replaceState(null, '', '#' + c.id); }
		});
	});

	function openHash() {
		var c = location.hash.length > 1 && document.getElementById(location.hash.slice(1));
		if (!c || !c.classList.contains('cmd')) { return; }
		var list = c.closest('.cmdlist');
		show(list.id.replace('say', ''), true);
		c.classList.add('flash-row');
		setTimeout(function () { c.classList.remove('flash-row'); }, 2500);
		setTimeout(function () { c.scrollIntoView({ block: 'center' }); }, 60);
	}
	window.addEventListener('hashchange', openHash);
	openHash();
})();
</script>
JS
); ?>
