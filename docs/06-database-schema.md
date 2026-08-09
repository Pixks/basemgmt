# 06 – Schemat bazy danych

Wszystkie tabele używają prefiksu `{wp_prefix}bm_`. Domyślnie np. `wp_bm_camps`.

> Wymagany silnik: **InnoDB** (dla transakcji i `SELECT FOR UPDATE`)

---

## Diagram relacji (ERD uproszczony)

```
bm_camps
    │
    ├─── bm_camp_cases
    │       └──< bm_camp_case_history
    ├─── bm_camp_organizers
    ├──< bm_camp_checklist_items
    ├─── bm_camp_prearrival
    ├──< bm_camp_documents
    │       └──< bm_camp_document_versions
    ├──< bm_camp_payment_schedules
    ├──< bm_camp_payments
    ├──< bm_camp_actual_stays
    ├──< bm_camp_actual_meals
    ├──< bm_camp_service_usages
    ├──< bm_camp_pricing_rules
    ├──< bm_camp_settlements
    │       └──< bm_camp_settlement_lines
    ├──< bm_camp_settlement_issues
    └─── bm_camp_closures
    │
    ├──< bm_staff              (kadra przypisana do obozu)
    │       └──< bm_sessions   (sesje frontendowe)
    │
    ├──< bm_daily_counts       (meldunki dzienne)
    │
    ├──< bm_announcement_camps >──── bm_announcements
    │
    ├──< bm_plan_camps >──────────── bm_plan_headers
    │                                    └──< bm_plan_items
    │                                            └──< bm_plan_item_revisions
    │
    ├──< bm_resource_reservations >── bm_resources
    │                                     └──< bm_resource_blocks
    │
    ├──< bm_conv_threads       (wątki komunikacji)
    │       └──< bm_conv_messages
    │
    ├──< bm_form_camps >──────────── bm_forms
    │                                    └──< bm_form_fields
    │
    └──< bm_submissions        (zgłoszenia)
            ├──< bm_submission_attachments
            └──< bm_submission_history

bm_weather_alerts  (niezależna tabela)
bm_meal_days       (jadłospis – globalna, nie per-obóz)
    └──< bm_meal_items
bm_meal_diets      (słownik diet)
bm_meal_locations  (słownik miejsc wydawania)
bm_help_articles   (baza pomocy – globalna)
bm_plan_templates  (globalne szablony planów dnia)
    └──< bm_plan_template_items
bm_operation_logs  (centralny dziennik operacji)
```

---

## Tabele szczegółowo

### bm_camps

| Kolumna | Typ | Null | Domyślnie | Opis |
|---------|-----|------|-----------|------|
| `id` | BIGINT UNSIGNED AI | NOT NULL | – | Klucz główny |
| `name` | VARCHAR(255) | NOT NULL | – | Nazwa obozu |
| `start_date` | DATE | NOT NULL | – | Data rozpoczęcia |
| `end_date` | DATE | NOT NULL | – | Data zakończenia |
| `status` | VARCHAR(20) | NOT NULL | `active` | `active` / `inactive` / `archived` |
| `created_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | |
| `updated_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | Auto-update |

**Indeksy**: `idx_status(status)`, `idx_dates(start_date, end_date)`

---

### bm_camp_cases

| Kolumna | Typ | Null | Domyślnie | Opis |
|---------|-----|------|-----------|------|
| `camp_id` | BIGINT UNSIGNED | NOT NULL | – | UNIQUE FK logiczny → bm_camps.id |
| `process_stage` | VARCHAR(40) | NOT NULL | `inquiry` | Etap procesu handlowo-formalnego |
| `needs_attention` | TINYINT(1) | NOT NULL | 0 | Flaga pilnej reakcji |
| `risk_level` | VARCHAR(20) | NOT NULL | `low` | Niskie / średnie / wysokie / krytyczne |
| `owner_user_id` | BIGINT UNSIGNED | YES | NULL | Odpowiedzialny użytkownik WP |
| `next_action_due_date` | DATE | YES | NULL | Termin kolejnego działania |
| `notes` | TEXT | YES | NULL | Notatki procesowe |
| `readiness_notes` | TEXT | YES | NULL | Uwagi do gotowości |

**Indeksy**: `uniq_camp(camp_id)`, `idx_stage`, `idx_attention`, `idx_risk`

---

### bm_camp_case_history

Historia zmian etapów procesu dla obozu: `old_stage`, `new_stage`, `changed_by`, `change_note`, `created_at`.

---

### bm_camp_organizers

Dane organizatora i rozliczeń: nazwa organizatora, osoba kontaktowa, kanały kontaktu, dane fakturowe, kontakty do rozliczeń i notatki.

---

### bm_camp_checklist_items

Checklista gotowości obozu: strona odpowiedzialna (`organizer` / `center` / `shared`), status, termin, komentarz i ślad ukończenia.

---

### bm_camp_prearrival

Dane operacyjne przed przyjazdem: termin i godziny przyjazdu/wyjazdu, deklarowane liczebności, diety, alergeny, plan infrastruktury, potrzeby dodatkowe oraz kontakty upoważnione.

---

### bm_staff

| Kolumna | Typ | Null | Domyślnie | Opis |
|---------|-----|------|-----------|------|
| `id` | BIGINT UNSIGNED AI | NOT NULL | – | Klucz główny |
| `camp_id` | BIGINT UNSIGNED | NOT NULL | – | FK → bm_camps.id |
| `first_name` | VARCHAR(100) | NOT NULL | – | Imię |
| `last_name` | VARCHAR(100) | NOT NULL | – | Nazwisko |
| `email` | VARCHAR(255) | YES | NULL | Email do powiadomień |
| `phone` | VARCHAR(50) | YES | NULL | Telefon |
| `role_in_camp` | VARCHAR(100) | YES | NULL | Rola w obozie |
| `security_code_hash` | VARCHAR(255) | NOT NULL | `''` | bcrypt hash kodu |
| `is_active` | TINYINT(1) | NOT NULL | 1 | Czy może się logować |
| `failed_attempts` | TINYINT UNSIGNED | NOT NULL | 0 | Liczba nieudanych prób |
| `locked_until` | DATETIME | YES | NULL | Blokada do kiedy |
| `permanent_lock` | TINYINT(1) | NOT NULL | 0 | Blokada trwała – wymaga odblokowania przez admina |
| `last_login` | DATETIME | YES | NULL | Ostatnie logowanie |
| `created_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | |
| `updated_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | Auto-update |

**Indeksy**: `idx_camp(camp_id)`, `idx_active(is_active)`

---

### bm_sessions

| Kolumna | Typ | Null | Domyślnie | Opis |
|---------|-----|------|-----------|------|
| `id` | BIGINT UNSIGNED AI | NOT NULL | – | Klucz główny |
| `token` | VARCHAR(64) | NOT NULL | – | 64-znakowy hex token |
| `staff_id` | BIGINT UNSIGNED | NOT NULL | – | FK → bm_staff.id |
| `camp_id` | BIGINT UNSIGNED | NOT NULL | – | FK → bm_camps.id |
| `ip_address` | VARCHAR(45) | YES | NULL | Adres IP |
| `created_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | |
| `expires_at` | DATETIME | NOT NULL | – | Wygaśnięcie (indeks) |

**Indeksy**: `UNIQUE uniq_token(token)`, `idx_expires(expires_at)`, `idx_staff(staff_id)`

---

### bm_daily_counts

| Kolumna | Typ | Null | Domyślnie | Opis |
|---------|-----|------|-----------|------|
| `id` | BIGINT UNSIGNED AI | NOT NULL | – | Klucz główny |
| `camp_id` | BIGINT UNSIGNED | NOT NULL | – | FK → bm_camps.id |
| `count_date` | DATE | NOT NULL | – | Data meldunku |
| `participants` | INT UNSIGNED | NOT NULL | 0 | Uczestnicy |
| `staff` | INT UNSIGNED | NOT NULL | 0 | Kadra |
| `workers` | INT UNSIGNED | NOT NULL | 0 | Pracownicy |
| `notes` | TEXT | YES | NULL | Uwagi |
| `submitted_by` | BIGINT UNSIGNED | YES | NULL | FK → bm_staff.id |
| `status` | VARCHAR(20) | NOT NULL | `none` | `none` / `submitted` / `confirmed` |
| `submitted_at` | DATETIME | YES | NULL | |
| `created_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | |
| `updated_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | Auto-update |

**Indeksy**: `UNIQUE uniq_camp_date(camp_id, count_date)`, `idx_camp`, `idx_date`, `idx_status`

---

### bm_announcements

| Kolumna | Typ | Null | Domyślnie | Opis |
|---------|-----|------|-----------|------|
| `id` | BIGINT UNSIGNED AI | NOT NULL | – | |
| `title` | VARCHAR(255) | NOT NULL | – | Tytuł |
| `content` | LONGTEXT | NOT NULL | – | Treść HTML |
| `status` | VARCHAR(20) | NOT NULL | `active` | `active/pending/expired/archived/draft` |
| `is_urgent` | TINYINT(1) | NOT NULL | 0 | Pilne |
| `priority` | TINYINT | NOT NULL | 0 | Wyższy = ważniejsze |
| `valid_from` | DATETIME | NOT NULL | – | Widoczne od |
| `valid_until` | DATETIME | NOT NULL | – | Widoczne do |
| `is_global` | TINYINT(1) | NOT NULL | 1 | Dla wszystkich obozów |
| `attachment_url` | VARCHAR(500) | YES | NULL | URL załącznika |
| `submitted_camp_id` | BIGINT UNSIGNED | YES | NULL | Jeśli zgłoszone przez obóz |
| `submitted_staff_id` | BIGINT UNSIGNED | YES | NULL | |
| `approved_by_user_id` | BIGINT UNSIGNED | YES | NULL | WP User ID |
| `approved_at` | DATETIME | YES | NULL | |
| `created_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | |
| `updated_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | Auto-update |

**Indeksy**: `idx_status`, `idx_global`, `idx_valid(valid_from, valid_until)`, `idx_priority`

---

### bm_announcement_camps

| Kolumna | Typ | Null | Opis |
|---------|-----|------|------|
| `announcement_id` | BIGINT UNSIGNED | NOT NULL | FK → bm_announcements.id |
| `camp_id` | BIGINT UNSIGNED | NOT NULL | FK → bm_camps.id |

**Indeksy**: `PRIMARY KEY(announcement_id, camp_id)`, `idx_camp(camp_id)`

---

### bm_weather_alerts

| Kolumna | Typ | Null | Domyślnie | Opis |
|---------|-----|------|-----------|------|
| `id` | BIGINT UNSIGNED AI | NOT NULL | – | |
| `title` | VARCHAR(255) | NOT NULL | – | Tytuł |
| `message` | TEXT | NOT NULL | – | Treść |
| `type` | VARCHAR(20) | NOT NULL | `info` | `info/warning/danger` |
| `source` | VARCHAR(20) | NOT NULL | `manual` | `manual/imgw` |
| `external_id` | VARCHAR(100) | YES | NULL | ID z IMGW |
| `is_active` | TINYINT(1) | NOT NULL | 1 | Aktywne |
| `is_urgent` | TINYINT(1) | NOT NULL | 0 | Pilne |
| `valid_from` | DATETIME | YES | NULL | |
| `valid_until` | DATETIME | YES | NULL | |
| `created_by` | BIGINT UNSIGNED | YES | NULL | WP User ID |
| `created_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | |
| `updated_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | Auto-update |

**Indeksy**: `idx_active`, `idx_until`, `idx_source`, `idx_external_id`

---

### bm_plan_headers

| Kolumna | Typ | Null | Domyślnie | Opis |
|---------|-----|------|-----------|------|
| `id` | BIGINT UNSIGNED AI | NOT NULL | – | |
| `plan_date` | DATE | NOT NULL | – | Data planu (1 plan per data) |
| `title` | VARCHAR(255) | NOT NULL | `''` | Opcjonalny tytuł |
| `is_global` | TINYINT(1) | NOT NULL | 1 | Globalny dla wszystkich obozów |
| `status` | VARCHAR(20) | NOT NULL | `active` | `active/draft/archived` |
| `created_by` | BIGINT UNSIGNED | YES | NULL | WP User ID |
| `created_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | |
| `updated_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | Auto-update |

**Indeksy**: `idx_date`, `idx_status`, `idx_global`

---

### bm_plan_items

| Kolumna | Typ | Null | Domyślnie | Opis |
|---------|-----|------|-----------|------|
| `id` | BIGINT UNSIGNED AI | NOT NULL | – | |
| `plan_id` | BIGINT UNSIGNED | NOT NULL | – | FK → bm_plan_headers.id |
| `time_from` | VARCHAR(10) | NOT NULL | `''` | np. "08:00" |
| `time_to` | VARCHAR(10) | NOT NULL | `''` | np. "08:30" |
| `title` | VARCHAR(255) | NOT NULL | – | Tytuł pozycji |
| `description` | TEXT | YES | NULL | Opis |
| `category` | VARCHAR(30) | NOT NULL | `inne` | Kategoria |
| `item_status` | VARCHAR(20) | NOT NULL | `active` | `active/changed/cancelled` |
| `is_mandatory` | TINYINT(1) | NOT NULL | 0 | Obowiązkowa |
| `sort_order` | INT | NOT NULL | 0 | Kolejność (drag&drop) |
| `is_new_today` | TINYINT(1) | NOT NULL | 0 | Nowa pozycja na dziś |
| `is_updated_today` | TINYINT(1) | NOT NULL | 0 | Zaktualizowana dziś |
| `created_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | |
| `updated_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | Auto-update |

**Indeksy**: `idx_plan(plan_id)`, `idx_order(plan_id, sort_order)`

---

### bm_plan_item_revisions

| Kolumna | Typ | Null | Opis |
|---------|-----|------|------|
| `id` | BIGINT UNSIGNED AI | NOT NULL | |
| `item_id` | BIGINT UNSIGNED | NOT NULL | FK → bm_plan_items.id |
| `change_type` | VARCHAR(20) | NOT NULL | `created/updated/cancelled` |
| `old_data` | LONGTEXT | YES | JSON poprzedniej wersji |
| `changed_by` | BIGINT UNSIGNED | YES | WP User ID |
| `changed_at` | DATETIME | NOT NULL | |

**Indeksy**: `idx_item(item_id)`

---

### bm_plan_camps

| Kolumna | Typ | Null | Opis |
|---------|-----|------|------|
| `plan_id` | BIGINT UNSIGNED | NOT NULL | FK → bm_plan_headers.id |
| `camp_id` | BIGINT UNSIGNED | NOT NULL | FK → bm_camps.id |

**Indeksy**: `PRIMARY KEY(plan_id, camp_id)`, `idx_camp(camp_id)`

---

### bm_resources

| Kolumna | Typ | Null | Domyślnie | Opis |
|---------|-----|------|-----------|------|
| `id` | BIGINT UNSIGNED AI | NOT NULL | – | |
| `name` | VARCHAR(255) | NOT NULL | – | Nazwa zasobu |
| `type` | VARCHAR(30) | NOT NULL | `inne` | Typ |
| `description` | TEXT | YES | NULL | Opis |
| `status` | VARCHAR(20) | NOT NULL | `active` | `active/inactive` |
| `rules` | TEXT | YES | NULL | Zasady korzystania |
| `available_from` | TIME | NOT NULL | `06:00:00` | Dostępny od |
| `available_to` | TIME | NOT NULL | `22:00:00` | Dostępny do |
| `min_duration_minutes` | INT UNSIGNED | NOT NULL | 0 | Min. czas (0=brak) |
| `max_duration_minutes` | INT UNSIGNED | NOT NULL | 0 | Max. czas (0=brak) |
| `min_advance_hours` | INT UNSIGNED | NOT NULL | 0 | Min. wyprzedzenie |
| `max_advance_days` | INT UNSIGNED | NOT NULL | 30 | Max. z wyprzedzeniem |
| `cancel_advance_hours` | INT UNSIGNED | NOT NULL | 0 | Min. h przed do anulowania |
| `max_reservations_per_camp` | INT UNSIGNED | NOT NULL | 0 | Limit dla obozu (0=brak) |
| `is_blocked` | TINYINT(1) | NOT NULL | 0 | Globalna blokada |
| `block_reason` | VARCHAR(255) | NOT NULL | `''` | Powód blokady |
| `block_from` | DATETIME | YES | NULL | Blokada od |
| `block_to` | DATETIME | YES | NULL | Blokada do |
| `created_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | |
| `updated_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | Auto-update |

**Indeksy**: `idx_status(status)`, `idx_type(type)`

---

### bm_resource_reservations

| Kolumna | Typ | Null | Domyślnie | Opis |
|---------|-----|------|-----------|------|
| `id` | BIGINT UNSIGNED AI | NOT NULL | – | |
| `resource_id` | BIGINT UNSIGNED | NOT NULL | – | FK → bm_resources.id |
| `camp_id` | BIGINT UNSIGNED | NOT NULL | – | FK → bm_camps.id |
| `staff_id` | BIGINT UNSIGNED | NOT NULL | 0 | FK → bm_staff.id |
| `res_date` | DATE | NOT NULL | – | Data rezerwacji |
| `start_time` | TIME | NOT NULL | – | Godzina od |
| `end_time` | TIME | NOT NULL | – | Godzina do |
| `purpose` | TEXT | YES | NULL | Cel/opis |
| `status` | VARCHAR(20) | NOT NULL | `pending` | Status rezerwacji |
| `admin_comment` | TEXT | YES | NULL | Komentarz admina |
| `created_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | |
| `updated_at` | DATETIME | NOT NULL | CURRENT_TIMESTAMP | Auto-update |

**Indeksy**: `idx_resource`, `idx_camp`, `idx_date`, `idx_status`, `idx_slot(resource_id, res_date, start_time, end_time)`

---

### bm_resource_blocks

| Kolumna | Typ | Null | Opis |
|---------|-----|------|------|
| `id` | BIGINT UNSIGNED AI | NOT NULL | |
| `resource_id` | BIGINT UNSIGNED | NOT NULL | FK → bm_resources.id |
| `reason` | VARCHAR(255) | NOT NULL | Powód blokady |
| `block_from` | DATETIME | NOT NULL | Blokada od |
| `block_to` | DATETIME | NOT NULL | Blokada do |
| `created_by` | BIGINT UNSIGNED | YES | WP User ID |
| `created_at` | DATETIME | NOT NULL | |

**Indeksy**: `idx_resource`, `idx_range(resource_id, block_from, block_to)`

---

## Użyteczne zapytania SQL

```sql
-- Obozy aktywne
SELECT * FROM wp_bm_camps WHERE status = 'active' ORDER BY name;

-- Kadra dla obozu (bez danych wrażliwych)
SELECT id, first_name, last_name, role_in_camp 
FROM wp_bm_staff 
WHERE camp_id = 1 AND is_active = 1;

-- Aktywne ogłoszenia dla obozu (globalne + przypisane)
SELECT a.* FROM wp_bm_announcements a
WHERE a.status = 'active'
  AND a.valid_from <= NOW() AND a.valid_until >= NOW()
  AND (
    a.is_global = 1
    OR EXISTS (
      SELECT 1 FROM wp_bm_announcement_camps ac 
      WHERE ac.announcement_id = a.id AND ac.camp_id = 1
    )
  )
ORDER BY a.priority DESC, a.valid_from DESC;

-- Konflikty rezerwacji dla zasobu
SELECT * FROM wp_bm_resource_reservations
WHERE resource_id = 1
  AND res_date = '2025-07-28'
  AND status IN ('pending','approved')
  AND start_time < '16:00:00'
  AND end_time   > '14:00:00';

-- Oczekujące rezerwacje (dla dashboardu)
SELECT r.*, res.name as resource_name, c.name as camp_name
FROM wp_bm_resource_reservations r
JOIN wp_bm_resources res ON res.id = r.resource_id
JOIN wp_bm_camps c ON c.id = r.camp_id
WHERE r.status = 'pending'
ORDER BY r.res_date ASC, r.start_time ASC;
```

---

### bm_meal_days

Nagłówek dnia jadłospisu. UNIQUE na `meal_date` – jeden rekord na dzień.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `meal_date` | DATE UNIQUE | Data jadłospisu |
| `notes` | TEXT | Notatki do dnia |
| `status` | VARCHAR(20) | `published` \| `draft` |
| `created_by` | BIGINT UNSIGNED | WP user ID |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP |

**Indeksy**: `idx_status (status)`

---

### bm_meal_items

Pozycje jadłospisu powiązane z dniem.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `meal_day_id` | BIGINT UNSIGNED | FK → bm_meal_days |
| `meal_type` | VARCHAR(30) | `sniadanie` \| `drugie_sniadanie` \| `obiad` \| `podwieczorek` \| `kolacja` \| `inne` |
| `time_from` | VARCHAR(10) | Godzina podania |
| `title` | VARCHAR(255) | Nazwa posiłku |
| `description` | TEXT | Opis |
| `location` | VARCHAR(255) | Miejsce wydawania |
| `diet_info` | VARCHAR(255) | Informacje dietetyczne |
| `allergens` | VARCHAR(255) | Alergeny |
| `sort_order` | INT | Kolejność |
| `is_new_today` | TINYINT(1) | Flaga nowej pozycji |
| `is_updated_today` | TINYINT(1) | Flaga zmiany |

**Indeksy**: `idx_day (meal_day_id)`, `idx_order (meal_day_id, sort_order)`

---

### bm_conv_threads

Wątki komunikacji obóz–administracja.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `camp_id` | BIGINT UNSIGNED | FK → bm_camps |
| `subject` | VARCHAR(255) | Temat wątku |
| `status` | VARCHAR(20) | `open` \| `closed` \| `archived` |
| `priority` | VARCHAR(20) | `low` \| `normal` \| `high` \| `urgent` |
| `is_urgent` | TINYINT(1) | Pilny |
| `assigned_to` | BIGINT UNSIGNED | WP user ID obsługującego |
| `last_message_at` | DATETIME | Czas ostatniej wiadomości |
| `unread_admin` | SMALLINT UNSIGNED | Nieprzeczytane przez admina |
| `unread_camp` | SMALLINT UNSIGNED | Nieprzeczytane przez obóz |
| `created_by_staff_id` | BIGINT UNSIGNED | FK → bm_staff |

**Indeksy**: `idx_camp`, `idx_status`, `idx_priority`, `idx_urgent`, `idx_last_msg`

---

### bm_conv_messages

Wiadomości w wątkach.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `thread_id` | BIGINT UNSIGNED | FK → bm_conv_threads |
| `author_type` | VARCHAR(10) | `staff` \| `admin` |
| `author_id` | BIGINT UNSIGNED | ID autora |
| `content` | LONGTEXT | Treść wiadomości |
| `is_system` | TINYINT(1) | Wiadomość systemowa |
| `attachment_url` | VARCHAR(500) | URL załącznika |
| `created_at` | DATETIME | |

**Indeksy**: `idx_thread (thread_id)`, `idx_date (created_at)`

---

### bm_help_articles

Artykuły bazy wiedzy.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `title` | VARCHAR(255) | Tytuł |
| `content` | LONGTEXT | Treść HTML |
| `excerpt` | TEXT | Krótki opis |
| `category` | VARCHAR(100) | Kategoria tekstowa |
| `type` | VARCHAR(20) | `article` \| `faq` \| `contact` \| `procedure` \| `instruction` |
| `status` | VARCHAR(20) | `published` \| `draft` |
| `is_pinned` | TINYINT(1) | Przypięty |
| `is_alarm` | TINYINT(1) | Alarmowy / ważny |
| `sort_order` | INT | Kolejność |
| `created_by` | BIGINT UNSIGNED | WP user ID |

**Indeksy**: `idx_type`, `idx_status`, `idx_pinned`, `idx_alarm`, `idx_category`, `idx_order`

---

### bm_forms

Definicje formularzy.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(255) | Nazwa formularza |
| `description` | TEXT | Opis |
| `category` | VARCHAR(50) | `techniczne` \| `organizacyjne` \| `medyczne` \| `magazynowe` \| `inne` |
| `status` | VARCHAR(20) | `active` \| `inactive` |
| `is_global` | TINYINT(1) | Widoczny dla wszystkich obozów |
| `is_pinned` | TINYINT(1) | Wyróżniony |
| `sort_order` | INT | Kolejność |
| `info_before` | TEXT | Tekst nad formularzem |
| `info_after` | TEXT | Tekst po wysłaniu |
| `created_by` | BIGINT UNSIGNED | WP user ID |

**Indeksy**: `idx_status`, `idx_global`, `idx_pinned`, `idx_order`

---

### bm_form_fields

Pola formularzy.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `form_id` | BIGINT UNSIGNED | FK → bm_forms |
| `label` | VARCHAR(255) | Etykieta pola |
| `field_key` | VARCHAR(100) | Klucz techniczny |
| `type` | VARCHAR(20) | `text` \| `textarea` \| `number` \| `email` \| `tel` \| `select` \| `radio` \| `checkbox` \| `date` \| `file` |
| `is_required` | TINYINT(1) | Wymagane |
| `placeholder` | VARCHAR(255) | Placeholder |
| `help_text` | VARCHAR(500) | Opis pomocniczy |
| `options_json` | LONGTEXT | JSON array opcji (select/radio/checkbox) |
| `default_value` | VARCHAR(255) | Wartość domyślna |
| `validation` | VARCHAR(100) | Reguła walidacji |
| `sort_order` | INT | Kolejność |

**Indeksy**: `idx_form (form_id)`, `idx_order (form_id, sort_order)`

---

### bm_form_camps

Pivot widoczności formularza dla konkretnych obozów (gdy `is_global = 0`).

| Kolumna | Typ | Opis |
|---------|-----|------|
| `form_id` | BIGINT UNSIGNED | FK → bm_forms (PK composite) |
| `camp_id` | BIGINT UNSIGNED | FK → bm_camps (PK composite) |

**Indeksy**: `idx_camp (camp_id)`

---

### bm_submissions

Zgłoszenia złożone przez obozy.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `form_id` | BIGINT UNSIGNED | FK → bm_forms |
| `camp_id` | BIGINT UNSIGNED | FK → bm_camps |
| `staff_id` | BIGINT UNSIGNED | FK → bm_staff (składający) |
| `category` | VARCHAR(50) | Dziedziczona z formularza |
| `status` | VARCHAR(20) | `new` \| `in_progress` \| `waiting` \| `closed` \| `cancelled` |
| `priority` | VARCHAR(20) | `low` \| `normal` \| `high` \| `urgent` |
| `admin_comment` | TEXT | Komentarz admina widoczny dla obozu |
| `assigned_to` | BIGINT UNSIGNED | WP user ID obsługującego |
| `form_snapshot` | LONGTEXT | JSON – snapshot definicji w chwili wysłania |
| `submission_data` | LONGTEXT | JSON – wypełnione wartości pól |

**Indeksy**: `idx_form`, `idx_camp`, `idx_staff`, `idx_status`, `idx_priority`, `idx_category`, `idx_assigned`, `idx_date (created_at)`

> **Ważne**: `form_snapshot` i `submission_data` są immutable po zapisie. Zmiany definicji formularza nie wpływają na istniejące zgłoszenia.

---

### bm_submission_attachments

Pliki załączone do zgłoszeń.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `submission_id` | BIGINT UNSIGNED | FK → bm_submissions |
| `original_name` | VARCHAR(255) | Oryginalna nazwa pliku |
| `stored_name` | VARCHAR(255) | Unikalna nazwa na dysku |
| `mime_type` | VARCHAR(100) | MIME (weryfikowany przez `finfo`) |
| `file_size` | BIGINT UNSIGNED | Rozmiar w bajtach (max 10 MB) |
| `file_path` | VARCHAR(1000) | Absolutna ścieżka (poza webroot lub z .htaccess deny) |

**Indeksy**: `idx_submission (submission_id)`

---

### bm_submission_history

Audit log zmian statusu zgłoszeń.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `submission_id` | BIGINT UNSIGNED | FK → bm_submissions |
| `changed_by` | BIGINT UNSIGNED | WP user ID zmieniającego |
| `from_status` | VARCHAR(20) | Poprzedni status |
| `to_status` | VARCHAR(20) | Nowy status |
| `note` | TEXT | Notatka do zmiany |
| `created_at` | DATETIME | Czas zmiany |

**Indeksy**: `idx_submission (submission_id)`, `idx_date (created_at)`

---

### bm_operation_logs

Centralny dziennik operacji administracyjnych i bezpieczeństwa.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `user_id` | BIGINT UNSIGNED | WP user ID wykonującego akcję |
| `staff_id` | BIGINT UNSIGNED | ID członka kadry, jeśli zdarzenie dotyczy frontendu |
| `action` | VARCHAR(100) | Typ akcji, np. `login_failed`, `unlock_staff`, `thread_created` |
| `object_type` | VARCHAR(50) | Typ obiektu, np. `staff`, `submission`, `plan_template` |
| `object_id` | BIGINT UNSIGNED | ID obiektu |
| `details` | LONGTEXT | Szczegóły tekstowe lub JSON |
| `ip_address` | VARCHAR(45) | Adres IP |
| `created_at` | DATETIME | Czas wpisu |

**Indeksy**: `idx_user`, `idx_staff`, `idx_action`, `idx_date`

---

### bm_plan_templates

Nagłówki globalnych szablonów planu dnia.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(255) | Nazwa szablonu |
| `description` | TEXT | Opis |
| `recurrence` | VARCHAR(20) | `once` \| `daily` \| `weekly` |
| `days_of_week` | VARCHAR(20) | CSV dni tygodnia dla trybu `weekly`, np. `1,3,5` |
| `created_by` | BIGINT UNSIGNED | WP user ID |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP |

---

### bm_plan_template_items

Pozycje przypisane do szablonu planu dnia.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `template_id` | BIGINT UNSIGNED | FK → bm_plan_templates |
| `time_from` | VARCHAR(10) | Godzina od |
| `time_to` | VARCHAR(10) | Godzina do |
| `title` | VARCHAR(255) | Tytuł pozycji |
| `description` | TEXT | Opis |
| `category` | VARCHAR(30) | Kategoria zgodna z planem dnia |
| `is_mandatory` | TINYINT(1) | Czy obowiązkowa |
| `sort_order` | INT | Kolejność |

**Indeksy**: `idx_template (template_id)`

---

### bm_meal_diets

Słownik predefiniowanych diet używany przez formularz jadłospisu.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(255) | Nazwa diety |
| `sort_order` | INT | Kolejność wyświetlania |
| `created_at` | DATETIME | |

---

### bm_meal_locations

Słownik predefiniowanych miejsc wydawania posiłków.

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(255) | Nazwa miejsca |
| `sort_order` | INT | Kolejność wyświetlania |
| `created_at` | DATETIME | |
