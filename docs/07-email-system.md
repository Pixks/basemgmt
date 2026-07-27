# 07 – System email

## Architektura

`EmailService` (`src/Core/EmailService.php`) to globalny, statyczny serwis email używany przez wszystkie moduły pluginu. Zapewnia:

- spójny wygląd (nagłówek, logo, kolory, stopka),
- konfigurowalną personalizację przez admina,
- system szablonów PHP,
- integrację z `wp_mail()`.

---

## Konfiguracja

WP Admin → **Baza Obozowa → Ustawienia** → sekcja "Ustawienia powiadomień email"

| Ustawienie | Domyślnie | Opis |
|------------|-----------|------|
| Nazwa nadawcy | Nazwa strony WP | Widoczna jako "Od:" |
| Email nadawcy | admin email WP | Adres "Od:" |
| Email admina (powiadomienia) | admin email WP | Adres, na który trafiają notyfikacje systemowe |
| Kolor nagłówka | `#2271b1` | Hex kolor paska nagłówka emaila |
| URL logo | *(puste)* | Jeśli podane, wyświetlane zamiast tytułu |
| Tytuł nagłówka | Nazwa strony WP | Tekst w nagłówku (gdy brak logo) |
| Stopka | Auto-generowana | Widoczna na dole każdego emaila |

Ustawienia przechowywane jako `basemgmt_email_settings` (WP Option).

---

## Wysyłka emaila

```php
use BaseMgmt\Core\EmailService;

// Prosty email z szablonu
EmailService::send(
    'odbiorca@example.com',         // $to
    EmailService::subject('Temat'), // $subject  → "[Nazwa Strony] Temat"
    'reservation_created',          // $template – slug pliku w templates/email/
    [                               // $data     – zmienne dostępne w szablonie
        'reservation'   => $reservation_array,
        'resource_name' => 'Boisko',
        'camp_name'     => 'Obóz Harcerzy',
        'is_admin'      => false,
    ]
);

// Email do wielu odbiorców
EmailService::send_many(
    ['a@example.com', 'b@example.com'],
    EmailService::subject('Komunikat'),
    'reservation_approved',
    $data
);
```

### `EmailService::subject(string $text): string`

Dodaje prefix `[Nazwa Strony]` do tematu:
```
[Baza Obozowa] Nowa rezerwacja: Boisko – Obóz Harcerzy
```

---

## Szablony email

Szablony znajdują się w `templates/email/`.

### Struktura renderowania

```
EmailService::send()
    │
    └─ render('reservation_created', $data)
           │
           ├─ 1. Wczytaj templates/email/reservation_created.php → $content
           │
           └─ 2. Owiń w templates/email/base.php ($content, $settings)
                  → Gotowy HTML email
```

### base.php

Szablon bazowy z:
- nagłówkiem (logo lub tytuł, kolor tła konfigurowalny)
- blokiem `$content`
- stopką z tekstem z ustawień

Zmienne dostępne w `base.php`:
- `$content` – wyrenderowany HTML z szablonu treści
- `$subject` – temat emaila
- `$settings` – tablica z ustawieniami EmailService

### Szablony treści

| Plik | Kiedy wysyłany |
|------|----------------|
| `reservation_created.php` | Nowa rezerwacja złożona przez obóz |
| `reservation_approved.php` | Admin zatwierdził rezerwację |
| `reservation_rejected.php` | Admin odrzucił rezerwację |
| `reservation_cancelled.php` | Rezerwacja anulowana (przez obóz lub admina) |

### Zmienne dostępne w szablonach treści

```php
// reservation_created.php, reservation_approved.php, ...
$reservation    // array/object – dane rezerwacji
$resource_name  // string – nazwa zasobu
$camp_name      // string – nazwa obozu
$is_admin       // bool – czy email dla admina (true) czy kadry (false)
$admin_comment  // string – komentarz admina (przy odrzuceniu/anulowaniu)
$settings       // array – ustawienia email
```

---

## Tworzenie nowego szablonu

1. Utwórz plik `templates/email/moj_szablon.php`
2. Użyj dostępnych zmiennych z `$data` przekazanego do `send()`

```php
<?php
// templates/email/moj_szablon.php
defined('ABSPATH') || exit;
?>
<h2>Witaj!</h2>
<p>Twoja wiadomość: <?php echo esc_html($tresc); ?></p>
```

3. Wywołaj:
```php
EmailService::send('odbiorca@example.com', EmailService::subject('Temat'), 'moj_szablon', [
    'tresc' => 'Przykładowa treść',
]);
```

---

## Powiadomienia o rezerwacjach (ReservationNotifier)

`ReservationNotifier` (`src/Modules/Reservations/ReservationNotifier.php`) nasłuchuje hooków WP i wywołuje `EmailService::send()`:

```php
// Rejestracja (Bootstrap.php)
$notifier = new ReservationNotifier();
$notifier->register();

// Hooksli
add_action('bm_reservation_created',        [$this, 'notify_created'],        10, 2);
add_action('bm_reservation_status_changed', [$this, 'notify_status_changed'], 10, 3);
```

### Logika adresata

1. Notifier pobiera `staff_id` z rezerwacji i szuka jego emaila w `bm_staff`
2. Jeśli brak – bierze email pierwszego aktywnego członka kadry tego obozu
3. Email admina pochodzi z `EmailService::get_settings()['admin_notify_email']`

---

## Filtrowanie `wp_mail`

```php
// Przed wysyłką – ustawia Content-Type i nadawcę
add_filter('wp_mail_content_type', fn() => 'text/html');
add_filter('wp_mail_from',         fn() => $settings['from_email']);
add_filter('wp_mail_from_name',    fn() => $settings['from_name']);

$result = wp_mail($to, $subject, $body);

// Po wysyłce – natychmiastowe czyszczenie filtrów
remove_all_filters('wp_mail_content_type');
remove_all_filters('wp_mail_from');
remove_all_filters('wp_mail_from_name');
```

Filtrowanie jest aktywne tylko na czas wysyłki pojedynczego emaila, aby nie wpływać na inne emaile WordPress.

---

## Test wysyłki emaila

WP Admin → **Baza Obozowa → Ustawienia** → sekcja "Test emaila"

- Wpisz adres odbiorcy
- Kliknij "Wyślij testowy email"
- Zostanie wysłany przykładowy email z szablonu `reservation_created`

---

## Dodawanie powiadomień z innych modułów

Aby dodać email z nowego modułu:

```php
// 1. Uruchom akcję w odpowiednim miejscu (np. w repozytorium)
do_action('bm_moj_event', $id, $data);

// 2. Utwórz klasę Notifier dla modułu
final class MojModulNotifier {
    public function register(): void {
        add_action('bm_moj_event', [$this, 'notify'], 10, 2);
    }

    public function notify(int $id, array $data): void {
        $settings = EmailService::get_settings();
        EmailService::send(
            $settings['admin_notify_email'],
            EmailService::subject(__('Nowe zdarzenie', 'basemgmt')),
            'moj_szablon',
            $data
        );
    }
}

// 3. Zarejestruj w Bootstrap::register_notifications()
$notifier = new MojModulNotifier();
$notifier->register();
```
