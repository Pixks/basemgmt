# 11 – Zadania cykliczne (WP-Cron)

## Przegląd

Zadania cykliczne zarządzane są przez `Scheduler` (`src/Cron/Scheduler.php`).

Rejestrowane podczas aktywacji pluginu przez `Activator::activate()` → `Scheduler::register_schedules()`.

Czyszczone podczas dezaktywacji przez `Deactivator::deactivate()` → `Scheduler::clear_schedules()`.

---

## Rejestracja hooków w Bootstrap

```php
// Bootstrap::register_cron()
$sched = new Scheduler();
$this->loader->add_action('bm_daily_reminders',       $sched, 'send_daily_reminders');
$this->loader->add_action('bm_expire_announcements',  $sched, 'expire_announcements');
$this->loader->add_action('bm_cleanup_sessions',      $sched, 'cleanup_sessions');
$this->loader->add_action('bm_refresh_weather',       $sched, 'refresh_weather');
$this->loader->add_action('bm_expire_weather_alerts', $sched, 'expire_weather_alerts');
$this->loader->add_action('bm_check_missing_reports', $sched, 'check_missing_reports');
$this->loader->add_action('bm_sync_imgw_alerts',      $sched, 'sync_imgw_alerts');
$this->loader->add_action('bm_expire_reservations',   $sched, 'expire_reservations');
$this->loader->add_action('bm_periodic_staff_report', $sched, 'send_periodic_staff_report');
```

---

## Harmonogram zadań

| Hook | Interwał | Godzina | Opis |
|------|----------|---------|------|
| `bm_daily_reminders` | daily | 08:00 | Sprawdza brakujące meldunki, wysyła email admina |
| `bm_check_missing_reports` | daily | 08:30 | Uruchamia hook `bm_missing_reports_checked` |
| `bm_expire_announcements` | hourly | – | Wygasa ogłoszenia po `valid_until` |
| `bm_cleanup_sessions` | daily | – | Usuwa wygasłe rekordy sesji frontendowych |
| `bm_refresh_weather` | hourly | – | Odświeża dane pogodowe z dostawcy |
| `bm_expire_weather_alerts` | hourly | – | Dezaktywuje ostrzeżenia po `valid_until` |
| `bm_sync_imgw_alerts` | konfigurowalne | – | Synchronizacja ostrzeżeń IMGW |
| `bm_expire_reservations` | daily | 00:05 | `pending` → `expired` dla przeszłych dat |
| `bm_periodic_staff_report` | konfigurowalne | – | Okresowy email ze stanami osobowymi aktywnych obozów |

---

## Szczegóły zadań

### bm_daily_reminders

```php
Scheduler::send_daily_reminders()
```

1. Pobiera wszystkie aktywne obozy
2. Sprawdza `DailyCountRepository::is_submitted_today()` dla każdego
3. Jeśli są brakujące meldunki → wysyła szablon HTML z bieżącą datą WordPress, liczbą braków i listą obozów na adresy z `bm_missing_report_emails` (fallback: `admin_email`)
4. Uruchamia hook `do_action('bm_daily_reminders_sent', $missing_camps)`

---

### bm_expire_announcements

```php
Scheduler::expire_announcements()
```

Wywołuje `AnnouncementRepository::expire_overdue()`:
```sql
UPDATE bm_announcements
SET status = 'expired'
WHERE status = 'active' AND valid_until < NOW()
```

Uruchamia `do_action('bm_announcements_expired', $count)`.

---

### bm_cleanup_sessions

```php
Scheduler::cleanup_sessions()
```

Wywołuje `SessionManager::cleanup_expired()`:
```sql
DELETE FROM bm_sessions WHERE expires_at < NOW()
```

---

### bm_refresh_weather

```php
Scheduler::refresh_weather()
```

Sprawdza `WeatherService::is_configured()`, następnie:
```php
$service = new WeatherService();
$service->refresh();
```

Dane pogodowe są cache'owane w WP Options/Transients.

---

### bm_expire_weather_alerts

```php
Scheduler::expire_weather_alerts()
```

Wywołuje `WeatherAlertRepository::deactivate_expired()`:
```sql
UPDATE bm_weather_alerts
SET is_active = 0
WHERE is_active = 1 AND valid_until IS NOT NULL AND valid_until < NOW()
```

---

### bm_sync_imgw_alerts

```php
Scheduler::sync_imgw_alerts()
```

Wywołuje `ImgwAlertsSync::sync()`:
1. Pobiera listę ostrzeżeń z API IMGW
2. Filtruje po województwie/powiecie (TERYT)
3. Upsert do `bm_weather_alerts` (source = 'imgw')
4. Dezaktywuje stare ostrzeżenia IMGW, których nie ma w aktualnej odpowiedzi

**Interwał**: konfigurowalny w ustawieniach pogody (`hourly` / `twicedaily` / `daily`)

**Przeplanowywanie**: `Scheduler::reschedule_imgw_sync()` wywoływane przy zmianie ustawień. Bezpieczne do wielokrotnego wywoływania – porównuje aktualny interwał.

---

### bm_expire_reservations

```php
Scheduler::expire_reservations()
```

Wywołuje `ReservationRepository::expire_past()`:
```sql
UPDATE bm_resource_reservations
SET status = 'expired', updated_at = NOW()
WHERE status = 'pending' AND res_date < CURDATE()
```

Wygasa oczekujące rezerwacje, których data minęła. Uruchamia `do_action('bm_reservations_expired', $count)`.

---

### bm_check_missing_reports

```php
Scheduler::check_missing_reports()
```

Pobiera listę obozów bez meldunku na bieżący dzień i uruchamia:
```php
do_action('bm_missing_reports_checked', $missing, $today);
```

Pozwala zewnętrznym rozszerzeniom reagować na brakujące meldunki.

---

### bm_periodic_staff_report

```php
Scheduler::send_periodic_staff_report()
```

1. Pobiera odbiorców z opcji `bm_report_emails`
2. Jeśli lista jest pusta – kończy działanie bez wysyłki
3. Pobiera wszystkie aktywne obozy
4. Dla każdego obozu pobiera dzisiejszy meldunek przez `DailyCountRepository::get_by_date()`
5. Buduje zbiorczy raport HTML z sumami i informacją o brakujących meldunkach
6. Wysyła email do wszystkich skonfigurowanych odbiorców
7. Uruchamia `do_action('bm_periodic_staff_report_sent', $totals, $camps)`

### Dynamiczne przeplanowanie raportu okresowego

Przy zapisie ustawień:

```php
Scheduler::reschedule_staff_report();
```

Logika:

- opcja `bm_report_emails` pusta → cron jest usuwany,
- opcja `bm_report_interval` określa interwał (`hourly`, `twicedaily`, `daily`),
- przy zmianie interwału zdarzenie jest przepinane automatycznie.

---

## Konfiguracja IMGW (dynamiczne przeplanowanie)

Gdy zmienisz interwał synchronizacji IMGW w ustawieniach pogody:

```php
// WeatherPage::handle_save_settings()
Scheduler::reschedule_imgw_sync();
```

```php
// Logika reschedule
public static function reschedule_imgw_sync(): void {
    $settings = ImgwAlertsSync::get_settings();
    $hook      = 'bm_sync_imgw_alerts';

    $ts = wp_next_scheduled($hook);

    if ( ! $settings['enabled'] ) {
        if ($ts) wp_unschedule_event($ts, $hook);
        return;
    }

    $interval = $settings['sync_interval'] ?: 'hourly';

    if ( $ts && wp_get_schedule($hook) === $interval ) {
        return; // już zaplanowane z tym interwałem
    }

    if ($ts) wp_unschedule_event($ts, $hook);
    wp_schedule_event(time(), $interval, $hook);
}
```

---

## Dodawanie nowego zadania cron

1. Dodaj hook do `ALL_HOOKS` w `Scheduler`:

```php
private const ALL_HOOKS = [
    // ... istniejące ...
    'bm_moje_zadanie',
];
```

2. Zarejestruj w `register_schedules()`:

```php
if ( ! wp_next_scheduled('bm_moje_zadanie') ) {
    wp_schedule_event(time(), 'daily', 'bm_moje_zadanie');
}
```

3. Dodaj callback:

```php
public function moje_zadanie(): void {
    // logika
    do_action('bm_moje_zadanie_done', $wynik);
}
```

4. Zarejestruj w `Bootstrap::register_cron()`:

```php
$this->loader->add_action('bm_moje_zadanie', $sched, 'moje_zadanie');
```

5. Dodaj do `uninstall.php`:

```php
wp_clear_scheduled_hook('bm_moje_zadanie');
```

---

## Debugowanie cron

### Sprawdź zaplanowane zdarzenia

```php
// wp-config.php lub przez WP-CLI
wp cron event list

// Lub z kodu
$events = _get_cron_array();
```

### Wymuś uruchomienie zadania

```bash
# WP-CLI
wp cron event run bm_expire_reservations
wp cron event run bm_sync_imgw_alerts
```

### Sprawdź, czy cron działa

Zainstaluj plugin "WP Crontrol" (bezpłatny) do podglądu i ręcznego uruchamiania zadań.

---

## Uwaga o WP-Cron

WordPress Cron uruchamia się przy każdym żądaniu HTTP do strony. Na stronach o małym ruchu zdarzenia mogą nie być wykonywane punktualnie.

**Rozwiązanie dla środowisk produkcyjnych**: Wyłącz WP-Cron i ustaw systemowy cron:

```bash
# /etc/cron.d/wordpress
*/5 * * * * www-data curl -s https://twoja-strona.pl/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

```php
// wp-config.php
define('DISABLE_WP_CRON', true);
```
