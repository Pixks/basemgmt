# 02 – Architektura pluginu

## Struktura katalogów

```
basemgmt/
├── basemgmt.php              ← Plik główny; stałe, autoloader, lifecycle hooks
├── uninstall.php             ← Czyszczenie tabel i opcji podczas odinstalowania
├── assets/
│   ├── css/
│   │   └── admin.css         ← Style panelu admina
│   └── js/
│       ├── admin.js          ← Admin JS: Sortable.js + FullCalendar init
│       └── bm-api.js         ← Alpine.js komponenty frontendu
├── docs/                     ← Dokumentacja (ten katalog)
├── languages/                ← Pliki tłumaczeń (.pot, .po, .mo)
├── src/                      ← Kod PHP (PSR-4, namespace BaseMgmt\)
│   ├── Admin/
│   │   ├── AdminMenu.php     ← Menu WP Admin, enqueue, flash notices
│   │   ├── ListTables/       ← (WP_List_Table extensions, reserved)
│   │   └── Pages/
│   │       ├── AnnouncementsPage.php
│   │       ├── CampsPage.php
│   │       ├── DashboardPage.php
│   │       ├── ReportsPage.php
│   │       ├── ReservationsPage.php
│   │       ├── SchedulePage.php
│   │       ├── SettingsPage.php
│   │       ├── StaffPage.php
│   │       ├── MenuPage.php
│   │       ├── CommunicationPage.php
│   │       ├── HelpPage.php
│   │       ├── FormsPage.php
│   │       └── WeatherPage.php
│   ├── Auth/
│   │   ├── Capabilities.php  ← WordPress roles & capabilities
│   │   ├── FrontendAuth.php  ← Logika logowania kadry
│   │   ├── RateLimiter.php   ← Zliczanie prób, blokady
│   │   └── SessionManager.php ← Sesje frontendowe (cookie + DB)
│   ├── Core/
│   │   ├── Activator.php     ← register_activation_hook callback
│   │   ├── Bootstrap.php     ← Główny punkt wejścia; wire all components
│   │   ├── Deactivator.php   ← register_deactivation_hook callback
│   │   ├── EmailService.php  ← Globalny serwis email z szablonami
│   │   └── Loader.php        ← Kolejkuje add_action / add_filter
│   ├── Cron/
│   │   └── Scheduler.php     ← Rejestracja i callbacki WP-Cron
│   ├── Database/
│   │   └── Schema.php        ← Definicje tabel, dbDelta()
│   ├── Frontend/
│   │   └── ShortcodeHandler.php ← Rejestracja shortcode'ów, assets
│   ├── Modules/
│   │   ├── Announcements/
│   │   │   └── AnnouncementRepository.php
│   │   ├── Camps/
│   │   │   ├── CampRepository.php
│   │   │   ├── DailyCountRepository.php
│   │   │   └── StaffRepository.php
│   │   ├── Reports/
│   │   ├── Reservations/
│   │   │   ├── ReservationNotifier.php
│   │   │   ├── ReservationRepository.php
│   │   │   └── ResourceRepository.php
│   │   ├── Menu/
│   │   │   └── MealRepository.php
│   │   ├── Communication/
│   │   │   └── ConversationRepository.php
│   │   ├── Help/
│   │   │   └── HelpRepository.php
│   │   └── Forms/
│   │       ├── FormRepository.php
│   │       └── SubmissionRepository.php
│   │   ├── Schedule/
│   │   │   └── ScheduleRepository.php
│   │   └── Weather/
│   │       ├── ImgwAlertsSync.php
│   │       ├── OpenMeteoProvider.php
│   │       ├── WeatherAlertRepository.php
│   │       ├── WeatherProviderInterface.php
│   │       └── WeatherService.php
│   └── REST/
│       ├── AuthController.php
│       ├── BaseController.php
│       ├── PanelController.php
│       ├── PublicController.php
│       ├── ReportsController.php
│       ├── ReservationsController.php
│       ├── ScheduleController.php
│       ├── MenuController.php
│       ├── CommunicationController.php
│       ├── HelpController.php
│       ├── FormsController.php
│       └── WeatherController.php
└── templates/
    ├── admin/
    │   ├── announcements/
    │   ├── camps/
    │   ├── dashboard.php
    │   ├── reports/
    │   ├── reservations/
    │   ├── schedule/
    │   ├── settings/
    │   │   └── index.php
    │   ├── staff/
    │   └── weather/
    ├── email/
    │   ├── base.php
    │   ├── reservation_approved.php
    │   ├── reservation_cancelled.php
    │   ├── reservation_created.php
    │   └── reservation_rejected.php
    └── frontend/
        └── panel.php         ← Główny szablon panelu frontendowego
```

---

## Autoloader PSR-4

```php
// basemgmt.php
spl_autoload_register(static function (string $class): void {
    if ( ! str_starts_with($class, 'BaseMgmt\\') ) return;
    $rel  = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 9));
    $file = BASEMGMT_DIR . 'src' . DIRECTORY_SEPARATOR . $rel . '.php';
    if ( is_readable($file) ) require_once $file;
});
```

Mapowanie namespace → ścieżka:
```
BaseMgmt\Core\Bootstrap         → src/Core/Bootstrap.php
BaseMgmt\Modules\Camps\CampRepository → src/Modules/Camps/CampRepository.php
BaseMgmt\REST\AuthController    → src/REST/AuthController.php
```

---

## Cykl życia żądania

```
WordPress loads plugins
    │
    └─ plugins_loaded (priority 0)
           │
           └─ Bootstrap::instance()->init()
                  │
                  ├─ load_textdomain()
                  ├─ register_capabilities()   → Capabilities::register()
                  ├─ register_admin()          → AdminMenu + admin-post handlers
                  ├─ register_rest()           → 8 × REST controller
                  ├─ register_frontend()       → ShortcodeHandler
                  ├─ register_cron()           → Scheduler + 9 cron hooks
                  ├─ register_notifications()  → ReservationNotifier::register()
                  └─ register_ajax()           → 2 × wp_ajax_bm_*
```

---

## Wzorzec Repository

Każdy moduł ma klasę `*Repository` z metodami statycznymi:

```php
// Przykład: CampRepository
CampRepository::get(int $id): ?object
CampRepository::get_all(array $filters = []): array
CampRepository::create(array $data): int
CampRepository::update(int $id, array $data): bool
CampRepository::delete(int $id): bool
```

Repozytoria:
- są `final` – nie podlegają dziedziczeniu
- używają wyłącznie `$wpdb` – bez zewnętrznych ORM
- sanityzują dane wejściowe
- zwracają obiekty stdClass lub null

---

## Wzorzec kontrolera REST

Wszystkie kontrolery REST dziedziczą po `BaseController`:

```
BaseController (abstract)
├── AuthController
├── PublicController
├── PanelController
├── ReportsController
├── WeatherController
├── ScheduleController
└── ReservationsController
├── MenuController
├── CommunicationController
├── HelpController
└── FormsController
```

`BaseController` dostarcza:
- `self::NAMESPACE = 'bm/v1'`
- `require_session(WP_REST_Request $r)` – weryfikuje sesję i wstrzykuje `_camp_id`, `_staff_id`
- `ok(array $data, int $status)` – odpowiedź 2xx
- `error(string $code, string $message, int $status)` – WP_Error

---

## Wzorzec strony admina

Każda strona admina to klasa z metodami `render()` i `handle_*()`:

```php
final class CampsPage {
    public function render(): void { /* include template */ }
    public function handle_save(): void { /* admin-post handler */ }
    public function handle_delete(): void { /* admin-post handler */ }
}
```

Handlery są rejestrowane przez `AdminMenu::post_actions()` → `Bootstrap::register_admin()`:

```php
// Bootstrap.php
foreach ( $menu->post_actions() as $action => [$obj, $method] ) {
    add_action("admin_post_{$action}", [$obj, $method]);
}
```

---

## Loader

`Loader` przechowuje kolejkę `add_action` / `add_filter` i wykonuje je na `run()`:

```php
$this->loader->add_action('rest_api_init', $controller, 'register_routes');
// ...
$this->loader->run(); // wykonuje wszystkie dodane hooki
```

---

## Dodawanie nowego modułu

1. Utwórz katalog `src/Modules/NazwaModulu/`
2. Dodaj klasę repozytorium `NazwaModuluRepository`
3. Dodaj tabelę DB w `Schema::create_tables()`
4. Utwórz klasę strony admina `src/Admin/Pages/NazwaModuluPage.php`
5. Zarejestruj w `AdminMenu`: menu + akcje
6. Opcjonalnie: utwórz kontroler REST w `src/REST/`
7. Zarejestruj kontroler w `Bootstrap::register_rest()`
8. Dodaj szablony do `templates/admin/nazwa_modulu/`

Szczegóły: [12 – Przewodnik dewelopera](12-developer-guide.md)

---

## Kluczowe zasady architektoniczne

| Zasada | Implementacja |
|--------|--------------|
| Bez globalnych zmiennych | Wszystko przez DI lub metody statyczne Repozytorium |
| Separacja warstw | Repozytoria ↔ Kontrolery ↔ Szablony |
| Bez zależności od ACF/WooCommerce | Czyste WP API + custom tables |
| WordPress Coding Standards | Sanitize, escape, nonce wszędzie |
| Przygotowanie pod i18n | Wszystkie stringi przez `__()` / `_e()` |
| `declare(strict_types=1)` | We wszystkich plikach PHP |
| Final classes | Repozytoria i kontrolery są `final` |
