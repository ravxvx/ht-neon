(function () {
	'use strict';

	// Mobile menu
	var burger = document.getElementById('htn-burger');
	var nav = document.getElementById('htn-nav');
	if (burger && nav) {
		burger.addEventListener('click', function () {
			var open = nav.classList.toggle('is-open');
			burger.setAttribute('aria-expanded', open ? 'true' : 'false');
			document.body.classList.toggle('htn-menu-open', open);
			if (open) {
				window.scrollTo(0, 0);
				var header = document.getElementById('htn-header');
				if (header) { nav.style.top = header.getBoundingClientRect().bottom + 'px'; }
			} else {
				nav.style.top = '';
			}
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && nav.classList.contains('is-open')) {
				nav.classList.remove('is-open');
				burger.setAttribute('aria-expanded', 'false');
				document.body.classList.remove('htn-menu-open');
				burger.focus();
			}
		});
	}

	// Log In: use the ListingPro login popup when it exists, otherwise follow the link to the login page.
	var login = document.querySelector('.htn-login');
	if (login) {
		login.addEventListener('click', function (e) {
			if (document.getElementById('modal-3')) {
				e.preventDefault();
				if (nav) {
					nav.classList.remove('is-open');
				}
				if (burger) {
					burger.setAttribute('aria-expanded', 'false');
				}
			}
		});
	}

	// Sticky registration bar
	var bar = document.getElementById('htn-bar');
	if (bar) {
		var KEY = 'htnBarClosed';
		var closed = false;
		try { closed = window.localStorage.getItem(KEY) === '1'; } catch (err) {}
		if (!closed) {
			var onScroll = function () {
				if (window.pageYOffset > 500) {
					bar.hidden = false;
					window.requestAnimationFrame(function () { bar.classList.add('is-in'); });
					window.removeEventListener('scroll', onScroll);
				}
			};
			window.addEventListener('scroll', onScroll, { passive: true });
			onScroll();
			var close = document.getElementById('htn-bar-close');
			if (close) {
				close.addEventListener('click', function () {
					bar.hidden = true;
					try { window.localStorage.setItem(KEY, '1'); } catch (err) {}
				});
			}
		}
	}
})();

// Any preloader box left white by its own inline styles is recoloured once it appears.
(function(){
	var SEL='[class*="preloader"],[id*="preloader"],[class*="page-loader"],[id*="page-loader"]';
	function fix(root){
		var nodes=(root.matches&&root.matches(SEL)?[root]:[]).concat(Array.prototype.slice.call(root.querySelectorAll?root.querySelectorAll(SEL):[]));
		nodes.forEach(function(el){
			var cs=window.getComputedStyle(el);
			if(cs.position==='fixed'){el.style.setProperty('background','#110a1b','important');}
			Array.prototype.forEach.call(el.querySelectorAll('*'),function(c){
				var s=window.getComputedStyle(c);
				var bg=s.backgroundColor;
				if(bg&&/^rgb\(2[3-5]\d, 2[3-5]\d, 2[3-5]\d\)$/.test(bg)){c.style.setProperty('background','#1e1230','important');c.style.setProperty('border-radius','20px','important');}
				if(s.borderTopColor&&s.borderTopColor!==s.borderRightColor){c.style.setProperty('border-top-color','#ff4fbe','important');c.style.setProperty('border-right-color','rgba(255,79,190,.25)','important');c.style.setProperty('border-bottom-color','rgba(255,79,190,.25)','important');c.style.setProperty('border-left-color','rgba(255,79,190,.25)','important');}
			});
		});
	}
	fix(document.documentElement);
	if(window.MutationObserver){
		new MutationObserver(function(m){m.forEach(function(r){Array.prototype.forEach.call(r.addedNodes,function(n){if(n.nodeType===1)fix(n);});});}).observe(document.documentElement,{childList:true,subtree:true});
	}
})();

// Natural home: simple scroll-snap carousels (Featured Therapists / Upcoming Events).
(function () {
	'use strict';
	var cars = document.querySelectorAll('[data-htn-carousel]');
	Array.prototype.forEach.call(cars, function (car) {
		var track = car.querySelector('.htn-ncar__track');
		var prev = car.querySelector('.htn-ncar__nav--prev');
		var next = car.querySelector('.htn-ncar__nav--next');
		var dots = car.querySelector('.htn-ncar__dots');
		if (!track) { return; }
		var items = track.children;
		function step() { return items.length ? items[0].getBoundingClientRect().width + 16 : 0; }
		function pages() { return Math.max(1, Math.ceil((track.scrollWidth - track.clientWidth) / Math.max(1, step())) + 1); }
		function current() { return Math.round(track.scrollLeft / Math.max(1, step())); }
		function draw() {
			if (!dots) { return; }
			var n = pages(); var c = current(); var html = '';
			for (var i = 0; i < n; i++) { html += '<i' + (i === c ? ' class="is-on"' : '') + '></i>'; }
			dots.innerHTML = n > 1 ? html : '';
			var max = track.scrollWidth - track.clientWidth - 2;
			if (prev) { prev.disabled = track.scrollLeft <= 2; }
			if (next) { next.disabled = track.scrollLeft >= max; }
		}
		if (prev) { prev.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); }); }
		if (next) { next.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: 'smooth' }); }); }
		track.addEventListener('scroll', function () { window.requestAnimationFrame(draw); }, { passive: true });
		window.addEventListener('resize', draw);
		draw();
	});
})();
