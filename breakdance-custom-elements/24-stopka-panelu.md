# 24. Stopka panelu

Minimalna stopka występująca na końcu całego panelu. Służy do zamknięcia widoku krótkim podpisem systemowym i może być stosowana niezależnie od reszty modułów.

## 1. Nazwa elementu w Breakdance
`BM 24 / Stopka panelu`

Sugerowana kategoria:
`ZHP Panel / Layout`

## 2. Kontrolki Element Studio

| Zakładka | Typ | ID | Domyślna wartość | Zastosowanie |
|---|---|---|---|---|
| Content | Text | `content.text` | `System Bazy Obozowej · ZHP` | Główna treść stopki. |
| Design | Color | `design.text_color` | `#8A96A1` | Kolor tekstu. |
| Design | Color | `design.background_color` | `transparent` | Tło stopki. |
| Design | Number | `design.padding_top` | `16` | Górny padding. |
| Design | Number | `design.padding_bottom` | `24` | Dolny padding. |

## 3. Twig

```twig
<footer class="bm-panel-footer">{{ content.text }}</footer>
```

## 4. CSS

```css
%%SELECTOR%% .bm-panel-footer {
  padding: {{ design.padding_top }}px 20px {{ design.padding_bottom }}px;
  background: {{ design.background_color }};
  color: {{ design.text_color }};
  text-align: center;
  font-size: .72rem;
  line-height: 1.5;
  font-family: Lato, 'Open Sans', system-ui, sans-serif;
}
```

## 5. JavaScript / Alpine.js / PHP / integracje

Ten element nie wymaga dodatkowego JavaScript, PHP ani integracji API.

## 6. Instrukcja wdrożenia

1. Utwórz nowy element w Element Studio.
2. Dodaj jedną kontrolkę tekstową i podstawowe ustawienia stylu.
3. Wklej Twig i CSS.
4. Umieść stopkę na końcu układu panelu.
5. Sprawdź kontrast i odstępy na telefonie.

## 7. Zależności i uwagi

- Stopka jest neutralna i może być używana w innych widokach panelowych.
- Jeśli ma zawierać linki lub numer wersji, dodaj kolejne kontrolki tekstowe lub URL.
