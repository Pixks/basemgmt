# 17 – Wdrożenie i dane startowe

## Cel dokumentu

Jednolita instrukcja uruchomienia CampLink w nowej instancji WordPress oraz minimalny zakres danych startowych potrzebnych do rozpoczęcia pracy.

---

## 1) Checklista wdrożenia (techniczna)

1. Zweryfikuj wymagania środowiska:
   - WordPress 6.0+
   - PHP 8.1+
   - MySQL/MariaDB na InnoDB
2. Wykonaj kopię zapasową bazy i plików WordPress.
3. Skopiuj katalog `basemgmt` do `wp-content/plugins/`.
4. Aktywuj wtyczkę CampLink w WP Admin.
5. Potwierdź dostępność panelu: **CampLink → Dashboard**.
6. Sprawdź, czy endpoint REST działa: `GET /wp-json/bm/v1/auth/status`.
7. Sprawdź, czy zadania WP-Cron są zaplanowane (sekcja cron w dokumentacji).
8. Ustaw role i dostęp administratorów do capability `manage_basemgmt`.

---

## 2) Checklista konfiguracji funkcjonalnej (po aktywacji)

1. **Ustawienia globalne**
   - CampLink → Ustawienia
   - konfiguracja emaili (nadawca, test wysyłki, odbiorcy raportów)
   - konfiguracja czasu blokady kont kadry
2. **Strony frontendowe panelu kadry**
   - utwórz stronę logowania (shortcode `bm_panel_login`)
   - utwórz stronę panelu (sekcje `bm_panel_*` + `bm_panel_session_guard`)
3. **Pogoda (opcjonalnie)**
   - konfiguracja lokalizacji i integracji IMGW
4. **Logi operacji**
   - ustaw zasady retencji i okresowe czyszczenie

---

## 3) Dane startowe – minimum do uruchomienia pracy

| Obszar | Minimalny zestaw danych | Gdzie uzupełnić |
|---|---|---|
| Obozy | nazwa, data od/do, status aktywny | CampLink → Obozy |
| Kadra obozowa | imię i nazwisko, obóz, rola, email, 6-cyfrowy kod bezpieczeństwa | CampLink → Kadra |
| Zasoby rezerwacyjne | nazwa zasobu, dostępność godzinowa, zasady rezerwacji | CampLink → Rezerwacje |
| Komunikaty operacyjne | przynajmniej 1 ogłoszenie startowe dla kadry | CampLink → Ogłoszenia |
| Plan dnia | minimum 1 plan dzienny dla aktywnego obozu | CampLink → Plan dnia |
| Jadłospis | minimum 1 dzień jadłospisu lub szablon | CampLink → Jadłospis |
| Diety i miejsca wydawania | podstawowa lista diet i lokalizacji wydawania | CampLink → Jadłospis → Opcje |
| Pomoc wewnętrzna | minimum 3 artykuły: kontakt, procedura alarmowa, zasady zgłoszeń | CampLink → Pomoc |
| Formularze i zgłoszenia | minimum 1 formularz operacyjny przypisany do obozu | CampLink → Formularze |

---

## 4) Dane startowe – zalecane rozszerzenie (operacyjne)

1. **Organizacja → Dokumenty i szablony dokumentów**  
   Wgraj wzory umów, regulaminy i dokumenty obozowe do użycia przez kadrę.
2. **Organizacja → Deklaracje**  
   Przygotuj szablony deklaracji i wymagane dokumenty pobytu.
3. **Organizacja → Finanse**  
   Uzupełnij pakiety i linie rozliczeniowe wymagane do późniejszego rozliczenia obozów.
4. **Organizacja → Noclegi / Diety**  
   Zdefiniuj słowniki operacyjne używane przy deklaracjach i obsłudze pobytów.
5. **Szablony planów i jadłospisów**  
   Przygotuj wzorce, aby przyspieszyć codzienną pracę administracji.

---

## 5) Test gotowości przed startem produkcyjnym

1. Logowanie kadry do panelu działa (poprawny kod, błędny kod, blokada po próbach).
2. Kadra widzi swój obóz i nie ma dostępu do danych innych obozów.
3. Działa pełny obieg podstawowy:
   - publikacja ogłoszenia,
   - złożenie meldunku dziennego,
   - utworzenie rezerwacji,
   - wysłanie formularza/zgłoszenia.
4. Działa wysyłka testowego emaila.
5. W logach operacji pojawiają się kluczowe zdarzenia.
6. Potwierdzona procedura backupu i odtworzenia.

---

## 6) Lista punktów do opracowania instrukcji użytkownika

### A. Komendant (zarządzanie i nadzór)

1. Model pracy w CampLink i odpowiedzialności ról.
2. Tworzenie i prowadzenie obozu przez pełny cykl życia.
3. Nadzór nad kadrą: dostęp, blokady, reset kodów.
4. Zarządzanie komunikacją i eskalacją zgłoszeń.
5. Nadzór nad meldunkami dziennymi i raportami.
6. Zarządzanie ryzykiem operacyjnym i dokumentacją obozu.
7. Rezerwacje zasobów i zasady akceptacji/odrzucania.
8. Wykorzystanie logów operacji i audytu.
9. Standard zamknięcia obozu i przygotowania rozliczenia.

### B. Kadra bazy wypoczynkowej (obsługa operacyjna)

1. Logowanie do panelu i zasady bezpieczeństwa kodu 6-cyfrowego.
2. Codzienny rytm pracy: meldunek, ogłoszenia, plan dnia, jadłospis.
3. Obsługa rezerwacji zasobów i anulacji.
4. Tworzenie i obsługa zgłoszeń/formularzy.
5. Korzystanie z modułu pomocy i procedur alarmowych.
6. Zasady komunikacji z administracją i priorytety spraw.
7. Najczęstsze błędy użytkownika i szybkie ścieżki naprawcze.
8. Procedura działania przy niedostępności systemu.
