/* global Alpine, bmApi */

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
			// Use server-injected list when available (works without WP login).
			if (Array.isArray(bmConfig.activeCamps) && bmConfig.activeCamps.length) {
				this.camps = bmConfig.activeCamps;
				return;
			}
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
				// Redirect if the shortcode supplied a redirect_url attribute.
				const redirectUrl = this.$el.dataset.bmRedirect;
				if (redirectUrl) {
					window.location.href = redirectUrl;
				}
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
