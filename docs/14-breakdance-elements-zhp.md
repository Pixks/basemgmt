# 14 – Breakdance Custom Elements – styl ZHP

Gotowe bloki HTML ze stylowaniem inspirowanym identyfikacją wizualną **Związku Harcerstwa Polskiego** (zhp.pl).  
Każdy blok wklejasz w **Breakdance Studio → Custom Element** lub **Code Block**.

> **Zacznij od Bloku 00** – to globalny CSS design system. Wklej go raz jako **Code Block** na szczycie strony lub w Breakdance → Settings → Custom Code → Head. Pozostałe bloki korzystają z zdefiniowanych w nim zmiennych CSS.

---

## Spis treści

| Nr | Element |
|----|---------|
| 00 | [ZHP Design System CSS](#00-zhp-design-system-css) |
| 01 | [Ekran logowania](#01-ekran-logowania) |
| 02 | [Nagłówek panelu obozu](#02-nagłówek-panelu-obozu) |
| 03 | [Belka użytkownika i wylogowanie](#03-belka-użytkownika-i-wylogowanie) |
| 04 | [Ogłoszenia](#04-ogłoszenia) |
| 05 | [Formularz nowego ogłoszenia](#05-formularz-nowego-ogłoszenia) |
| 06 | [Meldunek dzienny](#06-meldunek-dzienny) |
| 07 | [Pogoda i ostrzeżenia IMGW](#07-pogoda-i-ostrzeżenia-imgw) |
| 08 | [Plan dnia](#08-plan-dnia) |
| 09 | [Rezerwacje](#09-rezerwacje) |
| 10 | [Jadłospis dzienny](#10-jadłospis-dzienny) |
| 11 | [Jadłospis tygodniowy](#11-jadłospis-tygodniowy) |
| 12–14 | [Komunikacja](#12-14-komunikacja) |
| 15–16 | [Baza pomocy](#15-16-baza-pomocy) |
| 17–20 | [Formularze i zgłoszenia](#17-20-formularze-i-zgłoszenia) |
| 21 | [Wrapper ochrony sesji](#21-wrapper-ochrony-sesji) |
| 22 | [Licznik nieprzeczytanych](#22-licznik-nieprzeczytanych) |

---

## 00 – ZHP Design System CSS

Wklej jako **Code Block** lub w **Breakdance → Settings → Custom Code → `<head>`**.

```html
<style>
/* ============================================================
   ZHP DESIGN SYSTEM – CampLink
   Inspiracja: zhp.pl | Związek Harcerstwa Polskiego
   ============================================================ */

:root {
  /* Kolory główne */
  --zhp-green:        #1B5E33;   /* ciemna zieleń – primary */
  --zhp-green-mid:    #2A7A4B;   /* średnia zieleń */
  --zhp-green-light:  #EBF5EE;   /* blade tło zielone */
  --zhp-green-border: #A8D5B5;   /* border zielony */
  --zhp-red:          #C0392B;   /* czerwony – alert / pilne */
  --zhp-red-light:    #FDECEA;   /* blade tło czerwone */
  --zhp-gold:         #D4A017;   /* złoty – ostrzeżenia, wyróżnienie */
  --zhp-gold-light:   #FEF8E7;   /* blade tło złote */

  /* Tekst */
  --zhp-text:         #1A2530;   /* główny tekst */
  --zhp-text-mid:     #4B5A67;   /* pomocniczy tekst */
  --zhp-text-muted:   #8A96A1;   /* wyciszony tekst */

  /* Tła i obramowania */
  --zhp-bg:           #F6F8FA;   /* tło strony */
  --zhp-white:        #FFFFFF;
  --zhp-border:       #D0D8DC;
  --zhp-shadow:       0 2px 8px rgba(27,94,51,.12);
  --zhp-shadow-lg:    0 4px 20px rgba(27,94,51,.15);

  /* Typografia */
  --zhp-font:         'Lato', 'Open Sans', system-ui, sans-serif;
  --zhp-radius:       8px;
  --zhp-radius-sm:    4px;
  --zhp-radius-pill:  9999px;
}

/* === RESET BAZOWY === */
.zhp-panel *, .zhp-login * {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}
.zhp-panel, .zhp-login {
  font-family: var(--zhp-font);
  color: var(--zhp-text);
  background: var(--zhp-bg);
  font-size: 15px;
  line-height: 1.55;
}

/* === KARTY === */
.zhp-card {
  background: var(--zhp-white);
  border: 1px solid var(--zhp-border);
  border-radius: var(--zhp-radius);
  box-shadow: var(--zhp-shadow);
  overflow: hidden;
}
.zhp-card-header {
  background: var(--zhp-green);
  color: var(--zhp-white);
  padding: 14px 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.zhp-card-header h2,
.zhp-card-header h3 {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: .01em;
  color: var(--zhp-white);
}
.zhp-card-body {
  padding: 20px;
}

/* === PRZYCISKI === */
.zhp-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 20px;
  border-radius: var(--zhp-radius-pill);
  border: none;
  font-family: var(--zhp-font);
  font-size: .9rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: background .18s, transform .12s;
}
.zhp-btn:active { transform: scale(.97); }
.zhp-btn:disabled { opacity: .55; cursor: not-allowed; }

.zhp-btn-primary  { background: var(--zhp-green);     color: #fff; }
.zhp-btn-primary:hover  { background: var(--zhp-green-mid); }
.zhp-btn-danger   { background: var(--zhp-red);       color: #fff; }
.zhp-btn-danger:hover   { background: #a93226; }
.zhp-btn-ghost    { background: transparent; color: var(--zhp-green); border: 1.5px solid var(--zhp-green); }
.zhp-btn-ghost:hover    { background: var(--zhp-green-light); }
.zhp-btn-sm       { padding: 5px 14px; font-size: .8rem; }
.zhp-btn-icon     { padding: 7px 10px; }

/* === FORMULARZE === */
.zhp-field { margin-bottom: 14px; }
.zhp-label {
  display: block;
  font-size: .82rem;
  font-weight: 700;
  color: var(--zhp-text-mid);
  margin-bottom: 5px;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.zhp-input,
.zhp-select,
.zhp-textarea {
  width: 100%;
  padding: 10px 14px;
  border: 1.5px solid var(--zhp-border);
  border-radius: var(--zhp-radius-sm);
  font-family: var(--zhp-font);
  font-size: .95rem;
  color: var(--zhp-text);
  background: var(--zhp-white);
  transition: border-color .18s, box-shadow .18s;
  outline: none;
}
.zhp-input:focus,
.zhp-select:focus,
.zhp-textarea:focus {
  border-color: var(--zhp-green);
  box-shadow: 0 0 0 3px rgba(27,94,51,.15);
}
.zhp-textarea { resize: vertical; min-height: 90px; }

/* === ODZNAKI === */
.zhp-badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 10px;
  border-radius: var(--zhp-radius-pill);
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .03em;
  text-transform: uppercase;
}
.zhp-badge-green  { background: var(--zhp-green-light); color: var(--zhp-green); }
.zhp-badge-red    { background: var(--zhp-red-light);   color: var(--zhp-red);   }
.zhp-badge-gold   { background: var(--zhp-gold-light);  color: #7A5800;          }
.zhp-badge-gray   { background: #EAECEE;                color: var(--zhp-text-mid); }
.zhp-badge-blue   { background: #EBF4FF;                color: #1A5494;          }

/* === ALERTY === */
.zhp-alert {
  padding: 12px 16px;
  border-radius: var(--zhp-radius-sm);
  font-size: .9rem;
  margin-bottom: 12px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}
.zhp-alert-urgent { background: var(--zhp-red-light);  border-left: 4px solid var(--zhp-red);  color: var(--zhp-red); }
.zhp-alert-warn   { background: var(--zhp-gold-light); border-left: 4px solid var(--zhp-gold); color: #7A5800; }
.zhp-alert-ok     { background: var(--zhp-green-light);border-left: 4px solid var(--zhp-green);color: var(--zhp-green); }
.zhp-alert-info   { background: #EBF4FF;               border-left: 4px solid #1A5494;         color: #1A5494; }

/* === TABELA === */
.zhp-table {
  width: 100%;
  border-collapse: collapse;
  font-size: .88rem;
}
.zhp-table th {
  background: var(--zhp-green-light);
  color: var(--zhp-green);
  text-align: left;
  padding: 8px 12px;
  font-size: .76rem;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.zhp-table td {
  padding: 9px 12px;
  border-bottom: 1px solid var(--zhp-border);
}
.zhp-table tr:last-child td { border-bottom: none; }
.zhp-table tr:hover td      { background: var(--zhp-green-light); }

/* === ZAKŁADKI === */
.zhp-tabs { display: flex; gap: 4px; margin-bottom: 16px; border-bottom: 2px solid var(--zhp-border); }
.zhp-tab {
  padding: 8px 18px;
  border: none;
  background: none;
  font-family: var(--zhp-font);
  font-size: .88rem;
  font-weight: 600;
  color: var(--zhp-text-mid);
  cursor: pointer;
  border-bottom: 2.5px solid transparent;
  margin-bottom: -2px;
  transition: color .15s, border-color .15s;
}
.zhp-tab:hover     { color: var(--zhp-green); }
.zhp-tab.active    { color: var(--zhp-green); border-bottom-color: var(--zhp-green); }

/* === LOADER === */
.zhp-loader {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--zhp-text-muted);
  font-size: .9rem;
  padding: 16px 0;
}
.zhp-spinner {
  width: 18px; height: 18px;
  border: 2.5px solid var(--zhp-green-border);
  border-top-color: var(--zhp-green);
  border-radius: 50%;
  animation: zhp-spin .7s linear infinite;
}
@keyframes zhp-spin { to { transform: rotate(360deg); } }

/* === DIVIDER === */
.zhp-divider {
  border: none;
  border-top: 1px solid var(--zhp-border);
  margin: 16px 0;
}

/* === NAGŁÓWEK OBOZU === */
.zhp-camp-hero {
  background: linear-gradient(135deg, var(--zhp-green) 0%, var(--zhp-green-mid) 100%);
  color: #fff;
  padding: 24px 28px;
  border-radius: var(--zhp-radius);
  position: relative;
  overflow: hidden;
}
.zhp-camp-hero::before {
  content: '⚜';
  position: absolute;
  right: 20px; top: 14px;
  font-size: 3rem;
  opacity: .12;
}

/* === PASEK STATUSU === */
.zhp-status-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  padding: 12px 20px;
  background: var(--zhp-white);
  border-bottom: 1px solid var(--zhp-border);
}
.zhp-stat-item { text-align: center; }
.zhp-stat-val  { font-size: 1.4rem; font-weight: 700; color: var(--zhp-green); display: block; }
.zhp-stat-lbl  { font-size: .7rem; color: var(--zhp-text-muted); text-transform: uppercase; letter-spacing: .04em; }

/* === KOMUNIKATY === */
.zhp-msg-mine  { background: var(--zhp-green-light); border-radius: 12px 12px 4px 12px; padding: 10px 14px; margin: 6px 0 6px 40px; }
.zhp-msg-admin { background: #EBF4FF;                border-radius: 12px 12px 12px 4px; padding: 10px 14px; margin: 6px 40px 6px 0; }

/* === PLAN DNIA TIMELINE === */
.zhp-timeline { border-left: 3px solid var(--zhp-green-border); padding-left: 20px; }
.zhp-timeline-item {
  position: relative;
  padding: 10px 14px;
  background: var(--zhp-white);
  border: 1px solid var(--zhp-border);
  border-radius: var(--zhp-radius-sm);
  margin-bottom: 8px;
}
.zhp-timeline-item::before {
  content: '';
  position: absolute;
  left: -26px; top: 16px;
  width: 10px; height: 10px;
  border-radius: 50%;
  background: var(--zhp-green);
  border: 2px solid var(--zhp-white);
}
.zhp-time-label {
  font-size: .82rem;
  font-weight: 700;
  color: var(--zhp-green);
  margin-bottom: 2px;
}

/* === KARTY JADŁOSPISU === */
.zhp-meal-section { margin-bottom: 20px; }
.zhp-meal-title {
  font-size: .72rem;
  font-weight: 700;
  color: var(--zhp-text-muted);
  text-transform: uppercase;
  letter-spacing: .06em;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.zhp-meal-title::after { content: ''; flex: 1; height: 1px; background: var(--zhp-border); }
.zhp-meal-item {
  padding: 10px 14px;
  background: var(--zhp-white);
  border: 1px solid var(--zhp-border);
  border-radius: var(--zhp-radius-sm);
  margin-bottom: 4px;
}

/* === PROGNOZA POGODY === */
.zhp-weather-card {
  display: flex; align-items: center; gap: 20px;
  padding: 18px 20px;
  background: linear-gradient(135deg, #EBF5EE 0%, #D6EDE0 100%);
  border: 1px solid var(--zhp-green-border);
  border-radius: var(--zhp-radius);
  margin-bottom: 12px;
}
.zhp-weather-temp { font-size: 2.8rem; font-weight: 700; color: var(--zhp-green); line-height: 1; }
.zhp-forecast-grid { display: grid; grid-template-columns: repeat(5,1fr); gap: 6px; }
.zhp-forecast-day {
  text-align: center; padding: 8px 4px;
  background: var(--zhp-white); border: 1px solid var(--zhp-border);
  border-radius: var(--zhp-radius-sm); font-size: .78rem;
}
.zhp-forecast-icon { font-size: 1.4rem; }
</style>
```

---

## 01 – Ekran logowania

```html
<div class="zhp-login" x-data="bmLogin()" x-init="init()"
     style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--zhp-green);padding:20px;">

  <div style="width:100%;max-width:420px;">

    <!-- Logo / nagłówek -->
    <div style="text-align:center;margin-bottom:28px;">
      <div style="font-size:3rem;color:#fff;opacity:.9;margin-bottom:8px;">⚜</div>
      <h1 style="color:#fff;font-family:var(--zhp-font);font-size:1.4rem;font-weight:700;margin:0;">
        Panel Kadry Obozowej
      </h1>
      <p style="color:rgba(255,255,255,.7);font-size:.85rem;margin-top:4px;">
        Zaloguj się, aby uzyskać dostęp do panelu
      </p>
    </div>

    <!-- Karta logowania -->
    <div class="zhp-card">

      <!-- Krok 1: obóz -->
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

        <!-- Krok 2: osoba (pojawia się po wyborze obozu) -->
        <div class="zhp-field" x-show="campId && staffList.length" x-transition>
          <label class="zhp-label">Krok 2 – Wybierz siebie</label>
          <select class="zhp-select" x-model="staffId">
            <option value="">— wybierz osobę —</option>
            <template x-for="s in staffList" :key="s.id">
              <option :value="s.id" x-text="s.display_name + ' · ' + s.role"></option>
            </template>
          </select>
        </div>

        <!-- Krok 3: PIN -->
        <div class="zhp-field" x-show="staffId" x-transition>
          <label class="zhp-label">Krok 3 – Kod bezpieczeństwa (6 cyfr)</label>
          <input
            type="password"
            class="zhp-input"
            x-model="code"
            inputmode="numeric"
            maxlength="6"
            placeholder="●●●●●●"
            @keydown.enter="submit()"
            style="letter-spacing:.3em;font-size:1.2rem;text-align:center;"
          >
        </div>

        <!-- Błąd -->
        <div class="zhp-alert zhp-alert-urgent" x-show="error" x-transition>
          ⚠ <span x-text="error"></span>
        </div>

        <!-- Przycisk -->
        <button
          class="zhp-btn zhp-btn-primary"
          style="width:100%;justify-content:center;font-size:1rem;padding:13px;"
          @click="submit()"
          :disabled="loading || !campId || !staffId || !code"
        >
          <span x-show="loading" class="zhp-spinner"></span>
          <span x-text="loading ? 'Logowanie…' : 'Zaloguj się →'"></span>
        </button>
      </div>

    </div>

    <p style="text-align:center;color:rgba(255,255,255,.5);font-size:.75rem;margin-top:16px;">
      System Bazy Obozowej · ZHP
    </p>
  </div>
</div>
```

---

## 02 – Nagłówek panelu obozu

```html
<div class="zhp-panel" x-data="bmCamp()" x-init="init()">

  <!-- Loader -->
  <div class="zhp-loader" x-show="!camp">
    <div class="zhp-spinner"></div> Ładowanie danych obozu…
  </div>

  <!-- Hero obozu -->
  <template x-if="camp">
    <div>
      <div class="zhp-camp-hero" style="margin-bottom:0;border-radius:var(--zhp-radius) var(--zhp-radius) 0 0;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
          <div>
            <div style="font-size:.75rem;opacity:.7;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">
              ⚜ Obóz harcerski
            </div>
            <h2 x-text="camp.name" style="font-size:1.6rem;font-weight:700;color:#fff;margin:0 0 6px;"></h2>
            <div style="display:flex;gap:12px;flex-wrap:wrap;font-size:.85rem;opacity:.85;">
              <span>📅 <span x-text="camp.start_date"></span> – <span x-text="camp.end_date"></span></span>
            </div>
          </div>
          <!-- Status meldunku -->
          <div>
            <span
              x-show="submittedToday"
              style="background:rgba(255,255,255,.2);color:#fff;padding:6px 14px;border-radius:var(--zhp-radius-pill);font-size:.8rem;font-weight:600;">
              ✓ Meldunek złożony
            </span>
            <span
              x-show="!submittedToday"
              style="background:var(--zhp-gold);color:#fff;padding:6px 14px;border-radius:var(--zhp-radius-pill);font-size:.8rem;font-weight:600;">
              ⚠ Brak meldunku
            </span>
          </div>
        </div>
      </div>

      <!-- Pasek liczb -->
      <template x-if="latestCount">
        <div class="zhp-status-bar" style="border-radius:0 0 var(--zhp-radius) var(--zhp-radius);border:1px solid var(--zhp-border);border-top:none;">
          <div class="zhp-stat-item">
            <span class="zhp-stat-val" x-text="latestCount.participants ?? 0"></span>
            <span class="zhp-stat-lbl">Uczestnicy</span>
          </div>
          <div style="width:1px;background:var(--zhp-border);"></div>
          <div class="zhp-stat-item">
            <span class="zhp-stat-val" x-text="latestCount.staff ?? 0"></span>
            <span class="zhp-stat-lbl">Kadra</span>
          </div>
          <div style="width:1px;background:var(--zhp-border);"></div>
          <div class="zhp-stat-item">
            <span class="zhp-stat-val" x-text="latestCount.workers ?? 0"></span>
            <span class="zhp-stat-lbl">Pracownicy</span>
          </div>
          <div style="width:1px;background:var(--zhp-border);"></div>
          <div class="zhp-stat-item">
            <span class="zhp-stat-val" x-text="latestCount.total ?? 0"></span>
            <span class="zhp-stat-lbl">Łącznie</span>
          </div>
        </div>
      </template>
    </div>
  </template>

</div>
```

---

## 03 – Belka użytkownika i wylogowanie

```html
<div class="zhp-panel" x-data="bmLogout()"
     style="background:var(--zhp-white);border-bottom:1px solid var(--zhp-border);padding:10px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">

  <div style="display:flex;align-items:center;gap:10px;">
    <div style="width:34px;height:34px;border-radius:50%;background:var(--zhp-green-light);border:2px solid var(--zhp-green);display:flex;align-items:center;justify-content:center;font-size:1rem;color:var(--zhp-green);">
      ⚜
    </div>
    <div>
      <div style="font-weight:700;font-size:.9rem;color:var(--zhp-text);" x-text="$store.bm.displayName || 'Zalogowany'"></div>
      <div style="font-size:.75rem;color:var(--zhp-text-muted);" x-text="$store.bm.campName || ''"></div>
    </div>
  </div>

  <button class="zhp-btn zhp-btn-ghost zhp-btn-sm" @click="logout()">
    Wyloguj się ↩
  </button>

</div>
```

---

## 04 – Ogłoszenia

```html
<div class="zhp-panel" x-data="bmAnnouncements()">

  <!-- Nagłówek karty -->
  <div class="zhp-card">
    <div class="zhp-card-header" style="justify-content:space-between;">
      <h3>📢 Ogłoszenia</h3>
      <button class="zhp-btn zhp-btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:none;" @click="refresh()">
        ↻ Odśwież
      </button>
    </div>
    <div class="zhp-card-body" style="padding:16px;">

      <!-- Pilne -->
      <template x-for="ann in active.filter(a => a.is_urgent)" :key="ann.id">
        <div class="zhp-alert zhp-alert-urgent" style="flex-direction:column;align-items:flex-start;margin-bottom:10px;">
          <div style="display:flex;justify-content:space-between;width:100%;align-items:center;margin-bottom:4px;">
            <strong style="display:flex;align-items:center;gap:6px;">
              🚨 <span x-text="ann.title"></span>
            </strong>
            <span class="zhp-badge zhp-badge-red">PILNE</span>
          </div>
          <div x-html="ann.content" style="font-size:.9rem;color:var(--zhp-text);margin-top:4px;"></div>
          <div style="display:flex;justify-content:space-between;width:100%;margin-top:8px;font-size:.78rem;color:var(--zhp-red);">
            <span>Ważne do: <strong x-text="ann.valid_until || '—'"></strong></span>
            <a x-show="ann.attachment_url" :href="ann.attachment_url" target="_blank"
               style="color:var(--zhp-red);font-weight:600;">📎 Załącznik</a>
          </div>
        </div>
      </template>

      <!-- Zwykłe -->
      <template x-for="ann in active.filter(a => !a.is_urgent)" :key="ann.id">
        <div style="padding:14px;border:1px solid var(--zhp-border);border-radius:var(--zhp-radius-sm);margin-bottom:8px;background:var(--zhp-white);">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
            <strong style="color:var(--zhp-text);font-size:.95rem;" x-text="ann.title"></strong>
            <span class="zhp-badge zhp-badge-green">Aktywne</span>
          </div>
          <div x-html="ann.content" style="margin-top:8px;font-size:.88rem;color:var(--zhp-text-mid);"></div>
          <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:.78rem;color:var(--zhp-text-muted);">
            <span>Do: <span x-text="ann.valid_until || '—'"></span></span>
            <a x-show="ann.attachment_url" :href="ann.attachment_url" target="_blank"
               style="color:var(--zhp-green);font-weight:600;">📎 Załącznik</a>
          </div>
        </div>
      </template>

      <!-- Brak -->
      <div x-show="!active.length" style="text-align:center;padding:24px;color:var(--zhp-text-muted);">
        <div style="font-size:2rem;margin-bottom:8px;">📭</div>
        Brak aktywnych ogłoszeń.
      </div>

    </div>
  </div>

</div>
```

---

## 05 – Formularz nowego ogłoszenia

```html
<div class="zhp-panel">
  <div class="zhp-card">
    <div class="zhp-card-header"><h3>✏ Nowe ogłoszenie</h3></div>
    <form class="zhp-card-body" x-data="bmAnnForm()" @submit.prevent="submit()" style="padding:20px;">

      <div class="zhp-field">
        <label class="zhp-label">Tytuł *</label>
        <input type="text" class="zhp-input" x-model="title" required placeholder="Krótki, konkretny tytuł">
      </div>

      <div class="zhp-field">
        <label class="zhp-label">Treść</label>
        <textarea class="zhp-textarea" x-model="content" placeholder="Szczegółowy opis ogłoszenia…"></textarea>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="zhp-field">
          <label class="zhp-label">Ważne od *</label>
          <input type="date" class="zhp-input" x-model="valid_from" required>
        </div>
        <div class="zhp-field">
          <label class="zhp-label">Ważne do *</label>
          <input type="date" class="zhp-input" x-model="valid_until" required>
        </div>
      </div>

      <div class="zhp-field">
        <label class="zhp-label">URL załącznika</label>
        <input type="url" class="zhp-input" x-model="attachment_url" placeholder="https://…">
      </div>

      <div class="zhp-alert zhp-alert-ok"    x-show="success" x-text="success" x-transition></div>
      <div class="zhp-alert zhp-alert-urgent" x-show="error"  x-text="error"  x-transition></div>

      <button type="submit" class="zhp-btn zhp-btn-primary" :disabled="loading"
        style="width:100%;justify-content:center;">
        <span x-show="loading" class="zhp-spinner"></span>
        <span x-text="loading ? 'Wysyłanie…' : 'Wyślij do zatwierdzenia'"></span>
      </button>
    </form>
  </div>
</div>
```

---

## 06 – Meldunek dzienny

```html
<div class="zhp-panel" x-data="bmReports()" x-init="init()">
  <div class="zhp-card">
    <div class="zhp-card-header" style="justify-content:space-between;">
      <h3>📋 Meldunek dzienny</h3>
      <span class="zhp-badge"
        :class="today?.status === 'submitted' ? 'zhp-badge-green' : today?.status === 'draft' ? 'zhp-badge-gold' : 'zhp-badge-gray'"
        x-text="statusLabel || 'Nowy'">
      </span>
    </div>
    <div class="zhp-card-body">

      <div class="zhp-alert zhp-alert-ok" x-show="isSubmitted">
        ✓ Meldunek wysłany. Nie można już go modyfikować.
      </div>

      <fieldset :disabled="isSubmitted" style="border:none;padding:0;margin:0;">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:12px;">
          <div class="zhp-field">
            <label class="zhp-label">Uczestnicy</label>
            <input type="number" class="zhp-input" x-model.number="form.participants" min="0"
              style="text-align:center;font-size:1.4rem;font-weight:700;padding:12px 8px;">
          </div>
          <div class="zhp-field">
            <label class="zhp-label">Kadra</label>
            <input type="number" class="zhp-input" x-model.number="form.staff" min="0"
              style="text-align:center;font-size:1.4rem;font-weight:700;padding:12px 8px;">
          </div>
          <div class="zhp-field">
            <label class="zhp-label">Pracownicy</label>
            <input type="number" class="zhp-input" x-model.number="form.workers" min="0"
              style="text-align:center;font-size:1.4rem;font-weight:700;padding:12px 8px;">
          </div>
        </div>

        <!-- Suma -->
        <div style="background:var(--zhp-green-light);border:1px solid var(--zhp-green-border);border-radius:var(--zhp-radius-sm);padding:12px;text-align:center;margin-bottom:12px;">
          <span style="font-size:.8rem;color:var(--zhp-green);text-transform:uppercase;font-weight:700;">Łącznie</span>
          <div style="font-size:2rem;font-weight:700;color:var(--zhp-green);" x-text="total"></div>
        </div>

        <div class="zhp-field">
          <label class="zhp-label">Uwagi / notatki</label>
          <textarea class="zhp-textarea" x-model="form.notes" rows="2" placeholder="opcjonalnie…"></textarea>
        </div>
      </fieldset>

      <div class="zhp-alert zhp-alert-ok"     x-show="success" x-text="success" x-transition></div>
      <div class="zhp-alert zhp-alert-urgent"  x-show="error"  x-text="error"   x-transition></div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:4px;" x-show="!isSubmitted">
        <button class="zhp-btn zhp-btn-ghost" @click="saveDraft()" :disabled="loading">
          <span x-text="loading ? '…' : '💾 Zapisz roboczo'"></span>
        </button>
        <button class="zhp-btn zhp-btn-primary" @click="submit()" :disabled="loading">
          <span x-text="loading ? '…' : '✓ Wyślij meldunek'"></span>
        </button>
      </div>

      <!-- Historia -->
      <hr class="zhp-divider" style="margin-top:20px;">
      <details>
        <summary style="cursor:pointer;font-weight:700;color:var(--zhp-green);font-size:.9rem;outline:none;">
          📊 Historia meldunków (ostatnie 7 dni)
        </summary>
        <div style="margin-top:12px;overflow-x:auto;">
          <table class="zhp-table">
            <thead>
              <tr>
                <th>Data</th><th>Ucz.</th><th>Kadra</th><th>Prac.</th><th>Status</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="r in history.slice(0,7)" :key="r.id">
                <tr>
                  <td x-text="r.count_date"></td>
                  <td style="text-align:center;" x-text="r.participants"></td>
                  <td style="text-align:center;" x-text="r.staff"></td>
                  <td style="text-align:center;" x-text="r.workers"></td>
                  <td style="text-align:center;">
                    <span class="zhp-badge"
                      :class="r.status === 'submitted' ? 'zhp-badge-green' : r.status === 'draft' ? 'zhp-badge-gold' : 'zhp-badge-gray'"
                      x-text="r.status === 'submitted' ? '✓' : r.status === 'draft' ? 'roboczy' : '—'">
                    </span>
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
```

---

## 07 – Pogoda i ostrzeżenia IMGW

```html
<div class="zhp-panel" x-data="bmWeather()" x-init="init()">

  <div class="zhp-loader" x-show="loading"><div class="zhp-spinner"></div> Ładowanie pogody…</div>
  <div class="zhp-alert zhp-alert-urgent" x-show="error && !loading" x-text="error"></div>
  <div class="zhp-alert zhp-alert-warn" x-show="!configured && !loading">
    ⚠ Lokalizacja pogody nie skonfigurowana w ustawieniach.
  </div>

  <!-- Aktualna pogoda -->
  <template x-if="current">
    <div class="zhp-weather-card">
      <div style="font-size:3.5rem;line-height:1;" x-text="current.icon"></div>
      <div>
        <div class="zhp-weather-temp" x-text="current.temperature + '°C'"></div>
        <div style="font-weight:600;color:var(--zhp-text);margin-top:2px;" x-text="current.label"></div>
        <div style="font-size:.82rem;color:var(--zhp-text-mid);margin-top:4px;display:flex;gap:12px;">
          <span>💨 <span x-text="current.windspeed + ' km/h'"></span></span>
          <span>💧 <span x-text="current.humidity + '%'"></span></span>
        </div>
      </div>
    </div>
  </template>

  <!-- Prognoza 5-dniowa -->
  <div class="zhp-forecast-grid" style="margin-bottom:16px;">
    <template x-for="day in forecast" :key="day.date">
      <div class="zhp-forecast-day">
        <div style="color:var(--zhp-text-muted);margin-bottom:4px;" x-text="day.date.slice(5)"></div>
        <div class="zhp-forecast-icon" x-text="day.icon"></div>
        <div style="font-weight:700;color:var(--zhp-green);" x-text="day.temp_max + '°'"></div>
        <div style="color:var(--zhp-text-muted);" x-text="day.temp_min + '°'"></div>
      </div>
    </template>
  </div>

  <!-- Ostrzeżenia -->
  <template x-for="alert in alerts" :key="alert.id">
    <div :class="alert.is_urgent ? 'zhp-alert zhp-alert-urgent' : 'zhp-alert zhp-alert-warn'"
         style="flex-direction:column;align-items:flex-start;">
      <strong x-text="(alert.is_urgent ? '🚨 ' : '⚠️ ') + alert.title"></strong>
      <p x-text="alert.message" style="margin:4px 0 0;font-size:.85rem;"></p>
    </div>
  </template>

</div>
```

---

## 08 – Plan dnia

```html
<div class="zhp-panel" x-data="bmSchedule()" x-init="init()">
  <div class="zhp-card">
    <div class="zhp-card-header"><h3>🗓 Plan dnia</h3></div>
    <div class="zhp-card-body">

      <!-- Nawigacja daty -->
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
        <input type="date" class="zhp-input" x-model="selectedDate" @change="loadSchedule()"
          style="width:auto;padding:7px 12px;">
        <template x-for="d in availableDates" :key="d">
          <button
            @click="selectDate(d)"
            class="zhp-btn zhp-btn-sm"
            :class="d === selectedDate ? 'zhp-btn-primary' : 'zhp-btn-ghost'"
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
            style="color:var(--zhp-green);font-size:.9rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px;"></h4>
          <div class="zhp-timeline">
            <template x-for="item in plan.items" :key="item.id">
              <div class="zhp-timeline-item"
                   :style="item.item_status === 'cancelled' ? 'opacity:.5;background:#f9f9f9;' : ''">
                <div class="zhp-time-label"
                  x-text="item.time_from + (item.time_to ? ' – ' + item.time_to : '')">
                </div>
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                  <span :style="item.item_status === 'cancelled' ? 'text-decoration:line-through;' : 'font-weight:600;'"
                    x-text="item.title">
                  </span>
                  <span x-show="item.is_new_today"     class="zhp-badge zhp-badge-green" style="font-size:.65rem;">nowe</span>
                  <span x-show="item.is_updated_today" class="zhp-badge zhp-badge-gold"  style="font-size:.65rem;">zmienione</span>
                  <span x-show="item.is_mandatory"     class="zhp-badge zhp-badge-blue"  style="font-size:.65rem;">⚡ obowiązkowe</span>
                  <span x-show="item.item_status === 'cancelled'" class="zhp-badge zhp-badge-red" style="font-size:.65rem;">odwołane</span>
                </div>
                <p x-show="item.description" x-text="item.description"
                  style="margin:4px 0 0;font-size:.82rem;color:var(--zhp-text-mid);"></p>
                <p x-show="item.location" x-text="'📍 ' + item.location"
                  style="margin:2px 0 0;font-size:.78rem;color:var(--zhp-text-muted);"></p>
              </div>
            </template>
          </div>
        </div>
      </template>

    </div>
  </div>
</div>
```

---

## 09 – Rezerwacje

```html
<div class="zhp-panel" x-data="bmReservations()" x-init="init()">
  <div class="zhp-card">
    <div class="zhp-card-header">
      <h3>🏕 Rezerwacje zasobów</h3>
    </div>
    <div class="zhp-card-body">

      <div class="zhp-loader" x-show="loading"><div class="zhp-spinner"></div> Ładowanie…</div>
      <div class="zhp-alert zhp-alert-ok" x-show="success" x-text="success" x-transition></div>

      <!-- Lista zasobów -->
      <template x-if="!selectedResource">
        <div>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;">
            <template x-for="res in resources" :key="res.id">
              <div style="border:1px solid var(--zhp-border);border-radius:var(--zhp-radius);padding:16px;background:var(--zhp-white);">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                  <strong style="color:var(--zhp-text);" x-text="res.name"></strong>
                  <span class="zhp-badge zhp-badge-green" x-text="res.type"></span>
                </div>
                <p style="font-size:.8rem;color:var(--zhp-text-muted);margin-bottom:4px;"
                  x-text="'🕒 ' + res.available_from + ' – ' + res.available_to"></p>
                <p x-show="res.rules" x-text="res.rules"
                  style="font-size:.78rem;color:var(--zhp-text-muted);margin-bottom:10px;font-style:italic;"></p>
                <button class="zhp-btn zhp-btn-primary zhp-btn-sm" @click="openForm(res)">
                  Zarezerwuj →
                </button>
              </div>
            </template>
          </div>
          <p x-show="!loading && !resources.length" style="color:var(--zhp-text-muted);text-align:center;padding:20px;">
            Brak dostępnych zasobów.
          </p>
        </div>
      </template>

      <!-- Formularz rezerwacji -->
      <template x-if="selectedResource">
        <div style="background:var(--zhp-green-light);border:1px solid var(--zhp-green-border);border-radius:var(--zhp-radius);padding:20px;">
          <h4 style="color:var(--zhp-green);margin-bottom:16px;">
            Rezerwacja: <span x-text="selectedResource.name"></span>
          </h4>
          <form @submit.prevent="submitReservation()">
            <div class="zhp-field">
              <label class="zhp-label">Data *</label>
              <input type="date" class="zhp-input" x-model="form.res_date" @change="loadSlots()" required>
            </div>
            <template x-if="takenSlots.length">
              <div style="font-size:.8rem;color:var(--zhp-text-mid);margin-bottom:10px;">
                <strong>Zajęte:</strong>
                <template x-for="slot in takenSlots" :key="slot.start_time">
                  <span class="zhp-badge zhp-badge-red" style="margin-left:4px;"
                    x-text="slot.start_time.slice(0,5) + '–' + slot.end_time.slice(0,5)">
                  </span>
                </template>
              </div>
            </template>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
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
            <div style="display:grid;grid-template-columns:1fr auto;gap:10px;">
              <button type="submit" class="zhp-btn zhp-btn-primary" :disabled="loading"
                x-text="loading ? 'Wysyłanie…' : 'Zarezerwuj'">
              </button>
              <button type="button" class="zhp-btn zhp-btn-ghost" @click="selectedResource = null">Anuluj</button>
            </div>
          </form>
        </div>
      </template>

      <!-- Moje rezerwacje -->
      <hr class="zhp-divider" style="margin-top:20px;">
      <details>
        <summary style="cursor:pointer;font-weight:700;color:var(--zhp-green);font-size:.9rem;outline:none;">
          📋 Moje rezerwacje
        </summary>
        <div style="overflow-x:auto;margin-top:12px;">
          <table class="zhp-table">
            <thead>
              <tr><th>Zasób</th><th>Data</th><th>Godziny</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
              <template x-for="r in myReservations" :key="r.id">
                <tr>
                  <td x-text="r.resource_name || r.resource_id"></td>
                  <td x-text="r.res_date"></td>
                  <td x-text="r.start_time.slice(0,5) + '–' + r.end_time.slice(0,5)"></td>
                  <td>
                    <span class="zhp-badge"
                      :class="r.status === 'approved' ? 'zhp-badge-green' : r.status === 'pending' ? 'zhp-badge-gold' : 'zhp-badge-red'"
                      x-text="r.status_label || r.status">
                    </span>
                  </td>
                  <td>
                    <button x-show="r.status === 'pending'" @click="cancel(r.id)"
                      class="zhp-btn zhp-btn-danger zhp-btn-sm">Anuluj</button>
                  </td>
                </tr>
              </template>
              <tr x-show="!myReservations.length">
                <td colspan="5" style="text-align:center;color:var(--zhp-text-muted);padding:16px;">Brak rezerwacji.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </details>

    </div>
  </div>
</div>
```

---

## 10 – Jadłospis dzienny

```html
<div class="zhp-panel" x-data="bmMenu()" x-init="init()">
  <div class="zhp-card">
    <div class="zhp-card-header"><h3>🍽 Jadłospis</h3></div>
    <div class="zhp-card-body">

      <div class="zhp-loader" x-show="loading"><div class="zhp-spinner"></div> Ładowanie jadłospisu…</div>
      <div class="zhp-alert zhp-alert-urgent" x-show="error" x-text="error"></div>

      <!-- Wybór dnia -->
      <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;">
        <template x-for="d in availableDates" :key="d">
          <button @click="selectDate(d)" class="zhp-btn zhp-btn-sm"
            :class="d === selectedDate ? 'zhp-btn-primary' : 'zhp-btn-ghost'"
            x-text="d.slice(5)">
          </button>
        </template>
      </div>

      <p x-show="!loading && !day" style="color:var(--zhp-text-muted);text-align:center;padding:20px;">
        Brak jadłospisu na wybrany dzień.
      </p>

      <template x-if="day">
        <div>
          <p x-show="day.notes" x-text="day.notes"
            style="font-style:italic;color:var(--zhp-text-mid);margin-bottom:16px;padding:10px;background:var(--zhp-green-light);border-radius:var(--zhp-radius-sm);">
          </p>

          <template x-for="mealType in ['sniadanie','drugie_sniadanie','obiad','podwieczorek','kolacja','inne']" :key="mealType">
            <template x-if="day.items.filter(i => i.meal_type === mealType).length">
              <div class="zhp-meal-section">
                <div class="zhp-meal-title" x-text="mealTypeLabel(mealType)"></div>
                <template x-for="item in day.items.filter(i => i.meal_type === mealType)" :key="item.id">
                  <div class="zhp-meal-item">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                      <span x-show="item.time_from" x-text="item.time_from"
                        style="font-size:.78rem;color:var(--zhp-green);font-weight:700;"></span>
                      <strong x-text="item.title"></strong>
                      <span x-show="item.is_new_today"     class="zhp-badge zhp-badge-green" style="font-size:.65rem;">nowe</span>
                      <span x-show="item.is_updated_today" class="zhp-badge zhp-badge-gold"  style="font-size:.65rem;">zmienione</span>
                    </div>
                    <p x-show="item.description" x-text="item.description"
                      style="margin:4px 0 0;font-size:.82rem;color:var(--zhp-text-mid);"></p>
                    <p x-show="item.allergens" x-text="'⚠ Alergeny: ' + item.allergens"
                      style="margin:4px 0 0;font-size:.78rem;color:#7A5800;background:var(--zhp-gold-light);padding:3px 8px;border-radius:var(--zhp-radius-sm);display:inline-block;"></p>
                  </div>
                </template>
              </div>
            </template>
          </template>
        </div>
      </template>

    </div>
  </div>
</div>
```

---

## 11 – Jadłospis tygodniowy

```html
<div class="zhp-panel" x-data="bmMenu()" x-init="init()">
  <div class="zhp-card">
    <div class="zhp-card-header" style="justify-content:space-between;">
      <h3>🍽 Jadłospis</h3>
      <div style="display:flex;gap:4px;">
        <button @click="setViewMode('day')"  class="zhp-btn zhp-btn-sm"
          :class="viewMode==='day'  ? 'zhp-btn-primary' : ''"
          style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">Dzienny</button>
        <button @click="setViewMode('week')" class="zhp-btn zhp-btn-sm"
          :class="viewMode==='week' ? 'zhp-btn-primary' : ''"
          style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">Tygodniowy</button>
      </div>
    </div>
    <div class="zhp-card-body">

      <div class="zhp-loader" x-show="loading"><div class="zhp-spinner"></div> Ładowanie…</div>

      <!-- Widok tygodniowy -->
      <template x-if="viewMode === 'week'">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;">
          <template x-for="wday in weekDays" :key="wday.meal_date">
            <div style="border:1px solid var(--zhp-border);border-radius:var(--zhp-radius);overflow:hidden;">
              <div style="background:var(--zhp-green);color:#fff;padding:8px 12px;font-size:.82rem;font-weight:700;"
                x-text="wday.meal_date.slice(5)">
              </div>
              <div style="padding:10px;">
                <p x-show="!wday.items || !wday.items.length"
                  style="color:var(--zhp-text-muted);font-size:.8rem;text-align:center;">Brak</p>
                <template x-for="item in (wday.items || [])" :key="item.id">
                  <div style="font-size:.78rem;margin-bottom:5px;border-bottom:1px solid var(--zhp-border);padding-bottom:4px;">
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
```

---

## 12–14 – Komunikacja

Wszystkie trzy widoki (lista / nowy wątek / wątek) w jednym `x-data`.

```html
<div class="zhp-panel" x-data="bmConversations()" x-init="init()">
  <div class="zhp-card">

    <!-- WIDOK: lista wątków -->
    <template x-if="view === 'list'">
      <div>
        <div class="zhp-card-header" style="justify-content:space-between;">
          <h3>
            💬 Wiadomości
            <span x-show="unreadTotal > 0"
              class="zhp-badge zhp-badge-red"
              x-text="unreadTotal"
              style="margin-left:6px;">
            </span>
          </h3>
          <button class="zhp-btn zhp-btn-sm"
            style="background:rgba(255,255,255,.2);color:#fff;border:none;"
            @click="view = 'new'">
            + Nowy wątek
          </button>
        </div>
        <div class="zhp-card-body" style="padding:0;">
          <div class="zhp-loader" x-show="loading" style="padding:16px;">
            <div class="zhp-spinner"></div> Ładowanie…
          </div>
          <p x-show="!threads.length && !loading"
            style="text-align:center;color:var(--zhp-text-muted);padding:24px;">
            Brak wiadomości.
          </p>
          <template x-for="t in threads" :key="t.id">
            <div @click="openThread(t.id)"
              style="padding:14px 20px;border-bottom:1px solid var(--zhp-border);cursor:pointer;transition:background .15s;"
              :style="t.unread_camp > 0 ? 'border-left:4px solid var(--zhp-green);font-weight:600;background:var(--zhp-green-light);' : ''"
              @mouseenter="$el.style.background='#f9fafb'"
              @mouseleave="$el.style.background = t.unread_camp > 0 ? 'var(--zhp-green-light)' : ''">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                <span x-text="t.subject" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                <span x-show="t.unread_camp > 0"
                  class="zhp-badge zhp-badge-green"
                  x-text="t.unread_camp + ' nowych'">
                </span>
              </div>
              <div style="font-size:.78rem;color:var(--zhp-text-muted);margin-top:4px;display:flex;gap:8px;">
                <span class="zhp-badge zhp-badge-gray" style="font-size:.65rem;" x-text="t.status"></span>
                <span x-text="t.last_message_at"></span>
              </div>
            </div>
          </template>
        </div>
      </div>
    </template>

    <!-- WIDOK: nowy wątek -->
    <template x-if="view === 'new'">
      <div>
        <div class="zhp-card-header">
          <button class="zhp-btn zhp-btn-sm"
            style="background:rgba(255,255,255,.2);color:#fff;border:none;padding:4px 10px;"
            @click="view = 'list'">← Wróć</button>
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
            <textarea class="zhp-textarea" x-model="form.content" rows="5" required
              placeholder="Treść wiadomości…"></textarea>
          </div>
          <div class="zhp-alert zhp-alert-urgent" x-show="error"   x-text="error"   x-transition></div>
          <div class="zhp-alert zhp-alert-ok"     x-show="success" x-text="success" x-transition></div>
          <div style="display:grid;grid-template-columns:1fr auto;gap:10px;">
            <button class="zhp-btn zhp-btn-primary" @click="createThread()" :disabled="loading"
              x-text="loading ? 'Wysyłanie…' : '✓ Wyślij wiadomość'">
            </button>
            <button class="zhp-btn zhp-btn-ghost" @click="view = 'list'">Anuluj</button>
          </div>
        </div>
      </div>
    </template>

    <!-- WIDOK: wątek -->
    <template x-if="view === 'thread' && currentThread">
      <div>
        <div class="zhp-card-header" style="justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:10px;">
            <button class="zhp-btn zhp-btn-sm"
              style="background:rgba(255,255,255,.2);color:#fff;border:none;"
              @click="view = 'list'; currentThread = null;">← Lista</button>
            <h3 x-text="currentThread.subject" style="font-size:.95rem;"></h3>
          </div>
          <span class="zhp-badge"
            :class="currentThread.status === 'closed' ? 'zhp-badge-gray' : 'zhp-badge-green'"
            x-text="currentThread.status">
          </span>
        </div>
        <div class="zhp-card-body">
          <!-- Historia wiadomości -->
          <div style="max-height:360px;overflow-y:auto;display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
            <template x-for="msg in messages" :key="msg.id">
              <div :class="msg.author_type === 'admin' ? 'zhp-msg-admin' : 'zhp-msg-mine'">
                <div style="font-size:.72rem;color:var(--zhp-text-muted);margin-bottom:4px;display:flex;gap:6px;align-items:center;">
                  <strong x-text="msg.author_type === 'admin' ? '🏕 Administracja' : '👤 Ty'"></strong>
                  <span x-text="msg.created_at"></span>
                </div>
                <div x-html="msg.content" style="font-size:.9rem;"></div>
              </div>
            </template>
          </div>
          <!-- Formularz odpowiedzi -->
          <div x-show="currentThread.status !== 'closed' && currentThread.status !== 'archived'">
            <hr class="zhp-divider">
            <div class="zhp-field">
              <label class="zhp-label">Twoja odpowiedź</label>
              <textarea class="zhp-textarea" x-model="replyContent" rows="3"
                placeholder="Napisz odpowiedź…"></textarea>
            </div>
            <div class="zhp-alert zhp-alert-urgent" x-show="error" x-text="error"></div>
            <button class="zhp-btn zhp-btn-primary" @click="sendReply()" :disabled="loading || !replyContent"
              x-text="loading ? 'Wysyłanie…' : 'Wyślij odpowiedź ↩'">
            </button>
          </div>
          <div class="zhp-alert zhp-alert-warn" x-show="currentThread.status === 'closed'">
            Wątek zamknięty – nie możesz już odpowiadać.
          </div>
        </div>
      </div>
    </template>

  </div>
</div>
```

---

## 15–16 – Baza pomocy

```html
<div class="zhp-panel" x-data="bmHelp()" x-init="init()">
  <div class="zhp-card">

    <!-- LISTA artykułów -->
    <template x-if="!current">
      <div>
        <div class="zhp-card-header"><h3>📚 Baza pomocy</h3></div>
        <div class="zhp-card-body">

          <!-- Filtry -->
          <div style="display:grid;grid-template-columns:1fr auto;gap:8px;margin-bottom:12px;">
            <input type="text" class="zhp-input" x-model="search"
              @input.debounce.400ms="applyFilters()" placeholder="🔍 Szukaj w bazie pomocy…">
            <select class="zhp-select" x-model="filterType" @change="applyFilters()"
              style="width:auto;">
              <option value="">Wszystkie typy</option>
              <option value="article">Artykuł</option>
              <option value="faq">FAQ</option>
              <option value="contact">Kontakt</option>
              <option value="procedure">Procedura</option>
              <option value="instruction">Instrukcja</option>
            </select>
          </div>

          <!-- Kategorie -->
          <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;">
            <template x-for="cat in categories" :key="cat">
              <button @click="filterCat = filterCat === cat ? '' : cat; applyFilters()"
                class="zhp-btn zhp-btn-sm"
                :class="filterCat === cat ? 'zhp-btn-primary' : 'zhp-btn-ghost'"
                x-text="cat">
              </button>
            </template>
          </div>

          <div class="zhp-loader" x-show="loading"><div class="zhp-spinner"></div> Ładowanie…</div>

          <!-- Alarmowe -->
          <template x-for="art in alarmArticles" :key="art.id">
            <div @click="openArticle(art.id)"
              class="zhp-alert zhp-alert-urgent"
              style="cursor:pointer;flex-direction:column;align-items:flex-start;margin-bottom:8px;">
              <strong>🚨 <span x-text="art.title"></span></strong>
              <p x-text="art.excerpt" style="margin:4px 0 0;font-size:.85rem;"></p>
            </div>
          </template>

          <!-- Przypięte -->
          <template x-for="art in pinnedArticles" :key="art.id">
            <div @click="openArticle(art.id)"
              style="border:1px solid var(--zhp-gold);border-left:4px solid var(--zhp-gold);padding:12px;margin-bottom:8px;border-radius:var(--zhp-radius-sm);cursor:pointer;background:var(--zhp-gold-light);">
              <strong>📌 <span x-text="art.title"></span></strong>
              <p x-text="art.excerpt" style="margin:4px 0 0;font-size:.85rem;color:var(--zhp-text-mid);"></p>
            </div>
          </template>

          <!-- Pozostałe -->
          <template x-for="art in articles.filter(a => !a.is_alarm && !a.is_pinned)" :key="art.id">
            <div @click="openArticle(art.id)"
              style="border:1px solid var(--zhp-border);padding:12px;margin-bottom:6px;border-radius:var(--zhp-radius-sm);cursor:pointer;transition:border-color .15s;"
              @mouseenter="$el.style.borderColor='var(--zhp-green)'"
              @mouseleave="$el.style.borderColor='var(--zhp-border)'">
              <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                <span style="display:flex;align-items:center;gap:6px;">
                  <span x-text="art.type === 'faq' ? '❓' : art.type === 'contact' ? '📞' : art.type === 'procedure' ? '⚙' : '📄'"></span>
                  <strong x-text="art.title"></strong>
                </span>
                <span class="zhp-badge zhp-badge-gray" x-text="art.category"></span>
              </div>
              <p x-text="art.excerpt" style="margin:4px 0 0;font-size:.85rem;color:var(--zhp-text-mid);"></p>
            </div>
          </template>

          <p x-show="!loading && !articles.length" style="color:var(--zhp-text-muted);text-align:center;padding:20px;">
            Brak wyników.
          </p>
        </div>
      </div>
    </template>

    <!-- PODGLĄD artykułu -->
    <template x-if="current">
      <div>
        <div class="zhp-card-header">
          <button class="zhp-btn zhp-btn-sm"
            style="background:rgba(255,255,255,.2);color:#fff;border:none;"
            @click="closeArticle()">← Wróć</button>
          <h3 x-text="current.title" style="margin-left:8px;font-size:.95rem;"></h3>
        </div>
        <div class="zhp-card-body">
          <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
            <span class="zhp-badge zhp-badge-green" x-text="current.type || 'artykuł'"></span>
            <span class="zhp-badge zhp-badge-gray"  x-text="current.category" x-show="current.category"></span>
          </div>
          <div x-html="current.content" style="line-height:1.7;font-size:.92rem;"></div>
        </div>
      </div>
    </template>

  </div>
</div>
```

---

## 17–20 – Formularze i zgłoszenia

```html
<div class="zhp-panel" x-data="bmForms()" x-init="init()">
  <div class="zhp-card">

    <!-- LISTA formularzy -->
    <template x-if="view === 'list'">
      <div>
        <div class="zhp-card-header"><h3>📝 Formularze</h3></div>
        <div class="zhp-card-body" style="padding:0;">
          <div class="zhp-loader" x-show="loading" style="padding:16px;">
            <div class="zhp-spinner"></div> Ładowanie…
          </div>
          <p x-show="!loading && !forms.length"
            style="text-align:center;color:var(--zhp-text-muted);padding:24px;">Brak dostępnych formularzy.</p>
          <template x-for="form in forms" :key="form.id">
            <div @click="openForm(form.id)"
              style="padding:14px 20px;border-bottom:1px solid var(--zhp-border);cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:12px;transition:background .15s;"
              @mouseenter="$el.style.background='var(--zhp-green-light)'"
              @mouseleave="$el.style.background=''">
              <div>
                <strong x-text="form.name" style="color:var(--zhp-text);"></strong>
                <p x-show="form.description" x-text="form.description"
                  style="font-size:.82rem;color:var(--zhp-text-muted);margin-top:2px;"></p>
              </div>
              <span style="color:var(--zhp-green);font-size:1.2rem;">→</span>
            </div>
          </template>
        </div>
      </div>
    </template>

    <!-- WYPEŁNIANIE formularza -->
    <template x-if="view === 'form' && currentForm">
      <div>
        <div class="zhp-card-header">
          <button class="zhp-btn zhp-btn-sm"
            style="background:rgba(255,255,255,.2);color:#fff;border:none;"
            @click="view = 'list'; currentForm = null;">← Wróć</button>
          <h3 x-text="currentForm.name" style="margin-left:8px;font-size:.95rem;"></h3>
        </div>
        <form class="zhp-card-body" @submit.prevent="submitForm()">
          <p x-show="currentForm.description" x-text="currentForm.description"
            style="color:var(--zhp-text-mid);font-size:.9rem;margin-bottom:16px;"></p>

          <template x-for="field in currentForm.fields" :key="field.id">
            <div class="zhp-field">
              <label class="zhp-label">
                <span x-text="field.label"></span>
                <span x-show="field.required" style="color:var(--zhp-red);margin-left:2px;">*</span>
              </label>

              <!-- text / email / number / tel / url / date -->
              <template x-if="['text','email','number','tel','url','date'].includes(field.type)">
                <input :type="field.type" class="zhp-input"
                  x-model="answers[field.id]"
                  :required="field.required"
                  :placeholder="field.placeholder || ''">
              </template>

              <!-- textarea -->
              <template x-if="field.type === 'textarea'">
                <textarea class="zhp-textarea" x-model="answers[field.id]"
                  :required="field.required"
                  :placeholder="field.placeholder || ''">
                </textarea>
              </template>

              <!-- select -->
              <template x-if="field.type === 'select'">
                <select class="zhp-select" x-model="answers[field.id]" :required="field.required">
                  <option value="">— wybierz —</option>
                  <template x-for="opt in field.options_list" :key="opt">
                    <option :value="opt" x-text="opt"></option>
                  </template>
                </select>
              </template>

              <!-- checkbox -->
              <template x-if="field.type === 'checkbox'">
                <div style="display:flex;flex-direction:column;gap:6px;margin-top:4px;">
                  <template x-for="opt in field.options_list" :key="opt">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                      <input type="checkbox" :value="opt"
                        @change="toggleCheckbox(field.id, opt)"
                        :checked="(answers[field.id] || []).includes(opt)"
                        style="width:16px;height:16px;accent-color:var(--zhp-green);">
                      <span x-text="opt"></span>
                    </label>
                  </template>
                </div>
              </template>

              <!-- radio -->
              <template x-if="field.type === 'radio'">
                <div style="display:flex;flex-direction:column;gap:6px;margin-top:4px;">
                  <template x-for="opt in field.options_list" :key="opt">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                      <input type="radio" :name="'field_' + field.id" :value="opt"
                        x-model="answers[field.id]"
                        style="width:16px;height:16px;accent-color:var(--zhp-green);">
                      <span x-text="opt"></span>
                    </label>
                  </template>
                </div>
              </template>

              <!-- file -->
              <template x-if="field.type === 'file'">
                <input type="file" class="zhp-input" @change="handleFile(field.id, $event)"
                  :required="field.required" style="padding:8px;">
              </template>

            </div>
          </template>

          <div class="zhp-alert zhp-alert-ok"     x-show="success" x-text="success" x-transition></div>
          <div class="zhp-alert zhp-alert-urgent"  x-show="error"  x-text="error"   x-transition></div>

          <button type="submit" class="zhp-btn zhp-btn-primary" :disabled="loading"
            style="width:100%;justify-content:center;">
            <span x-show="loading" class="zhp-spinner"></span>
            <span x-text="loading ? 'Wysyłanie…' : '✓ Wyślij formularz'"></span>
          </button>
        </form>
      </div>
    </template>

    <!-- LISTA zgłoszeń -->
    <template x-if="view === 'submissions'">
      <div>
        <div class="zhp-card-header" style="justify-content:space-between;">
          <h3>📋 Moje zgłoszenia</h3>
          <button class="zhp-btn zhp-btn-sm"
            style="background:rgba(255,255,255,.2);color:#fff;border:none;"
            @click="view = 'list'">← Formularze</button>
        </div>
        <div class="zhp-card-body" style="padding:0;">
          <template x-for="sub in submissions" :key="sub.id">
            <div @click="openSubmission(sub.id)"
              style="padding:14px 20px;border-bottom:1px solid var(--zhp-border);cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:12px;"
              @mouseenter="$el.style.background='var(--zhp-green-light)'"
              @mouseleave="$el.style.background=''">
              <div>
                <strong x-text="sub.form_name" style="font-size:.9rem;"></strong>
                <div style="font-size:.78rem;color:var(--zhp-text-muted);margin-top:2px;" x-text="sub.submitted_at"></div>
              </div>
              <span class="zhp-badge"
                :class="sub.status === 'accepted' ? 'zhp-badge-green' : sub.status === 'rejected' ? 'zhp-badge-red' : 'zhp-badge-gold'"
                x-text="sub.status_label || sub.status">
              </span>
            </div>
          </template>
          <p x-show="!submissions.length"
            style="text-align:center;color:var(--zhp-text-muted);padding:24px;">Brak zgłoszeń.</p>
        </div>
      </div>
    </template>

    <!-- SZCZEGÓŁY zgłoszenia -->
    <template x-if="view === 'submission' && currentSubmission">
      <div>
        <div class="zhp-card-header">
          <button class="zhp-btn zhp-btn-sm"
            style="background:rgba(255,255,255,.2);color:#fff;border:none;"
            @click="view = 'submissions'">← Wróć</button>
          <h3 x-text="currentSubmission.form_name" style="margin-left:8px;font-size:.95rem;"></h3>
        </div>
        <div class="zhp-card-body">
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
            <span class="zhp-badge"
              :class="currentSubmission.status === 'accepted' ? 'zhp-badge-green' : currentSubmission.status === 'rejected' ? 'zhp-badge-red' : 'zhp-badge-gold'"
              x-text="currentSubmission.status_label || currentSubmission.status">
            </span>
            <span class="zhp-badge zhp-badge-gray" x-text="'Wysłano: ' + currentSubmission.submitted_at"></span>
          </div>

          <template x-for="field in currentSubmission.fields" :key="field.field_id">
            <div style="margin-bottom:12px;padding:10px 14px;background:var(--zhp-bg);border-radius:var(--zhp-radius-sm);">
              <div style="font-size:.75rem;font-weight:700;color:var(--zhp-text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;"
                x-text="field.label">
              </div>
              <div style="font-size:.92rem;color:var(--zhp-text);" x-text="field.value || '—'"></div>
              <a x-show="field.attachment_url" :href="field.attachment_url" target="_blank"
                class="zhp-btn zhp-btn-ghost zhp-btn-sm" style="margin-top:6px;">
                📎 Pobierz załącznik
              </a>
            </div>
          </template>

          <template x-if="currentSubmission.admin_comment">
            <div class="zhp-alert zhp-alert-info" style="margin-top:8px;flex-direction:column;align-items:flex-start;">
              <strong style="margin-bottom:4px;">Komentarz administratora:</strong>
              <span x-text="currentSubmission.admin_comment"></span>
            </div>
          </template>
        </div>
      </div>
    </template>

  </div>
</div>
```

---

## 21 – Wrapper ochrony sesji

Użyj jako zewnętrzny kontener dla wszystkich elementów panelu. Pokazuje panel tylko zalogowanym.

```html
<div x-data x-cloak>

  <!-- Niezalogowany: pokazuje ekran logowania -->
  <div x-show="!$store.bm.authenticated">
    <!-- tu wklej Element 01 – Ekran logowania -->
  </div>

  <!-- Zalogowany: właściwy panel -->
  <div x-show="$store.bm.authenticated" x-transition>

    <!-- Element 03 – belka użytkownika -->

    <!-- Nawigacja główna -->
    <nav style="background:var(--zhp-white);border-bottom:1px solid var(--zhp-border);padding:0 16px;overflow-x:auto;">
      <div class="zhp-tabs" style="margin-bottom:0;white-space:nowrap;">
        <button class="zhp-tab active" onclick="showSection('panel')">📊 Panel</button>
        <button class="zhp-tab" onclick="showSection('ogloszenia')">📢 Ogłoszenia</button>
        <button class="zhp-tab" onclick="showSection('meldunek')">📋 Meldunek</button>
        <button class="zhp-tab" onclick="showSection('plan')">🗓 Plan dnia</button>
        <button class="zhp-tab" onclick="showSection('jadlospis')">🍽 Jadłospis</button>
        <button class="zhp-tab" onclick="showSection('rezerwacje')">🏕 Rezerwacje</button>
        <button class="zhp-tab" onclick="showSection('wiadomosci')">💬 Wiadomości</button>
        <button class="zhp-tab" onclick="showSection('pomoc')">📚 Pomoc</button>
      </div>
    </nav>

    <div style="padding:16px;max-width:900px;margin:0 auto;">
      <!-- tu wklejasz poszczególne elementy UI -->
    </div>
  </div>
</div>

<script>
function showSection(id) {
  document.querySelectorAll('.zhp-tab').forEach(t => t.classList.remove('active'));
  event.target.classList.add('active');
  // Opcjonalnie: pokaż/ukryj sekcje
}
</script>
```

---

## 22 – Licznik nieprzeczytanych

Ikona z licznikiem do wklejenia w nawigacji lub headerze.

```html
<!-- Samodzielna odznaka licznika -->
<span x-data style="position:relative;display:inline-block;">
  <span style="font-size:1.3rem;">💬</span>
  <span
    x-show="$store.bm.unreadCount > 0"
    x-text="$store.bm.unreadCount"
    class="zhp-badge zhp-badge-red"
    style="position:absolute;top:-6px;right:-10px;min-width:18px;height:18px;display:flex;align-items:center;justify-content:center;padding:0 4px;font-size:.65rem;">
  </span>
</span>

<!-- Przycisk zakładki z licznikiem (do paska nawigacji) -->
<button class="zhp-tab" onclick="showSection('wiadomosci')" style="position:relative;">
  💬 Wiadomości
  <span
    x-data
    x-show="$store.bm.unreadCount > 0"
    x-text="$store.bm.unreadCount"
    class="zhp-badge zhp-badge-red"
    style="margin-left:6px;">
  </span>
</button>
```

---

## Wskazówki integracji z Breakdance Studio

1. **Blok 00 (CSS)** → wklej w `Breakdance → Global → Head Code` lub jako `Code Block` na szczycie strony.
2. **Każdy element** → wklej w `Custom Element` lub `Code Block` w Breakdance Studio.
3. **Dodaj `x-cloak` CSS** do globalnego CSS:
   ```css
   [x-cloak] { display: none !important; }
   ```
4. **Alpine.js i bmConfig** są ładowane automatycznie przez plugin – nie musisz ich dodawać ręcznie.
5. **Kolory ZHP** możesz nadpisać lokalnie przez `--zhp-green: #twoj-kolor;` na dowolnym elemencie nadrzędnym.
6. **Responsywność** – wszystkie bloki używają grid auto-fill i flex-wrap, działają na mobile bez zmian.

---

*Styl inspirowany identyfikacją wizualną ZHP (zhp.pl). Paleta kolorów: zieleń `#1B5E33`, akcent czerwony `#C0392B`, złoty `#D4A017`.*
