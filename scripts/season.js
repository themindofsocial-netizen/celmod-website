/*
 * |CelMod| seasonal backdrop: a light layer of falling snow (Christmas), bats and embers (Halloween),
 * confetti and fireworks (New Year) or strawberries and little clocks (Clock Day), drawn behind the page
 * frame so it never covers anything you read or click. Turned off for people who prefer reduced motion.
 */
(function () {
	var season = document.body.getAttribute('data-season');
	if (!season || !window.requestAnimationFrame) {
		return;
	}

	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	var canvas = document.createElement('canvas');
	canvas.className = 'season-layer';
	canvas.setAttribute('aria-hidden', 'true');
	document.body.appendChild(canvas);

	var ctx = canvas.getContext('2d');
	var w = 0, h = 0, dpr = Math.min(window.devicePixelRatio || 1, 2);

	function resize() {
		w = window.innerWidth;
		h = window.innerHeight;
		canvas.width = w * dpr;
		canvas.height = h * dpr;
		canvas.style.width = w + 'px';
		canvas.style.height = h + 'px';
		ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
	}
	resize();
	window.addEventListener('resize', resize);

	function rnd(a, b) { return a + Math.random() * (b - a); }

	// Fewer pieces on small screens.
	var count = Math.round(Math.min(90, Math.max(24, w * h / 22000)));
	var parts = [];
	var bursts = [];

	var palettes = {
		christmas: ['#ffffff', '#e8f4ff', '#d6ecff'],
		halloween: ['#FF7A00', '#FFB200', '#9b4dff'],
		newyears: ['#FFB200', '#71E100', '#99CCFF', '#FF3B6B', '#ffffff'],
		clockday: ['#FF3355', '#FFB200', '#ffffff']
	};
	var colors = palettes[season] || palettes.christmas;

	function make(p, top) {
		p.x = rnd(0, w);
		p.y = top ? rnd(-h * 0.2, -10) : rnd(0, h);
		p.r = rnd(1.2, 3.6);
		p.vy = rnd(0.25, 1.1);
		p.vx = rnd(-0.3, 0.3);
		p.a = rnd(0, Math.PI * 2);
		p.va = rnd(-0.03, 0.03);
		p.c = colors[Math.floor(Math.random() * colors.length)];
		p.kind = 0;

		if (season === 'halloween') {
			// Mostly drifting embers, with a few bats flapping across.
			p.kind = Math.random() < 0.18 ? 1 : 0;
			if (p.kind === 1) {
				p.r = rnd(7, 12);
				p.vx = rnd(0.6, 1.4) * (Math.random() < 0.5 ? -1 : 1);
				p.vy = rnd(-0.25, 0.25);
				p.y = rnd(h * 0.05, h * 0.6);
				p.x = p.vx > 0 ? -20 : w + 20;
			} else {
				p.y = top ? h + 10 : rnd(0, h);
				p.vy = -rnd(0.2, 0.7);
			}
		} else if (season === 'newyears') {
			p.r = rnd(2, 4.5);
			p.vy = rnd(0.6, 1.6);
		} else if (season === 'clockday') {
			p.kind = Math.random() < 0.45 ? 1 : 0; // clocks and strawberries
			p.r = rnd(5, 9);
			p.vy = rnd(0.3, 0.8);
		}
		return p;
	}

	for (var i = 0; i < count; i++) {
		parts.push(make({}, false));
	}

	function drawBat(p, t) {
		var flap = Math.sin(t / 90 + p.a) * 0.6;
		ctx.save();
		ctx.translate(p.x, p.y);
		ctx.scale(p.vx < 0 ? -1 : 1, 1);
		ctx.fillStyle = 'rgba(20, 10, 30, 0.85)';
		ctx.beginPath();
		ctx.ellipse(0, 0, p.r * 0.28, p.r * 0.4, 0, 0, Math.PI * 2);
		ctx.fill();
		for (var s = -1; s <= 1; s += 2) {
			ctx.beginPath();
			ctx.moveTo(0, 0);
			ctx.quadraticCurveTo(s * p.r * 0.7, -p.r * (0.6 + flap), s * p.r * 1.4, -p.r * flap * 0.4);
			ctx.quadraticCurveTo(s * p.r * 1.0, p.r * 0.1, s * p.r * 0.75, p.r * 0.05);
			ctx.quadraticCurveTo(s * p.r * 0.5, p.r * 0.35, 0, p.r * 0.15);
			ctx.fill();
		}
		ctx.restore();
	}

	function drawClock(p) {
		ctx.save();
		ctx.translate(p.x, p.y);
		ctx.fillStyle = 'rgba(255, 255, 255, 0.85)';
		ctx.strokeStyle = '#FF3355';
		ctx.lineWidth = 1.5;
		ctx.beginPath();
		ctx.arc(0, 0, p.r, 0, Math.PI * 2);
		ctx.fill();
		ctx.stroke();
		ctx.strokeStyle = '#333';
		ctx.lineWidth = 1.2;
		ctx.beginPath();
		ctx.moveTo(0, 0);
		ctx.lineTo(Math.cos(p.a) * p.r * 0.75, Math.sin(p.a) * p.r * 0.75);
		ctx.moveTo(0, 0);
		ctx.lineTo(Math.cos(p.a / 12) * p.r * 0.5, Math.sin(p.a / 12) * p.r * 0.5);
		ctx.stroke();
		ctx.restore();
	}

	function drawStrawberry(p) {
		ctx.save();
		ctx.translate(p.x, p.y);
		ctx.rotate(Math.sin(p.a) * 0.4);
		ctx.fillStyle = '#E8213F';
		ctx.beginPath();
		ctx.moveTo(0, p.r);
		ctx.bezierCurveTo(-p.r * 1.1, p.r * 0.2, -p.r * 0.9, -p.r * 0.8, 0, -p.r * 0.6);
		ctx.bezierCurveTo(p.r * 0.9, -p.r * 0.8, p.r * 1.1, p.r * 0.2, 0, p.r);
		ctx.fill();
		ctx.fillStyle = '#3FB33F';
		ctx.beginPath();
		ctx.ellipse(0, -p.r * 0.65, p.r * 0.55, p.r * 0.2, 0, 0, Math.PI * 2);
		ctx.fill();
		ctx.restore();
	}

	function firework() {
		var x = rnd(w * 0.1, w * 0.9), y = rnd(h * 0.1, h * 0.45);
		var c = colors[Math.floor(Math.random() * colors.length)];
		var sparks = [];
		for (var k = 0; k < 36; k++) {
			var ang = (k / 36) * Math.PI * 2, sp = rnd(1.2, 2.8);
			sparks.push({ x: x, y: y, vx: Math.cos(ang) * sp, vy: Math.sin(ang) * sp });
		}
		bursts.push({ s: sparks, life: 1, c: c });
	}

	var last = 0, nextBurst = 0;

	function frame(t) {
		if (document.hidden) {
			requestAnimationFrame(frame);
			return;
		}
		var dt = Math.min(3, last ? (t - last) / 16.7 : 1);
		last = t;

		ctx.clearRect(0, 0, w, h);

		for (var i = 0; i < parts.length; i++) {
			var p = parts[i];
			p.x += (p.vx + Math.sin(t / 1600 + p.a) * 0.25) * dt;
			p.y += p.vy * dt;
			p.a += p.va * dt;

			if (season === 'halloween' && p.kind === 1) {
				drawBat(p, t);
				if (p.x < -40 || p.x > w + 40) make(p, true);
				continue;
			}

			if (p.y > h + 20 || p.y < -h * 0.3 || p.x < -30 || p.x > w + 30) {
				make(p, true);
				continue;
			}

			if (season === 'clockday') {
				p.a += 0.02 * dt;
				if (p.kind === 1) drawClock(p); else drawStrawberry(p);
			} else if (season === 'newyears') {
				ctx.save();
				ctx.translate(p.x, p.y);
				ctx.rotate(p.a);
				ctx.fillStyle = p.c;
				ctx.fillRect(-p.r, -p.r * 0.45, p.r * 2, p.r * 0.9);
				ctx.restore();
			} else {
				// Snow and embers: soft round dots.
				ctx.globalAlpha = season === 'halloween' ? 0.55 + Math.sin(t / 300 + p.a) * 0.3 : 0.85;
				ctx.fillStyle = p.c;
				ctx.beginPath();
				ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
				ctx.fill();
				ctx.globalAlpha = 1;
			}
		}

		if (season === 'newyears') {
			if (t > nextBurst) {
				firework();
				nextBurst = t + rnd(1400, 3200);
			}
			for (var b = bursts.length - 1; b >= 0; b--) {
				var burst = bursts[b];
				burst.life -= 0.012 * dt;
				ctx.globalAlpha = Math.max(0, burst.life);
				ctx.fillStyle = burst.c;
				for (var s = 0; s < burst.s.length; s++) {
					var sp = burst.s[s];
					sp.x += sp.vx * dt;
					sp.y += sp.vy * dt;
					sp.vy += 0.03 * dt;
					ctx.fillRect(sp.x, sp.y, 2, 2);
				}
				ctx.globalAlpha = 1;
				if (burst.life <= 0) bursts.splice(b, 1);
			}
		}

		if (!reduce) {
			requestAnimationFrame(frame);
		}
	}

	requestAnimationFrame(frame);
})();
