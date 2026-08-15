/* global Alpine, bmApi */

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
if (ok) {
this.threads = data.threads || [];
if (typeof Alpine !== 'undefined') {
Alpine.store('bm').unreadCount = this.threads.reduce((s, t) => s + (t.unread_camp || 0), 0);
}
}
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


// ── bmFolderDocs – biblioteka dokumentów ─────────────────────────────────────

window.bmFolderDocs = function () {
	return {
		loading:   false,
		documents: [],
		error:     '',

		async init() {
			this.loading = true;
			const r = await bmApi.getFolderDocuments();
			this.loading = false;
			if (r.ok) {
				this.documents = r.data.documents || [];
			} else {
				this.error = r.data.message || 'Błąd ładowania dokumentów.';
			}
		},

		docIcon(type) {
			const map = { contract: '📄', regulation: '📜', info: 'ℹ️', form: '📝', document: '📄' };
			return map[type] || '📄';
		},
	};
};


// ── bmDamages – szkody obozu ─────────────────────────────────────────────────

window.bmDamages = function () {
	return {
		loading:  false,
		damages:  [],
		error:    '',
		success:  '',
		showForm: false,
		saving:   false,
		form: { name: '', description: '', cost: '' },

		async init() {
			await this.load();
		},

		async load() {
			this.loading = true;
			const r = await bmApi.getFolderDamages();
			this.loading = false;
			if (r.ok) {
				this.damages = r.data.damages || [];
			} else {
				this.error = r.data.message || 'Błąd ładowania szkód.';
			}
		},

		openForm() {
			this.form    = { name: '', description: '', cost: '' };
			this.error   = '';
			this.success = '';
			this.showForm = true;
		},

		async submit() {
			if (!this.form.name.trim()) { this.error = 'Podaj nazwę szkody.'; return; }
			this.saving = true;
			this.error  = '';
			const r = await bmApi.reportDamage({
				name:        this.form.name.trim(),
				description: this.form.description.trim(),
				cost:        parseFloat(this.form.cost) || 0,
				nonce:       bmConfig.panelNonce,
			});
			this.saving = false;
			if (r.ok) {
				this.showForm = false;
				this.success  = 'Szkoda zgłoszona pomyślnie.';
				await this.load();
			} else {
				this.error = r.data.message || 'Błąd zapisu.';
			}
		},

		statusLabel(s) {
			return { reported: 'Zgłoszona', acknowledged: 'Przyjęta', resolved: 'Rozwiązana' }[s] || s;
		},

		statusClass(s) {
			return { reported: 'zhp-badge-gold', acknowledged: 'zhp-badge-gray', resolved: 'zhp-badge-green' }[s] || 'zhp-badge-gray';
		},
	};
};


// ── bmDeclaration – deklaracja obozu ─────────────────────────────────────────

window.bmDeclaration = function () {
	return {
		loading:     false,
		declaration: null,
		days:        [],
		error:       '',
		editing:     null,
		saving:      false,

		async init() {
			await this.load();
		},

		async load() {
			this.loading = true;
			const r = await bmApi.getFolderDeclaration();
			this.loading = false;
			if (r.ok) {
				this.declaration = r.data.declaration;
				this.days        = r.data.days || [];
			} else {
				this.error = r.data.message || 'Błąd ładowania deklaracji.';
			}
		},

		editDay(day) {
			this.editing = {
				declaration_date: day.declaration_date,
				declared_persons: day.declared_persons,
				arrival_time:     day.arrival_time,
				departure_time:   day.departure_time,
			};
			this.error = '';
		},

		newDay() {
			this.editing = { declaration_date: '', declared_persons: 0, arrival_time: '', departure_time: '' };
			this.error = '';
		},

		cancelEdit() {
			this.editing = null;
		},

		async saveDay() {
			if (!this.editing.declaration_date) { this.error = 'Wybierz datę.'; return; }
			this.saving = true;
			this.error  = '';
			const r = await bmApi.saveDeclarationDay({
				declaration_date: this.editing.declaration_date,
				declared_persons: parseInt(this.editing.declared_persons) || 0,
				arrival_time:     this.editing.arrival_time || '',
				departure_time:   this.editing.departure_time || '',
				nonce:            bmConfig.panelNonce,
			});
			this.saving = false;
			if (r.ok) {
				this.editing = null;
				await this.load();
			} else {
				this.error = r.data.message || 'Błąd zapisu.';
			}
		},

		fmtDate(d) {
			if (!d) return '—';
			return new Date(d + 'T00:00:00').toLocaleDateString('pl-PL', { day: '2-digit', month: 'short', year: 'numeric' });
		},
	};
};


// ── bmCampDocuments – dokumenty przypisane do obozu ───────────────────────────

window.bmCampDocuments = function () {
	return {
		loading:   false,
		documents: [],
		error:     '',

		async init() {
			this.loading = true;
			const r = await bmApi.getCampDocuments();
			this.loading = false;
			if (r.ok) {
				this.documents = r.data.camp_documents || [];
			} else {
				this.error = r.data.message || 'Błąd ładowania dokumentów.';
			}
		},

		statusLabel(s) {
			return { draft: 'Roboczy', active: 'Aktywny', sent: 'Wysłany', signed: 'Podpisany', archived: 'Archiwum' }[s] || s;
		},

		statusClass(s) {
			return { draft: 'zhp-badge-gray', active: 'zhp-badge-green', sent: 'zhp-badge-blue', signed: 'zhp-badge-green', archived: 'zhp-badge-gray' }[s] || 'zhp-badge-gray';
		},

		docIcon(type) {
			const map = { contract: '📄', regulation: '📜', info: 'ℹ️', form: '📝', document: '📄' };
			return map[type] || '📄';
		},
	};
};


// ── bmDeclDocs – deklaracje organizacji przesłane do obozu ───────────────────

window.bmDeclDocs = function () {
	return {
		loading:  false,
		docs:     [],
		error:    '',
		success:  '',
		approving: 0,

		async init() {
			await this.load();
		},

		async load() {
			this.loading = true;
			const r = await bmApi.getDeclDocs();
			this.loading = false;
			if (r.ok) {
				this.docs = r.data.decl_docs || [];
			} else {
				this.error = r.data.message || 'Błąd ładowania deklaracji.';
			}
		},

		async approve(id) {
			if (!confirm('Zatwierdzić deklarację? Działanie jest nieodwracalne.')) return;
			this.approving = id;
			this.error   = '';
			this.success = '';
			const r = await bmApi.approveDeclDoc(id, bmConfig.panelNonce);
			this.approving = 0;
			if (r.ok) {
				this.success = 'Deklaracja zatwierdzona.';
				await this.load();
			} else {
				this.error = r.data.message || 'Błąd zatwierdzenia.';
			}
		},

		statusLabel(d) {
			if (d.camp_approved_at) return 'Zatwierdzona przez obóz';
			if (d.approved_at)      return 'Zatwierdzona przez org.';
			if (d.signed_at)        return 'Podpisana';
			return 'Oczekuje';
		},

		statusClass(d) {
			if (d.camp_approved_at) return 'zhp-badge-green';
			if (d.approved_at)      return 'zhp-badge-blue';
			if (d.signed_at)        return 'zhp-badge-gold';
			return 'zhp-badge-gray';
		},
	};
};


// ── bmEquipment – sprzęt obozu ────────────────────────────────────────────────

window.bmEquipment = function () {
	return {
		loading:  false,
		items:    [],
		error:    '',
		success:  '',
		showForm: false,
		saving:   false,
		form: { equipment_type: '', name: '', issued_qty: 1, notes: '' },

		async init() {
			await this.load();
		},

		async load() {
			this.loading = true;
			const r = await bmApi.getEquipment();
			this.loading = false;
			if (r.ok) {
				this.items = r.data.equipment || [];
			} else {
				this.error = r.data.message || 'Błąd ładowania sprzętu.';
			}
		},

		openForm() {
			this.form     = { equipment_type: '', name: '', issued_qty: 1, notes: '' };
			this.error    = '';
			this.success  = '';
			this.showForm = true;
		},

		async submit() {
			if (!this.form.name.trim()) { this.error = 'Podaj nazwę sprzętu.'; return; }
			if (this.form.issued_qty < 1) { this.error = 'Ilość musi być ≥ 1.'; return; }
			this.saving = true;
			this.error  = '';
			const r = await bmApi.issueEquipment({
				name:           this.form.name.trim(),
				equipment_type: this.form.equipment_type.trim(),
				issued_qty:     parseInt(this.form.issued_qty) || 1,
				notes:          this.form.notes.trim(),
				nonce:          bmConfig.panelNonce,
			});
			this.saving = false;
			if (r.ok) {
				this.showForm = false;
				this.success  = 'Sprzęt zapisany.';
				await this.load();
			} else {
				this.error = r.data.message || 'Błąd zapisu.';
			}
		},

		async registerReturn(id) {
			const qty = parseInt(prompt('Ile sztuk zwrócono?', '1'));
			if (!qty || qty < 1) return;
			this.error   = '';
			this.success = '';
			const r = await bmApi.returnEquipment(id, qty, bmConfig.panelNonce);
			if (r.ok) {
				this.success = `Zarejestrowano zwrot: ${r.data.returned_qty} / ${this.items.find(i => i.id === id)?.issued_qty ?? '?'} szt.`;
				await this.load();
			} else {
				this.error = r.data.message || 'Błąd rejestracji zwrotu.';
			}
		},

		get totalOutstanding() {
			return this.items.reduce((s, i) => s + i.outstanding, 0);
		},
	};
};
