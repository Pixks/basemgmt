# CampLink – Dokumentacja pluginu

> Wersja: **1.1.2** | Wymagania: WordPress 6.0+, PHP 8.1+, MySQL InnoDB

## Spis treści

| Dokument | Opis |
|----------|------|
| [01 – Przegląd i instalacja](01-overview-installation.md) | Wymagania, instalacja, pierwsze uruchomienie i nowości v1.1.2 |
| [02 – Architektura](02-architecture.md) | Struktura plików, PSR-4, wzorce, rozszerzalność |
| [03 – System dostępu frontendowego](03-frontend-access.md) | Logowanie kadry, sesje, rate limiting |
| [04 – Moduły](04-modules.md) | Obozy, Kadra, Ogłoszenia, Meldunki, Pogoda, Plan dnia, Rezerwacje, Jadłospis, Komunikacja, Pomoc, Formularze i Zgłoszenia |
| [05 – REST API](05-rest-api.md) | Pełna dokumentacja endpointów |
| [06 – Schemat bazy danych](06-database-schema.md) | Wszystkie tabele, kolumny, indeksy |
| [07 – System email](07-email-system.md) | EmailService, szablony HTML, konfiguracja nagłówka/stopki |
| [08 – Panel administratora](08-admin-panel.md) | Przewodnik po panelu WP Admin |
| [09 – Panel frontendowy](09-frontend-panel.md) | Alpine.js, Breakdance, bmConfig, komponenty |
| [10 – Bezpieczeństwo](10-security.md) | Polityki, mechanizmy ochrony |
| [11 – Zadania cykliczne (Cron)](11-cron.md) | WP-Cron, harmonogram, callbacki |
| [12 – Przewodnik dewelopera](12-developer-guide.md) | Dodawanie modułów, hooki, konwencje |
| [13 – Breakdance Custom Elements](13-breakdance-elements.md) | Gotowe bloki HTML/Alpine dla każdego elementu UI (styl neutralny) |
| [14 – Breakdance Elements – styl ZHP](14-breakdance-elements-zhp.md) | **Gotowe bloki ze stylowaniem ZHP** (paleta zhp.pl, CSS design system) |
| [15 – Gotowy panel – jeden blok](15-panel-full-breakdance.md) | **Kompletna strona panelu** – jeden blok do wklejenia w Breakdance |
| [16 – Logi operacji](16-operation-logs.md) | Dziennik zdarzeń, filtrowanie, retencja i zastosowania audytowe |

---

## Szybki start

```bash
# 1. Skopiuj plugin do katalogu wp-content/plugins/
cp -r basemgmt /var/www/html/wp-content/plugins/

# 2. Aktywuj przez WP Admin → Wtyczki
# lub
wp plugin activate basemgmt

# 3. Przejdź do CampLink → Dashboard
```

## Kluczowe koncepcje

- **Dwa poziomy dostępu**: administratorzy WP (konta WP) i kadra obozów (własny system).
- **Kadra nie posiada kont WordPress** – dostęp przez dedykowany panel frontendowy.
- **6-cyfrowy PIN** – kody bezpieczeństwa kadry to dokładnie 6 cyfr, haszowane bcrypt.
- **Modularność**: każdy moduł w osobnym namespace pod `src/Modules/`.
- **Breakdance-ready**: `bmConfig` i Alpine.js ładowane globalnie – nie wymaga shortcode. Każdy element UI to osobny blok HTML gotowy do wklejenia w Breakdance Studio (→ [doc 13](13-breakdance-elements.md)).
- **Edytowalne szablony email**: każdy email konfigurowalny przez edytor HTML (CodeMirror) z tokenami `{{zmiennych}}`. Nagłówek i stopka emaila również edytowalne jako pełny HTML.
- **Bezpieczeństwo**: haszowane 6-cyfrowe PIN-y, blokada czasowa i trwała kadry, sesje z TTL, wszystkie `/panel/*` endpointy chronione sesją.
- **Snapshot zgłoszeń**: dane formularzy utrwalane w momencie wysłania, odporne na późniejsze zmiany.
- **Komunikacja dwukierunkowa**: admin może inicjować wątki do obozów; kadra odpowiada przez panel.
- **Nowości v1.1.2**: dopracowany raport zbiorczy meldunków, poprawione szablony email dla przypomnień i raportów okresowych, lepszy widok wydruku/PDF oraz zachowanie tytułu i zasięgu przy tworzeniu planów z dnia źródłowego.
- **Naprawy tabel inline**: jadłospis, pomoc i formularze wykrywają brak tabel i oferują przycisk naprawy bez reaktywacji pluginu.

---

*Dokumentacja przygotowana dla pluginu CampLink v1.1.2.*
