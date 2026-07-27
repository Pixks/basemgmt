# 12 – Przewodnik dewelopera

## Zasady ogólne

| Zasada | Implementacja |
|--------|--------------|
| PHP 8.1+ | `declare(strict_types=1)` w każdym pliku |
| PSR-4 | `BaseMgmt\` → `src/`, bez Composera |
| OOP | Klasy `final`, bez dziedziczenia w repozytoriach |
| WP Coding Standards | Sanitize, escape, nonce, capability checks |
| i18n | Wszystkie stringi przez `__()` / `_e()`, text domain: `basemgmt` |
| Brak globalnych zmiennych | Dane przez DI lub statyczne metody Repository |

---

## Dodawanie nowego modułu

### Krok 1: Utwórz katalog modułu

```
src/Modules/MojModul/
    MojModulRepository.php
    MojModulNotifier.php     (opcjonalne, dla powiadomień email)
```

### Krok 2: Zdefiniuj tabelę w Schema.php

```php
// src/Database/Schema.php – wewnątrz create_tables()
$sql[] = "CREATE TABLE {$p}bm_moj_modul (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    camp_id    BIGINT UNSIGNED NOT NULL,
    name       VARCHAR(255)    NOT NULL,
    status     VARCHAR(20)     NOT NULL DEFAULT 'active',
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_camp (camp_id),
    KEY idx_status (status)
) $charset;";
```

Dodaj klucz do `table_names()`:
```php
'moj_modul' => $wpdb->prefix . 'bm_moj_modul',
```

Dodaj do `uninstall.php`:
```php
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bm_moj_modul");
```

### Krok 3: Utwórz repozytorium

```php
<?php
declare(strict_types=1);
namespace BaseMgmt\Modules\MojModul;

use BaseMgmt\Database\Schema;

defined('ABSPATH') || exit;

final class MojModulRepository {

    public static function get(int $id): ?object {
        global $wpdb;
        $t = Schema::table('moj_modul');
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d", $id)) ?: null;
    }

    public static function get_all(array $filters = []): array {
        global $wpdb;
        $t     = Schema::table('moj_modul');
        $where = ['1=1'];
        $vals  = [];

        if ( ! empty($filters['camp_id']) ) {
            $where[] = 'camp_id = %d';
            $vals[]  = (int) $filters['camp_id'];
        }

        $sql = 'SELECT * FROM ' . $t . ' WHERE ' . implode(' AND ', $where);
        if ($vals) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            return $wpdb->get_results($wpdb->prepare($sql, ...$vals));
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_results($sql);
    }

    public static function create(array $data): int {
        global $wpdb;
        $wpdb->insert(Schema::table('moj_modul'), [
            'camp_id' => (int) $data['camp_id'],
            'name'    => sanitize_text_field($data['name']),
            'status'  => sanitize_key($data['status'] ?? 'active'),
        ]);
        return (int) $wpdb->insert_id;
    }

    public static function update(int $id, array $data): bool {
        global $wpdb;
        return (bool) $wpdb->update(
            Schema::table('moj_modul'),
            ['name' => sanitize_text_field($data['name'] ?? '')],
            ['id' => $id]
        );
    }

    public static function delete(int $id): bool {
        global $wpdb;
        return (bool) $wpdb->delete(Schema::table('moj_modul'), ['id' => $id]);
    }
}
```

### Krok 4: Utwórz stronę admina

```php
<?php
declare(strict_types=1);
namespace BaseMgmt\Admin\Pages;

use BaseMgmt\Admin\AdminMenu;
use BaseMgmt\Auth\Capabilities;
use BaseMgmt\Modules\MojModul\MojModulRepository;

defined('ABSPATH') || exit;

final class MojModulPage {

    public function render(): void {
        Capabilities::require_admin();
        $items = MojModulRepository::get_all();
        include BASEMGMT_DIR . 'templates/admin/moj_modul/list.php';
    }

    public function handle_save(): void {
        Capabilities::require_admin();
        check_admin_referer('bm_save_moj_modul');
        $id = (int) ($_POST['item_id'] ?? 0);
        if ($id) {
            MojModulRepository::update($id, $_POST);
        } else {
            MojModulRepository::create($_POST);
        }
        AdminMenu::set_notice(__('Zapisano.', 'basemgmt'));
        wp_safe_redirect(admin_url('admin.php?page=basemgmt-moj-modul'));
        exit;
    }

    public function handle_delete(): void {
        Capabilities::require_admin();
        $id = (int) ($_GET['id'] ?? 0);
        check_admin_referer('bm_delete_moj_modul_' . $id);
        MojModulRepository::delete($id);
        AdminMenu::set_notice(__('Usunięto.', 'basemgmt'));
        wp_safe_redirect(admin_url('admin.php?page=basemgmt-moj-modul'));
        exit;
    }
}
```

### Krok 5: Dodaj do AdminMenu.php

```php
// Import
use BaseMgmt\Admin\Pages\MojModulPage;

// Właściwość
private MojModulPage $moj_modul;

// W konstruktorze
$this->moj_modul = new MojModulPage();

// W register_menus()
add_submenu_page(
    'basemgmt',
    __('Mój Moduł', 'basemgmt'),
    __('Mój Moduł', 'basemgmt'),
    'manage_basemgmt',
    'basemgmt-moj-modul',
    [$this->moj_modul, 'render']
);

// W post_actions()
'bm_save_moj_modul'   => [$this->moj_modul, 'handle_save'],
'bm_delete_moj_modul' => [$this->moj_modul, 'handle_delete'],
```

### Krok 6: (Opcjonalnie) Dodaj endpoint REST

```php
<?php
declare(strict_types=1);
namespace BaseMgmt\REST;

use BaseMgmt\Modules\MojModul\MojModulRepository;
use WP_REST_Request;

defined('ABSPATH') || exit;

final class MojModulController extends BaseController {

    public function register_routes(): void {
        register_rest_route(self::NAMESPACE, '/panel/moj-modul', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_items'],
            'permission_callback' => fn($r) => $this->require_session($r),
        ]);
    }

    public function get_items(WP_REST_Request $request): mixed {
        $camp_id = (int) $request->get_param('_camp_id'); // z sesji
        $items   = MojModulRepository::get_all(['camp_id' => $camp_id]);
        return $this->ok(['items' => $items]);
    }
}
```

Zarejestruj w `Bootstrap::register_rest()`:
```php
$this->loader->add_action('rest_api_init', new MojModulController(), 'register_routes');
```

---

## Dostępne hooki (do integracji)

### Akcje uruchamiane przez plugin

| Hook | Parametry | Opis |
|------|-----------|------|
| `bm_daily_reminders_sent` | `array $missing_camps` | Po wysłaniu przypomnień |
| `bm_announcements_expired` | `int $count` | Po wygaśnięciu ogłoszeń |
| `bm_missing_reports_checked` | `array $missing, string $date` | Codzienne sprawdzenie |
| `bm_weather_alerts_expired` | `int $count` | Po dezaktywacji ostrzeżeń |
| `bm_reservations_expired` | `int $count` | Po wygaśnięciu rezerwacji |
| `bm_reservation_created` | `int $id, array $data` | Nowa rezerwacja |
| `bm_reservation_status_changed` | `int $id, string $status, int $user_id` | Zmiana statusu rezerwacji |

### Przykład użycia

```php
// W motywie lub innej wtyczce
add_action('bm_reservation_created', function(int $id, array $data): void {
    // np. wyślij SMS
    my_sms_service_send($id);
}, 10, 2);

add_action('bm_missing_reports_checked', function(array $missing, string $date): void {
    // np. dodaj do zewnętrznego systemu monitoringu
    foreach ($missing as $camp) {
        my_monitor_log('missing_report', $camp->name, $date);
    }
}, 10, 2);
```

---

## Wzorzec powiadamiania (Notifier)

```php
// src/Modules/MojModul/MojModulNotifier.php
final class MojModulNotifier {

    public function register(): void {
        add_action('bm_moj_event', [$this, 'notify'], 10, 2);
    }

    public function notify(int $id, array $data): void {
        $settings = \BaseMgmt\Core\EmailService::get_settings();
        \BaseMgmt\Core\EmailService::send(
            $settings['admin_notify_email'],
            \BaseMgmt\Core\EmailService::subject(__('Nowe zdarzenie', 'basemgmt')),
            'moj_szablon_email',
            $data + ['settings' => $settings]
        );
    }
}
```

Rejestracja w `Bootstrap::register_notifications()`:
```php
(new MojModulNotifier())->register();
```

---

## Szablony admina – konwencje

```php
<?php
// templates/admin/moj_modul/list.php
defined('ABSPATH') || exit;
/**
 * @var array $items – lista elementów
 */
?>
<div class="wrap bm-wrap">
    <h1>
        <?php esc_html_e('Mój Moduł', 'basemgmt'); ?>
        <a href="...nowy..." class="page-title-action"><?php esc_html_e('Dodaj nowy', 'basemgmt'); ?></a>
    </h1>
    <!-- ... -->
    <?php foreach ($items as $item): ?>
        <tr>
            <td><?php echo esc_html($item->name); ?></td>
        </tr>
    <?php endforeach; ?>
</div>
```

**Zasady szablonów**:
- `defined('ABSPATH') || exit;` na początku
- Komentarz z typami zmiennych (`@var`)
- Zawsze `esc_html()`, `esc_attr()`, `esc_url()` przy wyświetlaniu
- Tylko logika prezentacji – żadnych zapytań DB
- Klasa `bm-wrap` na głównym `div`

---

## Tłumaczenia (i18n)

Plugin obsługuje tłumaczenia przez standard WordPress gettext.

```php
// Tłumaczenie stringa
__('Tekst do przetłumaczenia', 'basemgmt')
_e('Tekst do przetłumaczenia', 'basemgmt')

// Z zmienną
sprintf(__('Obóz: %s', 'basemgmt'), esc_html($name))

// Liczba mnoga
_n('1 obóz', '%d obozy', $count, 'basemgmt')
```

Generowanie pliku .pot:
```bash
wp i18n make-pot . languages/basemgmt.pot
```

---

## Testowanie

### PHP lint

```bash
find src/ -name "*.php" -exec php -l {} \;
```

### Sprawdzenie zapytań DB

```php
// Tymczasowo w dev
define('SAVEQUERIES', true);
// Potem:
var_dump($wpdb->queries);
```

### Test REST API

```bash
# Status sesji
curl -s https://dev.local/wp-json/bm/v1/auth/status

# Publiczne obozy
curl -s https://dev.local/wp-json/bm/v1/public/camps | json_pp
```

### Ręczne wywołanie crona

```bash
wp cron event run bm_expire_reservations
wp cron event run bm_daily_reminders
```

---

## Często popełniane błędy

### ❌ Czytanie camp_id z requestu zamiast z sesji

```php
// ZŁE – można sfałszować
$camp_id = (int) $request->get_param('camp_id');

// DOBRE – z sesji
$camp_id = (int) $request->get_param('_camp_id');
```

### ❌ Brak nonce w formularzu admina

```php
// ZŁE
<form method="post">
    <input name="action" value="bm_save_cokolwiek">

// DOBRE
<form method="post">
    <?php wp_nonce_field('bm_save_cokolwiek'); ?>
    <input name="action" value="bm_save_cokolwiek">
```

### ❌ Brak check_admin_referer w handlerze

```php
// ZŁE
public function handle_save(): void {
    Capabilities::require_admin();
    // ...

// DOBRE
public function handle_save(): void {
    Capabilities::require_admin();
    check_admin_referer('bm_save_cokolwiek');
    // ...
```

### ❌ Interpolacja SQL bez prepare()

```php
// ZŁE
$wpdb->get_results("SELECT * FROM $t WHERE id = " . $id);

// DOBRE
$wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE id = %d", $id));
```

### ❌ Echo bez escape

```php
// ZŁE
echo $user_input;

// DOBRE
echo esc_html($user_input);
```

---

## Zależności zewnętrzne (CDN, tylko admin)

| Biblioteka | Wersja | Ładowana na |
|------------|--------|------------|
| Sortable.js | 1.15.2 | `admin.php?page=basemgmt-schedule&edit=1` |
| FullCalendar | 6.1.11 | `admin.php?page=basemgmt-reservations` |

Frontend Alpine.js pochodzi z Breakdance – plugin **nie ładuje własnej instancji** gdy Alpine już istnieje.
