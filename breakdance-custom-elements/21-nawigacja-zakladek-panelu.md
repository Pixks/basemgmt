# 21. Nawigacja zakładek panelu

Pozioma nawigacja z zakładkami głównych sekcji panelu. W oryginalnym widoku występuje między topbarem a zawartością zakładek. Prezentuje listę sekcji i opcjonalne badge, a sam element jest hybrydowy: może działać na samych linkach albo na lokalnym stanie Alpine.

## 1. Nazwa elementu w Breakdance
`BM 21 / Nawigacja zakładek panelu`

Sugerowana kategoria:
`ZHP Panel / Nawigacja`

## 2. Kontrolki Element Studio

| Zakładka | Typ | ID | Domyślna wartość | Zastosowanie |
|---|---|---|---|---|
| Content | Repeater | `content.tabs` | `Panel, Ogłoszenia, Meldunek...` | Lista zakładek z polami `label`, `key`, `url`, `badge`. |
| Content | Text | `content.active_key` | `panel` | Klucz aktywnej zakładki w wariancie statycznym. |
| Content | Select | `content.mode` | `event` | `event` lub `link`. |
| Content | Text | `content.event_name` | `bm-tab` | Nazwa globalnego zdarzenia przy kliknięciu. |
| Design | Color | `design.primary_color` | `#1B5E33` | Aktywny kolor zakładki. |
| Design | Color | `design.border_color` | `#D0D8DC` | Obramowanie dolne belki. |
| Design | Color | `design.badge_color` | `#C0392B` | Tło badge. |
| Design | Number | `design.radius` | `8` | Zaokrąglenie badge i hoverów. |

## 3. Twig

```twig
{% set tabs = content.tabs|default([
  { label: '📊 Panel', key: 'panel', url: '#panel', badge: '' },
  { label: '📢 Ogłoszenia', key: 'ogloszenia', url: '#ogloszenia', badge: '' },
  { label: '📋 Meldunek', key: 'meldunek', url: '#meldunek', badge: '' },
  { label: '🗓 Plan dnia', key: 'plan', url: '#plan', badge: '' },
  { label: '💬 Wiadomości', key: 'komunikacja', url: '#komunikacja', badge: '3' }
]) %}

<div class="bm-el">
  <nav class="bm-nav" aria-label="Główna nawigacja panelu">
    <div class="bm-nav__inner">
      {% for tab in tabs %}
        {% set active = tab.key == content.active_key|default('panel') %}
        {% if content.mode|default('event') == 'event' %}
          <button
            type="button"
            class="bm-tab {{ active ? 'is-active' : '' }}"
            data-key="{{ tab.key }}"
            onclick="window.dispatchEvent(new CustomEvent('{{ content.event_name|default('bm-tab') }}', { detail: this.dataset.key }))"
          >
            <span>{{ tab.label }}</span>
            {% if tab.badge %}<span class="bm-tab__badge">{{ tab.badge }}</span>{% endif %}
          </button>
        {% else %}
          <a href="{{ tab.url|default('#') }}" class="bm-tab {{ active ? 'is-active' : '' }}">
            <span>{{ tab.label }}</span>
            {% if tab.badge %}<span class="bm-tab__badge">{{ tab.badge }}</span>{% endif %}
          </a>
        {% endif %}
      {% endfor %}
    </div>
  </nav>
</div>
```

## 4. CSS

```css
%%SELECTOR%% {
  --bm-primary: {{ design.primary_color }};
  --bm-border: {{ design.border_color }};
  --bm-radius: {{ design.radius }}px;
  display: block;
  font-family: Lato, 'Open Sans', system-ui, sans-serif;
}
%%SELECTOR%% .bm-nav { background:#fff; border-bottom:2px solid var(--bm-border); overflow-x:auto; }
%%SELECTOR%% .bm-nav__inner { display:inline-flex; min-width:100%; padding:0 8px; white-space:nowrap; }
%%SELECTOR%% .bm-tab { position:relative; display:inline-flex; align-items:center; gap:8px; padding:12px 16px; border:none; border-bottom:2.5px solid transparent; background:none; color:#66756C; text-decoration:none; font-weight:700; cursor:pointer; }
%%SELECTOR%% .bm-tab.is-active { color:var(--bm-primary); border-bottom-color:var(--bm-primary); }
%%SELECTOR%% .bm-tab__badge { min-width:16px; height:16px; padding:0 4px; display:inline-flex; align-items:center; justify-content:center; border-radius:999px; background:{{ design.badge_color }}; color:#fff; font-size:.62rem; }
%%SELECTOR%% .bm-tab:focus-visible { outline:3px solid rgba(212,160,23,.35); outline-offset:-2px; }
```

## 5. JavaScript / Alpine.js / PHP / integracje

Jeśli element ma przełączać widoki na jednej stronie, dodaj globalny nasłuch:

```html
<script>
window.addEventListener('bm-tab', function (event) {
  const key = event.detail;
  document.querySelectorAll('[data-bm-panel]').forEach(function (el) {
    el.hidden = el.getAttribute('data-bm-panel') !== key;
  });
});
</script>
```

Miejsce dodania: globalny kod strony albo sekcja JavaScript tego elementu.

## 6. Instrukcja wdrożenia

1. Otwórz Breakdance → Custom Elements → Element Studio.
2. Utwórz nowy element.
3. Dodaj repeater zakładek.
4. Wklej Twig i CSS.
5. Jeśli korzystasz z trybu `event`, dodaj skrypt nasłuchujący.
6. Skonfiguruj aktywny klucz, linki lub nazwy zdarzeń.
7. Sprawdź działanie na telefonie i w widoku przewijanym poziomo.

## 7. Zależności i uwagi

- Ten element nie wymaga pluginowego API.
- Jeśli sekcje są renderowane jako osobne strony, użyj trybu `link`.
- Badge powinny pochodzić z danych dynamicznych, a nie ze stałych wartości.
