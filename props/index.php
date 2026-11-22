<?php require __DIR__ . '/../includes/bootstrap.php'; cm_page_start('|CelMod| - Prop List'); ?>
<!-- Written by tools/export_props.py from the server's spawns.txt. -->
<h2><span class="celColor">|CelMod| Props</span><br>
</h2>
<p>686 spawnable props for use with !spawn<br>
  Usage: !spawn &lt;name of prop&gt; &lt;options&gt;<br>
  Ex. <span class="highlight">!spawn woodcrate1 drop</span><br>
  <b>OR</b><br>
  Ex. <span class="highlight">!woodcrate1 drop</span><br>
  <br>
  Options include:<br>
  <b>unfrozen</b> - Spawns the prop unfrozen<br>
  <b>nogod</b> - Spawns the prop and makes it breakable<br>
  <b>drop</b> - Spawns the prop at the floor<br>
</p>
<div class="searchbar"><input type="search" id="propfind" placeholder="Search all 686 props... (press /)" aria-label="Search props" autocomplete="off"> <span class="count" id="propcount" aria-live="polite"></span></div>
<div class="search-results" id="propresults" hidden></div>
<div class="btngrid letters">
<a href="a">A</a>
<a href="b">B</a>
<a href="c">C</a>
<a href="d">D</a>
<a href="e">E</a>
<a href="f">F</a>
<a href="g">G</a>
<a href="h">H</a>
<a href="i">I</a>
<a href="j">J</a>
<a href="k">K</a>
<a href="l">L</a>
<a href="m">M</a>
<a href="n">N</a>
<a href="o">O</a>
<a href="p">P</a>
<b title="No props yet">Q</b>
<a href="r">R</a>
<a href="s">S</a>
<a href="t">T</a>
<a href="u">U</a>
<a href="v">V</a>
<a href="w">W</a>
<b title="No props yet">X</b>
<b title="No props yet">Y</b>
<a href="z">Z</a>
<b title="No props yet">0-9</b>
</div>
<div class="btngrid"><a href="shop">Shop Props</a> <a href="dis">Discontinued Props</a></div>
<p>
</p>
<?php cm_page_end('<script src="search.js?v=' . (int)@filemtime(__DIR__ . '/search.js') . '" defer></script>'); ?>
