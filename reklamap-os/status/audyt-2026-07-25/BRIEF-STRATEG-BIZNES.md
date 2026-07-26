# BRIEF: DLA STRATEGA + DLA BIZNESOWEGO

**Data:** 2026-07-25 · **Autor:** Agent Strateg SEO (współpraca: Agent Biznesowy) · **Baza:** audyt 7 wymiarów z 2026-07-25

**Źródła danych (wszystkie już na dysku — nic nie pobierano ponownie poza weryfikacją stanu bloga na prodzie):**
`reklamap-os/stats/imports/gsc-2026-07-25/api/{q3m__query,q3m__query_page,q3m__page,last28__query,prev28__query}.json` ·
`scratchpad/raport-{gsc,podaz,bing,indeks,ga4,kod,cwv}.md` · `reklamap-os/blog/INDEX.md` · `reklamap-os/status/STRATEGY_LOG.md` ·
`GET https://api.reklamap.pl/api/blog` (2026-07-25, 30 opublikowanych postów) · repo (`listingsSeo.ts`, `ComparisonPage.vue`, `analytics.ts`).

Klastry liczone własnym skryptem na `q3m__query.json` z poprawnym foldem polskich znaków (`ł→l` NIE jest usuwane przez NFD — dlatego liczby dla Łodzi, Wrocławia i Białegostoku w niektórych wcześniejszych analizach były zaniżone).

---

## 0. TRZY OGRANICZENIA, KTÓRE OBOWIĄZUJĄ W CAŁYM BRIEFIE

**O1. Treść informacyjna idzie na BLOG, nigdy na stronę kategorii.**
Kategoria z podażą < 3 dostaje `noindex` (`frontend/src/utils/listingsSeo.ts:12`, `THIN_PAGE_THRESHOLD = 3`) i wypada z sitemapy oraz prerenderu — Googlebot dostaje szkielet 7 929 B z tytułem strony głównej. Dotyczy to dziś 88 URL-i, które zebrały **5 273 wyświetlenia = 45,3% wyświetleń stronowych** (raport-gsc §5a). Dopisywanie treści do takiej kategorii to praca, której Google nigdy nie zobaczy.

**O2. Publikacja artykułu bez deployu frontu = `noindex`.**
`reklamap.pl/sitemap.xml` (statyczny, z builda 2026-07-13) ma 983 URL-e, `api.reklamap.pl/sitemap.xml` — 987. Dwa opublikowane artykuły (`pozwolenie-na-tablice-reklamowa`, `reklama-bez-pozwolenia-kary`) są od 12 dni zgłaszane Google'owi i serwowane jako `noindex, follow` (raport-bing §6). **Każdy temat z tego briefu kończy się krokiem „deploy frontu", inaczej jest wart zero.**

**O3. 12 URL-i bloga jest zamrożonych w werdykcie „Błąd serwera (5xx)" z 2026-05-15 (71 dni) i ma DOKŁADNIE 0 wyświetleń w 3 miesiące** (raport-indeks §5b). Na żywo zwracają 200 dla każdego UA.

| Zamrożone (10 artykułów + 2 kategorie) | Klaster popytu, który obsługują |
|---|---|
| `poradniki/reklama-w-transporcie-publicznym` | transport 994 wyśw |
| `trendy/telebim-ekran-led-reklama` | LED 1 260 wyśw |
| `trendy/totem-reklamowy` | totem 154 wyśw (**najlepsza śr. pozycja klastra: 18,7**) |
| `poradniki/reklama-na-samochodzie` | mobilna 248 wyśw |
| `lokalizacje/reklama-outdoor-warszawa` | Warszawa 696 wyśw |
| `lokalizacje/reklama-outdoor-wroclaw` | Wrocław 275 wyśw |
| `poradniki/baner-reklamowy-cena` | baner 130 wyśw |
| `poradniki/tablica-reklamowa` | tablice 61 wyśw |
| `poradniki/reklama-zewnetrzna`, `poradniki/jak-wybrac-powierzchnie-reklamowa` | „powierzchnie reklamowe" (generyk) 675 wyśw |
| `/blog/poradniki`, `/blog/prawo-i-regulacje` (kategorie) | — |

Suma bez podwójnego liczenia: **243 frazy / 3 449 wyświetleń = 40,7% całego popytu w GSC** stoi nad artykułami, które są napisane, zrecenzowane, żywe na prodzie — i niewidoczne dla Google. Dla kontrastu 6 zdrowych artykułów zebrało 1 532 wyświetlenia.

> **Konsekwencja dla planowania treści:** narracja „silos `trendy` nie działa, bo 4 artykuły mają 0 wyświetleń" jest FAŁSZYWA. Trzy z czterech artykułów `trendy` (telebim, totem, murale — murale nie są na liście 5xx, ale dwa pozostałe tak) siedzą w zamrożonym werdykcie. **Nie przepisujemy tych artykułów. Odmrażamy je i mierzymy przez 30 dni.** Odmrożenie to akcja w UI GSC („Błąd serwera (5xx)" → Sprawdź poprawkę / Validate Fix + Poproś o zindeksowanie) — właściciel: Architekt/dev, nie Strateg. **Jest to warunek wstępny dla 4 z 7 tematów poniżej.**

---

# A) DLA STRATEGA

## Tabela tematów (sort: potencjał / wysiłek)

| # | Temat | Fraza główna | Potencjał (3 mies.) | Strona istnieje? | Typ akcji | Silos | Wysiłek |
|---|---|---|---|---|---|---|---|
| 1 | Transport miejski | `reklama na autobusach poznań` | **994 wyśw / 71 fraz / poz. 35,9**; last28 430 (prev28 116) | TAK — zamrożony, 7 728 zn (najkrótszy w klastrze) | odmrożenie + rozbudowa istniejącego URL-a | poradniki | M |
| 2 | Citylight — warstwa miejska | `citylight {miasto}` | **1 041 wyśw / 60 fraz / poz. 30,2**; last28 455 (prev28 10) — **największy przyrost klastrowy** | TAK — 14 782 zn, 136 wyśw, poz. **44,7** | rozbudowa istniejącego URL-a (sekcja miejska) | poradniki | S/M |
| 3 | LED / telebim — podział intencji | `reklama na ekranach led {miasto}` | **1 260 wyśw / 72 fraz / poz. 28,2**; last28 372 | TAK — trzy URL-e naraz (hub + 2 artykuły) | odmrożenie + rozdzielenie ról, anty-kanibalizacja | trendy + poradniki | M |
| 4 | Warszawa i Wrocław (lokalizacje) | `billboard warszawa`, `citylight wrocław` | **971 wyśw** (Warszawa 696 + Wrocław 275) | TAK — oba zamrożone, 0 wyśw. | odmrożenie + refresh faktograficzny | lokalizacje | XS/S |
| 5 | Dług publikacyjny: 3 gotowe artykuły | — | popyt GSC: Szczecin 15 wyśw; ogrodzenie/elewacja **brak danych** (0 fraz) | NIE — napisane i zrecenzowane, nieopublikowane | publikacja w panelu + deploy frontu | poradniki (PODAŻ) + lokalizacje | XS |
| 6 | Gdynia i Białystok (nowe lokalizacje) | `citylight gdynia`, `powierzchnie reklamowe białystok` | Gdynia **194 / 15 fraz / poz. 29,2**; Białystok **227 / 5 fraz / poz. 26,4** | NIE | nowy artykuł ×2 | lokalizacje | M ×2 |
| 7 | Klaster podażowy (właściciel nośnika) | `billboard na działce` | **0 fraz podażowych w 705 zapytaniach** — research od zera | częściowo (2 opublikowane, 27 wyśw łącznie) | nowy klaster, obowiązkowy ATP + Ahrefs | poradniki (PODAŻ) + prawo | L |

---

## 1. Transport miejski — jedyny klaster, który NIE MA gdzie wylądować poza blogiem

**Fraza główna:** `reklama na autobusach poznań` (74 wyśw / poz. 37,4). **Klaster:** 994 wyśw / 71 fraz / poz. 35,9 / **0 klików**.

**Popyt wg miast** (`q3m__query.json`, fold PL): Poznań **352** (17 fraz), Gdańsk **223** (8), Kraków **179** (12), Łódź **132** (8), Warszawa 39, Katowice 29, Wrocław 19.
Top frazy: `reklama na autobusach poznan` 74/37,4 · `reklama na przystankach gdansk` 68/39,5 · `reklama na tramwajach gdansk` 64/39,0 · `reklama w autobusach poznan` 57/42,5 · `reklama na tyle autobusu poznan` 50/41,6 · `reklama tranzytowa poznan` 41/**13,0** · `reklama na przystankach łódź` 41/22,5 · `reklama tranzytowa kraków` 31/**3,5**.

**Dlaczego to jest #1, a nie zadanie dla akwizycji podaży:** typ `transport` ma w całej bazie **1 nośnik** (raport-podaz §2). Strona `/powierzchnie-reklamowe/reklama-w-transporcie` jest `noindex` i poza sitemapą, kombinacje `/reklama-w-transporcie/{gdansk,poznan,krakow}` mają **0 ofert**, a same zebrały 414 + 370 + ~180 wyświetleń. Nawet gdyby akwizycja ruszyła jutro, transport miejski to inwentarz operatorów (MPK/ZTM/MZK), nie właścicieli prywatnych — **ReklaMap tego nie sprzeda i nie musi**. Ale zapytanie jest informacyjne („ile kosztuje reklama na tramwaju w Krakowie"), więc blog może je obsłużyć w 100% i przekierować użytkownika na formaty, które mamy.

**Typ akcji:** (a) odmrożenie URL-a (warunek O3); (b) rozbudowa `/blog/poradniki/reklama-w-transporcie-publicznym` — dziś 7 728 znaków, najkrótszy artykuł w tym klastrze, z sekcjami per miasto: **Poznań, Gdańsk, Kraków, Łódź** (nagłówki H2 = miasto, w środku: nośniki, publiczny cennik operatora, formalności). Materiał częściowo już zebrany w brudnopisach lokalizacyjnych (STRATEGY_LOG: MPK Poznań od 1.03.2026 — Moderus Beta 12 500 zł/mc, 596 ekranów LCD; MPK Kraków od 1.12.2025 — tramwaj E1/C3 4 244 zł/mc; MZK Bydgoszcz 2025 — fullback 1 000 zł/mc; MPK Wrocław — Skoda 19T 4 000 zł/mc; ZTM Gdańsk — brak publicznego cennika, oznaczyć jako widełki).
(c) linkowanie zwrotne z 4 artykułów lokalizacyjnych, które już rankują na frazy transportowe mimochodem: Kraków (84 wyśw na frazach tramwajowych), Gdańsk, Łódź, Poznań.

**Anty-kanibalizacja:** artykuł ma być JEDYNYM celem fraz transportowych. Kategorie `/reklama-w-transporcie/*` i tak są `noindex` — problem wygaśnie sam. Nie tworzyć osobnych artykułów „reklama na tramwajach Kraków" — to rozbiłoby 994 wyświetlenia na 4 słabe URL-e (wzorzec kanibalizacji z raport-gsc §2b).

**Status w STRATEGY_LOG:** temat nie występuje. Sygnalizowany w raport-bing §8.3 i priorytecie #9. **Nowy wpis do logu.**

---

## 2. Citylight — Google już mapuje nasz artykuł na frazy miejskie, tylko na pozycji 48

**Fraza główna:** `citylight {miasto}`. **Klaster:** 1 041 wyśw / 60 fraz / poz. 30,2 / 3 kliki. **Dynamika: prev28 10 wyśw → last28 455** — najszybciej rosnący klaster w serwisie.

**Popyt jest rozlany po miastach, nie skoncentrowany** (`q3m__query.json`): Kraków 77, Łódź 77, Gdańsk 74, Olsztyn 69, Gdynia 67, Warszawa 62, Katowice 60, Wrocław 60, Poznań 57, Lublin 48, Bydgoszcz 40, Białystok 32, Sosnowiec 24, Rzeszów 20, Toruń 16, Bielsko-Biała 13, Częstochowa 13.

**Dowód, że to zadanie dla artykułu, nie dla kategorii** (`q3m__query_page.json`): `/blog/poradniki/citylight-reklama` rankuje DZIŚ na frazy czysto miejskie — `citylight lublin` poz. 49,5 · `citylight warszawa` 48,9 · `citylight rzeszów` 47,7 · `reklama citylight katowice` 49,9 · `reklama citylight bydgoszcz` 48,0 · `citylight gdańsk` 47,2. Google widzi w artykule odpowiedź na intencję miejską i stawia go na 48. miejscu, bo artykuł tej treści nie ma. Równolegle te same frazy trzyma hub `/powierzchnie-reklamowe/citylighty` (1 089 wyśw stron na 121 frazach) — **przy 12 citylightach w całym kraju**, z czego większość kombinacji miasto×citylight jest `noindex`.

**Typ akcji:** rozbudowa `/blog/poradniki/citylight-reklama` (14 782 zn — już duży, więc NIE dosypywać ogólników) o jedną sekcję: **„Citylight w polskich miastach — kto jest operatorem i ile to kosztuje"** z podsekcjami dla 8–10 miast z listy powyżej. Zawartość per miasto: operator (AMS / Ströer / Clear Channel / komunikacja miejska), typowy format, widełki cenowe, czy działa uchwała krajobrazowa. Plus wzmocnienie `ile kosztuje reklama citylight` (16 wyśw / poz. 39,5 — fraza cenowa, artykuł miał już refresh cenowy 2026-05-12, ale pozycja pozostaje słaba).

**Uwaga na pułapkę:** `reklama citylight olsztyn` — 69 wyśw / poz. **11,8** — rankuje LEAF, który **nie istnieje już w bazie** (raport-gsc §5b, 32 usunięte ogłoszenia). Po naprawie soft-404 ta pozycja zniknie. Artykuł powinien ją przejąć: sekcja Olsztyn jest obowiązkowa, mamy też artykuł `lokalizacje/reklama-outdoor-olsztyn` (2 wyśw / poz. 25,5) do podlinkowania.

**Status w STRATEGY_LOG:** `citylight-reklama` miał refresh 2026-05-12 pod frazę cenową. To DRUGI refresh, pod inną intencję (miejską) — **potwierdzone danymi**, dopisać jako osobny wiersz.

---

## 3. LED / telebim — największy klaster popytu i trzy nasze URL-e, które o niego walczą

**Fraza główna:** `reklama na ekranach led {miasto}`. **Klaster:** 1 260 wyśw / 72 frazy / poz. 28,2 / 1 klik — **największy pojedynczy klaster typu w serwisie**.

**Popyt wg miast:** Poznań **350**, Kraków 141, Łódź 108, Wrocław 90, Katowice 81, Olsztyn 64, Bydgoszcz 62, Warszawa 56, Lublin 56, Gdańsk 44, Gdynia 40, Białystok 34, Częstochowa 18.
Najlepsze pozycje: `reklama na ekranach led kraków` 76 wyśw / **7,3** (na `/ekrany-led/krakow`, 0 nośników) · `reklama na ekranach led warszawa` 40 / **14,9** · `reklama na ekranach led poznan` 87 / 19,3.

**Problem strukturalny — trzy URL-e na jeden klaster:**

| URL | Rola dziś | Wyświetlenia | Podaż / stan |
|---|---|---|---|
| `/powierzchnie-reklamowe/ekrany-led` (hub) | absorbuje 1 288 wyśw stron na 107 frazach miejskich | 569 / 1 klik / poz. 34,1 | **5 ekranów LED w całym kraju**, rozrzuconych po 5 miastach |
| `/blog/poradniki/ekran-led-cena` | intencja cenowa | 11 / poz. 28,4 | opublikowany 2026-06, 9 098 zn |
| `/blog/trendy/telebim-ekran-led-reklama` | format / co to jest | **0** | **ZAMROŻONY (5xx)**, 8 090 zn |

**Typ akcji — w tej kolejności:**
1. Odmrożenie `telebim-ekran-led-reklama` (O3). Bez tego każda praca na tym klastrze idzie do kosza.
2. **Rozdzielenie intencji, spisane jawnie w brudnopisie dla Pisarza:** `ekran-led-cena` = wyłącznie „ile kosztuje" (CPM, cennik AMS Programmatic DOOH obowiązujący od 1.07.2024 — dane zwalidowane w STRATEGY_LOG 2026-06-09); `telebim-ekran-led-reklama` = „co to jest, jakie formaty, **gdzie w Polsce**" — i to on dostaje warstwę miejską (Poznań, Kraków, Łódź, Wrocław, Katowice).
3. Poznań ma już gotowy materiał: rozbudowa `lokalizacje/reklama-outdoor-poznan` o sekcję LED/DOOH została zrobiona i JEST na prodzie (zweryfikowane: treść zawiera Roosevelta/Screen Network, 14 620 zn). Z niej linkować do `telebim-…`, nie odwrotnie.

**Czego NIE robić:** nie poprawiać meta na hubie `/powierzchnie-reklamowe/ekrany-led` licząc na CTR. Hub przechodzi próg (5 ≥ 3), więc jest indeksowany, ale użytkownik szukający „ekrany led kraków" dostaje listę 5 ekranów z całej Polski — to problem podaży (blok B), nie snippetu.

**Status w STRATEGY_LOG:** `ekran-led-cena` i `telebim` w logu są; **podział intencji między nimi nie jest nigdzie zapisany** — to nowa decyzja anty-kanibalizacyjna do dopisania.

---

## 4. Warszawa i Wrocław — 971 wyświetleń popytu i dwa gotowe artykuły z zerową widocznością

**Potencjał:** Warszawa 696 wyśw / 55 fraz / poz. 40,8 · Wrocław 275 / 20 / poz. 19,1 (**druga najlepsza średnia pozycja miasta w serwisie**, m.in. `totemy reklamowe wrocław` 100 wyśw / poz. **11,4**, `citylight wrocław` 38 / 15,5, `reklama tranzytowa wrocław` 9 / **8,7**).

**Stan:** oba artykuły napisane i zrecenzowane (INDEX.md), oba **zamrożone w 5xx od 2026-05-15**, oba mają **0 wyświetleń w 3 miesiące**. Równolegle strony kategorii tych miast są `noindex`: Warszawa ma 2 nośniki (największa pojedyncza strata w serwisie — 452 wyśw / poz. 44,0), Wrocław 11.

**Typ akcji:** odmrożenie (XS, akcja w GSC) + refresh faktograficzny przed ponownym zgłoszeniem — Warszawa ma w treści uchwałę krajobrazową z lipca 2025 (STRATEGY_LOG 2026-04-18), której status nie był weryfikowany w audycie uchwał z 2026-07-12; Wrocław ma Park Kulturowy „Stare Miasto" i informację o wstrzymanej uchwale. **Reguła krytyczna Stratega obowiązuje: status uchwał wyłącznie z BIP miast i orzecznictwa, nigdy z blogów operatorów OOH.**

**Dlaczego przed Gdynią i Białymstokiem:** treść już istnieje, popyt (971 wyśw) jest 2,3× większy niż dla dwóch nowych miast razem (421 wyśw), a koszt to jedno kliknięcie w GSC plus weryfikacja prawna.

---

## 5. Dług publikacyjny — trzy artykuły gotowe od 2026-06-22, nieopublikowane

**Zweryfikowane na prodzie 2026-07-25** (`GET api.reklamap.pl/api/blog` → 30 postów; `INDEX.md` → 33): brakuje
`poradniki/reklama-na-ogrodzeniu`, `poradniki/reklama-na-elewacji-wspolnoty`, `lokalizacje/reklama-outdoor-szczecin`.
Wszystkie trzy mają status ✅ ZRECENZOWANY i gotowe brudnopisy (STRATEGY_LOG 2026-06-22).

**Potencjał — uczciwie:** Szczecin 15 wyśw / 2 frazy / poz. 27,3 (mały). Ogrodzenie i elewacja: **brak danych** — w 705 frazach GSC nie ma ani jednego zapytania podażowego, więc nie da się oszacować popytu z naszych danych (brak widoczności ≠ brak popytu; patrz temat 7).

**Typ akcji:** publikacja w panelu admina + **deploy frontu** (O2). Zerowy koszt treści.

**Przy okazji — housekeeping dla INDEX.md:** adnotacje „czeka na publikację" przy `reklama-outdoor-{krakow,gdansk,lodz,poznan}` i `uchwala-krajobrazowa-reklama` są **nieaktualne**. Sprawdziłem treść na prodzie: Kraków zawiera „Strefa I" + „XXXVI/908/20" (korekta z 07-12 jest live), Gdańsk „XLVIII/1465/18", Łódź „zawieszona", Poznań „LXXXVIII/1671". Korekty prawne zostały wdrożone — INDEX.md wprowadza w błąd i powinien zostać zaktualizowany, żeby nikt nie robił tej pracy drugi raz.

---

## 6. Gdynia i Białystok — powielenie zwalidowanego wzorca lokalizacyjnego

**Wzorzec jest zwalidowany:** cztery artykuły lokalizacyjne zebrały **582 wyświetlenia w 28 dni** (Kraków 238 / poz. 26,8; Poznań 122 / 30,7; Gdańsk 115 / 26,7; Łódź 107 / 29,8) — i to praktycznie od zera, bo blog wrócił do indeksu ok. 28.06 (prev28 cały blog = 1 wyświetlenie). Każdy z tych czterech ma po 1 kliku poza Łodzią. W Bingu ten sam szablon rankuje na poz. 1,2–3,5 — **ale nie należy pod to optymalizować**: fraza `reklama outdoor baner billboard {miasto}` ma w Google 0 wyświetleń i 0 klików przy pozycji 1,2, co raport-bing §8.2 klasyfikuje jako ruch nie-ludzki. Powielamy wzorzec ARTYKUŁU, nie frazę z Binga.

**Gdynia — 194 wyśw / 15 fraz / poz. 29,2.** Frazy: `citylight gdynia` 34/25,8 · `reklama citylight gdynia` 33/34,9 · `reklama na ekranach led gdynia` 29/35,0 · `billboardy gdynia` 22/25,4 · `bilbordy gdynia` 16/25,6 · `bilbordy reklamowe gdynia` 12/29,6 · `reklama w portach lotniczych gdynia` 5/22,4. Wyróżnik z kolejki NN-2: **trolejbusy PKT** (unikat w skali kraju). Podaż: 0 nośników. Osobny artykuł, **nie** rozbudowa Gdańska — frazy są rozłączne (`gdynia` nie wpada do `gdansk`).

**Białystok — 227 wyśw / 5 fraz / poz. 26,4.** To najbardziej skoncentrowany popyt miejski w serwisie: `powierzchnie reklamowe białystok` **119 wyśw / poz. 20,5** · `najem powierzchni reklamowej w białymstoku` 42/28,4 · `reklama na ekranach led białystok` 34/31,3 · `reklama citylight białystok` 24/43,3 · `citylight białystok` 8/32,6. Podaż: **0 nośników w promieniu 89,6 km** (raport-podaz §1) — czyli strona kategorii nigdy nie przekroczy progu 3 i temat jest w 100% blogowy. Wyróżnik z kolejki NN-2: miasto bez tramwajów, transit = autobusy.

**Status w STRATEGY_LOG:** oba są w kolejce NN-2 ze statusem „⏳ do walidacji". **Potwierdzone danymi GSC — zmienić status na zwalidowane, nie robić researchu Ahrefs od zera** (geo-frazy konsekwentnie mają < 100 vol, wzorzec z Łodzi/Gdańska/Katowic/Poznania/Olsztyna/Bydgoszczy — piszemy dla topical authority, jak zapisano w logu).

---

## 7. Klaster podażowy — jedyny temat, gdzie GSC nie pomoże, i jedyny zgodny z fazą produktu

**Fakt twardy:** w 705 frazach non-brand jest **zero zapytań z intencją podażową**. Filtr `zarobić|wynajmę|wydzierżawię|udostępnię|opłaca się|mam działkę|dochód|pasywny` → 0 fraz, 0 wyświetleń. Przefiltrowałem szerzej (`wynaj|dzierżaw|grunt|płot|ogrodzeni|elewacj|ścian`) — 35 fraz / 362 wyśw, ale **wszystkie są popytowe**: `wynajem billboardów warszawa` 49/26,3, `wynajem billboardu warszawa cena` 14/24,9, `bilbordy do wynajęcia warszawa` 8/36,6 — to reklamodawca chcący wynająć, nie właściciel chcący wystawić.

**Jedyne trzy realne sygnały podażowe w całym GSC** (`q3m__query_page.json`):
- `billboard na działce` — 9 wyśw / poz. 25,1 → rankuje `blog/poradniki/billboard-reklama`
- `bilbordy koszt budowy` — 10 wyśw / poz. **7,8** → j.w.
- `wynajem powierzchni reklamowej od osoby fizycznej` — 1 wyśw / poz. 26,0 → `jak-zarobic-na-wynajmie…`

Dwa opublikowane artykuły podażowe zebrały łącznie **27 wyświetleń** (`jak-zarobic-na-wynajmie…` 19 / poz. 12,9 — **najlepszy CTR bloga: 1 klik na 19 wyświetleń = 5,26%**; `czy-oplaca-sie…` 8 / poz. 15,0). Pozycje są dobre, wolumen żaden.

**Wniosek metodologiczny:** brak danych GSC oznacza tu „nie jesteśmy widoczni na tych zapytaniach", a nie „tych zapytań nie ma". **To jedyny temat w tym briefie, który wymaga pełnej ścieżki researchu z persony (ETAP 1 AnswerThePublic → ETAP 2 Ahrefs → ETAP 3 Perplexity)** — wszystkie pozostałe mają walidację w twardych danych GSC.

**Hasła do walidacji (kolejność wg sygnału, jaki mamy):**
1. `billboard na działce` / `ile można zarobić na billboardzie` — jedyne z potwierdzonym śladem (9 + 10 wyśw, poz. 7,8–25,1), dziś obsługiwane przypadkowo przez artykuł o billboardzie dla reklamodawcy
2. `dzierżawa gruntu pod reklamę — umowa i podatek` (NN-1 w kolejce, ⏳)
3. `podatek od wynajmu powierzchni reklamowej — ryczałt 8,5%` (NN-2 w kolejce, ⏳; YMYL — disclaimer obowiązkowy)

**Anty-kanibalizacja:** kotwicą klastra jest `jak-zarobic-na-wynajmie-powierzchni-reklamowej` (JAK: stawki, umowa, kroki), filarem decyzyjnym `czy-oplaca-sie…` (CZY: ryzyka, dla kogo). Nowy artykuł o działce musi być deep-divem (odległości od dróg, kwalifikacja jako obiekt budowlany, payback budowy własnego nośnika) z linkiem do kotwicy — a NIE powtórzeniem tabeli stawek. Fragment o zarobku z billboardu na działce trzeba przy okazji **przyciąć** w `billboard-reklama`, bo to on dziś rankuje na te frazy.

**Uzasadnienie biznesowe (przekazać Biznesowemu):** produkt jest w fazie budowy podaży, a SEO dostarcza **0 właścicieli nośników** — cały ruch organiczny to strona popytowa. Jednocześnie `/dodaj-powierzchnie-reklamowa` ma 24 wyświetlenia, wszystkie z frazy brandowej. Klaster podażowy to jedyna dźwignia SEO zgodna z aktualną fazą; wszystkie pozostałe 6 tematów obsługują popyt, którego dziś nie mamy czym zaspokoić.

---

## Czego NIE pisać (i dlaczego) — lista negatywna

| Temat / działanie | Dlaczego nie |
|---|---|
| Nowe artykuły „reklama na tramwajach Kraków", „citylight Gdańsk" jako osobne URL-e | Rozbija klastry 994 / 1 041 wyśw na słabe URL-e. Wzorzec kanibalizacji już nas kosztuje: 108 fraz ma > 2 nasze strony = 60,2% wyświetleń (raport-gsc §2) |
| Treść informacyjna na stronach kategorii (`/powierzchnie-reklamowe/{miasto}`, `/citylighty`, `/ekrany-led`) | Poniżej progu 3 → `noindex` + poza prerenderem. Powyżej progu — kategoria ma pokazywać oferty, nie poradnik (O1) |
| Poprawki meta/title dla `totemy reklamowe wrocław`, `powierzchnie reklamowe białystok`, `reklama na ekranach led poznan` | 78% „CTR-owych trupów" to szkielet `noindex` — snippet i tak pochodzi ze strony głównej (raport-gsc §3) |
| Optymalizacja pod `reklama outdoor baner billboard {miasto}` (fraza z Binga, poz. 1,2) | 0 wyświetleń w Google, 0 klików przy poz. 1,2 w Bingu, fraza obejmuje Sosnowiec, dla którego nie mamy artykułu — sygnatura rank-trackera (raport-bing §8.2) |
| Rzeszów (20 wyśw), Toruń (17), Szczecin jako priorytet | Wolumen o rząd wielkości mniejszy niż Gdynia/Białystok. Szczecin: artykuł już napisany — wystarczy opublikować (temat 5) |
| Nowe artykuły w silosie `trendy` „bo silos nie działa" | Silos nie ma 0 wyświetleń z powodu treści — 2 z 4 artykułów są zamrożone w 5xx (O3). Najpierw pomiar po odmrożeniu, potem decyzja |
| `reklama na lotnisku` (NN-3) — **odłożyć, nie skreślać** | Klaster REALNY: 235 wyśw / 16 fraz / poz. 38,6, z czego Gdańsk 190 (`reklama na portach lotniczych gdansk` 63/41,3, `reklama w portach lotniczych gdansk` 56/39,7). Ale poz. ~40 = daleko, zero perspektywy podaży (monopol operatorów lotniskowych). **Potwierdzone danymi — zmienić status w kolejce z ⏳ na zwalidowane, priorytet po temacie 6** |
| `reklama w galeriach handlowych` | 83 wyśw / 7 fraz / poz. 36,8 — za mało na osobny artykuł; zmieścić jako sekcję w Poznaniu (`reklama galerie handlowe poznan` 49 wyśw) |

---

## Kolejność operacyjna dla Content Pipeline

1. **Architekt/dev, przed jakimkolwiek pisaniem:** odmrożenie 12 URL-i (Validate Fix w GSC) + deploy frontu (odblokowuje 4 URL-e z `noindex`, w tym 2 opublikowane artykuły prawne).
2. **Publikacja 3 gotowych artykułów** (temat 5) — w tym samym oknie deployowym.
3. **30 dni pomiaru.** KPI dla bloga: wyświetlenia odmrożonych 12 URL-i (dziś 0) oraz liczba fraz w paśmie poz. 10–20. NIE średnia pozycja (raport-gsc §4a: spadek 59→26 to zanik ogona 50+, a nie wejście do strefy klikalnej).
4. Równolegle, bo nie zależy od odmrożenia: **temat 2 (citylight)** — jedyny, w którym artykuł jest zdrowy, rankuje i wymaga tylko jednej sekcji.
5. Po odmrożeniu, wg pomiaru: tematy 1 (transport) i 3 (LED).
6. Tematy 4 → 6 → 7.

Standardowa ścieżka: Strateg (brudnopis do `status/BRUDNOPIS_SEO.md`) → Pisarz → Korektor → `db:seed --class=BlogPostsSeeder` → publikacja w panelu → **deploy frontu**.
Dla tematów 1–4 (rozbudowy istniejących URL-i) obowiązuje uwaga z logu 2026-07-12: **seeder pomija istniejące posty (create-if-not-exists) — refresh wchodzi ręcznie w panelu admina.**

---

# B) DLA BIZNESOWEGO

Każdy sygnał kończy się decyzją w formie „X albo Y". To nie są rekomendacje do rozważenia — to rozwidlenia, które blokują planowanie.

---

## B1. Rozjazd podaż↔popyt jest po TYPIE nośnika, nie tylko po mieście

**Liczby.** Popyt vs podaż (`q3m__query.json` × prod API, 827 ogłoszeń):

| Typ | Popyt (wyśw) | % popytu typowanego | Podaż (szt.) | % podaży |
|---|---:|---:|---:|---:|
| billboard | 2 042 | 33% | **768** | **92,9%** |
| LED / telebim | **1 260** | 20% | 5 | 0,6% |
| citylight | **1 041** | 17% | 12 | 1,5% |
| transport | **994** | 16% | 1 | 0,1% |
| mobilna | 248 | 4% | 6 | 0,7% |
| totem | 154 | 2% | **0** | 0% |
| baner | 130 | 2% | 9 | 1,1% |
| ściana (wall) | 33 | 0,5% | **24** | 2,9% |

Non-billboard = **66,9% popytu przy 7,1% podaży**. Odwrotnie: `wall` to jedyny typ z nadpodażą (24 nośniki / 33 wyśw / poz. 61,4).

Geograficznie: 11 dużych miast = **68,5% popytu (5 806 z 8 471 wyśw) przy 4,8% podaży (40 z 827 nośników)**. Sześć hubów kłodzkich to **253 nośniki (30,6% bazy) → 39 wyświetleń i 0 kliknięć w 3 miesiące** = 0,15 wyśw/nośnik, wobec 34,9 w Poznaniu (przewaga 227×).

Najtańsze odblokowania (raport-podaz §6): **24 nośniki w 10 konkretnych slotach** odblokowują strony, które zebrały **2 484 wyświetlenia = 29,3% ruchu wyszukiwarkowego serwisu**. Te same 24 nośniki w Kłodzku dodają ~1,4 wyświetlenia.

| Slot | Jest | Brakuje | Wyśw. 3 mies. |
|---|---:|---:|---:|
| `/powierzchnie-reklamowe/warszawa` | 2 | **1** | 472 |
| `/reklama-w-transporcie/gdansk` | 0 | 3 | 414 |
| `/reklama-w-transporcie/poznan` | 0 | 3 | 370 |
| `/ekrany-led/poznan` | 0 | 3 | 351 |
| `/powierzchnie-reklamowe/lublin` | 1 | **2** | 174 |
| `/billboardy/warszawa` | 1 | **2** | 153 |
| `/powierzchnie-reklamowe/bialystok` | 0 | 3 | 170 |
| `/banery/lodz` | 0 | 3 | 166 |
| `/powierzchnie-reklamowe/bydgoszcz` | 0 | 3 | 161 |
| `/sciany-reklamowe/poznan` | 2 | **1** | 53 |

**DECYZJA: kolejna akwizycja idzie w ZASIĘG (typ × duże miasto) albo w GĘSTOŚĆ (więcej billboardów w istniejących hubach).**
**Dane wskazują jednoznacznie na zasięg**, bo marginalna wartość nośnika w Kłodzku to 0,06 wyświetlenia miesięcznie, a w slotach z tabeli 50–470 wyświetleń kwartalnie. Konkretnie: **jeden nośnik w Warszawie** (brakuje dokładnie 1 do progu) odblokowuje stronę, która sama zebrała 472 wyświetlenia. To najtańsza pojedyncza akcja w całym audycie.
**Zastrzeżenie do przekazania Marketerowi:** transport miejski (2 sloty z tabeli, 784 wyśw) to inwentarz MPK/ZTM — jeśli nie da się go pozyskać, te dwa sloty wypadają z listy i zostaje 18 nośników / 1 700 wyświetleń. Sprawdzić wykonalność PRZED planowaniem rozmów.

**Wyjątek: Katowice.** 140 nośników w promieniu 30 km, 8 na stronie miasta, 266 wyśw popytu. Tu nie brakuje podaży, tylko prezentacji — mechanizm promienia (`lat/lng/radius`) już istnieje w `AdvertisementController::buildFilteredQuery`. **Decyzja poboczna: rozszerzyć strony miast o promień aglomeracji albo nie.** Dane za: Katowice zyskują 132 nośniki bez jednej rozmowy sprzedażowej. Dane przeciw: dla Białegostoku (0 nośników w promieniu 50 km) i Szczecina (najbliższy 103 km) to nic nie zmienia, więc nie jest to rozwiązanie systemowe.

---

## B2. 463 z 827 nośników (56%) ma status `reserved` i nikt nie wie, czy to prawda

**Profil jest w 100% jednorodny** (raport-podaz §4): wszystkie 463 to `type=billboard`, `variant=standard`, `offer_type=agency`, `available_from = NULL`. Powstały w dwóch batchach importu (2026-06-15: 374 szt., 2026-06-10: 88 szt., +1 z 18.06). **Od 2026-06-18 żaden rekord nie zmienił statusu** — przy typowej rotacji kampanii OOH 2–4 tygodnie po 6 tygodniach coś powinno się zwolnić. W schemacie **nie istnieje pole `reserved_until`** (grep po `backend/app` i migracjach — 0 trafień), czyli rezerwacja nie ma końca.

**Co widzi użytkownik z Google:** na 55 stronach miast w sitemapie leży 730 kafelków, z czego **433 (59%) ma badge „Zarezerwowane"**. Pięć miast ma 100% podaży zarezerwowanej (Ząbkowice Śląskie 31/31, Bielawa 12/12, Braszowice 9/9, Łagiewniki 9/9, Szczytna 8/8). W Dzierżoniowie — jedynym małym hubie z realnym popytem (74 wyśw / 1 klik) — wolny jest **1 nośnik z 18**. Efektywna podaż platformy to **349 pozycji (42,2%)**, nie 827.

**Argument, żeby NIE ruszać progu:** gdyby `reserved` przestało się liczyć do progu thin, pokrycie indeksu spada z **55 → 28 stron miast** i **57 → 29 kombinacji**, a typ `billboardy` z 768 → 305. To połowa mapy kategorii. Dodatkowo `reserved` nie szkodzi indeksacji leafów (próbka GSC n=27: reserved 4 zindeksowane / 5 nie, active 3 / 10 — różnica nieistotna).

**DECYZJA: (X) zweryfikować aktualność rezerwacji u agencji-źródła i dodać pole `reserved_until` z automatycznym wygaszaniem, albo (Y) zostawić stan bez zmian.**
**Dane wskazują na X**, bo: (a) rezerwacja bez daty końca to stan, który się nie odwraca sam — po 6 tygodniach zerowej rotacji to nie jest zajętość, tylko migawka z dnia importu; (b) 5 miast, w których użytkownik z Google widzi wyłącznie „Zarezerwowane", to strony, które kosztują nas zaufanie, a nie brakuje im ruchu; (c) koszt jest mały — jedna migracja + `updateOrCreate` w miejscu (nigdy delete+create, CLAUDE.md).
**Czego NIE robić w żadnym wariancie:** zmieniać progu thin ani wykluczać `reserved` z sitemapy — to jednocześnie decyzja o połowie indeksu, a CLAUDE.md jawnie tego zabrania.
**Pytanie do rozstrzygnięcia przez foundera przed jakąkolwiek zmianą:** czy `reserved` z paczek z 10 i 15 czerwca odwzorowuje zajętość na dzień importu (wtedy jest po prostu przeterminowane), czy oznacza „powierzchnia w portfolio agencji, nie do wynajęcia przez ReklaMap"? Odpowiedź zmienia wszystko — w drugim wariancie to nie jest podaż i nie powinna liczyć się do progu.

---

## B3. Nie ma ani jednej ścieżki mailowej do wystawcy, a 82,6% wystawców czeka na maila

**Liczby.** W całym serwisie są **cztery linki `mailto:` i wszystkie prowadzą na `kontakt@reklamap.pl`** (`AppFooter.vue:56`, `ContactPage.vue:138`, `RegulaminPage.vue:222`, `FaqPage.vue:362`). Na stronie ogłoszenia jest telefon i formularz — **zero adresu wystawcy**. Jednocześnie **683 z 827 wystawców (82,6%) zadeklarowało e-mail jako preferowany kanał kontaktu** (raport-kod). Zdarzenie `contact_email_click` jest zdefiniowane w `analytics.ts:30` i nigdy nie wywołane, bo nie ma czego mierzyć.

**Skutki widoczne w GA4 (91 dni, prod):** 115 unikalnych userów weszło na kartę nośnika → **10 podjęło kontakt (8,7%)**: 7 telefon + 3 formularz. **Od 1 lipca zero kliknięć w telefon** (ostatnie 30.06). Formularz odpalił **3 userów w 3 miesiące**. Najlepszym kanałem popytu jest Organic Search — detal→kontakt **23,5% (4/17)** vs Direct 10,3% i Email 0%.

**Model biznesowy — przypomnienie:** ReklaMap jest platformą, nie brokerem. Zapytanie ofertowe lądujące na `kontakt@reklamap.pl` to **sygnał porażki samoobsługi**, nie lead. Dzisiejszy projekt UI produkuje dokładnie taki efekt: jedyne widoczne adresy e-mail w serwisie należą do platformy.

**DECYZJA: (X) udostępnić kontakt mailowy do wystawcy bezpośrednio na karcie nośnika (mailto z prefillem tematu/ID ogłoszenia albo formularz przekazujący wiadomość na adres wystawcy), albo (Y) zostawić telefon + formularz jak jest.**
**Dane wskazują na X**, bo: (a) 82,6% wystawców i tak oczekuje kontaktu mailowego — dziś nie ma jak go do nich dostarczyć; (b) telefon jako jedyna żywa ścieżka umarł 25 dni temu (0 kliknięć od 1.07); (c) ruch, który dochodzi do karty, konwertuje dobrze (Organic 23,5%) — wąskim gardłem jest liczba dostępnych ścieżek, nie jakość ruchu.
**Warunek brzegowy:** ścieżka ma iść reklamodawca → wystawca. Nie budować niczego, co kieruje zapytania na skrzynkę platformy.

---

## B4. Porównywarka kończy się PDF-em — nie ma jak zapytać o kilka nośników naraz

**Liczby.** `/porownaj` istnieje (`ComparisonPage.vue`, 1 466 linii), umożliwia porównanie, przeliczenie cen na 6 jednostek, pobranie PDF-u, wyczyszczenie listy i powrót do listingów. **Nie ma tam ani jednego CTA kontaktowego** — grep po `AdContactForm` i `mailto` w tym pliku: 0 trafień. Reklamodawca, który wybrał 5 nośników, musi wrócić na 5 kart i wykonać 5 osobnych telefonów.

**Czego nie wiemy:** ile osób w ogóle używa porównywarki. Zdarzenie `analytics.addToComparison` jest zdefiniowane w `analytics.ts:68` i **nigdy nie wywołane** — jedno z 6 martwych zdarzeń w kodzie. `/porownaj` jest też w `robots.txt` (`Disallow: /porownaj`), więc nie ma danych z GSC. **Brak danych o wykorzystaniu funkcji.**

**DECYZJA: (X) najpierw wpiąć pomiar (`addToComparison` + odsłona `/porownaj`), zebrać 30 dni i dopiero potem decydować o zapytaniu zbiorczym, albo (Y) zbudować zapytanie zbiorcze od razu.**
**Dane wskazują na X** — dokładnie dlatego, że danych nie ma. Funkcja kosztowała 1 466 linii i nie wiadomo, czy ktokolwiek jej używa; budowanie na niej kolejnej warstwy to zakład bez informacji. Koszt pomiaru: jedna linia wywołania (XS).
**Jeśli po pomiarze wyjdzie, że porównywarka żyje**, kierunek jest wyznaczony przez B3: jeden formularz → N wiadomości do N wystawców, platforma tylko routuje. Uwaga architektoniczna: `Disallow: /porownaj` należy zostawić — strona ma pozostać poza indeksem.

---

## B5. Kiedy przejść z fazy podaży w fazę popytu

**Stan dzisiaj.** Podaż: 827 ogłoszeń, z tego **349 realnie wolnych (42,2%)**, skupionych w miastach o znikomym wolumenie wyszukiwań (Kłodzko 138, Koszalin 70, Dąbrowa Górnicza 60). Popyt: **695 fraz non-brand → 5 klików / 8 276 wyświetleń w 3 miesiące (CTR 0,06%)** — i było tak samo w maju, na szczycie sprzed deindeksu (4 011 wyświetleń / 4 kliki). **66% wyświetleń na stronach kategorii trafia na URL-e z zerową podażą.** 95,6% inwentarza ogłoszeniowego (789 z 825 leafów) nie ma ani jednego wyświetlenia w 3 miesiące.

Ruch akwizycyjny idzie kanałem Direct (467 sesji / 235 userów — cold calle foundera) i Email (45 sesji / 17 userów, najwyższy engagement 0,71). Lejek podaży: home → `/dodaj` przepuszcza **19,5%**, ale kto ukończy pierwszy krok, w **93,3%** publikuje.

**DECYZJA: (X) kampania popytowa czeka na próg podażowy, albo (Y) ruszamy teraz, bo widoczność wróciła.**
**Dane wskazują na X, a próg da się zapisać liczbą:** kampania rusza, gdy **wszystkie 10 slotów z tabeli B1 przekroczy 3 nośniki (24 sztuki)** — wtedy 29,3% dzisiejszego ruchu wyszukiwarkowego przestaje lądować na stronach `noindex` z zerem ofert i trafia na strony z ofertą.
Argumenty przeciw Y: (a) lipcowy wzrost wyświetleń (162/dzień, powyżej poziomu majowego 156) **nie przełożył się na ruch** — sesje GA4 spadły o 71% (12,7/dzień w maju → 3,7 w lipcu), bo nowe wyświetlenia siedzą na pozycji 31; (b) skierowanie płatnego lub outreachowego ruchu na miasta bez podaży to spalenie pierwszego wrażenia u reklamodawcy, którego potem nie ma jak odzyskać; (c) 6 z 11 największych miast ma **zero** nośników i generuje 2 753 wyświetlenia (32,5% popytu).

**Rzecz do zdecydowania równolegle, bo nie zależy od progu:** Email jest najbardziej zaangażowanym kanałem (engagement 0,71, sesja 98,4 s, konwersja 17,6% na usera), a **51% jego sesji ląduje na generycznej home**, która przepuszcza dalej 19,5%. Strona `/dodaj-powierzchnie-reklamowa` — jedyna z realną gęstością konwersji (196,8 s na odsłonę, engagement 0,92) — dostaje z maila **0 sesji**. Decyzja: kierować outreach do właścicieli nośników bezpośrednio na `/dodaj-powierzchnie-reklamowa` z `utm_content`, albo dalej na `/`. Dane wskazują na pierwsze; koszt: zmiana linku w szablonie.

---

## B6. Tracimy dokładnie te strony, które rankowały — i nie ma polityki retencji

**Liczby.** Z 71 leafów, które zebrały wyświetlenia w 3 miesiące, **32–34 nie istnieje już w bazie** (ID 1–47, pierwotny seed z ręcznie pisanymi opisami). Odpowiadały za **411–427 wyświetleń (71% ruchu leafowego)** i **7 z 12 kliknięć leafowych w kwartale**. Ich pozycje: 2,7 · 3,9 · 5,2 · 5,9 · 5,9 · 6,3 · 8,0 — nieporównywalnie lepsze niż cokolwiek, co mamy dziś. Wszystkie zwracają **HTTP 200 + szkielet `noindex`** (soft-404), a nie 410. To także jedyne źródło rich snippetów: PRODUCT_SNIPPETS spadły 157 → 22 wyświetlenia (7×) dokładnie w tym oknie.

**DECYZJA: (X) polityka retencji — wygasły nośnik dostaje status `unavailable` i URL zostaje, albo (Y) nośnik znika z bazy i URL zwraca 410 Gone.**
**Dane wskazują na X dla nośników, które mogą wrócić** (sezonowość OOH), **i na Y dla trwale usuniętych** — ale w obu wariantach **HTTP 200 na nieistniejącym ogłoszeniu jest błędem**. Dodatkowo: jeśli któreś z tych 32 ogłoszeń wciąż fizycznie istnieje, przywrócenie musi iść przez `updateOrCreate` pod tym samym ID (delete+create zmienia ID → zmienia URL → zeruje statystyki, CLAUDE.md).
**Konsekwencja dla akwizycji:** bez tej polityki każdy nowy nośnik ma tę samą trwałość co tamte 32 — czyli inwestycja w podaż wycieka tą samą dziurą, którą zasypujemy.

---

## B7. Duplikacja opisów blokuje indeksację leafów, a zero linków blokuje pozycje

**Duplikacja.** 827 ogłoszeń ma **456 unikalnych tytułów i 598 unikalnych opisów**; **345 ogłoszeń (41,7%) dzieli parę tytuł+opis z innym** (122 klastry). Opisy z importu są szablonowe („Billboard o wymiarach … w lokalizacji … Powierzchnia reklamowa w portfolio agencji…"). Próbka GSC (n=27): **56% leafów jest w stanie „wykryte — obecnie niezindeksowana"**, ekstrapolacja: ~460 z 825. Jakość pól nie jest problemem (100% cena, 98,1% wymiary, 100% zdjęcie) — problemem jest unikalność.

**DECYZJA: (X) kolejny import wymaga unikalnego opisu per nośnik (choćby generowanego z lokalizacji, otoczenia i widoczności), albo (Y) importujemy dalej tym samym szablonem.**
**Dane wskazują na X:** import 768 billboardów Big Group nie przełożył się na widoczność (4,4% leafów ma jakiekolwiek wyświetlenie), a kolejne 400 rekordów tym samym szablonem pogłębi problem near-duplicate zamiast go rozwiązać.

**Linki.** Bing zna **zero** domen linkujących (`GetLinkCounts`, `GetUrlLinks`, `GetConnectedPages` — wszystkie puste). GA4 potwierdza niezależnie: 100% referrali to webmail (zasobygwp.pl 11 sesji, poczta.onet.pl 6, poczta.wp.pl 3), Facebook (7) i podglądy linków w Teams (1). Zero domen redakcyjnych.

**DECYZJA: (X) wydzielić budżet czasu na pozyskanie pierwszych linków, albo (Y) skalować dalej samą treścią.**
**Dane wskazują na X:** przy 9 790 wyświetleniach i średniej pozycji 31,4 treść dowozi ekspozycję, ale nie pozycje — a to pozycja, nie ekspozycja, decyduje o kliknięciach (CTR jest monotoniczną funkcją pozycji: poz. 47,4 → 0%, 34,4 → 0,29%, 26,3 → 0,45%, 16,8 → 2,04%, raport-cwv). Naturalni kandydaci: agencje, których nośniki już importujemy (Big Group, Outdoor 3miasto, reklama.ai, Optokom) — każda ma stronę i powód, żeby linkować do swojej oferty na ReklaMap. Konkretna lista to research Stratega, nie treść tej decyzji.

---

## Trzy decyzje, które blokują resztę

1. **Akwizycja: zasięg zamiast gęstości.** 24 nośniki w 10 slotach = 2 484 wyświetlenia (29,3% ruchu serwisu); te same 24 w Kłodzku = 1,4 wyświetlenia. Jeden nośnik w Warszawie odblokowuje stronę z 472 wyświetleniami. Priorytet typów: citylight, LED, transport, mobilna — 66,9% popytu przy 7,1% podaży.
2. **`reserved` 463/827: zweryfikować u agencji i dodać datę wygaśnięcia.** Rezerwacja bez `reserved_until`, zamrożona od 18.06, przy 5 miastach ze 100% zajętości. Nie ruszać progu thin (indeks spadłby z 55 do 28 stron miast).
3. **Ścieżka kontaktu: e-mail do wystawcy bezpośrednio z karty nośnika.** 82,6% wystawców czeka na maila, w serwisie nie ma ani jednego `mailto:` do wystawcy, telefon ma 0 kliknięć od 1 lipca, a formularz obsłużył 3 userów w 91 dni.

---

*Nie commitowano, nie deployowano, nie seedowano produkcji, nie wysyłano maili. Nowe zapytania w tej sesji: wyłącznie odczyt `GET api.reklamap.pl/api/blog` (weryfikacja, które artykuły są opublikowane) i lokalna analiza plików GSC.*
