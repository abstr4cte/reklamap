# Raport Analityczny ReklaMap — 26.04–25.07.2026 (audyt 7-wymiarowy)

**Data:** 2026-07-25 · **Agent:** Analityk Danych (raport zbiorczy) · **Faza projektu:** budowa PODAŻY

**Dane wejściowe:**
GSC API 91 dni (`reklamap-os/stats/imports/gsc-2026-07-25/api/q3m__{query,page,query_page,date,device,appearance}.json`, `last28__*`, `prev28__*`, + dociągnięte tygodniowe `scratchpad/analityk/w1..w4_{q,p}.json`) ·
GA4 property `526431028` (`imports/ga4-2026-07-25/*.json` + `runReport`/`runFunnelReport` własne, filtr `hostName=reklamap.pl`) ·
Bing Webmaster API (13 metod, `imports/bing-2026-07-25/*.json`) ·
GSC URL Inspection API — **183 inspekcje** (158 = 100% klasy nieleafowej z sitemapy, 25 leafów) ·
**218 pobrań HTML jako Googlebot** + 54 testy curl × 3 UA ·
**12 przebiegów Lighthouse 13.4.1** na żywym prodzie + sonda `PerformanceObserver` ·
Prod API ogłoszeń (827 rekordów, `scratchpad/prod_ads.json`) · `stats-2026-07-25.md` · kod repo `master@73ce63b`.

**Czego zabrakło (brak danych, nie zgadywanie):**
CrUX / dane polowe / INP — PSI API HTTP 429 (limit anonimowy), CrUX API HTTP 403 bez klucza ·
rozbicie lejka podaży po `step_number` — property ma **0 zarejestrowanych custom dimensions** (`analyticsdata/v1beta/properties/526431028/metadata`) ·
Bing AI Performance / Copilot — 7 wariantów nazw metody → HTTP 404, brak API ·
lista 24 URL-i „Błąd serwera (5xx)" z UI GSC (raport Crawl Stats nie ma API) ·
`owner_email` per ogłoszenie (pole `$hidden`, poza publicznym endpointem) — rozbicia `reserved` po właścicielach nie da się zrobić.

---

## 1. Najważniejsze wnioski (TL;DR)

1. **Recovery działa, ale mierzyliśmy go złym wymiarem.** Kliki non-brand (wymiar STRON, bez home) wzrosły **12 → 23 w 28 dni (+92%)**, tygodniowo **1 → 2 → 3 → 17**; spadek nagłówkowy 38 → 30 to w całości zapaść klików BRANDOWYCH na home (**26 → 7**), czyli miara aktywności cold-callowej foundera, nie SEO.
2. **Teza „695 fraz non-brand daje 6 klików" jest artefaktem anonimizacji GSC** — wymiar zapytań łapie 112 ze 167 klików (33% ginie), a realne kliki non-brand to **~60 w 3 miesiące** (167 total − 107 brand), nie 6.
3. **Twardym wąskim gardłem jest podaż, nie treść:** 88 URL-i z **5 273 wyświetleniami (45,3%)** ma <3 oferty i dostaje od Googlebota szkielet 7 929 B z `noindex`; **66% wyświetleń kategorii (4 173) trafia na strony z ZEROWĄ podażą**; 24 nośniki w 10 slotach odblokowują strony, które zebrały **2 484 wyświetlenia = 29,3% ruchu serwisu**.
4. **Dwa niezależne, żywe błędy publikacyjne kosztują ~1 500 wyświetleń/kwartał przy koszcie XS:** 4 URL-e z sitemapy (w tym 2 opublikowane artykuły) serwują `noindex` od 12 dni, a 12 URL-i bloga trzyma werdykt „Błąd serwera (5xx)" z **2026-05-15** i ma przez to **dokładnie 0 wyświetleń** — podczas gdy 6 zdrowych artykułów zebrało 1 532.
5. **Popyt jest w typach, których nie mamy:** LED + citylight + transport + totem = **3 315 wyświetleń (39% popytu) przy 18 nośnikach (2,2% podaży)**, przy 768 billboardach (92,9% podaży) obsługujących 24–33% popytu — a marketplace w 91 dni dowiózł **10 kontaktów ze 115 wejść na karty nośników (8,7%)** i **zero kliknięć w telefon od 1 lipca**.

---

## 2. Ruch organiczny — stan i trend

### 2a. Liczby bazowe (`q3m__date.json`, 91 dni)

| Okres | Kliki | Wyświetlenia | CTR | Poz. ważona |
|---|---:|---:|---:|---:|
| 26.04–25.07 (91 dni) | **167** | **10 233** | 1,63% | **31,3** |
| kwiecień (5 dni) | 10 | 187 | 5,35% | — |
| maj | 89 | 4 841 | 1,84% | — |
| czerwiec (deindeks) | 40 | 1 147 | 3,49% | — |
| lipiec 1–25 | 28 | 4 058 | **0,69%** | — |
| **last28** (28.06–25.07) | **30** | **4 212** | 0,71% | — |
| **prev28** (31.05–27.06) | **38** | **1 099** | 3,46% | — |

Wyświetlenia **+283%**, kliki **−21%**, CTR **−79%**. To jest napięcie do rozstrzygnięcia.

### 2b. ROZSTRZYGNIĘCIE: sukces czy porażka? — **sukces recovery, mierzony złą metryką**

Kluczowa obserwacja: wymiar **zapytań** w GSC anonimizuje frazy rzadkie, czyli dokładnie ten długi ogon, który recovery odbudowuje. `q3m__query` łapie **112 ze 167 klików** — 55 klików (33%) jest niewidocznych. Dlatego analiza po frazach pokazuje „płasko 2/0/2/2 na tydzień", a analiza po **stronach** pokazuje coś innego:

| Segment | prev28 kliki | last28 kliki | Δ |
|---|---:|---:|---|
| **home (≈ brand)** | **26** | **7** | **−73%** |
| blog | 1 | 7 | +600% |
| kategorie | 2 | 6 | +200% |
| kombinacje typ×miasto | 6 | 5 | −17% |
| leafy | 3 | 5 | +67% |
| **razem NIE-home** | **12** | **23** | **+92%** |
| **RAZEM** | 38 | 30 | −21% |

Tygodniowo (`analityk/w1..w4_p.json`), kliki poza home: **1 → 2 → 3 → 17**. W tygodniu 19–25.07 osiemnaście klików rozłożyło się na 18 różnych URL-i po jednym — czyli klasyczny długi ogon, nie jeden hit.

Rachunek brandu domyka obraz: fraza `reklamap` = **107 klików / 195 wyświetleń** w 3 mies., z czego **104 na `/`** (`q3m__query_page`). W last28 brand dał **5 klików**, w prev28 **27**.

**Odpowiedź wprost:**
- **Non-brand: sukces.** ~11 → ~25 klików na 28 dni (+127%), trend tygodniowy rosnący, całkowicie w segmentach treściowych (blog +6, kategorie +4, leafy +2).
- **Brand: realny spadek, ale to nie SEO.** Kliki brandowe 27 → 5 mierzą, ilu ludzi usłyszało o ReklaMap i wpisało nazwę w Google. Koreluje 1:1 z GA4: sesje **12,7/dzień (maj) → 3,7/dzień (lipiec), −71%**, Direct = osobiste cold calle. Spadek klików to spadek wolumenu akwizycji telefonicznej, nie rankingu.
- **CTR 3,46% → 0,71% to czysta zmiana mieszanki.** Prev28 to mała, brandowa baza (1 099 wyśw., 25,5% CTR na home); last28 to 4 212 wyświetleń długiego ogona na średniej pozycji 31. CTR jest tu funkcją pozycji, nie jakości snippetu — policzone z `q3m__page.json`: poz. 47,4 → 0%; 34,4 → 0,29%; 30,6 → 0,43%; 26,3 → 0,45%; 16,8 → 2,04%; 19,3 (home/brand) → 17,67%. Monotonicznie.
- **Ale sukces jest kruchy i mały w wartościach bezwzględnych:** 25 klików non-brand na 28 dni to <1/dzień. I nie tłumaczy zerowego popytu — patrz §7.

### 2c. Czy poprawa pozycji 59,5 → 26,4 jest trwała? Częściowo.

Rozkład wyświetleń wg pasm pozycji, tydzień po tygodniu (`analityk/w1..w4_q.json`):

| Tydzień | Wyśw | poz. 1–10 | poz. 10–20 | poz. 20–50 | poz. 50+ |
|---|---:|---:|---:|---:|---:|
| w1 28.06–04.07 | 435 | 25 (6%) | 18 | 218 | **174 (40%)** |
| w2 05–11.07 | 743 | 24 | 52 | 356 | 311 (42%) |
| w3 12–18.07 | 1 012 | 40 | 105 | 667 | 200 (20%) |
| w4 19–25.07 | 1 258 | **31 (2%)** | **146** | 1 029 (82%) | **52 (4%)** |

Trwały jest **wyłącznie przyrost w paśmie 10–20 (18 → 146 wyśw./tydz., +711%)**. Pasmo 1–10 stoi (25 → 31). Spadek średniej z 59,5 na 26,4 to w ~85% zanik ogona 50+ (174 → 52), czyli artefakt mieszanki — jeśli w sierpniu wejdzie nowa partia fraz na poz. 60+, średnia „się pogorszy" bez żadnej realnej zmiany.

**Zmiana KPI (do wdrożenia od następnego przeglądu):** przestać raportować średnią pozycję. Mierzyć tygodniowo: (a) wyświetlenia w paśmie 1–10, (b) w paśmie 10–20, (c) **kliki na stronach innych niż `/`** (proxy non-brand odporne na anonimizację), (d) osobno kliki brandowe jako miarę akwizycji offline.

---

## 3. Wisienki SEO — 43 frazy w poz. 4,5–20,5, posortowane wg potencjał/wysiłek

Baza: `q3m__query` × `q3m__query_page` × prod API (podaż) × sitemapa. Razem **1 238 wyświetleń, 4 kliki**.

### XS — strona indeksowalna + podaż ≥3, wystarczy meta/treść (4 frazy, 166 wyśw.)

| Fraza | Wyśw | Poz | Strona rankująca | Podaż |
|---|---:|---:|---|---|
| `reklama mobilna bydgoszcz` | 107 | 15,4 | `/powierzchnie-reklamowe/reklama-mobilna` | 6 w kraju, **0 w Bydgoszczy** |
| `billboardy koszalin` | 30 | 17,2 | `/powierzchnie-reklamowe/koszalin` | **70** |
| `billboardy dzierżoniów` | 19 | 18,9 | `/powierzchnie-reklamowe/billboardy/dzierzoniow` | 17 (ale **1 wolny**) |
| `billboardy sosnowiec` | 10 | 19,6 | `/powierzchnie-reklamowe/sosnowiec` | 21 |

**To jedyne 4 frazy w całym serwisie, gdzie praca na treści ma pod sobą realną podaż i indeksowalną stronę.** Wszystkie 4 potwierdzone jako prerenderowane (`curl -A Googlebot`: `/koszalin` = 186 000 B, `index, follow`, własny title).

### S — treść już istnieje, wystarczy sekcja/nagłówek (7 fraz, 108 wyśw.)

`nośniki reklamowe kraków outdoor` 25/**8,1** · `reklama na tramwajach kraków koszt` 19/19,4 · `reklama outdoorowa kraków` 16/18,6 → wszystkie na `blog/lokalizacje/reklama-outdoor-krakow` (3 wisienki na jednym artykule, poz. 8,1 to najbliżej top-5 w całym serwisie) · `reklama outdoorowa gdansk` 20/15,9 → artykuł Gdańsk · `billboard cennik` 12/16,5 i `bilbordy koszt budowy` 10/7,8 → `blog/poradniki/billboard-reklama` · `wynajem billboardów – porównaj warunki` 10/6,8 → `/faq`.

**Bonus z Binga (§8 raportu Bing):** ten sam artykuł krakowski zbiera w Google **~84 wyświetlenia na klastrze transportu miejskiego** (tramwaje/autobusy/MPK, poz. 19–41) — temat, którego dotyka mimochodem i który nie ma własnej strony.

### M — strona poza sitemapą albo leaf usunięty (4 frazy, 100 wyśw.)

`reklama citylight olsztyn` 69/**11,8** (best 5,7) → rankuje leaf `citylighty/olsztyn/…-32`, **którego nie ma już w bazie** · `bilbordy reklamowe warszawa srodmiescie` 9/10,7 (best **1,8**) → leaf id 36, usunięty · `billboard 6x3` 12/13,4 → slug zmieniony bez 301.

### L — podaż < 3, SEO tego nie odblokuje (28 fraz, **864 wyśw. = 69,8% puli wisienek**)

`powierzchnie reklamowe białystok` 119/20,5 (podaż **0**) · `powierzchnie reklamowe lublin` 121/8,1 (1) · `totemy reklamowe wrocław` 100/11,4 (**0 totemów w całym kraju**) · `reklama na ekranach led poznan` 87/19,3 (0) · `reklama na ekranach led kraków` 76/13,8 (0) · `reklama tranzytowa poznan` 41/13,0 (0) · `citylight wrocław` 38/15,5 (0)…

**To główny wniosek raportu:** 70% gotowego popytu w strefie klikalnej stoi nad stronami, które z definicji dostają `noindex` (próg `THIN_PAGE_THRESHOLD=3`, `frontend/src/utils/listingsSeo.ts:12`). **Żadna praca Stratega ani Pisarza tego nie odblokuje — odblokowuje to nośnik w bazie.**

---

## 4. Treści: co działa, co kuleje

### Działa

- **`blog/poradniki/billboard-reklama` — 814 wyśw. / 2 kliki / poz. 23,5**, sam robi **47,8% wyświetleń blogowych**, a w tygodniu 19–25.07 **391 wyśw. = 23,5% wyświetleń serwisu**. To jednocześnie największa koncentracja ryzyka — 1 artykuł niesie połowę bloga.
- **Silos `lokalizacje`** — 8 artykułów z ruchem, 598 wyśw. / 3 kliki / poz. 27,8, rośnie równomiernie (Kraków 238, Poznań 122, Gdańsk 115, Łódź 107).
- **`prawo-i-regulacje` — najlepsza średnia pozycja (18,9)** przy zaledwie 2 artykułach z ruchem (104 wyśw.). Najwyższy zwrot z dosypania treści.
- **`poradniki/jak-zarobic-na-wynajmie…` — CTR 5,26%** (1 klik / 19 wyśw. / poz. 12,9), najlepszy CTR na blogu. Jedyny artykuł podażowy z jakimkolwiek wynikiem.

### Kuleje

- **Silos `trendy`: 4 artykuły, 0 wyświetleń przez 3 miesiące.** W tym `telebim-ekran-led-reklama` i `totem-reklamowy` — leżące dokładnie na typach o największej luce popytowej (LED 1 267 wyśw., totem 154). Uwaga: 2 z tych 4 są w zbiorze 12 URL-i zamrożonych w werdykcie 5xx (§6a) — problem może być techniczny, nie redakcyjny.
- **16 z 33 blogowych URL-i ma 0 wyświetleń**, w tym `lokalizacje/reklama-outdoor-warszawa` i `reklama-outdoor-wroclaw` — dwa największe rynki.
- **Huby `/ekrany-led` (569 wyśw. / poz. 34,1) i `/citylighty` (633 / 35,8)** to 2. i 3. najczęściej wyświetlana strona serwisu — obsługiwane przez **5 i 12 nośników**. Zapytania są miejskie (`citylight gdynia`, `ekrany led łódź`), a użytkownik dostaje ogólnopolską listę kilku pozycji.
- **`/powierzchnie-reklamowe/poznan`: +513 wyśw. w 28 dni na pozycji 67, 0 klików** — czysta strata ekspozycji.

---

## 5. Białe plamy — popyt bez podaży i bez strony

### 5a. Wg typu nośnika (`q3m__query` × prod API, 827 ogłoszeń)

| Typ | Popyt (wyśw.) | % popytu typowanego | Podaż | % podaży |
|---|---:|---:|---:|---:|
| billboard | 2 070 | 33,1% | **768** | **92,9%** |
| **led_screen** | **1 267** | 20,3% | **5** | 0,6% |
| **citylight** | **1 222** | 19,6% | **12** | 1,5% |
| **transport** | **883** | 14,1% | **1** | 0,1% |
| mobile | 515 | 8,2% | 6 | 0,7% |
| totem | 154 | 2,5% | **0** | 0,0% |
| banner | 130 | 2,1% | 9 | 1,1% |
| wall | **8** | 0,1% | **24** | 2,9% |

**Non-billboard = 4 179 wyśw. (66,9% typowanego popytu) przy 59 nośnikach (7,1% podaży).** W last28 trend się pogłębia: citylight 526, LED 377, transport 372 vs billboard 626. `wall` to jedyny typ z nadpodażą (24 szt. / 8 wyśw.) — nie inwestować.

### 5b. Wg miasta

11 dużych miast = **5 806 wyśw. (68,5% popytu) przy 40 nośnikach (4,8% podaży)**; 6 z nich ma **zero** nośników (Gdańsk 943 wyśw., Kraków 658, Łódź 631, Bydgoszcz 279, Białystok 227, Szczecin 15). Odwrotnie: **6 hubów kłodzkich to 253 nośniki (30,6% bazy) → 39 wyświetleń i 0 kliknięć w 3 miesiące** = 0,15 wyśw./nośnik, wobec 34,9 w Poznaniu — **przewaga 227×**.

### 5c. Najtańsze odblokowania (wyśw. ÷ brakujące nośniki)

| # | URL | Jest | Brakuje | Wyśw. 3m |
|---|---|---:|---:|---:|
| 1 | `/powierzchnie-reklamowe/warszawa` | 2 | **1** | 472 |
| 2 | `/reklama-w-transporcie/gdansk` | 0 | 3 | 414 |
| 3 | `/reklama-w-transporcie/poznan` | 0 | 3 | 370 |
| 4 | `/ekrany-led/poznan` | 0 | 3 | 351 |
| 5 | `/powierzchnie-reklamowe/lublin` | 1 | **2** | 174 |
| 6 | `/billboardy/warszawa` | 1 | **2** | 153 |
| 7 | `/powierzchnie-reklamowe/bialystok` | 0 | 3 | 170 |
| 8 | `/banery/lodz` | 0 | 3 | 166 |
| 9 | `/powierzchnie-reklamowe/bydgoszcz` | 0 | 3 | 161 |
| 10 | `/sciany-reklamowe/poznan` | 2 | **1** | 53 |
| | **suma** | | **24 nośniki** | **2 484 wyśw. = 29,3% ruchu serwisu** |

Te same 24 nośniki dołożone w Kłodzku dodają **~1,4 wyświetlenia**.

### 5d. Katowice — jedyna dźwignia bez rozmowy sprzedażowej

**140 nośników w promieniu 30 km, 8 na stronie miasta, 266 wyśw. popytu.** Mechanizm promienia już istnieje (`lat/lng/radius` w `AdvertisementController::buildFilteredQuery`). Ta sama dźwignia NIE zadziała dla Białegostoku (0 nośników w promieniu 50 km) ani Szczecina (najbliższy 103 km).

### 5e. Zero fraz podażowych w 705 zapytaniach

Filtr intencji podażowej (`zarobic|wynajme|wydzierzaw|udostepni|oplaca sie|mam dzialk|postawic billboard|dochod|pasyw`) na `q3m__query`: **0 fraz, 0 wyświetleń**. Dwa artykuły pisane pod właściciela nośnika zebrały łącznie **12 wyświetleń**. `/dodaj-powierzchnie-reklamowa` ma 24 wyświetlenia — wszystkie z frazy brandowej. **W fazie budowy podaży SEO nie dostarcza ani jednego wejścia od właściciela nośnika.**

---

## 6. Higiena indeksu — co realnie kosztuje wyświetlenia

### 6a. 12 URL-i bloga zamrożonych w werdykcie „Błąd serwera (5xx)" z 2026-05-15

`pageFetchState = SERVER_ERROR` dla 12 ze 158 URL-i nieleafowych, **wszystkie z `lastCrawlTime = 2026-05-15`** — jeden dzień, jeden batch, 71 dni temu (epoka wygasłego prerender.io, CLAUDE.md datuje jego padnięcie na 18.05). Na żywo **wszystkie zwracają 200 dla każdego UA** (218/218 pobrań jako Googlebot = 200).

| Grupa | Wyśw. 3 mies. |
|---|---:|
| 12 URL-i zamrożonych w 5xx | **0** |
| 6 zdrowych artykułów (kontrola) | **1 532** |

Wśród zamrożonych: `/blog/poradniki`, `/blog/prawo-i-regulacje` (kategorie), `reklama-outdoor-warszawa`, `reklama-outdoor-wroclaw`, `telebim-ekran-led-reklama`, `totem-reklamowy`. **`lastmod` nie jest przyczyną** — 11 z 12 ma identyczny `lastmod=2026-07-13` co zdrowy `billboard-reklama`, pobrany 16.07 i zindeksowany. Google po prostu nie ponawia.

### 6b. Sitemapa frontu rozjechana z generatorem — publikacja bez deployu = `noindex`

`reklamap.pl/sitemap.xml` = **983 `<loc>`** (plik z builda, `Last-Modified: Mon, 13 Jul 2026 11:52:28 GMT`) vs `api.reklamap.pl/sitemap.xml` = **987**. Różnica = 4 URL-e, wszystkie zgłoszone Google'owi przez zarejestrowaną w GSC sitemapę `api`:

```
/blog/prawo-i-regulacje/pozwolenie-na-tablice-reklamowa   noindex, follow, 7 929 B, title strony głównej
/blog/prawo-i-regulacje/reklama-bez-pozwolenia-kary       noindex, follow  (oba OPUBLIKOWANE, ✅ zrecenzowane)
/powierzchnia-reklamowa/billboardy/jablonowo/…-997        noindex, follow
/powierzchnia-reklamowa/billboardy/nowa-wies-elcka/…-998  noindex, follow
```

**Przyczyna systemowa:** `BlogPost::saved` → `Cache::forget('sitemap_xml')` (`backend/app/Models/BlogPost.php:39`) wpuszcza URL do sitemapy natychmiast, ale prerender powstaje wyłącznie w `frontend/deploy.sh`. **Czas do indeksacji nowej treści = czas do następnego deployu frontu; dziś 12 dni.** `seo:tripwire` tego nie łapie z definicji — czyta sitemapę z frontu (`SeoTripwire.php:74`), czyli ten sam zamrożony plik.

### 6c. 32 usunięte ogłoszenia zwracają HTTP 200 (soft-404) — i były najlepiej rankującymi stronami

Porównanie 71 leafów z `q3m__page` z prod API po ID: **32 URL-e nie istnieją już w bazie** (ID 1–47, pierwotny seed) — **411 wyświetleń, 7 klików**, pozycje 2,7–8,0 (id 34 → 2,7; id 28 → 3,9; id 32 → 5,9 przy 89 wyśw.). To **7 z 12 wszystkich klików leafowych w kwartale**. Produkcja zwraca dla nich 200 + szkielet, nie 404/410. Dodatkowo 2 leafy zmieniły slug przy tym samym ID (bez 301).

To tłumaczy też zapaść jedynego typu rich resultu: **PRODUCT_SNIPPETS 157 wyśw. / 4 kliki / CTR 2,55%** (1,6× średnia serwisu) w 3 mies., ale **22 wyśw. / 0 klików w last28** — spadek 7×, zbieżny czasowo z usunięciem leafów. Schema `Product` istnieje wyłącznie na stronach ogłoszeń.

### 6d. Pokrycie indeksu i stan niezmienników

| Klasa | n | Zindeksowane | % |
|---|---:|---:|---:|
| home + hub + statyczne | 7 | 6 | 86% |
| typy + miasta | 61 | 24 | 39% |
| kombinacje typ×miasto | 57 | 21 | 37% |
| artykuły bloga | 28 | 16 | 57% |
| **kategorie bloga** | **4** | **0** | **0%** |
| leafy (próba 25 z 825) | 25 | 7 | 28% |

Szacunek całości: **~299 z 983 URL-i (~30%)**.

**Niezmienniki z CLAUDE.md TRZYMAJĄ** i nie należy ich ruszać: 218/218 stron = 200 + `index, follow`; 158/158 canonical = własny URL (0 rozjazdów); 60/60 leafów z seedem `__INITIAL_STATE__`; próg `THIN_PAGE_THRESHOLD=3` spójny w 3 miejscach — weryfikacja empiryczna dała **0 konfliktów w obie strony**; 301 www→non-www działa ze ścieżką.

**Jedyna realna luka w seedzie:** 5 tras bloga (`/blog`, `/blog/{kategoria}`) pobiera dane, a **nie ma seedu** — `main.ts:50-72` (`__collectSSRState`) nie zbiera listy postów, `prerender.mjs:116-117` (`needsState`) nie obejmuje `/blog`, a `BlogPage.vue:128-138,151-153` przy nieudanym fetchu ustawia `noindex`. To ta sama klasa awarii, którą dla listingów zamknięto bramką `hasLoaded`. Efekt: 0/4 kategorii bloga zindeksowanych.

---

## 7. Sygnały produktowe (dla Biznesowego)

### 7a. Marketplace w liczbach — 91 dni, produkcja

```
332 użytkowników → 257 na home → 103 na listingach → 115 na kartach nośników
                                                    →  10 podjęło kontakt (7 telefon + 3 formularz)
```

- **10 / 115 = 8,7%** konwersji karty w kontakt · **1 kontakt na 9,1 dnia**
- **Ostatni `contact_phone_click` = 2026-06-30.** Od 1 do 25 lipca: **zero kliknięć w telefon**.
- Lejek sekwencyjny (`runFunnelReport`, 26.05–25.07): home 108 → listingi 59 (30,6%) → detal 35 (35,6%) → kontakt 3 (8,6%); **kumulatywnie 2,8%**.
- Lejek podaży: home 113 → `/dodaj` 24 (**−80,5%**) → pierwszy ukończony krok 15 (−37,5%) → publikacja 14 (−6,7%). **Formularz NIE jest wąskim gardłem** — kto ukończy krok 1, w 93,3% publikuje.
- **Home to wąskie gardło obu lejków:** 47,7% odsłon, 77,4% userów, 13,0 s na odsłonę, bounce 0,48, przepuszcza dalej 19,5–30,6%. Dla kontrastu `/dodaj-powierzchnie-reklamowa` trzyma **196,8 s/odsłonę** przy engagementRate 0,92.

### 7b. Dwa zapytania ofertowe (13.07 Warszawa Śródmieście, 21.07 TVL dolnośląskie+lubelskie) — SYGNAŁ DIAGNOSTYCZNY

**Ramka interpretacyjna:** ReklaMap to platforma, nie broker. Founder nie odpisuje na te zapytania i to jego świadoma decyzja. Traktuję je wyłącznie jako **dwa pomiary tego, gdzie samoobsługa pękła** — każde z nich ma w danych domknięty, policzalny łańcuch przyczynowy.

**Zapytanie 1 — Warszawa Śródmieście (13.07). Diagnoza: brak podaży, nie brak UI.**
- Na prodzie w Warszawie są **2 nośniki** (1 `active` baner Al. KEN, 1 `reserved` billboard Połczyńska); w promieniu 15 km od Śródmieścia — **5** (4 billboardy + 1 baner).
- `/powierzchnie-reklamowe/warszawa` ma 2 nośniki → jest **poniżej progu thin** → `noindex`, poza sitemapą, poza prerenderem. Potwierdzone w GSC: „Strona wykluczona za pomocą tagu noindex", `robots=ALLOWED`, ostatnie skanowanie 2026-07-22.
- Ta sama strona zebrała mimo to **452 wyświetlenia** w 3 mies. i jest #1 na liście najtańszych odblokowań (**brakuje 1 nośnika**).
- Wtórnie: klient chcący kilka nośników nie ma jak zbudować koszyka — `/porownaj` ma limit 5 i **ograniczenie do jednego typu** (`usePreferencesStore.ts:63,74`), a `ComparisonPage.vue` nie ma żadnego CTA „zapytaj o wszystkie" (linie 335-471). Nie ma endpointu zbiorczego (`routes/api.php:43-44` — tylko per-ogłoszenie). **Ale to nie jest przyczyna utraty tej sprawy** — przy 5 nośnikach w promieniu 15 km zapytania nie dało się obsłużyć niezależnie od UI.

**Zapytanie 2 — TVL, dolnośląskie + lubelskie (21.07). Diagnoza: filtr województwa zwraca 0 przy 407 realnych nośnikach.**
- `AdvertisementController.php:257` robi exact match `where('region', $request->input('region'))`. Pomiar na żywym prod API (`X-App-Key` z `backend/.env`), wszystkie 16 id z frontowego słownika:
  ```
  dolnoslaskie=0  kujawsko-pomorskie=0  lubelskie=32  lubuskie=0   lodzkie=0
  malopolskie=0   mazowieckie=14        opolskie=0    podkarpackie=0  podlaskie=0
  pomorskie=0     slaskie=135           swietokrzyskie=0  warminsko-mazurskie=0
  wielkopolskie=0 zachodniopomorskie=80
  ```
- **13 z 16 województw zwraca 0.** Filtr sięga dziś **261 z 827 ogłoszeń (31,6%)**. Konkretnie dla tego zapytania: `dolnoslaskie` → **0 ofert, przy ~407 nośnikach realnie leżących w dolnośląskim** (bbox z lat/lng); `lubelskie` → 32 (działa).
- Pole `region` jest puste w **480/827 (58%)**, a wypełnione w dwóch formatach naraz (`śląskie` 135 vs `województwo śląskie` 14) — Nominatim zwraca raz tak, raz tak.
- Fix jest **napisany i przetestowany** w izolowanym worktree (`scratchpad/fix-region.patch`, backend 4/4, front 143/143); podnosi zasięg do 347/827 (42%), reszta wymaga kanonizacji 480 pustych rekordów (gotowa komenda `region:canonize`, dry-run domyślnie, UPDATE w miejscu, bez ruszania `updated_at`).

**Wspólny mianownik obu zapytań — dlaczego wylądowały na `kontakt@reklamap.pl`:**
- **683 z 827 wystawców (82,6%) zadeklarowało e-mail jako preferowany kanał kontaktu** (`contact_preference`), a na całym serwisie **nie ma ani jednego `mailto:` do wystawcy** — cztery istniejące (`ContactPage.vue:138`, `AppFooter.vue:56`, `RegulaminPage.vue:222`, `FaqPage.vue:362`) prowadzą na `kontakt@reklamap.pl`, przy czym stopka jest na **każdej** podstronie.
- Formularz na karcie nośnika odpalił **3 userów w 3 miesiące** i ma 2 pola (e-mail + treść ≥10 znaków) — za mało, by wystawca odpowiedział ofertą, a nie pytaniem.
- `AdDetailPage.vue:242` generuje na 827 ogłoszeniach meta-description obiecujący: *„skontaktuj się bezpośrednio z wystawcą — bez prowizji i pośredników"*. Bezpośredniego kanału nie ma.
- Ścieżka `POST /api/listings/{id}/contact` jest architektonicznie poprawna (`Mail::to($ad->owner_email)` + `replyTo` nadawcy, founder poza pętlą) — problem jest wyłącznie w tym, że interfejs do niej nie prowadzi.

**Zapytanie ofertowe na skrzynkę foundera jest przewidywalnym wynikiem projektu interfejsu, nie przypadkiem.** Dodatkowo: ta ścieżka jest **niemierzona** — `analytics.mainContactFormSubmit` (`utils/analytics.ts:64`) nie jest wywołane ani razu w całym froncie, więc formularz `/kontakt` nie generuje żadnego zdarzenia GA4. Gdyby nie skrzynka foundera, o obu tych zdarzeniach nie wiedzielibyśmy w ogóle.

### 7c. Status `reserved` — 463/827 (56,0%) — decyzja do foundera, nie do agenta

Profil w 100% jednorodny: `type=billboard`, `variant=standard`, `offer_type=agency`, `available_from=NULL` (wszystkie 463); dwa batche importu (2026-06-15 → 374 szt., 2026-06-10 → 88 szt.); **od 2026-06-18 żaden rekord nie zmienił statusu**; w schemacie **nie istnieje pole `reserved_until`** (grep po `backend/app`, `backend/database/migrations` — 0 trafień).

- Za realnymi rezerwacjami: status jest **mieszany wewnątrz tej samej paczki** (batch 15.06: 374 res + 34 act; Kłodzko 119 res / 18 act) — hurtowy import dałby 100%.
- Za stanem zamrożonym: brak daty końca, 6 tygodni bez rotacji (typowa kampania OOH to 2–4 tyg.), 0 rezerwacji poza dwoma oknami importu.
- **Co widzi użytkownik:** na 55 stronach miast w sitemapie leży 730 kafelków, **433 (59%) z badge „Zarezerwowane"**; 5 miast ma 100% zarezerwowanych (Ząbkowice Śląskie 31/31, Bielawa 12/12, Braszowice 9/9, Łagiewniki 9/9, Szczytna 8/8). Efektywna podaż to **349 pozycji „Wolne" (42,2%)**, nie 827.
- **Co wisi na tej decyzji:** gdyby `reserved` przestało liczyć się do progu thin, pokrycie spada z **55 → 28 stron miast** i **57 → 29 kombinacji**, a typ `billboardy` z 768 → 305. To połowa mapy kategorii.
- **Adwersaryjnie sprawdzone: `reserved` NIE szkodzi indeksacji leafów.** Próbka GSC n=27: reserved 4 zindeksowane / 5 niezindeksowanych; active 3 / 10. Różnica nieistotna.

**Pytanie do foundera (jedno, zamknięte):** czy `reserved` w paczkach z 10 i 15 czerwca odwzorowuje zajętość na dzień importu (czyli jest dziś przeterminowane), czy oznacza „powierzchnia w portfolio agencji, nie do wynajęcia przez ReklaMap"? Do czasu odpowiedzi **nie ruszać progu** (niezmiennik CLAUDE.md: `reserved` zostaje w sitemapie).

### 7d. Jakość podaży — braki pól nie są problemem, unikalność jest

100% cena, 98,1% wymiary, 100% zdjęcie główne, 100% lat/lng. Ale: **456 unikalnych tytułów na 827** (516 ogłoszeń dzieli tytuł), 598 unikalnych opisów, **345 ogłoszeń (41,7%) dzieli parę tytuł+opis**. Trzy URL-e sprawdzone jako Googlebot (`…biala-podlaska-{299,302,303}`) mają **identyczny `<title>`, meta-description i `<h1>`**; HTML 67 711 / 67 710 / 67 711 B. Koreluje z 56% leafów w stanie „wykryta, niezindeksowana" (próbka n=27). **Import kolejnych 400 billboardów tym samym szablonem pogłębi problem.** `is_verified` = 0 na wszystkich 827.

---

## 8. Kanały promocji — gdzie inwestować

Prod (`hostName=reklamap.pl`), 91 dni:

| Kanał | Sesje | Userzy | Zaang. | keyEvents | key/user | detal→kontakt | Werdykt |
|---|---:|---:|---:|---:|---:|---:|---|
| Direct (cold calle) | 450 | 231 | 0,45 | 30 | 13,0% | 3/29 = 10,3% | **dosypać — jedyny kanał podaży** |
| Organic Search | 231 | 80 | 0,56 | 5 | 6,3% | **4/17 = 23,5%** | **dosypać — najlepszy kanał POPYTU** |
| Email (outreach) | 45 | 17 | **0,71** | 3 | **17,6%** | 0/8 = 0% | dosypać, ale **zmienić landing** |
| Referral | 15 | 3 | 0,53 | 0 | 0% | 0/1 | nie kanał — webmail i podglądy linków |
| Organic Social | 7 | 7 | 0,57 | 0 | 0% | — | nieprzetestowany |

**Trzy rzeczy psują tę tabelę i trzeba je znać:**

1. **Jedynym key eventem w property jest `add_listing_success`** (38 keyEvents, wszystkie na nim; `contact_phone_click` 39 zdarzeń i `contact_form_submit` 4 mają keyEvents = 0). Kolumna „konwersje" w GA4 mierzy **wyłącznie podaż**. Po odsłonięciu surowych danych obraz się odwraca: **Organic Search dostarcza 35 z 39 kliknięć w telefon (90%)** i konwertuje detal→kontakt 2,3× lepiej niż Direct — a w raportach tego nie widać.
2. **18,9% odsłon w produkcyjnej property to ruch deweloperski** (`localhost` 153 odsłon, `127.0.0.1` 336 — razem 489 z 2 584). `G-0ZL0NS8F9W` jest na sztywno w `frontend/index.html:138,144` bez bramki środowiskowej. Każda dotychczasowa analiza kanałów bez filtra `hostName` jest przesunięta o ~19%.
3. **Outreach mailowy nie ma landinga:** 23 z 45 sesji Email (**51,1%**) ląduje na generycznej home, kolejne 6 na pustym hubie `/powierzchnie-reklamowe`. Strona `/dodaj-powierzchnie-reklamowa` — jedyna z realną gęstością konwersji (3 keyEvents na 10 sesji, 196,8 s/odsłonę) — dostaje z maila **0 sesji**. Kanał o najwyższym zaangażowaniu (0,71) marnuje ten kapitał na stronie, która przepuszcza dalej 19,5%.

**Kanał zerowy: linki przychodzące.** Bing zna **ZERO** domen linkujących (`GetLinkCounts`, `GetUrlLinks`, `GetConnectedPages` — wszystkie puste), a GA4 potwierdza niezależnie: 100% referrali to webmail (`zasobygwp.pl` 11 sesji/1 user, `poczta.onet.pl`, `poczta.wp.pl`), Facebook i podglądy linków w Teams. **Zero domen redakcyjnych.** Przy średniej pozycji 31,3 i zerowym autorytecie domeny dalsze skalowanie samą treścią ma malejący zwrot — treść dowozi wyświetlenia (10 233), nie pozycje.

**Bing jako kanał: 150 wyśw. / 3 kliki / 91 dni**, `bing/organic` = 2 sesje vs `google/organic` 222 (**0,9% organiki**). Indeks Binga 418 z 983 URL-i (42,5%), tempo crawla **34,4 str./dobę** = pełny recrawl ~29 dni, `CrawlBoostAvailable: false`. IndexNow (~70 linii kodu, plan w `raport-bing.md §7.3`) skróciłby czas do indeksacji nowego nośnika z ~29 dni do <24 h — **ale zysk ruchowy to uczciwie ~2–3 kliki miesięcznie**. To porządkowanie, nie dźwignia.

---

## 9. Wydajność (CWV) — zmierzona po raz pierwszy, i nie jest przyczyną słabego CTR

12 przebiegów Lighthouse 13.4.1 na żywym prodzie + niezależna sonda `PerformanceObserver`.

**Co jest zdrowe:** TTFB **16–21 ms na wszystkich 12 przebiegach** (LiteSpeed/Hostido bez zarzutu), TBT 0–257 ms, brak przekierowań, kompresja OK. **Serwer nie jest wąskim gardłem.**

**Co jest zepsute — nowe znalezisko, nie było w audycie 07.07:** **CLS 0,809–0,963 na 10 z 12 przebiegów** (próg „zły" = 0,25). Mechanizm zmierzony klatka po klatce: `app.mount('#app')` (`main.ts:43`) nie czeka na `router.isReady()`, a trasy są leniwe (`router.ts:31,59,108`) — prerenderowana treść kategorii **znika** (24 kafle → 0, stopka z y=2193 na y=100, HTML 197 779 → 86 116 znaków) i wraca po **0,55 s (szybkie łącze) do 13,7 s (slow-4G+4× CPU)**. Kontrola negatywna potwierdza mechanizm: **home ma CLS 0,000**, bo `HomePage` jest importowane statycznie (`router.ts:16`).

**Bajty do odzyskania (mierzone, nie szacowane):** logo `logo-text.webp` to **8056×3303 px / 465 KB** pobierane na **każdej** podstronie, także mobilnej, gdzie CSS je chowa (`AppHeader.vue:1173-1176`, `display:none` nie anuluje `src`) — 21% wagi strony bloga. reCAPTCHA: **852 KB + 793–918 ms render-blockingu na mobile + 429 ms CPU**, przy zerowym użyciu przed interakcją. Google Fonts przez `@import` (`style.css:1`): łańcuch render-block **791–831 ms**, brak `preconnect` do `fonts.gstatic.com`. Brak `srcset`: **1 755 KiB** do odzyskania na kategorii mobile.

**Czy CWV to przyczyna CTR 0,29% na kategoriach? NIE.** Trzy argumenty: (a) CTR jest monotoniczną funkcją pozycji (§2b) — 0,29% przy poz. 34,4 to dokładnie krzywa CTR trzeciej strony wyników; (b) CWV nie wchodzi do snippetu w SERP-ie, a sygnał Page Experience liczy się z danych polowych, których ta domena przy ~8,5 sesji dziennie najpewniej w ogóle nie ma (do sprawdzenia w 30 s: GSC → Core Web Vitals); (c) **89,2% wyświetleń idzie z desktopu**, gdzie wyniki są dwa razy lepsze.

**Gdzie CWV JEDNAK ma znaczenie:** w lejku podaży. Direct = 467 sesji = osobiste cold calle foundera; właściciel nośnika, któremu founder właśnie powiedział „proszę wejść na reklamap.pl", **ogląda tę stronę na telefonie w trakcie rozmowy** — i widzi ją pustą przez pół sekundy do kilkunastu sekund. To jedyne uzasadnienie napraw XS/S, i wystarczające.

**Obalone pomiarem — NIE implementować:** `preconnect` do `api.reklamap.pl` (nie pojawił się ani razu w 12 przebiegach — prerender + seed zdejmują API z krytycznej ścieżki) oraz `preconnect` do kafli OSM (też ani razu; Lighthouse wskazał wyłącznie `fonts.gstatic.com` 153–172 ms i `www.google.com` 95–122 ms). Lazy-LCP potwierdzony **tylko dla kategorii na mobile**; leaf ma `lcp-discovery` = 1/1 — **nie ruszać**.

---

## 10. Rekomendacje — priorytet wg (wpływ / wysiłek)

### TIER 1 — zrobić w tym tygodniu (wysoki wpływ, koszt XS)

| # | Akcja | Odbiorca | Wysiłek | Oczekiwany efekt |
|---|---|---|---|---|
| 1 | **Deploy frontu** (`cd frontend && ./deploy.sh`, po `git pull` + `cache:clear` na backendzie — kolejność z CLAUDE.md) | użytkownik / dev | XS | 4 URL-e wychodzą z `noindex`, w tym 2 opublikowane artykuły; punkt odniesienia: zdrowy artykuł = ~255 wyśw./kwartał |
| 2 | **GSC → „Błąd serwera (5xx)" → Validate Fix** + „Poproś o zindeksowanie" dla `reklama-outdoor-warszawa`, `reklama-outdoor-wroclaw`, `reklama-zewnetrzna`, `tablica-reklamowa`, `/blog/poradniki` (akcja w UI, API tego nie robi) | użytkownik | XS | 12 URL-i z 0 wyświetleń; grupa kontrolna 6 zdrowych = 1 532 wyśw./kwartał → potencjał rzędu 1 000+ |
| 3 | **Oznaczyć `contact_phone_click` i `contact_form_submit` jako key events** (GA4 Admin → Events, bez zmian w kodzie) | dev / użytkownik | XS | popyt przestaje być niewidoczny w raportach; dziś kolumna „konwersje" mierzy wyłącznie podaż |
| 4 | **Zarejestrować `step_number` i `ad_type` jako custom dimensions** (GA4 Admin → Custom definitions) | dev | XS | odblokowuje rozbicie lejka podaży po krokach. **Rejestracja NIE odzyskuje historii** — każdy dzień zwłoki to trwała strata danych od 14.05 |
| 5 | **Filtr Internal Traffic na `hostName` localhost/127.0.0.1** (GA4 Admin → Data filters, tryb Active) | dev | XS | usuwa 18,9% zanieczyszczenia z każdej przyszłej analizy |
| 6 | **Check driftu sitemap w `seo:tripwire`** — porównać `<loc>` z sitemapy backendu i frontu, niepusta różnica = alarm (snippet w `raport-bing.md §6`) | architekt / dev | XS | zamyka klasę błędu #1 na stałe; dziś tripwire czyta tylko sitemapę frontu (`SeoTripwire.php:74`), więc z definicji driftu nie widzi |
| 7 | **Przegenerować `logo-text.webp` (8056×3303 → ~800×328) i `logo.webp` (1024 → 224)** | dev | XS | **−~500 KB na każde wejście na każdą podstronę**, zero ryzyka SEO |
| 8 | **Google Fonts: `@import` → `<link>` + `preconnect fonts.gstatic.com`** | dev | XS | −791…831 ms render-blockingu na mobile |
| 9 | **Naprawić 5 padających testów na `master`** (`ManagementTest` ×4 → 422 zamiast 200, `SearchAlertTest` → 302 zamiast 404; testy nieaktualne, kod OK) | dev | XS | dziś sieć bezpieczeństwa dla wszystkich poniższych fixów jest wyłączona; pre-commit hook jest omijany |

### TIER 2 — następne 2–4 tygodnie (wysoki wpływ, koszt S/M)

| # | Akcja | Odbiorca | Wysiłek | Oczekiwany efekt |
|---|---|---|---|---|
| 10 | **`router.isReady()` przed `mount()`** (`main.ts:43`) — z pełną ścieżką weryfikacji: tripwire + `curl -A Googlebot` + **GSC Live Test** na kategorii i artykule | dev / architekt | XS kod / **M weryfikacja** | CLS 0,88 → ~0,06; strona przestaje znikać na 0,5–13,7 s. Dotyka ścieżki krytycznej SEO — nie wchodzi bez Live Testu |
| 11 | **Fix filtra województwa** (patch gotowy: `scratchpad/fix-region.patch`, backend 4/4, front 143/143) + `region:canonize` dry-run → `--apply` → `--apply --geocode` | dev | M | 13 z 16 województw przestaje zwracać 0; zasięg filtra 31,6% → 42% (kod) → docelowo 100% (kanonizacja). To błąd, który zabił zapytanie TVL |
| 12 | **410 dla 32 usuniętych ogłoszeń + 301 dla 2 zmienionych slugów** — najpierw ustalić, czy nośniki realnie zniknęły; jeśli nie, przywrócić przez `updateOrCreate` pod TYMI SAMYMI ID | dev | M | koniec soft-404 na stronach, które miały pozycje 2,7–8,0 i 7 z 12 klików leafowych; szansa na powrót PRODUCT_SNIPPETS (CTR 2,55%, 1,6× średnia) |
| 13 | **Wpiąć 6 martwych helperów GA4** (`clickEmail`, `search`, `filterUsed`, `newsletterSubscribe`, `mainContactFormSubmit`, `addToComparison`) | dev | S | domyka trzy „brak danych": czego ludzie szukają, ilu trafiło w zepsuty filtr, ile zapytań idzie przez `/kontakt` |
| 14 | **Przenieść `analytics.viewAd` poza blok zależny od odpowiedzi API** (`AdDetailPage.vue:604-615`) | dev | S | `view_item` gubi dziś 37,4% wejść na kartę (115 userów vs 72 zdarzenia) — popyt raportowany o 1/3 za nisko |
| 15 | **Odrzucać HeadlessChrome/boty w `incrementViews`** (`AdvertisementController.php:699-719`) albo `setRequestInterception` w `prerender.mjs` | dev | S | licznik odsłon pokazywany wystawcy jest zawyżony **13,7×** (591 wg `stats.php` vs 43 wg GA4). Do czasu naprawy nie prezentować go jako miary zainteresowania |
| 16 | **Outreach mailowy → landing `/dodaj-powierzchnie-reklamowa` z `utm_content`**, zamiast na `/` | biznesowy / użytkownik | S | kanał o najwyższym zaangażowaniu (0,71) i najwyższej konwersji na usera (17,6%) przestaje marnować ruch na stronie przepuszczającej 19,5% |
| 17 | **Meta + treść na 4 frazach XS** (`/koszalin`, `/billboardy/dzierzoniow`, `/sosnowiec`, `/reklama-mobilna`) — **i tylko na nich** | architekt | XS | jedyne 4 miejsca, gdzie praca na meta ma pod sobą podaż i indeksowalną stronę; 166 wyśw. na poz. 15–20 |
| 18 | **Rozbudowa `blog/lokalizacje/reklama-outdoor-krakow`** o sekcje pod `nośniki reklamowe kraków outdoor` (25 wyśw., **poz. 8,1**), `reklama na tramwajach kraków koszt` (19/19,4), `reklama outdoorowa kraków` (16/18,6); to samo dla Gdańska (`reklama outdoorowa gdansk` 20/15,9) | strateg → pisarz | S | najbliżej top-5 w całym serwisie; treść istnieje, prerender działa, podaż niepotrzebna |
| 19 | **Zaszyć listę postów w seedzie** — `blogList` w `__collectSSRState` (`main.ts:50`), seed w `BlogPage.vue`, `needsState` w `prerender.mjs` o `/blog` i `/blog/{kategoria}`, **plus bramka „nie ustawiaj `noindex` bez udanego fetcha"** | dev / architekt | S | 0/4 kategorii bloga zindeksowanych; to ta sama klasa awarii, którą dla listingów zamknięto bramką `hasLoaded` |

### TIER 3 — strategiczne, kwartał (najwyższy wpływ, koszt L — i tak trzeba zacząć teraz)

| # | Akcja | Odbiorca | Wysiłek | Oczekiwany efekt |
|---|---|---|---|---|
| 20 | **Przestawić akwizycję z billboardów na citylight / LED / transport / mobilną w 8 miastach z potwierdzonym popytem.** Cel operacyjny policzalny: **3 nośniki na kombinację typ×miasto** wg listy z §5c | biznesowy → marketer | **L** | 24 nośniki w 10 slotach odblokowują strony z **2 484 wyświetleniami = 29,3% ruchu serwisu**. Te same 24 w Kłodzku dodają 1,4 wyświetlenia |
| 21 | **Katowice: rozszerzyć stronę miasta o promień aglomeracji** (mechanizm `lat/lng/radius` już istnieje) | biznesowy → dev | M | 140 nośników ≤30 km vs 8 pokazywanych, 266 wyśw. popytu — podaż bez ani jednej rozmowy sprzedażowej. Nie zadziała dla Białegostoku (0 w promieniu 50 km) ani Szczecina (103 km) |
| 22 | **Ścieżka e-mailowa do wystawcy** — alias relay per ogłoszenie (`ad-{id}@kontakt.reklamap.pl`), mechanika dwóch kliknięć jak przy telefonie; NIE odsłaniać `owner_email` | biznesowy → dev | M | 82,6% wystawców preferuje e-mail, a serwis nie ma ani jednego `mailto:` do nich. Uwaga: strict DMARC — wysyłka wyłącznie przez SMTP Hostido |
| 23 | **Zbudować profil linków od zera** — katalogi branżowe OOH, izby gospodarcze, portale marketingowe, wymiana z agencjami, których nośniki już importujemy (Big Group, Outdoor 3miasto, reklama.ai, Optokom) | strateg (research) → biznesowy | L | **zero domen linkujących** potwierdzone dwoma niezależnymi źródłami. Przy poz. 31,3 to jedyna dźwignia ruszająca pozycje, a nie tylko wyświetlenia |
| 24 | **Drugi filar blogowy obok `billboard-reklama`** — poradnik LED albo citylight (po ~1,2 tys. wyśw. popytu każdy); przepisać `telebim-ekran-led-reklama` i `totem-reklamowy` pod frazy realnie występujące w GSC | strateg → pisarz | M | dziś 1 artykuł niesie 47,8% bloga i 23,5% wyświetleń serwisu — koncentracja ryzyka |
| 25 | **Klaster podażowy** (`ile można zarobić na billboardzie`, `billboard na działce pozwolenie/podatek`, `wydzierżawienie gruntu pod reklamę`) — research od zera, bo w naszym GSC tych fraz nie ma | strateg | M | **0 fraz podażowych w 705 zapytaniach**, a projekt jest w fazie budowy podaży. Tani start: 3 gotowe, zrecenzowane, **nieopublikowane** artykuły z tego obszaru |

### NIE ROBIĆ (obalone pomiarem albo bez pokrycia w danych)

- **Nie poprawiać meta na frazach typu `totemy reklamowe wrocław`, `powierzchnie reklamowe białystok`, `reklama na ekranach led poznan`** — 78% CTR-owych trupów (30 fraz / 900 wyśw.) to strony poza sitemapą, którym `.htaccess` oddaje szkielet 7 929 B z tytułem strony głównej. Snippet i tak nie pochodzi z naszej meta.
- **Nie inwestować w konsolidację www** — udział www w wyświetleniach: prev28 33,3% → last28 6,9% → ostatnie 7 dni **2,6%**. Zamknąć w `SEO_TECH_AUDIT` jako rozwiązane, monitorować; alert dopiero powyżej 5%.
- **Nie robić `preconnect` do `api.reklamap.pl` ani do kafli OSM** — Lighthouse na 12 przebiegach nie wskazał ich ani razu.
- **Nie ruszać `eager`/`fetchpriority` na leafie** — `lcp-discovery` = 1/1, jedyny zielony wynik tego audytu.
- **Nie dokładać podaży w hubach kłodzkich** — 253 nośniki (30,6% bazy) → 39 wyświetleń / 0 klików w 3 miesiące.
- **Nie inwestować w `wall`** — 24 nośniki przy 8 wyświetleniach popytu.
- **Nie ruszać bramki UA w `index.html:36`** ani niezmienników seedu/progu/301 z CLAUDE.md.
- **Nie pisać pod frazę `reklama outdoor baner billboard {miasto}`** z Binga (90 wyśw. / 0 klików / poz. 1,2–3,5) — w Google ta fraza ma **0 wyświetleń**; pozycja 1 z zerowym CTR to sygnatura rank-trackera, nie użytkownika.
- **Nie planować działań pod założenie „wracamy do stanu sprzed awarii"** — w maju, na szczycie, non-brand dawał 4 kliki na 4 011 wyświetleń w wymiarze zapytań.

---

## 11. Czego NIE wiemy i co trzeba rozstrzygnąć

### Pytania do foundera (blokują decyzje, nie zgaduję)

1. **Status `reserved` (463/827)** — czy to zajętość na dzień importu (przeterminowana), czy „portfolio agencji, nie do wynajęcia"? Wisi na tym połowa mapy kategorii (55→28 miast, 57→29 kombinacji) i to, co widzi użytkownik (59% kafelków „Zarezerwowane", 5 miast po 100%). Nie ma pola `reserved_until` — rezerwacja bez końca.
2. **32 usunięte ogłoszenia (ID 1–47)** — nośniki realnie zniknęły, czy zostały skasowane omyłkowo? Od odpowiedzi zależy 410 vs `updateOrCreate` pod tymi samymi ID. To były najlepiej rankujące strony serwisu (poz. 2,7–8,0).
3. **Kierunek akwizycji: gęstość czy zasięg?** Dane mówią jednoznacznie za zasięgiem (3 nośniki w miastach z popytem > 60 kolejnych w Kłodzku), ale to decyzja o modelu sprzedaży, nie o SEO.
4. **Czy blok „koszyk wielu nośników" (`/schowek` + `POST /api/inquiries`) wchodzi do backlogu?** Luka jest realna (limit 5, ograniczenie do jednego typu, brak CTA, brak endpointu zbiorczego), ale **nie była przyczyną utraty sprawy warszawskiej** — tam zabrakło nośników, nie formularza. Nie powinna wyprzedzić budowy podaży.

### Dane, których fizycznie nie mamy

- **CrUX / dane polowe / INP** — PSI 429, CrUX 403. Founder może to zamknąć w 30 s: GSC → Core Web Vitals. Jeśli widzi „Nie ma wystarczających danych", sprawa CWV-jako-sygnału-rankingowego jest zamknięta.
- **Lista 24 URL-i „Błąd serwera (5xx)" z UI GSC** — raport Crawl Stats nie ma API. Eksport + `curl -I -A Googlebot` po każdym zamyka temat w 10 minut. Dwie hipotezy pozostają nierozstrzygnięte: (a) zamrożony stan historyczny po awarii SSL/WAF `api.reklamap.pl`, (b) limit współbieżności LiteSpeed przy burstach Googlebota. **Nie warto budować teorii przed tym eksportem.**
- **Rozbicie lejka podaży po `step_number`** — 0 custom dimensions w property; dane od 14.05 są **bezpowrotnie stracone** i tracimy kolejne każdego dnia.
- **Bing AI Performance / Copilot** — brak w API (7 wariantów nazw → 404), tylko eksport ręczny z UI.
- **Ilu userów kliknęło w zepsuty filtr województwa** — `analytics.filterUsed` nie jest wołane ani razu.
- **Ile alertów wyszukiwania ma ustawiony `region`** — endpoint niepubliczny. (`SearchAlertService.php:25-26` ma tę samą klasę błędu co filtr: alert z ASCII-id nigdy nie dopasuje ogłoszenia z `województwo dolnośląskie`.)
- **Kto rozpuścił link `reklamap.pl:443`** — ciąg `:443` nie występuje nigdzie w repo (grep po `backend/app`, `backend/routes`, `backend/database`, `frontend/src`, `reklamap-os`). Źródło zewnętrzne, najpewniej skopiowany `ServerName` z konfiguracji vhosta. 5 userów, ostatnie wystąpienie 20.05 — nie wymaga naprawy, ale ujawniło soft-404 i pętlę guardu.

---

## 12. Uczciwie: co w poprzednich przeglądach było przeszacowane

| # | Teza (briefing / wcześniejsze przeglądy) | Stan faktyczny | Skala pomyłki |
|---|---|---|---|
| 1 | „695 fraz non-brand → **6 klików**" | Wymiar zapytań GSC anonimizuje frazy rzadkie: łapie 112 ze 167 klików. Realne kliki non-brand ≈ **60 w 3 mies.** (167 − 107 brand) | **10×** zaniżone |
| 2 | „Kliki płasko 2/0/2/2 tygodniowo" (analiza po frazach) | Po **stronach**: 3 / 3 / 6 / **18**; kliki poza home 1 / 2 / 3 / **17** | odwrotny wniosek |
| 3 | Bing: „`BlockedByRobotsTxt` = **5 255** przy `Code2xx` 14 656" | `GetCrawlStats` miesza semantykę pól — `Code2xx`/`BlockedByRobotsTxt`/`InIndex` to **migawki stanu**, nie zdarzenia dzienne. Realnie **101 zablokowanych / 465 znanych 2xx / 418 w indeksie** | **~52×** zawyżone |
| 4 | „`www.reklamap.pl` NIE jest skonsolidowane" (ust. #5) | 301 działa, `googleCanonical` = non-www, udział www **33,3% → 6,9% → 2,6%**. Agregat 3-miesięczny ciągnie maj–czerwiec. 89% wyświetleń www pochodzi ze stron dziś celowo `noindex` | temat zamknięty, nie otwarty |
| 5 | „Duble miast dzielą podaż" — 5 par (ust. #9) | Tylko **23 z 827 (2,8%)** realnie dzieli warianty zapisu. `Ząbkowice` vs `Ząbkowice Śląskie` to **różne miasta oddalone o 176 km**, `Duszniki` vs `Duszniki-Zdrój` o 213 km. Sitemapa ma **0 duplikatów `<loc>`** | w większości nieprawdziwe |
| 6 | „Filtr województwa zwraca 0" — sugestia, że wszystkie | **3 z 16 działają** (`slaskie` 135, `lubelskie` 32, `zachodniopomorskie` 80) — kolacja MySQL jest accent-insensitive, ale `ł` ma własną wagę. To **gorszy** wariant: przy ręcznym teście („Śląskie działa!") bug wygląda na nieistniejący | zła diagnoza, ten sam wniosek |
| 7 | „5xx dotyczą wyłącznie Googlebota — WAF na `api.reklamap.pl`" (ust. #4) | GSC: `api.reklamap.pl/`, `/sitemap.xml` i `/storage/…` = **„Adres URL jest Google nieznany"**. Google nigdy nie odwiedził api. Wszystkie 12 znalezionych 5xx to **blog**, wszystkie z crawla **2026-05-15** | obalone |
| 8 | „Lejek podaży 56 → 31 userów (55%)" (ust. #12) | Property zanieczyszczona ruchem dev (18,9% odsłon). Prod-only: **51 → 31 = 60,8%** | +5,8 pp |
| 9 | „Łącznie odsłon (30 dni) = 591" (`stats.php`) | GA4 na tym samym zakresie: **43 odsłony / 18 userów**. Licznik liczy prerender (983 trasy na deploy) i crawlery | **13,7×** zawyżone |
| 10 | Audyt 07.07 poz. #16 „brak image-sitemap" | **Zamknięte** — 825 `<image:loc>` przy 825 leafach (`routes/web.php:200-205`) | nieaktualne |
| 11 | Audyt 07.07 poz. #4 „near-duplikaty Big Group" | **Gorsze niż opisano**: 516/827 (62,4%) dzieli tytuł, 3 URL-e byte-identyczne | niedoszacowane |
| 12 | Audyt 07.07: `preconnect` do `api` i OSM, lazy-LCP wszędzie | `preconnect` **obalone na 12 przebiegach**; lazy-LCP potwierdzone **tylko dla kategorii mobile**, leaf ma 1/1 | częściowo obalone |
| 13 | Audyt 07.07 poz. #32 „formularz powinien być `noindex`" | **Podważam** — to landing pozyskania podaży w fazie budowy podaży; `noindex` odciąłby stronę, która ma sprzedawać wystawcom | świadoma decyzja, zamknąć |

**Metodologiczny wniosek na przyszłość:** przy tej skali ruchu (167 klików / kwartał) wymiar zapytań w GSC jest bezużyteczny do liczenia klików — anonimizacja zjada 33%, i to dokładnie długi ogon. **Wszystkie analizy klików robić na wymiarze STRON**, wymiar zapytań zostawić do fraz i pozycji. Analogicznie w Bingu: `Code2xx`/`BlockedByRobotsTxt`/`InIndex`/`AllOtherCodes` brać z **ostatniego wiersza**, sumować wyłącznie `CrawledPages`/`Code4xx`/`Code5xx`/`CrawlErrors`.

---

## 13. Brief dla kolejnych agentów

### ➡️ DLA STRATEGA
1. **`blog/lokalizacje/reklama-outdoor-krakow`** — rozbudowa o `nośniki reklamowe kraków outdoor` (25 wyśw., poz. **8,1** — najbliżej top-5 w serwisie), `reklama na tramwajach kraków koszt` (19/19,4), `reklama outdoorowa kraków` (16/18,6). Plus klaster transportu miejskiego: ~84 wyśw. na tym jednym artykule, poz. 19–41, bez własnej strony.
2. **To samo dla Gdańska** — `reklama outdoorowa gdansk` (20/15,9).
3. **Drugi filar poradnikowy: LED albo citylight** — po ~1,2 tys. wyśw. popytu każdy, dziś `billboard-reklama` niesie 47,8% bloga.
4. **Dosypać do `prawo-i-regulacje`** — najlepsza średnia pozycja (18,9) przy 2 artykułach z ruchem.
5. **Klaster podażowy — research od zera** (w naszym GSC 0 takich fraz). Tani start: 3 gotowe, zrecenzowane, **nieopublikowane** artykuły (`reklama-na-ogrodzeniu`, `reklama-na-elewacji-wspolnoty`, `reklama-outdoor-szczecin`).
6. **NIE pisać** nowych artykułów w silosie `trendy` w obecnej formule (4 szt., 0 wyświetleń / 3 mies.) — ale sprawdzić najpierw, czy to nie efekt zamrożonego 5xx (2 z 4 są w tej puli).

### ➡️ DLA ARCHITEKTA SEO
Tier 1 poz. 6 (check driftu sitemap w tripwire) · Tier 2 poz. 10, 19 · meta wyłącznie na 4 frazach XS (poz. 17) · schema `FAQPage` na artykułach z sekcjami pytań i na `/faq` (dziś PRODUCT_SNIPPETS to jedyny typ wzbogacenia, 22 wyśw./mies. — każdy nowy typ będzie widoczny w `dims=[searchAppearance]` po 30 dniach) · `<link rel=canonical>` w `spa-fallback.html` · `BingSiteAuth.xml` (weryfikacja Binga wisi dziś na `GSCImport`) · 301 z `www.api.reklamap.pl` (czwarty żywy hostname, 200 bez przekierowania) · `?_v=` → fragment `#` + `X-Robots-Tag` zamiast `Disallow` (blokada konserwuje 101 URL-i w indeksie Binga — bot nie pobierze, więc nie zobaczy `noindex`).

### ➡️ DLA BIZNESOWEGO
Tier 3 poz. 20–23 · rozstrzygnięcie `reserved` · decyzja gęstość vs zasięg · 768 billboardów Big Group jako zasób, który nie generuje SEO i nie zacznie (nie da się wykreować wolumenu wyszukiwań dla Szalejowa Górnego) · unikalność treści jako warunek kolejnych importów (41,7% dzieli tytuł+opis).

### ➡️ DLA UŻYTKOWNIKA
Tier 1 poz. 1–5 i 9 (deploy, GSC Validate Fix, GA4 Admin ×3, testy) · zmiana KPI z średniej pozycji na pasma 1–10 / 10–20 + kliki poza home · outreach na landing `/dodaj-powierzchnie-reklamowa` z UTM · **spadek klików brandowych 27 → 5 czytać jako spadek wolumenu cold-callingu, nie jako problem SEO**.

---

*Nie commitowano, nie deployowano, nie seedowano produkcji, nie wysyłano maili. Raporty cząstkowe: `scratchpad/raport-{gsc,ga4,bing,cwv,indeks,kod,podaz}.md`. Patch filtra województwa: `scratchpad/fix-region.patch` (repo główne nietknięte).*
