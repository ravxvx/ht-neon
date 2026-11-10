(function () {
	'use strict';

	// Mobile menu
	var burger = document.getElementById('htn-burger');
	var nav = document.getElementById('htn-nav');
	if (burger && nav) {
		burger.addEventListener('click', function () {
			var open = nav.classList.toggle('is-open');
			burger.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && nav.classList.contains('is-open')) {
				nav.classList.remove('is-open');
				burger.setAttribute('aria-expanded', 'false');
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
