/**
 * Baza Obozowa – Alpine.js API helper (bm-api.js)
 *
 * Exposes:
 *   window.bmApi          – thin fetch wrapper around REST endpoints
 *   Alpine store: bmStore – reactive global state (session, camp, counts, announcements)
 *   Alpine components:
 *     bmLogin()           – login form component
 *     bmCamp()            – camp overview component
 *     bmDailyCount()      – daily headcount form component
 *     bmAnnouncements()   – announcements board component
 *     bmAnnForm()         – submit new announcement component
 *
 * Usage in Breakdance (Code Block or Custom HTML element):
 *   Add x-data="bmLogin()" to your login form container.
 *   Bind inputs with x-model, button with @click / @submit.
 *   No additional JavaScript needed.
 */

/* global bmConfig, Alpine */

// ── API wrapper ───────────────────────────────────────────────────────────────

window.bmApi = (function () {
	const base  = bmConfig.restUrl;
	const wpNonce = bmConfig.wpNonce;

	async function request(path, method, body) {
		const opts = {
			method:      method || 'GET',
			credentials: 'same-origin',
			headers:     { 'X-WP-Nonce': wpNonce },
		};
		if (body) {
			opts.headers['Content-Type'] = 'application/json';
			opts.body = JSON.stringify(body);
		}
		const res  = await fetch(base + path, opts);
		const data = await res.json().catch(() => ({}));
		return { ok: res.ok, status: res.status, data };
	}

	return {
		get:  (path)        => request(path, 'GET'),
		post: (path, body)  => request(path, 'POST', body),

		// Convenience methods matching each REST endpoint:
		getCamps:           ()       => request('public/camps', 'GET'),
		getCampStaff:       (campId) => request(`public/camps/${campId}/staff`, 'GET'),
		getAuthStatus:      ()       => request('auth/status', 'GET'),
		login:              (payload)=> request('auth/login',  'POST', payload),
		logout:             ()       => request('auth/logout', 'POST'),
		getCamp:            ()       => request('panel/camp', 'GET'),
		getLastCount:       ()       => request('panel/daily-count/last', 'GET'),
		submitCount:        (payload)=> request('panel/daily-count', 'POST', payload),
		getAnnouncements:   ()       => request('panel/announcements', 'GET'),
		submitAnnouncement: (payload)=> request('panel/announcements', 'POST', payload),

		// Reports (Meldunki)
		getReportToday:     ()       => request('panel/reports/today', 'GET'),
		saveReportDraft:    (payload)=> request('panel/reports/save',  'POST', payload),
		submitReport:       (payload)=> request('panel/reports/submit','POST', payload),
		getReportHistory:   (limit)  => request(`panel/reports/history?limit=${limit || 30}`, 'GET'),

		// Schedule (Plan dnia)
		getSchedule:        (date)       => request(`panel/schedule?date=${date || ''}`, 'GET'),
		getScheduleDates:   ()           => request('panel/schedule/dates', 'GET'),

		// Reservations (Rezerwacje)
		getResources:       ()           => request('panel/reservations/resources', 'GET'),
		getReservationSlots:(rid, date)  => request(`panel/reservations/slots?resource_id=${rid}&date=${date}`, 'GET'),
		getMyReservations:  (status)     => request(`panel/reservations${status ? '?status=' + status : ''}`, 'GET'),
		createReservation:  (payload)    => request('panel/reservations', 'POST', payload),
		cancelReservation:  (id)         => request(`panel/reservations/${id}/cancel`, 'POST'),

		// Weather (Pogoda)
		getWeather:         ()       => request('panel/weather', 'GET'),

		// Menu (Jadłospis)
		getMenu:            (date)   => request(`panel/menu?date=${date || ''}`, 'GET'),
		getMenuDates:       ()       => request('panel/menu/dates', 'GET'),
		getMenuWeek:        (from)   => request(`panel/menu/week?from=${from || ''}`, 'GET'),

		// Communication (Komunikacja)
		getThreads:         ()             => request('panel/messages', 'GET'),
		createThread:       (payload)      => request('panel/messages', 'POST', payload),
		getThread:          (id)           => request(`panel/messages/${id}`, 'GET'),
		replyThread:        (id, payload)  => request(`panel/messages/${id}/reply`, 'POST', payload),

		// Help (Pomoc)
		getHelp:            (params) => request('panel/help' + (params ? '?' + new URLSearchParams(params).toString() : ''), 'GET'),
		getHelpArticle:     (id)     => request(`panel/help/${id}`, 'GET'),

		// Forms & Submissions (Formularze i Zgłoszenia)
		getForms:           (params) => request('panel/forms' + (params ? '?' + new URLSearchParams(params).toString() : ''), 'GET'),
		getForm:            (id)     => request(`panel/forms/${id}`, 'GET'),
		submitForm:         (payload)=> request('panel/submissions', 'POST', payload),
		getSubmissions:     (params) => request('panel/submissions' + (params ? '?' + new URLSearchParams(params).toString() : ''), 'GET'),
		getSubmission:      (id)     => request(`panel/submissions/${id}`, 'GET'),
		getAttachmentUrl:   (subId, attId) => bmConfig.restUrl + `panel/submissions/${subId}/attachment/${attId}`,

		// Camp Folder (Teczka obozu)
		getFolderDocuments:   ()        => request('panel/folder/documents', 'GET'),
		getCampDocuments:     ()        => request('panel/folder/camp-documents', 'GET'),
		getFolderDamages:     ()        => request('panel/folder/damages', 'GET'),
		reportDamage:         (payload) => request('panel/folder/damages', 'POST', payload),
		getFolderDeclaration: ()        => request('panel/folder/declaration', 'GET'),
		saveDeclarationDay:   (payload) => request('panel/folder/declaration/day', 'POST', payload),
		getDeclDocs:          ()        => request('panel/folder/decl-docs', 'GET'),
		approveDeclDoc:       (id, nonce) => request(`panel/folder/decl-docs/${id}/approve`, 'POST', { nonce }),
		getEquipment:         ()        => request('panel/folder/equipment', 'GET'),
		issueEquipment:       (payload) => request('panel/folder/equipment', 'POST', payload),
		returnEquipment:      (id, qty, nonce) => request(`panel/folder/equipment/${id}/return`, 'POST', { return_qty: qty, nonce }),
	};
})();


// ── Alpine store & component registration ────────────────────────────────────
//
// Breakdance ships its own Alpine v3 and may initialise it BEFORE or AFTER
// this script loads. We handle both cases with bmRegisterAll().
