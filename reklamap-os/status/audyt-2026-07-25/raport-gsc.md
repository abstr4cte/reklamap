# Raport GSC — głęboka analiza | ReklaMap | 26.04–25.07.2026

**Agent:** Analityk Danych. **Data:** 2026-07-25.
**Dane wejściowe:** `reklamap-os/stats/imports/gsc-2026-07-25/api/*.json` (q3m/last28/prev28 × query/page/query_page/date/device/appearance), dociągnięte przez `scratchpad/gapi.py`: `dev_nonbrand_q3m`, `country_q3m`, `app_l28`, `app_p28`, `q_2026m5/6/7`, `p_2026m7`, `last7_query`, `last7_page`, `w1..w4_q/p`, `date_q3m_desktop/mobile` (zapisane w `scratchpad/analityk/`). Podaż: `scratchpad/listings_p1..5.json` (prod API, 827 ogł.). Sitemap: `scratchpad/sitemap-urls.txt` + żywe `reklamap.pl/sitemap.xml` i `api.reklamap.pl/sitemap.xml`. Weryfikacja renderu: `curl -A Googlebot` na 15 URL-ach prod.
**Skrypty analizy:** `scratchpad/an.py`, `scratchpad/analityk/g2.py`, `g3.py`, `g4.py` (wyniki: `an-out.txt`, `analityk/g2-out.txt`, `g3-out.txt`, `g4-out.txt`).
**Czego zabrakło:** GSC nie udostępnia crossowania `query × device` po stronie zapytań (tylko agregat) — rozbicie CTR desktop/mobile per fraza: brak danych. Coverage/Index Status API nie było odpytywane (brak `urlInspection` w tej sesji poza pojedynczymi curl-ami).

---

## 0. Kontrola sum (żeby nie mieszać wymiarów)

| Wymiar | Kliki | Wyświetlenia | Uwaga |
|---|---|---|---|
| `q3m__date` (91 dni) | 167 | 10 233 | prawda o serwisie, poz. ważona **31,3** |
| `q3m__query` (705 fraz) | 112 | 8 471 | **brakuje 55 klik i 1 762 wyśw (17,2%) — anonimizacja fraz** |
| `q3m__page` (232 URL) | 167 | 11 650 | > date, bo 1 SERP z 2 naszymi URL-ami = 2 wyśw. strony |

Każdy wniosek poniżej podaje, z którego wymiaru pochodzi. Porównania „fraza vs strona" robię świadomie — różnica między nimi **jest** miarą kanibalizacji (sekcja 2).

---

## 1. WISIENKI 5–20 — posortowane wg potencjał/wysiłek, nie wyświetleń

Baza: `q3m__query` + `q3m__query_page` (która strona rankuje) + prod API (czy jest podaż) + sitemap (czy strona jest prerenderowana).
**43 frazy w przedziale poz. 4,5–20,5 z ≥5 wyświetleń: 1 238 wyśw, 4 kliki (CTR 0,32%).**

Model potencjału: `wyśw.3m × (CTR@poz6 − CTR@poz_obecna)`, krzywa CTR modelowa (nie pomiar) — służy tylko do rankingu między frazami.

### XS — strona indeksowalna + podaż ≥3, wystarczy meta/treść (4 frazy, 166 wyśw, model +5,8 klik)

| Fraza | Wyśw | Poz | Strona rankująca | Podaż |
|---|---|---|---|---|
| `reklama mobilna bydgoszcz` | 107 | 15,4 | `/powierzchnie-reklamowe/reklama-mobilna` | 6 mobilnych w kraju, **0 w Bydgoszczy** |
| `billboardy koszalin` | 30 | 17,2 | `/powierzchnie-reklamowe/koszalin` | **70** |
| `billboardy dzierżoniów` | 19 | 18,9 | `/powierzchnie-reklamowe/billboardy/dzierzoniow` | 17 |
| `billboardy sosnowiec` | 10 | 19,6 | `/powierzchnie-reklamowe/sosnowiec` | 21 |

To **jedyne 4 frazy w całym serwisie**, gdzie robota SEO (title/description/treść) ma pod sobą realną podaż i indeksowalną stronę. Koszalin/Dzierżoniów/Sosnowiec to zarazem jedyny przypadek, gdzie import Big Group spotyka realny popyt.

### S — treść już istnieje (blog prerenderowany), wystarczy sekcja/nagłówek (7 fraz, 108 wyśw, model +3,1)

`reklama outdoorowa gdansk` 20/15,9 → `blog/lokalizacje/reklama-outdoor-gdansk` · `reklama na tramwajach kraków koszt` 19/19,4 i `reklama outdoorowa kraków` 16/18,6 i `nośniki reklamowe kraków outdoor` 25/8,1 → `blog/lokalizacje/reklama-outdoor-krakow` · `billboard cennik` 12/16,5 i `bilbordy koszt budowy` 10/7,8 → `blog/poradniki/billboard-reklama` · `porownaj oferty wynajmu billboardów na autostradach` 6/10,5 → hub.

**Kraków ma 3 wisienki na jednym artykule** (60 wyśw), Gdańsk 1. To najtańsza pula w całym raporcie: treść istnieje, prerender działa, podaż niepotrzebna (informacyjne).

### M — strona poza sitemapą/prerenderem (4 frazy, 100 wyśw, model +3,2)

`reklama citylight olsztyn` 69 wyśw / poz. **11,8** → rankuje leaf `powierzchnia-reklamowa/citylighty/olsztyn/...-32`, **który nie istnieje już w bazie** (patrz sekcja 5b). `billboard 6x3` 12/13,4 → leaf `...billboard-6x3-72`, **slug zmieniony** na `billboard-6-x-3-72`. `bilbordy reklamowe warszawa srodmiescie` 9/10,7 → leaf id 36, usunięty. `wynajem billboardów – porównaj warunki współpracy` 10/6,8 → `/faq`.

### L — podaż < 3, SEO nie pomoże (28 fraz, **864 wyśw = 70% całej puli wisienek**)

`powierzchnie reklamowe białystok` 119/20,5 (podaż 0) · `powierzchnie reklamowe lublin` 121/8,1 (1) · `totemy reklamowe wrocław` 100/11,4 (0) · `reklama na ekranach led poznan` 87/19,3 (0) · `reklama na ekranach led kraków` 76/13,8 (0) · `reklama tranzytowa poznan` 41/13,0 (0) · `reklama na ekranach led warszawa` 40/15,6 (0) · `citylight wrocław` 38/15,5 (0) · `ekrany led kraków` 38/18,9 (0) …

**To jest główny wniosek raportu:** 70% gotowego popytu w strefie 5–20 stoi nad stronami, które z definicji dostają `noindex` (próg `THIN_PAGE_THRESHOLD=3`, `frontend/src/utils/listingsSeo.ts:12`). Żadna praca Stratega/Pisarza tego nie odblokuje — odblokowuje to **nośnik w bazie**.

---

## 2. KANIBALIZACJA — pełny obraz

Metryka: `x = Σ wyświetleń stron dla frazy / wyświetlenia frazy`. `x > 1` ⇒ kilka naszych URL-i pojawia się w tym samym SERP-ie.

- **108 fraz ma >2 nasze strony** (`q3m__query_page`), łącznie **5 098 wyświetleń stron = 60,2%** wszystkich wyświetleń z wymiaru query.
- Nadmiarowe wyświetlenia (duplikaty w SERP-ach): **1 188**, z tego non-brand **450**.
- **Model konsolidacji** (jeden URL na frazę, pozycja = obecna najlepsza): realne 5 klików → modelowe **48,7** ⇒ **+43,7 klika / 3 mies.** Model, nie pomiar — ale rząd wielkości pokazuje, że kanibalizacja jest drugim (po podaży) hamulcem.

### 2a. Brand: 63 URL-e na frazę `reklamap`
`imp(query)=195`, `Σimp(stron)=933`, **x=4,78**, 107 klików — z czego **104 na `/`**. Pozostałe 62 URL-e wygenerowały 739 wyświetleń i **3 kliki**. To sitelinkowe rozlanie: nie kosztuje ruchu (brand i tak trafia na home), ale **zaburza każdy agregat po stronach** — dlatego brand trzeba odfiltrować z analiz stronowych, inaczej `/powierzchnie-reklamowe/inne` wygląda na „poz. 1,6" (113 wyśw, w tym 101 z brandu).

### 2b. Non-brand, najbardziej odzyskiwalne (best_pos ≤ 25, ≥3 strony)

| Fraza | Wyśw | Poz. śr. | Poz. best | Stron | Która strona POWINNA rankować | Kto rozcieńcza |
|---|---|---|---|---|---|---|
| `reklama citylight olsztyn` | 69 | 11,8 | **5,7** | 5 | leaf citylight Olsztyn (poz. 5,7) — **usunięty z bazy** | `/citylighty` (17,4), `/citylighty/krakow` (36,2), blog citylight (48), home (48,3) |
| `powierzchnie reklamowe lublin` | 121 | 8,1 | 7,8 | 2 | `/powierzchnie-reklamowe/lublin` | wariant **www** (7,8) vs non-www (12,9) — ten sam URL w dwóch hostach |
| `reklama na ekranach led kraków` | 76 | 13,8 | 7,3 | 4 | `/ekrany-led/krakow` (7,3) | `/krakow` (28,2), `/ekrany-led` (33,6), www `/ekrany-led` (29,0) |
| `powierzchnie reklamowe poznań` | 64 | 23,6 | 9,0 | 6 | `/powierzchnie-reklamowe/poznan` (14,6) | www `/poznan` (9,0), www i non-www `/sciany-reklamowe/poznan`, `/totemy-reklamowe/poznan`, www `/banery/poznan` |
| `reklama mobilna bydgoszcz` | 107 | 15,4 | 15,7 | 3 | `/reklama-mobilna` (15,7) | `/bydgoszcz` (17,4), leaf przyczepki (24,0) |
| `bilbordy reklamowe warszawa srodmiescie` | 9 | 10,7 | **1,8** | 3 | leaf billboard Śródmieście (1,8) — **usunięty z bazy** | `/billboardy/warszawa`, `/warszawa` |
| `billboardy łódź` | 170* | 35,3 | 22,9 | 7 | `/billboardy/lodz` (22,9) | `/banery/lodz` (39,2), `/lodz` (39,7), `/totemy-reklamowe/lodz` (49,8), blog Łódź (43,5), `/reklama-w-transporcie/lodz` (54), `/billboardy/dabrowa-gornicza` (73) |
| `bilbordy gdańsk` | 138* | 31,7 | 31,7 | **9** | `/billboardy/gdansk` | `/sciany-reklamowe/gdansk`, `/totemy-reklamowe/gdansk`, `/gdansk`, `/reklama-mobilna/gdansk`, `/reklama-w-transporcie/gdansk`, `/citylighty/gdansk`, `/banery/gdansk`, blog Gdańsk |
| `powierzchnie reklamowe` | 110* | 30,5 | 24,6 | 9 | `/` lub hub `/powierzchnie-reklamowe` | 7 stron miast/typów |

\* wyświetlenia stron (Σ), nie zapytania.

**Wzorzec, nie przypadek:** kanibalizacja miast (`gdańsk`, `łódź`, `poznań`) bierze się stąd, że **wszystkie kombinacje typ×miasto dla tego samego miasta rankują na to samo zapytanie ogólne** — mimo że 7 z 9 z nich ma **0 ofert**. Google wybiera z nich najlepszą i nie ma z czego wybierać. Konsolidacja tu = usunięcie pustych kombinacji z indeksu (już są `noindex` — patrz sekcja 5 — więc problem sam wygaśnie), **nie** przepisywanie treści.

**Drugi wzorzec — www.** W 50 przypadkach ten sam URL istnieje w GSC w obu hostach; łącznie www 986 vs non-www 5 007 wyśw. Ale trend pokazuje, że **301 działa i to się zwija** (sekcja 4d) — nie jest to problem do naprawiania, tylko do przeczekania.

---

## 3. CTR-owe trupy — z rozbiciem na przyczynę

Definicja: `q3m__query`, poz ≤ 20,5, ≥5 wyśw, **0 klików**. Wynik: **41 fraz, 1 154 wyświetlenia, 0 klików.**

| Przyczyna | Fraz | Wyśw | Udział |
|---|---|---|---|
| **(b) strona thin/pusta — bot dostaje szkielet `noindex`** | 30 | **900** | **78,0%** |
| (a) meta/title (strona prerenderowana + podaż OK) | 11 | 254 | 22,0% |
| (c) fraza o zerowej intencji | 0 wykrytych automatycznie | — | — |

**(b) — mechanizm potwierdzony na produkcji, nie hipoteza.** URL spoza sitemapy dostaje `dist/spa-fallback.html` (`frontend/scripts/prerender.mjs:44-56`): **7 929 bajtów**, `<meta name="robots" content="noindex, follow">`, `<title>Wynajem powierzchni reklamowych w Polsce | ReklaMap</title>`. Zweryfikowane `curl -A Googlebot` na 11 URL-ach (m.in. `/powierzchnie-reklamowe/warszawa`, `/lublin`, `/bialystok`, `/citylighty/wroclaw`, `/reklama-w-transporcie/gdansk`, `/totemy-reklamowe/wroclaw`, `/bydgoszcz`, `/gdynia`). Dla porównania strony prerenderowane: `/powierzchnie-reklamowe/koszalin` **186 000 B**, `index, follow`, własny title; `/powierzchnie-reklamowe/citylighty` 122 668 B; `/blog/poradniki/billboard-reklama` 78 089 B.

⇒ Te frazy mają CTR 0 nie „bo zły opis", tylko dlatego, że **w SERP-ie widnieje generyczny tytuł strony głównej dla zapytania `totemy reklamowe wrocław`**. Poprawianie meta na tych stronach jest bezcelowe (i tak nadpisze je szkielet). Bez podaży → strona z definicji noindex → snippet nie do naprawienia.

**(a) — realny dług meta, 254 wyśw:** `billboardy dzierżoniów` (19/18,9, podaż 17), `billboardy sosnowiec` (10/19,6, podaż 21), `reklama mobilna bydgoszcz` (107/15,4), `nośniki reklamowe kraków outdoor` (25/8,1 → blog Kraków), `reklama outdoorowa gdansk/kraków`, `billboard cennik`, `bilbordy koszt budowy`, `reklama na tramwajach kraków koszt`, `porownaj oferty…`, `wynajem billboardów – porównaj warunki współpracy` (10/6,8 → `/faq`).

**(c)** — automatyczny filtr (co to/jak/czym/definicja) nie złapał nic w tej puli. Ręcznie: `billboard 6x3` (12/13,4) i `billboard wymiary` (11/43,9) to intencja specyfikacyjna, nie zakupowa; `wynajem billboardów – porównaj warunki współpracy` (10/6,8) wygląda na frazę odbitą z naszego własnego snippetu. Łącznie ≤ 33 wyśw — pomijalne. **Kategoria „zerowa intencja" praktycznie u nas nie występuje.**

**Bonus — trupy poza zasięgiem (poz. 20–40, 0 klików, ≥40 wyśw): 25 fraz / 1 526 wyśw.** Największe: `billboardy łódź` 146, `reklama led poznań` 123, `powierzchnie reklamowe` 105, `reklama na autobusach poznan` 74, `reklama na przystankach gdansk` 68, `billboardy gdańsk` 64, `powierzchnie reklamowe poznań` 64, `reklama na tramwajach gdansk` 64, `citylighty` 63, `reklama na ekranach led katowice` 63.

---

## 4. Co realnie napędza wzrost 28d — i czy poz. 59→26 jest trwała

**Odpowiedź: to w ~85% artefakt mieszanki (zanik ogona 50+), a nie wejście do strefy klikalnej.**

### 4a. Rozkład wyświetleń wg pozycji, tydzień po tygodniu (`w1..w4_q.json`)

| Tydzień | Fraz | Wyśw | Klik | poz 1–10 | poz 10–20 | poz 20–50 | poz 50+ |
|---|---|---|---|---|---|---|---|
| w1 28.06–04.07 | 136 | 435 | 2 | 25 (6%) | 18 (4%) | 218 (50%) | **174 (40%)** |
| w2 05–11.07 | 197 | 743 | 0 | 24 (3%) | 52 (7%) | 356 (48%) | **311 (42%)** |
| w3 12–18.07 | 293 | 1 012 | 2 | 40 (4%) | 105 (10%) | 667 (66%) | 200 (20%) |
| w4 19–25.07 | 311 | 1 258 | 2 | **31 (2%)** | 146 (12%) | 1 029 (82%) | **52 (4%)** |

- Wyświetlenia na **poz. 1–10: 25 → 31 — płasko** (a udział spadł 6% → 2%).
- Wyświetlenia na **poz. 10–20: 18 → 146 (+711%)** — to jedyny realny, trwały przyrost.
- Wyświetlenia na **poz. 50+: 174 → 52 (−70%)** — to zniknięcie ogona ciągnie średnią z 59,5 (05.07) do 26,4 (23.07).
- **Kliki: 2 / 0 / 2 / 2 — całkowicie płasko** mimo 3× większych wyświetleń.

### 4b. Dekompozycja last28 vs prev28 (`last28__query` vs `prev28__query`)
385 nowych fraz (2 255 wyśw = **65% przyrostu**), średnia pozycja ważona nowych fraz **46,1**; rozkład ich wyświetleń: 1–10 = 1%, 10–20 = 7%, 20–50 = 63%, 50+ = 28%. Frazy wspólne: 760 → 1 193 wyśw. Frazy, które zniknęły: 138 wyśw.

### 4c. Widok miesięczny — kliki non-brand nigdy nie działały

| Miesiąc | Wyśw | Klik | Brand klik | **Non-brand wyśw / klik** |
|---|---|---|---|---|
| maj (pełny) | 4 093 | 72 | 68 | **4 011 / 4** |
| czerwiec (deindeks) | 926 | 29 | 29 | **861 / 0** |
| lipiec 1–25 | 3 318 | 4 | 3 | **3 278 / 1** |

⇒ Narracja „deindeks zabrał ruch, recovery go wraca" jest **prawdziwa tylko dla wyświetleń**. Kliki non-brand wynosiły 4 na 4 011 wyświetleń już w maju, na szczycie. **Problem nie jest w indeksacji, tylko w tym, że nigdy nie weszliśmy do strefy klikalnej non-brand.**

### 4d. Urządzenia i geografia
- **Non-brand, 3 mies. (`dev_nonbrand_q3m`): DESKTOP 7 894 wyśw / 2 kliki (CTR 0,025%), MOBILE 382 / 3 (0,785%).** Desktop to **95,4%** wyświetleń non-brand i praktycznie 0 klików.
- Cały ruch (z brandem): MOBILE 1 101 wyśw / 109 klik / poz 16,7; DESKTOP 9 129 / 58 / poz 33,1; TABLET 3 / 0.
- Kraje: `pol` 9 542 wyśw / 162 klik; `usa` 208 / 0; `gbr` 83 / 2; `deu` 54 / 1. Ruch zagraniczny = 6,8% wyświetleń, 3% klików — szum.
- **www zwija się poprawnie:** udział www w wyświetleniach stron: prev28 **33,3%** → last28 **6,9%** → lipiec 1–25 **6,0%** → ostatnie 7 dni **2,6%**. Kontrola: `www.reklamap.pl/powierzchnie-reklamowe/totemy-reklamowe/wroclaw` → HTTP **301** → non-www. **Korekta do ustalenia #5 z briefingu:** rozjazd www jest widoczny wyłącznie w agregacie 3-miesięcznym (ciągnie go maj–czerwiec); w bieżącym oknie jest praktycznie zamknięty. Nie inwestować w to czasu.

**Werdykt na pytanie „czy trwała":** trwały jest przyrost fraz w paśmie 10–20 (18 → 146 wyśw/tydz.). Sama liczba „26,4" nie jest — to średnia, którą podnosi zanik śmieciowych zapytań na poz. 70–95 (typ `agencja reklamowa dąbrowa górnicza`, poz. 75,8). Jeśli w sierpniu wejdzie kolejna partia fraz na poz. 60+, średnia sama się „pogorszy" bez żadnej realnej zmiany. **KPI musi być liczba wyświetleń w paśmie 1–10 i 10–20, nie średnia pozycja.**

---

## 5. Strony z wyświetleniami, które są noindex/thin — gdzie marnujemy popyt

Podaż liczona z prod API (827 ogłoszeń, wszystkie `is_active=1`; statusy: active 349, reserved 463, soon_available 15). Mapa slugów typu wg `backend/routes/web.php:85-89`.

### 5a. Skala marnotrawstwa
- **88 URL-i z wyświetleniami ma podaż < 3 → 5 273 wyświetlenia / 23 kliki** (CTR 0,44%). To **45,3% wszystkich wyświetleń w wymiarze stron** (5 273 / 11 650).
- Z tego **69 URL-i ma podaż dokładnie 0 → 4 206 wyświetleń / 16 klik**.
- **Wszystkie 88 są poza sitemapą (0 sprzeczności)** — niezmiennik „próg 3 spójny w 3 miejscach" (`CLAUDE.md`) jest **zachowany**. Nie ma tu błędu do naprawienia w kodzie; jest błąd w portfelu podaży.

Największe pojedyncze straty (`q3m__page`, podaż z prod API):

| Strona | Wyśw | Klik | Poz | Podaż |
|---|---|---|---|---|
| `/powierzchnie-reklamowe/warszawa` | 452 | 4 | 44,0 | **2** |
| `/powierzchnie-reklamowe/reklama-w-transporcie/gdansk` | 334 | 0 | 42,9 | 0 |
| `/powierzchnie-reklamowe/reklama-w-transporcie/poznan` | 216 | 0 | 34,1 | 0 |
| `/powierzchnie-reklamowe/ekrany-led/poznan` | 203 | 0 | 23,6 | 0 |
| `/powierzchnie-reklamowe/banery/lodz` | 164 | 0 | 30,7 | 0 |
| `/powierzchnie-reklamowe/totemy-reklamowe/gdansk` | 156 | 1 | 27,6 | 0 |
| `/powierzchnie-reklamowe/bydgoszcz` | 154 | 0 | 22,1 | 0 |
| `/powierzchnie-reklamowe/reklama-mobilna/gdansk` | 153 | 1 | 29,7 | 0 |
| `/powierzchnie-reklamowe/lublin` | 150 | 0 | 12,9 | **1** |
| `/powierzchnie-reklamowe/billboardy/gdansk` | 121 | 0 | 30,1 | 0 |
| `/powierzchnie-reklamowe/billboardy/warszawa` | 121 | 0 | 31,0 | **1** |
| `/powierzchnie-reklamowe/ekrany-led/krakow` | 117 | 1 | 13,7 | 0 |

**Uwaga na dwie strony INDEKSOWALNE, ale faktycznie puste w treści:** `/powierzchnie-reklamowe/ekrany-led` — **569 wyśw / 1 klik / poz 34,1** przy **5 ekranach LED w całym kraju**; `/powierzchnie-reklamowe/citylighty` — **633 / 0 / 35,8** przy **12 citylightach**. Obie przechodzą próg 3 (5 ≥ 3, 12 ≥ 3), więc są w sitemapie i prerenderowane — ale użytkownik szukający „ekrany led kraków" dostaje stronę z 5 nośnikami z całej Polski. To 1 202 wyświetlenia obsłużone stroną, która nie ma czego pokazać.

### 5b. 32 usunięte ogłoszenia wciąż zwracają HTTP 200 (soft-404) — i były naszymi najlepszymi stronami

Sprawdziłem każdy leaf-URL z `q3m__page` przeciw prod API po ID:

- **32 URL-e nie istnieją już w bazie** (ID 1–47 — pierwotny seed z opisami pisanymi ręcznie): **411 wyświetleń, 7 klików**, pozycje w większości **2,7–8,0**.
- Ich pozycje były nieporównywalnie lepsze niż reszty serwisu: leaf id 28 → **3,9**; id 34 → **2,7**; id 8 → **5,2**; id 24 → **5,9**; id 32 → **5,9**; id 16 → **6,3**; id 1 → **8,0**.
- **7 z 12 klików na leafach w całym kwartale pochodziło z ogłoszeń, które już nie istnieją.**
- Produkcja zwraca dla nich **HTTP 200 + szkielet 7 929 B z `noindex`** — nie 404 ani 410. Zweryfikowane curl-em na 2 URL-ach.
- Dodatkowo **2 leafy zmieniły slug przy tym samym ID**: id 72 `billboard-6x3-72` → `billboard-6-x-3-72`, id 63 `billboard-20x6m-…` → `billboard-20-x-6m-…`. Stary URL jest w indeksie, nowy w sitemapie (16 wyśw).

⇒ To bezpośrednio tłumaczy zapaść rich resultów (sekcja 6) i zniknięcie fraz z poz. 5–10.

### 5c. Sitemap serwowany przez front jest rozjechany z generatorem

| Źródło | Liczba `<loc>` |
|---|---|
| `https://reklamap.pl/sitemap.xml` (statyczny plik z `dist/`) | **983** |
| `https://api.reklamap.pl/sitemap.xml` (generator, prawda) | **987** |

Brakujące 4 URL-e — i wszystkie 4 zwracają Googlebotowi szkielet `noindex` (zweryfikowane curl-em):
1. `/blog/prawo-i-regulacje/pozwolenie-na-tablice-reklamowa` — **opublikowany** (potwierdzone `api/blog`)
2. `/blog/prawo-i-regulacje/reklama-bez-pozwolenia-kary` — **opublikowany**
3. `/powierzchnia-reklamowa/billboardy/jablonowo/…-997`
4. `/powierzchnia-reklamowa/billboardy/nowa-wies-elcka/billboard-blisko-elku-998`

**Przyczyna systemowa:** statyczny `sitemap.xml` powstaje w `prerender.mjs` przy deployu frontu. Publikacja artykułu lub dodanie nośnika **bez deployu frontu** = treść nie trafia do serwowanej sitemapy **i** nie zostaje sprerenderowana ⇒ Google dostaje `noindex`. To nie jednorazowa wpadka, tylko stała klasa błędu.

### 5d. Wykorzystanie inwentarza
- **825 leafów w sitemapie, tylko 36 (4,4%) ma ≥1 wyświetlenie w 3 mies.** — razem 159 wyśw / 4 kliki.
- Kategorie/kombinacje: 118 URL-i w sitemapie, 35 z jakimikolwiek wyświetleniami.
- Innymi słowy: **95,6% inwentarza ogłoszeniowego jest niewidoczne w Google.** Import 768 billboardów Big Group nie przełożył się na widoczność — te nośniki stoją w miastach o znikomym wolumenie wyszukiwań (Kłodzko 138 szt., Koszalin 70, Dąbrowa Górnicza 60, Szalejów Górny 35).

### 5e. Popyt vs podaż wg TYPU nośnika (nowy przekrój)

Klasyfikacja 705 fraz non-brand po słowach kluczowych typu (`g4.py`), wyświetlenia z `q3m__query`:

| Typ | Popyt (wyśw) | % popytu typowanego | Podaż (szt.) | % podaży | Werdykt |
|---|---|---|---|---|---|
| billboard | 2 070 | 33,1% | **768** | 92,9% | podaż >> popyt |
| **led_screen** | **1 267** | 20,3% | **5** | 0,6% | **popyt >> podaż** |
| **citylight** | **1 222** | 19,6% | **12** | 1,5% | **popyt >> podaż** |
| **transport** | **883** | 14,1% | **1** | 0,1% | **popyt >> podaż** |
| **mobile** | 515 | 8,2% | 6 | 0,7% | **popyt >> podaż** |
| totem | 154 | 2,5% | **0** | 0,0% | popyt >> podaż |
| banner | 130 | 2,1% | 9 | 1,1% | ~ |
| wall | 8 | 0,1% | 24 | 2,9% | podaż >> popyt |
| (bez typu) | 2 339 | — | — | — | — |

**Non-billboard = 4 179 wyśw (66,9% typowanego popytu) przy 59 nośnikach (7,1% podaży).** W last28 udział popytu non-billboard rośnie dalej: citylight 526, transport 372, LED 377 vs billboard 626.

---

## 6. Wygląd w wyszukiwarce (`q3m__appearance`, `app_l28`, `app_p28`)

Jedyny typ wzbogaconego wyniku, jaki mamy: **PRODUCT_SNIPPETS**.

| Okres | Wyśw | Klik | CTR | Poz |
|---|---|---|---|---|
| 3 mies. (26.04–25.07) | **157** | **4** | 2,55% | 15,9 |
| last28 (28.06–25.07) | **22** | **0** | 0% | 22,5 |
| prev28 (31.05–27.06) | **0 wierszy** | — | — | — |

Wnioski liczbowe:
- Rich snippety obejmują **1,5% wyświetleń serwisu** (157 / 10 233), ale dały **2,4% klików** (4 / 167). CTR 2,55% vs 1,63% średnia serwisu ⇒ **snippet produktowy klika się ~1,6× lepiej** — przy tej próbce to sygnał, nie dowód.
- **Wolumen spadł 7× w ostatnim miesiącu** (157 → 22) i pozycja pogorszyła się z 15,9 do 22,5. Powód jest ten sam co w 5b: PRODUCT_SNIPPETS mogą pochodzić wyłącznie ze stron leaf (schema Product), a **32 najlepiej rankujące leafy usunięto z bazy**.
- **Zero innych typów wzbogaceń** — brak FAQ, brak breadcrumbs raportowanych jako osobny appearance, brak Sitelinks searchbox. Przy 30 artykułach blogowych z sekcjami pytań to niewykorzystany kanał.

---

## 7. Segment blog — który silos rośnie, który stoi

Blog łącznie 3 mies.: **1 704 wyśw / 8 klik** (`blog_pages_q3m`) = 16,6% wyświetleń serwisu, 4,8% klików.
Ważne odniesienie: **prev28 cały blog miał 1 wyświetlenie**. Blog wrócił do indeksu dopiero ~28.06 — więc każdy silos „rośnie z zera" i porównania MoM są bez wartości. Sensowna jest struktura, nie dynamika.

| Silos | Art. z ruchem | Wyśw 3m | Klik | Śr. poz | Ocena |
|---|---|---|---|---|---|
| **poradniki** | 6 | **1 000** | 4 | 26,6 | rośnie, ale ciągnie go 1 artykuł |
| **lokalizacje** | 8 | 598 | 3 | 27,8 | rośnie równomiernie, najlepszy fundament |
| **prawo-i-regulacje** | 2 | 104 | 1 | **18,9** | najlepsza średnia pozycja, najmniej treści |
| **trendy** | **0** | **0** | **0** | — | **stoi całkowicie — 4 artykuły, 0 wyświetleń przez 3 miesiące** |

### 7a. Ranking artykułów (3 mies.)
`poradniki/billboard-reklama` **814 / 2 / poz 23,5** — sam robi 47,8% wyświetleń blogowych, a w ostatnich 7 dniach (19–25.07) **391 wyśw = 23,5% wyświetleń serwisu w wymiarze stron** (391 / 1 663, `last7_page.json`). Dalej: `lokalizacje/reklama-outdoor-krakow` 238/1/26,8 · `poradniki/citylight-reklama` 136/1/44,7 · `lokalizacje/reklama-outdoor-poznan` 122/1/30,7 · `lokalizacje/reklama-outdoor-gdansk` 115/1/26,7 · `lokalizacje/reklama-outdoor-lodz` 107/0/29,8 · `prawo/uchwala-krajobrazowa-reklama` 103/1/19,0 · `poradniki/jak-zarobic-na-wynajmie…` 19/1/**12,9 — CTR 5,26%, najlepszy na blogu**.

### 7b. 16 z 33 blogowych URL-i ma **0 wyświetleń** w 3 mies.
Cały silos `trendy` (index + `dooh-reklama-programatyczna`, `murale-reklamowe`, `telebim-ekran-led-reklama`, `totem-reklamowy`), a także `lokalizacje/reklama-outdoor-warszawa`, `lokalizacje/reklama-outdoor-wroclaw` (!), `poradniki/{baner-reklamowy-cena, jak-wybrac-powierzchnie-reklamowa, reklama-na-samochodzie, reklama-w-transporcie-publicznym, reklama-zewnetrzna, tablica-reklamowa}` oraz 4 indeksy kategorii.

Boli szczególnie: `telebim-ekran-led-reklama` i `totem-reklamowy` leżą dokładnie na typach o **największej luce popytowej** (LED 1 267 wyśw, totem 154) i mają **0 wyświetleń**, podczas gdy cały ten popyt trafia na puste strony kategorii.

### 7c. Struktura intencji zapytań — czego blog NIE łapie

| Klasa intencji | Fraz | Wyśw | Klik | Śr. poz |
|---|---|---|---|---|
| komercyjna (cena/koszt/wynajem/ile kosztuje) | 95 | **879** (10,4%) | 1 | ~28 |
| **podażowa** (zarobić/wynajmę/opłaca się/mam działkę) | **0** | **0** | 0 | — |
| „agencja / firma reklamowa / druk / szyldy" (intencja niezgodna z produktem) | 25 | 219 | 0 | **73,6** |
| anglojęzyczna | 33 | 354 | 0 | ~35 |

**Zero fraz podażowych w 705 zapytaniach.** Dwa artykuły napisane pod właściciela nośnika zebrały łącznie **12 wyświetleń** (`jak-zarobic…` 7 na `wynajem powierzchni reklamowej`, `czy-oplaca-sie…` 5). W fazie, w której produkt buduje PODAŻ, **SEO nie dostarcza ani jednego wejścia od właściciela nośnika** — cały ruch organiczny to strona popytowa (reklamodawca).

`/dodaj-powierzchnie-reklamowa` ma 24 wyświetlenia — wszystkie z frazy brandowej `reklamap`.

---

## 8. Co z tego wynika — w kolejności

1. **Podaż jest wąskim gardłem SEO, nie treść.** 864 z 1 238 wyświetleń w paśmie 5–20 i 5 273 z 8 471 wyświetleń ogółem stoją nad stronami z podażą < 3. Priorytet pozyskania: **citylight / LED / transport / mobile w Poznaniu, Krakowie, Gdańsku, Wrocławiu, Warszawie, Łodzi, Białymstoku, Lublinie, Bydgoszczy** — dokładnie tam Google już nas pokazuje. Próg do przekroczenia to **3 nośniki na kombinację typ×miasto** — 3 citylighty w Poznaniu odblokowują stronę, która ma dziś 0 ofert i widoczność.
2. **32 usunięte ogłoszenia = największa pojedyncza strata rankingu i jedyne źródło rich snippetów.** Do decyzji: 410 zamiast 200 (żeby przestać trzymać soft-404), albo — jeśli nośniki wciąż istnieją — przywrócenie pod **tymi samymi ID/URL-ami** (`updateOrCreate`, nigdy delete+create).
3. **Sitemap frontu jest o 4 URL-e w tyle za generatorem** — publikacja treści bez deployu frontu = treść z `noindex`. To proces, nie incydent.
4. **KPI trzeba zmienić.** Średnia pozycja 26,4 nie mierzy postępu (spadek z 59 to zanik ogona 50+, top-10 płaskie). Mierz: wyświetlenia w paśmie 1–10 i 10–20 tydzień do tygodnia oraz kliki non-brand.
5. **www: zamknięte, nie ruszać.** 33,3% → 2,6% udziału w wyświetleniach.
6. **Blog: dosypać do `prawo-i-regulacje` (najlepsza pozycja przy najmniejszym nakładzie) i `lokalizacje`; `trendy` w obecnej formie nie działa** (0 wyświetleń / 4 artykuły / 3 miesiące) — ale jego tematy (telebim/LED, totem) mają największą lukę popytową, więc problem jest w wykonaniu/linkowaniu, nie w temacie.
7. **`billboard-reklama` to koncentracja ryzyka:** 1 artykuł = 47,8% wyświetleń blogowych i 23,5% wyświetleń serwisu w ostatnim tygodniu. Potrzebny drugi taki filar (LED albo citylight — po 1,2 tys. wyświetleń popytu każdy).
