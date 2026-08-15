# 23. Skrót ogłoszeń dashboardu

Skrócona karta ogłoszeń z zakładki głównej. Pokazuje pilne komunikaty, do trzech zwykłych wpisów i przycisk przejścia do pełnej sekcji. To niezależny komponent dashboardowy, lżejszy niż pełna tablica ogłoszeń.

## 1. Nazwa elementu w Breakdance
`BM 23 / Skrót ogłoszeń dashboardu`

Sugerowana kategoria:
`ZHP Panel / Dashboard`

## 2. Kontrolki Element Studio

| Zakładka | Typ | ID | Domyślna wartość | Zastosowanie |
|---|---|---|---|---|
| Content | Text | `content.heading` | `📢 Ogłoszenia` | Nagłówek karty. |
| Content | Text | `content.more_label` | `Wszystkie ogłoszenia →` | Tekst dolnego CTA. |
| Content | Textarea | `content.empty_text` | `Brak ogłoszeń.` | Pusty stan karty. |
| Design | Color | `design.primary_color` | `#1B5E33` | Główny kolor nagłówka i CTA. |
| Design | Color | `design.border_color` | `#D0D8DC` | Obramowanie karty. |
| Design | Number | `design.radius` | `8` | Zaokrąglenie karty. |

## 3. Twig

```twig
<div class="bm-el" x-data="bmAnnouncements()" x-cloak x-show="$store.bm.authenticated">
  <div class="bm-card">
    <div class="bm-card-header">
      <h3>{{ content.heading }}</h3>
      <button class="bm-btn bm-btn--light bm-btn--sm" @click="refresh()">↻</button>
    </div>
    <div class="bm-card-body">
      <template x-for="ann in active.filter(a => a.is_urgent)" :key="ann.id">
        <div class="bm-alert bm-alert--error" style="flex-direction:column;align-items:flex-start;">
          <strong>🚨 <span x-text="ann.title"></span></strong>
          <div x-html="ann.content"></div>
        </div>
      </template>
      <template x-for="ann in active.filter(a => !a.is_urgent).slice(0,3)" :key="ann.id">
        <div class="bm-list-item">
          <div class="bm-split"><strong x-text="ann.title"></strong><span class="bm-badge bm-badge--green">Aktywne</span></div>
          <div x-html="ann.content" style="margin-top:6px;color:#4B5A67;"></div>
        </div>
      </template>
      <div class="bm-empty" x-show="!active.length">{{ content.empty_text }}</div>
      <button class="bm-btn bm-btn--ghost" style="width:100%;justify-content:center;margin-top:4px;" @click="window.dispatchEvent(new CustomEvent('bm-tab', { detail: 'ogloszenia' }))">{{ content.more_label }}</button>
    </div>
  </div>
</div>
```

## 4. CSS

```css
%%SELECTOR%% {
  --bm-primary: {{ design.primary_color }};
  --bm-border: {{ design.border_color }};
  --bm-radius: {{ design.radius }}px;
  display:block;
  font-family:Lato,'Open Sans',system-ui,sans-serif;
}
%%SELECTOR%% .bm-card { background:#fff; border:1px solid var(--bm-border); border-radius:var(--bm-radius); overflow:hidden; box-shadow:0 2px 10px rgba(27,94,51,.12); }
%%SELECTOR%% .bm-card-header { display:flex; justify-content:space-between; gap:12px; align-items:center; padding:14px 18px; background:var(--bm-primary); color:#fff; }
%%SELECTOR%% .bm-card-header h3 { margin:0; color:#fff; }
%%SELECTOR%% .bm-card-body { padding:14px; }
%%SELECTOR%% .bm-list-item { padding:10px; border:1px solid var(--bm-border); border-radius:8px; margin-bottom:8px; }
%%SELECTOR%% .bm-split { display:flex; justify-content:space-between; gap:8px; }
%%SELECTOR%% .bm-badge { display:inline-flex; padding:2px 9px; border-radius:999px; font-size:.7rem; font-weight:700; }
%%SELECTOR%% .bm-badge--green { background:#EBF5EE; color:var(--bm-primary); }
%%SELECTOR%% .bm-alert { padding:11px 15px; border-radius:8px; margin-bottom:10px; }
%%SELECTOR%% .bm-alert--error { background:#FDECEA; border-left:4px solid #C0392B; color:#C0392B; }
%%SELECTOR%% .bm-btn { min-height:38px; padding:8px 14px; border-radius:999px; border:none; font-weight:700; cursor:pointer; }
%%SELECTOR%% .bm-btn--light { background:rgba(255,255,255,.18); color:#fff; border:1px solid rgba(255,255,255,.35); }
%%SELECTOR%% .bm-btn--ghost { background:transparent; color:var(--bm-primary); border:1px solid var(--bm-primary); }
```

## 5. JavaScript / Alpine.js / PHP / integracje

- Komponent korzysta z `bmAnnouncements()` i store `bm`.
- Dane pochodzą z `GET panel/announcements`.
- CTA emituje `bm-tab` z wartością `ogloszenia`, aby przełączyć pełny widok sekcji.

## 6. Instrukcja wdrożenia

1. Utwórz nowy element.
2. Wklej Twig i CSS.
3. Dodaj teksty kontrolne.
4. Jeśli używasz przełączania sekcji, zostaw zdarzenie `bm-tab`.
5. Sprawdź wariant z pilnym ogłoszeniem i pusty stan.

## 7. Zależności i uwagi

- To osobny widżet dashboardu; nie zastępuje pełnej tablicy ogłoszeń.
- Treść `x-html` musi być wcześniej oczyszczona po stronie WordPressa.
