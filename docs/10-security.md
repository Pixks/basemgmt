# 10 – Bezpieczeństwo

## Przegląd mechanizmów

| Mechanizm | Gdzie | Opis |
|-----------|-------|------|
| Haszowanie kodów | `StaffRepository`, `wp_hash_password()` | bcrypt, nigdy plain text |
| Rate limiting | `RateLimiter` | 5 prób, 15 min blokada |
| Neutralne komunikaty | `FrontendAuth` | Brak informacji o przyczynie błędu |
| Timing attack protection | `FrontendAuth` | Symulacja hasha przy błędzie |
| Session tokens | `SessionManager` | 32 bajty losowe, hex 64-znakowy |
| HttpOnly cookie | `SessionManager::set_cookie()` | Token niedostępny przez JS |
| SameSite=Strict | `SessionManager::set_cookie()` | Ochrona przed CSRF |
| Secure cookie | `SessionManager::set_cookie()` | Tylko HTTPS (gdy SSL) |
| Session TTL | `BASEMGMT_SESSION_TTL = 8h` | Automatyczne wygasanie |
| camp_id z sesji | Wszystkie REST handlery | Backend nie ufa wartości z requestu |
| Nonce CSRF | Formularze admina + REST login | `check_admin_referer()`, `wp_verify_nonce()` |
| Capability checks | Handlery admina | `current_user_can()` |
| Sanitization | Wszystkie repozytoria | `sanitize_text_field()`, `sanitize_email()` itp. |
| Escaping | Wszystkie szablony | `esc_html()`, `esc_attr()`, `esc_url()` |
| SELECT FOR UPDATE | `ReservationRepository` | Anti-double-booking, race condition |
| Prepared statements | Cały plugin | `$wpdb->prepare()` wszędzie |

---

## Uwierzytelnianie kadry

### Haszowanie kodów bezpieczeństwa

```php
// Przy tworzeniu/aktualizacji kodu
$hash = wp_hash_password($plain_code);
// Przechowywany hash, nigdy plain text

// Przy weryfikacji
if ( ! wp_check_password($entered_code, $stored_hash) ) {
    RateLimiter::record_failure($staff_id, $staff);
    return ['success' => false, 'message' => $generic_error];
}
```

`wp_hash_password()` używa `phpass` (kompatybilnego z bcrypt). Koszty obliczeniowe są odpowiednie do ochrony przed brute-force offline.

### Ochrona przed timing attacks

Przy każdym błędzie logowania (niezależnie od przyczyny) wykonywana jest symulacja:

```php
// Uniemożliwia zmierzenie czasu i odgadnięcie, czy użytkownik istnieje
wp_hash_password(bin2hex(random_bytes(16)));
```

### Rate limiting

```
Próba 1-4: normalna odpowiedź "Nieprawidłowe dane"
Próba 5  : zapis locked_until = NOW() + 15 minut
Próba 6+ : odpowiedź "Konto zablokowane, pozostało X sekund"
```

Dane blokady w `bm_staff`:
- `failed_attempts` – licznik
- `locked_until` – datetime blokady

Po udanym logowaniu oba pola są zerowane.

---

## Sesje frontendowe

### Token

```php
$token = bin2hex(random_bytes(32));  // 256 bitów entropii, 64-znakowy hex
```

### Przechowywanie

- **Serwer**: tabela `bm_sessions` z tokenem i datą wygaśnięcia
- **Klient**: ciasteczko `bm_session` z atrybutami bezpieczeństwa

### Walidacja

Każde żądanie `require_session()`:
1. Odczyta token z ciasteczka `$_COOKIE['bm_session']`
2. Sanityzuje: `sanitize_text_field(wp_unslash(...))`
3. Odpytuje DB: `WHERE token = %s AND expires_at > NOW()`
4. Jeśli brak rekordu → 401 Unauthorized

### Izolacja danych obozu

```php
// PanelController, ReservationsController, ScheduleController
$camp_id = (int) $request->get_param('_camp_id'); // ZAWSZE z sesji!

// Nigdy tak:
// $camp_id = (int) $request->get_param('camp_id'); // z requestu – niebezpieczne
```

---

## WordPress Admin

### Nonce

Każdy formularz admina:
```php
// W szablonie
wp_nonce_field('bm_save_camp');

// W handlerze
check_admin_referer('bm_save_camp');
// Automatycznie wywołuje wp_die() przy błędzie
```

### Capability checks

```php
// Capabilities.php – sprawdzenie uprawnienia
public static function require_admin(): void {
    if ( ! current_user_can('manage_basemgmt') ) {
        wp_die(__('Brak uprawnień.', 'basemgmt'), 403);
    }
}
```

### Prepared statements

Zawsze `$wpdb->prepare()`:

```php
$wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $t WHERE id = %d AND camp_id = %d",
    $id,
    $camp_id
));
```

---

## Sanityzacja danych wejściowych

### Repozytoria

| Typ danych | Funkcja |
|------------|---------|
| Tekst (krótki) | `sanitize_text_field()` |
| Tekst (wieloliniowy) | `sanitize_textarea_field()` |
| Email | `sanitize_email()` |
| URL | `esc_url_raw()` |
| HTML content | `wp_kses_post()` |
| Kolor hex | `sanitize_hex_color()` |
| Liczba całkowita | `(int)` lub `absint()` |
| Klucz/identyfikator | `sanitize_key()` |

### REST API

Argumenty endpointów z `sanitize_callback`:

```php
'args' => [
    'camp_id'       => ['required' => true, 'sanitize_callback' => 'absint'],
    'security_code' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
]
```

---

## Escaping wyjścia

W każdym szablonie PHP:

```php
echo esc_html($value);           // tekst
echo esc_attr($attribute);       // atrybut HTML
echo esc_url($url);              // URL
echo esc_js($js_value);          // w kontekście JS
echo wp_kses_post($html);        // dozwolone tagi HTML
```

---

## Ochrona przed double-booking

Krytyczny mechanizm w `ReservationRepository::create_with_conflict_check()`:

```
1. START TRANSACTION
2. SELECT ... FOR UPDATE  ← blokuje pasujące wiersze
3. Sprawdź konflikt
4. Jeśli konflikt → ROLLBACK, return ['error' => 'conflict']
5. Jeśli OK → INSERT
6. COMMIT
```

`FOR UPDATE` powoduje, że dwa równoczesne żądania nie mogą wejść w krok INSERT jednocześnie – drugie czeka na zwolnienie blokady z pierwszego.

---

## Rekomendacje dla produkcji

### Konfiguracja serwera

```nginx
# Nginx: ochrona przed dostępem do plików PHP w przesłanych katalogach
location ~* /uploads/.*\.php$ {
    deny all;
}
```

### `wp-config.php`

```php
define('DISALLOW_FILE_EDIT', true);  // Blokuj edytor plików WP
define('WP_DEBUG', false);           // Wyłącz debug na produkcji
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', true);        // Logi do pliku, nie do ekranu
```

### HTTPS

Sesje pluginu są oznaczone `Secure` tylko gdy `is_ssl()` zwraca `true`. **Uruchom plugin wyłącznie przez HTTPS w produkcji.**

### Backup

Przed aktualizacją pluginu zawsze wykonaj backup tabel `wp_bm_*`.

---

## Znane ograniczenia

| Ograniczenie | Opis |
|-------------|------|
| Brak 2FA | Logowanie opiera się na jednym kodzie |
| Brak audit log | Nie ma pełnej historii działań adminów |
| Jedna sesja per osoba | Nowe logowanie nie unieważnia poprzedniej sesji |
| IMGW API bez klucza | Publiczne API bez autoryzacji – może mieć limity |

---

## Zdarzenia bezpieczeństwa do monitorowania

Rekomendowane alerty w logach serwera/WP:

- Wiele żądań POST `/bm/v1/auth/login` z tego samego IP
- Błędy `bm_unauthorized` (401) w logach REST API
- Wyjątki bazy danych (nieudane transakcje)
