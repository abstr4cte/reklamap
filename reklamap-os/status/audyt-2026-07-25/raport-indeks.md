# Raport: zdrowie indeksacji reklamap.pl — 2026-07-25

Autor: Agent Architekt SEO. Wszystkie liczby z żywego proda (GSC URL Inspection API, `curl -A Googlebot`,
prod API, sitemapy) + kodu w repo. Dane surowe w tym samym katalogu scratchpad
(`insp_nonleaf.json`, `insp_leaf.json`, `crawl_nonleaf_gbot.json`, `crawl_leaf60.json`,
`sitemap.xml`, `sitemap-api.xml`, `prod_listings.json`, `gsc_sitemaps.json`).

Wykonano **183 inspekcje URL Inspection API** (158 URL-i nieleafowych = 100% tej klasy z sitemapy,
25 leafów = próba losowa) + **218 pobrań HTML jako Googlebot** (158 nieleaf + 60 leaf).

---

## 0. TL;DR — 5 rzeczy, które faktycznie bolą

| # | Problem | Skala | Źródło |
|---|---|---|---|
| 1 | 12 URL-i bloga zamrożonych w werdykcie „Błąd serwera (5xx)" od **2026-05-15** (71 dni). Dziś żywe zwracają 200. | **0 wyświetleń w 3 mies.** na tych 12 (kontrolna szóstka zdrowych: 1 532 wyśw.) | `insp_nonleaf.json`, `q3m__page.json` |
| 2 | Sitemapa frontu (983 URL) rozjechana z backendową (987). 4 URL-e są **zgłoszone do Google** przez `api.reklamap.pl/sitemap.xml`, a serwowane jako `noindex, follow` szkielet 7 929 B | 2 opublikowane artykuły + 2 nośniki niindeksowalne od 13.07 (12 dni) | `sitemap-api.xml` vs `sitemap.xml`, `gsc_sitemaps.json` |
| 3 | 4 kategorie bloga: **0 z 4 zindeksowanych**, żadna nie ma seedu `__INITIAL_STATE__`, a `BlogPage.vue` przy błędzie fetcha nadaje `noindex` | 0/4 | `insp_nonleaf.json`, `BlogPage.vue:133-137,152` |
| 4 | Google nigdy nie pobrał **47%** URL-i nieleafowych i **72%** próby leafów | 74/158 i 18/25 | `insp_nonleaf.json`, `insp_leaf.json` |
| 5 | `robots.txt: Disallow: /*?_v=` **zamraża** stare werdykty „zindeksowane, ale zablokowane" — przeciek generujący te URL-e jest zamknięty od 07.07 | 8 URL-i wg GSC UI (per briefing) | `robots.txt`, `git log -S "_v=" frontend/index.html` |

Rzeczy, które **działają** i nie należy ich ruszać: prerender leafów i kategorii (218/218 stron = 200
+ `index, follow`, 158/158 z canonicalem = własny URL), seed `__INITIAL_STATE__` na wszystkich trasach
stanowych (148/158 nieleaf + 60/60 leaf), 301 www→non-www z zachowaniem ścieżki, próg
`THIN_PAGE_THRESHOLD=3` (0 konfliktów w obie strony — patrz §4).

---

## 1. Próbka URL-i z sitemapy — werdykty (pkt 1 zlecenia)

Reprezentanci klas (URL Inspection API, `sc-domain:reklamap.pl`):

| URL | verdict | coverageState | robots | googleCanonical = userCanonical? | ostatni crawl |
|---|---|---|---|---|---|
| `/` (home) | PASS | Strona przesłana i zindeksowana | ALLOWED | tak (`https://reklamap.pl/`) | 2026-07-25 |
| `/powierzchnie-reklamowe` (hub) | PASS | przesłana i zindeksowana | ALLOWED | tak | 2026-07-08 |
| `/powierzchnie-reklamowe/billboardy` (typ) | PASS | przesłana i zindeksowana | ALLOWED | tak | 2026-07-24 |
| `/powierzchnie-reklamowe/klodzko` (miasto) | PASS | przesłana i zindeksowana | ALLOWED | tak | 2026-07-18 |
| `/powierzchnie-reklamowe/koszalin` (miasto) | PASS | przesłana i zindeksowana | ALLOWED | tak | 2026-07-18 |
| `/powierzchnie-reklamowe/billboardy/klodzko` (kombinacja) | PASS | przesłana i zindeksowana | ALLOWED | tak | 2026-07-18 |
| `…/polanica-zdroj/billboard-…-507` (leaf) | NEUTRAL | **Wykryta – obecnie niezindeksowana** | — | brak (nie pobrany) | nigdy |
| `/blog/poradniki/billboard-reklama` (artykuł) | PASS | przesłana i zindeksowana | ALLOWED | tak | 2026-07-16 |

**Rozjazd canonicali: ZERO.** W 158 inspekcjach nieleafowych ani razu `googleCanonical != userCanonical`.
W 218 pobraniach HTML ani razu `<link rel=canonical>` != własny URL. Sitemapa nie zawiera ani jednego
URL-a z `www` (0/983), prerenderowane pliki w `dist/` też nie (`grep -rl www.reklamap.pl dist --include=index.html` = 0).
`hreflang` nie występuje nigdzie (site jednojęzyczny — OK).

### Pełny rozkład pokrycia (158 URL-i nieleafowych = całość klasy)

| coverageState | n | % |
|---|---|---|
| Strona przesłana i zindeksowana | 68 | 43,0% |
| Strona wykryta – obecnie niezindeksowana | 53 | 33,5% |
| Adres URL jest Google nieznany | 21 | 13,3% |
| **Błąd serwera (5xx)** | **12** | **7,6%** |
| Strona zeskanowana, ale jeszcze nie zindeksowana | 4 | 2,5% |

Z podziałem na klasy:

| klasa | n | zindeksowane | % |
|---|---|---|---|
| home + hub + statyczne | 7 | 6 | 86% |
| typy + miasta (`/powierzchnie-reklamowe/X`) | 61 | 24 | 39% |
| kombinacje typ×miasto | 57 | 21 | 37% |
| artykuły bloga | 28 | 16 | 57% |
| **kategorie bloga** | **4** | **0** | **0%** |
| leafy (próba 25 z 825) | 25 | 7 | 28% |

Szacunek całości: **~299 z 983 URL-i w indeksie (~30%)**. Dla leafów próba n=25 daje 28%
(95% CI ok. 12–49%) → 99–404 z 825. Nie mam pełnego przeglądu — to szacunek z próby, nie pomiar.

---

## 2. „Zindeksowane, ale zablokowane przez robots.txt" — werdykt zamrożony (pkt 2 zlecenia)

**Wszystkie 5 wskazanych URL-i są DZIŚ `robotsTxtState=ALLOWED`, `verdict=PASS`, zindeksowane:**

| URL | verdict | robots | ostatni crawl |
|---|---|---|---|
| `/powierzchnie-reklamowe/poznan` | PASS / przesłana i zindeksowana | ALLOWED | 2026-07-21 |
| `/powierzchnie-reklamowe/citylighty` | PASS / przesłana i zindeksowana | ALLOWED | 2026-07-22 |
| `/blog/poradniki/citylight-reklama` | PASS / przesłana i zindeksowana | ALLOWED | 2026-07-17 |
| `/blog/poradniki/ile-kosztuje-reklama-outdoor` | PASS / przesłana i zindeksowana | ALLOWED | 2026-07-16 |
| `/powierzchnie-reklamowe/billboardy/dabrowa-gornicza` | PASS / przesłana i zindeksowana | ALLOWED | 2026-07-17 |

Czysty URL nie jest i nigdy nie był blokowany — `robots.txt` blokuje wyłącznie `/api/`, `/zarzadzaj/`,
`/porownaj`, `/*?_v=`, `/*&_v=`. Raport GSC dotyczy **wariantów z `?_v=`**, a nie czystych ścieżek; GSC UI
grupuje je pod nazwą strony. Nie mam eksportu tego raportu z UI, więc dokładnej listy 8 URL-i **nie
potwierdzam — brak danych**. Potwierdzam natomiast, że werdykt jest **zamrożony przez samą blokadę**:
Google nie może pobrać `?_v=`, więc nigdy nie zobaczy, że powinien je odrzucić.

Sprawdzone punktowo: `/blog/prawo-i-regulacje/oplata-reklamowa?_v=1781487146877` (URL, który realnie
zaindeksował Bing — `GetPageStats.json`) → GSC: **„Adres URL jest Google nieznany"**. Google go nie ma.
W danych GSC Search Analytics za 3 mies. (232 strony) **ani jednej** z `_v=` (`grep -c "_v=" Strony.csv` = 0).
Czyli: strata ruchu z tego tytułu ≈ 0; problem jest kosmetyczny (raport w GSC) + zżera crawl budget.

---

## 3. WWW vs NON-WWW (pkt 3 zlecenia)

### 3a. 301 działa poprawnie

```
https://www.reklamap.pl/                                          → 301 → https://reklamap.pl/
https://www.reklamap.pl/powierzchnie-reklamowe/totemy-reklamowe/wroclaw
                                → 301 → https://reklamap.pl/powierzchnie-reklamowe/totemy-reklamowe/wroclaw
http://reklamap.pl/  → 301 → https://reklamap.pl/    (server: LiteSpeed)
```
Reguła: `frontend/public/.htaccess:54-55` (`RewriteCond %{HTTP_HOST} ^www\.reklamap\.pl$ [NC]`).
Ścieżka jest zachowana, kod 301, cel HTTPS non-www. **Bez zarzutu.**

### 3b. Google JUŻ wybrał non-www — konsolidacja canonicali działa

Inspekcja `https://www.reklamap.pl/powierzchnie-reklamowe/totemy-reklamowe/wroclaw`:
- `googleCanonical = https://reklamap.pl/...` (non-www), `userCanonical = https://reklamap.pl/...`
- ale: `verdict=NEUTRAL`, `coverageState = Strona wykluczona za pomocą tagu „noindex"`, `indexingState=BLOCKED_BY_META_TAG`

Inspekcja wersji non-www: **identyczna** — też `BLOCKED_BY_META_TAG`, ten sam `lastCrawlTime` 2026-07-12T03:05:13Z.
To jeden i ten sam zasób w oczach Google.

### 3c. Dlaczego „po 2,5 miesiąca nie skonsolidowane" — bo cel konsolidacji też jest noindex

To NIE jest awaria kanonizacji. 45 z 60 URL-i `www` w GSC (**1 050 z 1 180 wyświetleń = 89%**) to
ścieżki, których **dziś nie ma w sitemapie**, czyli nie są prerenderowane, czyli `.htaccess:100-103`
oddaje im `spa-fallback.html` = 7 929 B, `<meta name="robots" content="noindex, follow">`,
**bez `<link rel=canonical>`**. Zweryfikowane `curl -A Googlebot`:

```
/powierzchnie-reklamowe/bialystok            200  7929 B  noindex, follow  brak canonical
/powierzchnie-reklamowe/ekrany-led/poznan    200  7929 B  noindex, follow  brak canonical
/powierzchnie-reklamowe/totemy-reklamowe/wroclaw 200 7929 B noindex, follow brak canonical
```

Skoro cel 301 jest `noindex` i bez canonicala, Google nie ma dokąd przenieść rankingu — zostawia
w wynikach historyczny wpis `www` do naturalnego wygaśnięcia. Wyświetlenia `www` maleją zgodnie z tą
tezą: **prev28 = 417 wyśw. (33,3% ruchu) → last28 = 306 (6,9%)**, ostatnie 14 dni 4–18 wyśw./dzień
(`gsc_page_date_14d.json`, świeże zapytanie GSC 11–25.07).

Reszta — **15 URL-i `www` obecnych w sitemapie, 130 wyświetleń (1,1% całości)** — to jedyna realna
niekonsolidowana duplikacja. Skala: pomijalna.

Jedyne miejsce w kodzie odwołujące się do `www`: `backend/config/cors.php:24` (`'https://www.reklamap.pl'`
na liście dozwolonych originów). Nieszkodliwe, ale i niepotrzebne po 301.

**Ciekawostka do §7 briefingu:** `/powierzchnie-reklamowe/totemy-reklamowe/wroclaw` ma 74 wyśw. na
pozycji 10,9 — a w całej bazie prod jest **0 totemów** (rozkład typów z 827 ogłoszeń: billboardy 768,
ściany 24, citylighty 12, banery 9, mobilna 6, LED 5, inne 2, transport 1, **totemy 0**).

---

## 4. Sitemapa i próg cienkiej strony (pkt 4 zlecenia)

### 4a. Liczby

- `https://reklamap.pl/sitemap.xml`: **983 URL-e** (319 282 B). To bajt w bajt plik z `frontend/dist/sitemap.xml`
  z **2026-07-13 13:52** (`cmp` = identyczne) — statyczny artefakt ostatniego builda.
- `https://api.reklamap.pl/sitemap.xml`: **987 URL-i** (320 440 B) — generowane na żywo z bazy.
- Skład sitemapy frontu: 825 leaf, 61 typ/miasto, 57 kombinacji, 28 artykułów, 4 kategorie bloga,
  1 hub, 1 home, 6 statycznych.
- **Obie sitemapy są zgłoszone w GSC** (`gsc_sitemaps.json`):
  `reklamap.pl/sitemap.xml` — 983 web, ostatnio pobrana 2026-07-23; `api.reklamap.pl/sitemap.xml` —
  987 web, ostatnio pobrana 2026-07-24. Błędów 0.

### 4b. Spójność `THIN_PAGE_THRESHOLD=3` w 3 miejscach — POTWIERDZONA

1. `frontend/src/utils/listingsSeo.ts:12` → `export const THIN_PAGE_THRESHOLD = 3`
2. `backend/routes/web.php:80` → `$thinPageThreshold = 3;` stosowany w pętli TYPÓW (`:90-93`),
   MIAST (`:108,117`) i KOMBINACJI (`:141,146`)
3. `frontend/scripts/prerender.mjs:188` → trasa stanowa bez danych **nie jest zapisywana**
   (`if (needState && !ssrJson) { stats.err++; … return; }`)

Weryfikacja empiryczna na żywych danych (827 ogłoszeń z prod API vs 983 URL-e z sitemapy, z tym samym
foldem diakrytyków i myślników co `PL_FOLD`):

- URL-e w sitemapie, dla których front policzy < 3 oferty (→ `noindex`): **0**
- Miasta z ≥3 ofertami poza sitemapą: **0**
- Kombinacje typ×miasto z ≥3 ofertami poza sitemapą: **0**
- Typy z ≥3 ofertami poza sitemapą: **0**

Potwierdzone też pobraniem: **158/158 URL-i nieleafowych i 60/60 leafów zwraca `index, follow`**.
Żadnego „Submitted URL marked noindex" z tej klasy. **Niezmiennik trzyma.**

### 4c. …ale jest „Submitted URL marked noindex" z INNEJ przyczyny

Różnica sitemap API − front = **4 URL-e**, wszystkie zgłoszone Google'owi przez `api.reklamap.pl/sitemap.xml`:

```
/blog/prawo-i-regulacje/pozwolenie-na-tablice-reklamowa      200  7929 B  noindex, follow  title="Wynajem powierzchni reklamowych w Polsce | ReklaMap"
/blog/prawo-i-regulacje/reklama-bez-pozwolenia-kary          200  7929 B  noindex, follow  jw.
/powierzchnia-reklamowa/billboardy/jablonowo/…-997           200  7929 B  noindex, follow  jw.
/powierzchnia-reklamowa/billboardy/nowa-wies-elcka/…-998     200  7929 B  noindex, follow  jw.
```

Mechanizm: backend generuje sitemapę z bazy → nowa treść (artykuł, nośnik) natychmiast trafia do
sitemapy `api`. Front serwuje **zamrożoną kopię z ostatniego builda**, a `.htaccess:100-103` każdemu
URL-owi bez prerenderowanego pliku oddaje `spa-fallback.html` z `noindex`. Efekt: **czas do indeksacji
nowej treści = czas do następnego `frontend/deploy.sh`**. Dziś dryf wynosi 12 dni.

To jest strukturalne, nie incydentalne: każdy nowy nośnik i każdy opublikowany artykuł ma ten problem.

### 4d. Tripwire ma martwe pole na dokładnie ten przypadek

`backend/app/Console/Commands/SeoTripwire.php:74` czyta sitemapę z `$base . '/sitemap.xml'` — czyli
**z frontu**, a więc z tego samego zamrożonego pliku. Z definicji nie może wykryć driftu front↔API.
Dodatkowo `:93-98` próbkuje (regex artykułu: `:97`) kombinację, kategorię, leaf i **artykuł** bloga (`#/blog/[^/]+/[^/]+$#`),
ale **nie kategorię bloga** (`/blog/poradniki` — jeden segment) — czyli tę klasę stron, która ma 0/4
indeksacji. I zgodnie z CLAUDE.md odpala się tylko przy deployu, więc drift powstający *między*
deployami nie ma szans go wywołać.

---

## 5. Błędy 5xx (pkt 5 zlecenia)

### 5a. Na żywo 5xx NIE WYSTĘPUJE

Test 4 URL-i × 5 UA (Googlebot, bingbot, zwykły Chrome, Google-InspectionTool, GoogleOther):
**20/20 odpowiedzi 200** dla `reklamap.pl`, czasy 0,11–0,14 s. `api.reklamap.pl/sitemap.xml` = 200 dla
wszystkich UA. `api.reklamap.pl/api/listings` = 403 dla wszystkich UA **bez** `X-App-Key` (to poprawne
zachowanie `VerifyAppKey`, nie WAF). Preflight `OPTIONS` z `Origin: https://reklamap.pl` → **204** z pełnym
zestawem nagłówków CORS.
Pobranie 158 + 60 stron jako Googlebot: **218/218 = HTTP 200**.

### 5b. To zamrożony werdykt z 2026-05-15 — wszystkie 12 na blogu

`pageFetchState = SERVER_ERROR` dla 12 z 158 URL-i nieleafowych. **Wszystkie mają
`lastCrawlTime = 2026-05-15`** — jeden dzień, jeden batch, 71 dni temu. Ani jednego 5xx z późniejszą datą
w 183 inspekcjach.

```
2026-05-15  /blog/poradniki                                  ← kategoria
2026-05-15  /blog/prawo-i-regulacje                          ← kategoria
2026-05-15  /blog/lokalizacje/reklama-outdoor-warszawa
2026-05-15  /blog/lokalizacje/reklama-outdoor-wroclaw
2026-05-15  /blog/poradniki/baner-reklamowy-cena
2026-05-15  /blog/poradniki/jak-wybrac-powierzchnie-reklamowa
2026-05-15  /blog/poradniki/reklama-na-samochodzie
2026-05-15  /blog/poradniki/reklama-w-transporcie-publicznym
2026-05-15  /blog/poradniki/reklama-zewnetrzna
2026-05-15  /blog/poradniki/tablica-reklamowa
2026-05-15  /blog/trendy/telebim-ekran-led-reklama
2026-05-15  /blog/trendy/totem-reklamowy
```

Data pasuje do awarii runtime'owego prerender.io: CLAUDE.md datuje jego wygaśnięcie na **2026-05-18**
(429 → deindeks), a `.htaccess:88-91` pokazuje, że blok `prerender-proxy.php` łapał wtedy
`googlebot|adsbot-google|bingbot|…`. 15.05 to końcówka wyczerpywania limitu. Zgodne z Bing:
`Code5xx = 0` na 14 656 pobrań (bo Bing crawluje ~10× rzadziej i nie trafił w okno).

### 5c. Koszt: te 12 URL-i ma DOKŁADNIE ZERO wyświetleń

| grupa | wyświetlenia 3 mies. | kliki |
|---|---|---|
| 12 URL-i zamrożonych w 5xx | **0** | **0** |
| 6 zdrowych artykułów (kontrola: `billboard-reklama` 814, `reklama-outdoor-krakow` 238, `citylight-reklama` 136, `…-poznan` 122, `…-gdansk` 115, `…-lodz` 107) | **1 532** | 6 |

Cały blog w 3 mies.: 1 787 wyświetleń na **19** URL-ach — bo 12 z 32 nie istnieje dla Google.
Wśród zamrożonych są `reklama-outdoor-warszawa` i `reklama-outdoor-wroclaw` — dwa największe rynki,
podczas gdy odpowiedniki dla Krakowa/Poznania/Gdańska/Łodzi robią 582 wyświetlenia.

`lastmod` nie jest przyczyną: 11 z 12 ma `lastmod = 2026-07-13`, identyczny jak zdrowy
`billboard-reklama`, który został pobrany 16.07 i jest zindeksowany. Google po prostu trzyma stary
werdykt i nie ponawia.

---

## 6. Przeciek `?_v=` — mechanizm ustalony (pkt 6 zlecenia)

Nie zgaduję — oś czasu z `git log`:

| data | commit | zdarzenie |
|---|---|---|
| 2026-04-14 | `cbe4bb9` | dodano guard stale-deploy z `location.replace(… '_v=' + now)` — **bez żadnej bramki UA** |
| 2026-05-12 | `90598cd` | do `robots.txt` dodano `Disallow: /*?_v=` |
| 2026-06-16 | `a3d1f22` | prerender build-time (`prerender.mjs`) |
| 2026-07-07 | `8f02064` | **dodano bramkę UA** (`if (!ua \|\| /bot\|crawl\|spider\|slurp\|…/i.test(ua)) return;`) — `frontend/index.html:36` |

Przez **84 dni (14.04 → 07.07)** guard działał dla każdego UA. Wystarczyło, że przy renderowaniu strony
Googlebot/Bingbot dostał błąd ładowania `<script>`/`<link>` (a WRS rutynowo odrzuca część zasobów), by
listener `error` wykonał `location.replace(pathname + '?_v=' + Date.now())`. Bot lądował na nowym URL-u
z pełną treścią, HTTP 200, `index, follow`. Bing zdążył taki URL zaindeksować:
`/blog/prawo-i-regulacje/oplata-reklamowa?_v=1781487146877` w `GetPageStats.json` (1 wyświetlenie).

Weryfikacja pozostałych hipotez — wszystkie **odpadają**:
- w sitemapie: `grep -c "?" sitemap-urls.txt` = **0** URL-i z query stringiem
- w linkach: `grep -c 'href="[^"]*_v='` na prerenderowanej stronie = **0**
- prerender: `outPath()` (`prerender.mjs:111`) zapisuje wyłącznie ścieżki z sitemapy — nie tworzy plików z query
- Service Worker (`frontend/public/sw.js:39`) cache'uje nawigacje, ale nie generuje URL-i i nie dotyczy botów

**Stan dzisiaj:** przeciek zamknięty (18 dni). Ale `?_v=` nadal serwuje **pełną, indeksowalną kopię**:

```
/powierzchnie-reklamowe/klodzko?_v=1781487146877  →  200, 182 060 B, "index, follow",
                                    canonical = https://reklamap.pl/powierzchnie-reklamowe/klodzko
```

Canonical jest poprawny — Google skonsolidowałby to w jednym crawlu. Blokuje to **własny
`Disallow: /*?_v=`**: Google nie może pobrać URL-a, więc nie zobaczy canonicala i nie porzuci wpisu.
Blokada, która miała problem rozwiązać, teraz go konserwuje.

---

## 7. Prerender — czy każdy URL z sitemapy ma swój plik (pkt 7 zlecenia)

- Lokalny `frontend/dist`: **983 pliki `index.html`** = dokładnie 983 URL-e z `dist/sitemap.xml`. 1:1.
- Żywy prod, 158 URL-i nieleafowych: **158× HTTP 200**, 158× `index, follow`, 0 fałszywych empty-state
  („Nie znaleziono ogłoszeń"), mediana HTML 81 405 B (min 32 343, max 219 124).
- Żywy prod, 60 losowych leafów: **60× 200**, 60× `index, follow`, 60× `__INITIAL_STATE__`,
  średnio 4,0 bloki `application/ld+json`, mediana 68 475 B.

### 7a. Luka: 10 stron z sitemapy BEZ seedu `__INITIAL_STATE__`

```
/dodaj-powierzchnie-reklamowa, /kontakt, /regulamin, /polityka-prywatnosci, /faq   (statyczne — OK)
/blog, /blog/poradniki, /blog/trendy, /blog/prawo-i-regulacje, /blog/lokalizacje   (POBIERAJĄ DANE!)
```

Pięć ostatnich to **realny problem**. `main.ts:50-72` (`__collectSSRState`, kolektor: `:50`, zwrot `:67`) zbiera tylko `search`, `ad`,
`blogPost`, `nav` — **listy postów nie zbiera wcale**. `prerender.mjs:116-117` (`needsState`) obejmuje
tylko `/`, `/powierzchnie-reklamowe*`, `/powierzchnia-reklamowa*`, więc dla bloga nie ponawia i nie
wymaga danych. A `BlogPage.vue`:

```js
// :128-138 (loadBlogPosts)
catch (error) { blogPosts.value = [] } finally { isLoading.value = false }
// :151-153
watch([filteredPosts, isLoading], () => {
  noindexEmptyCategory.value = !isLoading.value && filteredPosts.value.length === 0
                               && selectedCategory.value !== 'wszystkie'
})
```

Czyli: Vue montuje się od zera, kasuje prerenderowaną listę, fetchuje `/blog` z cross-origin
`api.reklamap.pl`; jeśli fetch nie zdąży lub padnie → `blogPosts = []`, `isLoading = false` →
**`noindex` na kategorii bloga**. To ta sama klasa awarii, którą CLAUDE.md opisuje dla listingów
(`resultCount = null` → NIE thin) i którą tam zamknięto bramką `hasLoaded`. Tutaj nie ma ani bramki,
ani seedu.

Wynik: **0 z 4 kategorii bloga jest zindeksowanych** (2× 5xx z 15.05, 1× „wykryta, niezindeksowana",
1× „nieznana Google"). Nie da się rozstrzygnąć, ile z tego to 5xx, a ile fałszywy `noindex` —
ale luka w kodzie jest realna i niezależna od 5xx.

### 7b. Odkrywalność: leafy praktycznie sieroce

`referringUrls` z inspekcji: w próbie 25 leafów **17 nie ma żadnego źródła odkrycia**, 2 mają wyłącznie
sitemapę, 6 ma linki wewnętrzne. Hub `/powierzchnie-reklamowe` zawiera 98 unikalnych linków wewnętrznych
(24 miasta, 24 kombinacje, **24 leafy**, 4 blog) — czyli 24 z 825 nośników, 2,9%. Reszta zależy od
paginowanych list, których bot nie klika.

---

## 8. Uboczne obserwacje

- Home ma w `referringUrls` spamowy backlink: `https://best-backlink-provider.com.in/pge-d606f196d0aa1080f8d901a196bb6f04.html`
  (inspekcja `/`, 2026-07-25). Pojedynczy — na razie tylko do odnotowania.
- `spa-fallback.html` (7 929 B) nie ma `<link rel=canonical>`. Strona `noindex` bez canonicala nie
  przekazuje żadnego sygnału konsolidacji — patrz §3c.
- `/faq` jest „Zeskanowana, ale jeszcze nie zindeksowana" z crawlem **2026-05-09** (77 dni);
  `/powierzchnie-reklamowe/sciany-reklamowe` — 2026-05-07 (79 dni). Świeżość crawla dla 84 pobranych
  URL-i: 07/2026 = 67, 06/2026 = 2, 05/2026 = 15.
- Bing API `GetCrawlIssues` i `GetBlockedUrls` zwracają puste tablice — **brak danych** o tym, które
  konkretnie URL-e złożyły się na `BlockedByRobotsTxt = 5 255`.
- Statusy na prodzie (827 ogłoszeń przez API): `reserved` 463, `active` 349, `soon_available` 15,
  zero `draft`/`unavailable` z `is_active=1` → licznik sitemapy (`is_active`) i licznik frontu
  (`AdvertisementController.php:218`) liczą ten sam zbiór. Brak rozjazdu.

---

## 9. Rekomendacje w kolejności zwrotu z pracy

1. **Odmrozić 12 URL-i bloga (5xx).** Strony zwracają 200 od dawna; potrzebny jest jedynie sygnał do
   Google. W GSC → raport „Błąd serwera (5xx)" → **Sprawdź poprawkę / Validate Fix**, plus
   „Poproś o zindeksowanie" dla `/blog/lokalizacje/reklama-outdoor-warszawa`,
   `/blog/lokalizacje/reklama-outdoor-wroclaw`, `/blog/poradniki/reklama-zewnetrzna`,
   `/blog/poradniki/tablica-reklamowa`, `/blog/poradniki` (API indeksujące tego nie robi — to akcja w UI).
   Koszt: kilkanaście minut. Potencjał: 12 stron × poziom porównywalnych zdrowych = rząd 1 000+ wyświetleń/kwartał.
2. **Zlikwidować dryf sitemap.** Najprostszy wariant bez zmian w architekturze: **wyrejestrować
   `api.reklamap.pl/sitemap.xml` z GSC** (przestaje zgłaszać URL-e, których front nie umie obsłużyć)
   i traktować deploy frontu jako obowiązkowy krok publikacji treści. Wariant docelowy: po publikacji
   artykułu / imporcie nośników automatycznie uruchamiać prerender (choćby tylko dla nowych ścieżek).
   Do decyzji właściciela — nie zmieniam nic sam.
3. **Zaszyć listę postów w seedzie.** W `main.ts` `__collectSSRState` dorzucić `blogList`, w `BlogPage.vue`
   seedować z `window.__INITIAL_STATE__.blogList` i **nie ustawiać `noindex`, dopóki nie było udanego
   fetcha** (odpowiednik `hasLoaded`/`resultCount = null` z `listingsSeo.ts`). W `prerender.mjs`
   rozszerzyć `needsState` o `/blog` i `/blog/<kategoria>`.
4. **Dodać kategorie bloga do tripwire'a** (`$pick('#/blog/[^/]+$#')` z `needState => true`) i czytać
   w nim sitemapę z **API**, nie z frontu — inaczej narzędzie z definicji nie widzi driftu z pkt. 2.
5. **Usunąć `Disallow: /*?_v=` z `robots.txt`.** Przeciek jest zamknięty od 07.07 (bramka UA w
   `index.html:36` — jej NIE ruszać), a `?_v=` serwuje poprawny canonical do czystego URL-a. Odblokowanie
   pozwoli Google jednym crawlem skonsolidować i porzucić te wpisy; utrzymanie blokady konserwuje je
   w nieskończoność. Ryzyko: znikome (0 wyświetleń w GSC z `_v=`).
6. **Dodać `<link rel="canonical">` do `spa-fallback.html`** (canonical = żądany URL). Nie zmienia
   `noindex`, ale daje Google punkt zaczepienia przy porzucaniu relików `www`.
7. **Odkrywalność leafów** — 17/25 bez źródła odkrycia, hub linkuje 24 z 825. To osobny, większy temat
   (linkowanie z kart miast, „podobne nośniki w okolicy"); wymaga decyzji produktowej, nie wrzucam
   go w ten raport jako gotowy plan.

Nie zrobiłem żadnych zmian w repo, nie commitowałem, nie deployowałem, nie seedowałem prod, nie wysłałem maili.
