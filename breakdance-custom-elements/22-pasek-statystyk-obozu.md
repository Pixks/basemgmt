# 22. Pasek statystyk obozu

Osobny pasek liczników znajdujący się pod hero obozu. W oryginalnym panelu pokazuje uczestników, kadrę, pracowników i liczbę łączną. To element hybrydowy: może korzystać z `latestCount` z `bmCamp()` albo z danych dynamicznych Breakdance.

## 1. Nazwa elementu w Breakdance
`BM 22 / Pasek statystyk obozu`

Sugerowana kategoria:
`ZHP Panel / Dashboard`

## 2. Kontrolki Element Studio

| Zakładka | Typ | ID | Domyślna wartość | Zastosowanie |
|---|---|---|---|---|
| Content | Repeater | `content.stats` | `Uczestnicy, Kadra, Pracownicy, Łącznie` | Każdy rekord powinien mieć `label`, `value`, opcjonalnie `dynamic_key`. |
| Content | Toggle | `content.use_alpine` | `true` | Pozwala podmienić wartości na `latestCount` z `bmCamp()`. |
| Design | Color | `design.primary_color` | `#1B5E33` | Kolor wartości liczbowych. |
| Design | Color | `design.surface_color` | `#FFFFFF` | Tło paska. |
| Design | Color | `design.border_color` | `#D0D8DC` | Obramowanie i separatory. |
| Design | Number | `design.radius` | `8` | Zaokrąglenie dolnych rogów. |

## 3. Twig

```twig
{% set stats = content.stats|default([
  { label: 'Uczestnicy', value: '120', dynamic_key: 'participants' },
  { label: 'Kadra', value: '18', dynamic_key: 'staff' },
  { label: 'Pracownicy', value: '7', dynamic_key: 'workers' },
  { label: 'Łącznie', value: '145', dynamic_key: 'total' }
]) %}

<div class="bm-el" {% if content.use_alpine|default(true) %}x-data="bmCamp()" x-init="init()"{% else %}x-data="{}"{% endif %} x-cloak x-show="$store.bm.authenticated">
  <div class="bm-status-bar">
    {% for stat in stats %}
      <div class="bm-stat">
        <strong {% if content.use_alpine|default(true) %}x-text="latestCount && latestCount['{{ stat.dynamic_key|default('') }}'] !== undefined ? latestCount['{{ stat.dynamic_key|default('') }}'] : '{{ stat.value }}'"{% endif %}>{{ stat.value }}</strong>
        <span>{{ stat.label }}</span>
      </div>
      {% if not loop.last %}<div class="bm-sep"></div>{% endif %}
    {% endfor %}
  </div>
</div>
```

## 4. CSS

```css
%%SELECTOR%% {
  --bm-primary: {{ design.primary_color }};
  --bm-surface: {{ design.surface_color }};
  --bm-border: {{ design.border_color }};
  --bm-radius: {{ design.radius }}px;
  display:block;
}
%%SELECTOR%% .bm-status-bar { display:flex; flex-wrap:wrap; gap:12px; padding:12px 20px; background:var(--bm-surface); border:1px solid var(--bm-border); border-top:none; border-radius:0 0 var(--bm-radius) var(--bm-radius); }
%%SELECTOR%% .bm-stat { min-width:64px; text-align:center; }
%%SELECTOR%% .bm-stat strong { display:block; color:var(--bm-primary); font-size:1.35rem; line-height:1.1; }
%%SELECTOR%% .bm-stat span { display:block; margin-top:4px; color:#66756C; font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; }
%%SELECTOR%% .bm-sep { width:1px; min-height:42px; background:var(--bm-border); }
@media (max-width: 767px) { %%SELECTOR%% .bm-sep { display:none; } }
```

## 5. JavaScript / Alpine.js / PHP / integracje

- Wariant dynamiczny korzysta z `bmCamp()` z `assets/js/bm-components-auth.js`.
- Dane pochodzą z `GET panel/camp`, z pola `latest_count`.
- Przykład:

```json
{ "latest_count": { "participants": 120, "staff": 18, "workers": 7, "total": 145 } }
```

- Jeśli wyłączysz `use_alpine`, element działa jako czysty komponent statystyczny z repeaterem.

## 6. Instrukcja wdrożenia

1. Utwórz element w Element Studio.
2. Dodaj repeater pozycji statystyk.
3. Wklej Twig i CSS.
4. Włącz lub wyłącz zasilanie z Alpine.
5. Sprawdź wariant z danymi i bez danych.

## 7. Zależności i uwagi

- Ten plik wydziela licznik ze wspólnego hero, aby można go było używać także osobno.
- Dla dostępności zawsze zostaw tekstowe etykiety pól.
