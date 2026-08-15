# Changelog

## [2.0.0-beta] – 2026-08-15

Pierwsze wydanie **beta** — konsolidacja wszystkich alpha. Wszystkie kluczowe moduły obozu są kompletne i gotowe do testów integracyjnych.

### Podsumowanie zmian względem alpha.3

- Moduł rozliczeń obozu (PDF, edytor, repozytorium)
- Zakładka Sprzęt i system deklaracji organizacyjnych
- Wysyłanie deklaracji do obozów
- Uproszczone etapy procesu (7 zamiast 12)
- Zasoby per-jednostka z automatycznym kosztem rezerwacji
- Podpisywanie dokumentów (kwalifikowany e-podpis + skan)
- Podział monolitycznego JS na moduły
- Wzmocnione bezpieczeństwo REST API i walidacja danych wejściowych
- Rozbudowany `uninstall.php`

---

## [2.0.0-alpha.7] – 2026-08-15

### Nowe funkcje

- **Moduł rozliczeń obozu** — nowa strona `CampSettlementPage`; formularz edycji rozliczenia z sekcjami: dane organizatora, podsumowanie pobytu, pozycje finansowe, uwagi końcowe; generowanie PDF (`templates/admin/pdf/settlement.php`) z pełnym układem: dane obozu, nabywca, podsumowanie pobytu, pozycje rozliczenia, uzgodnienie płatności.
- **`CampSettlementRepository`** — pełny CRUD rozliczeń i pozycji; nowe tabele `bm_camp_settlements`, `bm_camp_settlement_lines`, `bm_camp_settlement_issues`.

### Refaktoring

- **Podział JavaScript** — monolityczne `bm-api.js` i `frontend.js` zastąpione przez 4 moduły: `bm-store.js` (stan globalny), `bm-components-auth.js` (logowanie/sesja), `bm-components-content.js` (treść panelu), `bm-components-social.js` (komunikacja/ogłoszenia); usunięty stary `frontend.css`.
- **Czyszczenie danych demo** — usunięty plik `camplink-demo-backup.json` z repozytorium.

### Bezpieczeństwo

- Walidacja i sanityzacja danych wejściowych w `CampsPage`, `FormsPage`, `SettingsPage`.
- Dodatkowe sprawdzenie uprawnień w REST: `AuthController`, `FormsController`, `WeatherController`.
- Ochrona przed SSRF w `LicenseClient` i `OpenMeteoProvider`.
- Wzmocniona izolacja sesji w `FrontendAuth` i `ShortcodeHandler`.
- Rozbudowany `.gitignore` (pliki środowiskowe, lokalne konfiguracje).
- Pełne czyszczenie wtyczki przy deinstalacji w `uninstall.php`.

---

## [2.0.0-alpha.6] – 2026-08-14

### Nowe funkcje

- **Wysyłanie deklaracji do obozów** — nowa metoda `handle_push_to_camp` w `OrgDeclarationsPage`; przycisk „Wyślij do obozu" z modalem wyboru obozu na liście deklaracji; zaktualizowany widok teczki obozu z informacją o statusie deklaracji.
- **Schemat bazy danych** — nowe kolumny `sent_to_camp` i `camp_approved_at` w tabeli `bm_camp_decl_docs`.

### Refaktoring

- **Edytor deklaracji** — ujednolicone szablony `edit.php` / `list.php` deklaracji; obsługa załączników plików; ulepszony edytor HTML treści.
- **Edytor treści dokumentów** — nowy szablon `doc-content-edit.php` obsługujący zarówno dokumenty zwykłe, jak i deklaracje.
- **Modal załączania plików** — nowy modal w liście dokumentów do dołączania plików do istniejących pozycji.
- Lepsza nawigacja i responsywność list i formularzy obozów.

---

## [2.0.0-alpha.5] – 2026-08-13

### Nowe funkcje

- **Zakładka Sprzęt** — nowa zakładka „Sprzęt" w teczce obozu: wydawanie sprzętu (typ, nazwa, ilość), rejestrowanie zwrotów, usuwanie pozycji; nowa tabela `bm_camp_equipment`.
- **System deklaracji organizacyjnych** — nowa sekcja „Deklaracje" w menu Org (powyżej Finanse): CRUD szablonów deklaracji z flagą `auto_add`; szablony z `auto_add = 1` dodawane automatycznie przy tworzeniu obozu; deklaracje wyświetlane w zakładce Dokumenty teczki; nowe tabele `bm_decl_templates`, `bm_camp_decl_docs`.
- **Podpisywanie dokumentów** — kwalifikowany e-podpis (PDF z walidacją heurystyczną) + manualne skanowanie (PDF/JPG/PNG); semantyczne rozróżnienie: dokumenty = „Prześlij podpisany", deklaracje = „Zatwierdź".
- **Uproszczenie etapów procesu** — zmiana z 12 na 7 etapów: `inquiry`, `offer`, `contract_signed`, `on_site`, `settlement`, `closed`, `cancelled`; wsteczna kompatybilność przejść z poprzednich wersji.
- **Zasoby per-jednostka** — pola `pricing_mode` (`flat`/`per_unit`) i `total_units` w zasobach; rezerwacje z `reserved_units` i walidacją dostępności inwentarza; koszt per-jednostka (`reserved_units × cost_per_reservation`) automatycznie dodawany do harmonogramu płatności.

### Poprawki

- Poprawione nonce przy zwrocie sprzętu (formularz POST zamiast linku GET).
- Edycja szkód: modal + handler `handle_edit_camp_damage()`.
- Usunięto automatyczne tworzenie zadań z automatyzacji workflow (tylko ręczne przez szablony).
- Usunięto bloki „Co blokuje przejście dalej" i „Sugerowane działania" z zakładki Panel.
- Naprawiony błąd `str_replace` z argumentem float w `ResourceRepository`.

### Schemat bazy danych

- Nowe tabele: `bm_camp_equipment`, `bm_decl_templates`, `bm_camp_decl_docs`.
- ALTER: `pricing_mode`, `total_units` w `bm_resources`; `reserved_units` w `bm_resource_reservations`; `approved_by`, `approved_at` w `bm_camp_decl_docs`; `signed_method`, `signed_at`, `signed_by`, `signed_file_url` w `bm_camp_documents`.

---

## [2.0.0-alpha.4] – 2026-08-13

### Nowe funkcje

- **Camp Folder REST API** — nowy `FolderController` z endpointami do zarządzania dokumentami obozu, szkodami i deklaracjami dziennymi z poziomu frontendu.
- **Rozbudowa `bm-api.js`** — wrappery JS dla nowych endpointów teczki.
- **Dokumentacja Breakdance** — dodany `docs/15-panel-full-breakdance.md` z pełną strukturą panelu frontendowego opartego na Breakdance.

---

## [2.0.0-alpha.3] – 2026-08-13

### Poprawki i ulepszenia

- **Uproszczenie zakładki Panel** — usunięto oś faz workflow, pola uzasadnienia zmiany etapu, uwag gotowości, poziomu ryzyka i flagi pilnej reakcji; pozostały: 6 kart metryk, blokery + sugerowane działania, formularze „Podstawowe dane" i „Etap i odpowiedzialny".
- **Napisy po angielsku → polskie** — `'Workflow'` w liście obozów zmienione na `'Teczka'`; `'Lead / zapytanie'` w `CampCaseRepository` zmienione na `'Zapytanie'`.
- **Treści maili w szablonach zadań** — pole tematu (`email_subject`) i treści (`email_body`) dodane bezpośrednio do edytora szablonu zadania; osobny blok powiadomień w ustawieniach usunięty.
- **Auto-dodawanie szablonów zadań do obozu** — przy tworzeniu nowego obozu system automatycznie dodaje zadania z szablonów z flagą `auto_add = 1` do checklisty obozu.
- **Szkody w rozliczeniu** — blok „Szkody" w zakładce Finanse umożliwia dodawanie pozycji szkód z nazwą, opisem i kosztem; dedykowana tabela `bm_camp_damages`.
- **Poprawki CSS/układu** — naprawiony wygląd postboxów (nagłówki, ikony, WP toggle buttons ukryte); wejściówki w `.bm-form-grid` używają `width: 100%; box-sizing: border-box` eliminując przepełnienie kolumn; dodane klasy `.bm-box` / `.bm-box__header` / `.bm-box__body` jako alternatywa bez ingerencji WP.
- **Schemat bazy danych** — kolumny `email_subject` / `email_body` dodane do `bm_task_templates`; automatyczna migracja ALTER TABLE dla istniejących instalacji.

---

## [2.0.0-alpha.2] – 2026-08-12

### Nowe funkcje

- **Przeprojektowany interfejs teczki obozu** — zakładki: Panel, Centrum Pracy, Organizator, Dokumenty, Planowanie, Rozliczenie; nazwa obozu i status widoczne nad zakładkami.
- **Kanban zadań w Centrum Pracy** — zadania prezentowane jako kafelki w kolumnach statusów (Do zrobienia / W toku / Gotowe); możliwość wejścia do zadania, ręczne dodawanie zadań i przypisywanie do osób; usunięte automatyczne generowanie zadań na podstawie statusu.
- **Moduł Organizacja** — nowe podmenu w CampLink z zakładkami:
  - *Szablony dokumentów* — tworzenie i edytowanie szablonów HTML, generowanie PDF, flaga auto-dodawania do obozu.
  - *Dokumenty* — przechowywanie gotowych dokumentów z możliwością auto-dodawania.
  - *Szablony zadań* — CRUD szablonów zadań (tytuł, opis, priorytet, flaga auto-dodawania).
  - *Finanse* — definiowanie pakietów płatności (koszt osobodnia z rozbiciem na nocleg/wyżywienie/inne, podatki, zaliczki, własne pozycje z terminami płatności).
  - *Typy noclegów* — CRUD typów noclegów z stawkami za dobę.
- **Zakładka Organizator w teczce** — pola organizatora obejmują teraz dane rozliczeniowe (REGON, KRS, ulica, miasto, kod pocztowy, bank, numer konta) w dedykowanym bloku.
- **Zakładka Dokumenty w teczce** — dodawanie dokumentów z pliku lub tworzenie z szablonu HTML → PDF; wysyłanie do klienta do podpisu (blokuje edycję admina, dodaje ikonę kłódki).
- **Zakładka Finanse w teczce** — wybór pakietu płatności lub customowa edycja wartości; blok szkód.
- **Deklaracja per dzień** — deklaracja obozu podzielona na dni pobytu z rozbiciem diet i noclegów na typy (spójne ze stawkami); stała deklaracja liczby osób/diet/godzin przyjazdu–wyjazdu pozostaje jako wbudowana i może być wyłączona, ale nie usunięta.
- **Backup, import i czyszczenie danych** — nowa sekcja w Ustawieniach umożliwia pobranie backupu JSON, import z pliku, wyczyszczenie wszystkich danych wtyczki.
- **Internacjonalizacja** — wtyczka ładuje pliki `.po`/`.mo`; dodano tłumaczenia angielskie (`en_US`) i polskie (`pl_PL`); w ustawieniach przycisk kompilacji plików językowych.
- **Powiadomienia email** — konfigurowalne per-typ powiadomienia email przy dodawaniu zadań i dokumentów do obozu; szablony treści maili edytowalne w szablonach zadań.

### Schemat bazy danych

Nowe tabele:
- `bm_document_templates` — szablony dokumentów HTML
- `bm_documents` — gotowe dokumenty
- `bm_task_templates` — szablony zadań (z `auto_add`, `email_subject`, `email_body`)
- `bm_payment_packages` / `bm_payment_package_items` — pakiety płatności i ich pozycje
- `bm_accommodation_types` — typy noclegów ze stawkami
- `bm_camp_damages` — pozycje szkód powiązane z obozem
- `bm_camp_declaration_days` / `bm_camp_declaration_diet_lines` / `bm_camp_declaration_accommodation_lines` — deklaracje per dzień z rozbiciem diet i noclegów

---

## [2.0.0-alpha.1] – 2026-08-10

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

## [2.0.0-alpha.0] – 2026-08-09

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
