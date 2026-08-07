# 09 – Panel frontendowy

## Koncepcja

Panel frontendowy jest głównym interfejsem dla kadry obozów. Nie jest to klasyczna strona WordPress – to **single-page application** oparta na Alpine.js, komunikująca się z REST API pluginu.

---

## Ładowanie assetów – bez shortcode

Assetów (`bm-api.js`, Alpine.js, `bmConfig`) **nie trzeba już ładować przez shortcode**. Plugin ładuje je globalnie na każdej stronie frontendowej WordPress (`wp_enqueue_scripts`).

Dzięki temu **Breakdance Custom Elements i Code Blocks** mogą korzystać z komponentów Alpine.js na dowolnej stronie bez konieczności umieszczania shortcode.

---

## Shortcode (zachowany dla kompatybilności wstecznej)

```
[bm_init]
```

lub starsze wersje:

```
[camp_panel]   [camp_access]   [camp_overview]
```

Wszystkie shortcody wywołują `enqueue_assets()` (idempotent – bezpieczne wielokrotne wywołanie). Na nowych stronach shortcode nie jest wymagany.

---

## Integracja z Breakdance

Użyj elementu **Code Block** lub **Custom HTML** – `bmConfig` i Alpine.js są już załadowane na stronie:

```html
<div x-data="bmLogin">
  <!-- komponenty działają bez shortcode -->
</div>
```

> Plugin **nie ładuje drugiej instancji Alpine.js** gdy Breakdance już ją załadował. Komponenty rejestrowane są przez `window.Alpine.data()` z fallback obsługą race condition.

---

## Konfiguracja JS (`bmConfig`)

`ShortcodeHandler::enqueue_assets()` wstrzykuje globalny obiekt `bmConfig`:

```javascript
window.bmConfig = {
    restUrl:    'https://twoja-strona.pl/wp-json/',
    wpNonce:    'abc123',      // nonce WordPress REST API
    panelNonce: 'def456',      // nonce dla operacji panelowych
    loginNonce: 'ghi789',      // nonce dla logowania
    authenticated: false,
    campId:        0,
    staffId:       0,
    displayName:   '',
    sessionExpires:'2026-08-07 20:00:00'
};
```

---

## Architektura komponentów Alpine.js (`bm-api.js`)

Wszystkie komponenty zdefiniowane jako `Alpine.data('nazwaKomponentu', () => {...})`.

| Komponent | Opis |
|-----------|------|
| `bmLogin` | Ekran logowania (wybór obozu → kadry → wpisanie kodu) |
| `bmCamp` | Główny kontener panelu po zalogowaniu |
| `bmDailyCount` | Prosty formularz meldunku dziennego |
| `bmAnnouncements` | Lista ogłoszeń |
| `bmAnnForm` | Formularz zgłoszenia ogłoszenia |
| `bmReports` | Pełny moduł meldunków: status dnia, zapis roboczy, wysyłka, historia |
| `bmWeather` | Pogoda i ostrzeżenia |
| `bmSchedule` | Plan dnia |
| `bmReservations` | Rezerwacje (lista zasobów, składanie, podgląd) |
| `bmMenu` | Jadłospis (widok dzienny i tygodniowy) |
| `bmConversations` | Komunikacja (wątki, wiadomości, odpowiedzi) |
| `bmHelp` | Pomoc (artykuły, FAQ, filtry) |
| `bmForms` | Formularze (lista, wypełnianie, wysyłanie) |
| `bmSubmissions` | Zgłoszenia (lista, podgląd, załączniki) |
| `bmLogout` | Przycisk wylogowania |

---

## Przepływ interfejsu

```
Strona ładuje się
    │
    ├─ Sprawdź /auth/status
    │      │
    │      ├─ authenticated: true  → Pokaż panel obozu
    │      └─ authenticated: false → Pokaż ekran logowania
    │
    ▼
[Ekran logowania – bmLogin]
    1. Pobierz /public/camps → dropdown obozów
    2. Wybierz obóz → pobierz /public/camps/{id}/staff → dropdown kadry
    3. Wpisz kod → POST /auth/login
    4. Sukces → odświeżenie stanu → panel obozu
    │
    ▼
[Panel obozu – bmCamp + sekcje]
    ├── Ogłoszenia    (bmAnnouncements)
    ├── Meldunek     (bmDailyCount)
    ├── Pogoda       (bmWeather)
    ├── Plan dnia    (bmSchedule)
    ├── Rezerwacje   (bmReservations)
    ├── Jadłospis    (bmMenu)
    ├── Komunikacja  (bmConversations)
    ├── Pomoc        (bmHelp)
    ├── Formularze   (bmForms)
    └── Zgłoszenia   (bmSubmissions)
```

---

## Sekcje panelu

### Ekran logowania

**Kroki**:
1. Dropdown: wybierz obóz (dane z `GET /bm/v1/public/camps`)
2. Dropdown: wybierz siebie (dane z `GET /bm/v1/public/camps/{id}/staff`)
3. Input: wpisz kod bezpieczeństwa
4. Przycisk: Zaloguj się

**Walidacja**:
- Wszystkie pola wymagane
- Komunikat o blokadzie z odliczaniem (`locked_until` w sekundach)
- Przy blokadzie trwałej konto pozostaje niedostępne do czasu odblokowania w panelu admina
- Neutralny komunikat błędu (bez ujawniania szczegółów)

---

### Ogłoszenia

- Lista aktywnych ogłoszeń dla własnego obozu
- Pilne ogłoszenia wyróżnione (inny kolor/ikona)
- Sortowanie po priorytecie i dacie
- Formularz zgłoszenia nowego ogłoszenia (status `pending` – czeka na zatwierdzenie)

---

### Meldunek dzienny

- Widok bieżącego dnia
- Formularz: uczestnicy / kadra / pracownicy / uwagi
- Status: niezłożony / złożony / potwierdzony
- Blokada ponownego składania tego samego dnia

---

### Pogoda

- Bieżące warunki (temperatura, opis, wiatr, wilgotność)
- Prognoza 5-dniowa
- Lista aktywnych ostrzeżeń (wyróżnione pilne)

---

### Plan dnia

- Domyślnie plan na **bieżący dzień**
- Nawigacja do innych dni (daty z planami z `GET /panel/schedule/dates`)
- Wyróżnienie:
  - 🆕 nowych pozycji (`is_new_today`)
  - ✏ zaktualizowanych (`is_updated_today`)
  - ~~odwołanych~~ (przekreślony tekst dla `item_status = cancelled`)
  - ⚡ obowiązkowych (`is_mandatory`)
- Widok tylko do odczytu (kadra nie może edytować)

---

### Rezerwacje

#### Lista zasobów

- Dostępne zasoby z godzinami i zasadami
- Przy wyborze zasobu → formularz nowej rezerwacji

#### Formularz rezerwacji

- Wybór daty
- Widok zajętych slotów dla wybranego zasobu i daty
- Wybór godziny od/do
- Pole celu rezerwacji
- Walidacja godzin dostępności po stronie klienta (tylko UX – realna walidacja po stronie serwera)

#### Moje rezerwacje

- Lista własnych rezerwacji obozu
- Statusy z kolorowym oznaczeniem
- Przycisk anulowania dla `pending` (z uwzględnieniem `cancel_advance_hours`)

---

### Jadłospis

**Komponent**: `bmMenu()`

- Widok dzienny – pobiera `GET /panel/menu?date=YYYY-MM-DD`
- Widok tygodniowy – pobiera `GET /panel/menu/week?from=YYYY-MM-DD`
- Nawigacja po dniach (strzałki lub lista dat z `GET /panel/menu/dates`)
- Grupowanie pozycji po `meal_type` (śniadanie / drugie śniadanie / obiad / podwieczorek / kolacja / inne)
- Widok tylko do odczytu

---

### Komunikacja

**Komponent**: `bmConversations()`

- Lista wątków obozu z lokalnym licznikiem unread
- Tworzenie nowego wątku (temat + treść + priorytet)
- Widok wątku: pełna historia wiadomości chronologicznie
- Formularz odpowiedzi
- Po wejściu w wątek zerowany `unread_camp`

---

### Pomoc

**Komponent**: `bmHelp()`

- Lista artykułów z filtrami: typ, kategoria, szukaj
- Computed getters: `alarmArticles` (ważne/alarmowe), `pinnedArticles`, `faqArticles`, `contactArticles`
- Pełny podgląd artykułu z treścią HTML
- Widok tylko do odczytu

---

### Formularze

**Komponent**: `bmForms()`

- Lista dostępnych formularzy (globalne + przypisane do obozu)
- Filtrowanie po kategorii sprawy
- Wyróżnione formularze (`is_pinned`) na górze listy
- Otwieranie formularza → renderowanie pól według definicji
  - Obsługiwane typy: `text`, `textarea`, `number`, `email`, `tel`, `select`, `radio`, `checkbox`, `date`
  - Pola `file` obsługiwane osobno (nativny `<input type="file">`)
  - `help_text` jest prezentowany jako stała podpowiedź pod polem
- Wysyłanie zgłoszenia przez `POST /panel/submissions`
- Walidacja wymaganych pól po stronie klienta (UX); prawdziwa walidacja server-side
- Po wysłaniu: tekst `info_after` z odpowiedzi

---

### Zgłoszenia

**Komponent**: `bmSubmissions()`

- Lista własnych zgłoszeń obozu z filtrem statusu
- Podgląd szczegółów zgłoszenia:
  - Dane z `form_snapshot` + `submission_data` (stabilne mimo późniejszych zmian formularza)
  - Status i priorytet
  - Komentarz administratora (`admin_comment`)
  - Lista załączników z linkami do pobrania
- Obóz widzi **tylko własne** zgłoszenia (backend weryfikuje `camp_id`)

**Statusy zgłoszeń** (wizualne oznaczenie):

| Status | Znaczenie |
|--------|-----------|
| `new` | Nowe – czeka na reakcję |
| `in_progress` | W trakcie obsługi |
| `waiting` | Oczekuje na odpowiedź obozu |
| `closed` | Zamknięte |
| `cancelled` | Anulowane |

| Mechanizm | Opis |
|-----------|------|
| Nonce CSRF | Każda mutacja przekazuje nonce weryfikowane przez WP |
| `camp_id` z sesji | Backend nigdy nie ufa `camp_id` z żądania – bierze z sesji |
| HttpOnly cookie | Token sesji niedostępny przez JavaScript |
| SameSite=Strict | Ochrona przed CSRF |
| Timeout | Sesja wygasa po 8h, brak aktywności wylogowuje |

---

## Dostosowanie wyglądu

Główny szablon: `templates/frontend/panel.php`

Style admina: `assets/css/admin.css`

Frontend używa TailwindCSS przez CDN Breakdance lub własnych klas. Możesz nadpisać style w motywie:

```css
/* Przykładowe selektory */
.bm-panel { ... }
.bm-login-form { ... }
.bm-announcement-urgent { ... }
.bm-schedule-new { ... }
```

---

## Właściwości techniczne

| Aspekt | Wartość |
|--------|---------|
| Framework JS | Alpine.js v3 (z Breakdance lub ładowany przez plugin) |
| Komunikacja z API | `fetch()` z `window.bmConfig.wpNonce` w nagłówku `X-WP-Nonce` |
| State management | Alpine.js reactive data (`this.state = ...`) |
| Brak jQuery | Frontend nie używa jQuery |
| Kompatybilność | Nowoczesne przeglądarki (Chrome 80+, Firefox 75+, Safari 14+) |
