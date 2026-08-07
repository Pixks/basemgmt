# Baza Obozowa

**Modularny system zarządzania ośrodkiem obozowym dla WordPress**

---

## Opis

Baza Obozowa to produkcyjna wtyczka WordPress do kompleksowego zarządzania ośrodkiem obozowym. Umożliwia administratorom zarządzanie obozami, kadrą i zasobami ośrodka, a członkom kadry – dostęp do dedykowanego panelu frontendowego **bez konieczności posiadania kont WordPress**.

## Kluczowe funkcje

- 🏕️ **Obozy** – ewidencja, daty, statusy
- 👥 **Kadra** – przypisanie do obozów, bezpieczne kody dostępu (bcrypt), rate limiting
- 📢 **Ogłoszenia** – globalne i skierowane do wybranych obozów, workflow zatwierdzania
- 📊 **Meldunki dzienne** – liczniki uczestników, kadry i pracowników
- ⛅ **Pogoda** – Open-Meteo + automatyczna synchronizacja ostrzeżeń IMGW
- 📅 **Plan dnia** – tworzenie planów, drag & drop, historia zmian, kopiowanie
- 🔖 **Rezerwacje** – zasoby ośrodka, kalendarz, ochrona przed double-booking (SELECT FOR UPDATE)
- 📧 **Email** – konfigurowalny system szablonów HTML z brandingiem

## Wymagania

- WordPress **6.0+**
- PHP **8.1+**
- MySQL **5.7+** lub MariaDB **10.3+** z silnikiem **InnoDB**

## Instalacja

1. Skopiuj folder `basemgmt/` do `wp-content/plugins/`
2. Aktywuj przez WP Admin → Wtyczki
3. Przejdź do **Baza Obozowa → Dashboard**

## Dokumentacja

Pełna dokumentacja w katalogu [`docs/`](docs/README.md):

| Dokument | |
|----------|-|
| [Przegląd i instalacja](docs/01-overview-installation.md) | Wymagania, instalacja, pierwsze kroki |
| [Architektura](docs/02-architecture.md) | Struktura plików, wzorce, rozszerzalność |
| [System dostępu frontendowego](docs/03-frontend-access.md) | Logowanie kadry, sesje, bezpieczeństwo |
| [Moduły](docs/04-modules.md) | Wszystkie moduły z opisem danych i API |
| [REST API](docs/05-rest-api.md) | Dokumentacja endpointów |
| [Schemat bazy danych](docs/06-database-schema.md) | Tabele, kolumny, indeksy, przykładowe zapytania |
| [System email](docs/07-email-system.md) | EmailService, szablony, konfiguracja |
| [Panel administratora](docs/08-admin-panel.md) | Przewodnik po WP Admin |
| [Panel frontendowy](docs/09-frontend-panel.md) | Shortcode, Alpine.js, Breakdance |
| [Bezpieczeństwo](docs/10-security.md) | Mechanizmy ochrony, rekomendacje |
| [Cron](docs/11-cron.md) | Zadania cykliczne, harmonogram |
| [Przewodnik dewelopera](docs/12-developer-guide.md) | Dodawanie modułów, hooki, konwencje |

## Szybki start dla dewelopera

```php
// Nowe repozytorium modułu
namespace BaseMgmt\Modules\MojModul;
use BaseMgmt\Database\Schema;

final class MojModulRepository {
    public static function get_all(array $filters = []): array { /* ... */ }
    public static function create(array $data): int { /* ... */ }
}
```

Szczegóły: [12 – Przewodnik dewelopera](docs/12-developer-guide.md)

## Licencja

GPL-2.0-or-later

---

*Wersja 1.1.2 | PHP 8.1+ | WordPress 6.0+*
