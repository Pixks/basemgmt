# 16 – Logi operacji

## Cel modułu

CampLink 1.1.0 wprowadza centralny dziennik zdarzeń oparty o:

- tabelę `bm_operation_logs`,
- klasę `BaseMgmt\Core\OperationLogger`,
- ekran administracyjny `LogsPage`.

To warstwa audytowa wtyczki – pomocna przy analizie incydentów, diagnostyce i weryfikacji działań administratorów oraz kadry.

---

## Gdzie znaleźć logi?

WP Admin → **CampLink → Logi operacji**

Strona jest dostępna dla użytkowników z uprawnieniem `manage_options`.

---

## Struktura danych

| Kolumna | Opis |
|---------|------|
| `user_id` | WordPress user ID wykonującego akcję |
| `staff_id` | ID członka kadry, jeśli zdarzenie dotyczy frontendu |
| `action` | Typ akcji |
| `object_type` | Typ obiektu, np. `staff`, `submission`, `plan_template` |
| `object_id` | ID obiektu |
| `details` | Szczegóły tekstowe lub JSON |
| `ip_address` | Adres IP |
| `created_at` | Czas zdarzenia |

---

## Najważniejsze akcje

`OperationLogger` definiuje stałe `ACTION_*`. W praktyce logowane są m.in.:

### Bezpieczeństwo i dostęp

- `login_success`
- `login_failed`
- `login_locked`
- `logout`
- `unlock_staff`

### Dane podstawowe

- `camp_created`
- `camp_updated`
- `camp_deleted`
- `staff_created`
- `staff_updated`
- `staff_deleted`

### Plan dnia i jadłospis

- `plan_created`
- `plan_updated`
- `plan_deleted`
- `plan_item_saved`
- `plan_item_deleted`
- `template_created`
- `template_updated`
- `template_deleted`
- `template_applied`
- `meal_created`
- `meal_updated`
- `meal_deleted`
- `meal_item_saved`
- `meal_item_deleted`

### Komunikacja i zgłoszenia

- `thread_created`
- `message_sent`
- `form_saved`
- `submission_updated`

### Konfiguracja

- `settings_saved`

---

## Jak działa zapis?

W kodzie wywołuje się:

```php
OperationLogger::log(
    'thread_created',
    'submission',
    $submission_id,
    'thread_id=123',
    null
);
```

Metoda automatycznie zapisuje:

- aktualnego użytkownika WordPress,
- przekazany `staff_id` (jeśli dotyczy),
- adres IP z `$_SERVER`,
- szczegóły tekstowe lub zserializowany JSON.

---

## Gdzie logi są używane w praktyce?

### Logowanie kadry

`FrontendAuth` zapisuje:

- udane logowanie,
- nieudaną próbę logowania.

### Odblokowanie konta

`StaffPage::handle_unlock()` zapisuje odblokowanie konta kadry z adnotacją, że wymagany jest reset kodu.

### Tworzenie wątku ze zgłoszenia

`FormsPage::handle_create_thread_from_submission()`:

1. tworzy rekord w `bm_conv_threads`,
2. dodaje pierwszą wiadomość systemową do `bm_conv_messages`,
3. zapisuje wpis `thread_created` w logach.

### Szablony planów dnia

`PlanTemplatesPage` loguje:

- utworzenie,
- aktualizację,
- usunięcie,
- zastosowanie szablonu do planu dnia.

---

## Filtrowanie i przeglądanie

Ekran logów umożliwia:

- filtrowanie po `action`,
- filtrowanie po zakresie dat,
- paginację wyników,
- pobranie listy unikalnych akcji z `OperationLogger::get_action_types()`.

To szczególnie przydatne przy sprawdzaniu:

- serii błędnych logowań,
- działań na konkretnym obiekcie,
- operacji wykonanych w danym dniu.

---

## Retencja i czyszczenie

Logi można usuwać z panelu akcją czyszczenia starszych wpisów.

Mechanizm:

```php
OperationLogger::delete_older_than_days($days);
```

Usuwa wpisy starsze niż wskazana liczba dni. Domyślny scenariusz operacyjny to okresowe porządkowanie np. powyżej 90 dni.

---

## Ograniczenia

- Logi dotyczą działań wewnątrz wtyczki, nie całego WordPressa.
- Nie zastępują logów serwera HTTP ani logów PHP.
- Zakres wpisów zależy od tego, czy dana akcja została opakowana wywołaniem `OperationLogger::log()`.

---

## Dobre praktyki

- Regularnie przeglądaj `login_failed` i `unlock_staff`.
- Po incydencie bezpieczeństwa zestaw logi z `bm_sessions` i logami serwera WWW.
- Przy dodawaniu nowych krytycznych operacji do pluginu od razu dopisuj odpowiedni wpis do `OperationLogger`.

---

## Powiązane dokumenty

- [08 – Panel administratora](08-admin-panel.md)
- [10 – Bezpieczeństwo](10-security.md)
- [12 – Przewodnik dewelopera](12-developer-guide.md)
