# 13 – Breakdance Custom Elements – gotowe bloki kodu

Każdy blok to gotowy HTML który wklejasz w **Breakdance Studio → Custom Element** lub **Code Block**.
Alternatywnie te sekcje są dostępne jako shortcode `bm_panel_*` (patrz `docs/09-frontend-panel.md`).

> **Zasada widoczności**: większość elementów panelu powinna być widoczna tylko gdy użytkownik jest zalogowany.  
> Użyj warunku `Alpine.store('bm').authenticated === true` na wrapperze lub chroń widoczność przez `x-show`.

---

## Spis elementów

| Nr | Element | Komponent Alpine |
|----|---------|-----------------|
| 01 | [Ekran logowania](#01-ekran-logowania) | `bmLogin` |
| 02 | [Nagłówek panelu / informacje o obozie](#02-nagłówek-panelu--informacje-o-obozie) | `bmCamp` |
| 03 | [Przycisk wylogowania](#03-przycisk-wylogowania) | `bmLogout` |
| 04 | [Ogłoszenia](#04-ogłoszenia) | `bmAnnouncements` |
| 05 | [Formularz nowego ogłoszenia](#05-formularz-nowego-ogłoszenia) | `bmAnnForm` |
| 06 | [Meldunek dzienny](#06-meldunek-dzienny) | `bmReports` |
| 07 | [Pogoda i ostrzeżenia](#07-pogoda-i-ostrzeżenia) | `bmWeather` |
| 08 | [Plan dnia](#08-plan-dnia) | `bmSchedule` |
| 09 | [Rezerwacje](#09-rezerwacje) | `bmReservations` |
| 10 | [Jadłospis dzienny](#10-jadłospis-dzienny) | `bmMenu` |
| 11 | [Jadłospis tygodniowy](#11-jadłospis-tygodniowy) | `bmMenu` |
| 12 | [Komunikacja – lista wątków](#12-komunikacja--lista-wątków) | `bmConversations` |
| 13 | [Komunikacja – nowy wątek](#13-komunikacja--nowy-wątek) | `bmConversations` |
| 14 | [Komunikacja – widok wątku](#14-komunikacja--widok-wątku) | `bmConversations` |
| 15 | [Baza pomocy – lista](#15-baza-pomocy--lista) | `bmHelp` |
| 16 | [Baza pomocy – podgląd artykułu](#16-baza-pomocy--podgląd-artykułu) | `bmHelp` |
| 17 | [Formularze – lista](#17-formularze--lista) | `bmForms` |
| 18 | [Formularz – wypełnianie i wysyłanie](#18-formularz--wypełnianie-i-wysyłanie) | `bmForms` |
| 19 | [Zgłoszenia – lista](#19-zgłoszenia--lista) | `bmSubmissions` |
| 20 | [Zgłoszenia – podgląd szczegółów](#20-zgłoszenia--podgląd-szczegółów) | `bmSubmissions` |
| 21 | [Wrapper ochrony sesji](#21-wrapper-ochrony-sesji) | store `bm` |
| 22 | [Licznik nieprzeczytanych wiadomości](#22-licznik-nieprzeczytanych-wiadomości) | store `bm` |

---

## 01 – Ekran logowania

**Komponent**: `bmLogin`  
Trzy-krokowy wybór: obóz → osoba kadry → kod PIN.

```html
<div x-data="bmLogin()" x-init="init()" style="max-width:420px;margin:0 auto;">

  <!-- Krok 1: wybór obozu -->
  <div>
    <label>Wybierz obóz</label>
    <select x-model="campId" @change="loadStaff()">
      <option value="">— wybierz obóz —</option>
      <template x-for="c in camps" :key="c.id">
        <option :value="c.id" x-text="c.name"></option>
      </template>
    </select>
  </div>

  <!-- Krok 2: wybór osoby (pojawia się po wyborze obozu) -->
  <div x-show="campId && staffList.length">
    <label>Wybierz siebie</label>
    <select x-model="staffId">
      <option value="">— wybierz osobę —</option>
      <template x-for="s in staffList" :key="s.id">
        <option :value="s.id" x-text="s.display_name + ' (' + s.role + ')'"></option>
      </template>
    </select>
  </div>

  <!-- Krok 3: kod PIN -->
  <div x-show="staffId">
    <label>Kod bezpieczeństwa (6 cyfr)</label>
    <input
      type="password"
      x-model="code"
      inputmode="numeric"
      maxlength="6"
      placeholder="●●●●●●"
      @keydown.enter="submit()"
    >
  </div>

  <!-- Komunikat błędu -->
  <p x-show="error" x-text="error" style="color:#c0392b;margin-top:8px;"></p>

  <!-- Przycisk logowania -->
  <button
    @click="submit()"
    :disabled="loading || !campId || !staffId || !code"
    x-text="loading ? 'Logowanie…' : 'Zaloguj się'"
    style="margin-top:12px;"
  ></button>

</div>
```

**Zdarzenie po zalogowaniu**: `window.dispatchEvent(new CustomEvent('bm:login'))` – możesz podpiąć ukrywanie/pokazywanie sekcji.

---

## 02 – Nagłówek panelu / informacje o obozie

**Komponent**: `bmCamp`  
Wyświetla nazwę obozu, daty, status meldunku i ostatnie liczby.

```html
<div x-data="bmCamp()" x-init="init()">

  <!-- Loader -->
  <p x-show="!camp" style="color:#888;">Ładowanie danych obozu…</p>

  <!-- Dane obozu -->
  <template x-if="camp">
    <div>
      <h2 x-text="camp.name"></h2>
      <p>
        <span x-text="camp.start_date"></span>
        –
        <span x-text="camp.end_date"></span>
      </p>

      <!-- Status meldunku -->
      <p x-show="submittedToday" style="color:#27ae60;">✓ Meldunek dzienny złożony</p>
      <p x-show="!submittedToday" style="color:#e67e22;">⚠ Meldunek dzienny nie złożony</p>

      <!-- Ostatnie liczby -->
      <template x-if="latestCount">
        <table>
          <tr>
            <td>Uczestnicy</td>
            <td><strong x-text="latestCount.participants ?? 0"></strong></td>
          </tr>
          <tr>
            <td>Kadra</td>
            <td><strong x-text="latestCount.staff ?? 0"></strong></td>
          </tr>
          <tr>
            <td>Pracownicy</td>
            <td><strong x-text="latestCount.workers ?? 0"></strong></td>
          </tr>
          <tr>
            <td><strong>Łącznie</strong></td>
            <td><strong x-text="latestCount.total ?? 0"></strong></td>
          </tr>
        </table>
      </template>
    </div>
  </template>

</div>
```

---

## 03 – Przycisk wylogowania

**Komponent**: `bmLogout`

```html
<button x-data="bmLogout()" @click="logout()">
  Wyloguj się
</button>
```

Lub z imieniem zalogowanego:

```html
<div x-data="bmLogout()" style="display:flex;align-items:center;gap:12px;">
  <span x-text="'Zalogowany: ' + $store.bm.displayName"></span>
  <button @click="logout()">Wyloguj</button>
</div>
```

---

## 04 – Ogłoszenia

**Komponent**: `bmAnnouncements`  
Automatycznie pobiera ogłoszenia po załadowaniu (`Alpine.store('bm').init()` robi to przy starcie).

```html
<div x-data="bmAnnouncements()">

  <!-- Przycisk odświeżenia -->
  <button @click="refresh()" style="margin-bottom:12px;">↻ Odśwież</button>

  <!-- Pilne ogłoszenia -->
  <template x-for="ann in active.filter(a => a.is_urgent)" :key="ann.id">
    <div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:12px;margin-bottom:8px;">
      <strong>🚨 <span x-text="ann.title"></span></strong>
      <div x-html="ann.content" style="margin-top:6px;"></div>
      <small x-text="'Ważne do: ' + (ann.valid_until || '—')"></small>
      <a :href="ann.attachment_url" x-show="ann.attachment_url" target="_blank" style="display:block;margin-top:4px;">📎 Załącznik</a>
    </div>
  </template>

  <!-- Zwykłe ogłoszenia -->
  <template x-for="ann in active.filter(a => !a.is_urgent)" :key="ann.id">
    <div style="border:1px solid #e5e7eb;padding:12px;margin-bottom:8px;border-radius:4px;">
      <strong x-text="ann.title"></strong>
      <div x-html="ann.content" style="margin-top:6px;color:#555;"></div>
      <small x-text="'Do: ' + (ann.valid_until || '—')" style="color:#9ca3af;"></small>
      <a :href="ann.attachment_url" x-show="ann.attachment_url" target="_blank" style="display:block;margin-top:4px;">📎 Załącznik</a>
    </div>
  </template>

  <!-- Brak ogłoszeń -->
  <p x-show="!active.length" style="color:#9ca3af;">Brak aktywnych ogłoszeń.</p>

</div>
```

---

## 05 – Formularz nowego ogłoszenia

**Komponent**: `bmAnnForm`  
Kadra może zgłosić ogłoszenie do zatwierdzenia przez admina.

```html
<form x-data="bmAnnForm()" @submit.prevent="submit()">

  <div>
    <label>Tytuł *</label>
    <input type="text" x-model="title" required placeholder="Krótki tytuł ogłoszenia">
  </div>

  <div>
    <label>Treść</label>
    <textarea x-model="content" rows="4" placeholder="Szczegółowy opis…"></textarea>
  </div>

  <div>
    <label>Ważne od *</label>
    <input type="date" x-model="valid_from" required>
  </div>

  <div>
    <label>Ważne do *</label>
    <input type="date" x-model="valid_until" required>
  </div>

  <div>
    <label>URL załącznika (opcjonalnie)</label>
    <input type="url" x-model="attachment_url" placeholder="https://…">
  </div>

  <p x-show="success" x-text="success" style="color:#27ae60;margin-top:8px;"></p>
  <p x-show="error"   x-text="error"   style="color:#c0392b;margin-top:8px;"></p>

  <button type="submit" :disabled="loading" x-text="loading ? 'Wysyłanie…' : 'Wyślij do zatwierdzenia'"></button>

</form>
```

---

## 06 – Meldunek dzienny

**Komponent**: `bmReports`  
Pokaż formularz meldunku z historią. Zablokowany po wysłaniu tego dnia.

```html
<div x-data="bmReports()" x-init="init()">

  <!-- Status -->
  <div style="margin-bottom:16px;">
    <strong>Status: </strong>
    <span x-text="statusLabel"
      :style="today?.status === 'submitted' ? 'color:#27ae60;' : today?.status === 'draft' ? 'color:#e67e22;' : 'color:#9ca3af;'">
    </span>
  </div>

  <!-- Formularz -->
  <fieldset :disabled="isSubmitted">
    <div>
      <label>Uczestnicy</label>
      <input type="number" x-model.number="form.participants" min="0">
    </div>
    <div>
      <label>Kadra</label>
      <input type="number" x-model.number="form.staff" min="0">
    </div>
    <div>
      <label>Pracownicy</label>
      <input type="number" x-model.number="form.workers" min="0">
    </div>
    <div>
      <label>Łącznie: <strong x-text="total"></strong></label>
    </div>
    <div>
      <label>Uwagi</label>
      <textarea x-model="form.notes" rows="3" placeholder="opcjonalnie"></textarea>
    </div>
  </fieldset>

  <p x-show="isSubmitted" style="color:#27ae60;">✓ Meldunek wysłany. Nie można modyfikować.</p>
  <p x-show="success" x-text="success" style="color:#27ae60;"></p>
  <p x-show="error"   x-text="error"   style="color:#c0392b;"></p>

  <div style="display:flex;gap:8px;margin-top:12px;">
    <button @click="saveDraft()" :disabled="loading || isSubmitted">
      <span x-text="loading ? '…' : 'Zapisz roboczo'"></span>
    </button>
    <button @click="submit()" :disabled="loading || isSubmitted" style="font-weight:bold;">
      <span x-text="loading ? '…' : 'Wyślij meldunek'"></span>
    </button>
  </div>

  <!-- Historia (ostatnie 7 dni) -->
  <details style="margin-top:24px;">
    <summary style="cursor:pointer;font-weight:600;">Historia meldunków</summary>
    <table style="width:100%;margin-top:8px;border-collapse:collapse;">
      <thead>
        <tr style="background:#f3f4f6;">
          <th style="text-align:left;padding:6px;">Data</th>
          <th>Ucz.</th><th>Kadra</th><th>Prac.</th><th>Status</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="r in history.slice(0,7)" :key="r.id">
          <tr style="border-bottom:1px solid #e5e7eb;">
            <td style="padding:6px;" x-text="r.count_date"></td>
            <td style="text-align:center;" x-text="r.participants"></td>
            <td style="text-align:center;" x-text="r.staff"></td>
            <td style="text-align:center;" x-text="r.workers"></td>
            <td style="text-align:center;" x-text="r.status === 'submitted' ? '✓' : r.status === 'draft' ? '⏳' : '—'"></td>
          </tr>
        </template>
      </tbody>
    </table>
  </details>

</div>
```

---

## 07 – Pogoda i ostrzeżenia

**Komponent**: `bmWeather`

```html
<div x-data="bmWeather()" x-init="init()">

  <p x-show="loading" style="color:#9ca3af;">Ładowanie pogody…</p>
  <p x-show="error"   x-text="error" style="color:#c0392b;"></p>
  <p x-show="!configured && !loading" style="color:#e67e22;">⚠ Lokalizacja pogody nie skonfigurowana w ustawieniach.</p>

  <!-- Aktualna pogoda -->
  <template x-if="current">
    <div style="display:flex;align-items:center;gap:16px;padding:16px;background:#eff6ff;border-radius:8px;margin-bottom:12px;">
      <span x-text="current.icon" style="font-size:2.5rem;"></span>
      <div>
        <div style="font-size:2rem;font-weight:700;" x-text="current.temperature + '°C'"></div>
        <div x-text="current.label" style="color:#374151;"></div>
        <div style="font-size:0.85rem;color:#6b7280;margin-top:4px;">
          💨 <span x-text="current.windspeed + ' km/h'"></span>
          &nbsp;|&nbsp;
          💧 <span x-text="current.humidity + '%'"></span>
        </div>
      </div>
    </div>
  </template>

  <!-- Prognoza 5-dniowa -->
  <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:16px;">
    <template x-for="day in forecast" :key="day.date">
      <div style="text-align:center;padding:8px;background:#f9fafb;border-radius:6px;font-size:0.8rem;">
        <div style="color:#6b7280;" x-text="day.date.slice(5)"></div>
        <div x-text="day.icon" style="font-size:1.4rem;"></div>
        <div x-text="day.temp_max + '°'"></div>
        <div style="color:#9ca3af;" x-text="day.temp_min + '°'"></div>
      </div>
    </template>
  </div>

  <!-- Ostrzeżenia IMGW -->
  <template x-for="alert in alerts" :key="alert.id">
    <div :style="alert.is_urgent
        ? 'background:#fee2e2;border-left:4px solid #ef4444;padding:10px;margin-bottom:6px;border-radius:4px;'
        : 'background:#fef3c7;border-left:4px solid #f59e0b;padding:10px;margin-bottom:6px;border-radius:4px;'">
      <strong x-text="(alert.is_urgent ? '🚨 ' : '⚠️ ') + alert.title"></strong>
      <p x-text="alert.message" style="margin:4px 0 0;font-size:0.85rem;"></p>
    </div>
  </template>

</div>
```

---

## 08 – Plan dnia

**Komponent**: `bmSchedule`

```html
<div x-data="bmSchedule()" x-init="init()">

  <!-- Nawigacja daty -->
  <div style="display:flex;gap:8px;align-items:center;margin-bottom:12px;flex-wrap:wrap;">
    <input type="date" x-model="selectedDate" @change="loadSchedule()" style="padding:6px;">
    <!-- Skróty dat z planami -->
    <template x-for="d in availableDates" :key="d">
      <button
        @click="selectDate(d)"
        :style="d === selectedDate ? 'background:#2563eb;color:#fff;border:none;padding:4px 10px;border-radius:4px;' : 'background:#f3f4f6;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;'"
        x-text="d.slice(5)">
      </button>
    </template>
  </div>

  <p x-show="loading" style="color:#9ca3af;">Ładowanie planu…</p>
  <p x-show="!loading && !plans.length" style="color:#9ca3af;">Brak planu na wybrany dzień.</p>

  <template x-for="plan in plans" :key="plan.id">
    <div>
      <h3 x-show="plan.title" x-text="plan.title" style="margin-bottom:8px;"></h3>
      <div style="border-left:3px solid #3b82f6;padding-left:12px;">
        <template x-for="item in plan.items" :key="item.id">
          <div style="margin-bottom:10px;padding:8px;background:#f9fafb;border-radius:4px;"
            :style="item.item_status === 'cancelled' ? 'opacity:0.5;' : ''">

            <!-- Czas -->
            <span style="font-weight:600;color:#2563eb;min-width:110px;display:inline-block;"
              x-text="item.time_from + (item.time_to ? ' – ' + item.time_to : '')">
            </span>

            <!-- Tytuł (przekreślony gdy cancelled) -->
            <span :style="item.item_status === 'cancelled' ? 'text-decoration:line-through;' : ''"
              x-text="item.title">
            </span>

            <!-- Flagi zmian -->
            <span x-show="item.is_new_today"     style="margin-left:6px;color:#16a34a;font-size:0.75rem;">🆕 nowe</span>
            <span x-show="item.is_updated_today" style="margin-left:6px;color:#d97706;font-size:0.75rem;">✏ zmienione</span>
            <span x-show="item.is_mandatory"     style="margin-left:6px;color:#7c3aed;font-size:0.75rem;">⚡ obowiązkowe</span>
            <span x-show="item.item_status === 'cancelled'" style="margin-left:6px;color:#dc2626;font-size:0.75rem;">❌ odwołane</span>

            <!-- Opis -->
            <p x-show="item.description" x-text="item.description"
              style="margin:4px 0 0;font-size:0.85rem;color:#6b7280;"></p>

            <!-- Miejsce -->
            <p x-show="item.location" x-text="'📍 ' + item.location"
              style="margin:2px 0 0;font-size:0.8rem;color:#9ca3af;"></p>
          </div>
        </template>
      </div>
    </div>
  </template>

</div>
```

---

## 09 – Rezerwacje

**Komponent**: `bmReservations`  
Lista zasobów → formularz → moje rezerwacje.

```html
<div x-data="bmReservations()" x-init="init()">

  <p x-show="loading" style="color:#9ca3af;">Ładowanie…</p>
  <p x-show="success" x-text="success" style="color:#27ae60;margin-bottom:8px;"></p>

  <!-- Lista zasobów (gdy brak wybranego) -->
  <template x-if="!selectedResource">
    <div>
      <h3>Dostępne zasoby</h3>
      <template x-for="res in resources" :key="res.id">
        <div style="border:1px solid #e5e7eb;padding:12px;margin-bottom:8px;border-radius:6px;">
          <strong x-text="res.name"></strong>
          <span style="margin-left:8px;font-size:0.8rem;color:#6b7280;"
            x-text="'(' + res.type + ')'"></span>
          <p style="font-size:0.85rem;color:#6b7280;margin:4px 0;"
            x-text="'Dostępny: ' + res.available_from + ' – ' + res.available_to"></p>
          <p x-show="res.rules" x-text="res.rules"
            style="font-size:0.8rem;color:#9ca3af;margin:0 0 8px;"></p>
          <button @click="openForm(res)">Zarezerwuj</button>
        </div>
      </template>
    </div>
  </template>

  <!-- Formularz rezerwacji -->
  <template x-if="selectedResource">
    <div style="background:#f9fafb;padding:16px;border-radius:6px;">
      <h3>Rezerwacja: <span x-text="selectedResource.name"></span></h3>
      <form @submit.prevent="submitReservation()">
        <div>
          <label>Data *</label>
          <input type="date" x-model="form.res_date" @change="loadSlots()" required>
        </div>

        <!-- Zajęte sloty -->
        <template x-if="takenSlots.length">
          <div style="margin:8px 0;font-size:0.8rem;color:#9ca3af;">
            <strong>Zajęte:</strong>
            <template x-for="slot in takenSlots" :key="slot.start_time">
              <span x-text="' ' + slot.start_time.slice(0,5) + '–' + slot.end_time.slice(0,5)"></span>
            </template>
          </div>
        </template>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
          <div>
            <label>Od *</label>
            <input type="time" x-model="form.start_time" required>
          </div>
          <div>
            <label>Do *</label>
            <input type="time" x-model="form.end_time" required>
          </div>
        </div>
        <div>
          <label>Cel rezerwacji *</label>
          <input type="text" x-model="form.purpose" required placeholder="np. Mecz piłkarski">
        </div>

        <p x-show="formError" x-text="formError" style="color:#c0392b;"></p>

        <div style="display:flex;gap:8px;margin-top:12px;">
          <button type="submit" :disabled="loading" x-text="loading ? 'Wysyłanie…' : 'Zarezerwuj'"></button>
          <button type="button" @click="selectedResource = null">Anuluj</button>
        </div>
      </form>
    </div>
  </template>

  <!-- Moje rezerwacje -->
  <details style="margin-top:24px;">
    <summary style="cursor:pointer;font-weight:600;">Moje rezerwacje</summary>
    <table style="width:100%;margin-top:8px;border-collapse:collapse;font-size:0.85rem;">
      <thead>
        <tr style="background:#f3f4f6;">
          <th style="text-align:left;padding:6px;">Zasób</th>
          <th>Data</th><th>Godziny</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
        <template x-for="r in myReservations" :key="r.id">
          <tr style="border-bottom:1px solid #e5e7eb;">
            <td style="padding:6px;" x-text="r.resource_name || r.resource_id"></td>
            <td style="text-align:center;" x-text="r.res_date"></td>
            <td style="text-align:center;" x-text="r.start_time.slice(0,5) + '–' + r.end_time.slice(0,5)"></td>
            <td style="text-align:center;" x-text="r.status_label || r.status"></td>
            <td style="text-align:center;">
              <button
                x-show="r.status === 'pending'"
                @click="cancel(r.id)"
                style="font-size:0.75rem;color:#c0392b;background:none;border:none;cursor:pointer;">
                Anuluj
              </button>
            </td>
          </tr>
        </template>
        <tr x-show="!myReservations.length">
          <td colspan="5" style="text-align:center;color:#9ca3af;padding:12px;">Brak rezerwacji.</td>
        </tr>
      </tbody>
    </table>
  </details>

</div>
```

---

## 10 – Jadłospis dzienny

**Komponent**: `bmMenu` (widok dzienny)

```html
<div x-data="bmMenu()" x-init="init()">

  <p x-show="loading" style="color:#9ca3af;">Ładowanie jadłospisu…</p>
  <p x-show="error" x-text="error" style="color:#c0392b;"></p>

  <!-- Nawigacja po dniach -->
  <div style="display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap;">
    <template x-for="d in availableDates" :key="d">
      <button
        @click="selectDate(d)"
        :style="d === selectedDate
          ? 'background:#2563eb;color:#fff;border:none;padding:4px 10px;border-radius:4px;'
          : 'background:#f3f4f6;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;'"
        x-text="d.slice(5)">
      </button>
    </template>
  </div>

  <!-- Dzień bez jadłospisu -->
  <p x-show="!loading && !day" style="color:#9ca3af;">Brak jadłospisu na wybrany dzień.</p>

  <!-- Posiłki grupowane po typie -->
  <template x-if="day">
    <div>
      <p x-show="day.notes" x-text="day.notes" style="font-style:italic;color:#6b7280;margin-bottom:12px;"></p>

      <template x-for="mealType in ['sniadanie','drugie_sniadanie','obiad','podwieczorek','kolacja','inne']" :key="mealType">
        <template x-if="day.items.filter(i => i.meal_type === mealType).length">
          <div style="margin-bottom:16px;">
            <h4 style="text-transform:uppercase;font-size:0.75rem;color:#6b7280;letter-spacing:.05em;margin-bottom:6px;"
              x-text="mealTypeLabel(mealType)">
            </h4>
            <template x-for="item in day.items.filter(i => i.meal_type === mealType)" :key="item.id">
              <div style="padding:8px 12px;background:#f9fafb;border-radius:4px;margin-bottom:4px;">
                <span x-show="item.time_from"
                  x-text="item.time_from"
                  style="font-size:0.8rem;color:#6b7280;margin-right:8px;">
                </span>
                <strong x-text="item.title"></strong>
                <span x-show="item.is_new_today"     style="margin-left:6px;font-size:0.75rem;color:#16a34a;">🆕</span>
                <span x-show="item.is_updated_today" style="margin-left:6px;font-size:0.75rem;color:#d97706;">✏</span>
                <p x-show="item.description" x-text="item.description"
                  style="margin:2px 0 0;font-size:0.8rem;color:#6b7280;"></p>
                <p x-show="item.allergens"
                  x-text="'⚠ Alergeny: ' + item.allergens"
                  style="margin:2px 0 0;font-size:0.75rem;color:#92400e;"></p>
              </div>
            </template>
          </div>
        </template>
      </template>
    </div>
  </template>

</div>
```

---

## 11 – Jadłospis tygodniowy

**Komponent**: `bmMenu` (przełącznik widoku tygodniowego)

```html
<div x-data="bmMenu()" x-init="init()">

  <!-- Przełącznik widoku -->
  <div style="display:flex;gap:8px;margin-bottom:16px;">
    <button @click="setViewMode('day')"
      :style="viewMode==='day' ? 'font-weight:bold;' : ''">
      Dzienny
    </button>
    <button @click="setViewMode('week')"
      :style="viewMode==='week' ? 'font-weight:bold;' : ''">
      Tygodniowy
    </button>
  </div>

  <p x-show="loading" style="color:#9ca3af;">Ładowanie…</p>

  <!-- Widok tygodniowy -->
  <template x-if="viewMode === 'week'">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
      <template x-for="wday in weekDays" :key="wday.meal_date">
        <div style="border:1px solid #e5e7eb;border-radius:6px;padding:12px;">
          <h4 style="margin:0 0 8px;color:#374151;" x-text="wday.meal_date.slice(5)"></h4>
          <p x-show="!wday.items || !wday.items.length" style="color:#9ca3af;font-size:0.8rem;">Brak</p>
          <template x-for="item in (wday.items || [])" :key="item.id">
            <div style="font-size:0.8rem;margin-bottom:3px;">
              <span style="color:#6b7280;" x-text="mealTypeLabel(item.meal_type) + ': '"></span>
              <span x-text="item.title"></span>
            </div>
          </template>
        </div>
      </template>
    </div>
  </template>

  <!-- Widok dzienny (ten sam co element 10) -->
  <template x-if="viewMode === 'day'">
    <!-- tu wklej zawartość z elementu 10 (bez x-data) -->
    <p x-show="!day" style="color:#9ca3af;">Brak jadłospisu na dziś.</p>
    <template x-if="day">
      <p x-text="day.notes || ''"></p>
    </template>
  </template>

</div>
```

---

## 12 – Komunikacja – lista wątków

**Komponent**: `bmConversations`

```html
<div x-data="bmConversations()" x-init="init()">

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h3 style="margin:0;">Wiadomości
      <span x-show="unreadTotal > 0"
        x-text="'(' + unreadTotal + ' nowych)'"
        style="font-size:0.8rem;color:#2563eb;">
      </span>
    </h3>
    <button @click="view = 'new'">+ Nowy wątek</button>
  </div>

  <p x-show="loading" style="color:#9ca3af;">Ładowanie…</p>

  <!-- Lista wątków -->
  <template x-if="view === 'list'">
    <div>
      <p x-show="!threads.length && !loading" style="color:#9ca3af;">Brak wiadomości.</p>
      <template x-for="t in threads" :key="t.id">
        <div
          @click="openThread(t.id)"
          style="border:1px solid #e5e7eb;padding:12px;margin-bottom:6px;border-radius:6px;cursor:pointer;"
          :style="t.unread_camp > 0 ? 'border-left:4px solid #2563eb;font-weight:600;' : ''">
          <div style="display:flex;justify-content:space-between;">
            <span x-text="t.subject"></span>
            <span x-show="t.unread_camp > 0"
              x-text="t.unread_camp + ' nowych'"
              style="font-size:0.75rem;background:#2563eb;color:#fff;padding:2px 6px;border-radius:9999px;">
            </span>
          </div>
          <div style="font-size:0.8rem;color:#6b7280;margin-top:4px;">
            <span x-text="t.status"></span>
            &bull;
            <span x-text="t.last_message_at"></span>
          </div>
        </div>
      </template>
    </div>
  </template>

</div>
```

---

## 13 – Komunikacja – nowy wątek

Kontynuacja tego samego `x-data="bmConversations()"`:

```html
<!-- Wewnątrz tego samego x-data="bmConversations()" -->
<template x-if="view === 'new'">
  <div>
    <h4>Nowa wiadomość</h4>

    <label>Temat *</label>
    <input type="text" x-model="form.subject" required placeholder="Temat wiadomości">

    <label>Priorytet</label>
    <select x-model="form.priority">
      <option value="normal">Normalny</option>
      <option value="high">Wysoki</option>
      <option value="urgent">Pilny</option>
    </select>

    <label>Treść *</label>
    <textarea x-model="form.content" rows="5" required placeholder="Treść wiadomości…"></textarea>

    <p x-show="error"   x-text="error"   style="color:#c0392b;margin-top:6px;"></p>
    <p x-show="success" x-text="success" style="color:#27ae60;margin-top:6px;"></p>

    <div style="display:flex;gap:8px;margin-top:12px;">
      <button @click="createThread()" :disabled="loading"
        x-text="loading ? 'Wysyłanie…' : 'Wyślij'">
      </button>
      <button @click="view = 'list'">Anuluj</button>
    </div>
  </div>
</template>
```

---

## 14 – Komunikacja – widok wątku

Kontynuacja tego samego `x-data="bmConversations()"`:

```html
<!-- Wewnątrz tego samego x-data="bmConversations()" -->
<template x-if="view === 'thread' && currentThread">
  <div>
    <button @click="view = 'list'; currentThread = null;">← Wróć do listy</button>

    <h4 x-text="currentThread.subject" style="margin:12px 0 6px;"></h4>
    <span x-text="currentThread.status" style="font-size:0.8rem;color:#6b7280;"></span>

    <!-- Historia wiadomości -->
    <div style="margin:16px 0;max-height:400px;overflow-y:auto;">
      <template x-for="msg in messages" :key="msg.id">
        <div :style="msg.author_type === 'admin'
            ? 'background:#eff6ff;margin-left:32px;padding:10px;border-radius:6px;margin-bottom:8px;'
            : 'background:#f9fafb;margin-right:32px;padding:10px;border-radius:6px;margin-bottom:8px;'">
          <div style="font-size:0.75rem;color:#6b7280;margin-bottom:4px;">
            <strong x-text="msg.author_type === 'admin' ? '🏕 Administracja' : '👤 Kadra'"></strong>
            &bull;
            <span x-text="msg.created_at"></span>
          </div>
          <div x-html="msg.content"></div>
        </div>
      </template>
    </div>

    <!-- Formularz odpowiedzi -->
    <div x-show="currentThread.status !== 'closed' && currentThread.status !== 'archived'">
      <textarea x-model="replyContent" rows="3"
        placeholder="Twoja odpowiedź…"
        style="width:100%;margin-bottom:8px;">
      </textarea>
      <p x-show="error" x-text="error" style="color:#c0392b;"></p>
      <button @click="sendReply()" :disabled="loading || !replyContent"
        x-text="loading ? 'Wysyłanie…' : 'Wyślij odpowiedź'">
      </button>
    </div>
    <p x-show="currentThread.status === 'closed'" style="color:#6b7280;font-style:italic;">Wątek zamknięty.</p>
  </div>
</template>
```

---

## 15 – Baza pomocy – lista

**Komponent**: `bmHelp`

```html
<div x-data="bmHelp()" x-init="init()">

  <!-- Filtry -->
  <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
    <input type="text" x-model="search" @input.debounce.400ms="applyFilters()"
      placeholder="🔍 Szukaj…" style="flex:1;min-width:150px;">
    <select x-model="filterType" @change="applyFilters()">
      <option value="">Wszystkie typy</option>
      <option value="article">Artykuł</option>
      <option value="faq">FAQ</option>
      <option value="contact">Kontakt</option>
      <option value="procedure">Procedura</option>
      <option value="instruction">Instrukcja</option>
    </select>
    <template x-for="cat in categories" :key="cat">
      <button @click="filterCat = filterCat === cat ? '' : cat; applyFilters()"
        :style="filterCat === cat ? 'background:#2563eb;color:#fff;border:none;padding:4px 8px;border-radius:4px;' : 'background:#f3f4f6;border:none;padding:4px 8px;border-radius:4px;cursor:pointer;'"
        x-text="cat">
      </button>
    </template>
  </div>

  <p x-show="loading" style="color:#9ca3af;">Ładowanie…</p>

  <!-- Alarmowe (zawsze na górze) -->
  <template x-for="art in alarmArticles" :key="art.id">
    <div @click="openArticle(art.id)" style="background:#fee2e2;border-left:4px solid #ef4444;padding:12px;margin-bottom:8px;border-radius:4px;cursor:pointer;">
      <strong>🚨 <span x-text="art.title"></span></strong>
      <p x-text="art.excerpt" style="margin:4px 0 0;font-size:0.85rem;color:#6b7280;"></p>
    </div>
  </template>

  <!-- Przypięte -->
  <template x-for="art in pinnedArticles" :key="art.id">
    <div @click="openArticle(art.id)" style="border:1px solid #fbbf24;padding:12px;margin-bottom:6px;border-radius:4px;cursor:pointer;">
      <strong>📌 <span x-text="art.title"></span></strong>
      <p x-text="art.excerpt" style="margin:4px 0 0;font-size:0.85rem;color:#6b7280;"></p>
    </div>
  </template>

  <!-- Pozostałe -->
  <template x-for="art in articles.filter(a => !a.is_alarm && !a.is_pinned)" :key="art.id">
    <div @click="openArticle(art.id)" style="border:1px solid #e5e7eb;padding:12px;margin-bottom:6px;border-radius:4px;cursor:pointer;">
      <span x-text="art.type === 'faq' ? '❓ ' : art.type === 'contact' ? '📞 ' : '📄 '"></span>
      <strong x-text="art.title"></strong>
      <span style="margin-left:8px;font-size:0.75rem;color:#9ca3af;" x-text="art.category"></span>
      <p x-text="art.excerpt" style="margin:4px 0 0;font-size:0.85rem;color:#6b7280;"></p>
    </div>
  </template>

  <p x-show="!loading && !articles.length" style="color:#9ca3af;">Brak wyników.</p>

</div>
```

---

## 16 – Baza pomocy – podgląd artykułu

Kontynuacja tego samego `x-data="bmHelp()"`:

```html
<!-- Wewnątrz tego samego x-data="bmHelp()" -->
<template x-if="current">
  <div style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:20px;">
    <button @click="closeArticle()" style="margin-bottom:12px;">← Wróć do listy</button>
    <h2 x-text="current.title" style="margin:0 0 8px;"></h2>
    <span style="font-size:0.8rem;color:#6b7280;"
      x-text="(current.type || '') + (current.category ? ' · ' + current.category : '')">
    </span>
    <hr style="margin:12px 0;">
    <div x-html="current.content"></div>
  </div>
</template>
```

---

## 17 – Formularze – lista

**Komponent**: `bmForms`

```html
<div x-data="bmForms()" x-init="init()">

  <p x-show="loading" style="color:#9ca3af;">Ładowanie formularzy…</p>
  <p x-show="error"   x-text="error" style="color:#c0392b;"></p>

  <!-- Filtr kategorii -->
  <div x-show="categories.length > 1" style="display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap;">
    <button @click="filterCategory = ''"
      :style="!filterCategory ? 'background:#2563eb;color:#fff;border:none;padding:4px 10px;border-radius:4px;' : 'background:#f3f4f6;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;'">
      Wszystkie
    </button>
    <template x-for="cat in categories" :key="cat">
      <button @click="filterCategory = cat"
        :style="filterCategory === cat ? 'background:#2563eb;color:#fff;border:none;padding:4px 10px;border-radius:4px;' : 'background:#f3f4f6;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;'"
        x-text="cat">
      </button>
    </template>
  </div>

  <!-- Lista formularzy -->
  <template x-if="!currentForm">
    <div>
      <p x-show="!filtered.length && !loading" style="color:#9ca3af;">Brak dostępnych formularzy.</p>
      <template x-for="f in filtered" :key="f.id">
        <div style="border:1px solid #e5e7eb;padding:14px;margin-bottom:8px;border-radius:6px;"
          :style="f.is_pinned ? 'border-left:4px solid #f59e0b;' : ''">
          <span x-show="f.is_pinned" style="font-size:0.75rem;color:#d97706;">📌 Wyróżniony&nbsp;</span>
          <strong x-text="f.name"></strong>
          <span style="margin-left:8px;font-size:0.75rem;color:#9ca3af;" x-text="f.category"></span>
          <p x-text="f.description" style="margin:6px 0;font-size:0.85rem;color:#6b7280;"></p>
          <button @click="openForm(f.id)">Wypełnij formularz →</button>
        </div>
      </template>
    </div>
  </template>

</div>
```

---

## 18 – Formularz – wypełnianie i wysyłanie

Kontynuacja tego samego `x-data="bmForms()"`:

```html
<!-- Wewnątrz tego samego x-data="bmForms()" -->
<template x-if="currentForm && !submitted">
  <div>
    <button @click="closeForm()" style="margin-bottom:12px;">← Wróć do listy</button>
    <h3 x-text="currentForm.name"></h3>
    <p x-show="currentForm.info_before" x-text="currentForm.info_before"
      style="background:#eff6ff;padding:10px;border-radius:4px;margin-bottom:16px;font-size:0.9rem;"></p>

    <form @submit.prevent="submit()">
      <template x-for="field in fields" :key="field.field_key">
        <div style="margin-bottom:14px;">
          <label style="font-weight:600;">
            <span x-text="field.label"></span>
            <span x-show="field.is_required" style="color:#c0392b;"> *</span>
          </label>

          <!-- text / email / tel / number / date -->
          <template x-if="['text','email','tel','number','date'].includes(field.type)">
            <input
              :type="field.type"
              :required="field.is_required"
              :placeholder="field.placeholder"
              x-model="formValues[field.field_key]"
              style="width:100%;margin-top:4px;">
          </template>

          <!-- textarea -->
          <template x-if="field.type === 'textarea'">
            <textarea
              :required="field.is_required"
              :placeholder="field.placeholder"
              x-model="formValues[field.field_key]"
              rows="4"
              style="width:100%;margin-top:4px;">
            </textarea>
          </template>

          <!-- select -->
          <template x-if="field.type === 'select'">
            <select x-model="formValues[field.field_key]" :required="field.is_required" style="margin-top:4px;">
              <option value="">— wybierz —</option>
              <template x-for="opt in (field.options || [])" :key="opt">
                <option :value="opt" x-text="opt"></option>
              </template>
            </select>
          </template>

          <!-- radio -->
          <template x-if="field.type === 'radio'">
            <div style="margin-top:4px;">
              <template x-for="opt in (field.options || [])" :key="opt">
                <label style="display:block;font-weight:400;">
                  <input type="radio" :name="field.field_key" :value="opt"
                    x-model="formValues[field.field_key]" :required="field.is_required">
                  <span x-text="opt"></span>
                </label>
              </template>
            </div>
          </template>

          <!-- checkbox (multi) -->
          <template x-if="field.type === 'checkbox'">
            <div style="margin-top:4px;">
              <template x-for="opt in (field.options || [])" :key="opt">
                <label style="display:block;font-weight:400;">
                  <input type="checkbox" :value="opt"
                    @change="formValues[field.field_key].includes(opt)
                      ? formValues[field.field_key].splice(formValues[field.field_key].indexOf(opt),1)
                      : formValues[field.field_key].push(opt)">
                  <span x-text="opt"></span>
                </label>
              </template>
            </div>
          </template>

          <!-- Błąd pola -->
          <p x-show="fieldError(field.field_key)"
            x-text="fieldError(field.field_key)"
            style="color:#c0392b;font-size:0.8rem;margin-top:3px;">
          </p>

          <!-- Opis pomocniczy -->
          <p x-show="field.help_text" x-text="field.help_text"
            style="color:#6b7280;font-size:0.8rem;margin-top:3px;">
          </p>
        </div>
      </template>

      <p x-show="error" x-text="error" style="color:#c0392b;"></p>

      <button type="submit" :disabled="submitting"
        x-text="submitting ? 'Wysyłanie…' : 'Wyślij zgłoszenie'">
      </button>
    </form>
  </div>
</template>

<!-- Potwierdzenie po wysłaniu -->
<template x-if="submitted && submitResult">
  <div style="text-align:center;padding:32px;">
    <div style="font-size:3rem;">✅</div>
    <h3>Zgłoszenie wysłane!</h3>
    <p x-show="submitResult.info_after" x-text="submitResult.info_after"
      style="color:#6b7280;margin:8px 0 16px;">
    </p>
    <button @click="closeForm()">Wróć do listy</button>
  </div>
</template>
```

---

## 19 – Zgłoszenia – lista

**Komponent**: `bmSubmissions`

```html
<div x-data="bmSubmissions()" x-init="init()">

  <p x-show="loading" style="color:#9ca3af;">Ładowanie…</p>
  <p x-show="error"   x-text="error" style="color:#c0392b;"></p>

  <!-- Filtr statusu -->
  <div style="display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap;">
    <template x-for="[val, label] in Object.entries({
      '': 'Wszystkie', 'new': 'Nowe', 'in_progress': 'W trakcie',
      'waiting': 'Oczekuje', 'closed': 'Zamknięte', 'cancelled': 'Anulowane'
    })" :key="val">
      <button
        @click="filterStatus = val; applyFilter()"
        :style="filterStatus === val ? 'background:#2563eb;color:#fff;border:none;padding:4px 10px;border-radius:4px;' : 'background:#f3f4f6;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;'"
        x-text="label">
      </button>
    </template>
  </div>

  <!-- Lista -->
  <template x-if="!current">
    <div>
      <p x-show="!submissions.length && !loading" style="color:#9ca3af;">Brak zgłoszeń.</p>
      <template x-for="s in submissions" :key="s.id">
        <div @click="openSubmission(s.id)"
          style="border:1px solid #e5e7eb;padding:12px;margin-bottom:6px;border-radius:6px;cursor:pointer;">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <strong x-text="'#' + s.id + ' – ' + (s.form_name || s.form_id)"></strong>
            <span
              x-text="statusLabel(s.status)"
              :style="{
                new: 'background:#dbeafe;color:#1d4ed8;',
                in_progress: 'background:#fef3c7;color:#92400e;',
                waiting: 'background:#f3e8ff;color:#7e22ce;',
                closed: 'background:#d1fae5;color:#065f46;',
                cancelled: 'background:#e5e7eb;color:#374151;',
              }[s.status] + 'font-size:0.75rem;padding:2px 8px;border-radius:9999px;'"
            >
            </span>
          </div>
          <div style="font-size:0.8rem;color:#6b7280;margin-top:4px;">
            <span x-text="s.category || ''"></span>
            &bull;
            <span x-text="s.created_at"></span>
          </div>
        </div>
      </template>
    </div>
  </template>

</div>
```

---

## 20 – Zgłoszenia – podgląd szczegółów

Kontynuacja tego samego `x-data="bmSubmissions()"`:

```html
<!-- Wewnątrz tego samego x-data="bmSubmissions()" -->
<template x-if="current">
  <div>
    <button @click="closeSubmission()">← Wróć do listy</button>

    <p x-show="loadingDetail" style="color:#9ca3af;">Ładowanie szczegółów…</p>

    <template x-if="current.submission">
      <div style="margin-top:16px;">
        <h3 x-text="'Zgłoszenie #' + current.submission.id"></h3>

        <!-- Status i priorytet -->
        <div style="display:flex;gap:12px;margin-bottom:12px;">
          <span x-text="'Status: ' + statusLabel(current.submission.status)"
            style="font-size:0.85rem;font-weight:600;color:#374151;">
          </span>
          <span x-text="'Priorytet: ' + current.submission.priority"
            style="font-size:0.85rem;color:#6b7280;">
          </span>
        </div>

        <!-- Komentarz admina -->
        <template x-if="current.admin_comment">
          <div style="background:#eff6ff;border-left:4px solid #2563eb;padding:10px;margin-bottom:12px;border-radius:4px;">
            <strong>Komentarz administratora:</strong>
            <p x-text="current.admin_comment" style="margin:4px 0 0;"></p>
          </div>
        </template>

        <!-- Dane zgłoszenia -->
        <template x-if="current.submission_data">
          <div style="background:#f9fafb;padding:14px;border-radius:6px;margin-bottom:12px;">
            <h4 style="margin:0 0 10px;">Wypełnione dane</h4>
            <template x-for="field in (current.form_snapshot?.fields || [])" :key="field.field_key">
              <div style="margin-bottom:8px;">
                <dt style="font-weight:600;font-size:0.85rem;" x-text="field.label"></dt>
                <dd style="margin:2px 0 0;color:#374151;"
                  x-text="Array.isArray(current.submission_data[field.field_key])
                    ? current.submission_data[field.field_key].join(', ')
                    : current.submission_data[field.field_key] || '—'">
                </dd>
              </div>
            </template>
          </div>
        </template>

        <!-- Załączniki -->
        <template x-if="current.attachments && current.attachments.length">
          <div>
            <h4>Załączniki</h4>
            <template x-for="att in current.attachments" :key="att.id">
              <a
                :href="bmApi.getAttachmentUrl(current.submission.id, att.id)"
                target="_blank"
                style="display:flex;align-items:center;gap:8px;padding:8px;border:1px solid #e5e7eb;border-radius:4px;margin-bottom:4px;text-decoration:none;color:#2563eb;">
                📎 <span x-text="att.original_name"></span>
                <span style="font-size:0.75rem;color:#9ca3af;"
                  x-text="'(' + Math.round(att.file_size/1024) + ' KB)'">
                </span>
              </a>
            </template>
          </div>
        </template>
      </div>
    </template>
  </div>
</template>
```

---

## 21 – Wrapper ochrony sesji

Pokazuj treść tylko gdy użytkownik jest zalogowany. Przydatne do chowania całych sekcji strony.

```html
<!-- Widoczne tylko po zalogowaniu -->
<div x-data x-show="$store.bm.authenticated">
  <!-- Tu twój element panelu -->
</div>

<!-- Widoczne tylko gdy NIE jest zalogowany (np. ekran logowania) -->
<div x-data x-show="!$store.bm.authenticated">
  <!-- Tu element 01 – ekran logowania -->
</div>
```

Lub jako jeden kontener przełączający widok:

```html
<div x-data>
  <!-- Ekran logowania -->
  <div x-show="!$store.bm.authenticated" x-transition>
    <!-- Element 01 – bmLogin -->
  </div>
  <!-- Panel zalogowanego -->
  <div x-show="$store.bm.authenticated" x-transition>
    <!-- Elementy 02–20 -->
  </div>
</div>
```

---

## 22 – Licznik nieprzeczytanych wiadomości

Ikona/badge w menu nawigacji z liczbą nieprzeczytanych wątków.

```html
<div x-data>
  <a href="/komunikacja">
    📬 Wiadomości
    <template x-if="$store.bm.authenticated">
      <span
        x-data="bmConversations()"
        x-init="init()"
        x-show="unreadTotal > 0"
        x-text="unreadTotal"
        style="background:#ef4444;color:#fff;border-radius:9999px;font-size:0.7rem;padding:2px 6px;margin-left:4px;">
      </span>
    </template>
  </a>
</div>
```

---

## Wskazówki i uwagi

### Inicjalizacja Alpine Store

`Alpine.store('bm')` inicjalizuje się automatycznie po załadowaniu strony. Nie musisz nic wywoływać ręcznie. Jeśli komponent ładuje się na stronie gdzie użytkownik jest już zalogowany (ciasteczko `bm_session` aktywne), `bmConfig.authenticated` będzie `true` i store załaduje dane obozu.

### Zdarzenia custom

| Zdarzenie | Kiedy wywoływane |
|-----------|-----------------|
| `bm:login` | Po udanym zalogowaniu |
| `bm:logout` | Po wylogowaniu |
| `bm:countSaved` | Po zapisaniu meldunku |
| `bm:reportSubmitted` | Po wysłaniu meldunku |
| `bm:reservationCreated` | Po złożeniu rezerwacji |
| `bm:annSubmitted` | Po wysłaniu ogłoszenia |

Breakdance **Podmień widoczność** może reagować na te zdarzenia przez `window.addEventListener('bm:login', ...)`.

### Brak shortcode

Elementy działają na każdej stronie bez shortcode `[bm_init]`. Assetów nie trzeba ładować ręcznie.

### Własne style

Wszystkie przykładowe `style=""` to style inline dla przejrzystości kodu. W produkcji zastąp je klasami CSS z twojego motywu lub systemu designu.

### Błędy sesji / 401

Gdy sesja wygaśnie, endpointy panelowe zwrócą `401`. Komponenty wyświetlą błąd. Użytkownik musi ponownie przejść przez logowanie.