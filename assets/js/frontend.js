/**
 * Baza Obozowa – Frontend JavaScript
 * Vanilla JS (no framework). Handles:
 *  - Access screen: cascade dropdowns + login
 *  - Panel tabs
 *  - Daily count form
 *  - Announcements board + submit form
 *  - Logout
 */

/* global bmConfig */

(function () {
	'use strict';

	if (typeof bmConfig === 'undefined') return;

	const API   = bmConfig.restUrl;
	const i18n  = bmConfig.i18n;

	// ── Helpers ───────────────────────────────────────────────────────────────

	async function apiFetch(path, options = {}) {
		const headers = Object.assign({ 'X-WP-Nonce': bmConfig.nonce }, options.headers || {});
		const res = await fetch(API + path, Object.assign({ credentials: 'same-origin', headers }, options));
		const data = await res.json().catch(() => ({}));
		return { ok: res.ok, status: res.status, data };
	}

	function show(el)  { if (el) { el.style.display = ''; el.removeAttribute('aria-hidden'); } }
	function hide(el)  { if (el) { el.style.display = 'none'; el.setAttribute('aria-hidden', 'true'); } }
	function setText(el, txt) { if (el) el.textContent = txt; }
	function setHTML(el, html) { if (el) el.innerHTML = html; }

	function showAlert(el, msg, type) {
		if (!el) return;
		el.className = 'bm-alert bm-alert--' + type;
		el.textContent = msg;
		show(el);
	}

	function formatDate(dateStr) {
		if (!dateStr) return '';
		const d = new Date(dateStr);
		return d.toLocaleDateString('pl-PL');
	}

	// ── Access Screen ─────────────────────────────────────────────────────────

	function initAccessScreen() {
		const screen    = document.getElementById('bm-access-screen');
		if (!screen) return;

		const campSelect  = document.getElementById('bm-camp-select');
		const staffGroup  = document.getElementById('bm-staff-group');
		const staffSelect = document.getElementById('bm-staff-select');
		const codeGroup   = document.getElementById('bm-code-group');
		const codeInput   = document.getElementById('bm-security-code');
		const submitGroup = document.getElementById('bm-submit-group');
		const errorDiv    = document.getElementById('bm-login-error');
		const loginBtn    = document.getElementById('bm-login-btn');
		const loginForm   = document.getElementById('bm-login-form');

		// Load camps.
		apiFetch('public/camps').then(({ ok, data }) => {
			if (!ok || !Array.isArray(data)) return;
			data.forEach(camp => {
				const opt = document.createElement('option');
				opt.value       = camp.id;
				opt.textContent = camp.name;
				campSelect.appendChild(opt);
			});
		});

		// Camp selected → load staff.
		campSelect.addEventListener('change', async () => {
			const campId = campSelect.value;
			hide(staffGroup);
			hide(codeGroup);
			hide(submitGroup);
			hide(errorDiv);

			if (!campId) return;

			const { ok, data } = await apiFetch('public/camps/' + campId + '/staff');
			if (!ok || !Array.isArray(data)) return;

			// Rebuild staff dropdown.
			staffSelect.innerHTML = '<option value="">' + i18n.selectPerson + '</option>';
			data.forEach(s => {
				const opt = document.createElement('option');
				opt.value       = s.id;
				opt.textContent = s.display_name + (s.role ? ' (' + s.role + ')' : '');
				staffSelect.appendChild(opt);
			});

			show(staffGroup);
			staffGroup.removeAttribute('aria-hidden');
		});

		// Staff selected → show code field.
		staffSelect.addEventListener('change', () => {
			if (staffSelect.value) {
				show(codeGroup);
				show(submitGroup);
				codeInput.focus();
			} else {
				hide(codeGroup);
				hide(submitGroup);
			}
			hide(errorDiv);
		});

		// Login form submit.
		loginForm.addEventListener('submit', async (e) => {
			e.preventDefault();
			hide(errorDiv);
			loginBtn.disabled = true;
			loginBtn.textContent = i18n.loading;

			const { ok, data } = await apiFetch('auth/login', {
				method:  'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': bmConfig.nonce },
				body: JSON.stringify({
					camp_id:       parseInt(campSelect.value, 10),
					staff_id:      parseInt(staffSelect.value, 10),
					security_code: codeInput.value,
					nonce:         bmConfig.loginNonce,
				}),
			});

			if (ok && data.success) {
				// Reload page so PHP re-renders with the new cookie.
				window.location.reload();
				return;
			}

			const msg = data.message || i18n.error;
			showAlert(errorDiv, msg, 'error');
			codeInput.value = '';
			codeInput.focus();
			loginBtn.disabled = false;
			loginBtn.textContent = document.getElementById('bm-login-form')
				.querySelector('button[type=submit]').textContent || 'Wejdź do panelu';
		});
	}

	// ── Panel ─────────────────────────────────────────────────────────────────

	function initPanel() {
		const panel = document.getElementById('bm-panel');
		if (!panel) return;

		const campId  = parseInt(panel.dataset.campId,  10);
		const staffId = parseInt(panel.dataset.staffId, 10);
		const nonce   = panel.dataset.nonce;

		// Tabs.
		const tabs    = panel.querySelectorAll('.bm-tab');
		const tabPanes = panel.querySelectorAll('.bm-tab-content');

		tabs.forEach(tab => {
			tab.addEventListener('click', () => {
				tabs.forEach(t => { t.classList.remove('bm-tab--active'); t.setAttribute('aria-selected', 'false'); });
				tabPanes.forEach(p => hide(p));
				tab.classList.add('bm-tab--active');
				tab.setAttribute('aria-selected', 'true');
				const pane = document.getElementById('bm-tab-' + tab.dataset.tab);
				if (pane) show(pane);
			});
		});

		// Logout.
		document.getElementById('bm-logout-btn')?.addEventListener('click', async () => {
			if (!confirm(i18n.logoutConfirm)) return;
			await apiFetch('auth/logout', { method: 'POST' });
			window.location.reload();
		});

		// Load camp overview.
		loadCampOverview(campId, nonce);

		// Load announcements.
		loadAnnouncements(campId, nonce);

		// Daily count form.
		initDailyCount(campId, staffId, nonce);

		// Announcement submit form.
		initAnnForm(campId, nonce);
	}

	// ── Overview ─────────────────────────────────────────────────────────────

	async function loadCampOverview(campId, nonce) {
		const { ok, data } = await apiFetch('panel/camp');
		const container = document.getElementById('bm-overview-content');
		const title     = document.getElementById('bm-camp-name');

		if (!ok) {
			setHTML(container, '<p class="bm-alert bm-alert--error">' + i18n.error + '</p>');
			return;
		}

		setText(title, data.name);

		const lc    = data.latest_count;
		const total = lc ? (lc.participants + lc.staff + lc.workers) : 0;

		const submittedMsg = data.submitted_today
			? '<span class="bm-badge bm-badge--active">✓ Zgłoszono dziś</span>'
			: '<span class="bm-badge bm-badge--pending">⚠ Brak dzisiejszego meldunku</span>';

		setHTML(container, `
			<h3 style="margin-top:0">${escHtml(data.name)}</h3>
			<p>
				<strong>Okres:</strong> ${escHtml(formatDate(data.start_date))} – ${escHtml(formatDate(data.end_date))}
				&nbsp; <strong>Status:</strong> ${escHtml(data.status)}
			</p>
			<p>${submittedMsg}</p>
			${lc ? `
			<div class="bm-overview-stats">
				<div class="bm-overview-stat">
					<div class="bm-overview-stat__label">Uczestnicy</div>
					<div class="bm-overview-stat__value">${lc.participants}</div>
				</div>
				<div class="bm-overview-stat">
					<div class="bm-overview-stat__label">Kadra</div>
					<div class="bm-overview-stat__value">${lc.staff}</div>
				</div>
				<div class="bm-overview-stat">
					<div class="bm-overview-stat__label">Pracownicy</div>
					<div class="bm-overview-stat__value">${lc.workers}</div>
				</div>
				<div class="bm-overview-stat">
					<div class="bm-overview-stat__label">Łącznie</div>
					<div class="bm-overview-stat__value">${total}</div>
				</div>
			</div>
			<p style="font-size:12px;color:var(--bm-muted)">Dane z: ${escHtml(formatDate(lc.count_date))}</p>
			` : '<p>Brak wpisów liczebności.</p>'}
		`);

		// Warn about missing count – badge on tab.
		if (!data.submitted_today) {
			const badge = document.getElementById('bm-pending-badge');
			if (badge) { badge.textContent = '!'; show(badge); }
		}
	}

	// ── Daily count ───────────────────────────────────────────────────────────

	async function initDailyCount(campId, staffId, nonce) {
		const form    = document.getElementById('bm-count-form');
		if (!form) return;

		const pField  = document.getElementById('bm-participants');
		const sField  = document.getElementById('bm-staff');
		const wField  = document.getElementById('bm-workers');
		const total   = document.getElementById('bm-count-total');
		const notice  = document.getElementById('bm-count-submitted-notice');

		function updateTotal() {
			const t = parseInt(pField.value || 0, 10) + parseInt(sField.value || 0, 10) + parseInt(wField.value || 0, 10);
			setText(total, t);
		}

		[pField, sField, wField].forEach(f => f.addEventListener('input', updateTotal));

		// Pre-fill from last count.
		const { ok, data } = await apiFetch('panel/daily-count/last');
		if (ok && data.found) {
			pField.value = data.participants;
			sField.value = data.staff;
			wField.value = data.workers;
			updateTotal();
		}

		// Check if already submitted today.
		const campRes = await apiFetch('panel/camp');
		if (campRes.ok && campRes.data.submitted_today) {
			show(notice);
		}

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			const errEl = document.getElementById('bm-count-error');
			const sucEl = document.getElementById('bm-count-success');
			hide(errEl); hide(sucEl);

			const btn = form.querySelector('button[type=submit]');
			btn.disabled = true;

			const { ok, data: res } = await apiFetch('panel/daily-count', {
				method:  'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': bmConfig.nonce },
				body: JSON.stringify({
					participants: parseInt(pField.value, 10),
					staff:        parseInt(sField.value, 10),
					workers:      parseInt(wField.value, 10),
					notes:        document.getElementById('bm-count-notes').value,
					nonce:        nonce,
				}),
			});

			btn.disabled = false;

			if (ok && res.success) {
				showAlert(sucEl, 'Stan liczebności zapisany (' + res.date + ').', 'success');
				show(notice);
			} else {
				showAlert(errEl, res.message || i18n.error, 'error');
			}
		});
	}

	// ── Announcements ─────────────────────────────────────────────────────────

	async function loadAnnouncements(campId, nonce) {
		const { ok, data } = await apiFetch('panel/announcements');
		if (!ok) return;

		renderAnnList(document.getElementById('bm-announcements-active'),   data.active,   false);
		renderAnnList(document.getElementById('bm-announcements-archived'), data.archived, true);
		renderAnnList(document.getElementById('bm-announcements-own'),      data.own,      false, true);
	}

	function renderAnnList(container, items, archived, showStatus = false) {
		if (!container) return;
		if (!items || items.length === 0) {
			container.innerHTML = '<p style="color:var(--bm-muted);font-size:13px">Brak ogłoszeń.</p>';
			return;
		}

		container.innerHTML = items.map(ann => {
			const urgent = ann.is_urgent ? ' bm-ann-item--urgent' : '';
			const badge  = ann.is_urgent ? '<span class="bm-badge bm-badge--urgent">PILNE</span> ' : '';
			const status = showStatus ? `<span class="bm-badge bm-badge--${escHtml(ann.status)}">${escHtml(ann.status)}</span> ` : '';
			const attach = ann.attachment_url
				? `<div class="bm-ann-item__attach"><a href="${escHtml(ann.attachment_url)}" target="_blank" rel="noopener">📎 Załącznik</a></div>`
				: '';
			return `
				<div class="bm-ann-item${urgent}">
					<div class="bm-ann-item__title">${badge}${status}${escHtml(ann.title)}</div>
					<div class="bm-ann-item__meta">
						Ważne: ${escHtml(formatDate(ann.valid_from))} – ${escHtml(formatDate(ann.valid_until))}
					</div>
					<div class="bm-ann-item__body">${ann.content}</div>
					${attach}
				</div>
			`;
		}).join('');
	}

	function initAnnForm(campId, nonce) {
		const form = document.getElementById('bm-ann-form');
		if (!form) return;

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			const errEl = document.getElementById('bm-ann-error');
			const sucEl = document.getElementById('bm-ann-success');
			hide(errEl); hide(sucEl);

			const btn  = form.querySelector('button[type=submit]');
			btn.disabled = true;

			const fromVal  = form.querySelector('[name=valid_from]').value;
			const untilVal = form.querySelector('[name=valid_until]').value;

			const { ok, data } = await apiFetch('panel/announcements', {
				method:  'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': bmConfig.nonce },
				body: JSON.stringify({
					title:          form.querySelector('[name=title]').value,
					content:        form.querySelector('[name=content]').value,
					valid_from:     fromVal,
					valid_until:    untilVal,
					attachment_url: form.querySelector('[name=attachment_url]').value,
					nonce:          nonce,
				}),
			});

			btn.disabled = false;

			if (ok && data.success) {
				showAlert(sucEl, 'Ogłoszenie wysłane do akceptacji administratora.', 'success');
				form.reset();
				// Reload own list.
				loadAnnouncements(campId, nonce);
			} else {
				showAlert(errEl, data.message || i18n.error, 'error');
			}
		});
	}

	// ── Escape helper (no XSS in dynamic content) ─────────────────────────────
	function escHtml(str) {
		if (typeof str !== 'string') return '';
		return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
				  .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
	}

	// ── Boot ──────────────────────────────────────────────────────────────────
	document.addEventListener('DOMContentLoaded', () => {
		if (!bmConfig.authenticated) {
			initAccessScreen();
		} else {
			initPanel();
		}
	});

})();
