# Changelog

## [2.0.0-PRE2] – 2026-08-10

### Nowe funkcje

- **Workflow obozowy — pełne etapy biznesowe** — `CampCaseRepository` rozszerzony o metodę `workflow_phases()` grupującą 12 etapów w 6 faz: Lead, Oferta i ustalenia, Umowa i płatności, Przygotowanie operacyjne, Przyjazd i pobyt, Rozliczenie i zamknięcie.
- **Twarde reguły przejść między etapami** — `allowed_stage_transitions()` + `can_transition()` blokują niedozwolone przeskoki zarówno po stronie frontendu, jak i zapisu; przy próbie niedozwolonej zmiany admin widzi komunikat błędu z listą dozwolonych celów.
- **Workspace per etap** — `stage_workspace()` definiuje które sekcje i pola są widoczne/wymagane dla bieżącej fazy; widok `workflow_view=stage` renderuje tylko relevantne pola.
- **Automatyczne taski checklisty** — `sync_checklist_for_stage()` po każdej zmianie etapu dociąga brakujące zadania z `default_checklist_template()` przypisanego do fazy; `ensure_checklist_task()` używany przez automatyzacje do dorzucania pojedynczych zadań bez duplikowania.
- **Wskaźnik gotowości liczony z zadań** — `get_readiness_summary()` oblicza `percent`, `done`, `total`, `overdue` na podstawie wierszy `bm_camp_checklist_items`, a nie ręcznych notatek; procent gotowości widoczny w liście obozów i w sekcji workcenter.
- **Ekran Overview + Next Actions** — sekcja `bm-section-overview` prezentuje: pasek faz (done/current/upcoming), health-status, termin następnego działania, listę blokerów, listę sugestii (`next_actions`) z `build_workflow_snapshot()`.
- **Centrum pracy (workcenter)** — dedykowana sekcja z otwartymi taskami, ostatnią aktywnością (`get_recent_activity`) i otwartymi zdarzeniami automatyzacji (`get_open_events`).
- **Moduł automatyzacji workflow** — nowa klasa `CampWorkflowAutomationRepository` z obsługą 5 typów zdarzeń: `stage_change`, `overdue_payment`, `missing_prearrival`, `upcoming_start`, `missing_settlement`. Każde zdarzenie tworzy taska, oznacza obóz `needs_attention` i generuje `draft_message`.
- **Cron workflow** — nowe zadanie `bm_camp_workflow_check` uruchamia `evaluate_all_active_camps()` codziennie o 07:30 UTC i automatycznie tworzy eventy i taski dla wszystkich aktywnych obozów.
- **Dane modułów źródłowych w teczce** — `get_module_summary()` zbiera status z: płatności (`bm_camp_payment_schedules`), dokumentów (`bm_camp_documents`), formularzy (`bm_form_camps` + `bm_submissions`) i danych prearrival; teczka pełni rolę warstwy koordynacji.
- **Filtrowanie listy obozów** — nowe filtry: etap procesu, poziom gotowości, flaga `needs_attention`; zdrowie obozu widoczne jako kolorowy badge z tekstem OK / Wymaga uwagi / Zaległości.
- **Historia etapów** — `bm_camp_case_history` rejestruje każdą zmianę etapu z uzasadnieniem, starym i nowym etapem, autorem i timestampem; widoczna w sekcji historii na karcie obozu.

### Rozbudowa istniejących klas

- `CampCaseRepository` — dodane metody: `workflow_phases`, `allowed_stage_transitions`, `can_transition`, `sync_checklist_for_stage`, `ensure_checklist_task`, `get_open_checklist_items`, `get_recent_activity`, `get_module_summary`, `stage_workspace`, `build_workflow_snapshot`, `get_phase_for_stage`, `default_checklist_rows`, `pad_checklist_rows`, `set_attention_state`, `is_organizer_ready`, `is_prearrival_ready`, `get_future_module_counts`.
- `CampRepository` — dodana metoda `active_summary()` zwracająca zestawienie aktywnych obozów z danymi gotowości i etapu.
- `Scheduler` — rejestracja hooka `bm_camp_workflow_check` i metody `check_camp_workflows()`.

### Schemat bazy danych

Nowe tabele:
- `bm_camp_workflow_events` — zdarzenia automatyzacji (typ, tytuł, opis, sugestia, draft wiadomości, termin, metadata)
- `bm_camp_payment_schedules` — harmonogramy płatności (kwota, termin, status, data opłacenia)
- `bm_camp_payments` — powiązane wpłaty
- `bm_camp_documents` / `bm_camp_document_versions` — dokumenty i ich wersjonowanie
- `bm_camp_prearrival` — dane operacyjne przed przyjazdem (godziny, diety, alergeny, plan infrastruktury)
- `bm_camp_actual_stays` / `bm_camp_actual_meals` / `bm_camp_service_usages` — rzeczywiste dane pobytowe
- `bm_camp_pricing_tables` / `bm_camp_pricing_rules` — tabele i reguły cenowe
- `bm_camp_settlements` / `bm_camp_settlement_lines` / `bm_camp_settlement_issues` — rozliczenia
- `bm_camp_closures` — zamknięcia obozów

### Zmiany UI

- Karta obozu (`edit.php`) podzielona na sekcje nawigowane zakładkami: Overview, Workcenter, etapy operacyjne, dokumenty, płatności, rozliczenie.
- Lista obozów (`list.php`) z kolumnami: health-status, etap, gotowość (% + pasek), następny termin, przypisana kadra.
- Nowe komponenty CSS: `.bm-workflow-phase`, `.bm-workflow-phase--done/current/upcoming`, `.bm-stat-card`, `.bm-progress`, `.bm-workcenter`, `.bm-event-list`.

---

## [2.0.0-PRE1] – 2026-08-09

### Nowe funkcje

- **Teczka sprawy obozu** — każdy obóz zyskuje pełną dokumentację procesową. Nowa tabela `bm_camp_cases` przechowuje etap procesu (`inquiry` → `closed`), poziom ryzyka (`low` / `medium` / `high` / `critical`), termin następnego działania, notatki procesowe i flagę pilnej reakcji.
- **Historia zmian etapów** — tabela `bm_camp_case_history` rejestruje każdą zmianę etapu procesu ze starą i nową wartością, autorem oraz komentarzem.
- **Dane organizatora** — tabela `bm_camp_organizers` z pełnymi danymi kontaktowymi, fakturowymi i rozliczeniowymi organizatora obozu.
- **Checklista gotowości** — tabela `bm_camp_checklist_items` z konfigurowalnymi pozycjami (strona odpowiedzialna, status, termin, komentarz). Panel admina wyświetla wskaźnik gotowości (%) i oznacza pozycje po terminie.
- **Dane przed przyjazdem** — tabela `bm_camp_prearrival` przechowuje godziny przyjazdu/wyjazdu, deklarowane liczebności, diety, alergeny, plan infrastruktury i kontakty upoważnione.
- **Rozbudowane filtrowanie obozów** — nowe filtry: etap procesu, poziom gotowości, flaga pilnej reakcji, wyszukiwarka pełnotekstowa; paginacja uwzględnia wszystkie kryteria.
- **Nowe klasy PHP**: `CampCaseRepository` z metodami `get_case`, `save_case`, `get_organizer`, `save_organizer`, `get_prearrival`, `save_prearrival`, `get_checklist`, `replace_checklist`, `get_history`, `get_readiness_summary`, `process_stages`, `risk_levels`.

### Zmiany CSS

- Nowe klasy badge: `.bm-badge--{stage}`, `.bm-badge--{risk}` — kolorowe oznaczenia etapów i ryzyk.
- Nowe siatki układu: `.bm-filter-grid`, `.bm-form-grid`, `.bm-case-grid`, `.bm-case-card`, `.bm-form-section`.

---

## [1.3.0] – 2026-08-07

### Zmiany

- **Zmiana nazwy w menu WordPress** — pozycja w lewym menu administracyjnym zmieniona z „Baza Obozowa" na „CampLink".
- **Zaktualizowana dokumentacja** — README oraz dokumenty w katalogu `docs/` odzwierciedlają nową wersję i aktualną nazwę produktu.

## [1.1.2] – 2026-08-07

### Usprawnienia i poprawki

- **Raport zbiorczy meldunków** — widok dnia lepiej eksponuje sumy oraz listę obozów bez meldunku, co przyspiesza weryfikację braków.
- **Powiadomienia email o meldunkach** — dopracowane szablony przypomnień i raportów okresowych; przypomnienie korzysta z bieżącej daty WordPress i bezpiecznie obsługuje pustą listę HTML.
- **Drukuj / PDF** — poprawione formatowanie daty w widokach wydruku oraz mocniejsze wyróżnienie stanów alarmowych.
- **Masowe tworzenie planów dnia z dnia źródłowego** — nowe wpisy zachowują tytuł i zasięg planu źródłowego, o ile nie podano własnego wzorca tytułu.

## [1.1.1] – 2026-08-07

### Usprawnienia i poprawki

- **Wzmocniona blokada kont kadry** — limit błędnych prób logowania zmieniony na 3 (`BASEMGMT_MAX_ATTEMPTS = 3`), z zachowaniem blokady trwałej po kolejnym błędzie po odblokowaniu czasowym.
- **Szablony jadłospisów** — nowy panel CRUD (`Jadłospis → Szablony`) z możliwością tworzenia szablonów i pozycji oraz szybkim zastosowaniem do wybranego dnia jadłospisu.
- **Masowe tworzenie planów dnia** — nowy formularz w liście planów do generowania pustych planów na zakres dat (do 90 dni), z pomijaniem dat, które już mają plan.
- **Porządek w menu administratora** — przegrupowane pozycje menu CampLink, dodane skróty podrzędne dla planów i jadłospisu; logi operacji ukryte z głównego menu i dostępne z poziomu ustawień.
- **Rozszerzenie modelu danych jadłospisu** — dodane tabele `bm_meal_templates` i `bm_meal_template_items` do trwałego przechowywania szablonów i ich pozycji.

## [1.1.0] – 2026-08-07

Renamed plugin to **CampLink**.

### New features

#### Auth & security
- **Staff account lockout** — 5 failed attempts → configurable temp lock (default 15 min); 1 failure after temp-unlock → permanent lock. Admin panel unlocks with forced security code reset. (`RateLimiter` rewritten; `permanent_lock` column added to `bm_staff`)

#### Scheduling
- **Global day plan templates** — `bm_plan_templates` / `bm_plan_template_items` tables; full CRUD panel; "Apply template" widget inline on the plan edit page

#### Meals
- **Predefined diets & serving locations** — `bm_meal_diets` / `bm_meal_locations` tables; admin CRUD panel; selects with "Other – type manually" fallback (JS swaps field name on submit) in the meal item editor
- **Auto-add meal to daily plan** — checkbox on meal item save triggers plan entry creation for the meal's date

#### Notifications & reporting
- **Configurable e-mail notifications** — settings section for per-type recipient addresses; `bm_missing_report_emails` replaces hardcoded `admin_email` in `Scheduler::send_daily_reminders()`
- **Periodic staff count e-mail reports** — `bm_periodic_staff_report` WP-Cron event; configurable interval (`hourly` / `twicedaily` / `daily`) and recipients; `reschedule_staff_report()` called on settings save

#### Audit & transparency
- **Operation logs** — `bm_operation_logs` table; `OperationLogger` class with `ACTION_*` constants; logs logins, failures, unlocks, thread creation; filterable admin panel with optional log pruning

#### UX / admin
- **Create conversation thread from submission** — button on submission detail view; inserts `bm_conv_threads` + system `bm_conv_messages` row
- **Dashboard buttons moved to top** — action buttons rendered above data grids
- **Form builder help text** — `<p>` on every field; dynamic per-type hint shown via JS when type select changes
- **PDF / print export** — print-friendly full-HTML pages (camp headcounts, daily schedule, meal menu) opened in new tab with `window.print()` — no external library

### Bug fixes
- `DailyCountRepository::get_for_date()` → `get_by_date()` (non-existent method call in `PdfPage` and `Scheduler`)
- `OperationLogger::log()` argument order corrected in `FrontendAuth` (was passing `int $staff->id` as `string $action`)
- Duplicate "Last login" `<td>` removed from `staff/list.php` (header/body column mismatch)
- `PlanTemplatesPage::handle_apply()` error redirect now preserves `plan_id` context
