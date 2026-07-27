# 01 – Przegląd i instalacja

## Czym jest Baza Obozowa?

**Baza Obozowa** to produkcyjna wtyczka WordPress do kompleksowego zarządzania ośrodkiem obozowym. Obsługuje ewidencję obozów, meldunki dzienne, ogłoszenia, plan dnia, rezerwacje zasobów, informacje pogodowe oraz dedykowany panel frontendowy dla kadry obozów — bez konieczności tworzenia kont WordPress dla każdego pracownika.

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
4. Znajdź **Baza Obozowa** i kliknij **Aktywuj**

### Metoda 2 – WP-CLI

```bash
wp plugin activate basemgmt
```

### Metoda 3 – Upload przez WP Admin

WP Admin → Wtyczki → Dodaj nową → Wyślij wtyczkę → wybierz archiwum `.zip`

---

## Co dzieje się podczas aktywacji?

Aktywacja wywołuje `Activator::activate()`, który:

1. **Tworzy 15 tabel** w bazie danych przez `dbDelta()` (bezpieczne przy aktualizacjach)
2. **Rejestruje role i uprawnienia** WordPress (capability `manage_basemgmt`)
3. **Planuje zadania cykliczne** WP-Cron (8 hooków)
4. **Zapisuje wersję pluginu** w opcji `basemgmt_db_version`

Tabele tworzone podczas aktywacji:

```
wp_bm_camps                  – obozy
wp_bm_staff                  – kadra obozów
wp_bm_daily_counts           – meldunki dzienne
wp_bm_announcements          – ogłoszenia
wp_bm_announcement_camps     – powiązania ogłoszenie→obóz
wp_bm_sessions               – sesje frontendowe
wp_bm_weather_alerts         – ostrzeżenia pogodowe
wp_bm_plan_headers           – nagłówki planów dnia
wp_bm_plan_items             – pozycje planu dnia
wp_bm_plan_item_revisions    – historia zmian planu
wp_bm_plan_camps             – powiązania plan→obóz
wp_bm_resources              – zasoby do rezerwacji
wp_bm_resource_reservations  – rezerwacje
wp_bm_resource_blocks        – blokady techniczne zasobów
```

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

WP Admin → **Baza Obozowa → Obozy → Nowy obóz**

Wypełnij:
- Nazwa obozu
- Data rozpoczęcia i zakończenia
- Status: Aktywny

### 2. Dodaj członków kadry

WP Admin → **Baza Obozowa → Kadra → Dodaj osobę**

Dla każdej osoby:
- Imię i nazwisko
- Przypisanie do obozu
- Rola (komendant, zastępca, kwatermistrz…)
- Email (do powiadomień)
- **Kod bezpieczeństwa** – dowolny ciąg znaków; zostanie automatycznie zahaszowany

### 3. Skonfiguruj panel frontendowy

Wstaw shortcode `[camp_panel]` na wybraną stronę WordPress lub skonfiguruj go w Breakdance jako komponent niestandardowy z kodem PHP.

### 4. Skonfiguruj email

WP Admin → **Baza Obozowa → Ustawienia** → sekcja "Ustawienia powiadomień email"

### 5. Opcjonalnie: Skonfiguruj pogodę

WP Admin → **Baza Obozowa → Pogoda → Ustawienia**
- Wybierz dostawcę (Open-Meteo lub IMGW)
- Ustaw współrzędne lub województwo/powiat
- Włącz synchronizację IMGW

---

## Stałe pluginu

Zdefiniowane w `basemgmt.php`:

| Stała | Wartość domyślna | Opis |
|-------|-----------------|------|
| `BASEMGMT_VERSION` | `1.3.0` | Wersja pluginu |
| `BASEMGMT_FILE` | `__FILE__` | Ścieżka do głównego pliku |
| `BASEMGMT_DIR` | `plugin_dir_path(...)` | Ścieżka katalogu |
| `BASEMGMT_URL` | `plugin_dir_url(...)` | URL katalogu |
| `BASEMGMT_SESSION_COOKIE` | `bm_session` | Nazwa ciasteczka sesji |
| `BASEMGMT_SESSION_TTL` | `28800` (8h) | Czas życia sesji w sekundach |
| `BASEMGMT_MAX_ATTEMPTS` | `5` | Maks. prób logowania przed blokadą |
| `BASEMGMT_LOCKOUT_TTL` | `900` (15 min) | Czas blokady po przekroczeniu limitu |

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

- Sprawdź, czy shortcode `[camp_panel]` jest na stronie
- Upewnij się, że REST API WordPress działa: `GET /wp-json/bm/v1/auth/status`
- Sprawdź konsolę przeglądarki pod kątem błędów JS

### Błąd "Nieprawidłowa odpowiedź API IMGW"

- API IMGW zwraca pustą odpowiedź gdy nie ma ostrzeżeń – to poprawne zachowanie
- Sprawdź łączność z `https://danepubliczne.imgw.pl/api/data/warningsmeteo`
