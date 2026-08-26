# 14. Moje zgłoszenia

Element do przeglądu własnych zgłoszeń wraz z filtrem statusu, ekranem szczegółów, załącznikami i komentarzem administratora.

## 1. Nazwa elementu w Breakdance
`BM 14 / Moje zgłoszenia`

## 2. Kontrolki Element Studio

| Zakładka | Typ | ID | Domyślna wartość | Zastosowanie |
|---|---|---|---|---|
| Content | Text | `content.heading` | `📋 Moje zgłoszenia` | Nagłówek listy zgłoszeń. |
| Content | Textarea | `content.empty_text` | `Brak zgłoszeń.` | Komunikat przy pustej liście. |
| Content | Text | `content.back_label` | `← Wróć` | Etykieta przycisku powrotu z detalu. |
| Design | Color | `design.primary_color` | `#1B5E33` | Główny zielony kolor ZHP używany w nagłówkach, CTA i akcentach. |
| Design | Color | `design.primary_hover_color` | `#2A7A4B` | Kolor hover / drugi odcień zieleni w gradientach i aktywnych stanach. |
| Design | Color | `design.surface_color` | `#FFFFFF` | Tło kart i kontenerów elementu. |
| Design | Color | `design.border_color` | `#D0D8DC` | Obramowania kart, pól i tabel. |
| Design | Number | `design.radius` | `8` | Promień zaokrąglenia wszystkich kart, pól i chipów. |

## 3. Twig

```twig
<div class="bm-el" x-data="bmSubmissions()" x-init="init()" x-cloak x-show="$store.bm.authenticated">
  <div class="bm-card">
    <div class="bm-loader" x-show="loading" style="padding:16px 18px;"><span class="bm-spinner"></span><span>Ładowanie zgłoszeń…</span></div>
    <div class="bm-alert bm-alert--error" x-show="error && !loading" x-text="error"></div>

    <div x-show="!current">
      <div class="bm-card-header">
        <h3>{{ content.heading }}</h3>
        <select class="bm-select" x-model="filterStatus" @change="applyFilter()" style="max-width:180px;background:rgba(255,255,255,.16);color:#fff;border-color:rgba(255,255,255,.28);">
          <option value="" style="color:#1A2530;">Wszystkie</option>
          <option value="new" style="color:#1A2530;">Nowe</option>
          <option value="in_progress" style="color:#1A2530;">W trakcie</option>
          <option value="waiting" style="color:#1A2530;">Oczekuje</option>
          <option value="closed" style="color:#1A2530;">Zamknięte</option>
        </select>
      </div>
      <div class="bm-card-body" style="padding:0;">
        <div class="bm-empty" x-show="!loading && !submissions.length">{{ content.empty_text }}</div>
        <template x-for="sub in submissions" :key="sub.id">
          <div class="bm-doc-row" @click="openSubmission(sub.id)" style="border-radius:0;margin:0;border-bottom:1px solid var(--bm-border);cursor:pointer;">
            <div>
              <strong style="font-size:.92rem;">Zgłoszenie #<span x-text="sub.id"></span></strong>
              <div class="bm-pills" style="margin:4px 0 0;">
                <span class="bm-badge bm-badge--gray" x-text="sub.category || 'bez kategorii'"></span>
                <span class="bm-badge bm-badge--blue" x-text="priorityLabel(sub.priority)"></span>
              </div>
              <div class="bm-meta" x-text="sub.created_at"></div>
            </div>
            <span class="bm-badge" :class="sub.status==='closed' ? 'bm-badge--green' : sub.status==='in_progress' ? 'bm-badge--blue' : sub.status==='waiting' ? 'bm-badge--gold' : 'bm-badge--gray'" x-text="statusLabel(sub.status)"></span>
          </div>
        </template>
      </div>
    </div>

    <div x-show="current">
      <div class="bm-card-header">
        <button class="bm-btn bm-btn--light bm-btn--sm" @click="closeSubmission()">{{ content.back_label }}</button>
        <h3 style="margin-left:8px;font-size:.92rem;">Zgłoszenie #<span x-text="current && current.submission && current.submission.id"></span></h3>
      </div>
      <div class="bm-loader" x-show="loadingDetail" style="padding:16px 18px;"><span class="bm-spinner"></span><span>Ładowanie szczegółów…</span></div>
      <div class="bm-card-body" x-show="current && !loadingDetail">
        <div class="bm-pills">
          <span class="bm-badge" :class="current && current.submission && current.submission.status==='closed' ? 'bm-badge--green' : current && current.submission && current.submission.status==='in_progress' ? 'bm-badge--blue' : current && current.submission && current.submission.status==='waiting' ? 'bm-badge--gold' : 'bm-badge--gray'" x-text="current && current.submission && statusLabel(current.submission.status)"></span>
          <span class="bm-badge bm-badge--blue" x-text="current && current.submission && priorityLabel(current.submission.priority)"></span>
          <span class="bm-badge bm-badge--gray" x-text="'Wysłano: ' + (current && current.submission && current.submission.created_at)"></span>
        </div>

        <template x-if="current && current.form_snapshot && current.form_snapshot.fields">
          <div>
            <template x-for="field in current.form_snapshot.fields" :key="field.field_key">
              <div class="bm-note" style="margin-bottom:10px;">
                <div class="bm-label" style="margin-bottom:3px;" x-text="field.label"></div>
                <div x-text="current.submission_data && current.submission_data[field.field_key] !== undefined ? (Array.isArray(current.submission_data[field.field_key]) ? current.submission_data[field.field_key].join(', ') : String(current.submission_data[field.field_key])) : '—'"></div>
              </div>
            </template>
          </div>
        </template>

        <template x-if="current && current.attachments && current.attachments.length">
          <div style="margin-top:12px;">
            <div class="bm-label" style="margin-bottom:6px;">Załączniki</div>
            <template x-for="att in current.attachments" :key="att.id">
              <a :href="att.download_url" target="_blank" class="bm-btn bm-btn--ghost bm-btn--sm" style="margin:4px 4px 0 0;">📎 <span x-text="att.original_name"></span></a>
            </template>
          </div>
        </template>

        <template x-if="current && current.admin_comment">
          <div class="bm-alert bm-alert--info" style="margin-top:12px;flex-direction:column;align-items:flex-start;">
            <strong>Komentarz administratora:</strong>
            <span x-text="current.admin_comment"></span>
          </div>
        </template>
      </div>
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
```

## 5. JavaScript / Alpine.js / PHP / integracje

- Alpine: `window.bmSubmissions()` z `assets/js/bm-components-social.js`.
- REST / helper: `bmApi.getSubmissions(params)`, `bmApi.getSubmission(id)`, `bmApi.getAttachmentUrl(submissionId, attachmentId)` w `assets/js/bm-api.js`.
- PHP / konfiguracja: w praktyce lista detalu korzysta z `download_url` zwróconego przez API; helper `getAttachmentUrl` jest przydatny, jeśli backend zwraca jedynie identyfikatory załączników.

## 6. Instrukcja wdrożenia

1. Utwórz nowy **Custom Element** w Breakdance i nadaj mu nazwę z sekcji 1.
2. Dodaj kontrolki z sekcji 2 dokładnie z tymi samymi identyfikatorami (`content.*`, `design.*`).
3. Wklej kod z sekcji **Twig** do pola HTML/Twig, a kod z sekcji **CSS** do zakładki CSS elementu.
4. Ustaw warunek widoczności: **pokazuj tylko wtedy, gdy `Alpine.store('bm').authenticated === true`**.
5. Nie duplikuj skryptów w elemencie — korzystaj z globalnie załadowanych plików `bm-api.js`, `bm-store.js` i właściwego modułu komponentów.

## 7. Zależności i uwagi

- Styl bazowy jest utrzymany w domyślnych kolorach ZHP; zmieniaj tylko kontrolki `design.*`, żeby zachować spójność.
- Element wymaga `assets/js/bm-store.js`, `assets/js/bm-api.js` oraz `assets/js/bm-components-social.js`.
- `bmConfig` musi być dostępny globalnie z prawidłowym `restUrl`, `wpNonce` i odpowiednim nonce panelowym / logowania.
- Element jest niezależny: nie zakłada istnienia wrappera z `docs/15-panel-full-breakdance.md`, ale może współdzielić store `bm` z innymi elementami strony.
- Element odtwarza stan z zakładki „Moje zgłoszenia”, ale bez otaczającej sub-nawigacji — to minimalny samodzielny blok.
- Snapshot pól formularza jest czytany z `current.form_snapshot.fields`; nie zakładaj stałego schematu formularza w Twig.
