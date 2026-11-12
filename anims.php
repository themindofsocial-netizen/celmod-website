<?php require __DIR__ . '/includes/bootstrap.php'; cm_head('|CelMod| - Animation'); ?>
<?php cm_logo(true); ?>
<div id="main">
<div id="content">

<h2><FONT style="COLOR: #99CCFF">|CelMod| Animation</FONT><br>
</h2>
<p><b>:Starting:</b></p>
<p> - To start animating, look at an object and type <span class="h">!anim (name)</span> where<br>
(name) is the name you want to give it.<br>
 - Once you have the desired position, hit <span class="h">ALT</span> to save the frame. And repeat.<br>
</p>
<p><b>:Keys:</b></p>
<p> - To move on the <FONT style="COLOR: #FF4040"><b>X</b></FONT> axis, press <span class="h">W</span> or <span class="h">S</span> <b>-- / --</b> <span class="h">Up Arrow</span> or <span class="h">Down Arrow</span>.<br>
 - To move on the <FONT style="COLOR: #71E100"><b>Y</b></FONT> axis, press <span class="h">A</span> or <span class="h">D</span> <b>-- / --</b> <span class="h">Left Arrow</span> or <span class="h">Right Arrow</span>.<br>
 - To move on the <FONT style="COLOR: #99CCFF"><b>Z</b></FONT> axis, press <span class="h">CTRL</span> or <span class="h">SPACE</span>.<br>
<br>
 - To move the object by <span class="h">position/angles</span>, press the <span class="h">SHIFT</span> key.<br>
<br>
 - To save a <span class="h">frame</span>, press the <span class="h">ALT</span> key.<br>
</p>
<p><b>:Finishing:</b></p>
<p> - When you're done with your animation, type <span class="h">!anim</span> in chat.<br>
<br>
 - Once you've finished the animation, you can view it on any object by<br>
 typing <span class="h">!view (name)</span> in chat.<br>
 - To loop the animation, type <span class="h">!view (name) #loop</span>.<br>
 - If you want to stop an animation, type <span class="h">!view (name) #stop</span>.<br>
 - To get rid of an animation, type <span class="h">!anim (name) #clear</span>.<br>
</p>
</div>
</div>
<?php cm_footer_bar(); ?>
<?php cm_bg_script(); ?>
</html>
