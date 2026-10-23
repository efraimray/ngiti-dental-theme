/**
 * Ngiti Dental theme script (vanilla JS, no dependencies).
 *
 * 1. Light/dark toggle (remembered in localStorage when allowed)
 * 2. Mobile menu
 * 3. Live "open now" status from the clinic schedule
 * 4. Form validation (and a simulated submit in the static demo)
 * 5. Tooth finder (FDI chart -> likely services)
 */
(function () {
	'use strict';

	var cfg = window.ngitiTheme || {};
	var T = Object.assign({
		open: 'Open now', soon: 'Closing soon', closed: 'Closed',
		closesAt: 'Closes at %s', opensAt: 'Opens at %s', opensTomorrow: 'Opens tomorrow at %s', opensDay: 'Opens %1$s at %2$s',
		required: 'This field is required.', mobile: 'Enter a PH mobile number, e.g. 0917 555 0142.',
		email: 'Enter a valid email address.', date: 'Choose a date from tomorrow onwards.',
		sunday: 'The clinic is closed on that day. Please choose another day.',
		consent: 'Please agree to the privacy notice so we can contact you.', fix: 'Please fix the highlighted fields.'
	}, cfg.i18n || {});
	var root = document.documentElement;

	function fmt(str) {
		var args = Array.prototype.slice.call(arguments, 1), i = 0;
		return str.replace(/%(\d\$)?s/g, function (m, pos) { return pos ? args[parseInt(pos, 10) - 1] : args[i++]; });
	}
	function store(key, value) {
		try {
			if (value === undefined) { return window.localStorage.getItem(key); }
			window.localStorage.setItem(key, value);
		} catch (e) { /* storage blocked: keep working without it */ }
		return null;
	}

	/* 1. Theme toggle ------------------------------------------------------ */
	function currentTheme() {
		var set = root.getAttribute('data-theme');
		if (set) { return set; }
		return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
	}
	document.querySelectorAll('[data-ngiti-theme-toggle]').forEach(function (btn) {
		btn.setAttribute('aria-pressed', String(currentTheme() === 'dark'));
		btn.addEventListener('click', function () {
			var next = currentTheme() === 'dark' ? 'light' : 'dark';
			root.setAttribute('data-theme', next);
			store('ngiti-theme', next);
			btn.setAttribute('aria-pressed', String(next === 'dark'));
		});
	});

	/* 2. Mobile menu --------------------------------------------------------- */
	var toggle = document.querySelector('[data-ngiti-menu-toggle]');
	var nav = document.getElementById('primary-nav');
	if (toggle && nav) {
		var setOpen = function (open) {
			nav.classList.toggle('is-open', open);
			toggle.setAttribute('aria-expanded', String(open));
			document.body.classList.toggle('nav-open', open);
		};
		toggle.addEventListener('click', function () { setOpen(toggle.getAttribute('aria-expanded') !== 'true'); });
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') { setOpen(false); toggle.focus(); }
		});
		nav.addEventListener('click', function (e) { if (e.target.closest('a')) { setOpen(false); } });
		window.addEventListener('resize', function () { if (window.innerWidth > 960) { setOpen(false); } });
	}

	/* 3. Open-now status ------------------------------------------------------ */
	var DAY_NAMES = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
	function clinicNow(tz) {
		// Returns {day: 1-7 (ISO), min: minutes since midnight} in the clinic's timezone.
		var d = new Date();
		var m = /^([+-])(\d{2}):?(\d{2})$/.exec(tz || '');
		if (m) {
			var off = (m[1] === '-' ? -1 : 1) * (parseInt(m[2], 10) * 60 + parseInt(m[3], 10));
			var u = new Date(d.getTime() + off * 60000);
			return { day: ((u.getUTCDay() + 6) % 7) + 1, min: u.getUTCHours() * 60 + u.getUTCMinutes() };
		}
		try {
			var p = {};
			new Intl.DateTimeFormat('en-US', { timeZone: tz || 'Asia/Manila', weekday: 'short', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })
				.formatToParts(d).forEach(function (x) { p[x.type] = x.value; });
			var idx = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].indexOf(p.weekday) + 1;
			return { day: idx, min: parseInt(p.hour, 10) * 60 + parseInt(p.minute, 10) };
		} catch (e) {
			return { day: ((d.getDay() + 6) % 7) + 1, min: d.getHours() * 60 + d.getMinutes() };
		}
	}
	function toMin(hm) { var a = hm.split(':'); return parseInt(a[0], 10) * 60 + parseInt(a[1], 10); }
	function timeLabel(min) {
		var h = Math.floor(min / 60), mm = min % 60;
		if (h === 12 && mm === 0) { return '12:00 NN'; }
		var suf = h < 12 ? 'AM' : 'PM', h12 = h % 12 || 12;
		return h12 + ':' + (mm < 10 ? '0' : '') + mm + ' ' + suf;
	}
	/** Pure status function (exported for tests): week = {1:[["09:00","12:00"],...],...}. */
	function computeStatus(week, now, soonMin) {
		soonMin = soonMin || 30;
		var today = week[now.day] || week[String(now.day)] || [];
		for (var i = 0; i < today.length; i++) {
			var o = toMin(today[i][0]), c = toMin(today[i][1]);
			if (now.min >= o && now.min < c) {
				return { state: c - now.min <= soonMin ? 'soon' : 'open', at: c, offset: 0 };
			}
		}
		for (var k = 0; k < 8; k++) {
			var day = ((now.day - 1 + k) % 7) + 1;
			var r = week[day] || week[String(day)] || [];
			for (var j = 0; j < r.length; j++) {
				var start = toMin(r[j][0]);
				if (k > 0 || start > now.min) { return { state: 'closed', at: start, offset: k, day: day }; }
			}
		}
		return { state: 'closed', at: null, offset: 0 };
	}
	function renderStatus(el) {
		var week;
		try { week = JSON.parse(el.getAttribute('data-ngiti-hours') || '{}'); } catch (e) { return; }
		var st = computeStatus(week, clinicNow(el.getAttribute('data-ngiti-tz')));
		var label = st.state === 'open' ? T.open : (st.state === 'soon' ? T.soon : T.closed);
		var detail = '';
		if (st.at !== null) {
			if (st.state !== 'closed') { detail = fmt(T.closesAt, timeLabel(st.at)); }
			else if (st.offset === 0) { detail = fmt(T.opensAt, timeLabel(st.at)); }
			else if (st.offset === 1) { detail = fmt(T.opensTomorrow, timeLabel(st.at)); }
			else { detail = fmt(T.opensDay, DAY_NAMES[st.day - 1], timeLabel(st.at)); }
		}
		el.className = 'open-status is-' + st.state;
		el.querySelector('b').textContent = label;
		el.querySelector('.detail').textContent = detail;
	}
	var statusEls = document.querySelectorAll('[data-ngiti-hours]');
	statusEls.forEach(renderStatus);
	if (statusEls.length) { setInterval(function () { statusEls.forEach(renderStatus); }, 60000); }

	/* 4. Forms ----------------------------------------------------------------- */
	function localISO(d) {
		return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
	}
	/** Pure validators (exported for tests). */
	var validators = {
		mobile: function (v) {
			var d = String(v).replace(/\D+/g, '');
			if (d.indexOf('639') === 0 && d.length === 12) { d = '0' + d.slice(2); }
			return /^09\d{9}$/.test(d);
		},
		email: function (v) { return v === '' || /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v); },
		date: function (v, closedDays, today) {
			if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) { return 'date'; }
			today = today || new Date();
			var tomorrow = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1);
			var parts = v.split('-'), picked = new Date(+parts[0], +parts[1] - 1, +parts[2]);
			if (picked < tomorrow) { return 'date'; }
			var iso = ((picked.getDay() + 6) % 7) + 1;
			if ((closedDays || []).indexOf(iso) !== -1) { return 'sunday'; }
			return '';
		}
	};
	function setError(field, msg) {
		var wrap = field.closest('.field');
		var out = document.getElementById(field.id + '-error');
		if (wrap) { wrap.classList.toggle('has-error', !!msg); }
		if (out) { out.textContent = msg || ''; }
		field.setAttribute('aria-invalid', msg ? 'true' : 'false');
		if (out) { field.setAttribute('aria-describedby', out.id); }
	}
	function validateField(field, form) {
		var v = (field.value || '').trim(), msg = '';
		var closed = (form.getAttribute('data-closed-days') || '7').split(',').map(Number);
		if (field.type === 'checkbox') {
			msg = field.required && !field.checked ? T.consent : '';
		} else if (field.required && v === '') {
			msg = T.required;
		} else if (field.getAttribute('data-validate') === 'mobile' && v !== '' && !validators.mobile(v)) {
			msg = T.mobile;
		} else if (field.getAttribute('data-validate') === 'email' && !validators.email(v)) {
			msg = T.email;
		} else if (field.getAttribute('data-validate') === 'date' && v !== '') {
			var r = validators.date(v, closed);
			msg = r === 'sunday' ? T.sunday : (r ? T.date : '');
		}
		setError(field, msg);
		return !msg;
	}
	function makeRef() {
		var d = new Date(), chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789', s = '';
		for (var i = 0; i < 4; i++) { s += chars.charAt(Math.floor(Math.random() * chars.length)); }
		return 'NGT-' + String(d.getFullYear()).slice(2) + String(d.getMonth() + 1).padStart(2, '0') + String(d.getDate()).padStart(2, '0') + '-' + s;
	}
	function showDemoSuccess(form, ref) {
		var card = form.closest('.form-card') || form.parentNode;
		var type = form.getAttribute('data-ngiti-form');
		var box = document.createElement('div');
		box.className = 'form-success';
		box.setAttribute('role', 'status');
		box.setAttribute('tabindex', '-1');
		box.innerHTML = '<svg class="success-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>' +
			'<h2></h2><p>Your reference number:</p><p class="ref"></p>' +
			'<p>We will text or call you within clinic hours to confirm. Your slot is final only after we confirm it.</p>' +
			'<p class="demo-flag">Demo form: nothing was sent from this static preview. On WordPress, the theme saves the request and emails the clinic.</p>' +
			'<button type="button" class="btn btn--ghost btn--small">Send another</button>';
		box.querySelector('h2').textContent = type === 'message' ? 'Message sent' : 'Request received';
		box.querySelector('.ref').textContent = ref;
		form.hidden = true;
		card.appendChild(box);
		box.focus();
		box.querySelector('button').addEventListener('click', function () {
			box.remove(); form.reset(); form.hidden = false;
			var first = form.querySelector('input:not([type=hidden]):not([tabindex="-1"])');
			if (first) { first.focus(); }
		});
	}
	document.querySelectorAll('form[data-ngiti-form]').forEach(function (form) {
		var fields = form.querySelectorAll('input[required], select[required], textarea[required], [data-validate]');
		var dateInput = form.querySelector('[data-validate="date"]');
		if (dateInput) {
			var t = new Date(); t.setDate(t.getDate() + 1); dateInput.min = localISO(t);
		}
		fields.forEach(function (f) {
			f.addEventListener('blur', function () { if (f.value !== '' || f.getAttribute('aria-invalid') === 'true') { validateField(f, form); } });
			f.addEventListener('change', function () { if (f.getAttribute('aria-invalid') === 'true' || f.type === 'checkbox' || f.type === 'date') { validateField(f, form); } });
		});
		form.addEventListener('submit', function (e) {
			var ok = true, firstBad = null;
			fields.forEach(function (f) { if (!validateField(f, form)) { ok = false; firstBad = firstBad || f; } });
			var summary = form.querySelector('.form-summary');
			if (summary) { summary.textContent = ok ? '' : T.fix; }
			var hp = form.querySelector('[name="ngiti_website"]');
			if (!ok) { e.preventDefault(); if (firstBad) { firstBad.focus(); } return; }
			if (cfg.demo) {
				e.preventDefault();
				if (hp && hp.value) { return; }
				var ref = makeRef();
				var log = [];
				try { log = JSON.parse(store('ngiti-demo-requests') || '[]'); } catch (err) { log = []; }
				log.push({ ref: ref, type: form.getAttribute('data-ngiti-form'), at: new Date().toISOString() });
				store('ngiti-demo-requests', JSON.stringify(log.slice(-20)));
				showDemoSuccess(form, ref);
				return;
			}
			var btn = form.querySelector('button[type="submit"]');
			if (btn) { btn.disabled = true; }
		});
	});
	var sent = document.querySelector('.form-success[tabindex="-1"]');
	if (sent) { sent.focus(); }

	/* 5. Tooth finder ------------------------------------------------------------ */
	var TYPE_BY_DIGIT = { 1: 'incisor', 2: 'incisor', 3: 'canine', 4: 'premolar', 5: 'premolar', 6: 'molar', 7: 'molar', 8: 'wisdom' };
	var NAME_BY_DIGIT = { 1: 'central incisor', 2: 'lateral incisor', 3: 'canine', 4: 'first premolar', 5: 'second premolar', 6: 'first molar', 7: 'second molar', 8: 'third molar (wisdom tooth)' };
	var QUADRANT = { 1: 'Upper right', 2: 'Upper left', 3: 'Lower left', 4: 'Lower right' };
	/** Pure lookup (exported for tests). */
	function describeTooth(fdi, services) {
		var q = Math.floor(fdi / 10), n = fdi % 10, type = TYPE_BY_DIGIT[n];
		var list = (services || []).filter(function (s) { return s.g.indexOf(type) !== -1 || s.g.indexOf('all') !== -1; });
		return { fdi: fdi, type: type, name: QUADRANT[q] + ' ' + NAME_BY_DIGIT[n], services: list };
	}
	document.querySelectorAll('[data-ngiti-teeth]').forEach(function (finder) {
		var services = [];
		try { services = JSON.parse(finder.getAttribute('data-ngiti-teeth') || '[]'); } catch (e) { services = []; }
		var out = finder.querySelector('[data-ngiti-tooth-result]');
		finder.addEventListener('click', function (e) {
			var btn = e.target.closest('.tooth');
			if (!btn) { return; }
			finder.querySelectorAll('.tooth').forEach(function (b) { b.setAttribute('aria-pressed', String(b === btn)); });
			var info = describeTooth(parseInt(btn.getAttribute('data-fdi'), 10), services);
			out.innerHTML = '';
			var eb = document.createElement('p'); eb.className = 'eyebrow'; eb.textContent = 'Tooth ' + info.fdi;
			var h = document.createElement('h3'); h.textContent = info.name.charAt(0).toUpperCase() + info.name.slice(1);
			var p = document.createElement('p'); p.className = 'muted'; p.textContent = 'Services that often apply to this tooth:';
			var ul = document.createElement('ul');
			info.services.forEach(function (s) {
				var li = document.createElement('li');
				li.textContent = s.t + (s.d ? ' · ' + s.d : '') + (s.p ? ' · ' + s.p : '');
				ul.appendChild(li);
			});
			var note = document.createElement('p'); note.className = 'muted';
			note.textContent = 'Only an examination can confirm what the tooth needs.';
			out.appendChild(eb); out.appendChild(h); out.appendChild(p); out.appendChild(ul); out.appendChild(note);
		});
	});

	// Expose pure helpers for automated tests only.
	window.ngitiTest = { computeStatus: computeStatus, validators: validators, describeTooth: describeTooth, timeLabel: timeLabel };
})();
