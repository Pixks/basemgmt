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
	};
})();


// ── Alpine store & component registration ────────────────────────────────────
//
// Breakdance ships its own Alpine v3 and may initialise it BEFORE or AFTER
// this script loads. We handle both cases with bmRegisterAll().

function bmRegisterAll() {
if (typeof Alpine === 'undefined') return;

if (!Alpine.store('bm')) {
Alpine.store('bm', {
authenticated:  bmConfig.authenticated,
campId:         bmConfig.campId,
staffId:        bmConfig.staffId,
displayName:    bmConfig.displayName,
camp:           null,
latestCount:    null,
submittedToday: false,
announcements:  { active: [], archived: [], own: [] },

async init() {
if (this.authenticated) {
await this.loadCamp();
await this.loadAnnouncements();
}
},
async loadCamp() {
const { ok, data } = await bmApi.getCamp();
if (!ok) return;
this.camp           = data;
this.latestCount    = data.latest_count;
this.submittedToday = data.submitted_today;
},
async loadAnnouncements() {
const { ok, data } = await bmApi.getAnnouncements();
if (!ok) return;
this.announcements = {
active:   data.active   || [],
archived: data.archived || [],
own:      data.own      || [],
};
},
setSession(data) {
this.authenticated = true;
this.campId        = data.camp_id;
this.staffId       = data.staff_id;
this.displayName   = data.display_name;
},
clearSession() {
this.authenticated = false;
this.campId = 0; this.staffId = 0; this.displayName = '';
this.camp = null; this.latestCount = null;
},
});
}

if (typeof Alpine.data === 'function') {
Alpine.data('bmLogin',         window.bmLogin);
Alpine.data('bmCamp',          window.bmCamp);
Alpine.data('bmDailyCount',    window.bmDailyCount);
Alpine.data('bmAnnouncements', window.bmAnnouncements);
Alpine.data('bmAnnForm',       window.bmAnnForm);
Alpine.data('bmLogout',        window.bmLogout);
Alpine.data('bmReports',       window.bmReports);
Alpine.data('bmWeather',       window.bmWeather);
Alpine.data('bmSchedule',      window.bmSchedule);
Alpine.data('bmReservations',  window.bmReservations);
Alpine.data('bmMenu',          window.bmMenu);
Alpine.data('bmConversations', window.bmConversations);
Alpine.data('bmHelp',          window.bmHelp);
Alpine.data('bmForms',         window.bmForms);
Alpine.data('bmSubmissions',   window.bmSubmissions);
}
}

document.addEventListener('alpine:init', bmRegisterAll);

if (typeof Alpine !== 'undefined') {
bmRegisterAll();
}

document.addEventListener('alpine:initialized', () => {
const store = typeof Alpine !== 'undefined' ? Alpine.store('bm') : null;
if (store && typeof store.init === 'function') {
store.init();
}
});


window.bmLogin = function () {
	return {
		camps:    [],
		staffList:[],
		campId:   '',
		staffId:  '',
		code:     '',
		loading:  false,
		error:    '',

		async init() {
			const { ok, data } = await bmApi.getCamps();
			if (ok) this.camps = data;
		},

		async loadStaff() {
			this.staffId  = '';
			this.staffList = [];
			this.error    = '';
			if (!this.campId) return;
			const { ok, data } = await bmApi.getCampStaff(this.campId);
			if (ok) this.staffList = data;
		},

		async submit() {
			if (!this.campId || !this.staffId || !this.code) return;
			this.loading = true;
			this.error   = '';

			const { ok, data } = await bmApi.login({
				camp_id:       parseInt(this.campId,  10),
				staff_id:      parseInt(this.staffId, 10),
				security_code: this.code,
				nonce:         bmConfig.loginNonce,
			});

			this.loading = false;

			if (ok && data.success) {
				Alpine.store('bm').setSession(data);
				await Alpine.store('bm').loadCamp();
				await Alpine.store('bm').loadAnnouncements();
				// Trigger a custom event so Breakdance visibility conditions can react.
				window.dispatchEvent(new CustomEvent('bm:login', { detail: data }));
			} else {
				this.error = data.message || 'Nieprawidłowe dane logowania.';
				this.code  = '';
				if (data.locked_until) {
					this.error += ` Spróbuj ponownie za ${Math.ceil(data.locked_until / 60)} min.`;
				}
			}
		},
	};
};

// ── Alpine component: Camp overview ──────────────────────────────────────────

/**
 * Breakdance usage example:
 *
 * <div x-data="bmCamp()">
 *   <h2 x-text="camp?.name ?? '…'"></h2>
 *   <p>Okres: <span x-text="camp?.start_date"></span> – <span x-text="camp?.end_date"></span></p>
 *   <p x-show="submittedToday" style="color:green">✓ Meldunek dziś złożony</p>
 *   <p x-show="!submittedToday" style="color:orange">⚠ Brak dzisiejszego meldunku</p>
 *   <p>Uczestnicy: <strong x-text="latestCount?.participants ?? 0"></strong></p>
 *   <p>Kadra:      <strong x-text="latestCount?.staff        ?? 0"></strong></p>
 *   <p>Pracownicy: <strong x-text="latestCount?.workers      ?? 0"></strong></p>
 *   <p>Łącznie:    <strong x-text="latestCount?.total        ?? 0"></strong></p>
 * </div>
 */
window.bmCamp = function () {
	return {
		get camp()           { return Alpine.store('bm').camp; },
		get latestCount()    { return Alpine.store('bm').latestCount; },
		get submittedToday() { return Alpine.store('bm').submittedToday; },
		get authenticated()  { return Alpine.store('bm').authenticated; },

		async init() {
			if (this.authenticated && !this.camp) {
				await Alpine.store('bm').loadCamp();
			}
		},
	};
};

// ── Alpine component: Daily count form ───────────────────────────────────────

/**
 * Breakdance usage example:
 *
 * <form x-data="bmDailyCount()" @submit.prevent="submit" x-init="init()">
 *
 *   <input type="number" x-model.number="participants" min="0">
 *   <input type="number" x-model.number="staff"        min="0">
 *   <input type="number" x-model.number="workers"      min="0">
 *   <textarea x-model="notes"></textarea>
 *
 *   <!-- Reactive total -->
 *   <p>Łącznie: <strong x-text="total"></strong></p>
 *
 *   <p x-show="success" x-text="success" style="color:green"></p>
 *   <p x-show="error"   x-text="error"   style="color:red"></p>
 *
 *   <button type="submit" :disabled="loading">
 *     <span x-show="!loading">Zapisz</span>
 *     <span x-show="loading">Zapisuję…</span>
 *   </button>
 *
 * </form>
 */
window.bmDailyCount = function () {
	return {
		participants: 0,
		staff:        0,
		workers:      0,
		notes:        '',
		loading:      false,
		success:      '',
		error:        '',

		get total() {
			return this.participants + this.staff + this.workers;
		},

		async init() {
			// Pre-fill from last count.
			const { ok, data } = await bmApi.getLastCount();
			if (ok && data.found) {
				this.participants = data.participants;
				this.staff        = data.staff;
				this.workers      = data.workers;
			}
		},

		async submit() {
			this.loading = true;
			this.success = '';
			this.error   = '';

			const { ok, data } = await bmApi.submitCount({
				participants: this.participants,
				staff:        this.staff,
				workers:      this.workers,
				notes:        this.notes,
				nonce:        bmConfig.panelNonce,
			});

			this.loading = false;

			if (ok && data.success) {
				this.success = `Stan zapisany (${data.date}).`;
				Alpine.store('bm').submittedToday = true;
				await Alpine.store('bm').loadCamp();
				window.dispatchEvent(new CustomEvent('bm:countSaved', { detail: data }));
			} else {
				this.error = data.message || 'Błąd zapisu.';
			}
		},
	};
};

// ── Alpine component: Announcements board ────────────────────────────────────

/**
 * Breakdance usage example:
 *
 * <div x-data="bmAnnouncements()">
 *   <!-- Active list -->
 *   <template x-for="ann in active" :key="ann.id">
 *     <div :class="ann.is_urgent ? 'urgent' : ''">
 *       <h3 x-text="ann.title"></h3>
 *       <p  x-html="ann.content"></p>
 *       <small x-text="'Do: ' + ann.valid_until"></small>
 *       <a :href="ann.attachment_url" x-show="ann.attachment_url" target="_blank">Załącznik</a>
 *     </div>
 *   </template>
 *
 *   <!-- Archived list -->
 *   <template x-for="ann in archived" :key="ann.id">
 *     <div x-text="ann.title"></div>
 *   </template>
 * </div>
 */
window.bmAnnouncements = function () {
	return {
		get active()   { return Alpine.store('bm').announcements.active; },
		get archived() { return Alpine.store('bm').announcements.archived; },
		get own()      { return Alpine.store('bm').announcements.own; },

		async refresh() {
			await Alpine.store('bm').loadAnnouncements();
		},
	};
};

// ── Alpine component: Submit announcement form ────────────────────────────────

/**
 * Breakdance usage example:
 *
 * <form x-data="bmAnnForm()" @submit.prevent="submit">
 *   <input  type="text"     x-model="title"          required>
 *   <textarea               x-model="content"        ></textarea>
 *   <input  type="date"     x-model="valid_from"     required>
 *   <input  type="date"     x-model="valid_until"    required>
 *   <input  type="url"      x-model="attachment_url" >
 *   <p x-show="success" x-text="success" style="color:green"></p>
 *   <p x-show="error"   x-text="error"   style="color:red"></p>
 *   <button type="submit" :disabled="loading">Wyślij do zatwierdzenia</button>
 * </form>
 */
window.bmAnnForm = function () {
	return {
		title:          '',
		content:        '',
		valid_from:     '',
		valid_until:    '',
		attachment_url: '',
		loading:        false,
		success:        '',
		error:          '',

		async submit() {
			this.loading = true;
			this.success = '';
			this.error   = '';

			const { ok, data } = await bmApi.submitAnnouncement({
				title:          this.title,
				content:        this.content,
				valid_from:     this.valid_from,
				valid_until:    this.valid_until,
				attachment_url: this.attachment_url,
				nonce:          bmConfig.panelNonce,
			});

			this.loading = false;

			if (ok && data.success) {
				this.success = 'Ogłoszenie wysłane do akceptacji.';
				this.title = this.content = this.valid_from = this.valid_until = this.attachment_url = '';
				await Alpine.store('bm').loadAnnouncements();
				window.dispatchEvent(new CustomEvent('bm:annSubmitted', { detail: data }));
			} else {
				this.error = data.message || 'Błąd wysyłania.';
			}
		},
	};
};

// ── Alpine component: Logout button ──────────────────────────────────────────

/**
 * Breakdance usage example:
 *
 * <button x-data="bmLogout()" @click="logout">Wyloguj</button>
 */
window.bmLogout = function () {
	return {
		async logout() {
			await bmApi.logout();
			Alpine.store('bm').clearSession();
			window.dispatchEvent(new CustomEvent('bm:logout'));
			// Optional: reload page to reset server-side rendered state.
			window.location.reload();
		},
	};
};

// ── Alpine component: Reports (Meldunki) ─────────────────────────────────────

/**
 * Breakdance usage example:
 *
 * <div x-data="bmReports()" x-init="init()">
 *
 *   <!-- Status badge -->
 *   <p x-text="statusLabel"></p>
 *
 *   <!-- Form fields -->
 *   <input type="number" x-model.number="form.participants" min="0">
 *   <input type="number" x-model.number="form.staff"        min="0">
 *   <input type="number" x-model.number="form.workers"      min="0">
 *   <textarea x-model="form.notes"></textarea>
 *
 *   <p x-show="success" x-text="success" style="color:green"></p>
 *   <p x-show="error"   x-text="error"   style="color:red"></p>
 *
 *   <button @click="saveDraft()" :disabled="loading || isSubmitted">Zapisz roboczo</button>
 *   <button @click="submit()"    :disabled="loading || isSubmitted">Wyślij meldunek</button>
 *
 *   <!-- History -->
 *   <template x-for="r in history" :key="r.id">
 *     <div>
 *       <span x-text="r.count_date"></span>
 *       <span x-text="r.participants + ' / ' + r.staff + ' / ' + r.workers"></span>
 *       <span x-text="r.status"></span>
 *     </div>
 *   </template>
 * </div>
 */
window.bmReports = function () {
	return {
		form: {
			participants: 0,
			staff:        0,
			workers:      0,
			notes:        '',
		},
		today:       null,    // today's report object from API
		history:     [],
		loading:     false,
		success:     '',
		error:       '',

		get isSubmitted() {
			return this.today?.status === 'submitted';
		},

		get statusLabel() {
			const map = { none: 'Brak', draft: 'Roboczy', submitted: 'Wysłany' };
			return map[this.today?.status] ?? 'Brak';
		},

		get total() {
			return (this.form.participants || 0) + (this.form.staff || 0) + (this.form.workers || 0);
		},

		async init() {
			this.loading = true;
			const { ok, data } = await bmApi.getReportToday();
			if (ok) {
				if (data.today) {
					this.today = data.today;
					this.form.participants = data.today.participants;
					this.form.staff        = data.today.staff;
					this.form.workers      = data.today.workers;
					this.form.notes        = data.today.notes || '';
				} else if (data.prefill) {
					this.form.participants = data.prefill.participants;
					this.form.staff        = data.prefill.staff;
					this.form.workers      = data.prefill.workers;
				}
			}
			await this.loadHistory();
			this.loading = false;
		},

		async loadHistory() {
			const { ok, data } = await bmApi.getReportHistory(30);
			if (ok) this.history = data.reports || [];
		},

		async saveDraft() {
			if (this.isSubmitted) return;
			this.loading = true;
			this.success = '';
			this.error   = '';

			const { ok, data } = await bmApi.saveReportDraft({ ...this.form, nonce: bmConfig.panelNonce });
			this.loading = false;

			if (ok) {
				this.today   = data.report;
				this.success = 'Meldunek zapisany roboczo.';
				await this.loadHistory();
				window.dispatchEvent(new CustomEvent('bm:reportSaved', { detail: data }));
			} else {
				this.error = data.message || 'Błąd zapisu.';
			}
		},

		async submit() {
			if (this.isSubmitted) return;
			if (!confirm('Wysłać meldunek? Nie będzie możliwości zmiany.')) return;

			this.loading = true;
			this.success = '';
			this.error   = '';

			const { ok, data } = await bmApi.submitReport({ ...this.form, nonce: bmConfig.panelNonce });
			this.loading = false;

			if (ok) {
				this.today   = data.report;
				this.success = 'Meldunek wysłany pomyślnie!';
				await this.loadHistory();
				window.dispatchEvent(new CustomEvent('bm:reportSubmitted', { detail: data }));
			} else {
				this.error = data.message || 'Błąd wysyłki.';
			}
		},
	};
};

// ── Alpine component: Weather (Pogoda) ───────────────────────────────────────

/**
 * Breakdance usage example:
 *
 * <div x-data="bmWeather()" x-init="init()">
 *
 *   <!-- Current weather -->
 *   <template x-if="weather">
 *     <div>
 *       <p><span x-text="weather.current.icon"></span> <span x-text="weather.current.label"></span></p>
 *       <p x-text="weather.current.temperature + '°C'"></p>
 *       <p>💨 <span x-text="weather.current.windspeed"></span> km/h</p>
 *       <p>💧 <span x-text="weather.current.humidity"></span>%</p>
 *     </div>
 *   </template>
 *
 *   <!-- Forecast -->
 *   <template x-for="day in forecast" :key="day.date">
 *     <div>
 *       <span x-text="day.date"></span>
 *       <span x-text="day.icon + ' ' + day.label"></span>
 *       <span x-text="day.temp_max + '° / ' + day.temp_min + '°'"></span>
 *     </div>
 *   </template>
 *
 *   <!-- Alerts -->
 *   <template x-for="alert in alerts" :key="alert.id">
 *     <div :class="alert.is_urgent ? 'urgent-alert' : 'normal-alert'">
 *       <strong x-text="alert.title"></strong>
 *       <p x-text="alert.message"></p>
 *     </div>
 *   </template>
 *
 *   <p x-show="!configured" style="color:orange">Lokalizacja nie skonfigurowana.</p>
 * </div>
 */
window.bmWeather = function () {
	return {
		weather:    null,
		alerts:     [],
		location:   '',
		configured: false,
		loading:    false,
		error:      '',

		get current()  { return this.weather?.current  || null; },
		get forecast() { return this.weather?.forecast || []; },
		get hasAlerts() { return this.alerts.length > 0; },
		get urgentAlerts() { return this.alerts.filter(a => a.is_urgent); },

		async init() {
			this.loading = true;
			const { ok, data } = await bmApi.getWeather();
			if (ok) {
				this.weather    = data.weather;
				this.alerts     = data.alerts   || [];
				this.location   = data.location || '';
				this.configured = data.configured;
			} else {
				this.error = 'Nie udało się pobrać danych pogodowych.';
			}
			this.loading = false;
		},
	};
};

// ── Boot store after Alpine is ready ─────────────────────────────────────────

document.addEventListener('alpine:initialized', () => {
	Alpine.store('bm').init();
});

// ── Alpine component: Schedule (Plan dnia) ────────────────────────────────────

/**
 * Breakdance usage example:
 *
 * <div x-data="bmSchedule()" x-init="init()">
 *
 *   <!-- Date navigation -->
 *   <input type="date" x-model="selectedDate" @change="loadSchedule()">
 *
 *   <!-- Plan items -->
 *   <template x-for="plan in plans" :key="plan.id">
 *     <div>
 *       <h3 x-text="plan.title || 'Plan dnia'"></h3>
 *       <template x-for="item in plan.items" :key="item.id">
 *         <div :class="{
 *           'cancelled': item.item_status === 'cancelled',
 *           'changed':   item.item_status === 'changed',
 *           'new-today': item.is_new_today,
 *         }">
 *           <strong x-text="item.time_from + (item.time_to ? ' – ' + item.time_to : '')"></strong>
 *           <span x-text="item.title"></span>
 *           <span x-show="item.is_new_today"     style="color:green;">🆕 nowe</span>
 *           <span x-show="item.is_updated_today" style="color:orange;">✏ zmienione</span>
 *           <span x-show="item.item_status==='cancelled'" style="color:red;">❌ odwołane</span>
 *           <p x-text="item.description"></p>
 *         </div>
 *       </template>
 *     </div>
 *   </template>
 *
 *   <!-- Date picker buttons -->
 *   <template x-for="d in availableDates" :key="d">
 *     <button @click="selectDate(d)" :class="d === selectedDate ? 'active' : ''" x-text="d"></button>
 *   </template>
 *
 *   <p x-show="!loading && !plans.length" style="color:#888;">Brak planu na wybrany dzień.</p>
 * </div>
 */
window.bmSchedule = function () {
	return {
		plans:          [],
		availableDates: [],
		selectedDate:   new Date().toISOString().slice(0, 10),
		loading:        false,
		error:          '',

		get hasChanges() {
			return this.plans.some(p => p.items.some(i => i.is_new_today || i.is_updated_today));
		},

		async init() {
			this.loading = true;
			const [schedRes, datesRes] = await Promise.all([
				bmApi.getSchedule(this.selectedDate),
				bmApi.getScheduleDates(),
			]);
			if (schedRes.ok)  this.plans          = schedRes.data.plans  || [];
			if (datesRes.ok)  this.availableDates  = datesRes.data.dates || [];
			this.loading = false;
		},

		async selectDate(date) {
			this.selectedDate = date;
			await this.loadSchedule();
		},

		async loadSchedule() {
			this.loading = true;
			this.error   = '';
			const { ok, data } = await bmApi.getSchedule(this.selectedDate);
			if (ok) {
				this.plans = data.plans || [];
			} else {
				this.error = 'Nie udało się pobrać planu dnia.';
			}
			this.loading = false;
		},
	};
};

// ── Alpine component: Reservations (Rezerwacje) ───────────────────────────────

/**
 * Breakdance usage example:
 *
 * <div x-data="bmReservations()" x-init="init()">
 *
 *   <!-- Resources list -->
 *   <template x-for="res in resources" :key="res.id">
 *     <div>
 *       <h3 x-text="res.name"></h3>
 *       <p x-text="res.available_from + ' – ' + res.available_to"></p>
 *       <button @click="openForm(res)">Zarezerwuj</button>
 *     </div>
 *   </template>
 *
 *   <!-- Reservation form (shown when selectedResource != null) -->
 *   <template x-if="selectedResource">
 *     <form @submit.prevent="submitReservation()">
 *       <p x-text="selectedResource.name"></p>
 *       <input type="date" x-model="form.res_date" @change="loadSlots()">
 *       <input type="time" x-model="form.start_time">
 *       <input type="time" x-model="form.end_time">
 *       <input type="text" x-model="form.purpose" placeholder="Cel rezerwacji">
 *       <!-- Taken slots info -->
 *       <template x-for="slot in takenSlots" :key="slot.start_time">
 *         <div x-text="slot.start_time + ' – ' + slot.end_time + ' (' + slot.status + ')'"></div>
 *       </template>
 *       <p x-show="formError" x-text="formError" style="color:red;"></p>
 *       <button type="submit" :disabled="loading">Zarezerwuj</button>
 *       <button type="button" @click="selectedResource = null">Anuluj</button>
 *     </form>
 *   </template>
 *
 *   <!-- My reservations -->
 *   <template x-for="r in myReservations" :key="r.id">
 *     <div>
 *       <span x-text="r.res_date + ' ' + r.start_time + '–' + r.end_time"></span>
 *       <span x-text="r.status_label"></span>
 *       <button x-show="r.status === 'pending'" @click="cancel(r.id)">Anuluj</button>
 *     </div>
 *   </template>
 * </div>
 */
window.bmReservations = function () {
	return {
		resources:        [],
		myReservations:   [],
		takenSlots:       [],
		selectedResource: null,
		form: {
			res_date:    '',
			start_time:  '',
			end_time:    '',
			purpose:     '',
		},
		loading:   false,
		formError: '',
		success:   '',

		async init() {
			this.loading = true;
			const [resRes, myRes] = await Promise.all([
				bmApi.getResources(),
				bmApi.getMyReservations(),
			]);
			if (resRes.ok) this.resources      = resRes.data.resources      || [];
			if (myRes.ok)  this.myReservations = myRes.data.reservations    || [];
			this.loading = false;
		},

		openForm(resource) {
			this.selectedResource = resource;
			this.form.res_date    = new Date().toISOString().slice(0, 10);
			this.form.start_time  = '';
			this.form.end_time    = '';
			this.form.purpose     = '';
			this.takenSlots       = [];
			this.formError        = '';
			this.success          = '';
			this.loadSlots();
		},

		async loadSlots() {
			if (!this.selectedResource || !this.form.res_date) return;
			const { ok, data } = await bmApi.getReservationSlots(this.selectedResource.id, this.form.res_date);
			if (ok) this.takenSlots = data.reserved_slots || [];
		},

		async submitReservation() {
			if (!this.selectedResource) return;
			this.loading   = true;
			this.formError = '';
			this.success   = '';

			const { ok, data } = await bmApi.createReservation({
				resource_id: this.selectedResource.id,
				res_date:    this.form.res_date,
				start_time:  this.form.start_time,
				end_time:    this.form.end_time,
				purpose:     this.form.purpose,
				nonce:       bmConfig.panelNonce,
			});

			this.loading = false;

			if (ok) {
				this.success          = 'Rezerwacja złożona – oczekuje na zatwierdzenie.';
				this.selectedResource = null;
				await this.init();
				window.dispatchEvent(new CustomEvent('bm:reservationCreated', { detail: data }));
			} else {
				this.formError = data.message || 'Błąd rezerwacji.';
			}
		},

		async cancel(id) {
			if (!confirm('Anulować rezerwację?')) return;
			this.loading = true;
			const { ok, data } = await bmApi.cancelReservation(id);
			this.loading = false;
			if (ok) {
				this.myReservations = this.myReservations.map(r =>
					r.id === id ? { ...r, status: 'cancelled', status_label: 'Anulowana' } : r
				);
				window.dispatchEvent(new CustomEvent('bm:reservationCancelled', { detail: { id } }));
			} else {
				alert(data.message || 'Nie można anulować.');
			}
		},
	};
};


// ── Alpine component: Menu (Jadłospis) ───────────────────────────────────────

window.bmMenu = function () {
return {
availableDates: [],
selectedDate:   '',
day:            null,
weekDays:       [],
viewMode:       'day',   // 'day' | 'week'
loading:        false,
error:          '',

async init() {
this.loading = true;
const today = new Date().toISOString().slice(0, 10);
this.selectedDate = today;
const [datesRes] = await Promise.all([bmApi.getMenuDates()]);
if (datesRes.ok) this.availableDates = datesRes.data.dates || [];
await this.loadDay(today);
this.loading = false;
},

async loadDay(date) {
this.loading = true;
this.error   = '';
const { ok, data } = await bmApi.getMenu(date);
if (ok) {
this.selectedDate = date;
this.day = data.day;
} else {
this.error = 'Nie udało się pobrać jadłospisu.';
}
this.loading = false;
},

async loadWeek(from) {
this.loading = true;
this.error   = '';
const { ok, data } = await bmApi.getMenuWeek(from);
if (ok) this.weekDays = data.days || [];
else this.error = 'Nie udało się pobrać jadłospisu tygodniowego.';
this.loading = false;
},

selectDate(date) {
this.loadDay(date);
},

setViewMode(mode) {
this.viewMode = mode;
if (mode === 'week') {
const today = new Date().toISOString().slice(0, 10);
this.loadWeek(today);
}
},

get mealTypeLabel() {
const labels = {
sniadanie: 'Śniadanie', drugie_sniadanie: 'Drugie śniadanie',
obiad: 'Obiad', podwieczorek: 'Podwieczorek',
kolacja: 'Kolacja', inne: 'Inne',
};
return (type) => labels[type] || type;
},

get hasChanges() {
if (!this.day) return false;
return this.day.items.some(i => i.is_new_today || i.is_updated_today);
},
};
};


// ── Alpine component: Conversations (Komunikacja) ────────────────────────────

window.bmConversations = function () {
return {
threads:         [],
currentThread:   null,
messages:        [],
view:            'list',  // 'list' | 'thread' | 'new'
form: {
subject:  '',
content:  '',
priority: 'normal',
},
replyContent:    '',
loading:         false,
error:           '',
success:         '',

async init() {
this.loading = true;
await this.loadThreads();
this.loading = false;
},

async loadThreads() {
const { ok, data } = await bmApi.getThreads();
if (ok) this.threads = data.threads || [];
},

async openThread(id) {
this.loading = true;
this.error   = '';
const { ok, data } = await bmApi.getThread(id);
if (ok) {
this.currentThread = data.thread;
this.messages      = data.messages || [];
this.view          = 'thread';
// Clear unread badge locally.
this.threads = this.threads.map(t =>
t.id === id ? { ...t, unread_camp: 0 } : t
);
} else {
this.error = 'Nie udało się załadować wątku.';
}
this.loading = false;
},

async createThread() {
if (!this.form.subject || !this.form.content) {
this.error = 'Podaj temat i treść wiadomości.';
return;
}
this.loading = true;
this.error   = '';
const { ok, data } = await bmApi.createThread(this.form);
this.loading = false;
if (ok) {
this.success = 'Wątek utworzony. Odpowiedź otrzymasz wkrótce.';
this.form = { subject: '', content: '', priority: 'normal' };
await this.loadThreads();
this.view = 'list';
} else {
this.error = data.message || 'Błąd tworzenia wątku.';
}
},

async sendReply() {
if (!this.replyContent || !this.currentThread) return;
this.loading = true;
this.error   = '';
const { ok, data } = await bmApi.replyThread(this.currentThread.id, { content: this.replyContent });
this.loading = false;
if (ok) {
this.replyContent = '';
// Reload thread messages.
const res = await bmApi.getThread(this.currentThread.id);
if (res.ok) this.messages = res.data.messages || [];
} else {
this.error = data.message || 'Nie udało się wysłać wiadomości.';
}
},

get unreadTotal() {
return this.threads.reduce((s, t) => s + (t.unread_camp || 0), 0);
},
};
};


// ── Alpine component: Help (Pomoc) ───────────────────────────────────────────

window.bmHelp = function () {
return {
articles:    [],
categories:  [],
types:       {},
current:     null,
search:      '',
filterType:  '',
filterCat:   '',
loading:     false,
error:       '',

async init() {
this.loading = true;
await this.load();
this.loading = false;
},

async load(params) {
const { ok, data } = await bmApi.getHelp(params || {});
if (ok) {
this.articles   = data.articles   || [];
this.categories = data.categories || [];
this.types      = data.types      || {};
} else {
this.error = 'Nie udało się załadować bazy pomocy.';
}
},

async applyFilters() {
const p = {};
if (this.search)     p.search   = this.search;
if (this.filterType) p.type     = this.filterType;
if (this.filterCat)  p.category = this.filterCat;
this.loading = true;
await this.load(Object.keys(p).length ? p : null);
this.loading = false;
},

async openArticle(id) {
this.loading = true;
const { ok, data } = await bmApi.getHelpArticle(id);
if (ok) this.current = data.article;
this.loading = false;
},

closeArticle() {
this.current = null;
},

get alarmArticles() {
return this.articles.filter(a => a.is_alarm);
},

get pinnedArticles() {
return this.articles.filter(a => a.is_pinned && !a.is_alarm);
},

get faqArticles() {
return this.articles.filter(a => a.type === 'faq');
},

get contactArticles() {
return this.articles.filter(a => a.type === 'contact');
},
};
};

// ── bmForms – available forms list + form fill-in ────────────────────────────

window.bmForms = function () {
return {
loading:       false,
error:         '',
forms:         [],
filterCategory:'',
currentForm:   null,
fields:        [],
formValues:    {},
submitting:    false,
submitted:     false,
submitResult:  null,

async init() {
this.loading = true;
const r = await bmApi.getForms();
this.loading = false;
if (r.ok) { this.forms = r.data.forms || []; }
else { this.error = r.data.error || 'Błąd ładowania formularzy.'; }
},

get filtered() {
if (!this.filterCategory) return this.forms;
return this.forms.filter(f => f.category === this.filterCategory);
},

get categories() {
const seen = new Set();
const cats = [];
for (const f of this.forms) {
if (f.category && !seen.has(f.category)) { seen.add(f.category); cats.push(f.category); }
}
return cats;
},

async openForm(id) {
this.loading = true;
this.currentForm = null;
this.fields = [];
this.formValues = {};
this.submitted = false;
this.submitResult = null;
const r = await bmApi.getForm(id);
this.loading = false;
if (r.ok) {
this.currentForm = r.data.form;
this.fields = r.data.fields || [];
for (const f of this.fields) {
this.formValues[f.field_key] = f.type === 'checkbox' ? [] : (f.default_value || '');
}
} else {
this.error = r.data.error || 'Błąd ładowania formularza.';
}
},

closeForm() {
this.currentForm = null;
this.fields = [];
this.formValues = {};
this.submitted = false;
this.submitResult = null;
},

async submit() {
this.submitting = true;
this.error = '';
const r = await bmApi.submitForm({ form_id: this.currentForm.id, data: this.formValues });
this.submitting = false;
if (r.ok) {
this.submitted = true;
this.submitResult = r.data;
} else if (r.status === 422) {
this.error = r.data.error || 'Sprawdź błędy w formularzu.';
this._fieldErrors = r.data.fields || {};
} else {
this.error = r.data.error || 'Błąd wysyłania zgłoszenia.';
}
},

fieldError(key) {
return (this._fieldErrors && this._fieldErrors[key]) ? this._fieldErrors[key] : '';
},

_fieldErrors: {},
};
};

// ── bmSubmissions – own submissions list + detail view ────────────────────────

window.bmSubmissions = function () {
return {
loading:      false,
error:        '',
submissions:  [],
filterStatus: '',
current:      null,
loadingDetail:false,

async init() {
this.loading = true;
await this.loadList();
this.loading = false;
},

async loadList() {
const params = this.filterStatus ? { status: this.filterStatus } : {};
const r = await bmApi.getSubmissions(Object.keys(params).length ? params : undefined);
if (r.ok) { this.submissions = r.data.submissions || []; }
else { this.error = r.data.error || 'Błąd ładowania zgłoszeń.'; }
},

async applyFilter() {
this.loading = true;
await this.loadList();
this.loading = false;
},

async openSubmission(id) {
this.loadingDetail = true;
this.current = null;
const r = await bmApi.getSubmission(id);
this.loadingDetail = false;
if (r.ok) {
this.current = r.data;
} else {
this.error = r.data.error || 'Błąd ładowania zgłoszenia.';
}
},

closeSubmission() {
this.current = null;
},

statusLabel(status) {
const map = {
new: 'Nowe', in_progress: 'W trakcie', waiting: 'Oczekuje',
closed: 'Zamknięte', cancelled: 'Anulowane',
};
return map[status] || status;
},

statusClass(status) {
const map = { new: 'new', in_progress: 'inprog', waiting: 'wait', closed: 'closed', cancelled: 'cancelled' };
return 'bm-status-' + (map[status] || 'default');
},

priorityLabel(priority) {
const map = { low: 'Niski', normal: 'Normalny', high: 'Wysoki', urgent: 'Pilny' };
return map[priority] || priority;
},
};
};
