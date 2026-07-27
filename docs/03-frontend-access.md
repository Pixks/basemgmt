# 03 – System dostępu frontendowego

## Koncepcja

Kadra obozów **nie posiada kont WordPress**. Dostęp do panelu obozu odbywa się przez dedykowany ekran frontendowy oparty na:

- własnej tabeli `bm_staff` z haszowanymi kodami bezpieczeństwa,
- tokenach sesji przechowywanych w cookie `HttpOnly; SameSite=Strict`,
- mechanizmie rate limiting z automatyczną blokadą konta.

> Administratorzy ośrodka używają normalnych kont WordPress i panelu WP Admin.

---

## Przepływ logowania (krok po kroku)

```
[Ekran frontendowy]
       │
       ▼
1. Użytkownik wybiera obóz z listy aktywnych obozów
       │  GET /bm/v1/public/camps
       ▼
2. Wybiera swoje imię z listy kadry przypisanej do obozu
       │  GET /bm/v1/public/camps/{id}/staff
       ▼
3. Wpisuje kod bezpieczeństwa
       │
       ▼
4. POST /bm/v1/auth/login
       │  ├── Weryfikacja nonce (CSRF)
       │  ├── Wczytanie rekordu staff (camp_id + staff_id jednocześnie)
       │  ├── Sprawdzenie is_active
       │  ├── Sprawdzenie lockout (RateLimiter)
       │  ├── wp_check_password(kod, hash)
       │  ├── Sukces → RateLimiter::clear() + SessionManager::create()
       │  └── Porażka → RateLimiter::record_failure()
       │
       ▼
5. Ustawienie cookie bm_session (HttpOnly, SameSite=Strict)
       │
       ▼
6. Frontend wyświetla panel obozu (dane chronione przez require_session)
```

---

## Komponenty

### FrontendAuth (`src/Auth/FrontendAuth.php`)

Główna klasa logiki uwierzytelniania.

```php
// Pobierz listę aktywnych obozów (dla dropdownu)
FrontendAuth::get_active_camps(): array

// Pobierz aktywną kadrę dla obozu (tylko imię/nazwisko/rola)
FrontendAuth::get_active_staff_for_camp(int $camp_id): array

// Weryfikuj dane logowania
FrontendAuth::attempt(int $camp_id, int $staff_id, string $security_code): array
// Zwraca: ['success' => true, 'token' => '...', 'camp_id' => N, ...]
// lub:    ['success' => false, 'message' => '...']
```

**Ochrona przed enumeracją**: Przy każdej nieudanej próbie (niezależnie od powodu) wykonywana jest symulacja haszowania (`wp_hash_password(random_bytes(16))`), aby uniemożliwić ataki czasowe.

### SessionManager (`src/Auth/SessionManager.php`)

Zarządza tokenami sesji.

```php
// Utwórz sesję (wywoływane po pomyślnym logowaniu)
SessionManager::create(int $staff_id, int $camp_id): string  // zwraca token

// Sprawdź aktualną sesję (z ciasteczka)
SessionManager::current(): ?object  // obiekt z staff_id, camp_id, expires_at

// Wyloguj (usuń sesję z DB + wyczyść cookie)
SessionManager::destroy(): void

// Cron: usuń wygasłe sesje
SessionManager::cleanup_expired(): void
```

**Właściwości tokenu**:
- 64-znakowy hex string (32 bajty losowych danych)
- Przechowywany w tabeli `bm_sessions` z datą wygaśnięcia
- Cookie: `HttpOnly`, `SameSite=Strict`, `Secure` (jeśli HTTPS)
- TTL: 8 godzin (konfigurowalne przez stałą `BASEMGMT_SESSION_TTL`)

### RateLimiter (`src/Auth/RateLimiter.php`)

Chroni przed atakami brute-force.

```php
RateLimiter::is_locked(object $staff): bool
RateLimiter::record_failure(int $staff_id, object $staff): void
RateLimiter::clear(int $staff_id): void           // po udanym logowaniu
RateLimiter::lockout_remaining(object $staff): int // sekundy do odblokowania
```

**Konfiguracja**:

| Stała | Wartość | Opis |
|-------|---------|------|
| `BASEMGMT_MAX_ATTEMPTS` | `5` | Próby przed blokadą |
| `BASEMGMT_LOCKOUT_TTL` | `900 s` (15 min) | Czas blokady |

Dane blokady (`failed_attempts`, `locked_until`) przechowywane są bezpośrednio w rekordzie `bm_staff`, co eliminuje potrzebę osobnej tabeli.

---

## Kod bezpieczeństwa

- Administrator ustawia kod dla każdego członka kadry w panelu WP Admin
- Kod jest **natychmiast haszowany** funkcją `wp_hash_password()` (bcrypt)
- W bazie nigdy nie jest przechowywany plain text
- Weryfikacja przez `wp_check_password($code, $hash)`
- Administrator może **zresetować kod** w dowolnym momencie (akcja `bm_reset_staff_code`)

---

## Ochrona danych obozu

Po zalogowaniu każde żądanie REST musi przejść przez `require_session()`:

```php
// BaseController.php
protected function require_session(WP_REST_Request $request): bool|\WP_Error {
    $session = SessionManager::current();
    if ( ! $session ) {
        return new \WP_Error('bm_unauthorized', '...', ['status' => 401]);
    }
    // Wstrzyknięcie camp_id i staff_id do żądania
    $request->set_param('_camp_id',  (int) $session->camp_id);
    $request->set_param('_staff_id', (int) $session->staff_id);
    return true;
}
```

Każdy handler w `PanelController`, `ReservationsController`, `ScheduleController` itp. wymaga poprawnej sesji i **filtruje dane wyłącznie do własnego obozu**:

```php
// Przykład w PanelController
$camp_id = (int) $request->get_param('_camp_id'); // z sesji, nie z requestu!
$announcements = AnnouncementRepository::get_for_camp($camp_id);
```

---

## Wygasanie sesji

- Po 8 godzinach cookie wygasa automatycznie
- Po kolejnym żądaniu do REST API – odpowiedź `401 Unauthorized`
- Frontend wyświetla ekran logowania
- Zadanie cron `bm_cleanup_sessions` (daily) usuwa wygasłe rekordy z `bm_sessions`

---

## Wylogowanie

```
POST /bm/v1/auth/logout
```
- Usuwa rekord sesji z bazy danych
- Ustawia cookie z datą wygaśnięcia w przeszłości (czyszczenie)
- Frontend przekierowuje do ekranu logowania

---

## Zarządzanie kadrą (Admin)

### Dodawanie osoby

WP Admin → **Baza Obozowa → Kadra → Dodaj osobę**

Formularz POST na `admin-post.php?action=bm_save_staff`. Kod bezpieczeństwa jest haszowany w `StaffRepository::create()`.

### Resetowanie kodu

Akcja `bm_reset_staff_code` generuje nowy losowy kod (np. 6 cyfr) i wyświetla go jednorazowo w flash notice. Nowy kod jest od razu haszowany.

### Dezaktywacja osoby

Przełącznik `is_active = 0` uniemożliwia logowanie bez usuwania danych historycznych. Istniejące aktywne sesje wygasają naturalnie lub można je usunąć manualnie.

---

## Diagram tabel sesji

```
bm_staff
├── id
├── camp_id (FK → bm_camps.id)
├── security_code_hash (bcrypt)
├── failed_attempts
├── locked_until
├── last_login
└── is_active

bm_sessions
├── id
├── token (UNIQUE, 64 hex)
├── staff_id (FK → bm_staff.id)
├── camp_id  (FK → bm_camps.id)
├── ip_address
├── created_at
└── expires_at (INDEX)
```

---

## Często zadawane pytania

**Czy kadra może logować się do wielu obozów jednocześnie?**
Każdy rekord `bm_staff` jest przypisany do jednego obozu. Jeśli osoba pracuje w kilku obozach, należy założyć osobne rekordy z osobnymi kodami.

**Co jeśli ktoś zna camp_id i staff_id innej osoby?**
Bez poprawnego kodu bezpieczeństwa logowanie jest niemożliwe. Komunikaty błędów są celowo neutralne ("Nieprawidłowe dane logowania") – nie ujawniają, czy problem dotyczy osoby czy kodu.

**Czy można wydłużyć czas sesji?**
Tak – zmień stałą `BASEMGMT_SESSION_TTL` w `basemgmt.php` (domyślnie `8 * HOUR_IN_SECONDS`).

**Czy można wymusić wylogowanie konkretnej osoby?**
Tak – usuń rekord z tabeli `bm_sessions` gdzie `staff_id = X`, lub ustaw `is_active = 0` dla tej osoby.
