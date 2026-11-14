<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/updates.php';

$updates = cm_all_updates();

cm_page_start('|CelMod| - Updates', '', ['wide' => true]);
?>
<h2><span class="celColor">|CelMod|</span> Updates<br>
</h2>
<div style="text-align:left">
<p><span class="headColor">Latest Updates</span><br>
New commands in <b><i>bold italics</i></b>, existing commands in <i>normal italics</i>.<br>
Chat commands in <span class="highlight">green italics</span>.</p>

<table class="changelog cmtable">
<?php foreach ($updates as $u): $fresh = strtotime($u['date']) >= strtotime('-14 days') && strtotime($u['date']) <= time(); ?>
	<tr class="update-entry<?= $fresh ? ' fresh' : '' ?>"><td><b>-=- <?= h(date('m/d/y', strtotime($u['date']))) ?>: </b><?= $fresh ? '<span class="pill green">New</span> ' : '' ?><?= $u['html'] ?></td></tr>
<?php endforeach; ?>
</table>
<p><a href="originalupdates.php"><b>The original 2009 - 2014 updates</b></a></p>
</div>
<?php cm_page_end(); ?>
