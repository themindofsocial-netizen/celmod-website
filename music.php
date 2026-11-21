<?php require __DIR__ . '/includes/bootstrap.php'; cm_page_start('|CelMod| - Music'); ?>
<!-- Written by tools/export_sounds.py (celmod repo) from the server's sounds.txt: edit that file, not this one. -->
<h2><span class="celColor">|CelMod| Music</span><br>
</h2>
<p>25 songs for the music cel. Spawn a radio with <span class="h">!music &lt;song&gt; [speed 50-200]</span>, then press <b>E</b> on it to play the song, and again to stop it. In game, <span class="h">!celmusic</span> lists them.<br>
Click a song to copy its command.</p>
<div class="searchbar"><input type="search" id="soundfind" placeholder="Find a song... (press /)" aria-label="Find a song" autocomplete="off"> <span class="count" id="soundcount" aria-live="polite"></span></div>
<div class="btngrid"><a href="sounds.php">Sound List</a> <a href="cmds.php">Commands</a></div>
<div class="table-scroll soundlist"><table class="cmtable">
<tr><th>Song</th><th class="num">Length</th></tr>
<tr data-name="song3"><td><b data-copy="!music song3" title="Copy !music song3">song3</b></td><td class="num">2:11</td></tr>
<tr data-name="song5"><td><b data-copy="!music song5" title="Copy !music song5">song5</b></td><td class="num">1:36</td></tr>
<tr data-name="song6"><td><b data-copy="!music song6" title="Copy !music song6">song6</b></td><td class="num">1:40</td></tr>
<tr data-name="song9"><td><b data-copy="!music song9" title="Copy !music song9">song9</b></td><td class="num">1:33</td></tr>
<tr data-name="song10"><td><b data-copy="!music song10" title="Copy !music song10">song10</b></td><td class="num">1:44</td></tr>
<tr data-name="song11"><td><b data-copy="!music song11" title="Copy !music song11">song11</b></td><td class="num">0:34</td></tr>
<tr data-name="song14"><td><b data-copy="!music song14" title="Copy !music song14">song14</b></td><td class="num">1:30</td></tr>
<tr data-name="song15"><td><b data-copy="!music song15" title="Copy !music song15">song15</b></td><td class="num">2:00</td></tr>
<tr data-name="song17"><td><b data-copy="!music song17" title="Copy !music song17">song17</b></td><td class="num">2:03</td></tr>
<tr data-name="song19"><td><b data-copy="!music song19" title="Copy !music song19">song19</b></td><td class="num">1:55</td></tr>
<tr data-name="song20"><td><b data-copy="!music song20" title="Copy !music song20">song20</b></td><td class="num">1:24</td></tr>
<tr data-name="song21"><td><b data-copy="!music song21" title="Copy !music song21">song21</b></td><td class="num">1:24</td></tr>
<tr data-name="song24"><td><b data-copy="!music song24" title="Copy !music song24">song24</b></td><td class="num">1:17</td></tr>
<tr data-name="song25"><td><b data-copy="!music song25" title="Copy !music song25">song25</b></td><td class="num">1:01</td></tr>
<tr data-name="song26"><td><b data-copy="!music song26" title="Copy !music song26">song26</b></td><td class="num">0:37</td></tr>
<tr data-name="airbag"><td><b data-copy="!music airbag" title="Copy !music airbag">airbag</b></td><td class="num">4:45</td></tr>
<tr data-name="chip"><td><b data-copy="!music chip" title="Copy !music chip">chip</b></td><td class="num">1:46</td></tr>
<tr data-name="chip2"><td><b data-copy="!music chip2" title="Copy !music chip2">chip2</b></td><td class="num">4:24</td></tr>
<tr data-name="chip3"><td><b data-copy="!music chip3" title="Copy !music chip3">chip3</b></td><td class="num">1:42</td></tr>
<tr data-name="chip4"><td><b data-copy="!music chip4" title="Copy !music chip4">chip4</b></td><td class="num">3:14</td></tr>
<tr data-name="friends"><td><b data-copy="!music friends" title="Copy !music friends">friends</b></td><td class="num">4:13</td></tr>
<tr data-name="nosup"><td><b data-copy="!music nosup" title="Copy !music nosup">nosup</b></td><td class="num">3:49</td></tr>
<tr data-name="arctic"><td><b data-copy="!music arctic" title="Copy !music arctic">arctic</b></td><td class="num">1:19</td></tr>
<tr data-name="storm"><td><b data-copy="!music storm" title="Copy !music storm">storm</b></td><td class="num">1:22</td></tr>
<tr data-name="summersolace"><td><b data-copy="!music summersolace" title="Copy !music summersolace">summersolace</b></td><td class="num">4:11</td></tr>
</table></div>
<?php cm_page_end(<<<'JS'
<script>
(function () {
	var box = document.getElementById('soundfind'), count = document.getElementById('soundcount');
	var items = document.querySelectorAll('.soundlist [data-name]'), groups = document.querySelectorAll('.soundgroup');
	if (!box) { return; }
	box.addEventListener('input', function () {
		var q = box.value.trim().toLowerCase().replace(/^!(sound|music)\s+/, '').replace(/^!/, ''), shown = 0;
		items.forEach(function (el) {
			var ok = q === '' || el.getAttribute('data-name').indexOf(q) !== -1;
			el.style.display = ok ? '' : 'none';
			if (ok) { shown++; }
		});
		groups.forEach(function (g) {
			var any = g.querySelector('[data-name]:not([style*="none"])');
			g.style.display = any ? '' : 'none';
			if (g.tagName === 'DETAILS') { g.open = q !== '' && !!any; }
		});
		count.textContent = q === '' ? '' : (shown === 0 ? 'Nothing matches' : shown + ' match' + (shown === 1 ? '' : 'es'));
	});
})();
</script>
JS); ?>
