/* global Alpine, bmApi */

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
		blockWindows:     [],
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
			this.blockWindows     = [];
			this.formError        = '';
			this.success          = '';
			this.loadSlots();
		},

		async loadSlots() {
			if (!this.selectedResource || !this.form.res_date) return;
			const { ok, data } = await bmApi.getReservationSlots(this.selectedResource.id, this.form.res_date);
			if (ok) {
				this.takenSlots   = data.reserved_slots || [];
				this.blockWindows = data.block_windows  || [];
			}
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
