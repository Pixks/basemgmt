# 04 – Moduły

## Przegląd modułów

| Moduł | Namespace | Tabele | Admin | Frontend |
|-------|-----------|--------|-------|---------|
| Obozy | `Modules\Camps` | `bm_camps` | ✅ | tylko odczyt |
| Kadra | `Modules\Camps` | `bm_staff`, `bm_sessions` | ✅ | logowanie |
| Ogłoszenia | `Modules\Announcements` | `bm_announcements`, `bm_announcement_camps` | ✅ | ✅ |
| Meldunki | `Modules\Reports` | `bm_daily_counts` | ✅ | ✅ |
| Pogoda | `Modules\Weather` | `bm_weather_alerts` | ✅ | ✅ |
| Plan dnia | `Modules\Schedule` | `bm_plan_headers`, `bm_plan_items`, `bm_plan_item_revisions`, `bm_plan_camps` | ✅ | tylko odczyt |
| Rezerwacje | `Modules\Reservations` | `bm_resources`, `bm_resource_reservations`, `bm_resource_blocks` | ✅ | ✅ |
| Jadłospis | `Modules\Menu` | `bm_meal_days`, `bm_meal_items` | ✅ | tylko odczyt |
| Komunikacja | `Modules\Communication` | `bm_conv_threads`, `bm_conv_messages` | ✅ | ✅ |
| Pomoc | `Modules\Help` | `bm_help_articles` | ✅ | tylko odczyt |
| **Formularze i Zgłoszenia** | `Modules\Forms` | `bm_forms`, `bm_form_fields`, `bm_form_camps`, `bm_submissions`, `bm_submission_attachments`, `bm_submission_history` | ✅ | ✅ |

---

## Moduł: Obozy

**Klasa**: `CampRepository` (`src/Modules/Camps/CampRepository.php`)

### Model danych

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `name` | VARCHAR(255) | Nazwa obozu |
| `start_date` | DATE | Data rozpoczęcia |
| `end_date` | DATE | Data zakończenia |
| `status` | VARCHAR(20) | `active` \| `inactive` \| `archived` |
| `created_at` | DATETIME | Automatycznie |
| `updated_at` | DATETIME | Automatycznie |

### Główne metody

```php
CampRepository::get(int $id): ?object
CampRepository::get_all(array $filters = []): array
// Filtry: status, search, date_from, date_to

CampRepository::create(array $data): int  // zwraca nowe ID
CampRepository::update(int $id, array $data): bool
CampRepository::delete(int $id): bool
```

### Panel admina

- Lista obozów z filtrowaniem po statusie i dacie
- Formularz tworzenia/edycji obozu
- Dezaktywacja (zmiana statusu bez usuwania)

---

## Moduł: Kadra

**Klasa**: `StaffRepository` (`src/Modules/Camps/StaffRepository.php`)

### Model danych

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `camp_id` | BIGINT UNSIGNED | FK → bm_camps |
| `first_name` | VARCHAR(100) | Imię |
| `last_name` | VARCHAR(100) | Nazwisko |
| `email` | VARCHAR(255) | Email (do powiadomień) |
| `phone` | VARCHAR(50) | Telefon |
| `role_in_camp` | VARCHAR(100) | Rola (komendant, zastępca…) |
| `security_code_hash` | VARCHAR(255) | bcrypt hash kodu |
| `is_active` | TINYINT(1) | Czy może się logować |
| `failed_attempts` | TINYINT UNSIGNED | Liczba nieudanych prób |
| `locked_until` | DATETIME | Blokada do kiedy |
| `last_login` | DATETIME | Ostatnie logowanie |

### Główne metody

```php
StaffRepository::get(int $id): ?object
StaffRepository::get_by_camp(int $camp_id, bool $active_only = false): array
StaffRepository::create(array $data): int
StaffRepository::update(int $id, array $data): bool
StaffRepository::delete(int $id): bool
StaffRepository::set_code(int $id, string $plain_code): void
StaffRepository::toggle_active(int $id): bool
```

### Kody bezpieczeństwa

Kod podany w formularzu jest automatycznie haszowany przez `wp_hash_password()` w `StaffRepository::set_code()`. Nigdy nie jest przechowywany w plain text.

---

## Moduł: Ogłoszenia

**Klasa**: `AnnouncementRepository` (`src/Modules/Announcements/AnnouncementRepository.php`)

### Model danych

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `title` | VARCHAR(255) | Tytuł |
| `content` | LONGTEXT | Treść |
| `status` | VARCHAR(20) | `active` \| `pending` \| `expired` \| `draft` \| `archived` |
| `is_urgent` | TINYINT(1) | Czy pilne |
| `priority` | TINYINT | Priorytet (0–10) |
| `valid_from` | DATETIME | Od kiedy widoczne |
| `valid_until` | DATETIME | Do kiedy widoczne |
| `is_global` | TINYINT(1) | Widoczne dla wszystkich obozów |
| `submitted_camp_id` | BIGINT UNSIGNED | Jeśli zgłoszone przez obóz |
| `submitted_staff_id` | BIGINT UNSIGNED | Kto z kadry zgłosił |
| `approved_by_user_id` | BIGINT UNSIGNED | WP User ID zatwierdzającego |
| `approved_at` | DATETIME | Kiedy zatwierdzone |

### Przepływ statusów

```
draft → pending → active → expired/archived
              ↗
     (obóz może zgłosić)
```

### Kierowanie do obozów

Ogłoszenia `is_global = 1` widoczne są dla wszystkich obozów.
Ogłoszenia `is_global = 0` są powiązane z obozami przez tabelę `bm_announcement_camps`.

### Główne metody

```php
AnnouncementRepository::get_for_camp(int $camp_id): array   // aktywne dla obozu
AnnouncementRepository::count_pending(): int                 // badge w menu
AnnouncementRepository::expire_overdue(): int                // wywoływane przez cron
```

---

## Moduł: Meldunki dzienne

**Klasa**: `DailyCountRepository` (`src/Modules/Camps/DailyCountRepository.php`)

### Model danych

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `camp_id` | BIGINT UNSIGNED | FK → bm_camps |
| `count_date` | DATE | Data meldunku (UNIQUE per obóz) |
| `participants` | INT UNSIGNED | Liczba uczestników |
| `staff` | INT UNSIGNED | Liczba kadry |
| `workers` | INT UNSIGNED | Liczba pracowników obsługi |
| `notes` | TEXT | Uwagi |
| `status` | VARCHAR(20) | `none` \| `submitted` \| `confirmed` |
| `submitted_by` | BIGINT UNSIGNED | staff_id osoby składającej |
| `submitted_at` | DATETIME | Kiedy złożono |

### Główne metody

```php
DailyCountRepository::is_submitted_today(int $camp_id): bool
DailyCountRepository::get_missing_camps_for_date(string $date): array
DailyCountRepository::upsert(int $camp_id, string $date, array $data): bool
```

### Integracja z Cron

Codziennie o 08:00 zadanie `bm_daily_reminders` sprawdza, które obozy nie złożyły meldunku i wysyła email do administratora.

---

## Moduł: Pogoda

### Klasy

| Klasa | Plik | Opis |
|-------|------|------|
| `WeatherService` | `Weather/WeatherService.php` | Pobieranie i cache pogody |
| `WeatherProviderInterface` | `Weather/WeatherProviderInterface.php` | Kontrakt dla dostawców |
| `OpenMeteoProvider` | `Weather/OpenMeteoProvider.php` | Integracja Open-Meteo API |
| `ImgwAlertsSync` | `Weather/ImgwAlertsSync.php` | Synchronizacja IMGW |
| `WeatherAlertRepository` | `Weather/WeatherAlertRepository.php` | CRUD ostrzeżeń |

### Model danych (bm_weather_alerts)

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `title` | VARCHAR(255) | Tytuł ostrzeżenia |
| `message` | TEXT | Treść |
| `type` | VARCHAR(20) | `info` \| `warning` \| `danger` |
| `source` | VARCHAR(20) | `manual` \| `imgw` |
| `external_id` | VARCHAR(100) | ID z systemu IMGW |
| `is_active` | TINYINT(1) | Aktywne/nieaktywne |
| `is_urgent` | TINYINT(1) | Pilne |
| `valid_from` | DATETIME | Ważne od |
| `valid_until` | DATETIME | Ważne do |

### Konfiguracja IMGW

```php
// Ustawienia przechowywane w opcji 'bm_imgw_settings'
[
    'enabled'          => bool,    // czy synchronizacja włączona
    'sync_interval'    => string,  // 'hourly' | 'twicedaily' | 'daily'
    'voivodeship_code' => string,  // kod GUS województwa
    'county_teryt'     => string,  // 4-cyfrowy kod TERYT powiatu
]
```

API IMGW: `https://danepubliczne.imgw.pl/api/data/warningsmeteo`

> Pusta odpowiedź (0 bajtów, HTTP 200) oznacza brak aktywnych ostrzeżeń – **nie jest to błąd**.

### Dostawcy pogody

Plugin obsługuje wzorzec Provider:

```php
interface WeatherProviderInterface {
    public function get_current(): array;
    public function get_forecast(int $days = 5): array;
}
```

Implementacje: `OpenMeteoProvider` (domyślny, bezpłatny API).

---

## Moduł: Plan dnia

**Klasa**: `ScheduleRepository` (`src/Modules/Schedule/ScheduleRepository.php`)

### Model danych

#### bm_plan_headers (nagłówki planów)

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `plan_date` | DATE | Data planu |
| `title` | VARCHAR(255) | Opcjonalny tytuł |
| `is_global` | TINYINT(1) | Widoczny dla wszystkich obozów |
| `status` | VARCHAR(20) | `active` \| `draft` \| `archived` |

#### bm_plan_items (pozycje planu)

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `plan_id` | BIGINT UNSIGNED | FK → bm_plan_headers |
| `time_from` | VARCHAR(10) | Godzina rozpoczęcia (np. "08:00") |
| `time_to` | VARCHAR(10) | Godzina zakończenia |
| `title` | VARCHAR(255) | Tytuł pozycji |
| `description` | TEXT | Opis |
| `category` | VARCHAR(30) | `apel` \| `posilek` \| `cisza_nocna` \| `zajecia` \| `zbiorka` \| `info` \| `inne` |
| `item_status` | VARCHAR(20) | `active` \| `changed` \| `cancelled` |
| `is_mandatory` | TINYINT(1) | Obowiązkowa pozycja |
| `sort_order` | INT | Kolejność wyświetlania (drag&drop) |
| `is_new_today` | TINYINT(1) | Nowa na dziś (flaga manualna) |
| `is_updated_today` | TINYINT(1) | Zaktualizowana dziś (flaga manualna) |

#### bm_plan_item_revisions (historia zmian)

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `item_id` | BIGINT UNSIGNED | FK → bm_plan_items |
| `change_type` | VARCHAR(20) | `created` \| `updated` \| `cancelled` |
| `old_data` | LONGTEXT | JSON poprzednich wartości |
| `changed_by` | BIGINT UNSIGNED | WP User ID |
| `changed_at` | DATETIME | Kiedy zmieniono |

#### bm_plan_camps (przypisanie planu do obozów)

```sql
PRIMARY KEY (plan_id, camp_id)
```

### Kluczowe operacje

```php
ScheduleRepository::get_header_by_date(string $date): ?object
ScheduleRepository::get_items_for_plan(int $plan_id): array
ScheduleRepository::copy_from_date(string $from_date, string $to_date): array
// Zwraca ['header_id' => N] lub ['error' => 'source_not_found']

ScheduleRepository::reorder_items(array $ordered_ids): void
// Aktualizuje sort_order dla tablicy ID w podanej kolejności

ScheduleRepository::reset_today_flags(int $plan_id): void
// Zeruje is_new_today, is_updated_today dla wszystkich pozycji
```

### Drag & drop (admin)

Sortable.js jest ładowany CDN tylko na stronie edycji planu. Po przeciągnięciu wysyła AJAX `wp_ajax_bm_reorder_plan_items` z tablicą ID w nowej kolejności.

---

## Moduł: Rezerwacje

### Klasy

| Klasa | Opis |
|-------|------|
| `ResourceRepository` | CRUD zasobów i blokad technicznych |
| `ReservationRepository` | CRUD rezerwacji + anti-double-booking |
| `ReservationNotifier` | Powiadomienia email o zmianach statusu |

### Model danych: Zasoby (bm_resources)

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `name` | VARCHAR(255) | Nazwa zasobu |
| `type` | VARCHAR(30) | `ognisko` \| `boisko` \| `sala` \| `sprzet` \| `inne` |
| `status` | VARCHAR(20) | `active` \| `inactive` |
| `available_from` | TIME | Godziny dostępności od |
| `available_to` | TIME | Godziny dostępności do |
| `min_duration_minutes` | INT UNSIGNED | Min. czas rezerwacji (0 = brak) |
| `max_duration_minutes` | INT UNSIGNED | Max. czas rezerwacji (0 = brak) |
| `min_advance_hours` | INT UNSIGNED | Min. wyprzedzenie |
| `max_advance_days` | INT UNSIGNED | Max. z wyprzedzeniem (30 dni) |
| `cancel_advance_hours` | INT UNSIGNED | Min. godzin przed do anulowania |
| `max_reservations_per_camp` | INT UNSIGNED | Limit aktywnych dla obozu (0 = brak) |
| `is_blocked` | TINYINT(1) | Globalna blokada |
| `block_reason` | VARCHAR(255) | Powód globalnej blokady |
| `block_from/block_to` | DATETIME | Zakres globalnej blokady |

### Model danych: Rezerwacje (bm_resource_reservations)

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `resource_id` | BIGINT UNSIGNED | FK → bm_resources |
| `camp_id` | BIGINT UNSIGNED | FK → bm_camps |
| `staff_id` | BIGINT UNSIGNED | FK → bm_staff |
| `res_date` | DATE | Data rezerwacji |
| `start_time` | TIME | Godzina rozpoczęcia |
| `end_time` | TIME | Godzina zakończenia |
| `purpose` | TEXT | Cel/opis |
| `status` | VARCHAR(20) | `pending` \| `approved` \| `rejected` \| `cancelled` \| `expired` |
| `admin_comment` | TEXT | Komentarz admina |

### Przepływ statusów rezerwacji

```
           [Obóz składa wniosek]
                    │
                    ▼
              ● pending ──────────────────────────────────► ● cancelled
                    │                                        (przez obóz lub admin)
         ┌──────────┴──────────┐
         ▼                     ▼
    ● approved            ● rejected
         │
         └─ (po dacie) ─► ● expired (cron)
```

**Polityka blokowania slotów**: Zarówno `pending` jak i `approved` blokują termin.

### Ochrona przed double-booking

```php
// ReservationRepository::create_with_conflict_check()
$wpdb->query('START TRANSACTION');

// SELECT FOR UPDATE blokuje wiersze na czas transakcji (InnoDB)
$conflict = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM bm_resource_reservations
     WHERE resource_id = %d AND res_date = %s
       AND status IN ('pending','approved')
       AND start_time < %s AND end_time > %s
     FOR UPDATE",
    $resource_id, $date, $end_time, $start_time
));

if ($conflict > 0) { $wpdb->query('ROLLBACK'); return ['error' => 'conflict']; }

$wpdb->insert(...);
$wpdb->query('COMMIT');
```

> Wymaga silnika **InnoDB**. Chroni przed race condition przy równoczesnych requestach.

### Kody błędów `create_with_conflict_check()`

| Klucz błędu | Znaczenie |
|-------------|-----------|
| `unavailable` | Zasób nieaktywny lub poza godzinami |
| `blocked` | Globalna blokada lub blokada techniczna |
| `too_short` | Czas krótszy niż `min_duration_minutes` |
| `too_long` | Czas dłuższy niż `max_duration_minutes` |
| `camp_limit` | Przekroczono `max_reservations_per_camp` |
| `conflict` | Kolizja terminów (double-booking) |
| `db_error` | Nieoczekiwany błąd bazy |

### Blokady techniczne (bm_resource_blocks)

Administrator może zablokować zasób na określony przedział czasu (np. konserwacja). Blokady są sprawdzane niezależnie od statusu zasobu.

```php
ResourceRepository::add_block(int $resource_id, array $data): int
ResourceRepository::delete_block(int $id): bool
ResourceRepository::has_block_conflict(int $resource_id, string $start, string $end): bool
```

### Powiadomienia email

`ReservationNotifier` nasłuchuje hooków WP:

| Hook | Akcja |
|------|-------|
| `bm_reservation_created` | Email do admina + email do kadry obozu |
| `bm_reservation_status_changed` | Email do kadry (zatwierdzona/odrzucona/anulowana) |

Szablony: `templates/email/reservation_{created,approved,rejected,cancelled}.php`

### Wygasanie rezerwacji

Cron `bm_expire_reservations` (daily 00:05) zmienia status `pending` na `expired` dla rezerwacji z datą w przeszłości.

---

## Wspólne wzorce modułów

### Sanityzacja danych wejściowych

Każde repozytorium sanityzuje dane przed zapisem:
```php
'name'    => sanitize_text_field($data['name']),
'content' => sanitize_textarea_field($data['content']),
'email'   => sanitize_email($data['email']),
```

### Escaping danych wyjściowych

W szablonach PHP zawsze używamy:
```php
echo esc_html($value);
echo esc_attr($attr);
echo esc_url($url);
echo wp_kses_post($html_content);
```

### Nonce w formularzach

```php
// W formularzu:
wp_nonce_field('bm_save_camp');

// W handlerze:
check_admin_referer('bm_save_camp');
```

---

## Moduł: Jadłospis

**Klasy**: `MealRepository` (`src/Modules/Menu/MealRepository.php`), `MenuPage`, `MenuController`

### Model danych

**bm_meal_days** – nagłówek dnia

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `meal_date` | DATE | Data (UNIQUE) |
| `notes` | TEXT | Notatki do dnia |
| `status` | VARCHAR(20) | `published` \| `draft` |

**bm_meal_items** – pozycje jadłospisu

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `meal_day_id` | BIGINT UNSIGNED | FK → bm_meal_days |
| `meal_type` | VARCHAR(30) | `sniadanie` \| `obiad` \| `kolacja` \| `inne` |
| `time_from` | VARCHAR(10) | Godzina podania |
| `title` | VARCHAR(255) | Nazwa posiłku |
| `description` | TEXT | Opis |
| `location` | VARCHAR(255) | Miejsce wydawania |
| `diet_info` | VARCHAR(255) | Informacje dietetyczne |
| `allergens` | VARCHAR(255) | Alergeny |
| `sort_order` | INT | Kolejność wyświetlania |
| `is_new_today` | TINYINT(1) | Flaga nowej pozycji |
| `is_updated_today` | TINYINT(1) | Flaga zmienionej pozycji |

### Kluczowe metody

- `get_day($date)` – pobiera dzień z pozycjami
- `get_day_for_frontend($date)` – tylko status `published`
- `copy_from_date($from, $to)` – kopiuje jadłospis na inny dzień
- `get_available_dates()` – lista dat z opublikowanym jadłospisem

### Alpine.js

Komponent `bmMenu()` – widok dzienny i tygodniowy z filtrowaniem po `meal_type`.

---

## Moduł: Komunikacja

**Klasy**: `ConversationRepository` (`src/Modules/Communication/ConversationRepository.php`), `CommunicationPage`, `CommunicationController`

### Model danych

**bm_conv_threads** – wątki rozmów

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `camp_id` | BIGINT UNSIGNED | FK → bm_camps |
| `subject` | VARCHAR(255) | Temat wątku |
| `status` | VARCHAR(20) | `open` \| `closed` \| `archived` |
| `priority` | VARCHAR(20) | `low` \| `normal` \| `high` \| `urgent` |
| `is_urgent` | TINYINT(1) | Flaga pilności |
| `assigned_to` | BIGINT UNSIGNED | WP user ID obsługującego |
| `last_message_at` | DATETIME | Data ostatniej wiadomości |
| `unread_admin` | SMALLINT UNSIGNED | Licznik nieprzeczytanych przez admina |
| `unread_camp` | SMALLINT UNSIGNED | Licznik nieprzeczytanych przez obóz |
| `created_by_staff_id` | BIGINT UNSIGNED | FK → bm_staff |

**bm_conv_messages** – wiadomości w wątku

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `thread_id` | BIGINT UNSIGNED | FK → bm_conv_threads |
| `author_type` | VARCHAR(10) | `staff` \| `admin` |
| `author_id` | BIGINT UNSIGNED | ID autora |
| `content` | LONGTEXT | Treść wiadomości |
| `is_system` | TINYINT(1) | Wiadomość systemowa |
| `attachment_url` | VARCHAR(500) | URL załącznika |

### Logika unread

Liczniki `unread_admin` / `unread_camp` aktualizowane atomicznie SQL UPDATE przy każdej nowej wiadomości. Zerowane przez `mark_read_admin(id)` / `mark_read_camp(id)`.

Badge w menu admina: `ConversationRepository::count_unread_admin()`.

### Izolacja danych

`get_thread_for_camp(id, camp_id)` – zwraca `null` gdy camp_id nie pasuje. Żaden obóz nie widzi wątków innych obozów.

### Alpine.js

Komponent `bmConversations()` – lista wątków, tworzenie, podgląd z odpowiedzią, lokalny licznik unread.

---

## Moduł: Pomoc

**Klasy**: `HelpRepository` (`src/Modules/Help/HelpRepository.php`), `HelpPage`, `HelpController`

### Model danych

**bm_help_articles**

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `title` | VARCHAR(255) | Tytuł artykułu |
| `content` | LONGTEXT | Treść (HTML przez wp_editor) |
| `excerpt` | TEXT | Krótki opis |
| `category` | VARCHAR(100) | Kategoria tekstowa |
| `type` | VARCHAR(20) | `article` \| `faq` \| `contact` \| `procedure` \| `instruction` |
| `status` | VARCHAR(20) | `published` \| `draft` |
| `is_pinned` | TINYINT(1) | Przypięty na górze listy |
| `is_alarm` | TINYINT(1) | Ważny / alarmowy |
| `sort_order` | INT | Kolejność |

### Kluczowe metody

- `get_all($filters)` – filtrowanie po `type`, `status`, `category`, `search` (LIKE)
- `get_categories()` – unikalne wartości kategorii z tabeli (dynamiczne)
- `count_important()` – liczba wpisów z `is_alarm = 1`

### Alpine.js

Komponent `bmHelp()` – filtry po typie/kategorii/szukaj, computed getters: `alarmArticles`, `pinnedArticles`, `faqArticles`, `contactArticles`.

---

## Moduł: Formularze i Zgłoszenia

**Klasy**: `FormRepository`, `SubmissionRepository` (`src/Modules/Forms/`), `FormsPage`, `FormsController`

### Architektura (dwie warstwy)

1. **Definicje formularzy** – administrator tworzy formularze z polami przez builder
2. **Zgłoszenia** – obozy wypełniają formularze i składają zgłoszenia (tickety)

### Kluczowe założenie: snapshot

Każde zgłoszenie przechowuje **dwie kolumny JSON**:
- `form_snapshot` – definicja formularza + pól **w chwili wysłania**
- `submission_data` – wypełnione wartości `field_key → value`

Zmiana definicji formularza przez admina **nie wpływa** na istniejące zgłoszenia.

### Model danych

**bm_forms** – definicje formularzy

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `name` | VARCHAR(255) | Nazwa formularza |
| `description` | TEXT | Opis |
| `category` | VARCHAR(50) | `techniczne` \| `organizacyjne` \| `medyczne` \| `magazynowe` \| `inne` |
| `status` | VARCHAR(20) | `active` \| `inactive` |
| `is_global` | TINYINT(1) | Widoczny dla wszystkich obozów |
| `is_pinned` | TINYINT(1) | Wyróżniony / przypięty |
| `sort_order` | INT | Kolejność wyświetlania |
| `info_before` | TEXT | Tekst wyświetlany nad formularzem |
| `info_after` | TEXT | Tekst wyświetlany po wysłaniu |
| `created_by` | BIGINT UNSIGNED | WP user ID twórcy |

**bm_form_fields** – pola formularza

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `form_id` | BIGINT UNSIGNED | FK → bm_forms |
| `label` | VARCHAR(255) | Etykieta pola |
| `field_key` | VARCHAR(100) | Klucz techniczny (unikalny w formularzu) |
| `type` | VARCHAR(20) | `text` \| `textarea` \| `number` \| `email` \| `tel` \| `select` \| `radio` \| `checkbox` \| `date` \| `file` |
| `is_required` | TINYINT(1) | Pole wymagane |
| `placeholder` | VARCHAR(255) | Placeholder |
| `help_text` | VARCHAR(500) | Opis pomocniczy |
| `options_json` | LONGTEXT | JSON array opcji dla select/radio/checkbox |
| `default_value` | VARCHAR(255) | Wartość domyślna |
| `validation` | VARCHAR(100) | Reguła walidacji |
| `sort_order` | INT | Kolejność pola |

**bm_form_camps** – widoczność dla wybranych obozów

| Kolumna | Typ | Opis |
|---------|-----|------|
| `form_id` | BIGINT UNSIGNED | FK → bm_forms |
| `camp_id` | BIGINT UNSIGNED | FK → bm_camps |

**bm_submissions** – zgłoszenia

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `form_id` | BIGINT UNSIGNED | FK → bm_forms |
| `camp_id` | BIGINT UNSIGNED | FK → bm_camps |
| `staff_id` | BIGINT UNSIGNED | FK → bm_staff (składający) |
| `category` | VARCHAR(50) | Dziedziczona z formularza |
| `status` | VARCHAR(20) | `new` \| `in_progress` \| `waiting` \| `closed` \| `cancelled` |
| `priority` | VARCHAR(20) | `low` \| `normal` \| `high` \| `urgent` |
| `admin_comment` | TEXT | Komentarz admina widoczny dla obozu |
| `assigned_to` | BIGINT UNSIGNED | WP user ID obsługującego |
| `form_snapshot` | LONGTEXT | JSON – snapshot definicji formularza |
| `submission_data` | LONGTEXT | JSON – wypełnione wartości |

**bm_submission_attachments** – pliki

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `submission_id` | BIGINT UNSIGNED | FK → bm_submissions |
| `original_name` | VARCHAR(255) | Oryginalna nazwa pliku |
| `stored_name` | VARCHAR(255) | Nazwa na dysku (unikalna) |
| `mime_type` | VARCHAR(100) | MIME type (weryfikowany przez finfo) |
| `file_size` | BIGINT UNSIGNED | Rozmiar w bajtach (max 10 MB) |
| `file_path` | VARCHAR(1000) | Absolutna ścieżka na dysku |

**bm_submission_history** – audit log

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED | Klucz główny |
| `submission_id` | BIGINT UNSIGNED | FK → bm_submissions |
| `changed_by` | BIGINT UNSIGNED | WP user ID zmieniającego |
| `from_status` | VARCHAR(20) | Poprzedni status |
| `to_status` | VARCHAR(20) | Nowy status |
| `note` | TEXT | Notatka do zmiany |
| `created_at` | DATETIME | Czas zmiany |

### Bezpieczeństwo plików

- Upload przez `SubmissionRepository::handle_upload()` z weryfikacją MIME przez `finfo`
- Pliki w `wp-content/uploads/basemgmt/{submission_id}/` z `.htaccess deny from all`
- Pobieranie przez chroniony endpoint REST (weryfikacja `camp_id`) lub admin-post (wymaga `manage_basemgmt`)
- Obóz nie może pobrać załącznika innego obozu

### Admin-post actions

| Action | Handler |
|--------|---------|
| `bm_save_form` | `FormsPage::handle_save_form()` |
| `bm_delete_form` | `FormsPage::handle_delete_form()` |
| `bm_save_form_field` | `FormsPage::handle_save_field()` |
| `bm_delete_form_field` | `FormsPage::handle_delete_field()` |
| `bm_update_submission` | `FormsPage::handle_update_submission()` |
| `bm_download_attachment` | `FormsPage::handle_download_attachment()` |

### Alpine.js

- `bmForms()` – lista formularzy z filtrowaniem po kategorii, otwieranie, wypełnianie i wysyłanie
- `bmSubmissions()` – lista własnych zgłoszeń z filtrem statusu, podgląd detalu z historią i załącznikami
