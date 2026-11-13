/* HT Neon – single listing: "Book an Appointment" shortcut (ListingPro Bookings widget) */
(function () {
	'use strict';
	var cfg = window.htnListing || {};
	function findBooking() {
		var nodes = document.querySelectorAll('.sidebar-post h4, .sidebar-post h3, .sidebar-post h2, .widget-box h4, .widget-box h3, .widget-header, [class*="booking"] h4, [class*="booking"] h3');
		for (var i = 0; i < nodes.length; i++) {
			if (/book an appointment|book now|appointment/i.test(nodes[i].textContent)) { return nodes[i]; }
		}
		return null;
	}
	var head = findBooking();
	if (!head) { return; }
	var box = head.closest('.widget-box, .sidebar-post > div, .post-row, [class*="booking"]') || head.parentNode;
	box.id = box.id || 'htn-booking';
	box.classList.add('htn-booking-box');

	function go(e) {
		if (e) { e.preventDefault(); }
		if (!cfg.loggedIn && cfg.loginUrl) { window.location.href = cfg.loginUrl; return; }
		var top = box.getBoundingClientRect().top + window.pageYOffset - 90;
		window.scrollTo({ top: top, behavior: 'smooth' });
		var collapsed = box.querySelector('.collapse:not(.in), [style*="display: none"], [aria-expanded="false"]');
		if (collapsed) { head.click(); }
		box.classList.add('is-flash');
		setTimeout(function () { box.classList.remove('is-flash'); }, 1600);
	}

	var label = cfg.loggedIn ? 'Book an Appointment' : 'Book an Appointment';
	var stat = document.querySelector('.post-meta-right-box .post-stat');
	if (stat) {
		var li = document.createElement('li');
		li.className = 'htn-book-li';
		li.innerHTML = '<a href="#htn-booking" class="htn-book-btn"><i class="fa fa-calendar-check-o"></i> ' + label + '</a>';
		stat.appendChild(li);
		li.querySelector('a').addEventListener('click', go);
	}
	var bar = document.createElement('div');
	bar.className = 'htn-book-bar';
	bar.innerHTML = '<a href="#htn-booking" class="htn-book-bar__btn"><i class="fa fa-calendar-check-o"></i> ' + label + '</a>';
	document.body.appendChild(bar);
	document.body.classList.add('htn-has-book-bar');
	bar.querySelector('a').addEventListener('click', go);
	if (!cfg.loggedIn) {
		var note = document.createElement('p');
		note.className = 'htn-book-note';
		note.innerHTML = 'You need a free account to book online. <a href="' + (cfg.loginUrl || '#') + '">Log in</a> or <a href="' + (cfg.registerUrl || '#') + '">create one</a>.';
		box.appendChild(note);
	}
})();
