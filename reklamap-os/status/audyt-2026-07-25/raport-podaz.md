# Raport: rozjazd PODAŻ ↔ POPYT (ReklaMap, 2026-07-25)

Autor: Agent Analityk + Biznesowy. Zakres: ilościowe zbadanie problemu strukturalnego nr 1 z BRIEFING.md (ustalenie 7).

## Źródła danych (wszystko świeże / lokalne, nic zgadywane)

| Dane | Źródło | Kiedy |
|---|---|---|
| Podaż: 827 nośników, wszystkie pola | `https://api.reklamap.pl/api/listings?per_page=200&page=1..5` (X-App-Key z `backend/.env`) → `scratchpad/listings_p1..5.json` | 2026-07-25, pobrane w tej sesji |
| Popyt: 705 fraz, 8 471 wyśw / 112 klik | `reklamap-os/stats/imports/gsc-2026-07-25/api/q3m__query.json` (26.04–25.07) | eksport 2026-07-25 |
| Popyt per URL | `…/api/q3m__page.json`, `last28__page.json`, `prev28__page.json` | j.w. |
| Werdykty indeksacji (próbka 27 leafów + 14 stron kategorii) | GSC URL Inspection API przez `scratchpad/gapi.py` (`gsc_inspect`) | 2026-07-25, w tej sesji |
| Sitemap produkcyjny: 983 URL-e | `curl https://reklamap.pl/sitemap.xml` | 2026-07-25 |
| Progi/logika | `frontend/src/utils/listingsSeo.ts:12` (`THIN_PAGE_THRESHOLD = 3`), `backend/routes/web.php:80,93,108,141` | repo |

Uwaga metodologiczna: strona kategorii liczy **wszystkie** ogłoszenia `is_active=1` (łącznie z `reserved`) — zweryfikowane na prodzie: `?city=Klodzko&city_strict=1` → `total: 138` (baza ma 138 rekordów w Kłodzku, w tym 119 `reserved`). Czyli próg thin=3 działa na sumie, nie na wolnych.

**Brak danych:** publiczny endpoint `/api/listings` nie zwraca `owner_email` ani telefonu (patrz lista pól w `listings_p1.json`) — rozbicia `reserved` po właścicielach **nie da się zrobić z tego źródła**. Substytut: `offer_type` + batch `created_at` + katalog zdjęć (`image_url`).

---

## 1. Duże miasta: popyt vs podaż

Popyt = suma wyświetleń fraz zawierających nazwę miasta (regex z odmianami, granice słowa; „Kłodzko" nie wpada do „Łódź").
Podaż = rekordy, których `slug(city)` odpowiada slugowi miasta (tak jak `city_strict` w API).

| Miasto | wyśw 3m | klik | fraz | śr. poz. | podaż | ACT | RES | wyśw / nośnik ACT |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Poznań | 1 547 | 0 | 124 | 43,9 | 18 | 17 | 1 | 91 |
| Gdańsk | 943 | 0 | 44 | 37,3 | **0** | 0 | 0 | ∞ |
| Warszawa | 696 | 0 | 55 | 40,8 | **2** | 1 | 1 | 696 |
| Kraków | 658 | 1 | 46 | 27,6 | **0** | 0 | 0 | ∞ |
| Łódź | 631 | 2 | 38 | 32,7 | **0** | 0 | 0 | ∞ |
| Bydgoszcz | 279 | 0 | 14 | 26,9 | **0** | 0 | 0 | ∞ |
| Wrocław | 275 | 0 | 20 | 19,1 | 11 | 8 | 3 | 34 |
| Lublin | 269 | 0 | 20 | 19,7 | **1** | 1 | 0 | 269 |
| Katowice | 266 | 0 | 21 | 30,8 | 8 | 7 | 1 | 38 |
| Białystok | 227 | 0 | 5 | 26,4 | **0** | 0 | 0 | ∞ |
| Szczecin | 15 | 0 | 2 | 27,3 | **0** | 0 | 0 | ∞ |
| **RAZEM** | **5 806** | **3** | 389 | — | **40** | **34** | **6** | 171 |

- 11 miast = **68,5% wszystkich wyświetleń** GSC (5 806 / 8 471) i **4,8% podaży** (40 / 827).
- 6 z 11 miast ma **zero** nośników; łącznie generują **2 753 wyśw** (32,5% całego popytu) przy 0 ofert.
- Średnia dla całej bazy: **10,2 wyśw / nośnik**, **24,3 wyśw / nośnik ACT**. W Kłodzku: **0,06 wyśw / nośnik** (8 wyśw strony miasta / 138 nośników).

Geografia (Haversine z centroidów miast do wszystkich 827 nośników):

| Miasto | ≤15 km | ≤30 km | ≤50 km | najbliższy [km] |
|---|---:|---:|---:|---:|
| Katowice | 99 | 140 | 157 | 1,1 |
| Wrocław | 19 | 38 | 57 | 2,0 |
| Poznań | 16 | 34 | 47 | 1,1 |
| Warszawa | 5 | 13 | 20 | 8,1 |
| Kraków | 1 | 4 | 11 | 14,3 |
| Gdańsk | 1 | 4 | 6 | 11,7 |
| Łódź | 0 | 4 | 8 | 25,7 |
| Lublin | 1 | 1 | 1 | 3,3 |
| Bydgoszcz | 0 | 0 | 1 | 47,8 |
| Białystok | 0 | 0 | 0 | 89,6 |
| Szczecin | 0 | 0 | 0 | 103,2 |

Wniosek: dla Katowic (140 nośników ≤30 km, 8 w mieście) problemem nie jest podaż, tylko **prezentacja** — strona `/powierzchnie-reklamowe/katowice` pokazuje 8, choć aglomeracja ma 140. Dla Białegostoku, Szczecina i Bydgoszczy nie ma czego pokazywać w żadnym promieniu.

## 2. Typy nośnika: popyt vs podaż

| Typ (slug) | wyśw 3m | fraz | śr. poz. | podaż | ACT | RES | wyśw / ACT | strona typu |
|---|---:|---:|---:|---:|---:|---:|---:|---|
| billboard `billboardy` | 2 042 | 193 | 35,0 | 768 | 291 | 463 | 7 | index |
| led_screen `ekrany-led` | 1 222 | 68 | 28,2 | **5** | 5 | 0 | **244** | index (5 ≥ 3) |
| citylight `citylighty` | 1 041 | 60 | 30,1 | **12** | 12 | 0 | **87** | index |
| transport `reklama-w-transporcie` | 900 | 62 | 38,2 | **1** | 1 | 0 | **900** | **noindex, poza sitemapą** |
| mobile `reklama-mobilna` | 247 | 16 | 23,8 | 6 | 6 | 0 | 41 | index |
| totem `totemy-reklamowe` | 152 | 5 | **18,1** | **0** | 0 | 0 | ∞ | **noindex, poza sitemapą** |
| banner `banery` | 130 | 23 | 46,8 | 9 | 9 | 0 | 14 | index |
| wall `sciany-reklamowe` | 77 | 14 | 48,9 | 24 | 23 | 0 | 3 | index |

- Cztery typy o realnym popycie (**LED + transport + totem + citylight = 3 315 wyśw, 39% całego GSC**) mają łącznie **18 nośników** (2,2% bazy), a `led_screen` rozrzucone po 5 różnych miastach (Częstochowa, Sieradz, Lublin, Budzistowo, Września) — każda kombinacja LED×miasto jest thin.
- Billboard: 93% podaży (768/827) obsługuje 24% popytu (2 042/8 471).
- `totemy reklamowe wrocław` = 100 wyśw na **poz. 11,4** przy **zerowej** podaży totemów w całym kraju (typ `totem` nie występuje w bazie ani razu).
- `wall` to jedyny typ z nadpodażą względem popytu (24 nośniki / 77 wyśw, śr. poz. 48,9).

## 3. Podaż bez popytu — czy huby w małych miastach mają pokrycie w wyszukiwaniach?

Popyt liczony jako frazy zawierające **wszystkie** tokeny nazwy miasta.

| Miasto (slug) | podaż | ACT | RES | wyśw fraz 3m | wyśw **strony miasta** 3m | klik |
|---|---:|---:|---:|---:|---:|---:|
| klodzko | 138 | 19 | 119 | 3 | 8 | 0 |
| koszalin | 70 | 70 | 0 | 30 | 40 | 0 |
| dabrowa-gornicza | 60 | 32 | 21 | 81 | brak w top | 0 |
| szalejow-gorny | 35 | 1 | 34 | **0** | 0 | 0 |
| biala-podlaska | 32 | 2 | 30 | 2 | 36 | 2 |
| zabkowice-slaskie | 31 | 0 | 31 | **0** | 0 | 0 |
| kudowa-zdroj | 19 | 1 | 18 | **0** | 2 | 0 |
| dzierzoniow | 18 | 1 | 17 | 74 | 61 | 1 |
| jordanow-slaski | 16 | 2 | 14 | **0** | 9 | 0 |
| polanica-zdroj | 14 | 1 | 13 | **0** | 20 | 0 |
| bielawa | 12 | 0 | 12 | 12 | 13 | 0 |
| **6 hubów kłodzkich** (klodzko, szalejow-gorny, zabkowice-slaskie, kudowa-zdroj, jordanow-slaski, polanica-zdroj) | **253** | **24** | **229** | **3** | **39** | **0** |

- **253 nośniki (30,6% całej bazy) generują 39 wyświetleń w 3 miesiące i 0 kliknięć.** To 0,15 wyśw/nośnik. Dla porównania Poznań: 18 nośników → 629 wyśw strony miasta (34,9 wyśw/nośnik) — **przewaga 227×**.
- Strony te są zaindeksowane (GSC Inspection 2026-07-25: `/powierzchnie-reklamowe/klodzko`, `/szalejow-gorny`, `/zabkowice-slaskie` = „Strona przesłana i zindeksowana"), więc problemem nie jest indeksacja tylko **brak zapytań** — w Google nikt nie szuka billboardu w Szalejowie Górnym.
- Wyjątki warte uwagi: **Dąbrowa Górnicza** 81 wyśw (fraza generyczna „agencja reklamowa dąbrowa górnicza" 29 — intencja usługowa, nie zakupowa), **Dzierżoniów** 74 wyśw / 1 klik (i tylko 1 wolny nośnik z 18!), **Koszalin** 30 wyśw / 1 klik przy 70 wolnych.

**Odpowiedź na pytanie: nie, strategia hubów w małych miastach nie ma pokrycia w wyszukiwaniach.** Ma za to pokrycie w indeksie (55 stron miast w sitemapie) — o czym niżej.

### Gdzie realnie ląduje popyt na stronach kategorii

Agregacja `q3m__page.json` (kategorie + kombinacje, 6 318 wyśw) skrzyżowana z podażą:

| Segment stron | wyśw 3m | udział |
|---|---:|---:|
| strony z podażą **0** | **4 173** | **66%** |
| strony z podażą 1–2 (thin → noindex) | 949 | 15% |
| strony z podażą ≥3 (index) | 1 196 | 19% |

Dwie trzecie zainteresowania Google trafia na strony, na których nie mamy **ani jednej** oferty. Top: `/reklama-w-transporcie/gdansk` 414 wyśw / 0 ofert, `/reklama-w-transporcie/poznan` 370 / 0, `/ekrany-led/poznan` 351 / 0, `/banery/lodz` 166 / 0, `/totemy-reklamowe/gdansk` 156 / 0.

## 4. Status `reserved` — 463/827 (56,0%)

### Profil (100% jednorodny)

| Cecha | Wartość dla wszystkich 463 |
|---|---|
| `type` | `billboard` (463/463) |
| `variant` | `standard` (463/463) |
| `offer_type` | `agency` (463/463; w całej bazie 770 agency / 57 owner) |
| `available_from` | `NULL` (463/463) |
| `created_at` | 2 batche: **2026-06-15 21:09 → 374 szt.**, **2026-06-10 16:xx → 88 szt.**, +1 szt. 2026-06-18 |
| `updated_at` | 450 szt. 2026-06-15, 11 szt. 2026-06-10, 1 szt. 2026-06-18, **1 szt. 2026-07-09** |
| `region` | puste 375/463; reszta `śląskie` 50, `lubelskie` 30, `mazowieckie` 5, `małopolskie` 3 |
| katalog zdjęć | głównie `advertisements/reklama-ai/…` (408 rekordów w całej bazie) |

Rozkład po miastach (top): Kłodzko 119, Szalejów Górny 34, Ząbkowice Śląskie 31, Biała Podlaska 30, Dąbrowa Górnicza 21, Kudowa Zdrój 18, Dzierżoniów 17, Jordanów Śląski 14, Polanica Zdrój 13, Bielawa 12. **22 miasta mają ≥50% podaży zarezerwowanej**, w tym 5 miast po 100% (Ząbkowice Śląskie 31/31, Bielawa 12/12, Braszowice 9/9, Łagiewniki 9/9, Szczytna 8/8).

### Hipoteza — argumenty w obie strony (PYTANIE DO FOUNDERA, nie przesądzam)

Za **realnymi rezerwacjami**: status jest **mieszany wewnątrz tej samej paczki importu** (batch 2026-06-15T21: 374 `reserved` + 34 `active`; w samym Kłodzku 119 res / 18 act; Krosnowice 3 res / 3 act) — gdyby import ustawiał status hurtem, byłoby 100% w paczce. Ceny w obu grupach są te same (810/540/1620 zł/mc), opisy podobnej długości (mediana 193 vs 190 zn.) — czyli status niósł jakąś informację ze źródła (najpewniej lista zajętości agencji).

Za **artefaktem / stanem zamrożonym**: (a) 463/463 to jeden typ, jeden wariant, jeden `offer_type`; (b) `available_from = NULL` u wszystkich — **nie ma daty końca rezerwacji**, w schemacie nie istnieje pole `reserved_until` (grep po `backend/app`, `backend/database/migrations`: brak trafień); (c) od 2026-06-18 **żaden** rekord nie zmienił statusu (jedyny `updated_at` po tej dacie to 1 szt. 09.07) — po 6 tygodniach naturalna rotacja kampanii OOH (typowo 2–4 tyg.) powinna zwolnić część powierzchni; (d) 0 rezerwacji powstało poza dwoma oknami importu.

**Pytanie do foundera (do rozstrzygnięcia przed jakąkolwiek zmianą):** czy `reserved` w paczkach z 10 i 15 czerwca odwzorowuje zajętość na dzień importu (i wtedy jest po prostu przeterminowane), czy oznacza „powierzchnia w portfolio agencji, nie do wynajęcia przez ReklaMap"?

### Ile oferty realnie ubywa i co widzi użytkownik

- Na 55 stronach miast obecnych w sitemapie leży łącznie **730 kafelków, z czego 433 (59%) ma badge „Zarezerwowane"** (`AdSidebar.vue:187`, `AdDetailPage.vue:161`).
- Użytkownik wchodzący z Google na `/powierzchnie-reklamowe/zabkowice-slaskie`, `/bielawa`, `/braszowice`, `/lagiewniki`, `/szczytna` widzi **wyłącznie** zarezerwowane nośniki (100%). Na `/powierzchnie-reklamowe/klodzko` — 138 ofert, z czego **19 wolnych**. W Dzierżoniowie (74 wyśw fraz, jedyny mały hub z realnym popytem) wolny jest **1 nośnik z 18**.
- Efektywna podaż platformy to **349 pozycji „Wolne" (42,2%)**, nie 827.
- Ryzyko odwrotne: gdyby uznać, że `reserved` nie powinno się liczyć do progu thin, pokrycie indeksu spada z **55 → 28 stron miast** (−27) i **57 → 29 kombinacji** (−28), a typ `billboardy` z 768 → 305. To połowa mapy kategorii. Decyzja o `reserved` jest więc jednocześnie decyzją o połowie indeksu — dlatego wymaga odpowiedzi foundera, a nie samodzielnej zmiany progu.

Adwersaryjnie: **`reserved` nie szkodzi indeksacji leafów.** Próbka 27 losowych leafów przez GSC URL Inspection (2026-07-25): `reserved` → 4 zindeksowane / 5 „wykryte, niezindeksowane" / 4 nieznane; `active` → 3 / 10 / 2. Różnica nieistotna przy tej próbce. Status nie jest przyczyną słabej indeksacji.

## 5. Jakość podaży (n = 827)

| Atrybut | Pokrycie |
|---|---|
| cena > 0 | 827 (100%) |
| wymiary (width×height > 0) | 811 (98,1%) |
| zdjęcie główne | 827 (100%) |
| ≥2 zdjęcia | 109 (13,2%) |
| `is_verified` | **0 (0,0%)** |
| opis niepusty | 827 (100%), mediana **198 zn.**, średnia 266, max 1 900 |
| opis ≥300 zn. | 274 (33,1%) |
| `region` wypełniony | 347 (41,9%) — 480 pustych (potwierdza ustalenie 1 z BRIEFING) |
| lat/lng | 827 (100%) |
| `price_unit` | `month` 815, `year` 6, `day` 4, `campaign` 2 |

Braki pól **nie są** problemem. Problemem jest **unikalność**:

- **Unikalnych tytułów: 456/827.** Najczęstsze: „Billboard 5.04×2.38 m – Biała Podlaska" ×23, „Billboard Jordanów Śląski — Przy drodze krajowej nr 8" ×16, „Billboard Koszalin – ul. Paderewskiego" ×13.
- **Unikalnych opisów: 598/827.** **345 ogłoszeń (41,7%) dzieli parę tytuł+opis z innym ogłoszeniem** (122 klastry duplikatów).
- Opisy z importu są generowane szablonem: „Billboard o wymiarach 5.05×2.35 m (12 m²) w lokalizacji: … Powierzchnia reklamowa w portfolio agencji. Stawka … zł/mc netto (cena wywoławcza, do negocjacji)." (rekord id 717).

### Czy to tłumaczy ~900 URL-i „wykryte, niezindeksowane"?

Próbka 27 leafów (GSC Inspection, 2026-07-25): **7 zindeksowanych (26%), 15 „wykryte – obecnie niezindeksowana" (56%), 5 „URL nieznany Google" (18%)**. Ekstrapolacja na 825 leafów w sitemapie: **~460 leafów w stanie „wykryte, niezindeksowane"** (±, próbka mała). Strony kategorii/kombinacji zachowują się odwrotnie — 8 z 10 sprawdzonych to „przesłana i zindeksowana”.

Czyli: tak, **to leafy tworzą gros puli „wykryte, niezindeksowane”**, a mechanizmem jest near-duplicate przy 41,7% powtórzonych par tytuł+opis i 93% jednorodności typu — nie brak pól. Do puli dokłada się jeszcze crawl budget na URL-ach `?_v=` (ustalenie 3 z BRIEFING) i na martwych leafach (niżej).

### Churn: tracimy dokładnie te strony, które rankowały

Z 71 leafów, które zebrały wyświetlenia w 3 miesiące, **34 (48%) nie istnieje już w bazie** — odpowiadały za **427 z 605 wyświetleń leafowych (71%)** i 8 z 13 kliknięć. Wśród nich najlepiej rankujące strony całego serwisu:

| URL (nie istnieje) | wyśw | poz. |
|---|---:|---:|
| `/powierzchnia-reklamowa/citylighty/olsztyn/citylight-olsztyn-dywizjonu-303-…` | 89 | **5,9** |
| `/powierzchnia-reklamowa/reklama-mobilna/bydgoszcz/przyczepka-reklamowa-…` | 63 | 13,7 |
| `/powierzchnia-reklamowa/ekrany-led/olsztyn/ekran-led-olsztyn-2` | 49 | 43,2 |
| `/powierzchnia-reklamowa/citylighty/wroclaw/citylight-pilsudskiego-wroclaw-…` | 39 | 17,7 |
| `/powierzchnia-reklamowa/billboardy/warszawa/billboard-premium-srodmiescie-…` | 38 | 15,8 |
| `/powierzchnia-reklamowa/billboardy/mszczonow/billboard-przy-mop-adamowice-…` | 11 | **3,9** (1 klik) |

Wszystkie zwracają **HTTP 200** z treścią 404 + `noindex` (`curl -A Googlebot …` = 200, 7 929 B) — czyli soft-404, a nie twarde 410/404. Zniknęły całe typy w miastach: citylight jest dziś tylko w Poznaniu (8) i Kaliszu (4), LED po 1 szt. w 5 miastach, mobile 6 szt., transport 1 szt. To dokładnie te typy, na które mamy popyt (sekcja 2).

## 6. Progi thin (THIN_PAGE_THRESHOLD = 3) — stan i najtańsze odblokowania

Stan sitemapy prod (983 URL-e, `curl` 2026-07-25): 7 statycznych, 33 blogowe, **61 kategorii** (55 miast + 6 typów), **57 kombinacji**, 825 leafów. Zgodność progu w 3 miejscach: **OK** — moja rekonstrukcja daje identyczne 55/57/6, brak rozjazdu między `listingsSeo.ts` a `web.php` (raw-city HAVING vs fold-slug nie różnią się dziś w żadnym mieście).

Poniżej progu:

- **77 slugów miast (97 nośników)** jest poniżej progu → noindex + poza sitemapą + poza prerenderem. Z tego **20 miast ma dokładnie 2 nośniki (brakuje po 1)**, 57 ma 1 (brakuje po 2).
- **90 ze 147 istniejących kombinacji typ×miasto (61%)** jest poniżej progu (21 potrzebuje 1 nośnika, 69 potrzebuje 2).
- **3 strony typów są noindex**: `totemy-reklamowe` (0, brakuje 3), `reklama-w-transporcie` (1, brakuje 2), `inne` (2, brakuje 1).
- Potwierdzone w GSC (Inspection 2026-07-25): `/powierzchnie-reklamowe/warszawa` = „Strona wykluczona za pomocą tagu **noindex**", `robots=ALLOWED`, ostatnie skanowanie 2026-07-22. Analogicznie `/ekrany-led/poznan`, `/totemy-reklamowe/wroclaw`, `/billboardy/lodz`.

### Ranking najtańszego wzrostu pokrycia indeksu (wyświetlenia 3m ÷ brakujące nośniki)

| # | URL | jest | brakuje | wyśw 3m strony | wyśw / brakujący nośnik |
|---|---|---:|---:|---:|---:|
| 1 | `/powierzchnie-reklamowe/warszawa` | 2 | **1** | 472 | **472** |
| 2 | `/reklama-w-transporcie/gdansk` | 0 | 3 | 414 | 138 |
| 3 | `/reklama-w-transporcie/poznan` | 0 | 3 | 370 | 123 |
| 4 | `/ekrany-led/poznan` | 0 | 3 | 351 | 117 |
| 5 | `/powierzchnie-reklamowe/lublin` | 1 | **2** | 174 | 87 |
| 6 | `/billboardy/warszawa` | 1 | **2** | 153 | 76 |
| 7 | `/powierzchnie-reklamowe/bialystok` | 0 | 3 | 170 | 57 |
| 8 | `/banery/lodz` | 0 | 3 | 166 | 55 |
| 9 | `/powierzchnie-reklamowe/bydgoszcz` | 0 | 3 | 161 | 54 |
| 10 | `/sciany-reklamowe/poznan` | 2 | **1** | 53 | 53 |
| — | **suma top-10** | — | **24 nośniki** | **2 484 wyśw** | — |
| — | wszystkie 56 stron thin z ruchem | — | 156 nośników | 5 122 wyśw | — |

**24 nośniki w 10 konkretnych miejscach odblokowują strony, które w 3 miesiące zebrały 2 484 wyświetlenia (29,3% całego ruchu wyszukiwarkowego serwisu).** Te same 24 nośniki dołożone w Kłodzku dodają ok. 1,4 wyświetlenia (0,06 wyśw/nośnik).

Miasta poniżej progu, które mają realny popyt (kandydaci „1–2 nośniki"): Warszawa (2, brak 1, 641 wyśw fraz), Lublin (1, brak 2, 269), Legnica (1, brak 2, 42), Częstochowa (2, brak 1, 31), Słupsk (1, brak 2, 27), Rzeszów (1, brak 2, 20), Bielsko-Biała (1, brak 2, 19), Toruń (1, brak 2, 17).

## 7. Wnioski dla fazy projektu (dla Agenta Biznesowego)

1. **Dane nie wspierają dalszej budowy podaży w małych miastach.** 253 nośniki w 6 hubach kłodzkich = 30,6% bazy → 39 wyświetleń / 0 kliknięć w 3 miesiące. Marginalna wartość kolejnego nośnika w Kłodzku ≈ 0,06 wyśw/mies. Marginalna wartość nośnika w Warszawie/Gdańsku/Poznaniu (jako odblokowanie strony) ≈ 50–470 wyśw/3m.
2. **Kierunek to nie „duże miasta" w ogóle, tylko 24 konkretne sloty** z tabeli w sekcji 6. Cel operacyjny jest policzalny: 3 nośniki na kombinację, nie „zbuduj podaż w Warszawie".
3. **Drugi wymiar rozjazdu to TYP, nie tylko miasto.** LED + transport + totem + citylight = 39% popytu przy 18 nośnikach (2,2% podaży). Pozyskanie 3 ekranów LED w Poznaniu i 3 nośników transportowych w Gdańsku/Poznaniu adresuje 1 135 wyświetleń — więcej niż cała podaż dolnośląska razem wzięta.
4. **Zanim dołożymy podaż, trzeba przestać ją tracić.** 34 leafy z 427 wyświetleniami (71% ruchu leafowego), w tym strony na poz. 3,9 i 5,9, zostały skasowane i zwracają soft-404 (HTTP 200). To był najlepszy rankingowo content, jaki serwis miał. Bez polityki retencji (i twardego 410 dla realnie usuniętych) każdy nowy nośnik ma tę samą trwałość.
5. **Sprawa `reserved` musi zostać rozstrzygnięta przez foundera, bo jest to jednocześnie decyzja o połowie indeksu** (55→28 miast, 57→29 kombinacji) i o tym, co widzi użytkownik (59% kafelków „Zarezerwowane", 5 miast po 100%). Rekomendowana kolejność: najpierw odpowiedź „czy te rezerwacje są aktualne", potem ewentualnie mechanizm wygaszania (brak pola `reserved_until` = rezerwacja bez końca).
6. **Katowice to test tezy „prezentacja zamiast pozyskania"**: 140 nośników ≤30 km, 8 na stronie miasta, 266 wyśw popytu. Rozszerzenie strony miasta o promień aglomeracji (mechanizm już istnieje — `lat/lng/radius` w `AdvertisementController::buildFilteredQuery`) daje podaż bez ani jednej rozmowy sprzedażowej. Ta sama dźwignia nie zadziała dla Białegostoku (0 nośników w promieniu 50 km) ani Szczecina (najbliższy 103 km).
7. **Jakość danych nie jest wąskim gardłem** (100% cena, 98% wymiary, 100% zdjęcie), ale **unikalność jest**: 41,7% ogłoszeń dzieli tytuł+opis z innym, co koreluje z 56% leafów w stanie „wykryte, niezindeksowane" (próbka n=27). Import kolejnych 400 billboardów tym samym szablonem pogłębi problem zamiast go rozwiązać.

---

*Nie commitowano, nie deployowano, nie seedowano produkcji, nie wysyłano maili. Skrypty analizy: `scratchpad/an2.py`, `an3.py`, `an4.py`, `an5.py`, `an6.py`, `an7.py`; surowe dane podaży: `scratchpad/listings_p1..5.json`; werdykty GSC: `scratchpad/inspect.json`.*
