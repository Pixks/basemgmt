# 01 – Przegląd i instalacja

## Czym jest CampLink?

**CampLink** to produkcyjna wtyczka WordPress do kompleksowego zarządzania ośrodkiem obozowym. Obsługuje ewidencję obozów, meldunki dzienne, ogłoszenia, plan dnia, rezerwacje zasobów, jadłospis, komunikację, formularze zgłoszeń oraz dedykowany panel frontendowy dla kadry obozów — bez konieczności tworzenia kont WordPress dla każdego pracownika.

### Główne cechy

| Cecha | Opis |
|-------|------|
| Dualny dostęp | Administratorzy WP Admin + kadra przez panel frontendowy |
| Brak kont WP dla kadry | Własny system uwierzytelniania oparty na kodach bezpieczeństwa |
| Modularność | Każdy moduł można rozwijać niezależnie |
| Bezpieczeństwo | Haszowane kody, rate limiting, sesje HttpOnly, double-booking prevention |
| Notyfikacje email | Konfigurowalny system szablonów HTML |
| Integracja IMGW | Automatyczna synchronizacja ostrzeżeń meteorologicznych |
| WP-Cron | Automatyczne wygasanie, porządkowanie, synchronizacja |

---

## Wymagania systemowe

| Składnik | Minimum | Zalecane |
|----------|---------|---------|
| WordPress | 6.0 | 6.5+ |
| PHP | 8.1 | 8.2+ |
| MySQL / MariaDB | MySQL 5.7 / MariaDB 10.3 | MySQL 8.0+ |
| Silnik tabel | InnoDB | InnoDB |
| Rozszerzenia PHP | `pdo`, `mbstring`, `json` | + `opcache` |

> ⚠️ **Ważne**: Plugin wymaga silnika InnoDB dla mechanizmu `SELECT FOR UPDATE` chroniącego przed podwójnymi rezerwacjami. Tabele MyISAM nie są wspierane.

---

## Instalacja

### Metoda 1 – Ręczna (zalecana dla środowisk produkcyjnych)

```bash
# 1. Skopiuj katalog pluginu
cp -r basemgmt /ścieżka/do/wordpress/wp-content/plugins/

# 2. Sprawdź uprawnienia (Linux)
chown -R www-data:www-data wp-content/plugins/basemgmt
chmod -R 755 wp-content/plugins/basemgmt
```

3. Zaloguj się do WP Admin → **Wtyczki → Zainstalowane wtyczki**
4. Znajdź **CampLink** i kliknij **Aktywuj**

### Metoda 2 – WP-CLI

```bash
wp plugin activate basemgmt
```

### Metoda 3 – Upload przez WP Admin

WP Admin → Wtyczki → Dodaj nową → Wyślij wtyczkę → wybierz archiwum `.zip`

---

## Co dzieje się podczas aktywacji?

Aktywacja wywołuje `Activator::activate()`, który:

1. **Tworzy komplet tabel pluginu** w bazie danych przez `dbDelta()` (bezpieczne przy aktualizacjach)
2. **Rejestruje role i uprawnienia** WordPress (capability `manage_basemgmt`)
3. **Planuje zadania cykliczne** WP-Cron (10 hooków)
4. **Zapisuje wersję pluginu** w opcji `basemgmt_db_version`
5. **Kompiluje tłumaczenia `.mo` oraz odświeża reguły routingu** (`flush_rewrite_rules()`), aby endpointy REST były dostępne od razu po aktywacji

Aktualna, pełna lista tabel jest utrzymywana w dokumencie: [06 – Schemat bazy danych](06-database-schema.md).

---

## Co dzieje się podczas dezaktywacji?

`Deactivator::deactivate()` usuwa wszystkie zaplanowane zdarzenia cron. **Dane w bazie są zachowane.**

---

## Co dzieje się podczas odinstalowania?

`uninstall.php` **usuwa wszystkie tabele i opcje pluginu**. Tej operacji nie można cofnąć.

> 🔴 Przed odinstalowaniem wykonaj backup bazy danych!

---

## Aktualizacja pluginu

Plugin używa `dbDelta()` – wystarczy nadpisać pliki i ponownie aktywować (lub kliknąć "Dezaktywuj → Aktywuj"). Schemat bazy zostanie zaktualizowany automatycznie. Żadne dane nie zostaną utracone.

---

## Pierwsze kroki po instalacji

### 1. Utwórz pierwszy obóz

WP Admin → **CampLink → Obozy → Nowy obóz**

Wypełnij:
- Nazwa obozu
- Data rozpoczęcia i zakończenia
- Status: Aktywny

### 2. Dodaj członków kadry

WP Admin → **CampLink → Kadra → Dodaj osobę**

Dla każdej osoby:
- Imię i nazwisko
- Przypisanie do obozu
- Rola (komendant, zastępca, kwatermistrz…)
- Email (do powiadomień)
- **Kod bezpieczeństwa** – dokładnie 6 cyfr; zostanie automatycznie zahaszowany

### 3. Skonfiguruj panel frontendowy

Wstaw shortcody panelu `bm_panel_*` na wybranych stronach WordPress (np. `bm_panel_login`, `bm_panel_camp_header`, `bm_panel_announcements`) lub skonfiguruj elementy w Breakdance.

### 4. Skonfiguruj email

WP Admin → **CampLink → Ustawienia** → sekcja "Ustawienia powiadomień email"

### 5. Opcjonalnie: Skonfiguruj pogodę

WP Admin → **CampLink → Pogoda → Ustawienia**
- Wybierz dostawcę (Open-Meteo lub IMGW)
- Ustaw współrzędne lub województwo/powiat
- Włącz synchronizację IMGW

---

## Stałe pluginu

Zdefiniowane w `basemgmt.php`:

| Stała | Wartość domyślna | Opis |
|-------|-----------------|------|
| `BASEMGMT_VERSION` | `2.0.0-beta` | Wersja pluginu |
| `BASEMGMT_FILE` | `__FILE__` | Ścieżka do głównego pliku |
| `BASEMGMT_DIR` | `plugin_dir_path(...)` | Ścieżka katalogu |
| `BASEMGMT_URL` | `plugin_dir_url(...)` | URL katalogu |
| `BASEMGMT_SESSION_COOKIE` | `bm_session` | Nazwa ciasteczka sesji |
| `BASEMGMT_SESSION_TTL` | `28800` (8h) | Czas życia sesji w sekundach |
| `BASEMGMT_MAX_ATTEMPTS` | `3` | Maks. prób logowania przed blokadą czasową |
| `BASEMGMT_LOCKOUT_TTL` | `900` (15 min) | Bazowy czas blokady; realna wartość jest konfigurowalna w ustawieniach |

### Tryb deweloperski licencji

Na potrzeby developmentu możesz całkowicie wyłączyć system licencji przez dopisanie w `wp-config.php`:

```php
define('BASEMGMT_DEV_LICENSE_OVERRIDE', '<developer_key>');
```

Po ustawieniu tej stałej wtyczka przechodzi w tryb deweloperski, pomija walidację licencji i pokazuje odpowiedni status w zakładce licencji.

---

## Najważniejsze nowości w CampLink 2.0.0-beta

- **Teczka sprawy obozu** — każdy obóz zyskuje pełną dokumentację procesową: etap procesu handlowo-formalnego (od zapytania do zamknięcia), poziom ryzyka, termin następnego działania, notatki procesowe i flaga pilnej reakcji.
- **Dane organizatora** — nowa zakładka z pełnymi danymi kontaktowymi i fakturowymi organizatora obozu.
- **Checklista gotowości** — konfigurowalna lista kontrolna (strona odpowiedzialna, status, termin, komentarz) z wskaźnikiem gotowości i automatycznym wykrywaniem pozycji po terminie.
- **Dane przed przyjazdem** — sekcja operacyjna z godzinami przyjazdu/wyjazdu, deklarowanymi liczebnościami, dietami, alergenami, planem infrastruktury i kontaktami upoważnionymi.
- **Historia zmian etapów** — pełen audit trail zmian etapu procesu dla każdego obozu.
- **Rozbudowane filtrowanie obozów** — filtrowanie po statusie pobytu, etapie procesu, poziomie gotowości i fladze pilnej reakcji; wyszukiwarka pełnotekstowa.
- **Nowe tabele bazy danych**: `bm_camp_cases`, `bm_camp_case_history`, `bm_camp_organizers`, `bm_camp_checklist_items`, `bm_camp_prearrival`.

---

## Rozwiązywanie problemów

### Plugin nie aktywuje się

- Sprawdź wymagania PHP (≥ 8.1) i WordPress (≥ 6.0)
- Sprawdź uprawnienia do plików (`chmod 755`)
- Włącz `WP_DEBUG` i sprawdź logi

### Tabele nie zostały utworzone

- Upewnij się, że MySQL używa InnoDB
- Sprawdź, czy użytkownik bazy ma uprawnienia `CREATE TABLE`
- Ręcznie wywołaj: `Schema::create_tables()` lub dezaktywuj i aktywuj plugin

### Panel frontendowy nie wyświetla się

- Sprawdź, czy na stronach frontendowych używasz shortcodów `bm_panel_*` (legacy `camp_*` działa tylko dla kompatybilności wstecznej)
- Upewnij się, że REST API WordPress działa: `GET /wp-json/bm/v1/auth/status`
- Sprawdź konsolę przeglądarki pod kątem błędów JS

### Błąd "Nieprawidłowa odpowiedź API IMGW"

- API IMGW zwraca pustą odpowiedź gdy nie ma ostrzeżeń – to poprawne zachowanie
- Sprawdź łączność z `https://danepubliczne.imgw.pl/api/data/warningsmeteo`
