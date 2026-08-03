# 08 – Panel administratora

## Dostęp

WP Admin → **Baza Obozowa** (ikona kalendarza, pozycja 30 w menu)

Wymagane uprawnienie: `manage_basemgmt` (nadawane automatycznie administratorom WP).

---

## Struktura menu

```
Baza Obozowa
├── Dashboard
├── Obozy
├── Kadra
├── Ogłoszenia  [badge z liczbą oczekujących]
├── Meldunki
├── Pogoda
├── Plan dnia
├── Rezerwacje
├── Jadłospis
├── Komunikacja  [badge z liczbą nieprzeczytanych]
├── Pomoc
├── Formularze   (Formularze i Zgłoszenia)
└── Ustawienia    [tylko dla WP Admin z manage_options]
```

---

## Dashboard

Strona startowa z widgetem podsumowania.

**Widgety**:

| Widget | Opis |
|--------|------|
| Aktywne obozy | Liczba obozów ze statusem `active` |
| Meldunki dziś | Ile obozów złożyło meldunek / ile aktywnych |
| Oczekujące ogłoszenia | Zgłoszone przez obozy, czekające na zatwierdzenie |
| Oczekujące rezerwacje | Liczba rezerwacji ze statusem `pending` |
| Plan na dziś | Skrócona lista pozycji planu dnia na bieżącą datę |
| Najbliższe rezerwacje | Lista 5 najbliższych zatwierdzonych rezerwacji |

---

## Obozy

**URL**: `admin.php?page=basemgmt-camps`

### Operacje

| Operacja | Akcja admin-post |
|----------|-----------------|
| Utwórz obóz | `bm_save_camp` |
| Edytuj obóz | `bm_save_camp` (z `camp_id`) |
| Usuń obóz | `bm_delete_camp` |

### Formularz obozu

- Nazwa (wymagana)
- Data rozpoczęcia (wymagana)
- Data zakończenia (wymagana)
- Status: Aktywny / Nieaktywny / Archiwum

> Usunięcie obozu jest trwałe. Rekomendowane jest archiwizowanie zamiast usuwania.

---

## Kadra

**URL**: `admin.php?page=basemgmt-staff`

### Operacje

| Operacja | Akcja |
|----------|-------|
| Dodaj osobę | `bm_save_staff` |
| Edytuj dane | `bm_save_staff` |
| Usuń osobę | `bm_delete_staff` |
| Aktywuj/dezaktywuj | `bm_toggle_staff_active` |
| Resetuj kod | `bm_reset_staff_code` |

### Zarządzanie kodem bezpieczeństwa

- Przy tworzeniu: podaj **6-cyfrowy kod numeryczny** (np. `482016`) w formularzu (pole z walidacją `pattern=\d{6}`)
- Przycisk **"Generuj kod"** generuje losowy, kryptograficznie bezpieczny 6-cyfrowy kod i wstawia go do pola
- Kod jest automatycznie haszowany przy zapisie (`wp_hash_password`)
- **Reset kodu**: osobna sekcja w formularzu edycji – wpisz lub wygeneruj nowy kod i kliknij "Zapisz nowy kod"
- Format kodu jest walidowany server-side (`/^\d{6}$/`) – inne formaty są odrzucane
- Po resecie nie ma możliwości odczytu kodu – tylko nowy reset

### Historia logowań

Kolumna `last_login` w tabeli `bm_staff` jest aktualizowana przy każdym pomyślnym logowaniu.

---

## Ogłoszenia

**URL**: `admin.php?page=basemgmt-announcements`

### Operacje

| Operacja | Akcja |
|----------|-------|
| Utwórz ogłoszenie | `bm_save_announcement` |
| Edytuj | `bm_save_announcement` |
| Zatwierdź zgłoszenie od obozu | `bm_approve_announcement` |
| Usuń | `bm_delete_announcement` |

### Statusy ogłoszeń

| Status | Opis |
|--------|------|
| `draft` | Robocze, niewidoczne |
| `pending` | Zgłoszone przez obóz, czeka na zatwierdzenie |
| `active` | Widoczne dla obozów (w przedziale `valid_from`–`valid_until`) |
| `expired` | Automatycznie po przekroczeniu `valid_until` |
| `archived` | Ręcznie zarchiwizowane |

### Kierowanie do obozów

- **Globalne** (`is_global = 1`): widoczne dla wszystkich aktywnych obozów
- **Wybrane obozy** (`is_global = 0`): widoczne tylko dla zaznaczonych

---

## Meldunki

**URL**: `admin.php?page=basemgmt-reports`

Widok tabelaryczny z meldunkami dziennymi wszystkich obozów.

- Filtrowanie po dacie i obozie
- Widok sumaryczny: uczestnicy + kadra + pracownicy
- Eksport (jeśli zaimplementowany)
- Ręczne dodanie/korekta meldunku przez admina: `bm_save_report`

---

## Pogoda

**URL**: `admin.php?page=basemgmt-weather`

### Sekcje

1. **Bieżące warunki** – pobierane z dostawcy (Open-Meteo)
2. **Prognoza** – 5-dniowa
3. **Ostrzeżenia IMGW** – lista aktywnych ostrzeżeń
4. **Ręczne ostrzeżenia** – admin może dodać własne
5. **Ustawienia** – konfiguracja dostawcy, lokalizacja, IMGW

### Konfiguracja IMGW

1. Włącz synchronizację
2. Wybierz **Województwo** z listy
3. Wybierz **Powiat** z listy (filtrowana dynamicznie po wyborze województwa)
4. Wybierz interwał synchronizacji (co godzinę / dwa razy dziennie / raz dziennie)
5. Zapisz → cron jest automatycznie przeplanowywany

> Kod TERYT powiatu (4 cyfry) jest używany do filtrowania ostrzeżeń IMGW.

### Operacje

| Operacja | Akcja |
|----------|-------|
| Zapisz ustawienia | `bm_save_weather_settings` |
| Dodaj ręczne ostrzeżenie | `bm_save_weather_alert` |
| Usuń ostrzeżenie | `bm_delete_weather_alert` |
| Odśwież pogodę | `bm_refresh_weather` |
| Ręczna synchronizacja IMGW | `bm_sync_imgw` |

---

## Plan dnia

**URL**: `admin.php?page=basemgmt-schedule`

### Lista planów

- Tabela z datami i statusami planów
- Filtrowanie po dacie i zasięgu
- Akcje: edytuj, usuń, kopiuj z poprzedniego dnia

### Edycja planu

**URL**: `admin.php?page=basemgmt-schedule&edit=1&plan_id=N`

#### Nagłówek planu
- Data (blokowana po zapisaniu)
- Tytuł (opcjonalny)
- Status: Aktywny / Roboczy / Archiwum
- Zasięg: Globalny / Tylko wybrane obozy (checkbox lista obozów)

#### Pozycje planu

Tabela pozycji z możliwością **drag & drop** (Sortable.js):
- Uchwyt do przeciągania (ikona `⠿`)
- Godziny: od–do
- Tytuł
- Kategoria (apel, posiłek, cisza nocna, zajęcia, zbiórka, info, inne)
- Status (aktywna / zmieniona / odwołana)
- Flagi: 🆕 nowe, ✏ zmienione, ⚡ obowiązkowe

**Opcje dla pozycji**:
- Edycja inline (toggle bez przeładowania strony)
- Usuwanie z potwierdzeniem
- Dodawanie nowej pozycji (expandable sekcja na dole)

#### Resetowanie flag zmian

Przycisk "Resetuj flagi zmian" → akcja `bm_reset_plan_flags` → zeruje `is_new_today` i `is_updated_today` dla wszystkich pozycji.

#### Kopiowanie planu

Akcja `bm_copy_plan` kopiuje nagłówek i pozycje z wybranej daty do nowej daty. Nowe pozycje mają zresetowane flagi zmian.

### Operacje

| Operacja | Akcja |
|----------|-------|
| Zapisz nagłówek | `bm_save_schedule` |
| Usuń plan | `bm_delete_plan` |
| Dodaj/edytuj pozycję | `bm_save_plan_item` |
| Usuń pozycję | `bm_delete_plan_item` |
| Kopiuj plan | `bm_copy_plan` |
| Reset flag | `bm_reset_plan_flags` |
| Reorder (AJAX) | `wp_ajax_bm_reorder_plan_items` |

---

## Rezerwacje

**URL**: `admin.php?page=basemgmt-reservations`

### Widok listy

Tabela rezerwacji z filtrami:
- Zasób
- Obóz
- Status
- Data

**Akcje na rezerwacji**:
- ✓ Zatwierdź (dla `pending`)
- ✗ Odrzuć (dla `pending`)
- Anuluj (dla `approved`)

Każda akcja obsługiwana przez `bm_reservation_action` z wartością `approve`, `reject`, `cancel`.

### Ręczne dodanie rezerwacji

Expandable formularz "Dodaj rezerwację ręcznie" na górze strony. Admin pomija limit wyprzedzenia i może rezerwować w imieniu dowolnego obozu.

### Widok kalendarza (FullCalendar)

Sekcja kalendarza pod tabelą listy:
- Filtr zasobu (dropdown)
- Kolory: żółty = pending, zielony = approved, czerwony = rejected, szary = cancelled
- Kliknięcie w event → popup ze szczegółami

Zdarzenia pobierane przez AJAX: `wp_ajax_bm_calendar_events`

### Zarządzanie zasobami

**URL**: `admin.php?page=basemgmt-reservations&bm_action=resources`

Lista zasobów z akcjami: edytuj, dezaktywuj.

**Edycja zasobu** (`&bm_action=resource_edit&id=N`):
- Nazwa, typ, opis, zasady
- Status (aktywny/nieaktywny)
- Godziny dostępności
- Limity czasu trwania
- Wyprzedzenie (min. i max.)
- Anulowanie z wyprzedzeniem (`cancel_advance_hours`)
- Max. aktywnych rezerwacji na obóz
- Globalna blokada

**Blokady techniczne**:
- Lista istniejących blokad
- Formularz dodania nowej blokady (od–do, powód)
- Usuwanie blokady

### Operacje zasobów

| Operacja | Akcja |
|----------|-------|
| Zapisz zasób | `bm_save_resource` |
| Usuń zasób | `bm_delete_resource` |
| Dodaj blokadę | `bm_save_resource_block` |
| Usuń blokadę | `bm_delete_resource_block` |
| Akcja na rezerwacji | `bm_reservation_action` |
| Ręczna rezerwacja | `bm_admin_create_reservation` |

---

## Ustawienia

**URL**: `admin.php?page=basemgmt-settings`

Widoczne tylko dla użytkowników z uprawnieniem `manage_options` (domyślnie tylko Super Admin i Admin).

### Sekcje

1. **Ustawienia email** – konfiguracja globalnego systemu powiadomień
2. **Szablony emaili** – edytor HTML szablonów z podstawianiem zmiennych `{{token}}`
3. **Test emaila** – wysyłka testowego emaila na podany adres
4. **O pluginie** – wersja, lista tabel, wymagania

### Operacje

| Operacja | Akcja |
|----------|-------|
| Zapisz ustawienia | `bm_save_settings` |
| Wyślij test email | `bm_send_test_email` |
| Zapisz szablon emaila | `bm_save_email_template` |
| Przywróć domyślny szablon | `bm_reset_email_template` |

### Edytor szablonów emaili

Każdy szablon emaila można edytować przez interfejs graficzny:

- Przycisk **Edytuj** przy szablonie na liście → otwiera edytor (`?edit_template={slug}`)
- Edytor CodeMirror (podświetlanie składni HTML, numery linii, zawijanie)
- Prawy sidebar: lista zmiennych `{{token}}` z opisami; kliknięcie wstawia token w miejscu kursora
- Wskaźnik **● Własny / ○ Domyślny** – widoczny na liście szablonów
- **Przywróć domyślny** – usuwa override, wraca do wbudowanego szablonu

Szczegółowa dokumentacja: [07 – System email](07-email-system.md).

---

## Uprawnienia (Capabilities)

| Capability | Kto ma | Opis |
|------------|--------|------|
| `manage_basemgmt` | Administrator, redaktor (konfigurowalnie) | Dostęp do głównego panelu |
| `manage_bm_camps` | Administrator | Zarządzanie obozami |
| `manage_bm_staff` | Administrator | Zarządzanie kadrą |
| `manage_bm_announcements` | Administrator, redaktor | Zarządzanie ogłoszeniami |
| `manage_options` | Administrator | Ustawienia globalnego |

Uprawnienia rejestrowane przez `Capabilities::register()` podczas aktywacji.

---

## Flash notices (komunikaty)

Po każdej operacji wyświetlany jest komunikat przez `AdminMenu::set_notice()`:

```php
// Sukces (zielone)
AdminMenu::set_notice(__('Obóz zapisany.', 'basemgmt'));

// Błąd (czerwone)
AdminMenu::set_notice(__('Błąd: brak uprawnień.', 'basemgmt'), 'error');
```

Przechowywany jako WP Transient `bm_admin_notice_{user_id}` z TTL 60 sekund.

---

## Jadłospis

**Plik**: `src/Admin/Pages/MenuPage.php`  
**Menu**: WP Admin → Baza Obozowa → Jadłospis

### Sekcje

- **Lista dni** – przegląd dat z opublikowanym/roboczym jadłospisem
- **Edycja dnia** – edycja nagłówka + zarządzanie pozycjami jadłospisu
  - Typy posiłku: Śniadanie, Obiad, Kolacja, Inne
  - Pola pozycji: godzina, tytuł, opis, lokalizacja, dieta, alergeny
  - Flagi: `Nowe dzisiaj`, `Zmienione dzisiaj`
- **Kopiowanie** – kopiowanie jadłospisu z innej daty

### Operacje (admin-post)

| Action | Opis |
|--------|------|
| `bm_save_menu` | Zapisuje nagłówek dnia |
| `bm_delete_menu` | Usuwa dzień (i wszystkie pozycje) |
| `bm_save_meal_item` | Dodaje / edytuje pozycję jadłospisu |
| `bm_delete_meal_item` | Usuwa pozycję |
| `bm_copy_menu` | Kopiuje jadłospis z jednej daty na inną |
| `bm_reset_menu_flags` | Zeruje flagi `is_new_today` / `is_updated_today` |

---

## Komunikacja

**Plik**: `src/Admin/Pages/CommunicationPage.php`  
**Menu**: WP Admin → Baza Obozowa → Komunikacja *(badge z liczbą nieprzeczytanych)*

### Sekcje

- **Lista wątków** – wszystkie wątki ze wszystkich obozów; filtry: obóz, status, priorytet
- **Widok wątku** – pełna historia wiadomości + formularz odpowiedzi admina
  - Pola: treść, opcjonalnie zmiana statusu / priorytetu / przypisanie

### Operacje

| Action | Opis |
|--------|------|
| `bm_admin_reply` | Dodaje odpowiedź admina do wątku |
| `bm_update_thread` | Aktualizuje status, priorytet, przypisanie wątku |

### Unread badge

`ConversationRepository::count_unread_admin()` → suma `unread_admin` ze wszystkich niedomkniętych wątków. Aktualizowana przy każdym przeładowaniu menu.

---

## Pomoc

**Plik**: `src/Admin/Pages/HelpPage.php`  
**Menu**: WP Admin → Baza Obozowa → Pomoc

### Sekcje

- **Lista artykułów** – z filtrem: typ, kategoria, status; flagi: alarm/pinned
- **Edycja artykułu** – `wp_editor()` do treści, pola: tytuł, kategoria, typ, status, kolejność

### Operacje

| Action | Opis |
|--------|------|
| `bm_save_help` | Zapisuje artykuł pomocy |
| `bm_delete_help` | Usuwa artykuł |

---

## Formularze i Zgłoszenia

**Plik**: `src/Admin/Pages/FormsPage.php`  
**Menu**: WP Admin → Baza Obozowa → Formularze

Router oparty na parametrze `?view=`:
- `forms` (domyślny) – lista formularzy
- `edit_form` – edycja formularza + builder pól
- `submissions` – lista zgłoszeń z filtrami
- `view_submission` – podgląd i zarządzanie zgłoszeniem

### Formularze – operacje

| Action | Opis |
|--------|------|
| `bm_save_form` | Tworzy / aktualizuje formularz i widoczność dla obozów |
| `bm_delete_form` | Usuwa formularz (kaskadowo usuwa pola i przypisania) |
| `bm_save_form_field` | Dodaje / edytuje pole formularza |
| `bm_delete_form_field` | Usuwa pole |

### Zgłoszenia – operacje

| Action | Opis |
|--------|------|
| `bm_update_submission` | Zmienia status, priorytet, przypisanie, komentarz admina |
| `bm_download_attachment` | Pobiera plik załączony do zgłoszenia |

### Widok zgłoszenia

- Lewa kolumna: dane z `form_snapshot` + `submission_data` w tabeli pól
- Prawa kolumna: panel zarządzania (status, priorytet, przypisanie, komentarz)
- Sekcja załączników z linkami do pobrania
- Historia zmian statusu z notami i autorami
