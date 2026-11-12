<?php
require __DIR__ . '/includes/bootstrap.php';

$address = cm_config('server')['address'];

cm_page_start('|CelMod| - Live Server');
?>

<h2><span class="celColor">|CelMod| Live Server</span><br>
</h2>
<?php if (!cm_db()): cm_db_offline_notice(); else: ?>
<p id="status">Connecting to the server...</p>
<p>
	<span class="stat"><b id="s-players">-</b>Players</span>
	<span class="stat"><b id="s-props">-</b>Props on Server</span>
	<span class="stat"><b id="s-cels">-</b>Cels on Server</span>
	<br>
	<span class="stat"><b id="s-maptime">-</b>Map Time</span>
	<span class="stat"><b id="s-today">-</b>Joins Today</span>
	<span class="stat"><b id="s-peak">-</b>Peak Today</span>
</p>
<p><a href="steam://connect/<?= h($address) ?>"><b>Connect to <?= h(cm_config('server')['name']) ?></b></a><br>
<span class="muted"><?= h($address) ?></span></p>

<p><b>:Players Online:</b></p>
<table class="cmtable" id="players">
<tr><th>Name</th><th class="num">Props</th><th class="num">Cels</th><th class="num">Balance</th><th class="num">Time</th></tr>
</table>

<p><b>:Recent Activity:</b></p>
<table class="cmtable" id="events"></table>
<p class="muted">Updates automatically every 5 seconds.</p>
<?php endif; ?>

<?php
ob_start();
?>
<script type="text/javascript">
(function () {
	function el(tag, text, cls) {
		var e = document.createElement(tag);
		if (text !== undefined) e.textContent = text;
		if (cls) e.className = cls;
		return e;
	}
	function link(name, id) {
		var a = el('a', name);
		a.href = 'player.php?id=' + encodeURIComponent(id);
		return a;
	}
	function dur(s) {
		if (s === null) return '-';
		var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60);
		return h ? h + 'h ' + m + 'm' : m + 'm';
	}
	var verbs = {
		connect: 'joined the server',
		disconnect: 'left the server',
		purchase: 'bought',
		web_purchase: 'bought on the website:',
		map_change: 'Map changed to'
	};

	function render(d) {
		var s = d.server, status = document.getElementById('status');
		status.innerHTML = '';
		if (s.online) {
			status.appendChild(el('span', '● ', 'grassy'));
			status.appendChild(el('b', s.hostname));
			status.appendChild(document.createElement('br'));
			status.appendChild(document.createTextNode('Playing '));
			status.appendChild(el('span', s.map, 'h'));
			status.appendChild(document.createTextNode(' with ' + s.players + '/' + s.max_players + ' players'));
		} else {
			status.appendChild(el('span', '● ', 'bloody'));
			status.appendChild(el('b', 'The server is offline'));
			status.appendChild(document.createTextNode(' (last seen ' + dur(s.updated_ago) + ' ago)'));
		}
		document.getElementById('s-players').textContent = s.players + '/' + s.max_players;
		document.getElementById('s-props').textContent = s.total_props;
		document.getElementById('s-cels').textContent = s.total_cels;
		document.getElementById('s-maptime').textContent = s.online ? dur(s.map_seconds) : '-';
		document.getElementById('s-today').textContent = d.today.connections;
		document.getElementById('s-peak').textContent = d.today.peak_players;

		var pt = document.getElementById('players');
		while (pt.rows.length > 1) pt.deleteRow(1);
		if (!d.players.length) {
			var r = pt.insertRow(); var c = r.insertCell(); c.colSpan = 5;
			c.appendChild(el('span', 'Nobody is building right now.', 'muted'));
		}
		d.players.forEach(function (p) {
			var r = pt.insertRow();
			r.insertCell().appendChild(link(p.name, p.steamid64));
			[p.props, p.cels, p.balance, p.time].forEach(function (v) {
				var c = r.insertCell(); c.className = 'num'; c.textContent = v;
			});
		});

		var et = document.getElementById('events');
		while (et.rows.length) et.deleteRow(0);
		d.events.forEach(function (e) {
			var r = et.insertRow(), c = r.insertCell();
			if (e.type === 'map_change') {
				c.appendChild(document.createTextNode(verbs.map_change + ' '));
				c.appendChild(el('span', e.detail, 'h'));
			} else {
				c.appendChild(e.steamid64 ? link(e.name, e.steamid64) : el('b', e.name));
				c.appendChild(document.createTextNode(' ' + (verbs[e.type] || e.type) + ' '));
				if (e.detail) c.appendChild(el('span', e.detail, 'h'));
			}
			var t = r.insertCell(); t.className = 'num muted'; t.textContent = e.ago;
		});
	}

	function poll() {
		var x = new XMLHttpRequest();
		x.open('GET', 'api/status.php', true);
		x.onload = function () {
			try { var d = JSON.parse(x.responseText); if (d.ok) render(d); } catch (err) {}
		};
		x.send();
	}
	if (document.getElementById('status')) {
		poll();
		setInterval(poll, 5000);
	}
})();
</script>
<?php
cm_page_end(ob_get_clean());
