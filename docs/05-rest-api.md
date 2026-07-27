# 05 – REST API

Wszystkie endpointy działają pod namespace `bm/v1`.

Bazowy URL: `https://twoja-strona.pl/wp-json/bm/v1/`

---

## Uwierzytelnianie

### Endpointy publiczne

Nie wymagają sesji. Używają nonce `bm_login` do ochrony CSRF.

### Endpointy panelowe (`/panel/...`)

Wymagają ważnej sesji frontendowej (ciasteczko `bm_session`).

Brak sesji → `401 Unauthorized`:
```json
{ "code": "bm_unauthorized", "message": "Wymagane zalogowanie.", "data": { "status": 401 } }
```

### Endpointy admina (zarządzanie przez WP Admin)

Obsługiwane przez `admin-post.php` (nie REST API) z nonce + `current_user_can()`.

---

## Endpointy autentykacji

### POST /bm/v1/auth/login

Logowanie kadry. Weryfikuje nonce CSRF.

**Body**:
```json
{
    "camp_id": 1,
    "staff_id": 5,
    "security_code": "abc123",
    "nonce": "wp_nonce_value"
}
```

**Odpowiedź sukces** `200`:
```json
{
    "success": true,
    "camp_id": 1,
    "staff_id": 5,
    "display_name": "Jan Kowalski"
}
```
Ustawia ciasteczko `bm_session`.

**Odpowiedź błąd** `401`:
```json
{
    "success": false,
    "message": "Nieprawidłowe dane logowania."
}
```

**Odpowiedź blokada** `401`:
```json
{
    "success": false,
    "message": "Konto tymczasowo zablokowane.",
    "locked_until": 742
}
```
`locked_until` = sekundy do odblokowania.

---

### POST /bm/v1/auth/logout

Wylogowanie. Usuwa sesję i czyści ciasteczko.

**Odpowiedź** `200`:
```json
{ "success": true }
```

---

### GET /bm/v1/auth/status

Sprawdzenie stanu sesji (używane przy ładowaniu aplikacji).

**Odpowiedź – zalogowany** `200`:
```json
{
    "authenticated": true,
    "camp_id": 1,
    "staff_id": 5,
    "expires_at": "2025-07-28 10:30:00"
}
```

**Odpowiedź – niezalogowany** `200`:
```json
{ "authenticated": false }
```

---

## Endpointy publiczne

### GET /bm/v1/public/camps

Lista aktywnych obozów do dropdownu na ekranie logowania.

**Odpowiedź** `200`:
```json
{
    "camps": [
        { "id": 1, "name": "Obóz Harcerzy 2025" },
        { "id": 2, "name": "Obóz Szóstek" }
    ]
}
```

---

### GET /bm/v1/public/camps/{id}/staff

Lista aktywnej kadry dla danego obozu (tylko imię/nazwisko/rola – bez danych wrażliwych).

**Parametry URL**: `id` – ID obozu

**Odpowiedź** `200`:
```json
{
    "staff": [
        { "id": 5, "display_name": "Jan Kowalski", "role": "Komendant" },
        { "id": 6, "display_name": "Anna Nowak", "role": "Zastępca" }
    ]
}
```

---

## Endpointy panelowe (wymagają sesji)

### GET /bm/v1/panel/camp

Dane własnego obozu.

**Odpowiedź** `200`:
```json
{
    "id": 1,
    "name": "Obóz Harcerzy 2025",
    "start_date": "2025-07-01",
    "end_date": "2025-08-15",
    "status": "active"
}
```

---

### GET /bm/v1/panel/announcements

Aktywne ogłoszenia dla własnego obozu (globalne + przypisane).

**Odpowiedź** `200`:
```json
{
    "announcements": [
        {
            "id": 10,
            "title": "Zmiana harmonogramu",
            "content": "...",
            "is_urgent": true,
            "priority": 5,
            "valid_from": "2025-07-27 08:00:00",
            "valid_until": "2025-07-28 20:00:00"
        }
    ]
}
```

---

### POST /bm/v1/panel/announcements

Zgłoszenie ogłoszenia przez kadrę (wymaga zatwierdzenia admina).

**Body**:
```json
{
    "title": "Prośba o zmianę",
    "content": "...",
    "nonce": "bm_panel_nonce"
}
```

**Odpowiedź** `201`:
```json
{ "success": true, "id": 15 }
```

---

### GET /bm/v1/panel/daily-count

Aktualny meldunek dzienny własnego obozu.

**Odpowiedź** `200`:
```json
{
    "date": "2025-07-27",
    "participants": 45,
    "staff": 8,
    "workers": 3,
    "status": "submitted",
    "submitted_at": "2025-07-27 09:15:00"
}
```

---

### POST /bm/v1/panel/daily-count

Złożenie meldunku dziennego.

**Body**:
```json
{
    "participants": 45,
    "staff": 8,
    "workers": 3,
    "notes": "Uwagi...",
    "nonce": "bm_panel_nonce"
}
```

---

### GET /bm/v1/panel/weather

Bieżące dane pogodowe i aktywne ostrzeżenia.

**Odpowiedź** `200`:
```json
{
    "current": {
        "temperature": 24.5,
        "description": "Pochmurnie",
        "wind_speed": 12,
        "humidity": 65
    },
    "alerts": [
        {
            "id": 3,
            "title": "Ostrzeżenie burzowe",
            "type": "warning",
            "source": "imgw",
            "is_urgent": true
        }
    ]
}
```

---

### GET /bm/v1/panel/schedule

Plan dnia dla własnego obozu.

**Parametry zapytania**:
- `date` (opcjonalny) – format `Y-m-d`, domyślnie dzisiaj

**Odpowiedź** `200`:
```json
{
    "date": "2025-07-27",
    "plan_id": 12,
    "title": "",
    "is_global": true,
    "items": [
        {
            "id": 45,
            "time_from": "07:00",
            "time_to": "07:30",
            "title": "Pobudka i apel poranny",
            "category": "apel",
            "item_status": "active",
            "is_mandatory": true,
            "is_new_today": false,
            "is_updated_today": false
        }
    ]
}
```

---

### GET /bm/v1/panel/schedule/dates

Lista dat, dla których istnieje plan (do nawigacji kalendarza).

**Parametry**: `from`, `to` – format `Y-m-d`

**Odpowiedź** `200`:
```json
{ "dates": ["2025-07-25", "2025-07-26", "2025-07-27"] }
```

---

### GET /bm/v1/panel/reservations/resources

Lista aktywnych zasobów dostępnych do rezerwacji.

**Odpowiedź** `200`:
```json
{
    "resources": [
        {
            "id": 1,
            "name": "Boisko główne",
            "type": "boisko",
            "available_from": "06:00:00",
            "available_to": "22:00:00",
            "min_advance_hours": 2,
            "rules": "Rezerwacja min. 2h wcześniej."
        }
    ]
}
```

---

### GET /bm/v1/panel/reservations/slots

Zajęte sloty dla zasobu w danym dniu.

**Parametry**: `resource_id` (wymagany), `date` (wymagany, `Y-m-d`)

**Odpowiedź** `200`:
```json
{
    "slots": [
        { "start_time": "10:00:00", "end_time": "12:00:00", "status": "approved" },
        { "start_time": "15:00:00", "end_time": "16:30:00", "status": "pending" }
    ]
}
```

---

### GET /bm/v1/panel/reservations

Własne rezerwacje obozu.

**Parametry**: `status` (opcjonalny), `date_from`, `date_to`

**Odpowiedź** `200`:
```json
{
    "reservations": [
        {
            "id": 7,
            "resource_id": 1,
            "res_date": "2025-07-28",
            "start_time": "14:00:00",
            "end_time": "16:00:00",
            "purpose": "Mecz piłkarski",
            "status": "pending",
            "admin_comment": null
        }
    ]
}
```

---

### POST /bm/v1/panel/reservations

Złożenie rezerwacji.

**Body**:
```json
{
    "resource_id": 1,
    "res_date": "2025-07-28",
    "start_time": "14:00",
    "end_time": "16:00",
    "purpose": "Mecz piłkarski",
    "nonce": "bm_panel_nonce"
}
```

**Odpowiedź sukces** `201`:
```json
{ "success": true, "id": 8 }
```

**Odpowiedź błąd** `400` / `409`:
```json
{ "success": false, "error": "conflict", "message": "Wybrany termin jest już zajęty." }
```

Możliwe wartości `error`: `conflict`, `blocked`, `unavailable`, `too_short`, `too_long`, `camp_limit`

---

### POST /bm/v1/panel/reservations/{id}/cancel

Anulowanie własnej rezerwacji (tylko `pending`, przed datą, z uwzględnieniem `cancel_advance_hours`).

**Odpowiedź** `200`:
```json
{ "success": true }
```

**Odpowiedź błąd** `403`:
```json
{ "success": false, "message": "Nie można anulować tej rezerwacji." }
```

---

## Kody HTTP używane przez API

| Kod | Znaczenie |
|-----|-----------|
| 200 | Sukces |
| 201 | Zasób utworzony |
| 400 | Błąd walidacji lub nieprawidłowe dane |
| 401 | Brak sesji / niezalogowany |
| 403 | Brak uprawnień (nonce, forbidden) |
| 404 | Zasób nie istnieje |
| 409 | Konflikt (np. double-booking) |
| 500 | Błąd serwera |

---

## Testowanie API

Przykłady z cURL:

```bash
# Sprawdź status sesji
curl -s https://twoja-strona.pl/wp-json/bm/v1/auth/status \
     -H "Cookie: bm_session=TOKEN"

# Lista aktywnych obozów
curl -s https://twoja-strona.pl/wp-json/bm/v1/public/camps

# Logowanie
curl -s -X POST https://twoja-strona.pl/wp-json/bm/v1/auth/login \
     -H "Content-Type: application/json" \
     -d '{"camp_id":1,"staff_id":5,"security_code":"abc123","nonce":"NONCE"}'
```

Nonce do testów możesz pobrać z `bmConfig.loginNonce` wstrzykniętego przez `ShortcodeHandler::enqueue_assets()`.
