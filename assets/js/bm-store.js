/* global bmConfig, Alpine */

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
unreadCount:    0,
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
Alpine.data('bmFolderDocs',    window.bmFolderDocs);
Alpine.data('bmDamages',       window.bmDamages);
Alpine.data('bmDeclaration',   window.bmDeclaration);
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
