# Baza Obozowa – Dokumentacja Pluginu

> Wersja: **1.3.0** | Wymagania: WordPress 6.0+, PHP 8.1+, MySQL InnoDB

## Spis treści

| Dokument | Opis |
|----------|------|
| [01 – Przegląd i instalacja](01-overview-installation.md) | Wymagania, instalacja, pierwsze uruchomienie |
| [02 – Architektura](02-architecture.md) | Struktura plików, PSR-4, wzorce, rozszerzalność |
| [03 – System dostępu frontendowego](03-frontend-access.md) | Logowanie kadry, sesje, rate limiting |
| [04 – Moduły](04-modules.md) | Obozy, Kadra, Ogłoszenia, Meldunki, Pogoda, Plan dnia, Rezerwacje |
| [05 – REST API](05-rest-api.md) | Pełna dokumentacja endpointów |
| [06 – Schemat bazy danych](06-database-schema.md) | Wszystkie tabele, kolumny, indeksy |
| [07 – System email](07-email-system.md) | EmailService, szablony, konfiguracja |
| [08 – Panel administratora](08-admin-panel.md) | Przewodnik po panelu WP Admin |
| [09 – Panel frontendowy](09-frontend-panel.md) | Shortcody, Alpine.js, Breakdance |
| [10 – Bezpieczeństwo](10-security.md) | Polityki, mechanizmy ochrony |
| [11 – Zadania cykliczne (Cron)](11-cron.md) | WP-Cron, harmonogram, callbacki |
| [12 – Przewodnik dewelopera](12-developer-guide.md) | Dodawanie modułów, hoooki, konwencje |

---

## Szybki start

```bash
# 1. Skopiuj plugin do katalogu wp-content/plugins/
cp -r basemgmt /var/www/html/wp-content/plugins/

# 2. Aktywuj przez WP Admin → Wtyczki
# lub
wp plugin activate basemgmt

# 3. Przejdź do Baza Obozowa → Dashboard
```

## Kluczowe koncepcje

- **Dwa poziomy dostępu**: administratorzy WP (konta WP) i kadra obozów (własny system).
- **Kadra nie posiada kont WordPress** – dostęp przez dedykowany panel frontendowy.
- **Modularność**: każdy moduł w osobnym namespace pod `src/Modules/`.
- **Bezpieczeństwo**: haszowane kody, rate limiting, sesje z TTL, SELECT FOR UPDATE.

---

*Dokumentacja wygenerowana dla pluginu Baza Obozowa v1.3.0.*
