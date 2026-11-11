/* HT Neon — dashboard: mobile sidebar toggle. */
(function () {
	'use strict';
	var header = document.querySelector('.lp-dashboard-new .lp-user-header');
	var side = document.getElementById('sidebar-wrapper');
	if (!header || !side) { return; }
	// On phones, take the sidebar out of #wrapper so no theme layer can sit above it.
	var mq = window.matchMedia('(max-width: 767px)');
	var placeholder = document.createComment('htn-sidebar');
	var parent = side.parentNode;
	function detach() {
		if (side.parentNode !== document.body) {
			parent = side.parentNode;
			parent.insertBefore(placeholder, side);
			document.body.appendChild(side);
			side.classList.add('htn-dash-side');
		}
	}
	function attach() {
		if (side.parentNode === document.body && placeholder.parentNode) {
			placeholder.parentNode.insertBefore(side, placeholder);
			placeholder.parentNode.removeChild(placeholder);
			side.classList.remove('htn-dash-side');
		}
	}
	// "Back to website" link at the top of the sidebar menu (all screen sizes).
	var list = document.getElementById('sidebar') || side.querySelector('ul.sidebar-nav:not(#sidebar_menu)');
	if (list && !list.querySelector('.htn-dash-home')) {
		var home = document.createElement('li');
		home.className = 'htn-dash-home';
		var homeUrl = (document.body.getAttribute('data-htn-home') || '/');
		home.innerHTML = '<a href="' + homeUrl + '"><i class="fa fa-arrow-left" aria-hidden="true"></i> <span>Back to website</span></a>';
		list.insertBefore(home, list.firstChild);
	}
	var brandLink = side.querySelector('.sidebar-brand > a:first-child');
	if (brandLink) { brandLink.setAttribute('href', document.body.getAttribute('data-htn-home') || '/'); }
	function sync() { if (mq.matches) { detach(); } else { attach(); } }
	sync();
	if (mq.addEventListener) { mq.addEventListener('change', sync); } else if (mq.addListener) { mq.addListener(sync); }
	var btn = document.createElement('a');
	btn.href = '#';
	btn.className = 'htn-dash-burger';
	btn.setAttribute('aria-label', 'Menu');
	btn.innerHTML = '<i class="fa fa-bars" aria-hidden="true"></i>';
	header.insertBefore(btn, header.firstChild);
	var overlay = document.createElement('div');
	overlay.className = 'htn-dash-overlay';
	document.body.appendChild(overlay);
	overlay.addEventListener('click', function () { document.body.classList.remove('htn-dash-open'); });
	function close() { document.body.classList.remove('htn-dash-open'); }
	btn.addEventListener('click', function (e) { e.preventDefault(); document.body.classList.toggle('htn-dash-open'); });
	document.addEventListener('click', function (e) {
		if (!document.body.classList.contains('htn-dash-open')) { return; }
		if (side.contains(e.target) || btn.contains(e.target)) { return; }
		if (e.target === overlay) { return; }
	});
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
	var toggle = document.getElementById('menu-toggle');
	if (toggle) {
		toggle.addEventListener('click', function () { if (window.innerWidth < 768) { close(); } });
	}
})();
