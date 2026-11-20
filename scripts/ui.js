/*
 * |CelMod| site conveniences, on every page:
 *  - the nav plate gets a shadow once it sticks to the top
 *  - a back-to-top button after scrolling a screen
 *  - [data-copy="text"] copies the text when clicked, with a toast
 *  - "/" jumps to the page's search box, Esc clears it
 *  - <ul class="foldable"> shows its first four items and a "show more" button
 *  - on pages with a contents grid (guides), the section being read is marked
 * The pages work without it.
 */
(function () {
	'use strict';

	var nav = document.getElementById('navbar');

	// --- Toasts ---
	var toastEl = null, toastTimer = 0;

	function toast(text) {
		if (!toastEl) {
			toastEl = document.createElement('div');
			toastEl.className = 'toast';
			toastEl.setAttribute('role', 'status');
			toastEl.setAttribute('aria-live', 'polite');
			document.body.appendChild(toastEl);
		}
		toastEl.textContent = text;
		toastEl.classList.add('show');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 1800);
	}

	function copy(text, label) {
		var done = function () { toast(label || ('Copied ' + text)); };
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text) && done(); });
		} else if (fallbackCopy(text)) {
			done();
		}
	}

	function fallbackCopy(text) {
		var t = document.createElement('textarea');
		t.value = text;
		t.setAttribute('readonly', '');
		t.style.position = 'fixed';
		t.style.opacity = '0';
		document.body.appendChild(t);
		t.select();
		var ok = false;
		try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
		document.body.removeChild(t);
		if (!ok) { toast('Copy it by hand: ' + text); }
		return ok;
	}

	window.cmCopy = copy;
	window.cmToast = toast;

	document.addEventListener('click', function (e) {
		var el = e.target.closest && e.target.closest('[data-copy]');
		if (!el) { return; }
		e.preventDefault();
		copy(el.getAttribute('data-copy'), el.getAttribute('data-copy-label'));
	});

	// --- Sticky nav shadow and back to top ---
	var top = document.createElement('button');
	top.type = 'button';
	top.className = 'to-top';
	top.title = 'Back to top';
	top.setAttribute('aria-label', 'Back to top');
	top.innerHTML = '&#9650;';
	top.addEventListener('click', function () { window.scrollTo({ top: 0 }); });
	document.body.appendChild(top);

	var navTop = nav ? nav.offsetTop : 0, ticking = false;

	function onScroll() {
		ticking = false;
		var y = window.pageYOffset || document.documentElement.scrollTop;
		if (nav) { nav.classList.toggle('stuck', y > navTop + 2); }
		top.classList.toggle('show', y > window.innerHeight * 0.8);
	}

	window.addEventListener('scroll', function () {
		if (!ticking) { ticking = true; requestAnimationFrame(onScroll); }
	}, { passive: true });
	onScroll();

	// The current page's link is scrolled into view in the phone nav row.
	var active = nav && nav.querySelector('.nav-links a.active');
	if (active && active.parentNode.scrollWidth > active.parentNode.clientWidth) {
		active.parentNode.scrollLeft = active.offsetLeft - active.parentNode.clientWidth / 2 + active.offsetWidth / 2;
	}

	// --- "/" focuses search, Esc clears it ---
	document.addEventListener('keydown', function (e) {
		var tag = (document.activeElement && document.activeElement.tagName) || '';
		var typing = /INPUT|TEXTAREA|SELECT/.test(tag) || (document.activeElement && document.activeElement.isContentEditable);
		if (e.key === '/' && !typing && !e.ctrlKey && !e.metaKey && !e.altKey) {
			var box = document.querySelector('#content input[type=search]');
			if (box) { e.preventDefault(); box.focus(); box.select(); }
		} else if (e.key === 'Escape' && tag === 'INPUT' && document.activeElement.type === 'search' && document.activeElement.value !== '') {
			document.activeElement.value = '';
			document.activeElement.dispatchEvent(new Event('input', { bubbles: true }));
		}
	});

	document.querySelectorAll('#content input[type=search]').forEach(function (box) {
		if (!box.getAttribute('title')) { box.setAttribute('title', 'Press / to search, Esc to clear'); }
	});

	// --- Folding lists ---
	document.querySelectorAll('ul.foldable').forEach(function (list) {
		var extra = list.children.length - 4;
		if (extra < 1) { return; }
		list.classList.add('folded');
		var b = document.createElement('button');
		b.type = 'button';
		b.className = 'fold-toggle';
		b.setAttribute('aria-expanded', 'false');
		var label = function () {
			var folded = list.classList.contains('folded');
			b.textContent = folded ? '+ ' + extra + ' more change' + (extra === 1 ? '' : 's') : '- Show less';
			b.setAttribute('aria-expanded', folded ? 'false' : 'true');
		};
		b.addEventListener('click', function () { list.classList.toggle('folded'); label(); });
		label();
		list.parentNode.insertBefore(b, list.nextSibling);
	});

	// --- Contents grid: mark the section being read ---
	var tocLinks = Array.prototype.filter.call(document.querySelectorAll('#content .btngrid a[href^="#"]'), function (a) {
		return document.getElementById(a.getAttribute('href').slice(1));
	});
	if (tocLinks.length > 2 && 'IntersectionObserver' in window) {
		var byId = {};
		tocLinks.forEach(function (a) { byId[a.getAttribute('href').slice(1)] = a; });
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting) {
					tocLinks.forEach(function (a) { a.classList.remove('reading'); });
					byId[en.target.id].classList.add('reading');
				}
			});
		}, { rootMargin: '-30% 0px -60% 0px' });
		Object.keys(byId).forEach(function (id) { io.observe(document.getElementById(id)); });
	}
})();
