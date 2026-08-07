# 10 – Bezpieczeństwo

## Przegląd mechanizmów

| Mechanizm | Gdzie | Opis |
|-----------|-------|------|
| Haszowanie kodów | `StaffRepository`, `wp_hash_password()` | 6-cyfrowy PIN jest przechowywany wyłącznie jako hash |
| Rate limiting | `RateLimiter` | 3 próby → blokada czasowa; kolejna nieudana próba po jej wygaśnięciu → blokada trwała |
| Neutralne komunikaty logowania | `FrontendAuth` | Brak ujawniania, czy błąd dotyczy obozu, osoby czy PIN-u |
| Ochrona przed timing attacks | `FrontendAuth` | Symulacja haszowania przy błędach logowania |
| Sesje frontendowe | `SessionManager` | Losowy token 32 B, cookie `HttpOnly`, `SameSite=Strict`, opcjonalnie `Secure` |
| Separacja danych obozów | kontrolery REST | `camp_id` pobierane z sesji, nie z żądania |
| Nonce CSRF | REST + admin-post | `wp_verify_nonce()` i `check_admin_referer()` |
| Capability checks | panel admina | `current_user_can()` / `Capabilities::require_admin()` |
| Prepared statements | repozytoria | `$wpdb->prepare()` |
| Anti-double-booking | `ReservationRepository` | transakcja + `SELECT ... FOR UPDATE` |
| Dziennik operacji | `OperationLogger` | audit trail logowań, odblokowań, tworzenia wątków i innych działań |

---

## Uwierzytelnianie kadry

### 6-cyfrowy kod bezpieczeństwa

CampLink wymaga dokładnie **6 cyfr**:

```php
if ( ! preg_match('/^\d{6}$/', $security_code) ) {
    // odrzucenie formularza lub logowania
}
```

Przepływ:

1. administrator zapisuje PIN w formularzu kadry,
2. `StaffRepository` hashuje go przez `wp_hash_password()`,
3. frontend weryfikuje go przez `wp_check_password()`.

W bazie nie jest przechowywany tekst jawny.

---

## Blokada konta kadry

### Model danych

Blokada jest zapisywana bezpośrednio w `bm_staff`:

- `failed_attempts`
- `locked_until`
- `permanent_lock`

### Logika `RateLimiter`

```text
Próby 1–2  → komunikat neutralny
Próba 3    → ustawienie locked_until = teraz + N minut
Po wygaśnięciu blokady:
  1 kolejna nieudana próba → permanent_lock = 1
```

### Konfiguracja

- limit prób: `BASEMGMT_MAX_ATTEMPTS = 3`
- czas blokady: opcja `bm_lockout_minutes` (domyślnie 15)

### Odblokowanie

Blokadę trwałą usuwa wyłącznie administrator:

1. akcja `bm_unlock_staff`,
2. wyzerowanie liczników,
3. reset kodu bezpieczeństwa.

To celowy mechanizm wymuszający zmianę kompromitowanego PIN-u.

---

## Ochrona przed enumeracją i timing attacks

`FrontendAuth::attempt()` zwraca ten sam komunikat dla większości błędów logowania:

```text
Nieprawidłowe dane logowania.
```

Gdy rekord nie istnieje lub konto jest nieaktywne, wykonywane jest dodatkowo symulowane haszowanie:

```php
wp_hash_password(bin2hex(random_bytes(16)));
```

Utrudnia to wnioskowanie na podstawie czasu odpowiedzi.

---

## Sesje frontendowe

### Właściwości tokenu

- `bin2hex(random_bytes(32))`
- zapis w `bm_sessions`
- TTL: `BASEMGMT_SESSION_TTL` (8 godzin)

### Cookie

- `HttpOnly`
- `SameSite=Strict`
- `Secure`, jeśli WordPress działa po HTTPS

### Walidacja

Każde żądanie chronionego REST API przechodzi przez `BaseController::require_session()`:

1. odczyt tokenu z cookie,
2. sanityzacja,
3. sprawdzenie `expires_at > NOW()`,
4. wstrzyknięcie `_camp_id` i `_staff_id` do requestu.

---

## Bezpieczeństwo panelu administracyjnego

### Nonce

Każda operacja mutująca stan używa `check_admin_referer()`:

```php
check_admin_referer('bm_save_staff');
check_admin_referer('bm_update_submission');
check_admin_referer('bm_apply_plan_template');
```

### Uprawnienia

Główny warunek dostępu:

```php
Capabilities::require_admin();
```

Dodatkowo część ekranów, np. **Ustawienia** i **Logi operacji**, wymaga `manage_options`.

---

## Rezerwacje i integralność danych

Tworzenie rezerwacji korzysta z transakcji i blokady wierszy:

```text
START TRANSACTION
SELECT ... FOR UPDATE
sprawdzenie konfliktu
INSERT lub ROLLBACK
COMMIT
```

W praktyce:

- `pending` i `approved` blokują slot,
- dwa równoczesne żądania nie zapiszą tej samej godziny,
- wymagany jest InnoDB.

---

## Uploady i zgłoszenia

Załączniki zgłoszeń są chronione przez backend:

- MIME jest sprawdzany przez `finfo`,
- pliki mają rekord w `bm_submission_attachments`,
- pobieranie przechodzi przez chroniony endpoint REST lub `admin-post`,
- obóz może pobrać tylko własne pliki.

Sam snapshot formularza (`form_snapshot`) zabezpiecza zgłoszenie przed późniejszą zmianą definicji pól.

---

## Logi operacji jako warstwa audytowa

CampLink 1.1.0 wprowadza centralne logi `bm_operation_logs`.

Rejestrowane są m.in.:

- `login_success`
- `login_failed`
- `unlock_staff`
- `thread_created`
- akcje na szablonach planów dnia

Każdy wpis może zawierać:

- `user_id`,
- `staff_id`,
- `ip_address`,
- `object_type`,
- `object_id`,
- `details`.

To nie zastępuje logów serwera, ale znacząco poprawia ślad audytowy na poziomie samej wtyczki.

---

## Rekomendacje produkcyjne

- Wymuś HTTPS.
- Włącz backup tabel `wp_bm_*`.
- Ustaw `DISALLOW_FILE_EDIT`.
- Nie współdziel jednego PIN-u między członkami kadry.
- Regularnie przeglądaj **Logi operacji** i retencję wpisów.

---

## Powiązane dokumenty

- [03 – System dostępu frontendowego](03-frontend-access.md)
- [11 – Zadania cykliczne (Cron)](11-cron.md)
- [16 – Logi operacji](16-operation-logs.md)
