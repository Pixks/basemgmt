# 01. Logowanie kadry obozowej

Samodzielny ekran logowania do panelu obozowego. Element odwzorowuje pierwszy blok z panelu pełnego i korzysta z trzystopniowego przepływu: obóz → osoba kadry → kod bezpieczeństwa.

## 1. Nazwa elementu w Breakdance
`BM 01 / Logowanie kadry obozowej`

## 2. Kontrolki Element Studio

| Zakładka | Typ | ID | Domyślna wartość | Zastosowanie |
|---|---|---|---|---|
| Content | Text | `content.heading` | `Panel Kadry Obozowej` | Nagłówek ekranu logowania. |
| Content | Textarea | `content.lead` | `Zaloguj się, aby uzyskać dostęp do swojego obozu.` | Krótki opis pod nagłówkiem. |
| Content | Text | `content.login_label` | `Zaloguj się →` | Etykieta głównego przycisku logowania. |
| Design | Color | `design.primary_color` | `#1B5E33` | Główny zielony kolor ZHP używany w nagłówkach, CTA i akcentach. |
| Design | Color | `design.primary_hover_color` | `#2A7A4B` | Kolor hover / drugi odcień zieleni w gradientach i aktywnych stanach. |
| Design | Color | `design.surface_color` | `#FFFFFF` | Tło kart i kontenerów elementu. |
| Design | Color | `design.border_color` | `#D0D8DC` | Obramowania kart, pól i tabel. |
| Design | Number | `design.radius` | `8` | Promień zaokrąglenia wszystkich kart, pól i chipów. |

## 3. Twig

```twig
<div class="bm-el" x-data="bmLogin()" x-init="init()" x-cloak x-show="!$store.bm.authenticated">
  <div class="bm-login-screen">
    <div class="bm-login-shell">
      <div class="bm-login-brand">
        <div class="bm-login-mark">⚜</div>
        <h2>{{ content.heading }}</h2>
        <p>{{ content.lead }}</p>
      </div>

      <div class="bm-card">
        <div class="bm-card-body">
          <div class="bm-field">
            <label class="bm-label">Krok 1 – wybierz obóz</label>
            <select class="bm-select" x-model="campId" @change="loadStaff()">
              <option value="">— wybierz obóz —</option>
              <template x-for="c in camps" :key="c.id">
                <option :value="c.id" x-text="c.name"></option>
              </template>
            </select>
          </div>

          <div class="bm-field" x-show="campId && staffList.length">
            <label class="bm-label">Krok 2 – wybierz siebie</label>
            <select class="bm-select" x-model="staffId">
              <option value="">— wybierz osobę —</option>
              <template x-for="s in staffList" :key="s.id">
                <option :value="s.id" x-text="s.display_name"></option>
              </template>
            </select>
          </div>

          <div class="bm-field" x-show="staffId">
            <label class="bm-label">Krok 3 – kod bezpieczeństwa (6 cyfr)</label>
            <input
              type="password"
              class="bm-input"
              x-model="code"
              inputmode="numeric"
              maxlength="6"
              placeholder="●●●●●●"
              @keydown.enter="submit()"
              style="letter-spacing:.35em;font-size:1.15rem;text-align:center;"
            >
          </div>

          <div class="bm-alert bm-alert--error" x-show="error">
            <span>⚠</span>
            <span x-text="error"></span>
          </div>

          <button
            class="bm-btn bm-btn--primary bm-btn--block"
            @click="submit()"
            :disabled="loading || !campId || !staffId || !code"
          >
            <span x-show="loading" class="bm-spinner"></span>
            <span x-text="loading ? 'Logowanie…' : '{{ content.login_label }}'"></span>
          </button>
        </div>
      </div>

      <div class="bm-login-foot">System Bazy Obozowej · ZHP</div>
    </div>
  </div>
</div>
```

## 4. CSS

```css
%%SELECTOR%% {
  --bm-primary: {{ design.primary_color }};
  --bm-primary-hover: {{ design.primary_hover_color }};
  --bm-surface: {{ design.surface_color }};
  --bm-border: {{ design.border_color }};
  --bm-radius: {{ design.radius }}px;
  --bm-bg: #F6F8FA;
  --bm-text: #1A2530;
  --bm-muted: #8A96A1;
  --bm-success: #1B5E33;
  --bm-success-bg: #EBF5EE;
  --bm-danger: #C0392B;
  --bm-danger-bg: #FDECEA;
  --bm-warn: #D4A017;
  --bm-warn-bg: #FEF8E7;
  --bm-info: #1A5494;
  --bm-info-bg: #EBF4FF;
  --bm-shadow: 0 2px 10px rgba(27,94,51,.12);
  display: block;
  font-family: Lato, 'Open Sans', system-ui, sans-serif;
  color: var(--bm-text);
}
%%SELECTOR%% [x-cloak] { display: none !important; }
%%SELECTOR%% .bm-el,
%%SELECTOR%% .bm-el * { box-sizing: border-box; }
%%SELECTOR%% .bm-el {
  background: var(--bm-bg);
  font-size: 15px;
  line-height: 1.55;
}
%%SELECTOR%% .bm-card {
  background: var(--bm-surface);
  border: 1px solid var(--bm-border);
  border-radius: var(--bm-radius);
  box-shadow: var(--bm-shadow);
  overflow: hidden;
}
%%SELECTOR%% .bm-card + .bm-card { margin-top: 16px; }
%%SELECTOR%% .bm-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 18px;
  background: var(--bm-primary);
  color: #fff;
}
%%SELECTOR%% .bm-card-header h2,
%%SELECTOR%% .bm-card-header h3,
%%SELECTOR%% .bm-card-header h4 {
  margin: 0;
  color: #fff;
  font-size: 1rem;
  font-weight: 700;
}
%%SELECTOR%% .bm-card-body { padding: 18px; }
%%SELECTOR%% .bm-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}
%%SELECTOR%% .bm-grid-2 {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}
%%SELECTOR%% .bm-grid-3 {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}
%%SELECTOR%% .bm-grid-auto {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 12px;
}
@media (max-width: 767px) {
  %%SELECTOR%% .bm-grid-2,
  %%SELECTOR%% .bm-grid-3 { grid-template-columns: 1fr; }
}
%%SELECTOR%% .bm-field { margin-bottom: 14px; }
%%SELECTOR%% .bm-label {
  display: block;
  margin-bottom: 5px;
  font-size: .78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
  color: #4B5A67;
}
%%SELECTOR%% .bm-input,
%%SELECTOR%% .bm-select,
%%SELECTOR%% .bm-textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1.5px solid var(--bm-border);
  border-radius: calc(var(--bm-radius) - 3px);
  background: #fff;
  color: var(--bm-text);
  font: inherit;
  outline: none;
  transition: border-color .15s, box-shadow .15s;
}
%%SELECTOR%% .bm-input:focus,
%%SELECTOR%% .bm-select:focus,
%%SELECTOR%% .bm-textarea:focus {
  border-color: var(--bm-primary);
  box-shadow: 0 0 0 3px rgba(27,94,51,.12);
}
%%SELECTOR%% .bm-textarea {
  resize: vertical;
  min-height: 88px;
}
%%SELECTOR%% .bm-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 9px 16px;
  border: none;
  border-radius: 999px;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  transition: transform .1s, background .15s, color .15s;
}
%%SELECTOR%% .bm-btn:active { transform: scale(.98); }
%%SELECTOR%% .bm-btn:disabled { opacity: .55; cursor: not-allowed; }
%%SELECTOR%% .bm-btn--primary { background: var(--bm-primary); color: #fff; }
%%SELECTOR%% .bm-btn--primary:hover { background: var(--bm-primary-hover); }
%%SELECTOR%% .bm-btn--ghost {
  background: transparent;
  color: var(--bm-primary);
  border: 1.5px solid var(--bm-primary);
}
%%SELECTOR%% .bm-btn--ghost:hover { background: #EBF5EE; }
%%SELECTOR%% .bm-btn--danger { background: var(--bm-danger); color: #fff; }
%%SELECTOR%% .bm-btn--light {
  background: rgba(255,255,255,.18);
  color: #fff;
  border: 1px solid rgba(255,255,255,.35);
}
%%SELECTOR%% .bm-btn--sm { padding: 6px 12px; font-size: .82rem; }
%%SELECTOR%% .bm-btn--block { width: 100%; }
%%SELECTOR%% .bm-alert {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  padding: 12px 14px;
  border-radius: calc(var(--bm-radius) - 3px);
  margin-bottom: 12px;
  font-size: .9rem;
}
%%SELECTOR%% .bm-alert--success {
  background: var(--bm-success-bg);
  border-left: 4px solid var(--bm-success);
  color: var(--bm-success);
}
%%SELECTOR%% .bm-alert--error {
  background: var(--bm-danger-bg);
  border-left: 4px solid var(--bm-danger);
  color: var(--bm-danger);
}
%%SELECTOR%% .bm-alert--warn {
  background: var(--bm-warn-bg);
  border-left: 4px solid var(--bm-warn);
  color: #7A5800;
}
%%SELECTOR%% .bm-alert--info {
  background: var(--bm-info-bg);
  border-left: 4px solid var(--bm-info);
  color: var(--bm-info);
}
%%SELECTOR%% .bm-badge {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .03em;
}
%%SELECTOR%% .bm-badge--green { background: #EBF5EE; color: #1B5E33; }
%%SELECTOR%% .bm-badge--red { background: #FDECEA; color: #C0392B; }
%%SELECTOR%% .bm-badge--gold { background: #FEF8E7; color: #7A5800; }
%%SELECTOR%% .bm-badge--gray { background: #EAECEE; color: #4B5A67; }
%%SELECTOR%% .bm-badge--blue { background: #EBF4FF; color: #1A5494; }
%%SELECTOR%% .bm-loader {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 0;
  color: var(--bm-muted);
  font-size: .9rem;
}
%%SELECTOR%% .bm-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid #A8D5B5;
  border-top-color: var(--bm-primary);
  border-radius: 50%;
  animation: bm-spin .65s linear infinite;
  flex: 0 0 auto;
}
@keyframes bm-spin { to { transform: rotate(360deg); } }
%%SELECTOR%% .bm-empty {
  text-align: center;
  color: var(--bm-muted);
  padding: 20px 0;
}
%%SELECTOR%% .bm-list-item {
  padding: 12px 14px;
  border: 1px solid var(--bm-border);
  border-radius: calc(var(--bm-radius) - 3px);
  background: #fff;
  margin-bottom: 8px;
}
%%SELECTOR%% .bm-list-item:last-child { margin-bottom: 0; }
%%SELECTOR%% .bm-soft { background: #F6F8FA; }
%%SELECTOR%% .bm-meta { font-size: .78rem; color: var(--bm-muted); }
%%SELECTOR%% .bm-table-wrap { overflow-x: auto; }
%%SELECTOR%% .bm-table {
  width: 100%;
  border-collapse: collapse;
  font-size: .87rem;
}
%%SELECTOR%% .bm-table th {
  padding: 8px 10px;
  background: #EBF5EE;
  color: #1B5E33;
  text-align: left;
  font-size: .74rem;
  text-transform: uppercase;
  letter-spacing: .04em;
}
%%SELECTOR%% .bm-table td {
  padding: 9px 10px;
  border-bottom: 1px solid var(--bm-border);
}
%%SELECTOR%% .bm-table tr:last-child td { border-bottom: none; }
%%SELECTOR%% .bm-hero {
  padding: 22px 24px;
  border-radius: var(--bm-radius) var(--bm-radius) 0 0;
  background: linear-gradient(135deg, var(--bm-primary) 0%, var(--bm-primary-hover) 100%);
  color: #fff;
  position: relative;
  overflow: hidden;
}
%%SELECTOR%% .bm-hero::after {
  content: '⚜';
  position: absolute;
  right: 18px;
  top: 10px;
  font-size: 3rem;
  opacity: .12;
}
%%SELECTOR%% .bm-status-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  padding: 12px 18px;
  background: #fff;
  border: 1px solid var(--bm-border);
  border-top: none;
  border-radius: 0 0 var(--bm-radius) var(--bm-radius);
}
%%SELECTOR%% .bm-stat { min-width: 72px; text-align: center; }
%%SELECTOR%% .bm-stat strong {
  display: block;
  font-size: 1.4rem;
  color: var(--bm-primary);
}
%%SELECTOR%% .bm-stat span {
  font-size: .72rem;
  color: var(--bm-muted);
  text-transform: uppercase;
  letter-spacing: .04em;
}
%%SELECTOR%% .bm-sep { width: 1px; background: var(--bm-border); }
%%SELECTOR%% .bm-forecast {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(88px, 1fr));
  gap: 8px;
}
%%SELECTOR%% .bm-forecast-day {
  text-align: center;
  padding: 8px 6px;
  border: 1px solid var(--bm-border);
  border-radius: calc(var(--bm-radius) - 3px);
  background: #fff;
}
%%SELECTOR%% .bm-timeline {
  border-left: 3px solid #A8D5B5;
  padding-left: 16px;
}
%%SELECTOR%% .bm-timeline-item {
  position: relative;
  padding: 10px 12px;
  border: 1px solid var(--bm-border);
  border-radius: calc(var(--bm-radius) - 3px);
  background: #fff;
  margin-bottom: 8px;
}
%%SELECTOR%% .bm-timeline-item::before {
  content: '';
  position: absolute;
  left: -22px;
  top: 14px;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: var(--bm-primary);
  border: 2px solid #F6F8FA;
}
%%SELECTOR%% .bm-message-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 360px;
  overflow: auto;
  margin-bottom: 14px;
}
%%SELECTOR%% .bm-message {
  padding: 10px 12px;
  border-radius: 12px;
  font-size: .9rem;
}
%%SELECTOR%% .bm-message--mine {
  margin-left: 30px;
  background: #EBF5EE;
  border-radius: 12px 12px 4px 12px;
}
%%SELECTOR%% .bm-message--admin {
  margin-right: 30px;
  background: #EBF4FF;
  border-radius: 12px 12px 12px 4px;
}
%%SELECTOR%% .bm-pills {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}
%%SELECTOR%% .bm-note {
  padding: 10px 12px;
  background: #F6F8FA;
  border-radius: calc(var(--bm-radius) - 3px);
}
%%SELECTOR%% .bm-doc-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  background: #F6F8FA;
  border-radius: calc(var(--bm-radius) - 3px);
  margin-bottom: 8px;
  flex-wrap: wrap;
}
%%SELECTOR%% .bm-resource {
  padding: 14px;
  border: 1px solid var(--bm-border);
  border-radius: var(--bm-radius);
  background: #fff;
}
%%SELECTOR%% .bm-split {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
}

%%SELECTOR%% .bm-login-screen {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: linear-gradient(180deg, var(--bm-primary) 0%, var(--bm-primary-hover) 100%);
}
%%SELECTOR%% .bm-login-shell { width: 100%; max-width: 420px; }
%%SELECTOR%% .bm-login-brand { text-align: center; margin-bottom: 24px; color: #fff; }
%%SELECTOR%% .bm-login-mark { font-size: 3rem; line-height: 1; margin-bottom: 8px; opacity: .88; }
%%SELECTOR%% .bm-login-brand h2 { margin: 0 0 6px; color: #fff; }
%%SELECTOR%% .bm-login-brand p { margin: 0; color: rgba(255,255,255,.78); }
%%SELECTOR%% .bm-login-foot { margin-top: 14px; text-align: center; color: rgba(255,255,255,.56); font-size: .74rem; }
```

## 5. JavaScript / Alpine.js / PHP / integracje

- Alpine: `window.bmLogin()` z `assets/js/bm-components-auth.js`.
- REST / helper: `bmApi.getCamps()`, `bmApi.getCampStaff(campId)`, `bmApi.login(payload)` w `assets/js/bm-api.js`.
- Store: po sukcesie wywołuje `Alpine.store('bm').setSession(data)`, a następnie `loadCamp()` i `loadAnnouncements()` z `assets/js/bm-store.js`.
- Zdarzenia przeglądarki: `bm:login`.
- PHP / konfiguracja: wymaga globalnego `bmConfig.activeCamps` lub aktywnych endpointów publicznych oraz `bmConfig.loginNonce` dla `POST auth/login`.

## 5a. Przekierowanie po zalogowaniu (osobna strona logowania)

Gdy formularz logowania jest na innej stronie niż panel, użyj shortcode'u zamiast elementu Breakdance i przekaż atrybut `redirect_url`:

```
[bm_panel_login redirect_url="/panel/"]
```

Po pomyślnym zalogowaniu użytkownik trafi automatycznie pod podany URL.

Aby zalogowany użytkownik nie widział ponownie strony logowania, dodaj nad formularzem shortcode guard:

```
[bm_panel_session_guard logged="1" redirect="/panel/"][/bm_panel_session_guard]
[bm_panel_login redirect_url="/panel/"]
```

Na stronie panelu możesz analogicznie chronić treść przed niezalogowanymi:

```
[bm_panel_session_guard logged="0" redirect="/logowanie/"][/bm_panel_session_guard]
```

Szczegóły obu atrybutów opisano w `docs/09-frontend-panel.md`.

## 6. Instrukcja wdrożenia

1. Utwórz nowy **Custom Element** w Breakdance i nadaj mu nazwę z sekcji 1.
2. Dodaj kontrolki z sekcji 2 dokładnie z tymi samymi identyfikatorami (`content.*`, `design.*`).
3. Wklej kod z sekcji **Twig** do pola HTML/Twig, a kod z sekcji **CSS** do zakładki CSS elementu.
4. Ustaw warunek widoczności: **pokazuj tylko wtedy, gdy `Alpine.store('bm').authenticated === false`**.
5. Nie duplikuj skryptów w elemencie — korzystaj z globalnie załadowanych plików `bm-api.js`, `bm-store.js` i właściwego modułu komponentów.

## 7. Zależności i uwagi

- Styl bazowy jest utrzymany w domyślnych kolorach ZHP; zmieniaj tylko kontrolki `design.*`, żeby zachować spójność.
- Element wymaga `assets/js/bm-store.js`, `assets/js/bm-api.js` oraz `assets/js/bm-components-auth.js`.
- `bmConfig` musi być dostępny globalnie z prawidłowym `restUrl`, `wpNonce` i odpowiednim nonce panelowym / logowania.
- Element jest niezależny: nie zakłada istnienia wrappera z `docs/15-panel-full-breakdance.md`, ale może współdzielić store `bm` z innymi elementami strony.
- Jeżeli strona ma własną ochronę sesji, ten element może być jedyną sekcją widoczną przed logowaniem.
- Komponent czyści pole kodu po błędzie logowania i obsługuje komunikat o czasowej blokadzie dostępu.
