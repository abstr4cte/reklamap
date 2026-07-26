# Raport GA4 — głęboka analiza lejków, ReklaMap, 2026-07-25

Property `526431028`. Okno bazowe **2026-04-26 … 2026-07-25** (91 dni).
Wszystkie liczby poniżej — o ile nie zaznaczono inaczej — są **filtrowane do `hostName = reklamap.pl`**
(odcięcie ruchu deweloperskiego, patrz F9). Źródła: pliki
`reklamap-os/stats/imports/ga4-2026-07-25/*.json` oraz zapytania własne przez
`gapi.ga4_report` / `runReport` / `runFunnelReport` (v1alpha).

## 0. Baza odniesienia (prod)

| miara | wartość |
|---|---|
| użytkownicy | **332** |
| sesje | **754** |
| odsłony | **2 095** |
| unikalnych ścieżek | 248 |

Zastrzeżenia metodologiczne, które trzeba znać przy czytaniu reszty:

1. **`runFunnelReport` widzi tylko ~2 ostatnie miesiące.** Zwraca 160 userów `session_start`
   przy 333 w raportach standardowych; liczby pokrywają się dokładnie z oknem
   **2026-05-26 … 2026-07-25** (164/24/14). To domyślna retencja danych user-level w GA4.
   Lejki user-level liczę więc na oknie 60-dniowym i tak je oznaczam.
2. **Instrumentacja lejków ruszyła w połowie maja.** `zdarz_dzien.json`: pierwsze
   `view_item` i `contact_phone_click` = **2026-05-13**, pierwsze `add_listing_step_view`
   i `add_listing_step_complete` = **2026-05-14**. Metryki 3-miesięczne dla tych zdarzeń
   opisują 73 dni, nie 91. Dlatego `add_listing_success` ma 31 userów, a `step_complete` tylko 17 —
   to nie paradoks lejka, tylko różne okna pomiaru.

---

## 1. LEJEK POPYTU — gdzie się urywa

### 1a. Poziom użytkowników, grupy stron (91 dni, prod)

| etap | userzy | sesje | odsłony | % userów prod |
|---|---|---|---|---|
| dowolna strona | 332 | 754 | 2 095 | 100% |
| home `/` | **257** | 578 | 1 000 | 77,4% |
| listingi `/powierzchnie-reklamowe*` | **103** | 132 | 294 | 31,0% |
| detal `/powierzchnia-reklamowa/*` | **115** | 211 | 449 | 34,6% |
| `view_item` (zdarzenie) | **72** | 123 | — | 21,7% |
| kontakt (telefon LUB formularz) | **10** | 18 | — | **3,0%** |
| ↳ `contact_phone_click` | 7 | 15 | 39 zd. | 2,1% |
| ↳ `contact_form_submit` | 3 | 3 | 4 zd. | 0,9% |

Zbiory 7 i 3 są rozłączne (7 + 3 = 10 = wynik zapytania na `eventName IN [...]`).

**Detal (115) > listingi (103)** — więcej ludzi trafia na kartę nośnika bezpośrednio
(Google, direct) niż przez wyszukiwarkę serwisu. Wyszukiwarka nie jest głównym wejściem.

### 1b. Poziom użytkowników, sekwencyjnie (`runFunnelReport`, 26.05–25.07)

```
home 108  →  listingi 59 (30,6%)  →  detal 35 (35,6%)  →  kontakt 3 (8,6%)
             ubytek 75 (69,4%)      ubytek 38 (64,4%)     ubytek 32 (91,4%)
kumulatywnie: 108 → 3 = 2,8%

dowolne wejście 159 → listingi 61 (37,7%) → detal 21 (34,4%) → kontakt 2 (9,5%)
detal 56 → kontakt 7 = 12,5%
```

Rozbicie ostatniego kroku po kanale (`funnelBreakdown`):

| kanał | detal (userzy) | kontakt | konwersja |
|---|---|---|---|
| Organic Search | 17 | 4 | **23,5%** |
| Direct | 29 | 3 | 10,3% |
| Email | 8 | 0 | **0%** |
| Referral | 1 | 0 | 0% |

**Największy ubytek bezwzględny: home → listingi (75 z 108 userów).**
**Największy ubytek względny: detal → kontakt (91,4%).**

### 1c. Krok „wyszukiwarka" jest praktycznie niemierzony

`view_search_results` = **20 zdarzeń / 6 userów** w 91 dni (1,8% userów prod), przy 103 userach
faktycznie oglądających strony listingów. Przyczyna jest w kodzie:

- `view_search_results` to zdarzenie **Enhanced Measurement (site search)** — GA4 odpala je tylko,
  gdy w URL jest parametr `q` (lub `s`, `search`, `query`, `keyword`).
- `frontend/src/utils/filterUtils.ts:163` — `q` powstaje **wyłącznie** z `filters.keyword`.
  Miasto, typ, cena, wymiary idą jako `city`, `type`, `priceFrom`… (linie 164–219).
  Nawigacja po kategoriach i filtrach **nie generuje żadnego zdarzenia wyszukiwania.**
- `analytics.search()` (`frontend/src/utils/analytics.ts:50`) i `analytics.filterUsed()` (`:53`)
  **nie są wywołane nigdzie w repo** (grep po `frontend/src`, 0 trafień poza definicją).
  Potwierdzenie po stronie danych: w `zdarzenia.json` nie ma zdarzeń `search` ani `filter_used`.

Czyli: 6 userów w 3 miesiące użyło pola tekstowego. O tym, ilu użyło filtrów, **brak danych**.

### 1d. Ubytek w samej instrumentacji karty nośnika

115 userów weszło na `/powierzchnia-reklamowa/*`, ale tylko **72** odpaliło `view_item` — **−37,4%**.
`AdDetailPage.vue:604–615`: `analytics.viewAd(...)` siedzi wewnątrz `try` po `await axios.get(/api/listings/{id})`.
Jeśli API nie odpowie (timeout, 5xx, WAF), zdarzenie nie leci, a użytkownik i tak widzi
prerenderowaną treść. Lejek popytu jest więc raportowany o ~1/3 za nisko.

---

## 2. LEJEK PODAŻY — na którym kroku odpada najwięcej

### 2a. Zdarzenia (prod, 14.05–25.07 — okno pełnej instrumentacji)

| zdarzenie | zdarzeń | userów |
|---|---|---|
| `add_listing_start` | 58 | **25** |
| `add_listing_step_view` | 324 | 25 |
| `add_listing_step_complete` | 240 | **17** |
| `add_listing_success` | 38 | **16** |

25 → 17 → 16 = **68% → 94%**, łącznie **64%** startujących kończy.

### 2b. Sekwencyjnie (`runFunnelReport`, 26.05–25.07)

```
home 113  →  /dodaj-powierzchnie-reklamowa 24 (19,5%)  →  step_complete 15 (62,5%)  →  success 14 (93,3%)
             ubytek 89 (80,5%)                            ubytek 9 (37,5%)              ubytek 1 (6,7%)
```

**Wniosek jest odwrotny do intuicji: formularz nie jest wąskim gardłem.**
Kto ukończy pierwszy krok, ten w 93,3% dochodzi do publikacji. Odpada się (a) po drodze na
formularz (80,5%) i (b) na **pierwszym kroku** formularza (37,5%).

Rozbicia:

| wymiar | start | step_complete | success |
|---|---|---|---|
| Direct | 14 | 9 (64,3%) | 9 (100%) |
| Organic Search | 7 | 4 (57,1%) | 3 (75%) |
| Email | 2 | 2 (100%) | 2 (100%) |
| desktop | 18 | 11 (61,1%) | 10 (90,9%) |
| mobile | 6 | 4 (66,7%) | 4 (100%) |

### 2c. Rozbicie po `step_number` — BRAK DANYCH (i dlaczego)

`analytics.ts:42–46` wysyła parametry `step_number` i `ad_type`, ale w property
**nie zarejestrowano ani jednej niestandardowej definicji**:
`analyticsdata/v1beta/properties/526431028/metadata` → 0 wymiarów `customEvent:*`.
Zapytanie o `customEvent:step_number` zwraca HTTP 400 „is not a valid dimension".
**Parametr jest zbierany, ale bezpowrotnie nieodczytywalny w raportach** —
dane historyczne sprzed rejestracji custom dimension też nie wrócą.

Co da się powiedzieć pośrednio:
- 324 `step_view` / 25 userów = **13,0 wejść na krok na użytkownika** przy formularzu
  6-krokowym (`AddAdPage.vue:85 totalSteps = 6`) → 2,2× więcej niż ścieżka minimalna;
  ludzie się cofają.
- 240 `step_complete` / 17 userów = **14,1** przy maksymalnie 5 możliwych completach
  (krok 6 uruchamia `handleSubmit()`, nie `nextStep()` — `AddAdPage.vue:1434`) → ~2,8× powtórzeń.
- Krok 1 (`validateStep`, `case 1`) wymaga naraz: **e-mail + tytuł + opis (free-text) + typ**.
  To jedyny krok, na którym 37,5% odpada — i jedyny, który prosi o e-mail zanim cokolwiek dał.

### 2d. Cross-check z bazą

`reklamap-os/stats/stats-2026-07-25.md`: „Nowe realne (30 dni) = **7**".
GA4 `add_listing_success` 26.06–25.07 = **4 zdarzenia / 4 userów**. Różnica 3 = adblock/brak JS
albo dodania poza formularzem. Zgodność rzędu wielkości — instrumentacja sukcesu działa.

---

## 3. Kanały — kto konwertuje ponadprzeciętnie

Prod, 91 dni:

| kanał | sesje | userzy | engagementRate | keyEvents | s/sesja | key/user |
|---|---|---|---|---|---|---|
| Direct | 450 | 231 | 0,45 | 30 | 74,9 | 13,0% |
| Organic Search | 231 | 80 | 0,56 | 5 | 86,1 | 6,3% |
| **Email** (`outreach/email`) | 45 | 17 | **0,71** | 3 | **98,4** | **17,6%** |
| Referral | 15 | 3 | 0,53 | 0 | 28,5 | 0% |
| Organic Social | 7 | 7 | 0,57 | 0 | 3,4 | 0% |
| Unassigned | 7 | 5 | 0,00 | 0 | 17,4 | 0% |

### To, co psuje całą tę tabelę

**Jedynym key eventem w property jest `add_listing_success`** (zapytanie `eventName × keyEvents`:
38 keyEvents, wszystkie na `add_listing_success`; wszystkie pozostałe zdarzenia mają keyEvents = 0).
`contact_phone_click` i `contact_form_submit` **nie są oznaczone jako kluczowe**.

Konsekwencja: kolumna „keyEvents" w każdym raporcie GA4 — kanałów, landingów, kampanii —
mierzy **wyłącznie podaż**. Popyt nie istnieje w żadnym raporcie konwersji.
Pytanie „czy Organic Search konwertuje" ma więc dwie różne odpowiedzi:

- **Po stronie podaży** — słabo: 5 konwersji / 80 userów = 6,3%, dwukrotnie gorzej niż Direct.
- **Po stronie popytu** — **najlepiej ze wszystkich kanałów**: detal → kontakt 4/17 = **23,5%**,
  przy Direct 3/29 = 10,3% i Email 0/8 = 0% (§1b). Organic dostarcza 88 z 255 `view_item`
  (23 userów) i 35 z 39 `contact_phone_click` (3 userów) — czyli **90% wszystkich kliknięć
  w telefon pochodzi z Google**, a w raportach GA4 tego nie widać.

Urządzenia (prod): desktop 455 sesji / 214 userów / eng 0,55 / **34 keyEvents**;
mobile 298 sesji / 118 userów / eng 0,42 / **4 keyEvents**.
Mobile = 39,5% sesji i 35,5% userów, ale tylko **10,5% konwersji**.

Nowi vs powracający: new 323 sesje / 323 userów / 10 keyEvents; returning 318 sesji / 69 userów /
**28 keyEvents**. 21% userów (powracający) generuje 74% konwersji — podaż wymaga powrotu.
Dodatkowo 112 sesji / 38 userów ma `newVsReturning = (not set)` i engagementRate 0,00.

---

## 4. Landingi ruchu nie-organicznego

| landing | kanał | sesje | userzy | eng | key |
|---|---|---|---|---|---|
| `/` | Direct | **343** | 198 | 0,45 | 20 |
| `/` | Email | **23** | 11 | 0,74 | 3 |
| `(not set)` | Direct | 11 | 11 | 0,00 | 0 |
| `/dodaj-powierzchnie-reklamowa` | Direct | 10 | 4 | 0,40 | **3** |
| `/powierzchnia-reklamowa/inne/kaszewy-dworne/...` | Referral | 10 | 1 | 0,70 | 0 |
| `/zarzadzaj/0017ffd5-…` | Direct | 10 | 1 | 0,40 | 0 |
| `/powierzchnie-reklamowe` | Email | 6 | 6 | **1,00** | 0 |
| `/powierzchnia-reklamowa/billboardy/wroclaw/…-504` | Email | 5 | 1 | 0,60 | 0 |
| `/reklamap.pl:443` | Direct | 5 | 5 | 1,00 | 0 |
| `/kontakt` | Direct | 4 | 4 | 0,75 | 0 |
| `/powierzchnie-reklamowe/warszawa` | Direct | 4 | 4 | **0,00** | 0 |

Ocena sensowności:

- **Outreach nie ma landinga.** 23 z 45 sesji Email (**51,1%**) ląduje na generycznej home;
  kolejne 6 na pustym hubie `/powierzchnie-reklamowe`. Strona `/dodaj-powierzchnie-reklamowa` —
  jedyna z realną gęstością konwersji (3 keyEvents na 10 sesji, 196,8 s na odsłonę, eng 0,92) —
  dostaje z maila **0 sesji**.
- 5 sesji Email ląduje na `/zarzadzaj/…` (link zarządzania) — to ruch obsługowy istniejącego
  wystawcy, nie akwizycja; miesza się w tym samym kanale.
- `zasobygwp.pl / referral` = 11 sesji / **1 user** na jednym leafie — wygląda na agregator/scraper,
  nie na ruch ludzki.
- `/powierzchnie-reklamowe/warszawa`: 4 sesje Direct z engagementRate **0,00** i 35 odsłon /17 userów
  łącznie — strona thin (2 nośniki, `noindex` z progu `THIN_PAGE_THRESHOLD=3`) nadal odbiera ruch.

---

## 5. Trend w czasie i korelacja z GSC

GA4 prod (sesje) vs GSC (`gsc-2026-07-25/api/q3m__date.json`):

| miesiąc | GA4 sesje | GA4 sesje/dzień | GA4 Organic | GSC kliki | GSC wyświetlenia | GSC wyśw./dzień | CTR |
|---|---|---|---|---|---|---|---|
| 04 (5 dni) | 37 | 7,4 | 10 | 10 | 187 | 37 | 5,35% |
| 05 | **394** | **12,7** | 120 | **89** | 4 841 | 156 | 1,84% |
| 06 | 232 | 7,7 | 69 | 40 | 1 147 | 38 | 3,49% |
| 07 (25 dni) | **92** | **3,7** | 32 | 28 | 4 058 | **162** | **0,69%** |

Tygodniowo (GA4 sesje / GSC kliki / GSC wyświetlenia):

```
04.05–10.05   211 /  47 / 1098      ← szczyt ruchu
11.05–17.05    98 /  26 / 1789
18.05–24.05    46 /  10 / 1239
25.05–31.05    30 /   5 /  647
01.06–07.06    46 /   4 /  217      ← dno widoczności (deindeks)
08.06–14.06    76 /  15 /  261
15.06–21.06    62 /  13 /  233
22.06–28.06    36 /   6 /  334
29.06–05.07    34 /   3 /  543
06.07–12.07    14 /   4 /  891      ← dno ruchu przy odbudowie wyświetleń
13.07–19.07    28 /   7 / 1375
20.07–25.07    28 /  16 / 1351
```

**Korelacja jest ujemna.** Wyświetlenia w lipcu wróciły do poziomu majowego
(162/dzień vs 156/dzień), a ruch spadł o **71%** (12,7 → 3,7 sesji/dzień) i kliki o **61%**
(2,87 → 1,12/dzień). CTR spadł 2,7× (1,84% → **0,69%**). Odbudowa po deindeksie odzyskała
ekspozycję, ale na pozycjach, które nie zbierają kliknięć (średnia pozycja 31,4 wg briefingu).
Interpretacja: **odzyskaliśmy widoczność, nie odzyskaliśmy ruchu** — i te dwie rzeczy
w lipcu rozjechały się maksymalnie.

Zgodność źródeł jest dobra (GSC kliki ≈ GA4 Organic sesje): 04 → 10 vs 10, 07 → 28 vs 32.
Rozjazd w 05–06 (89 vs 120, 40 vs 69) to najpewniej sesje organiczne z innych wyszukiwarek
i re-atrybucja.

---

## 6. Duży ruch, zerowe zaangażowanie

Prod, `userEngagementDuration / screenPageViews`:

| strona | odsłony | userzy | s/odsłonę | engRate | bounce |
|---|---|---|---|---|---|
| **`/`** | **1 000** (47,7% ruchu) | 257 | **13,0** | 0,52 | **0,48** |
| `/zarzadzaj/0017ffd5-…` | 15 | 1 | **2,1** | 0,42 | 0,58 |
| `/reklamap.pl:443` | 12 | 5 | 8,9 | 1,00 | 0,00 |
| `/powierzchnia-reklamowa/billboardy/nowa-ruda/…-111` | 6 | 5 | **1,5** | 0,67 | 0,33 |
| `/zarzadzaj/6efaaf8b-…` | 5 | 2 | **1,2** | 0,40 | 0,60 |
| `/zarzadzaj/c91755d1-…` | 5 | 1 | **1,0** | 0,80 | 0,20 |
| `/powierzchnia-reklamowa/banery/katowice/…bochenskiego` | 5 | 3 | **2,2** | 1,00 | 0,00 |
| `/powierzchnie-reklamowe/warszawa` | 35 | 17 | 11,6 | 0,76 (Direct-landing: **0,00**) | 0,24 |

Dla kontrastu — strony, które trzymają uwagę:
`/dodaj-powierzchnie-reklamowa` **196,8 s/odsłonę** (125 odsłon, 51 userów, eng 0,92),
`/powierzchnia-reklamowa/billboardy/warszawa/billboard-premium-sr…` 80,0 s,
`/faq` 37,4 s, `/blog` 36,5 s, `/kontakt` 30,5 s.

**Kandydat #1 to home.** Skupia 47,7% odsłon i 77,4% userów, ale trzyma 13 s na odsłonę,
odbija 48% sesji i przepuszcza dalej tylko 30,6% (§1b). To jedno miejsce kosztuje
69,4% lejka popytu i 80,5% lejka podaży.

**Kandydat #2 to `/zarzadzaj/*`** — trzy różne tokeny, 25 odsłon łącznie, **1,0–2,1 s na odsłonę**.
Wystawca klika link z maila, patrzy 1–2 s i wychodzi. Wygląda na wygasły token / błąd panelu,
ale to sygnał, nie dowód — do sprawdzenia ręcznego.

---

## 7. Anomalia `/reklamap.pl:443`

Pełny obraz z `fullPageUrl` + `pageReferrer`:

```
reklamap.pl/reklamap.pl:443                                  5 odsłon / 5 userów
reklamap.pl/reklamap.pl:443?_v=1778750598606                 1
reklamap.pl/reklamap.pl:443?_v=1778750598606&_v=1778750620303 1
reklamap.pl/reklamap.pl:443?_v=1778750701851                 1
reklamap.pl/reklamap.pl:443?_v=1778750701851&_v=1778750742249 1
reklamap.pl/reklamap.pl:443?_v=1778750795669                 1
reklamap.pl/reklamap.pl:443?_v=1778750795669&_v=1778750838681 1
reklamap.pl/reklamap.pl:443?_v=1779280158863                 1
pageReferrer: https://reklamap.pl/reklamap.pl:443            (self-referral)
```

Razem 12 odsłon / 5 sesji / 5 userów. Daty: **2026-05-14** (4 userzy: 2× desktop Chrome,
2× mobile Safari) i **2026-05-20** (1 desktop Chrome). Kanał: w 100% Direct.

**Mechanizm:** ktoś kliknął/wkleił link `reklamap.pl:443` **bez schematu**. Przeglądarka
potraktowała to jako ścieżkę względną i rozwiązała względem origin →
`https://reklamap.pl/reklamap.pl:443`. Ciąg `:443` **nie występuje nigdzie w repo**
(grep po `backend/app`, `backend/resources`, `backend/routes`, `backend/database`,
`frontend/src`, `reklamap-os` — 0 trafień), więc źródło jest zewnętrzne: najpewniej
konfiguracja vhosta / panel hostingu (`ServerName reklamap.pl:443`) skopiowana do wiadomości.
**Dokładnego nadawcy nie da się ustalić — brak danych.**

Sama anomalia jest błaha (5 userów), ale odsłania dwa realne defekty:

**(a) Soft-404 — serwer zwraca 200 na dowolną ścieżkę.**
```
curl https://reklamap.pl/reklamap.pl:443        → 200, 7929 B
curl https://reklamap.pl/jakas-losowa-sciezka-xyz → 200, 7929 B  (identyczna odpowiedź)
```
Z UA Googlebot treść zawiera `noindex` i `<title>Wynajem powierzchni reklamowych w Polsce | ReklaMap</title>`
— czyli szkielet home. `noindex` chroni przed indeksacją, ale **status 200 zamiast 404**
to klasyczne „soft 404" w GSC i nieskończona powierzchnia śmieciowych URL-i.

**(b) Guard `?_v=` zapętla się na takich URL-ach.**
Widać łańcuchy `?_v=1778750598606&_v=1778750620303` — odstęp 22 s, czyli guard odpalił drugi raz
mimo throttlingu 10 s (`frontend/index.html:44`). Skala na produkcji jest istotna:
**83 odsłony z `?_v=` / 69 userów / 75 sesji**, rozłożone na **24 różne dni**
(kwiecień 12, maj 45, czerwiec 13, lipiec 13). To **9,9% sesji prod i 20,8% userów prod**,
u których nie załadował się skrypt lub arkusz i zadziałało recovery — to nie jednorazowy
efekt deployu, tylko stały poziom błędów ładowania zasobów.

---

## 8. Realna metryka wartości marketplace

91 dni, produkcja:

```
115 unikalnych userów  weszło na stronę ogłoszenia (/powierzchnia-reklamowa/*)
 72                    odpaliło view_item                    (instrumentacja gubi 37,4%)
 10                    podjęło kontakt   =  7 telefon + 3 formularz (zbiory rozłączne)
```

- **10 / 115 = 8,7%** (wobec realnych wejść na kartę)
- **10 / 72 = 13,9%** (wobec zmierzonych `view_item`)
- **10 / 332 = 3,0%** wszystkich użytkowników serwisu
- w przeliczeniu na czas: **1 kontakt na 9,1 dnia**

Zanik w lipcu: **ostatni `contact_phone_click` = 2026-06-30** (`zdarz_dzien.json`);
w oknie 26.06–25.07 są 3 zdarzenia / 1 user, wszystkie z 30.06. Od 1 lipca do 25 lipca
**zero kliknięć w telefon**. `contact_form_submit`: 1 zdarzenie 2026-07-23.

Kontrola krzyżowa z backendem (`stats-2026-07-25.md`, 30 dni):
- „Zapytań przez formularz = **1**" vs GA4 `contact_form_submit` = **1** → **zgodne**.
- „Łącznie odsłon = **591**" vs GA4 odsłony stron ogłoszeń = **43** (18 userów) → **13,7× rozbieżności**.

Wyjaśnienie rozbieżności (istotne, bo ten licznik widzi wystawca):
`AdvertisementController.php:699–719` — `incrementViews` dedupuje po `IP + ad_id` na 1 h,
więc jedno przejście po całym katalogu z jednego IP liczy się jako tyle odsłon, ile ogłoszeń.
`frontend/scripts/prerender.mjs` renderuje przy każdym deployu wszystkie trasy z sitemapy
(**983 URL-e, w tym 825 leafów ogłoszeń** — `curl reklamap.pl/sitemap.xml`), **nie blokuje żadnych
żądań** (brak `setRequestInterception` w pliku) i czeka `networkidle0` + do 20 s na dane
(`prerender.mjs:137–152`) — czyli dłużej niż 2-sekundowy timer POST-a `increment-views`
(`AdDetailPage.vue:618–620`), a `axios.defaults.baseURL` w buildzie produkcyjnym wskazuje
na **prod API** (`frontend/src/api/axios.ts:7`). GA4 tego ruchu nie widzi: host `localhost`
ma w property tylko 153 odsłony / 2 userów przez 3 miesiące, mimo że każdy deploy renderuje 983 trasy
(automatyczne filtrowanie HeadlessChrome). Do tego dochodzą crawlery wykonujące JS
(Bing sam raportuje 14 656 pobrań `Code2xx` w 91 dni).

**Wniosek: licznik „Odsłony" pokazywany właścicielowi nośnika jest zawyżony rzędu 14×
i nie mierzy zainteresowania ludzi.**

---

## 9. Higiena danych — co jeszcze psuje pomiar

- **Ruch deweloperski w produkcyjnej property.** `hostName`: `reklamap.pl` 754 sesje / 2 095 odsłon,
  `localhost` 15 / 153, `127.0.0.1` 3 / 336. Razem **489 z 2 584 odsłon (18,9%)** to dev.
  Zanieczyszczał też lejek podaży: `add_listing_start` ma 181 zdarzeń / 56 userów łącznie,
  ale **127 / 51 na produkcji** — 54 zdarzenia i 5 userów pochodziło z `localhost`/`127.0.0.1`.
  (Korekta ustalenia #12 z briefingu: prod-only jest 51 → 31 = **60,8%**, nie 55%.)
  Przyczyna: `G-0ZL0NS8F9W` jest wpisany na sztywno w `frontend/index.html:138,144` bez bramki na środowisko.
- **Martwy kod pomiarowy w `analytics.ts`** — funkcje bez ani jednego wywołania w repo:
  `clickEmail` (:30, potwierdzone w briefingu), `search` (:50), `filterUsed` (:53),
  `newsletterSubscribe` (:57), `mainContactFormSubmit` (:64), `addToComparison` (:68).
  Sześć z czternastu zdefiniowanych zdarzeń nigdy nie odpala.
- **`(not set)` w landingach:** 22 sesje / 20 userów bez landing page, engagementRate 0,00.

---

## 10. Skrót liczbowy

| pytanie | odpowiedź |
|---|---|
| Gdzie urywa się popyt | home→listingi −69,4%, listingi→detal −64,4%, detal→kontakt −91,4%; kumulatywnie 108→3 userów (2,8%) |
| Gdzie urywa się podaż | home→/dodaj −80,5%, /dodaj→krok 1 −37,5%, dalej −6,7% |
| Który krok formularza zabija | **brak danych** — 0 zarejestrowanych custom dimensions, `step_number` nieodczytywalny |
| Kanał ponadprzeciętny (podaż) | Email 17,6% key/user; Direct 13,0%; Organic 6,3% |
| Kanał ponadprzeciętny (popyt) | **Organic Search 23,5%** detal→kontakt; Direct 10,3%; Email 0% |
| Czy Organic konwertuje | tak — i po stronie popytu najlepiej, ale GA4 tego nie raportuje (0 key eventów popytu) |
| Landing outreachu | `/` (51,1% sesji Email); `/dodaj-powierzchnie-reklamowa` = 0 sesji z maila |
| Trend ruchu | −71% maj→lipiec (12,7 → 3,7 sesji/dzień) przy wyświetleniach GSC 156 → 162/dzień |
| Strona do naprawy #1 | `/` — 47,7% odsłon, 13,0 s/odsłonę, bounce 0,48 |
| Anomalia `:443` | 5 userów, link bez schematu rozwiązany relatywnie; źródło poza repo; ujawnia soft-404 (200) i pętlę `?_v=` |
| Realna wartość marketplace | **115 userów na kartach → 10 kontaktów (8,7%)** w 91 dni; w lipcu 0 telefonów |
