# CampLink

**Modularny system zarządzania ośrodkiem obozowym dla WordPress**

[![Version](https://img.shields.io/badge/wersja-2.0.0--beta-blue)](CHANGELOG.md)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb3)](https://php.net)
[![License](https://img.shields.io/badge/licencja-GPL--2.0--or--later-green)](LICENSE)

---

## Opis

**CampLink** to produkcyjna wtyczka WordPress do kompleksowego zarządzania ośrodkiem obozowym. Umożliwia administratorom zarządzanie obozami, kadrą i zasobami ośrodka, a członkom kadry – dostęp do dedykowanego panelu frontendowego **bez konieczności posiadania kont WordPress**.

---

## Kluczowe funkcje

| Moduł | Opis |
|-------|------|
| 🏕️ **Obozy** | Ewidencja pobytów + pełny workflow: 6 faz biznesowych, automatyczne taski, checklista, automatyzacje, centrum pracy |
| 👥 **Kadra** | Przypisanie do obozów, bezpieczne kody dostępu (bcrypt), rate limiting, blokada kont |
| 📢 **Ogłoszenia** | Globalne i skierowane do wybranych obozów, workflow zatwierdzania |
| 📊 **Meldunki dzienne** | Liczniki uczestników, kadry i pracowników; raport zbiorczy |
| ⛅ **Pogoda** | Open-Meteo + automatyczna synchronizacja ostrzeżeń IMGW |
| 📅 **Plan dnia** | Tworzenie planów, drag & drop, szablony, masowe generowanie, historia zmian |
| 🔖 **Rezerwacje** | Zasoby ośrodka, kalendarz FullCalendar, ochrona przed double-booking |
| 🍽️ **Jadłospis** | Jadłospisy z szablonami, pozycjami, dietami i miejscami wydawania |
| 💬 **Komunikacja** | Wątki rozmów między administracją a kadrą obozową |
| 📋 **Formularze** | Konfigurowalne formularze zgłoszeń z przeglądem odpowiedzi |
| 📧 **Email** | Konfigurowalny system szablonów HTML z brandingiem i edytorem CodeMirror |
| 📄 **PDF / Wydruk** | Eksport planu dnia, jadłospisu i raportów do widoku druku |
| 🔐 **Logi operacji** | Audyt logowań, błędów, odblokowań i zmian |

---

## Wymagania

| Składnik | Minimum |
|----------|---------|
| WordPress | **6.0+** |
| PHP | **8.1+** |
| MySQL | **5.7+** lub MariaDB **10.3+** (InnoDB) |

---

## Instalacja

1. Skopiuj folder `basemgmt/` do `wp-content/plugins/`
2. Aktywuj przez **WP Admin → Wtyczki**
3. Przejdź do **CampLink → Dashboard**

---

## Dokumentacja

Pełna dokumentacja w katalogu [`docs/`](docs/README.md):

| Dokument | Zawartość |
|----------|-----------|
| [01 – Przegląd i instalacja](docs/01-overview-installation.md) | Wymagania, instalacja, pierwsze kroki |
| [02 – Architektura](docs/02-architecture.md) | Struktura plików, wzorce, rozszerzalność |
| [03 – System dostępu frontendowego](docs/03-frontend-access.md) | Logowanie kadry, sesje, bezpieczeństwo |
| [04 – Moduły](docs/04-modules.md) | Wszystkie moduły z opisem danych i API |
| [05 – REST API](docs/05-rest-api.md) | Dokumentacja endpointów |
| [06 – Schemat bazy danych](docs/06-database-schema.md) | Tabele, kolumny, indeksy |
| [07 – System email](docs/07-email-system.md) | EmailService, szablony, konfiguracja |
| [08 – Panel administratora](docs/08-admin-panel.md) | Przewodnik po WP Admin |
| [09 – Panel frontendowy](docs/09-frontend-panel.md) | Shortcode, Alpine.js, Breakdance |
| [10 – Bezpieczeństwo](docs/10-security.md) | Mechanizmy ochrony, rekomendacje |
| [11 – Cron](docs/11-cron.md) | Zadania cykliczne, harmonogram |
| [12 – Przewodnik dewelopera](docs/12-developer-guide.md) | Dodawanie modułów, hooki, konwencje |

---

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

---

## Changelog

Pełna historia zmian: [CHANGELOG.md](CHANGELOG.md)

---

## Licencja

GPL-2.0-or-later

---

*CampLink v2.0.0-beta | PHP 8.1+ | WordPress 6.0+*
