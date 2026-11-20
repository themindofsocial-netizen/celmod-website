/* Search box on the props index: looks through every prop name (props.json, written by the server repo's
 * tools/export_props.py). Clicking a result copies "!name", ready to paste into the game's chat. */
(function () {
	'use strict';

	var box = document.getElementById('propfind'), out = document.getElementById('propresults'), count = document.getElementById('propcount');
	var names = null, MAX = 120;

	if (!box) { return; }

	function load(then) {
		if (names) { then(); return; }
		fetch('props.json').then(function (r) { return r.json(); }).then(function (list) { names = list; then(); }, function () {
			count.textContent = 'Search is unavailable; use the letters below.';
		});
	}

	function esc(s) {
		return s.replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; });
	}

	function render() {
		var q = box.value.trim().toLowerCase().replace(/^!|^(spawn|v_spawn)\s+/, '');
		if (q === '') { out.hidden = true; out.innerHTML = ''; count.textContent = ''; return; }

		// Names that start with the search come first, then ones that contain it.
		var starts = [], has = [];
		names.forEach(function (n) {
			var i = n.toLowerCase().indexOf(q);
			if (i === 0) { starts.push(n); } else if (i > 0) { has.push(n); }
		});
		var hits = starts.concat(has);

		count.textContent = hits.length === 0 ? 'No props found' : hits.length + ' prop' + (hits.length === 1 ? '' : 's') + (hits.length > MAX ? ' (first ' + MAX + ' shown)' : '');
		out.hidden = false;
		out.innerHTML = hits.length === 0 ? '<span class="none">Try part of the name, like "crate" or "chair".</span>' : hits.slice(0, MAX).map(function (n) {
			var i = n.toLowerCase().indexOf(q);
			return '<button type="button" data-copy="!' + esc(n) + '" title="Copy !' + esc(n) + '">' + esc(n.slice(0, i)) + '<mark>' + esc(n.slice(i, i + q.length)) + '</mark>' + esc(n.slice(i + q.length)) + '</button>';
		}).join('');
	}

	box.addEventListener('focus', function () { load(function () {}); }, { once: true });
	box.addEventListener('input', function () { load(render); });
})();
