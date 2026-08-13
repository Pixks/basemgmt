# 15 – Gotowy panel użytkownika – jeden blok do Breakdance

Wklej **cały poniższy kod** jako jeden `Code Block` w Breakdance Studio.  
Plugin automatycznie ładuje Alpine.js, `bmConfig` i wszystkie komponenty – nie musisz dodawać nic więcej.

> Jedyne co musisz mieć globalnie to `[x-cloak] { display: none !important; }` w CSS strony (możesz dodać w Breakdance → Global CSS).

**Zmiany względem poprzedniej wersji:**
- Zakładka Meldunek korzysta z nowego `ReportsController` (`/panel/reports/*`) – obsługuje tryby roboczy i wysłany
- Formularz rezerwacji pokazuje blokady techniczne (`block_windows`)
- Pogoda pobierana z `/panel/weather` (wymaga sesji)
- Naprawiono: `campName` w topbarze, licznik nieprzeczytanych wiadomości, lookup nazwy zasobu w rezerwacjach
- Usunięto `role` z listy kadry w ekranie logowania (API nie zwraca tego pola)

---

```html
<!-- ============================================================
     CAMPLINK – Pełny panel frontendowy
     Styl: ZHP | Alpine.js + REST API plugin
     ============================================================ -->

<style>
[x-cloak] { display: none !important; }

:root {
  --zhp-green:        #1B5E33;
  --zhp-green-mid:    #2A7A4B;
  --zhp-green-light:  #EBF5EE;
  --zhp-green-border: #A8D5B5;
  --zhp-red:          #C0392B;
  --zhp-red-light:    #FDECEA;
  --zhp-gold:         #D4A017;
  --zhp-gold-light:   #FEF8E7;
  --zhp-text:         #1A2530;
  --zhp-text-mid:     #4B5A67;
  --zhp-text-muted:   #8A96A1;
  --zhp-bg:           #F6F8FA;
  --zhp-white:        #FFFFFF;
  --zhp-border:       #D0D8DC;
  --zhp-shadow:       0 2px 8px rgba(27,94,51,.12);
  --zhp-font:         'Lato', 'Open Sans', system-ui, sans-serif;
  --zhp-radius:       8px;
  --zhp-radius-sm:    4px;
  --zhp-radius-pill:  9999px;
}
.bm-panel *, .bm-login * { box-sizing: border-box; }
.bm-panel, .bm-login {
  font-family: var(--zhp-font);
  color: var(--zhp-text);
  background: var(--zhp-bg);
  font-size: 15px;
  line-height: 1.55;
}
.zhp-card { background: var(--zhp-white); border: 1px solid var(--zhp-border); border-radius: var(--zhp-radius); box-shadow: var(--zhp-shadow); overflow: hidden; margin-bottom: 16px; }
.zhp-card-header { background: var(--zhp-green); color: #fff; padding: 13px 20px; display: flex; align-items: center; gap: 10px; }
.zhp-card-header h3 { margin: 0; font-size: 1rem; font-weight: 700; color: #fff; }
.zhp-card-body { padding: 18px 20px; }
.zhp-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: var(--zhp-radius-pill); border: none; font-family: var(--zhp-font); font-size: .88rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: background .15s, transform .1s; }
.zhp-btn:active { transform: scale(.97); }
.zhp-btn:disabled { opacity: .5; cursor: not-allowed; }
.zhp-btn-primary { background: var(--zhp-green); color: #fff; }
.zhp-btn-primary:hover { background: var(--zhp-green-mid); }
.zhp-btn-danger { background: var(--zhp-red); color: #fff; }
.zhp-btn-danger:hover { background: #a93226; }
.zhp-btn-ghost { background: transparent; color: var(--zhp-green); border: 1.5px solid var(--zhp-green); }
.zhp-btn-ghost:hover { background: var(--zhp-green-light); }
.zhp-btn-white { background: rgba(255,255,255,.18); color: #fff; border: 1px solid rgba(255,255,255,.35); }
.zhp-btn-white:hover { background: rgba(255,255,255,.28); }
.zhp-btn-sm { padding: 5px 13px; font-size: .8rem; }
.zhp-field { margin-bottom: 13px; }
.zhp-label { display: block; font-size: .78rem; font-weight: 700; color: var(--zhp-text-mid); margin-bottom: 4px; text-transform: uppercase; letter-spacing: .04em; }
.zhp-input, .zhp-select, .zhp-textarea { width: 100%; padding: 9px 13px; border: 1.5px solid var(--zhp-border); border-radius: var(--zhp-radius-sm); font-family: var(--zhp-font); font-size: .93rem; color: var(--zhp-text); background: var(--zhp-white); transition: border-color .15s, box-shadow .15s; outline: none; }
.zhp-input:focus, .zhp-select:focus, .zhp-textarea:focus { border-color: var(--zhp-green); box-shadow: 0 0 0 3px rgba(27,94,51,.13); }
.zhp-textarea { resize: vertical; min-height: 80px; }
.zhp-badge { display: inline-flex; align-items: center; padding: 2px 9px; border-radius: var(--zhp-radius-pill); font-size: .7rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; }
.zhp-badge-green { background: var(--zhp-green-light); color: var(--zhp-green); }
.zhp-badge-red   { background: var(--zhp-red-light);   color: var(--zhp-red); }
.zhp-badge-gold  { background: var(--zhp-gold-light);  color: #7A5800; }
.zhp-badge-gray  { background: #EAECEE; color: var(--zhp-text-mid); }
.zhp-badge-blue  { background: #EBF4FF; color: #1A5494; }
.zhp-alert { padding: 11px 15px; border-radius: var(--zhp-radius-sm); font-size: .88rem; margin-bottom: 11px; display: flex; align-items: flex-start; gap: 9px; }
.zhp-alert-urgent { background: var(--zhp-red-light);  border-left: 4px solid var(--zhp-red);  color: var(--zhp-red); }
.zhp-alert-warn   { background: var(--zhp-gold-light); border-left: 4px solid var(--zhp-gold); color: #7A5800; }
.zhp-alert-ok     { background: var(--zhp-green-light);border-left: 4px solid var(--zhp-green);color: var(--zhp-green); }
.zhp-alert-info   { background: #EBF4FF; border-left: 4px solid #1A5494; color: #1A5494; }
.zhp-table { width: 100%; border-collapse: collapse; font-size: .86rem; }
.zhp-table th { background: var(--zhp-green-light); color: var(--zhp-green); text-align: left; padding: 7px 11px; font-size: .73rem; text-transform: uppercase; letter-spacing: .04em; }
.zhp-table td { padding: 8px 11px; border-bottom: 1px solid var(--zhp-border); }
.zhp-table tr:last-child td { border-bottom: none; }
.zhp-table tr:hover td { background: var(--zhp-green-light); }
.zhp-loader { display: flex; align-items: center; gap: 8px; color: var(--zhp-text-muted); font-size: .88rem; padding: 14px 0; }
.zhp-spinner { width: 16px; height: 16px; border: 2px solid var(--zhp-green-border); border-top-color: var(--zhp-green); border-radius: 50%; animation: zhp-spin .65s linear infinite; flex-shrink: 0; }
@keyframes zhp-spin { to { transform: rotate(360deg); } }
.zhp-divider { border: none; border-top: 1px solid var(--zhp-border); margin: 14px 0; }
.zhp-timeline { border-left: 3px solid var(--zhp-green-border); padding-left: 18px; }
.zhp-timeline-item { position: relative; padding: 9px 13px; background: var(--zhp-white); border: 1px solid var(--zhp-border); border-radius: var(--zhp-radius-sm); margin-bottom: 7px; }
.zhp-timeline-item::before { content: ''; position: absolute; left: -24px; top: 14px; width: 9px; height: 9px; border-radius: 50%; background: var(--zhp-green); border: 2px solid var(--zhp-bg); }
.zhp-time-label { font-size: .8rem; font-weight: 700; color: var(--zhp-green); margin-bottom: 2px; }
.zhp-meal-section { margin-bottom: 18px; }
.zhp-meal-title { font-size: .7rem; font-weight: 700; color: var(--zhp-text-muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
.zhp-meal-title::after { content: ''; flex: 1; height: 1px; background: var(--zhp-border); }
.zhp-meal-item { padding: 9px 13px; background: var(--zhp-white); border: 1px solid var(--zhp-border); border-radius: var(--zhp-radius-sm); margin-bottom: 4px; }
.zhp-msg-mine  { background: var(--zhp-green-light); border-radius: 12px 12px 4px 12px; padding: 9px 13px; margin: 5px 0 5px 36px; }
.zhp-msg-admin { background: #EBF4FF; border-radius: 12px 12px 12px 4px; padding: 9px 13px; margin: 5px 36px 5px 0; }

/* Nawigacja główna */
.bm-nav { background: var(--zhp-white); border-bottom: 2px solid var(--zhp-border); overflow-x: auto; white-space: nowrap; }
.bm-nav-inner { display: inline-flex; min-width: 100%; padding: 0 8px; }
.bm-tab { padding: 12px 16px; border: none; background: none; font-family: var(--zhp-font); font-size: .85rem; font-weight: 600; color: var(--zhp-text-muted); cursor: pointer; border-bottom: 2.5px solid transparent; margin-bottom: -2px; transition: color .14s, border-color .14s; white-space: nowrap; position: relative; }
.bm-tab:hover { color: var(--zhp-green); }
.bm-tab.bm-active { color: var(--zhp-green); border-bottom-color: var(--zhp-green); }
.bm-tab-badge { position: absolute; top: 6px; right: 4px; background: var(--zhp-red); color: #fff; border-radius: 9999px; font-size: .62rem; font-weight: 700; min-width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; }

/* Topbar */
.bm-topbar { background: var(--zhp-white); border-bottom: 1px solid var(--zhp-border); padding: 9px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }

/* Layout sekcji */
.bm-section { padding: 16px; max-width: 920px; margin: 0 auto; }
.bm-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 640px) { .bm-grid-2 { grid-template-columns: 1fr; } }
.bm-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
@media (max-width: 600px) { .bm-grid-3 { grid-template-columns: 1fr; } }

/* Camp hero */
.zhp-camp-hero { background: linear-gradient(135deg, var(--zhp-green) 0%, var(--zhp-green-mid) 100%); color: #fff; padding: 22px 26px; border-radius: var(--zhp-radius); position: relative; overflow: hidden; margin-bottom: 16px; }
.zhp-camp-hero::before { content: '⚜'; position: absolute; right: 18px; top: 12px; font-size: 3rem; opacity: .11; pointer-events: none; }
.zhp-status-bar { display: flex; flex-wrap: wrap; gap: 12px; padding: 12px 20px; background: var(--zhp-white); border: 1px solid var(--zhp-border); border-top: none; border-radius: 0 0 var(--zhp-radius) var(--zhp-radius); margin-bottom: 16px; }
.zhp-stat-item { text-align: center; min-width: 60px; }
.zhp-stat-val { font-size: 1.35rem; font-weight: 700; color: var(--zhp-green); display: block; }
.zhp-stat-lbl { font-size: .68rem; color: var(--zhp-text-muted); text-transform: uppercase; letter-spacing: .04em; }
.zhp-stat-sep { width: 1px; background: var(--zhp-border); }

/* Weather */
.zhp-weather-card { display: flex; align-items: center; gap: 18px; padding: 16px 20px; background: linear-gradient(135deg, #EBF5EE 0%, #D6EDE0 100%); border: 1px solid var(--zhp-green-border); border-radius: var(--zhp-radius); margin-bottom: 11px; }
.zhp-weather-temp { font-size: 2.6rem; font-weight: 700; color: var(--zhp-green); line-height: 1; }
.zhp-forecast-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; }
.zhp-forecast-day { text-align: center; padding: 7px 4px; background: var(--zhp-white); border: 1px solid var(--zhp-border); border-radius: var(--zhp-radius-sm); font-size: .76rem; }
</style>

<!-- ======================================================== -->
<!-- GŁÓWNY WRAPPER                                            -->
<!-- ======================================================== -->

<div class="bm-panel" x-data="{ tab: 'panel' }" x-cloak>

  <!-- ═══════════════════════════════════════════════════════ -->
  <!-- EKRAN LOGOWANIA                                         -->
  <!-- ═══════════════════════════════════════════════════════ -->

  <div x-show="!$store.bm.authenticated" x-transition.opacity>
    <div x-data="bmLogin()" x-init="init()"
         style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--zhp-green);padding:20px;">
      <div style="width:100%;max-width:420px;">

        <div style="text-align:center;margin-bottom:28px;">
          <div style="font-size:3.2rem;color:#fff;opacity:.85;margin-bottom:6px;">⚜</div>
          <h1 style="color:#fff;font-family:var(--zhp-font);font-size:1.45rem;font-weight:700;margin:0;">
            Panel Kadry Obozowej
          </h1>
          <p style="color:rgba(255,255,255,.65);font-size:.85rem;margin-top:5px;">
            Zaloguj się, aby uzyskać dostęp do swojego obozu
          </p>
        </div>

        <div class="zhp-card">
          <div class="zhp-card-body">

            <div class="zhp-field">
              <label class="zhp-label">Krok 1 – Wybierz obóz</label>
              <select class="zhp-select" x-model="campId" @change="loadStaff()">
                <option value="">— wybierz obóz —</option>
                <template x-for="c in camps" :key="c.id">
                  <option :value="c.id" x-text="c.name"></option>
                </template>
              </select>
            </div>

            <div class="zhp-field" x-show="campId && staffList.length" x-transition>
              <label class="zhp-label">Krok 2 – Wybierz siebie</label>
              <select class="zhp-select" x-model="staffId">
                <option value="">— wybierz osobę —</option>
                <template x-for="s in staffList" :key="s.id">
                  <option :value="s.id" x-text="s.display_name"></option>
                </template>
              </select>
            </div>

            <div class="zhp-field" x-show="staffId" x-transition>
              <label class="zhp-label">Krok 3 – Kod bezpieczeństwa (6 cyfr)</label>
              <input type="password" class="zhp-input"
                x-model="code" inputmode="numeric" maxlength="6" placeholder="●●●●●●"
                @keydown.enter="submit()"
                style="letter-spacing:.35em;font-size:1.2rem;text-align:center;">
            </div>

            <div class="zhp-alert zhp-alert-urgent" x-show="error" x-transition>
              ⚠ <span x-text="error"></span>
            </div>

            <button class="zhp-btn zhp-btn-primary"
              style="width:100%;justify-content:center;font-size:1rem;padding:12px;"
              @click="submit()"
              :disabled="loading || !campId || !staffId || !code">
              <span x-show="loading" class="zhp-spinner"></span>
              <span x-text="loading ? 'Logowanie…' : 'Zaloguj się →'"></span>
            </button>

          </div>
        </div>

        <p style="text-align:center;color:rgba(255,255,255,.4);font-size:.72rem;margin-top:14px;">
          System Bazy Obozowej · ZHP
        </p>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════ -->
  <!-- PANEL GŁÓWNY (po zalogowaniu)                          -->
  <!-- ═══════════════════════════════════════════════════════ -->

  <div x-show="$store.bm.authenticated" x-transition.opacity>

    <!-- TOPBAR -->
    <div class="bm-topbar" x-data="bmLogout()">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--zhp-green-light);border:2px solid var(--zhp-green);display:flex;align-items:center;justify-content:center;font-size:1rem;color:var(--zhp-green);">
          ⚜
        </div>
        <div>
          <div style="font-weight:700;font-size:.9rem;" x-text="$store.bm.displayName || 'Zalogowany'"></div>
          <div style="font-size:.72rem;color:var(--zhp-text-muted);" x-text="$store.bm.camp?.name || ''"></div>
        </div>
      </div>
      <button class="zhp-btn zhp-btn-ghost zhp-btn-sm" @click="logout()">Wyloguj się ↩</button>
    </div>

    <!-- NAWIGACJA ZAKŁADEK -->
    <div class="bm-nav">
      <div class="bm-nav-inner">
        <button class="bm-tab" :class="tab==='panel' && 'bm-active'" @click="tab='panel'">📊 Panel</button>
        <button class="bm-tab" :class="tab==='ogloszenia' && 'bm-active'" @click="tab='ogloszenia'">📢 Ogłoszenia</button>
        <button class="bm-tab" :class="tab==='meldunek' && 'bm-active'" @click="tab='meldunek'">📋 Meldunek</button>
        <button class="bm-tab" :class="tab==='plan' && 'bm-active'" @click="tab='plan'">🗓 Plan dnia</button>
        <button class="bm-tab" :class="tab==='jadlospis' && 'bm-active'" @click="tab='jadlospis'">🍽 Jadłospis</button>
        <button class="bm-tab" :class="tab==='rezerwacje' && 'bm-active'" @click="tab='rezerwacje'">🏕 Rezerwacje</button>
        <button class="bm-tab" :class="tab==='komunikacja' && 'bm-active'" @click="tab='komunikacja'"
          style="position:relative;">
          💬 Wiadomości
          <span x-data class="bm-tab-badge"
            x-show="$store.bm.unreadCount > 0"
            x-text="$store.bm.unreadCount">
          </span>
        </button>
        <button class="bm-tab" :class="tab==='pomoc' && 'bm-active'" @click="tab='pomoc'">📚 Pomoc</button>
        <button class="bm-tab" :class="tab==='formularze' && 'bm-active'" @click="tab='formularze'">📝 Formularze</button>
      </div>
    </div>

    <!-- ─────────────────────────────────────────────────── -->
    <!-- ZAKŁADKA: PANEL GŁÓWNY                              -->
    <!-- ─────────────────────────────────────────────────── -->
    <div x-show="tab==='panel'" class="bm-section">

      <!-- Hero obozu -->
      <div x-data="bmCamp()" x-init="init()">
        <div class="zhp-loader" x-show="!camp"><div class="zhp-spinner"></div> Ładowanie danych obozu…</div>
        <template x-if="camp">
          <div>
            <div class="zhp-camp-hero" style="border-radius:var(--zhp-radius) var(--zhp-radius) 0 0;margin-bottom:0;">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
                <div>
                  <div style="font-size:.72rem;opacity:.7;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">⚜ Obóz harcerski</div>
                  <h2 x-text="camp.name" style="font-size:1.55rem;font-weight:700;color:#fff;margin:0 0 6px;"></h2>
                  <div style="font-size:.83rem;opacity:.82;">
                    📅 <span x-text="camp.start_date"></span> – <span x-text="camp.end_date"></span>
                  </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end;">
                  <span x-show="submittedToday"
                    style="background:rgba(255,255,255,.2);color:#fff;padding:5px 13px;border-radius:var(--zhp-radius-pill);font-size:.78rem;font-weight:600;">
                    ✓ Meldunek złożony
                  </span>
                  <span x-show="!submittedToday"
                    style="background:var(--zhp-gold);color:#fff;padding:5px 13px;border-radius:var(--zhp-radius-pill);font-size:.78rem;font-weight:600;">
                    ⚠ Brak meldunku
                  </span>
                </div>
              </div>
            </div>
            <template x-if="latestCount">
              <div class="zhp-status-bar">
                <div class="zhp-stat-item">
                  <span class="zhp-stat-val" x-text="latestCount.participants ?? 0"></span>
                  <span class="zhp-stat-lbl">Uczestnicy</span>
                </div>
                <div class="zhp-stat-sep"></div>
                <div class="zhp-stat-item">
                  <span class="zhp-stat-val" x-text="latestCount.staff ?? 0"></span>
                  <span class="zhp-stat-lbl">Kadra</span>
                </div>
                <div class="zhp-stat-sep"></div>
                <div class="zhp-stat-item">
                  <span class="zhp-stat-val" x-text="latestCount.workers ?? 0"></span>
                  <span class="zhp-stat-lbl">Pracownicy</span>
                </div>
                <div class="zhp-stat-sep"></div>
                <div class="zhp-stat-item">
                  <span class="zhp-stat-val" x-text="latestCount.total ?? 0"></span>
                  <span class="zhp-stat-lbl">Łącznie</span>
                </div>
              </div>
            </template>
          </div>
        </template>
      </div>

      <div class="bm-grid-2">
        <!-- Ogłoszenia (skrócone) -->
        <div x-data="bmAnnouncements()">
          <div class="zhp-card">
            <div class="zhp-card-header" style="justify-content:space-between;">
              <h3>📢 Ogłoszenia</h3>
              <button class="zhp-btn zhp-btn-white zhp-btn-sm" @click="refresh()">↻</button>
            </div>
            <div class="zhp-card-body" style="padding:14px;">
              <template x-for="ann in active.filter(a => a.is_urgent)" :key="ann.id">
                <div class="zhp-alert zhp-alert-urgent" style="flex-direction:column;align-items:flex-start;">
                  <strong>🚨 <span x-text="ann.title"></span></strong>
                  <div x-html="ann.content" style="font-size:.85rem;margin-top:4px;"></div>
                </div>
              </template>
              <template x-for="ann in active.filter(a => !a.is_urgent).slice(0,3)" :key="ann.id">
                <div style="padding:10px;border:1px solid var(--zhp-border);border-radius:var(--zhp-radius-sm);margin-bottom:7px;">
                  <div style="display:flex;justify-content:space-between;gap:8px;">
                    <strong style="font-size:.9rem;" x-text="ann.title"></strong>
                    <span class="zhp-badge zhp-badge-green" style="font-size:.65rem;">aktywne</span>
                  </div>
                  <div x-html="ann.content" style="margin-top:6px;font-size:.83rem;color:var(--zhp-text-mid);"></div>
                </div>
              </template>
              <p x-show="!active.length" style="color:var(--zhp-text-muted);text-align:center;padding:12px;">Brak ogłoszeń.</p>
              <button class="zhp-btn zhp-btn-ghost zhp-btn-sm" style="width:100%;justify-content:center;margin-top:4px;"
                @click="$dispatch('bm-tab', 'ogloszenia')">
                Wszystkie ogłoszenia →
              </button>
            </div>
          </div>
        </div>

        <!-- Pogoda -->
        <div x-data="bmWeather()" x-init="init()">
          <div class="zhp-card">
            <div class="zhp-card-header"><h3>🌤 Pogoda</h3></div>
            <div class="zhp-card-body" style="padding:14px;">
              <div class="zhp-loader" x-show="loading"><div class="zhp-spinner"></div> Ładowanie…</div>
              <div class="zhp-alert zhp-alert-warn" x-show="!configured && !loading" style="font-size:.82rem;">
                ⚠ Lokalizacja nie skonfigurowana.
              </div>
              <template x-if="current">
                <div class="zhp-weather-card" style="margin-bottom:10px;">
                  <div style="font-size:3rem;line-height:1;" x-text="current.icon"></div>
                  <div>
                    <div class="zhp-weather-temp" x-text="current.temperature + '°C'"></div>
                    <div style="font-weight:600;font-size:.9rem;" x-text="current.label"></div>
                    <div style="font-size:.78rem;color:var(--zhp-text-mid);margin-top:3px;">
                      💨 <span x-text="current.windspeed + ' km/h'"></span>
                      &nbsp;|&nbsp;
                      💧 <span x-text="current.humidity + '%'"></span>
                    </div>
                  </div>
                </div>
              </template>
              <div class="zhp-forecast-grid">
                <template x-for="day in forecast" :key="day.date">
                  <div class="zhp-forecast-day">
                    <div style="color:var(--zhp-text-muted);margin-bottom:3px;" x-text="day.date.slice(5)"></div>
                    <div style="font-size:1.3rem;" x-text="day.icon"></div>
                    <div style="font-weight:700;color:var(--zhp-green);" x-text="day.temp_max + '°'"></div>
                    <div style="color:var(--zhp-text-muted);" x-text="day.temp_min + '°'"></div>
                  </div>
                </template>
              </div>
              <template x-for="alert in alerts" :key="alert.id">
                <div :class="alert.is_urgent ? 'zhp-alert zhp-alert-urgent' : 'zhp-alert zhp-alert-warn'"
                     style="margin-top:8px;flex-direction:column;align-items:flex-start;">
                  <strong x-text="(alert.is_urgent ? '🚨 ' : '⚠️ ') + alert.title"></strong>
                  <p x-text="alert.message" style="margin:3px 0 0;font-size:.82rem;"></p>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ─────────────────────────────────────────────────── -->
    <!-- ZAKŁADKA: OGŁOSZENIA                                -->
    <!-- ─────────────────────────────────────────────────── -->
    <div x-show="tab==='ogloszenia'" class="bm-section">
      <div x-data="bmAnnouncements()">
        <div class="zhp-card">
          <div class="zhp-card-header" style="justify-content:space-between;">
            <h3>📢 Ogłoszenia</h3>
            <button class="zhp-btn zhp-btn-white zhp-btn-sm" @click="refresh()">↻ Odśwież</button>
          </div>
          <div class="zhp-card-body" style="padding:14px;">
            <template x-for="ann in active.filter(a => a.is_urgent)" :key="ann.id">
              <div class="zhp-alert zhp-alert-urgent" style="flex-direction:column;align-items:flex-start;margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;width:100%;margin-bottom:4px;">
                  <strong>🚨 <span x-text="ann.title"></span></strong>
                  <span class="zhp-badge zhp-badge-red">PILNE</span>
                </div>
                <div x-html="ann.content" style="font-size:.88rem;color:var(--zhp-text);"></div>
                <div style="display:flex;justify-content:space-between;width:100%;margin-top:6px;font-size:.76rem;">
                  <span>Ważne do: <strong x-text="ann.valid_until || '—'"></strong></span>
                  <a x-show="ann.attachment_url" :href="ann.attachment_url" target="_blank"
                     style="color:var(--zhp-red);font-weight:600;">📎 Załącznik</a>
                </div>
              </div>
            </template>
            <template x-for="ann in active.filter(a => !a.is_urgent)" :key="ann.id">
              <div style="padding:13px;border:1px solid var(--zhp-border);border-radius:var(--zhp-radius-sm);margin-bottom:8px;background:var(--zhp-white);">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                  <strong style="font-size:.93rem;" x-text="ann.title"></strong>
                  <span class="zhp-badge zhp-badge-green">Aktywne</span>
                </div>
                <div x-html="ann.content" style="margin-top:7px;font-size:.86rem;color:var(--zhp-text-mid);"></div>
                <div style="display:flex;justify-content:space-between;margin-top:7px;font-size:.76rem;color:var(--zhp-text-muted);">
                  <span>Do: <span x-text="ann.valid_until || '—'"></span></span>
                  <a x-show="ann.attachment_url" :href="ann.attachment_url" target="_blank"
                     style="color:var(--zhp-green);font-weight:600;">📎 Załącznik</a>
                </div>
              </div>
            </template>
            <p x-show="!active.length" style="color:var(--zhp-text-muted);text-align:center;padding:20px;">
              <span style="font-size:1.8rem;display:block;margin-bottom:6px;">📭</span>
              Brak aktywnych ogłoszeń.
            </p>
          </div>
        </div>

        <!-- Formularz nowego ogłoszenia -->
        <div class="zhp-card" x-data="bmAnnForm()">
          <div class="zhp-card-header"><h3>✏ Zgłoś ogłoszenie</h3></div>
          <form class="zhp-card-body" @submit.prevent="submit()">
            <div class="bm-grid-2" style="gap:12px;">
              <div class="zhp-field">
                <label class="zhp-label">Tytuł *</label>
                <input type="text" class="zhp-input" x-model="title" required placeholder="Krótki tytuł">
              </div>
              <div class="zhp-field">
                <label class="zhp-label">URL załącznika</label>
                <input type="url" class="zhp-input" x-model="attachment_url" placeholder="https://…">
              </div>
            </div>
            <div class="zhp-field">
              <label class="zhp-label">Treść</label>
              <textarea class="zhp-textarea" x-model="content" rows="3" placeholder="Treść ogłoszenia…"></textarea>
            </div>
            <div class="bm-grid-2" style="gap:12px;">
              <div class="zhp-field">
                <label class="zhp-label">Ważne od *</label>
                <input type="date" class="zhp-input" x-model="valid_from" required>
              </div>
              <div class="zhp-field">
                <label class="zhp-label">Ważne do *</label>
                <input type="date" class="zhp-input" x-model="valid_until" required>
              </div>
            </div>
            <div class="zhp-alert zhp-alert-ok"     x-show="success" x-text="success" x-transition></div>
            <div class="zhp-alert zhp-alert-urgent"  x-show="error"  x-text="error"   x-transition></div>
            <button type="submit" class="zhp-btn zhp-btn-primary" :disabled="loading"
              style="width:100%;justify-content:center;">
              <span x-show="loading" class="zhp-spinner"></span>
              <span x-text="loading ? 'Wysyłanie…' : 'Wyślij do zatwierdzenia'"></span>
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- ─────────────────────────────────────────────────── -->
    <!-- ZAKŁADKA: MELDUNEK                                   -->
    <!-- ─────────────────────────────────────────────────── -->
    <div x-show="tab==='meldunek'" class="bm-section">
      <div x-data="bmReports()" x-init="init()">
        <div class="zhp-card">
          <div class="zhp-card-header" style="justify-content:space-between;">
            <h3>📋 Meldunek dzienny</h3>
            <span class="zhp-badge"
              :class="today?.status==='submitted' ? 'zhp-badge-green' : today?.status==='draft' ? 'zhp-badge-gold' : 'zhp-badge-gray'"
              x-text="statusLabel || 'Nowy'">
            </span>
          </div>
          <div class="zhp-card-body">
            <div class="zhp-alert zhp-alert-ok" x-show="isSubmitted">✓ Meldunek wysłany. Nie można modyfikować.</div>
            <fieldset :disabled="isSubmitted" style="border:none;padding:0;margin:0;">
              <div class="bm-grid-3" style="margin-bottom:12px;">
                <div class="zhp-field">
                  <label class="zhp-label">Uczestnicy</label>
                  <input type="number" class="zhp-input" x-model.number="form.participants" min="0"
                    style="text-align:center;font-size:1.4rem;font-weight:700;padding:11px 8px;">
                </div>
                <div class="zhp-field">
                  <label class="zhp-label">Kadra</label>
                  <input type="number" class="zhp-input" x-model.number="form.staff" min="0"
                    style="text-align:center;font-size:1.4rem;font-weight:700;padding:11px 8px;">
                </div>
                <div class="zhp-field">
                  <label class="zhp-label">Pracownicy</label>
                  <input type="number" class="zhp-input" x-model.number="form.workers" min="0"
                    style="text-align:center;font-size:1.4rem;font-weight:700;padding:11px 8px;">
                </div>
              </div>
              <div style="background:var(--zhp-green-light);border:1px solid var(--zhp-green-border);border-radius:var(--zhp-radius-sm);padding:12px;text-align:center;margin-bottom:12px;">
                <span style="font-size:.75rem;color:var(--zhp-green);text-transform:uppercase;font-weight:700;">Łącznie</span>
                <div style="font-size:2rem;font-weight:700;color:var(--zhp-green);" x-text="total"></div>
              </div>
              <div class="zhp-field">
                <label class="zhp-label">Uwagi</label>
                <textarea class="zhp-textarea" x-model="form.notes" rows="2" placeholder="opcjonalnie…"></textarea>
              </div>
            </fieldset>
            <div class="zhp-alert zhp-alert-ok"    x-show="success" x-text="success" x-transition></div>
            <div class="zhp-alert zhp-alert-urgent" x-show="error"  x-text="error"   x-transition></div>
            <div class="bm-grid-2" x-show="!isSubmitted" style="gap:10px;">
              <button class="zhp-btn zhp-btn-ghost" @click="saveDraft()" :disabled="loading"
                x-text="loading ? '…' : '💾 Zapisz roboczo'"></button>
              <button class="zhp-btn zhp-btn-primary" @click="submit()" :disabled="loading"
                x-text="loading ? '…' : '✓ Wyślij meldunek'"></button>
            </div>
            <hr class="zhp-divider" style="margin-top:18px;">
            <details>
              <summary style="cursor:pointer;font-weight:700;color:var(--zhp-green);font-size:.88rem;outline:none;">
                📊 Historia meldunków
              </summary>
              <div style="overflow-x:auto;margin-top:10px;">
                <table class="zhp-table">
                  <thead><tr><th>Data</th><th>Ucz.</th><th>Kadra</th><th>Prac.</th><th>Status</th></tr></thead>
                  <tbody>
                    <template x-for="r in history.slice(0,7)" :key="r.id">
                      <tr>
                        <td x-text="r.count_date"></td>
                        <td style="text-align:center;" x-text="r.participants"></td>
                        <td style="text-align:center;" x-text="r.staff"></td>
                        <td style="text-align:center;" x-text="r.workers"></td>
                        <td style="text-align:center;">
                          <span class="zhp-badge"
                            :class="r.status==='submitted'?'zhp-badge-green':r.status==='draft'?'zhp-badge-gold':'zhp-badge-gray'"
                            x-text="r.status==='submitted'?'✓':r.status==='draft'?'roboczy':'—'"></span>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
            </details>
          </div>
        </div>
      </div>
    </div>

    <!-- ─────────────────────────────────────────────────── -->
    <!-- ZAKŁADKA: PLAN DNIA                                  -->
    <!-- ─────────────────────────────────────────────────── -->
    <div x-show="tab==='plan'" class="bm-section">
      <div x-data="bmSchedule()" x-init="init()">
        <div class="zhp-card">
          <div class="zhp-card-header"><h3>🗓 Plan dnia</h3></div>
          <div class="zhp-card-body">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
              <input type="date" class="zhp-input" x-model="selectedDate" @change="loadSchedule()"
                style="width:auto;padding:7px 11px;">
              <template x-for="d in availableDates" :key="d">
                <button @click="selectDate(d)" class="zhp-btn zhp-btn-sm"
                  :class="d===selectedDate ? 'zhp-btn-primary' : 'zhp-btn-ghost'"
                  x-text="d.slice(5)">
                </button>
              </template>
            </div>
            <div class="zhp-loader" x-show="loading"><div class="zhp-spinner"></div> Ładowanie planu…</div>
            <p x-show="!loading && !plans.length" style="color:var(--zhp-text-muted);text-align:center;padding:20px;">
              Brak planu na wybrany dzień.
            </p>
            <template x-for="plan in plans" :key="plan.id">
              <div>
                <h4 x-show="plan.title" x-text="plan.title"
                  style="color:var(--zhp-green);font-size:.85rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;"></h4>
                <div class="zhp-timeline">
                  <template x-for="item in plan.items" :key="item.id">
                    <div class="zhp-timeline-item" :style="item.item_status==='cancelled'?'opacity:.5;':''">
                      <div class="zhp-time-label"
                        x-text="item.time_from + (item.time_to ? ' – ' + item.time_to : '')"></div>
                      <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                        <span :style="item.item_status==='cancelled'?'text-decoration:line-through;':'font-weight:600;'"
                          x-text="item.title"></span>
                        <span x-show="item.is_new_today"     class="zhp-badge zhp-badge-green" style="font-size:.63rem;">nowe</span>
                        <span x-show="item.is_updated_today" class="zhp-badge zhp-badge-gold"  style="font-size:.63rem;">zmienione</span>
                        <span x-show="item.is_mandatory"     class="zhp-badge zhp-badge-blue"  style="font-size:.63rem;">⚡ obowiązkowe</span>
                        <span x-show="item.item_status==='cancelled'" class="zhp-badge zhp-badge-red" style="font-size:.63rem;">odwołane</span>
                      </div>
                      <p x-show="item.description" x-text="item.description"
                        style="margin:3px 0 0;font-size:.8rem;color:var(--zhp-text-mid);"></p>
                      <p x-show="item.location" x-text="'📍 ' + item.location"
                        style="margin:2px 0 0;font-size:.76rem;color:var(--zhp-text-muted);"></p>
                    </div>
                  </template>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- ─────────────────────────────────────────────────── -->
    <!-- ZAKŁADKA: JADŁOSPIS                                  -->
    <!-- ─────────────────────────────────────────────────── -->
    <div x-show="tab==='jadlospis'" class="bm-section">
      <div x-data="bmMenu()" x-init="init()">
        <div class="zhp-card">
          <div class="zhp-card-header" style="justify-content:space-between;">
            <h3>🍽 Jadłospis</h3>
            <div style="display:flex;gap:4px;">
              <button @click="setViewMode('day')"  class="zhp-btn zhp-btn-white zhp-btn-sm"
                :style="viewMode==='day'  ? 'background:rgba(255,255,255,.35);' : ''">Dzienny</button>
              <button @click="setViewMode('week')" class="zhp-btn zhp-btn-white zhp-btn-sm"
                :style="viewMode==='week' ? 'background:rgba(255,255,255,.35);' : ''">Tygodniowy</button>
            </div>
          </div>
          <div class="zhp-card-body">
            <div class="zhp-loader" x-show="loading"><div class="zhp-spinner"></div> Ładowanie jadłospisu…</div>
            <div class="zhp-alert zhp-alert-urgent" x-show="error" x-text="error"></div>

            <!-- Wybór dnia -->
            <div x-show="viewMode==='day'" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;">
              <template x-for="d in availableDates" :key="d">
                <button @click="selectDate(d)" class="zhp-btn zhp-btn-sm"
                  :class="d===selectedDate ? 'zhp-btn-primary' : 'zhp-btn-ghost'"
                  x-text="d.slice(5)">
                </button>
              </template>
            </div>

            <!-- Widok dzienny -->
            <template x-if="viewMode==='day' && !loading">
              <div>
                <p x-show="!day" style="color:var(--zhp-text-muted);text-align:center;padding:16px;">Brak jadłospisu na wybrany dzień.</p>
                <template x-if="day">
                  <div>
                    <p x-show="day.notes" x-text="day.notes"
                      style="font-style:italic;color:var(--zhp-text-mid);margin-bottom:14px;padding:9px;background:var(--zhp-green-light);border-radius:var(--zhp-radius-sm);font-size:.88rem;"></p>
                    <template x-for="mealType in ['sniadanie','drugie_sniadanie','obiad','podwieczorek','kolacja','inne']" :key="mealType">
                      <template x-if="day.items.filter(i => i.meal_type === mealType).length">
                        <div class="zhp-meal-section">
                          <div class="zhp-meal-title" x-text="mealTypeLabel(mealType)"></div>
                          <template x-for="item in day.items.filter(i => i.meal_type === mealType)" :key="item.id">
                            <div class="zhp-meal-item">
                              <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
                                <span x-show="item.time_from" x-text="item.time_from"
                                  style="font-size:.76rem;color:var(--zhp-green);font-weight:700;"></span>
                                <strong x-text="item.title"></strong>
                                <span x-show="item.is_new_today"     class="zhp-badge zhp-badge-green" style="font-size:.62rem;">nowe</span>
                                <span x-show="item.is_updated_today" class="zhp-badge zhp-badge-gold"  style="font-size:.62rem;">zmienione</span>
                              </div>
                              <p x-show="item.description" x-text="item.description"
                                style="margin:3px 0 0;font-size:.8rem;color:var(--zhp-text-mid);"></p>
                              <p x-show="item.allergens" x-text="'⚠ Alergeny: ' + item.allergens"
                                style="margin:3px 0 0;font-size:.74rem;color:#7A5800;background:var(--zhp-gold-light);padding:2px 7px;border-radius:var(--zhp-radius-sm);display:inline-block;"></p>
                            </div>
                          </template>
                        </div>
                      </template>
                    </template>
                  </div>
                </template>
              </div>
            </template>

            <!-- Widok tygodniowy -->
            <template x-if="viewMode==='week' && !loading">
              <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:10px;">
                <template x-for="wday in weekDays" :key="wday.meal_date">
                  <div style="border:1px solid var(--zhp-border);border-radius:var(--zhp-radius);overflow:hidden;">
                    <div style="background:var(--zhp-green);color:#fff;padding:7px 11px;font-size:.8rem;font-weight:700;"
                      x-text="wday.meal_date.slice(5)"></div>
                    <div style="padding:9px;">
                      <p x-show="!wday.items||!wday.items.length" style="color:var(--zhp-text-muted);font-size:.78rem;text-align:center;">Brak</p>
                      <template x-for="item in (wday.items||[])" :key="item.id">
                        <div style="font-size:.76rem;margin-bottom:4px;border-bottom:1px solid var(--zhp-border);padding-bottom:3px;">
                          <span style="color:var(--zhp-text-muted);" x-text="mealTypeLabel(item.meal_type) + ': '"></span>
                          <span style="font-weight:600;" x-text="item.title"></span>
                        </div>
                      </template>
                    </div>
                  </div>
                </template>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- ─────────────────────────────────────────────────── -->
    <!-- ZAKŁADKA: REZERWACJE                                 -->
    <!-- ─────────────────────────────────────────────────── -->
    <div x-show="tab==='rezerwacje'" class="bm-section">
      <div x-data="bmReservations()" x-init="init()">
        <div class="zhp-card">
          <div class="zhp-card-header"><h3>🏕 Rezerwacje zasobów</h3></div>
          <div class="zhp-card-body">
            <div class="zhp-loader" x-show="loading"><div class="zhp-spinner"></div> Ładowanie…</div>
            <div class="zhp-alert zhp-alert-ok" x-show="success" x-text="success" x-transition></div>

            <template x-if="!selectedResource">
              <div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:12px;">
                  <template x-for="res in resources" :key="res.id">
                    <div style="border:1px solid var(--zhp-border);border-radius:var(--zhp-radius);padding:15px;background:var(--zhp-white);">
                      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:7px;">
                        <strong x-text="res.name"></strong>
                        <span class="zhp-badge zhp-badge-green" x-text="res.type"></span>
                      </div>
                      <p style="font-size:.78rem;color:var(--zhp-text-muted);margin-bottom:4px;"
                        x-text="'🕒 ' + res.available_from + ' – ' + res.available_to"></p>
                      <p x-show="res.rules" x-text="res.rules"
                        style="font-size:.76rem;color:var(--zhp-text-muted);margin-bottom:9px;font-style:italic;"></p>
                      <button class="zhp-btn zhp-btn-primary zhp-btn-sm" @click="openForm(res)">Zarezerwuj →</button>
                    </div>
                  </template>
                </div>
                <p x-show="!loading && !resources.length" style="color:var(--zhp-text-muted);text-align:center;padding:18px;">
                  Brak dostępnych zasobów.
                </p>
              </div>
            </template>

            <template x-if="selectedResource">
              <div style="background:var(--zhp-green-light);border:1px solid var(--zhp-green-border);border-radius:var(--zhp-radius);padding:18px;">
                <h4 style="color:var(--zhp-green);margin-bottom:14px;">
                  Rezerwacja: <span x-text="selectedResource.name"></span>
                </h4>
                <form @submit.prevent="submitReservation()">
                  <div class="zhp-field">
                    <label class="zhp-label">Data *</label>
                    <input type="date" class="zhp-input" x-model="form.res_date" @change="loadSlots()" required>
                  </div>
                  <template x-if="takenSlots.length">
                    <div style="font-size:.78rem;color:var(--zhp-text-mid);margin-bottom:9px;">
                      <strong>Zajęte:</strong>
                      <template x-for="slot in takenSlots" :key="slot.start_time">
                        <span class="zhp-badge zhp-badge-red" style="margin-left:4px;"
                          x-text="slot.start_time.slice(0,5) + '–' + slot.end_time.slice(0,5)"></span>
                      </template>
                    </div>
                  </template>
                  <template x-if="blockWindows.length">
                    <div class="zhp-alert zhp-alert-warn" style="font-size:.78rem;margin-bottom:9px;flex-direction:column;align-items:flex-start;">
                      <strong>⚠ Blokady techniczne:</strong>
                      <template x-for="bw in blockWindows" :key="bw.from">
                        <div x-text="bw.from.slice(0,5) + '–' + bw.to.slice(0,5) + (bw.reason ? ' · ' + bw.reason : '')"></div>
                      </template>
                    </div>
                  </template>
                  <div class="bm-grid-2" style="gap:11px;">
                    <div class="zhp-field">
                      <label class="zhp-label">Od *</label>
                      <input type="time" class="zhp-input" x-model="form.start_time" required>
                    </div>
                    <div class="zhp-field">
                      <label class="zhp-label">Do *</label>
                      <input type="time" class="zhp-input" x-model="form.end_time" required>
                    </div>
                  </div>
                  <div class="zhp-field">
                    <label class="zhp-label">Cel rezerwacji *</label>
                    <input type="text" class="zhp-input" x-model="form.purpose" required placeholder="np. Mecz piłkarski">
                  </div>
                  <div class="zhp-alert zhp-alert-urgent" x-show="formError" x-text="formError"></div>
                  <div class="bm-grid-2" style="gap:9px;">
                    <button type="submit" class="zhp-btn zhp-btn-primary" :disabled="loading"
                      x-text="loading ? 'Wysyłanie…' : 'Zarezerwuj'"></button>
                    <button type="button" class="zhp-btn zhp-btn-ghost" @click="selectedResource=null">Anuluj</button>
                  </div>
                </form>
              </div>
            </template>

            <hr class="zhp-divider" style="margin-top:18px;">
            <details>
              <summary style="cursor:pointer;font-weight:700;color:var(--zhp-green);font-size:.88rem;outline:none;">
                📋 Moje rezerwacje
              </summary>
              <div style="overflow-x:auto;margin-top:10px;">
                <table class="zhp-table">
                  <thead><tr><th>Zasób</th><th>Data</th><th>Godziny</th><th>Status</th><th></th></tr></thead>
                  <tbody>
                    <template x-for="r in myReservations" :key="r.id">
                      <tr>
                        <td x-text="resources.find(res => res.id === r.resource_id)?.name || ('#' + r.resource_id)"></td>
                        <td x-text="r.res_date"></td>
                        <td x-text="r.start_time.slice(0,5) + '–' + r.end_time.slice(0,5)"></td>
                        <td>
                          <span class="zhp-badge"
                            :class="r.status==='approved'?'zhp-badge-green':r.status==='pending'?'zhp-badge-gold':'zhp-badge-red'"
                            x-text="r.status_label || r.status"></span>
                        </td>
                        <td>
                          <button x-show="r.status==='pending'" @click="cancel(r.id)"
                            class="zhp-btn zhp-btn-danger zhp-btn-sm">Anuluj</button>
                        </td>
                      </tr>
                    </template>
                    <tr x-show="!myReservations.length">
                      <td colspan="5" style="text-align:center;color:var(--zhp-text-muted);padding:14px;">Brak rezerwacji.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </details>
          </div>
        </div>
      </div>
    </div>

    <!-- ─────────────────────────────────────────────────── -->
    <!-- ZAKŁADKA: KOMUNIKACJA                               -->
    <!-- ─────────────────────────────────────────────────── -->
    <div x-show="tab==='komunikacja'" class="bm-section">
      <div x-data="bmConversations()" x-init="init()">
        <div class="zhp-card">

          <!-- LISTA -->
          <template x-if="view==='list'">
            <div>
              <div class="zhp-card-header" style="justify-content:space-between;">
                <h3>
                  💬 Wiadomości
                  <span x-show="unreadTotal > 0" class="zhp-badge zhp-badge-red" x-text="unreadTotal" style="margin-left:6px;"></span>
                </h3>
                <button class="zhp-btn zhp-btn-white zhp-btn-sm" @click="view='new'">+ Nowy wątek</button>
              </div>
              <div style="padding:0;">
                <div class="zhp-loader" x-show="loading" style="padding:16px;"><div class="zhp-spinner"></div> Ładowanie…</div>
                <p x-show="!threads.length && !loading" style="text-align:center;color:var(--zhp-text-muted);padding:24px;">Brak wiadomości.</p>
                <template x-for="t in threads" :key="t.id">
                  <div @click="openThread(t.id)"
                    style="padding:13px 20px;border-bottom:1px solid var(--zhp-border);cursor:pointer;transition:background .13s;"
                    :style="t.unread_camp>0 ? 'border-left:4px solid var(--zhp-green);font-weight:600;background:var(--zhp-green-light);' : ''"
                    @mouseenter="$el.style.background='#f9fafb'"
                    @mouseleave="$el.style.background = t.unread_camp > 0 ? 'var(--zhp-green-light)' : ''">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                      <span x-text="t.subject" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.93rem;"></span>
                      <span x-show="t.unread_camp>0" class="zhp-badge zhp-badge-green" x-text="t.unread_camp + ' nowych'"></span>
                    </div>
                    <div style="font-size:.76rem;color:var(--zhp-text-muted);margin-top:3px;display:flex;gap:7px;">
                      <span class="zhp-badge zhp-badge-gray" style="font-size:.63rem;" x-text="t.status"></span>
                      <span x-text="t.last_message_at"></span>
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </template>

          <!-- NOWY WĄTEK -->
          <template x-if="view==='new'">
            <div>
              <div class="zhp-card-header">
                <button class="zhp-btn zhp-btn-white zhp-btn-sm" @click="view='list'">← Wróć</button>
                <h3 style="margin-left:8px;">Nowa wiadomość</h3>
              </div>
              <div class="zhp-card-body">
                <div class="zhp-field">
                  <label class="zhp-label">Temat *</label>
                  <input type="text" class="zhp-input" x-model="form.subject" required placeholder="Temat wiadomości">
                </div>
                <div class="zhp-field">
                  <label class="zhp-label">Priorytet</label>
                  <select class="zhp-select" x-model="form.priority">
                    <option value="normal">Normalny</option>
                    <option value="high">Wysoki</option>
                    <option value="urgent">Pilny</option>
                  </select>
                </div>
                <div class="zhp-field">
                  <label class="zhp-label">Treść *</label>
                  <textarea class="zhp-textarea" x-model="form.content" rows="5" required placeholder="Treść wiadomości…"></textarea>
                </div>
                <div class="zhp-alert zhp-alert-urgent" x-show="error"   x-text="error"   x-transition></div>
                <div class="zhp-alert zhp-alert-ok"     x-show="success" x-text="success" x-transition></div>
                <div class="bm-grid-2" style="gap:9px;">
                  <button class="zhp-btn zhp-btn-primary" @click="createThread()" :disabled="loading"
                    x-text="loading ? 'Wysyłanie…' : '✓ Wyślij wiadomość'"></button>
                  <button class="zhp-btn zhp-btn-ghost" @click="view='list'">Anuluj</button>
                </div>
              </div>
            </div>
          </template>

          <!-- WĄTEK -->
          <template x-if="view==='thread' && currentThread">
            <div>
              <div class="zhp-card-header" style="justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:9px;">
                  <button class="zhp-btn zhp-btn-white zhp-btn-sm" @click="view='list';currentThread=null;">← Lista</button>
                  <h3 x-text="currentThread.subject" style="font-size:.92rem;"></h3>
                </div>
                <span class="zhp-badge"
                  :class="currentThread.status==='closed' ? 'zhp-badge-gray' : 'zhp-badge-green'"
                  x-text="currentThread.status">
                </span>
              </div>
              <div class="zhp-card-body">
                <div style="max-height:340px;overflow-y:auto;display:flex;flex-direction:column;gap:7px;margin-bottom:14px;">
                  <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.author_type==='admin' ? 'zhp-msg-admin' : 'zhp-msg-mine'">
                      <div style="font-size:.7rem;color:var(--zhp-text-muted);margin-bottom:3px;display:flex;gap:5px;">
                        <strong x-text="msg.author_type==='admin' ? '🏕 Administracja' : '👤 Ty'"></strong>
                        <span x-text="msg.created_at"></span>
                      </div>
                      <div x-html="msg.content" style="font-size:.88rem;"></div>
                    </div>
                  </template>
                </div>
                <div x-show="currentThread.status!=='closed' && currentThread.status!=='archived'">
                  <hr class="zhp-divider">
                  <div class="zhp-field">
                    <label class="zhp-label">Twoja odpowiedź</label>
                    <textarea class="zhp-textarea" x-model="replyContent" rows="3" placeholder="Napisz odpowiedź…"></textarea>
                  </div>
                  <div class="zhp-alert zhp-alert-urgent" x-show="error" x-text="error"></div>
                  <button class="zhp-btn zhp-btn-primary" @click="sendReply()" :disabled="loading || !replyContent"
                    x-text="loading ? 'Wysyłanie…' : 'Wyślij odpowiedź ↩'">
                  </button>
                </div>
                <div class="zhp-alert zhp-alert-warn" x-show="currentThread.status==='closed'">
                  Wątek zamknięty.
                </div>
              </div>
            </div>
          </template>

        </div>
      </div>
    </div>

    <!-- ─────────────────────────────────────────────────── -->
    <!-- ZAKŁADKA: BAZA POMOCY                               -->
    <!-- ─────────────────────────────────────────────────── -->
    <div x-show="tab==='pomoc'" class="bm-section">
      <div x-data="bmHelp()" x-init="init()">
        <div class="zhp-card">

          <!-- LISTA -->
          <template x-if="!current">
            <div>
              <div class="zhp-card-header"><h3>📚 Baza pomocy</h3></div>
              <div class="zhp-card-body">
                <div style="display:grid;grid-template-columns:1fr auto;gap:8px;margin-bottom:10px;">
                  <input type="text" class="zhp-input" x-model="search"
                    @input.debounce.400ms="applyFilters()" placeholder="🔍 Szukaj w bazie pomocy…">
                  <select class="zhp-select" x-model="filterType" @change="applyFilters()" style="width:auto;">
                    <option value="">Wszystkie typy</option>
                    <option value="article">Artykuł</option>
                    <option value="faq">FAQ</option>
                    <option value="contact">Kontakt</option>
                    <option value="procedure">Procedura</option>
                    <option value="instruction">Instrukcja</option>
                  </select>
                </div>
                <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:12px;">
                  <template x-for="cat in categories" :key="cat">
                    <button @click="filterCat = filterCat===cat ? '' : cat; applyFilters()"
                      class="zhp-btn zhp-btn-sm"
                      :class="filterCat===cat ? 'zhp-btn-primary' : 'zhp-btn-ghost'"
                      x-text="cat">
                    </button>
                  </template>
                </div>
                <div class="zhp-loader" x-show="loading"><div class="zhp-spinner"></div> Ładowanie…</div>
                <template x-for="art in alarmArticles" :key="art.id">
                  <div @click="openArticle(art.id)" class="zhp-alert zhp-alert-urgent"
                       style="cursor:pointer;flex-direction:column;align-items:flex-start;margin-bottom:7px;">
                    <strong>🚨 <span x-text="art.title"></span></strong>
                    <p x-text="art.excerpt" style="margin:3px 0 0;font-size:.83rem;"></p>
                  </div>
                </template>
                <template x-for="art in pinnedArticles" :key="art.id">
                  <div @click="openArticle(art.id)"
                    style="border:1px solid var(--zhp-gold);border-left:4px solid var(--zhp-gold);padding:11px;margin-bottom:7px;border-radius:var(--zhp-radius-sm);cursor:pointer;background:var(--zhp-gold-light);">
                    <strong>📌 <span x-text="art.title"></span></strong>
                    <p x-text="art.excerpt" style="margin:3px 0 0;font-size:.83rem;color:var(--zhp-text-mid);"></p>
                  </div>
                </template>
                <template x-for="art in articles.filter(a => !a.is_alarm && !a.is_pinned)" :key="art.id">
                  <div @click="openArticle(art.id)"
                    style="border:1px solid var(--zhp-border);padding:11px;margin-bottom:6px;border-radius:var(--zhp-radius-sm);cursor:pointer;transition:border-color .13s;"
                    @mouseenter="$el.style.borderColor='var(--zhp-green)'"
                    @mouseleave="$el.style.borderColor='var(--zhp-border)'">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                      <span>
                        <span x-text="art.type==='faq'?'❓':art.type==='contact'?'📞':art.type==='procedure'?'⚙':'📄'"></span>
                        <strong x-text="art.title"></strong>
                      </span>
                      <span class="zhp-badge zhp-badge-gray" x-text="art.category"></span>
                    </div>
                    <p x-text="art.excerpt" style="margin:3px 0 0;font-size:.83rem;color:var(--zhp-text-mid);"></p>
                  </div>
                </template>
                <p x-show="!loading && !articles.length" style="color:var(--zhp-text-muted);text-align:center;padding:18px;">Brak wyników.</p>
              </div>
            </div>
          </template>

          <!-- PODGLĄD ARTYKUŁU -->
          <template x-if="current">
            <div>
              <div class="zhp-card-header">
                <button class="zhp-btn zhp-btn-white zhp-btn-sm" @click="closeArticle()">← Wróć</button>
                <h3 x-text="current.title" style="margin-left:8px;font-size:.92rem;"></h3>
              </div>
              <div class="zhp-card-body">
                <div style="display:flex;gap:7px;flex-wrap:wrap;margin-bottom:14px;">
                  <span class="zhp-badge zhp-badge-green" x-text="current.type || 'artykuł'"></span>
                  <span class="zhp-badge zhp-badge-gray"  x-text="current.category" x-show="current.category"></span>
                </div>
                <div x-html="current.content" style="line-height:1.7;font-size:.91rem;"></div>
              </div>
            </div>
          </template>

        </div>
      </div>
    </div>

    <!-- ─────────────────────────────────────────────────── -->
    <!-- ZAKŁADKA: FORMULARZE I ZGŁOSZENIA                   -->
    <!-- ─────────────────────────────────────────────────── -->
    <div x-show="tab==='formularze'" class="bm-section" x-data="{ subtab: 'forms' }">

      <!-- Sub-nawigacja -->
      <div style="display:flex;gap:4px;margin-bottom:14px;">
        <button class="zhp-btn zhp-btn-sm" :class="subtab==='forms' ? 'zhp-btn-primary' : 'zhp-btn-ghost'"
          @click="subtab='forms'">📝 Formularze</button>
        <button class="zhp-btn zhp-btn-sm" :class="subtab==='submissions' ? 'zhp-btn-primary' : 'zhp-btn-ghost'"
          @click="subtab='submissions'">📋 Moje zgłoszenia</button>
      </div>

      <!-- FORMULARZE -->
      <div x-show="subtab==='forms'" x-data="bmForms()" x-init="init()">
        <div class="zhp-card">
          <div class="zhp-loader" x-show="loading" style="padding:16px;"><div class="zhp-spinner"></div> Ładowanie...</div>
          <div class="zhp-alert zhp-alert-urgent" x-show="error && !loading" x-text="error"></div>

          <!-- Lista formularzy -->
          <div x-show="!currentForm && !submitted">
            <div class="zhp-card-header"><h3>📝 Dostępne formularze</h3></div>
            <div style="padding:0;">
              <p x-show="!loading && !forms.length"
                style="text-align:center;color:var(--zhp-text-muted);padding:22px;">
                Brak dostępnych formularzy.
              </p>
              <template x-for="form in filtered" :key="form.id">
                <div @click="openForm(form.id)"
                  style="padding:13px 20px;border-bottom:1px solid var(--zhp-border);cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:12px;transition:background .13s;"
                  @mouseenter="$el.style.background='var(--zhp-green-light)'"
                  @mouseleave="$el.style.background=''">
                  <div>
                    <strong x-text="form.name" style="font-size:.93rem;"></strong>
                    <p x-show="form.description" x-text="form.description"
                      style="font-size:.8rem;color:var(--zhp-text-muted);margin-top:2px;"></p>
                  </div>
                  <span style="color:var(--zhp-green);font-size:1.1rem;">→</span>
                </div>
              </template>
            </div>
          </div>

          <!-- Wypełnianie formularza -->
          <div x-show="currentForm && !submitted">
            <div class="zhp-card-header">
              <button class="zhp-btn zhp-btn-white zhp-btn-sm" @click="closeForm()">← Wróć</button>
              <h3 x-text="currentForm && currentForm.name" style="margin-left:8px;font-size:.92rem;"></h3>
            </div>
            <form class="zhp-card-body" @submit.prevent="submit()">
              <p x-show="currentForm && currentForm.info_before"
                x-text="currentForm && currentForm.info_before"
                style="color:var(--zhp-text-mid);font-size:.88rem;margin-bottom:14px;padding:10px;background:var(--zhp-green-light);border-radius:var(--zhp-radius-sm);"></p>

              <template x-for="field in fields" :key="field.id">
                <div class="zhp-field">
                  <label class="zhp-label">
                    <span x-text="field.label"></span>
                    <span x-show="field.is_required" style="color:var(--zhp-red);margin-left:2px;">*</span>
                  </label>

                  <template x-if="['text','email','number','tel','url','date'].includes(field.type)">
                    <input :type="field.type" class="zhp-input"
                      x-model="formValues[field.field_key]"
                      :required="field.is_required"
                      :placeholder="field.placeholder || ''">
                  </template>

                  <template x-if="field.type === 'textarea'">
                    <textarea class="zhp-textarea"
                      x-model="formValues[field.field_key]"
                      :required="field.is_required"
                      :placeholder="field.placeholder || ''"></textarea>
                  </template>

                  <template x-if="field.type === 'select'">
                    <select class="zhp-select" x-model="formValues[field.field_key]" :required="field.is_required">
                      <option value="">— wybierz —</option>
                      <template x-for="opt in field.options" :key="opt">
                        <option :value="opt" x-text="opt"></option>
                      </template>
                    </select>
                  </template>

                  <template x-if="field.type === 'radio'">
                    <div style="display:flex;flex-direction:column;gap:6px;margin-top:3px;">
                      <template x-for="opt in field.options" :key="opt">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                          <input type="radio" :name="'field_' + field.field_key" :value="opt"
                            x-model="formValues[field.field_key]"
                            style="width:16px;height:16px;accent-color:var(--zhp-green);">
                          <span x-text="opt"></span>
                        </label>
                      </template>
                    </div>
                  </template>

                  <!-- Alpine x-model na array dla checkbox dziala natywnie -->
                  <template x-if="field.type === 'checkbox'">
                    <div style="display:flex;flex-direction:column;gap:6px;margin-top:3px;">
                      <template x-for="opt in field.options" :key="opt">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                          <input type="checkbox" :value="opt"
                            x-model="formValues[field.field_key]"
                            style="width:16px;height:16px;accent-color:var(--zhp-green);">
                          <span x-text="opt"></span>
                        </label>
                      </template>
                    </div>
                  </template>

                  <p x-show="fieldError(field.field_key)"
                    x-text="fieldError(field.field_key)"
                    style="color:var(--zhp-red);font-size:.8rem;margin-top:3px;"></p>
                  <p x-show="field.help_text" x-text="field.help_text"
                    style="color:var(--zhp-text-muted);font-size:.78rem;margin-top:3px;"></p>
                </div>
              </template>

              <div class="zhp-alert zhp-alert-urgent" x-show="error" x-text="error" x-transition></div>

              <button type="submit" class="zhp-btn zhp-btn-primary" :disabled="submitting"
                style="width:100%;justify-content:center;margin-top:4px;">
                <span x-show="submitting" class="zhp-spinner"></span>
                <span x-text="submitting ? 'Wysyłanie...' : '✓ Wyślij formularz'"></span>
              </button>
            </form>
          </div>

          <!-- Sukces -->
          <div x-show="submitted" class="zhp-card-body" style="text-align:center;padding:32px 20px;">
            <div style="font-size:3rem;margin-bottom:12px;">✅</div>
            <h3 style="color:var(--zhp-green);margin-bottom:8px;">Formularz wysłany!</h3>
            <p x-show="currentForm && currentForm.info_after"
              x-text="currentForm && currentForm.info_after"
              style="color:var(--zhp-text-mid);margin-bottom:16px;"></p>
            <button class="zhp-btn zhp-btn-ghost" @click="closeForm()">← Wróć do listy formularzy</button>
          </div>

        </div>
      </div>

      <!-- MOJE ZGLOSZENIA -->
      <div x-show="subtab==='submissions'" x-data="bmSubmissions()" x-init="init()">
        <div class="zhp-card">
          <div class="zhp-loader" x-show="loading" style="padding:16px;"><div class="zhp-spinner"></div> Ładowanie...</div>
          <div class="zhp-alert zhp-alert-urgent" x-show="error && !loading" x-text="error"></div>

          <!-- Lista zgłoszeń -->
          <div x-show="!current">
            <div class="zhp-card-header" style="justify-content:space-between;">
              <h3>📋 Moje zgłoszenia</h3>
              <select x-model="filterStatus" @change="applyFilter()"
                style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:var(--zhp-radius-sm);padding:4px 8px;font-size:.8rem;cursor:pointer;">
                <option value="" style="color:var(--zhp-text);">Wszystkie</option>
                <option value="new" style="color:var(--zhp-text);">Nowe</option>
                <option value="in_progress" style="color:var(--zhp-text);">W trakcie</option>
                <option value="waiting" style="color:var(--zhp-text);">Oczekuje</option>
                <option value="closed" style="color:var(--zhp-text);">Zamknięte</option>
              </select>
            </div>
            <div style="padding:0;">
              <p x-show="!loading && !submissions.length"
                style="text-align:center;color:var(--zhp-text-muted);padding:22px;">Brak zgłoszeń.</p>
              <template x-for="sub in submissions" :key="sub.id">
                <div @click="openSubmission(sub.id)"
                  style="padding:13px 20px;border-bottom:1px solid var(--zhp-border);cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:12px;transition:background .13s;"
                  @mouseenter="$el.style.background='var(--zhp-green-light)'"
                  @mouseleave="$el.style.background=''">
                  <div>
                    <strong style="font-size:.9rem;">Zgłoszenie #<span x-text="sub.id"></span></strong>
                    <div style="display:flex;gap:5px;margin-top:3px;flex-wrap:wrap;">
                      <span class="zhp-badge zhp-badge-gray" style="font-size:.65rem;" x-text="sub.category || 'bez kategorii'"></span>
                      <span class="zhp-badge zhp-badge-blue" style="font-size:.65rem;" x-text="priorityLabel(sub.priority)"></span>
                    </div>
                    <div style="font-size:.76rem;color:var(--zhp-text-muted);margin-top:2px;" x-text="sub.created_at"></div>
                  </div>
                  <span class="zhp-badge"
                    :class="sub.status==='closed'?'zhp-badge-green':sub.status==='in_progress'?'zhp-badge-blue':sub.status==='waiting'?'zhp-badge-gold':'zhp-badge-gray'"
                    x-text="statusLabel(sub.status)">
                  </span>
                </div>
              </template>
            </div>
          </div>

          <!-- Szczegóły zgłoszenia -->
          <div x-show="current">
            <div class="zhp-card-header">
              <button class="zhp-btn zhp-btn-white zhp-btn-sm" @click="closeSubmission()">← Wróć</button>
              <h3 style="margin-left:8px;font-size:.92rem;">
                Zgłoszenie #<span x-text="current && current.submission && current.submission.id"></span>
              </h3>
            </div>
            <div class="zhp-loader" x-show="loadingDetail" style="padding:16px;">
              <div class="zhp-spinner"></div> Ładowanie...
            </div>
            <div class="zhp-card-body" x-show="current && !loadingDetail">
              <div style="display:flex;gap:7px;flex-wrap:wrap;margin-bottom:14px;">
                <span class="zhp-badge"
                  :class="current && current.submission && current.submission.status==='closed'?'zhp-badge-green':current && current.submission && current.submission.status==='in_progress'?'zhp-badge-blue':current && current.submission && current.submission.status==='waiting'?'zhp-badge-gold':'zhp-badge-gray'"
                  x-text="current && current.submission && statusLabel(current.submission.status)">
                </span>
                <span class="zhp-badge zhp-badge-blue"
                  x-text="current && current.submission && priorityLabel(current.submission.priority)">
                </span>
                <span class="zhp-badge zhp-badge-gray"
                  x-text="'Wysłano: ' + (current && current.submission && current.submission.created_at)">
                </span>
              </div>

              <!-- Pola ze snapshotu formularza -->
              <template x-if="current && current.form_snapshot && current.form_snapshot.fields">
                <div>
                  <template x-for="field in current.form_snapshot.fields" :key="field.field_key">
                    <div style="margin-bottom:10px;padding:9px 13px;background:var(--zhp-bg);border-radius:var(--zhp-radius-sm);">
                      <div style="font-size:.72rem;font-weight:700;color:var(--zhp-text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;"
                        x-text="field.label"></div>
                      <div style="font-size:.9rem;"
                        x-text="current.submission_data && current.submission_data[field.field_key] !== undefined
                          ? (Array.isArray(current.submission_data[field.field_key])
                              ? current.submission_data[field.field_key].join(', ')
                              : String(current.submission_data[field.field_key]))
                          : '—'">
                      </div>
                    </div>
                  </template>
                </div>
              </template>

              <!-- Załączniki -->
              <template x-if="current && current.attachments && current.attachments.length">
                <div style="margin-top:12px;">
                  <div class="zhp-label" style="margin-bottom:6px;">Załączniki</div>
                  <template x-for="att in current.attachments" :key="att.id">
                    <a :href="att.download_url" target="_blank"
                      class="zhp-btn zhp-btn-ghost zhp-btn-sm"
                      style="margin:4px 4px 0 0;display:inline-flex;">
                      📎 <span x-text="att.original_name"></span>
                    </a>
                  </template>
                </div>
              </template>

              <!-- Komentarz admina -->
              <template x-if="current && current.admin_comment">
                <div class="zhp-alert zhp-alert-info" style="margin-top:12px;flex-direction:column;align-items:flex-start;">
                  <strong style="margin-bottom:3px;">Komentarz administratora:</strong>
                  <span x-text="current.admin_comment"></span>
                </div>
              </template>
            </div>
          </div>

        </div>
      </div>

    </div><!-- /formularze -->

    <!-- Stopka -->
    <div style="text-align:center;padding:16px 20px 24px;color:var(--zhp-text-muted);font-size:.72rem;">
      System Bazy Obozowej · ZHP
    </div>

  </div><!-- /panel -->
</div><!-- /wrapper -->
```
