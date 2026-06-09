# Audyt techniczny SEO — ReklaMap

Prowadzony przez Agenta Architekta SEO. Najnowszy audyt na górze. Statusy aktualizować przy wdrożeniach.

---

## 2026-06-09 — pełny audyt + DECYZJA (nowy zrzut GSC „Stan indeksowania")

Źródło: zrzut GSC „Dlaczego strony nie są zindeksowane" z 2026-06-09 + **żywe testy produkcji** (curl jako Googlebot: robots.txt, `/`, 18 URL-i z sitemap, `/porownaj`, `/zarzadzaj`) + re-read stanu wdrożonego: `frontend/index.html`, `frontend/src/composables/useSeo.ts`, `frontend/public/.htaccess`, `backend/routes/web.php` (sitemap).

**⚠️ KOREKTA wcześniejszej (chat) tezy „pusty shell = strony nie wchodzą do indeksu".** Surowy HTML faktycznie jest generycznym shellem dla każdego URL (ten sam `<title>`, brak og:/JSON-LD — `useSeo.ts` wstrzykuje meta dopiero klient-side). ALE to NIE jest przyczyną niezaindeksowania: Googlebot renderuje JS (dowód: 165+ stron w indeksie i rankujące frazy), a bucket „Wykryta–niezindeksowana" **spadł 149 (29.05) → 80 (09.06)** mimo niezmienionej architektury. To potwierdza, że lek z 29.05 (przycięcie crawl surface) **działa** i recovery trwa. Hipoteza „prerender potrzebny do indeksacji" pozostaje **odrzucona** (zgodnie z poz. #3 z 29.05).

### Porównanie bucketów (delta 29.05 → 09.06)

| Bucket GSC | 29.05 | 09.06 | Odczyt |
|---|---|---|---|
| Wykryta – niezindeksowana | 149 | **80** | 🟢 −69, główny efekt przyciętej sitemapy + recovery |
| Zeskanowana – niezindeksowana | 12 | 12 | bez zmian, cienkie agregaty na granicy progu |
| Błąd serwera (5xx) | 30 | 30 | **zaległa walidacja** — 18/18 testów live = 200, brak w przyciętej sitemapie |
| Nie znaleziono (404) | 28 | 28 | jw. — stare/zewnętrzne URL-e poza sitemapą, wygasają |
| Wykluczona `noindex` | — | 10 | „Niepowodzenie" walidacji = nadal noindex (cienkie <3 — zamierzone, ktoś omyłkowo odpalił walidację) |
| Przekierowanie | — | 35 | www→non-www + legacy 301 + 301 sitemapy — konsolidacja, benign |
| Zablokowana robots.txt | — | 4 | `/porownaj`, `/zarzadzaj` itp. — zamierzone |
| Alternatywna z canonical | — | 4 | warianty kanonikalizowane — OK |
| 401 | — | 1 | chroniona ścieżka — OK |

### 🎯 DECYZJA (o co prosił użytkownik)

| # | Kwestia | Decyzja | Uzasadnienie |
|---|---|---|---|
| A | **Przywrócić prerender / SSR dla indeksacji?** | **NIE.** | Recovery potwierdzone danymi (149→80, green rośnie, 18/18 live=200, 165+ w indeksie). Prerender nie ruszy igły (nie wyrenderuje stron, których bot z braku priorytetu nie crawl-uje) i przywraca tryb awarii 429/quota z 18.05. |
| B | **Czy architektura SPA-only ma realną lukę?** | **TAK — jedną: social/non-JS og:.** | Surowy HTML nie ma og:title/og:image/og:description (jest tylko `<meta description>`). Google renderuje JS → indeks OK, ale FB/LinkedIn/WhatsApp/Slack/Twitter NIE renderują → puste/generyczne podglądy udostępnianych linków. Istotne PRZY AKTYWNYM OUTREACHU (maile z linkami, pitch agencyjny, cold calling). |
| C | **Jak załatać B bez powrotu do prerendera?** | Lekki **PHP og-shim** tylko dla UA scraperów social (NIE Googlebot): pobiera dane ogłoszenia z `api.reklamap.pl` i wstrzykuje og: do `index.html`. Bez headless, bez Node, bez quota/429. | Reużywa martwego wzorca `prerender-proxy.php`, ale uproszczonego do string-inject meta z JSON. P2 — do greenlightu, nie blokuje recovery. |

### Akcje operacyjne (nie-kod, po stronie użytkownika w GSC)

| # | Akcja | Priorytet | Status |
|---|---|---|---|
| 1 | **5xx (30) i 404 (28): walidacja JUŻ W TOKU („Rozpoczęto") — czekać, ZERO akcji.** GSC wygasza „Sprawdź poprawność" w trakcie walidacji (nie da się zrestartować — i nie wolno anulować, bo licznik rusza od zera). 5xx: live 18/18=200 → przejdzie sama (dni–~4 tyg). 404: może wrócić „Niepowodzenie" i to OK — stare/zewnętrzne URL-e poza sitemapą, wypadną przez re-crawl niezależnie od wyniku walidacji. | ⚪ | ⏳ w toku po stronie Google, bez akcji usera |
| 2 | **noindex (10): wyeksportuj listę URL z GSC.** Jeśli to cienkie `<3 ogł.` → zostawić, NIE walidować (noindex jest celowy). Jeśli któraś POWINNA być w indeksie → zgłoś, to bug do naprawy. | 🟡 | ⏳ czeka na eksport URL |
| 3 | Przekierowanie (35), robots (4), canonical (4), 401 (1) — **bez akcji**, zamierzone/benign, konsolidują się same. | ⚪ | — |

### Rekomendacja kodu (C) — ✅ WDROŻONA W KODZIE 2026-06-09 (czeka na deploy `dist/`)

**Problem:** `frontend/index.html` miał tylko `<meta name="description">` i `<title>` — zero og:/twitter w surowym HTML. `useSeo.ts` wstrzykuje og:/twitter/JSON-LD, ale klient-side (po JS).
**Ryzyko SEO/biznes:** udostępniony link (np. agencja wkleja ofertę na Slacku/LinkedIn) renderuje pusty/generyczny karton zamiast „Billboardy Warszawa – wynajem" + zdjęcie → niższy CTR z social/DM w trakcie akwizycji.
**Co zbudowano (3 pliki):**
- **`frontend/public/og-meta.php`** (NOWY) — shim: parsuje `{id}` z końcówki sluga ogłoszenia, GET `api.reklamap.pl/api/listings/{id}` (nagłówek `X-App-Key`, timeout 5s), wstrzykuje do `index.html` `<title>` + og:/twitter z pól ogłoszenia (typ→etykieta, miasto, lokalizacja, wymiary z konwersją LED→mm, cena, pierwsze zdjęcie ze `STORAGE_URL`). Szablon **lustrzany** wobec `AdDetailPage.vue`. Fallback (błąd API / ogłoszenie nieaktywne / strona nie-ogłoszeniowa) = czysty `index.html` (zawsze 200, nigdy 5xx). Log diagnostyczny: `?showlog=rm2024debug`.
- **`frontend/public/.htaccess`** — przed SPA-fallback dodany routing **tylko dla UA scraperów social** (facebookexternalhit, linkedinbot, slackbot, whatsapp, discordbot, telegrambot, twitterbot, pinterest, redditbot, embedly…) → `og-meta.php`. **ŚWIADOMIE bez googlebot/bingbot** — Google renderuje JS sam (useSeo).
- **`frontend/index.html`** — dodane statyczne, domyślne og:/twitter (website) → home/kategorie/blog i fallback shima mają sensowny karton; `useSeo`/`og-meta.php` je nadpisują per-route.
**Testy 2026-06-09 (php built-in server + curl UA Facebook, żywe API prod):** ogłoszenie 111 → og podmienione na „Billboardy Nowa Ruda – wynajem", og:image = realne zdjęcie ze storage, og:type=product, **zero duplikatów**; home/blog/nieistniejące ID → baseline `og:type=website` bez wywołania API; `php -l` czysto. Każdy przypadek = HTTP 200.
**Deploy:** wgrać na `reklamap.pl` (docroot=`dist/`): `og-meta.php`, zaktualizowany `.htaccess`, przebudowany `index.html` (`npm run build` kopiuje `public/*` do `dist/` i przetwarza `index.html` — przy okazji odświeża stary `dist/.htaccess` z poz. #5 z 29.05). Po deployu: walidator FB Sharing Debugger / LinkedIn Post Inspector na URL ogłoszenia.
**Przewidywany zysk:** bogate podglądy linków na FB/LinkedIn/WhatsApp/Slack/Twitter → wyższy CTR udostępnień w outreachu. Zero wpływu (pozytywnego ani negatywnego) na indeksację Google.

---

## 2026-05-29 — audyt na bazie briefu Analityka (spadek ruchu od 18.05)

Źródło: brief Analityka 2026-05-29 (`ANALYTICS_LOG.md` — impresje GSC 389 @18.05 → ~80 @27.05, poz. 24→32) + **żywe testy produkcji** (curl jako Googlebot) + przegląd `frontend/public/.htaccess`, `prerender-proxy.php`, `index.html`, `useSeo.ts`, `backend/routes/web.php`, `frontend/src/config.ts`.

**Ustalona topologia produkcji:** frontend SPA na `reklamap.pl` (docroot = `frontend/dist`), backend Laravel na **`api.reklamap.pl`** (`API_URL=https://api.reklamap.pl/api`). To kluczowe dla #1.

**Korekta hipotezy Analityka:** spadek od 18.05 to NIE „SPA zamiast prerendera" jako główna przyczyna. Wyłączenie prerendera (18.05) było *lekiem* na 5xx 429, nie sprawcą. Realny mix: (a) dogasający efekt deindeksu z incydentu 5xx 16–18.05 (Google zrzuca URL-e tygodniami po błędzie), (b) **martwa sitemapa na kanonicznym hoście** (#1 — nowe), (c) wolniejsze indeksowanie surowego SPA. Nie zgadujemy proporcji — weryfikujemy w GSC (#2).

| # | Problem | Plik / miejsce | Kto | Wysiłek | Priorytet | Status |
|---|---|---|---|---|---|---|
| 1 | **Higiena: `reklamap.pl/sitemap.xml` oddaje HTML (SPA-fallback łapie URL przed Laravelem).** ⚠️ KOREKTA 2026-05-29: to NIE jest krytyczne — GSC pokazuje, że `api.reklamap.pl/sitemap.xml` jest **przesłana i czytana od 19.04, stan „Sukces", 267 wykrytych URL-i**. Discovery działa. `robots.txt` deklaruje jednak `reklamap.pl/sitemap.xml` (serwuje HTML) — niespójność hosta do uporządkowania, ale nie przyczyna spadku ruchu | `frontend/public/.htaccess` (przed SPA-fallback) | dev | ~10 min | ⚪ (zdegradowane z 🔴) | ✅ WDROŻONE I ZWERYFIKOWANE 2026-05-29 — produkcja: `reklamap.pl/sitemap.xml` → `HTTP 301 → api.reklamap.pl/sitemap.xml` (curl ✓). GSC: oba submission „Sukces", 267 URL-i. Oba można zostawić (identyczna treść przez 301) |
| 2 | **Diagnoza spadku — ROZSTRZYGNIĘTA 2026-05-29 (dane GSC od usera).** Indeks: **165 zaindeksowanych / 272 nie**. Dominująca przyczyna niezaindeksowania = **„Wykryta — obecnie niezindeksowana"** z `Ostatnie zindeksowanie: Nie dotyczy` (bot NIGDY nie zeskanował). Lista to w ~90% cienkie agregaty małych miejscowości (`/powierzchnie-reklamowe/{wioska}` + duplikat `miasto×typ`). Wniosek: NIE rendering (165 zaindeksowanych + rankujące frazy = SPA renderuje się OK), tylko **crawl prioritization na młodej domenie z przerośniętą powierzchnią cienkich stron + dołek crawl-rate po 5xx** | GSC Indeksowanie stron | użytkownik | — | 🔴→✅ | ✅ ZDIAGNOZOWANE — przekierowuje priorytet na #4 (crawl surface) |
| 3 | ~~Decyzja o przywróceniu prerendera (self-host)~~ — **ZAMKNIĘTE 2026-05-29: NIE ROBIMY.** Dane (#2) obaliły hipotezę „SPA się nie renderuje": 165 stron zaindeksowanych dowodzi, że Google renderuje SPA poprawnie. Prerender nie rusza igły — nie wyrenderuje stron, których bot nie crawl-uje. Infra (`prerenderReady`, `prerender-proxy.php`) zostaje w repo na wypadek, gdyby kiedyś zabolały social previews/Bing — ale to NIE jest lek na obecny spadek | — | — | — | ❌ ODRZUCONE (błędna hipoteza) |
| **2b** | **🎯 GŁÓWNY LEK: przyciąć crawl surface.** Rozbicie 272 niezaindeksowanych (dane GSC 2026-05-29): **149 „Wykryta–niezindeksowana"** (nigdy nie crawl-owana) + **12 „zeskanowana, niezindeksowana"** = cienkie agregaty. Sitemap generowała URL dla KAŻDEGO miasta i KAŻDEJ pary typ×miasto **bez progu**, podczas gdy front (`ListingsPage.vue` `THIN_PAGE_THRESHOLD=3`) te same strony oznaczał `noindex` → sprzeczny sygnał. Plus 30×5xx (goi się) + 28×404 (soft-404, patrz #6) | **backend:** `routes/web.php` sitemap | dev | ~0.5 dnia | 🔴 | ⏳ KOD WDROŻONY 2026-05-29 — `havingRaw('COUNT(*) >= 3')` na query miast i typ×miasto (próg = THIN_PAGE_THRESHOLD frontu). Front BEZ ZMIAN (noindex <3 już był). Leaf-ogłoszenia bez progu. `php -l` ✓, query odpala ✓. ✅ WDROŻONE I ZWERYFIKOWANE NA PRODUKCJI 2026-05-29: sitemap **267 → 157 URL-i** (110 cienkich agregatów usuniętych); wioski z 1 ogł. (szopinek/biskupice/...) NIEOBECNE, miasta ≥3 (Radom/Koszalin/Warszawa/Kalisz/Lublin) obecne — wisienka Lublin (3 ogł.) ocalała. **Pozostało (opcjonalnie): GSC „Sprawdź poprawność" na raporcie 5xx** |
| 4 | **CTR-trupy z briefu** (`reklama tranzytowa kraków` poz 3.55/0, `reklama citylight olsztyn` 7.8/0, `powierzchnie reklamowe lublin` 7.7/0, `citylighty warszawa` 8.1/0). Title/desc szablonowe wdrożone 2026-05-12 (audyt niżej #3), ale „0 klików" może być wtórne do deindeksu 5xx/SPA | `AdDetailPage.vue`, `categoryDescriptions.ts` | — | — | 🟡 | ⏳ rewalidować PO 2b + recovery 5xx (nie ruszać, póki nie wiadomo, czy to CTR czy indeksacja) |
| 6 | **28× 404 w GSC — ZBADANE 2026-05-29: brak defektu kodu.** URL-e bez kategorii (`/blog/ile-kosztuje-reklama-outdoor`) obsługuje shim `router.ts:110-130` (`/blog/:slug` → fetch po slug → redirect na `/blog/{kategoria}/{slug}`, przy braku → not-found). URL-e do nieistniejących artykułów (`/blog/rynek-ooh/...`, `/blog/pozwolenie-na-billboard-...`) NIE są nigdzie linkowane (grep czysty) — stare/zewnętrzne wejścia, wypadną same. Zostaje architektoniczny **soft-404 SPA** (każda ścieżka → 200 + index.html; not-found tylko klient-side) — wymaga SSR/prerendera, NIE naprawiamy teraz | `router.ts` (shim OK), architektura SPA | — | — | ⚪ | ✅ ZBADANE — brak akcji (poza ew. przyszłym SSR dla twardego 404) |
| 5 | **Lokalna higiena:** `frontend/dist/.htaccess` (artefakt builda, maj 18) jest STARSZY niż `public/.htaccess` — brak legacy-301 i węższa lista botów. Produkcja OK (legacy `dodaj-ogloszenie`→301 żywy, czyli prod ma wersję z `public/`), ale lokalny `dist/` myli. Przebudować przy następnym deployu | `frontend/dist/` (rebuild) | dev | ~2 min | ⚪ | TODO — nie wpływa na produkcję |

### Rekomendacja #1 — fix sitemapy (gotowy snippet)
W `frontend/public/.htaccess`, **przed** blokiem `# SPA fallback` (l.70), dodać 301 na host backendu (Google podąża za przekierowaniem sitemapy):
```apache
# Sitemapa generowana dynamicznie przez backend (api.reklamap.pl) — przekieruj,
# żeby nie złapał jej SPA-fallback i nie oddał HTML zamiast XML.
RewriteRule ^sitemap\.xml$ https://api.reklamap.pl/sitemap.xml [R=301,L]
```
Alternatywa (bez ruszania `.htaccess`): zmienić w `robots.txt` linię `Sitemap:` na `https://api.reklamap.pl/sitemap.xml` i zgłosić ten URL w GSC — działa, jeśli usługa GSC jest typu **Domena** (`reklamap.pl`) albo `api.reklamap.pl` jest osobno zweryfikowana. Wariant z 301 jest pewniejszy (nie zależy od typu usługi). Po wdrożeniu: GSC → Mapy witryn → przesłać `https://reklamap.pl/sitemap.xml`, sprawdzić status „Pobrano pomyślnie".
**Zysk:** przywrócenie kanału discovery/refresh z `lastmod` → szybsza (re)indeksacja ogłoszeń i kategorii, wsparcie recovery po 5xx.

---

## 2026-05-18 — INCYDENT: 5xx w GSC na 31 URL-ach (od 16.05.2026)

Zgłoszenie z Google Search Console: „Błąd serwera (5xx)", 31 stron, pierwsze wykrycie 16.05.2026, pełny przekrój (home, blog, kategorie, ogłoszenia). Użytkownik sprawdził link w przeglądarce — działał.

| # | Problem | Plik / miejsce | Kto | Wysiłek | Priorytet | Status |
|---|---|---|---|---|---|---|
| 1 | `prerender-proxy.php` ślepo przekazywał kod odpowiedzi prerender.io do Googlebota (`http_response_code($httpCode)`, linia 42). 5xx/timeout prerender.io (a `$httpCode=0` przy padzie curl) → Googlebot dostawał 5xx, choć SPA dla ludzi działa (ścieżka bot vs człowiek rozdzielona w `.htaccess:62-65` po User-Agent) | `frontend/public/prerender-proxy.php`, `frontend/dist/prerender-proxy.php` | dev | ~30 min | 🔴 | ✅ kod wdrożony 2026-05-18 — graceful fallback: przy braku 2xx/3xx lub pustej odpowiedzi proxy serwuje lokalny `index.html` z kodem 200 (nigdy 5xx do bota); ostateczność = 503 + `Retry-After` zamiast gołego 5xx; log dopisuje `Served: prerender|fallback-spa`. **Pozostało: deploy `dist/` na produkcję + w GSC „Sprawdź poprawność" na raporcie 5xx** |

**Weryfikacja źródła:** wbudowany log proxy — `https://reklamap.pl/prerender-proxy.php?showlog=rm2024debug` — szukać `HTTP: 5xx` / `HTTP: 0` z datami od ~16.05. Po deployu te same wpisy powinny mieć `Served: fallback-spa` (200) zamiast propagacji 5xx.

**Działanie następcze:** ustalić *dlaczego* prerender.io zwraca 5xx (limit konta / timeout renderu ciężkiego SPA z mapą). Fallback ratuje indeks, ale prerender daje lepsze SEO niż surowy SPA — to obejście, nie rozwiązanie przyczyny.

| # | Lek na przyczynę | Plik / miejsce | Kto | Priorytet | Status |
|---|---|---|---|---|---|
| 2 | **Brak `window.prerenderReady`** w całym froncie → prerender.io leci na pełny timeout na ciężkich stronach (mapa Leaflet + async API) → 504/5xx + spalanie limitu konta przy każdym crawlu | `frontend/index.html` (init `=false`), `frontend/src/composables/useSeo.ts` (`signalPrerenderReady()` po `updateMetaTags`, flip `=true` gdy jest realny title) | dev | 🔴 | ✅ kod wdrożony 2026-05-18, `vue-tsc` czysto. Czeka na deploy `dist/` |
| 3 | Weryfikacja limitu/błędów po stronie dostawcy | dashboard prerender.io (konto, quota, error rate, cache hit) | użytkownik | 🟠 | TODO — sprawdzić czy 5xx to quota czy timeout renderu |
| 4 | Crawl surface za duży — każda cienka kombinacja typ×miasto to osobny render | `noindex` na cienkich kombinacjach (powiązane z poz. #4 audytu 2026-05-12) | dev | 🟡 | TODO — mniej URL-i do renderu = mniej spalonego limitu |

**ROZSTRZYGNIĘCIE 2026-05-18 (log produkcyjny):** `prerender-proxy.php` dochodzi, ale prerender.io oddaje **`HTTP 429 | Size 0` na każdy request bota** (Googlebot/AdsBot/Bingbot, cały dzień) — **trial prerender.io wygasł**, nie timeout. Dodatkowo log nie ma kolumny `Served:` → wcześniejszy deploy fixu proxy nie wszedł na produkcję. Decyzja: zamiast naprawiać deploy proxy / stawiać self-host — **najprostsza droga: całkowite wyłączenie prerendera**.

| # | Rozwiązanie finalne | Plik | Status |
|---|---|---|---|
| 5 | Zakomentowany blok przekierowania botów do `prerender-proxy.php` — boty dostają ten sam SPA (200) co użytkownik, Googlebot renderuje JS sam. Zero PHP/Node/cache do utrzymania, zero kosztu, źródło 429 znika trwale. `prerender-proxy.php` zostaje w repo (martwy) na wypadek przyszłego self-hostu | `frontend/public/.htaccess`, `frontend/dist/.htaccess` (blok Prerender zakomentowany) | ✅ kod 2026-05-18. Deploy = wgranie samego `.htaccess` (czytany live, bez restartu lsphp) |

**Niuans poz. 2:** flip następuje przy pierwszym realnym `title`. Strony data-driven ustawiają `seoOptions` w watcherze po API (np. `AdDetailPage` na `[ad, similarAds]`) → snapshot z prawdziwą treścią. Gdyby któraś strona ustawiała generyczny title przed danymi — snapshot byłby minimalnie wczesny, ale i tak 200 z poprawnym meta (nieporównywalnie lepsze niż 5xx). Jeśli GSC pokaże ubogie snapshoty — dociążyć per-stronę.

---

## 2026-05-12 — audyt na bazie pierwszego przeglądu Analityka (`ANALYTICS_LOG.md`)

Źródło: dane GSC/GA4 z 14 kwi–11 maj 2026 + przegląd kodu (`useSeo.ts`, `analytics.ts`, `index.html`, `AdDetailPage.vue`, `ListingsPage.vue`, `categoryDescriptions.ts`).

| # | Problem | Plik / miejsce | Kto | Wysiłek | Priorytet | Status |
|---|---|---|---|---|---|---|
| 1 | Zdarzenia kontaktu zdefiniowane w `analytics.ts`, ale nie wpięte w widok ogłoszenia | `frontend/src/views/AdDetailPage.vue`, `frontend/src/components/detail/AdContactForm.vue` | dev | ~1 h | 🔴 | ✅ kod wdrożony 2026-05-12 (viewAd, clickPhone, sendAdMessage). Pozostało: deploy + oznaczyć kluczowe zdarzenia w GA4 Admin |
| 2 | Duplikacja www vs non-www — Google miał zaindeksowane wersje `www` z kodem 200 | `frontend/public/.htaccess` (już zawiera 301 `www`→non-www, dodane ~2026-05-07); `frontend/src/utils/url.ts` (`appUrl = https://reklamap.pl`); `backend/.env` (`FRONTEND_URL=https://reklamap.pl` ✓) | — | — | 🟠→✅ | ✅ FAKTYCZNIE ZAŁATWIONE — `.htaccess` robi 301 `www.reklamap.pl/*` → `reklamap.pl/*`; GSC potwierdza (URL-e `www` w raporcie indeksowania mają status „Strona zawiera przekierowanie"). Krok w panelu histido („Wymuś przekierowanie") — ZBĘDNY, redundantny. Pozostało tylko czekać, aż Google skonsoliduje stare wpisy `www` (200) na wersję kanoniczną — dzieje się automatycznie. |
| 3 | `<title>`/`<meta description>` strony ogłoszenia budowane z surowego tytułu wystawcy (często bełkot) zamiast szablonu z parametrów | `frontend/src/views/AdDetailPage.vue` (watcher `[ad, similarAds]`, `seoOptions`) | dev | ~0.5 dnia | 🟡 | ✅ kod wdrożony 2026-05-12 — title `{Typ} {Miasto} – wynajem \| ReklaMap`, description z typu/miasta/lokalizacji/wymiarów/ceny + CTA; surowy tytuł wystawcy zostaje jako `<h1>`. Czeka na deploy |
| 4 | Strony typ+miasto bez treści per-miasto → thin content, fallback „X ofert" | `frontend/src/data/categoryDescriptions.ts`, `frontend/src/views/ListingsPage.vue` | Pisarz + dev | ~1–2 dni | 🟡 | ✅ kod wdrożony 2026-05-12 — (1) Pisarz dodał 10 opisów typ+miasto (citylighty: olsztyn/gdynia/bydgoszcz; transport: krakow/poznan; mobilna: warszawa/krakow/bydgoszcz; totemy: poznan; banery: lodz) + opis miasta Olsztyn, zweryfikowane przez Korektora; (2) `ListingsPage.vue` — dla kombinacji typ×miasto bez ręcznego wpisu syntezuje unikalny opis (pierwsze zdanie opisu typu + pierwsze zdanie opisu miasta + zachęta) zamiast pokazywać generyczny opis typu; `<title>`/`<meta description>` strony typ×miasto są teraz miastowo-specyficzne (`{Typ} {Miasto} – wynajem \| ReklaMap`) zamiast generycznego title typu. Typecheck + testy czyste. Pozostało (opcjonalnie): wzmocnić istniejące opisy `billboardy/[miasto]` które rankują 23–36. Czeka na deploy |
| 5 | GA Measurement ID na sztywno w `index.html`; brak filtra ruchu wewnętrznego w GA4 | `frontend/index.html`; GA4 Admin | devops | ~15 min | ⚪ | TODO |

### Szczegóły

**#1 — pomiar kontaktów.** `frontend/src/utils/analytics.ts` ma gotowe: `analytics.clickPhone()`→`contact_phone_click`, `clickEmail()`→`contact_email_click`, `sendAdMessage()`→`contact_form_submit`, `viewAd()`→`view_item`. W `AdDetailPage.vue` były wpięte tylko `startAddAd`/`finishAddAd` (stąd `add_listing_*` w GA4) — kontaktowe nie. Wdrożono 2026-05-12:
- `analytics.viewAd()` w `loadAd()` (`AdDetailPage.vue`) — `view_item` przy otwarciu ogłoszenia
- `analytics.clickPhone()` w `handlePhoneCall`/`handleShowPhone` (`AdDetailPage.vue`) — `contact_phone_click` przy odsłonięciu numeru
- `analytics.sendAdMessage()` w `submitContactForm` po udanym POST (`AdContactForm.vue`) — `contact_form_submit`
`clickEmail` nie wpięty, bo w widoku ogłoszenia nie ma linku `mailto:` (kontakt = telefon + formularz). `vue-tsc --noEmit` czysto. **Pozostało (poza kodem):** (1) deploy frontu na produkcję; (2) GA4 Admin → Wyświetlanie danych → Zdarzenia → oznaczyć `contact_phone_click`, `contact_form_submit`, `add_listing_success` jako kluczowe (lub utworzyć je z wyprzedzeniem przez „Utwórz kluczowe zdarzenie", bo `contact_phone_click`/`contact_form_submit` jeszcze nigdy nie wpadły).

**#2 — www/non-www.** Brak 301 (to konfiguracja nginx/Apache, poza repo). `backend/config/cors.php` ma wpisany `https://www.reklamap.pl` → www realnie zwraca 200. Canonical we frontendzie jest z `appUrl`, ale SPA — Googlebot widzi obie wersje 200 zanim wykona JS. Fix: 301 z jednej wersji na drugą na poziomie serwera (sugestia: bez `www`), `appUrl` ma zwracać stały host kanoniczny (nie `window.location.host`), sprawdzić zgodność `APP_URL` w `backend/.env` produkcji.

**#3 — title ogłoszenia.** W `AdDetailPage.vue` `seoOptions.title` = `${newAd.title} | ReklaMap` — czyli surowy tytuł od wystawcy. Przy słabym tytule („citylight olsztyn dywizjonu 303 sikorskiego przystanek tramwajowy") SERP wygląda jak bełkot mimo poz. 6. Fix: `<title>` i `description` z szablonu (`${typeLabel} ${city}${street ? ', '+street : ''} – wynajem | ReklaMap`), surowy tytuł zostaje jako `<h1>`. Zgodne z wytyczną z `CLAUDE.md` (Title/Description ogłoszeń mają zawierać Miasto/Typ/Cena).

**#4 — transport per-miasto.** `categoryDescriptions.ts` ma opis dla `reklama-w-transporcie` (kategoria), brak wariantów per-miasto. `ListingsPage.vue` schodzi wtedy do `${typeLabel} – ${cityName}` + `${listings.length} ofert.` = thin. Uwaga: „poz. 1.67, 0 klików" z raportu Analityka przy 12 impresjach to szum statystyczny, nie kryzys CTR — realny problem jest strukturalny (brak unikalnej treści). Fix: dopisać wpisy per-miasto (start: Kraków, Poznań, Łódź — mają impresje), `title` z frazą transakcyjną; systemowo wzbogacić fallback szablonu kombinacji typ+miasto do ≥150 słów z parametrów.

**#5 — GA4.** Tag działa i jest stabilny (dane za 14 kwi–11 maj kompletne; wcześniejsze „brak danych" to był zakres dat / glitch UI, nie zepsuty tag). ID `G-0ZL0NS8F9W` na sztywno w `index.html` — niezgodne z duchem „config w env", ale niski priorytet. Filtr ruchu wewnętrznego (wyklucz IP founder'a/dev) w GA4 Admin — niski wysiłek, naprawia zawyżony Direct widoczny u Analityka.

### Rekomendowana kolejność
1 → 2 (razem ~pół dnia, odblokowują pomiar i konsolidują SEO kategorii) → 3 → 4 → 5.
