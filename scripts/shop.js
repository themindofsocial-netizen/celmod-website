/*
 * Shop: search, sort and filter the cards without reloading, and a two-click buy button
 * (the first click asks "Buy for 50K CL?", the second places the order).
 */
(function () {
	var search = document.getElementById('shop-search');
	var sort = document.getElementById('shop-sort');
	var afford = document.getElementById('shop-afford');
	var hideOwned = document.getElementById('shop-hide-owned');
	var empty = document.getElementById('shop-empty');
	var sections = document.querySelectorAll('.shop-section');

	function apply() {
		var q = search ? search.value.trim().toLowerCase() : '';
		var shown = 0;

		sections.forEach(function (section) {
			var grid = section.querySelector('.shop-grid');
			var cards = Array.prototype.slice.call(grid.children);
			var visible = 0;

			cards.forEach(function (card) {
				var ok = (!q || card.getAttribute('data-name').indexOf(q) !== -1)
					&& (!afford || !afford.checked || card.getAttribute('data-afford') === '1')
					&& (!hideOwned || !hideOwned.checked || card.getAttribute('data-owned') !== '1');
				card.style.display = ok ? '' : 'none';
				if (ok) visible++;
			});

			var by = sort ? sort.value : '';
			cards.sort(function (a, b) {
				if (by === 'price-asc') return a.getAttribute('data-price') - b.getAttribute('data-price');
				if (by === 'price-desc') return b.getAttribute('data-price') - a.getAttribute('data-price');
				if (by === 'name') return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
				return a.getAttribute('data-order') - b.getAttribute('data-order');
			}).forEach(function (card) { grid.appendChild(card); });

			section.style.display = visible ? '' : 'none';
			shown += visible;
		});

		if (empty) empty.style.display = shown ? 'none' : 'block';
	}

	[search, sort, afford, hideOwned].forEach(function (el) {
		if (el) el.addEventListener(el === search ? 'input' : 'change', apply);
	});

	// Two clicks to buy: the button asks first, then places the order.
	document.querySelectorAll('.buyform button[data-confirm]').forEach(function (btn) {
		var label = btn.textContent;
		var timer = null;
		btn.addEventListener('click', function (e) {
			if (btn.classList.contains('armed')) {
				btn.textContent = 'Ordering...';
				return;
			}
			e.preventDefault();
			btn.classList.add('armed');
			btn.textContent = btn.getAttribute('data-confirm');
			clearTimeout(timer);
			timer = setTimeout(function () {
				btn.classList.remove('armed');
				btn.textContent = label;
			}, 4000);
		});
	});
})();
