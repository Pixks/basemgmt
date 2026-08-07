# 07 – System email

## Architektura

`EmailService` (`src/Core/EmailService.php`) to globalny, statyczny serwis email używany przez CampLink. Zapewnia:

- wspólny layout HTML (nagłówek, treść, stopka),
- ustawienia nadawcy i wyglądu przechowywane w WP Options,
- edytowalne szablony email z tokenami `{{zmiennych}}`,
- wysyłkę przez `wp_mail()`,
- integrację z powiadomieniami rezerwacji i raportami systemowymi.

---

## Gdzie konfiguruje się email?

WP Admin → **CampLink → Ustawienia**

W praktyce strona ustawień ma cztery obszary związane z komunikacją:

1. **Ustawienia powiadomień email** – nadawca, nagłówek, stopka.
2. **Szablony emaili** – edycja tematów i HTML dla zdefiniowanych typów wiadomości.
3. **Test emaila** – wysyłka wiadomości testowej.
4. **Konfiguracja powiadomień** – adresy odbiorców dla brakujących meldunków i cyklicznych raportów oraz interwał raportów.

---

## Ustawienia ogólne

Przechowywane głównie w opcji `basemgmt_email_settings`.

| Ustawienie | Opis |
|------------|------|
| Nazwa nadawcy (`from_name`) | Nazwa widoczna w polu „Od” |
| Email nadawcy (`from_email`) | Adres nadawcy |
| Email admina (`admin_notify_email`) | Główny adres dla notyfikacji administracyjnych, np. rezerwacji |
| Kolor nagłówka (`header_color`) | Kolor paska nagłówka w domyślnym layoucie |
| URL logo (`logo_url`) | Logo w nagłówku |
| Tytuł nagłówka (`header_title`) | Tekst zastępczy, gdy nie ma logo |
| Nagłówek emaila (HTML) (`header_html`) | Pełny własny HTML nagłówka |
| Stopka emaila (HTML) (`footer_text`) | Pełny HTML stopki |

> Jeśli `header_html` jest uzupełnione, nadpisuje prosty układ oparty o logo, kolor i tytuł.

---

## Konfigurowalne adresy odbiorców

Nowości v1.1.0 rozszerzają konfigurację o osobne listy odbiorców dla określonych typów komunikacji:

| Opcja | Zastosowanie |
|-------|--------------|
| `bm_missing_report_emails` | Adresy dla przypomnień o brakujących meldunkach dziennych |
| `bm_report_emails` | Adresy dla cyklicznych raportów stanów osobowych |
| `bm_report_interval` | Interwał raportu `hourly` / `twicedaily` / `daily` |

Adresy są wpisywane jako lista rozdzielona przecinkami.

### Jak działają odbiorcy?

- **Brakujące meldunki**: `Scheduler::send_daily_reminders()` pobiera odbiorców z `bm_missing_report_emails`, a gdy opcja jest pusta – używa `admin_email`.
- **Raport okresowy stanów osobowych**: `Scheduler::send_periodic_staff_report()` pobiera odbiorców z `bm_report_emails`. Pusta wartość wyłącza harmonogram.
- **Rezerwacje**: `ReservationNotifier` korzysta z `admin_notify_email` dla strony administracyjnej oraz z adresów zapisanych przy członkach kadry.

---

## Wysyłka emaila w kodzie

```php
use BaseMgmt\Core\EmailService;

EmailService::send(
    'odbiorca@example.com',
    EmailService::subject('Temat wiadomości'),
    'reservation_created',
    [
        'reservation'   => $reservation,
        'resource_name' => 'Boisko główne',
        'camp_name'     => 'Obóz Harcerzy',
        'is_admin'      => false,
    ]
);
```

### Temat wiadomości

`EmailService::subject()` dodaje prefix oparty o nazwę witryny:

```text
[CampLink] Nowa rezerwacja: Boisko główne – Obóz Harcerzy
```

---

## Szablony email

Za rejestr i zapis odpowiada `EmailTemplateRepository`.

### Zasada renderowania

```text
EmailService::send()
  └─ render($slug, $data)
       ├─ 1. sprawdź override w opcji basemgmt_email_tpl_{slug}
       ├─ 2. jeśli brak override → użyj definicji domyślnej z rejestru
       └─ 3. owiń wynik w templates/email/base.php
```

### Zarejestrowane szablony

| Slug | Zastosowanie |
|------|--------------|
| `reservation_created` | Nowa rezerwacja |
| `reservation_approved` | Rezerwacja zatwierdzona |
| `reservation_rejected` | Rezerwacja odrzucona |
| `reservation_cancelled` | Rezerwacja anulowana |

### Typowe tokeny

| Token | Opis |
|-------|------|
| `{{oboz}}` | Nazwa obozu |
| `{{zasob}}` | Nazwa zasobu |
| `{{data}}` | Data rezerwacji |
| `{{godzina_od}}` / `{{godzina_do}}` | Godziny |
| `{{cel}}` | Cel rezerwacji |
| `{{komentarz}}` | Komentarz administratora |
| `{{nazwa_systemu}}` | Nazwa witryny / systemu |

### Edytor szablonów

WP Admin → **CampLink → Ustawienia → Szablony emaili**

Funkcje edytora:

- CodeMirror dla HTML,
- osobne pole tematu,
- lista dostępnych tokenów,
- status **Własny / Domyślny**,
- przycisk **Przywróć domyślny**.

---

## Powiadomienia systemowe powiązane z cronem

### 1. Przypomnienia o brakujących meldunkach

Hook: `bm_daily_reminders`

`Scheduler::send_daily_reminders()`:

1. pobiera aktywne obozy,
2. sprawdza, które nie wysłały meldunku na dziś,
3. buduje prostą wiadomość tekstową,
4. wysyła ją na adresy z `bm_missing_report_emails`.

### 2. Okresowy raport stanów osobowych

Hook: `bm_periodic_staff_report`

`Scheduler::send_periodic_staff_report()`:

1. pobiera odbiorców z `bm_report_emails`,
2. dla każdego aktywnego obozu pobiera meldunek z bieżącego dnia,
3. buduje zbiorczy raport tekstowy,
4. wysyła go do wszystkich skonfigurowanych adresów.

Jeżeli `bm_report_emails` jest puste, `Scheduler::reschedule_staff_report()` usuwa harmonogram i raport nie jest wysyłany.

---

## Test emaila

WP Admin → **CampLink → Ustawienia → Test emaila**

Test:

- używa aktywnego layoutu email,
- renderuje szablon `reservation_created`,
- pozwala sprawdzić zarówno wygląd HTML, jak i konfigurację serwera pocztowego.

---

## Zachowanie `wp_mail()`

Na czas wysyłki CampLink ustawia:

- `wp_mail_content_type` → `text/html`,
- `wp_mail_from` → skonfigurowany adres,
- `wp_mail_from_name` → skonfigurowaną nazwę.

Po wysyłce filtry są czyszczone, aby nie wpływać na inne wiadomości WordPress.

---

## Dodawanie nowego typu wiadomości

1. Dodaj wpis do rejestru w `EmailTemplateRepository::get_registry()`.
2. Dodaj mapowanie tokenów w metodzie budującej zmienne.
3. Wywołaj `EmailService::send()` z nowym slugiem.
4. Jeśli potrzebujesz logiki warunkowej, użyj `templates/email/base.php` jako layoutu i przygotuj własny domyślny HTML.

---

## Powiązane dokumenty

- [08 – Panel administratora](08-admin-panel.md)
- [11 – Zadania cykliczne (Cron)](11-cron.md)
- [16 – Logi operacji](16-operation-logs.md)
