# Audyt techniczny SEO — ReklaMap

Prowadzony przez Agenta Architekta SEO. Najnowszy audyt na górze. Statusy aktualizować przy wdrożeniach.

---

## 2026-06-26 — noindex miast: w WIĘKSZOŚCI POPRAWNY (thin) + fix migotania dla zdrowych miast

**Zgłoszenie:** GSC URL Inspection (live 18:18) `…/warszawa`: „Pobranie: Udało się", „Indeksowanie: Nie — noindex". User: „z prerender.io było OK".

**⚠️ KOREKTA po weryfikacji puppeteerem na żywym prodzie (przechwycenie odpowiedzi API):** noindex Warszawy jest **POPRAWNY**. Realne liczby (`/api/listings?city=…&city_strict=1`): **Warszawa total=2, Kraków=0** → <3 (próg thin) → celowy noindex. To NIE bug. Zdrowe miasta SĄ index: **Poznań=18** (prerender ma `ItemList numberOfItems=18`, zawsze index), **`/powierzchnie-reklamowe`=821** (zawsze index). **Prawdziwy lewar = PODAŻ nośników w dużych miastach, nie kod.** Pierwsza diagnoza („krytyczny false-noindex zabijający strony miast") była przeszacowana — większość thin-miast jest słusznie wykluczona.

**Żywa diagnoza (curl jako Googlebot + git):**

| Sprawdzenie | Wynik |
|---|---|
| Statyczny prerender (curl, bez JS) | `<meta robots>` = **`index, follow`** dla wszystkich miast (warszawa/kraków/gdańsk/poznań/wrocław), pełna treść |
| GSC live test (z JS) | **`noindex`** — sprzeczność z curl → różnica = **Google hydratuje JS** |
| robots.txt | OK, `/` Allow (to NIE robots) |
| `AdDetailPage.vue:629` `noindex:true` | tylko w `catch` 404 — leafy bezpieczne |

**Root cause (mechanizm):** `ListingsPage.vue` liczył `noindex` z `resultCount`, a `useSearchStore` startuje `isLoading=false`, `listings=[]`, `serverTotal=0`. Przy hydratacji u Googlebota, ZANIM `onMounted` odpali fetch (albo gdy fetch padnie), `resultCount = !isLoading ? ... : null` dawało **0** (nie null) → `isThinPage=true` → `noindex` nadpisywał prerenderowy `index`. Google honoruje DOM po renderze = `noindex`. Komentarz w kodzie twierdził, że `!isLoading` chroni — chronił tylko *w trakcie* ładowania, nie w stanie **początkowym**/po błędzie.

**Czemu prerender.io to maskował:** runtime prerender.io serwował botowi **finalny, ustabilizowany HTML** (po załadowaniu ofert → `index`); Google nie hydratował pustego store. Bug wszedł **12 maja** (`b19affa`, dodanie `isThinPage`), ale spał pod prerender.io do jego wygaśnięcia **18 maja**; **ujawnił się** po wdrożeniu prerenderu build-time **16 czerwca** (`a3d1f22`), bo build-time serwuje normalne SPA, które Google sam odpala.

**Fix (kod):**

| # | Zmiana | Plik | Status |
|---|---|---|---|
| 1 | Flaga `hasLoaded` (true tylko po UDANYM fetchu) | `stores/useSearchStore.ts` | ✅ kod |
| 2 | `resultCount`/`noindex` wydzielone do czystych, testowalnych funkcji (`computeListingResultCount` bramkuje na `hasLoaded`; `shouldNoindexListing`) | `utils/listingsSeo.ts` (nowy) + `views/ListingsPage.vue` | ✅ kod |
| 3 | Test regresyjny (12 case'ów: stan początkowy/po błędzie → `null` → `index`; realnie thin → `noindex`) | `tests/unit/listingsSeo.test.ts` (nowy) | ✅ 143/143 zielone |

Efekt: dopóki nie ma **potwierdzonego** wyniku z API → `resultCount=null` → strona zostaje `index` (zgodnie z prerenderem). `noindex` wchodzi tylko gdy API realnie zwróci <3 oferty (zamierzony thin-content).

**Wdrożone + zweryfikowane (puppeteer, live prod 2026-06-26):** Poznań i `/powierzchnie-reklamowe` = ZAWSZE index (zero migotania); Warszawa/Kraków = noindex (poprawnie, thin). 143 testy zielone, `vue-tsc` czysto, deploy OK. **NIE** prosić o indeksację Warszawy/Kraków (są słusznie thin) — request indexing tylko dla zdrowych miast (Poznań itp.) i strony głównej. Knob: `THIN_PAGE_THRESHOLD=3` w `utils/listingsSeo.ts` — obniżenie wpuści cieńsze strony do indeksu (niezalecane, thin-content).

**Drobiazg (osobny, niski prio):** prerender thin-miast (warszawa/krakow) wypieka `index`+pusto zamiast `noindex` (łapie stan przed dokończeniem fetcha → resultCount=null → mój gate → index). Google renderuje JS i dostaje poprawny `noindex`, więc nieszkodliwe, ale statyczny plik niespójny z live. Docelowo: prerender powinien czekać na fetch listingów miasta (jak robi dla Poznania).

**Wątki poboczne (ten sam zrzut GSC):**
- **Strona główna „Zindeksowana, ale zablokowana robots":** stale cache robots w Google (live plik OK, `/` Allow, 10/10 strzałów 200, identyczny dla każdego UA). Łagodne, goi się po odświeżeniu cache. = powtórka pozycji z audytu 2026-06-15.
- **Prośba o usunięcie `api.reklamap.pl` (GSC Usunięcia):** zbędna — `site:api.reklamap.pl` = 0 stron (api nie jest w indeksie), `Disallow:/` już blokuje. Zweryfikować prefiks; anulować jeśli dotyka `reklamap.pl` bez `api.`.

---

## 2026-06-19 — GSC „Żądanie indeksowania odrzucone" na `billboardy/dabrowa-gornicza` = FAŁSZYWY ALARM

User dostał w GSC (URL Inspection → Poproś o zindeksowanie) komunikat: „Żądanie indeksowania zostało odrzucone. Podczas testowania bieżącej wersji wykryto błędy indeksowania tego adresu URL" dla `https://reklamap.pl/powierzchnie-reklamowe/billboardy/dabrowa-gornicza`.

**Żywa diagnoza (curl jako Googlebot, prod):**

| Sprawdzenie | Wynik |
|---|---|
| HTTP status | **200**, bez przekierowań, TTFB 0,1 s / total 0,18 s |
| `<meta robots>` / `X-Robots-Tag` | `index, follow` / brak noindex w nagłówku |
| Canonical | self-referencing, poprawny |
| robots.txt | brak `Disallow` pasującego do ścieżki (Allow) |
| Render dla bota | **PEŁNY** — `<h1>Billboardy – Dąbrowa Górnicza`, breadcrumbs, 57 realnych ogłoszeń z opisami/cenami, silosy „inne miasta"/„inne typy", 3× JSON-LD |
| Sitemapa | URL **JEST** w `api.reklamap.pl/sitemap.xml` (obok 49 innych miast billboardowych) |

**Werdykt:** strona jest w 100% indeksowalna. Komunikat to znana, **przejściowa usterka po stronie GSC** (on-demand live-test renderer bywa przeciążony / ma chwilowy hiccup) — pojawia się masowo dla zdrowych stron. NIE jest to błąd kodu ani regresja. Akcja: NIE spamować „Poproś o zindeksowanie", polegać na sitemapie + normalnym crawlu, ewentualnie ponowić za kilka dni; weryfikować przez raport Strony/`site:`, nie przez on-demand test.

**Drobiazg (kosmetyka, opcjonalnie):** `robots.txt` deklaruje `Sitemap: https://reklamap.pl/sitemap.xml`, który robi **301 → `api.reklamap.pl/sitemap.xml`**. Google podąża za 301 na sitemapie bez problemu, ale best-practice to deklarować docelowy URL wprost. Niski priorytet, nie wiąże się z tym błędem.

---

## 2026-06-15 — diagnoza nowego zrzutu GSC (skok niezaindeksowanych = import Optokom)

Źródło: 3 zrzuty GSC od usera (15.06) + **żywe odpytanie prod API** (listingi 241/215/172, `total=296`), `optokom.json` (192 rekordy), `git show 4c368b1`, live `robots.txt` (reklamap.pl, api, www.api), live 301 www→non-www.

### Rekonsyliacja bucketów (09.06 → 15.06)

| Bucket GSC | 09.06 | 15.06 | Δ | Odczyt |
|---|---|---|---|---|
| **Wykryta – niezindeksowana** | 80 | **289** | **+209** | 🔴 import Optokom (192 nośniki, baza 104→296). Leaf bez progu → Google odkrył wszystkie naraz. NIE regresja kodu — backlog crawl na młodej domenie |
| Zeskanowana – niezindeksowana | 12 | 27 | +15 | część Optokom już scrawlowana → thin/near-dup → odrzucona z indeksu |
| noindex (Niepowodzenie) | 10 | 10 | 0 | celowe cienkie <3; walidacja zawsze „Niepowodzenie" — OK |
| 5xx / 404 / Przekierowanie (Rozpoczęto) | 30/28/35 | 30/28/35 | 0 | goją się same, bez akcji |
| robots-blocked / canonical-alt / 401 | 4/4/1 | 4/4/1 | 0 | zamierzone/benign |
| **[NOWY, „Popraw wygląd"] Zindeksowana ale zablokowana robots** | — | 2 | +2 | `/` + `/powierzchnie-reklamowe/warszawa` — STRONY SĄ W INDEKSIE; flaga kosmetyczna |

### Ustalenia (żywy kod/prod)

- **Optokom = 192 nośniki, ~65% katalogu.** Wszystkie billboardy 5,04×2,38 m (12 m²), dzielą `advertisements/optokom-placeholder.jpg`, opis **templatowany** (różni się adres/miasto/cena), część `status=reserved` ale `is_active=true` → **w sitemapie** (potwierdzone: leaf 215 reserved był crawlowany). Klasyczny profil thin/near-duplicate → dokładnie to, na co Google odpowiada „crawled, not indexed". To powtórka problemu z maja (doorway/thin), tym razem na poziomie leaf.
- **Bug sluga:** `AdvertisementController:428` `slug=Str::slug($ad->title)`. Title „Billboard 5.04×2.38 m – Jaworzno" → `Str::slug` zjada „×" i „." → `billboard-504238-m-jaworzno`. Wszystkie 12 m² dzielą stem „504238" → URL brzydki + wygląda na duplikat.
- **„Zindeksowana ale zablokowana robots" (2):** live `reklamap.pl/robots.txt` ma `Allow: /` i ZERO Disallow pasującego do `/` ani `/powierzchnie-reklamowe/warszawa`. Flaga jest STALE/transient — najpewniej chwilowy nieudany fetch robots.txt w oknie 5xx (pierwsze wykrycie 9.06 zbiega się z trwającą walidacją 5xx; Google przy nieosiągalnym robots defensywnie blokuje ostatnio crawlowane URL). Klasa „Popraw wygląd" = strony SĄ w indeksie. Akcja: jeden klik „Sprawdź poprawność".
- **`www.api.reklamap.pl/`** (był w zrzucie zeskanowana): `www.api.reklamap.pl/robots.txt` → `Disallow: /` — już pokryte, wypadnie samo. Host www na subdomenie api to kosmetyka (opcjonalny 301 www.api→api).
- www→non-www 301 działa (curl: `billboardy/poznan` → 301 na non-www). Bez zmian.

### Rekomendacje (priorytety)

| # | Akcja | Plik / miejsce | Kto | Prio | Status |
|---|---|---|---|---|---|
| 1 | **Realne zdjęcia zamiast `optokom-placeholder.jpg`** — najsilniejszy lewar (unikalny obraz = unikalna strona, odblokowuje indeksację leaf) | dane/operacyjne (Optokom dośle foty) | user/Marketer | 🔴 | TODO |
| 2 | **Wykluczyć z sitemapy nośniki nierentowalne** (`reserved`/`draft`) — rentować się nie da, nie reklamuj w Google; zostaw `active`+`soon_available`. Zmniejsza crawl surface | `backend/routes/web.php` (leaf-query — `whereIn('status',['active','soon_available'])` obok `is_active`) | dev | 🟡 | ✅ WDROŻONE W KODZIE 2026-06-15 — smoke prod-like: 181 wynajmowalnych / 643 is_active (462 `reserved` znika z leaf-sitemapy). Agregaty miast/typ×miasto BEZ zmian (próg liczy `is_active`, zgodnie z frontowym resultCount). Czeka na deploy backendu |
| 3 | **Fix sluga wymiarów** — normalizacja wymiarów PRZED slugify. Single source of truth: helper `Advertisement::slugifyTitle()` (PHP) + lustrzany `slugify()` (TS), używany w kontrolerze/sederze/sitemapie i wszystkich 12+ miejscach budujących URL we froncie | `Advertisement.php` (helper), `AdvertisementController.php`, `OptokomSeeder.php`, `web.php`, `frontend/src/utils/slugify.ts` + migracja backfill | dev | 🟡 | ✅ WDROŻONE W KODZIE 2026-06-15 — `billboard-504238-m-jaworzno` → `billboard-5-04-x-2-38-m-jaworzno`. PHP==TS potwierdzone testem (11/11 backend + 131/131 front, `vue-tsc` czysto). Resolucja po `{id}` → stare URL-e nadal 200, canonical wskazuje nowy slug (Google konsoliduje; leafy w większości jeszcze niezaindeksowane). Migracja `2026_06_15_000000_backfill_advertisement_slugs` przelicza istniejące (tylko zmienione, bez dotykania `updated_at`). Czeka na deploy + `php artisan migrate` |
| 4 | Lekkie zróżnicowanie opisu importu (zdanie o otoczeniu/widoczności per nośnik) + stagger przy kolejnych importach | `scripts/import_optokom.py` / `OptokomSeeder` | dev/Pisarz | ⚪ | TODO |
| 5 | GSC: **NIE re-walidować** noindex(10)/zeskanowana(27) — „Niepowodzenie" jest oczekiwane dla thin/near-dup, walidacja nie przejdzie bez poprawy treści (#1). „Zindeksowana-ale-robots"(2) — jeden klik walidacji | GSC | user | ⚪ | — |

**Werdykt:** skok 80→289 to NIE awaria — to fallout legalnego importu podaży (founder buduje gęstość nośników). 289 spadnie samo w miarę crawlu, **o ile** poprawi się jakość leaf (zdjęcia #1) i odetnie nierentowalne z sitemapy (#2). Strona główna jest zaindeksowana — „zablokowana robots" to kosmetyka.

---

## 2026-06-09 — audyt wdrożeniowy briefu SEO_RESEARCH

> **Weryfikacja (Claude, 2026-06-09):** finding A1 potwierdzony w kodzie i seederze — `BlogPostPage.vue:104` duplikował `import {computed}` (już w l.2), a l.147 używała niezadeklarowanego `base` (jedyne wystąpienie w pliku; nie eksportowane z `utils/url.ts`); `BlogPostsSeeder.php:63-68` ustawia `image_alt`/`image_prompt`, ale NIE `image`. **Sprostowanie streszczenia:** to NIE „blog się nie buduje" — build przechodzi (esbuild toleruje duplikat), crash jest RUNTIME'owy i tylko dla artykułów z pustym `image` (każdy świeżo seedowany). Istniejące opublikowane artykuły renderują się, bo mają ustawiony `image` (operator `||` nie sięga `base`). **A1 NAPRAWIONE w tej sesji** (`base`→`appUrl`, usunięty duplikat importu w BlogPostPage.vue) — czeka na `npm run build` + deploy `dist/`. Pozostałe findingi (A2/C/D) ugruntowane w plik:linia, nieprzeklikane linia-po-linii.

### ✅ Stan wdrożenia (Claude, 2026-06-09 — część kodowa)

Zaimplementowane i zweryfikowane w tej sesji (`vue-tsc --noEmit` czysto, **127/127 testów** zielone, `php -l og-meta.php` czysto):

| # | Co zrobione | Pliki |
|---|---|---|
| **A1** | `base`→`appUrl` + usunięty duplikat `import {computed}` | `BlogPostPage.vue` |
| **A2** | Gałąź bloga w og-shim: `fetchBlogPost` + `injectBlogMeta` (og:type=article, fetch `/api/blog/{slug}`, fallback og-image dla artykułów bez hero) | `public/og-meta.php` |
| **C1** | Statyczny `@graph` (Organization+WebSite z `@id`, `?q=`) w `<head>`; usunięta imperatywna injekcja; usunięte Org/WebSite z useSeo home | `index.html`, `App.vue`, `HomePage.vue` |
| **C2** | BreadcrumbList tylko ze współdzielonego `<Breadcrumbs>` (usunięty duplikat z ogłoszenia); `publisher`/`brand`/`offeredBy` jako `{@id}`; usunięty import `logo.webp` | `AdDetailPage.vue`, `BlogPostPage.vue` |
| **D1** | „Czytaj więcej" jako `<router-link>` → crawlowalny `<a href>` (graf hub→artykuł bez JS) | `BlogPage.vue` |
| **D2** | `onContentClick` — linki wewnętrzne w `v-html` jako SPA-nav (href zostaje dla bota) | `BlogPostPage.vue` |
| **B2** | Dodane opisy `rzeszow` i `torun` do `cityDescriptions` | `categoryDescriptions.ts` |
| **E2** | ❌ **COFNIĘTE po weryfikacji treści** — artykuły mają już CTA wpisane ręcznie w treści (16/25 ma supply-CTA, reszta demand, często geo-specyficzne, np. „Wystaw swój nośnik w Łodzi"). Dołączany komponent dublowałby/konfliktował (demand box pod artykułem kończącym się supply-CTA). CTA pozostaje robotą Pisarza per artykuł. Usunięto `ArticleCta.vue` + `config/supplyArticles.ts` | — |
| **D3** | „Powiązane artykuły" — 3 posty z tej samej kategorii (bez bieżącego), reużyty endpoint `/api/blog?category=`, crawlowalne `<router-link>` na dole artykułu (blog→blog, silos) | `BlogPostPage.vue` |

**⚠️ KOREKTA rekomendacji audytu (C2/D1):** audyt sugerował usunięcie JSON-LD z `Breadcrumbs.vue` — to zregresowałoby ListingsPage (jedyne źródło BreadcrumbList tam, bo komponent jest współdzielony). Zamiast tego usunięto duplikat z `AdDetailPage`. D1: zamiast przerabiać całą kartę na `<a>` (ryzyko layoutu — `.blog-card` to `<article>`), crawlowalnym `<a href>` uczyniono „Czytaj więcej" i zachowano `@click` karty.

**⏳ Czeka na DEPLOY (user/dev):** `npm run build` + wgranie `dist/` i `og-meta.php` na `reklamap.pl`; potem walidacja FB Sharing Debugger / LinkedIn Post Inspector na URL artykułu.

**⛔ Zależne od usera:** `sameAs` w `@graph` (najpierw założyć profile social); publikacja draft→published w panelu PRZED rozsyłką linków.

**⬜ Pozostałe NIEzrobione (świadomie — decyzje/proces):** linkowanie kategoria→blog (drugi kierunek silosu — `relatedArticle` w `categoryDescriptions` → wymaga zmiany interfejsu + render w `ListingsPage`); supply-CTA w treści 8 artykułów, które go nie mają (`jak-wybrac-powierzchnie`, `ile-kosztuje-reklama-outdoor`, `reklama-w-transporcie-publicznym`, `reklama-outdoor-warszawa`/`krakow`/`wroclaw`, `telebim-ekran-led-reklama`, `baner-reklamowy-cena`) — robota Pisarza w markdownie; F2 kategoria `dla-wlascicieli` (decyzja Biznesowy — czysty silos podażowy); D4 `INTENT_MAP_PRAWO.md` (Strateg); F1 `Disallow` filtrów (po danych GSC).

Źródło: brief `reklamap-os/status/SEO_RESEARCH_2026-06-09.md` (5 pytań technicznych) + **żywy re-read kodu** (`BlogPostPage.vue`, `og-meta.php`, `App.vue`, `HomePage.vue`, `router.ts`, `BlogPage.vue`, `backend/routes/web.php`, `BlogPostsSeeder.php`, `categoryDescriptions.ts`, `ListingsPage.vue`, `AdDetailPage.vue`, `Breadcrumbs.vue`). Bazuje na stanie z audytu 2026-06-09 (og-shim WDROŻONY W KODZIE dla ogłoszeń, sitemap z progiem ≥3, recovery 149→80) — rzeczy już rozwiązane NIE diagnozuję od zera.

**Werdykt:** architektura jest gotowa w ~80%. Fundament bloga (canonical per-URL, BlogPosting/Breadcrumb/FAQ schema, enum kategorii pod miasta i prawo, data-driven sitemap) STOI. Ale **dwa twarde blokery zerują ROI dystrybucji** (priorytet 1) + encja marki wymaga konsolidacji (priorytet 2). Reszta to plumbing i procesy.

---

### A) Co BLOKUJE rozsyłkę treści podażowej (og: dla artykułów)

#### A1. 🔴 BLOCKER — blog się NIE buduje/wysypuje: duplikat importu `computed` + niezadeklarowane `${base}`

| Pole | Treść |
|---|---|
| **Analiza** | `frontend/src/views/BlogPostPage.vue:2` importuje `import { ref, computed, onMounted, watch } from 'vue'`, a **l.104 PONOWNIE** `import { computed } from 'vue'` (wstrzyknięty w środek `<script setup>`). Dodatkowo **l.147**: `ogImage: newPost.image \|\| ` + szablon `` `${base}/og-image.png` `` — zmienna `base` **nigdzie nie zadeklarowana** (import w l.6 to `appUrl`, nie `base`). esbuild toleruje duplikat importu, więc `npm run build` przechodzi (cichy dług). `computed` jest leniwy: artykuł Z obrazkiem omija gałąź `${base}` i działa; artykuł BEZ hero (`image=null`) rzuca `ReferenceError: base is not defined` przy obliczaniu `seoOptions`. **Seeder NIE ustawia pola `image`** (`BlogPostsSeeder.php:68` ustawia tylko status), więc każdy seedowany artykuł podażowy → `image=null` → crash. |
| **Ryzyko SEO** | WYSOKIE / blocker priorytetu #5 z briefu. Każdy artykuł bez hero (cały net-new klaster) → ReferenceError przy renderze → `useSeo` nie wstrzykuje meta/og/BlogPosting/Breadcrumb/FAQ JSON-LD, `prerenderReady` nie flipuje, ryzyko białej strony. Zero treści dla bota i usera. ROI całego klastra = 0. |
| **Rekomendacja** | `frontend/src/views/BlogPostPage.vue`: (1) usunąć l.104 `import { computed } from 'vue'`; (2) l.147 zmienić `` `${base}/og-image.png` `` → `` `${appUrl}/og-image.png` `` (`appUrl` już zaimportowane w l.6). Po fixie `npm run build` + redeploy `dist/`. |
| **Przewidywany zysk** | Odblokowanie renderu WSZYSTKICH artykułów (istniejących i podażowych); spójny fallback og:image (jak `AdDetailPage.vue` i `BlogPage.vue`). |
| **Stan** | do-zrobienia |

#### A2. 🔴 BLOCKER — og-shim pokrywa TYLKO ogłoszenia, blog dostaje generyczny default

| Pole | Treść |
|---|---|
| **Analiza** | `frontend/public/og-meta.php:64` — JEDYNY regex to `#^powierzchnia-reklamowa/[^/]+/[^/]+/.+-(\d+)$#` (pasuje wyłącznie do leaf-ogłoszeń); fetch z `/api/listings/{id}` (l.85). Brak gałęzi dla `^blog/...` → dla artykułu `$served='fallback'` (l.59) i oddawany jest statyczny default og (`og:type=website`, `og:image=/og-image.png` z `index.html`). `.htaccess:69-72` routuje scrapery social (facebookexternalhit/linkedinbot/whatsapp/slackbot/discordbot/telegrambot…) do shima dla KAŻDEJ ścieżki — więc `/blog/{kat}/{slug}` **trafia** do shima, ale shim go nie obsługuje i zwraca generyk. `useSeo.ts` ustawia per-post og klient-side (po JS), czego scraper NIE wykona. |
| **Ryzyko SEO** | WYSOKIE / blokuje ROI dystrybucji. Brief Next action #1 wprost: og-shim PRZED jakąkolwiek dystrybucją w social. Klaster podażowy ma być rozsyłany prospektom po cold callu (LinkedIn DM/WhatsApp/Slack/Messenger). Bez per-artykuł og: każdy link wyświetli identyczny karton „Wynajem powierzchni… \| ReklaMap" bez tytułu/excerptu/hero → zerowy CTR z DM, zmarnowana dystrybucja. **NIE jest pokryte** mimo że audyt mówi „shim wdrożony" — wdrożony TYLKO dla ogłoszeń. |
| **Rekomendacja** | `frontend/public/og-meta.php` — dodać gałąź bloga **przed** `logLine`/`echo` (po l.72). Slug bez numerycznego ID, fetch po slugu. Snippet:<br>```php<br>// Strona artykułu bloga: blog/{kategoria?}/{slug}<br>elseif (preg_match('#^blog/(?:[a-z0-9-]+/)?([a-z0-9-]+)$#', $path, $mb)) {<br>    $post = fetchBlogPost($mb[1]);   // GET API_BASE.'/blog/'.$slug, X-App-Key, timeout 5s<br>    if ($post !== null) { $indexHtml = injectBlogMeta($indexHtml, $post, $path); $served = 'og-blog'; }<br>}<br>```<br>`injectBlogMeta` lustrzane do ogłoszeń: `og:title = $post['title'].' \| Blog ReklaMap'`, `og:description = $post['excerpt']`, `og:image = $post['image'] ?? OG_FALLBACK_IMAGE`, **`og:type = 'article'`** (spójnie z `BlogPostPage.vue:146`), `og:url = APP_URL.'/'.$path`, `twitter:card = summary_large_image`. `fetchBlogPost` analogiczna do `fetchAd` (`BlogController::show` zwraca title/excerpt/image/category — image jako absolutny URL przez `url('storage/...')`). Zaktualizować komentarz nagłówkowy (l.8-9 mówi tylko „dane ogłoszenia"). |
| **Przewidywany zysk** | Odblokowuje całą dystrybucję klastra: poprawny social preview (tytuł+excerpt+hero) → realny CTR z cold calli. Domyka pytania #1 i #5 z briefu. |
| **Stan** | do-zrobienia |

#### A3. ⚪ Spójność og:type — po A2 domknięte; NIE rozszerzać shima na home/listy

| Pole | Treść |
|---|---|
| **Analiza** | `og-meta.php:156` dla ogłoszenia ustawia `og:type=product` (spójne z `AdDetailPage.vue`) — OK. Strony `/blog` (lista), `/blog/{kat}` (kategoria), home, kategorie ogłoszeń NIE są wzbogacane (brak gałęzi) i dostają default `og:type=website` z `index.html`. Dla artykułów gałąź z A2 ustawi `og:type=article`. |
| **Ryzyko SEO** | NISKIE. Strony indeksowe (listy/kategorie) na default `website` są poprawne — to indeksy, nie pojedyncze treści do udostępniania. |
| **Rekomendacja** | Gałąź A2 załatwia `og:type=article`. **NIE** rozszerzać shima na home/listy — default `website` jest dla nich poprawny (zgodnie z audytem 2026-06-09). Zero pracy poza A2. |
| **Przewidywany zysk** | Spójność `og:type` article między shimem a `useSeo` dla artykułów; brak nadmiarowego wzbogacania. |
| **Stan** | do-zrobienia (w ramach A2) |

---

### B) Architektura URL / sitemap dla nowych miast (pytanie #2)

#### B1. ✅ Architektura URL bloga udźwignie net-new miasta i silos prawny BEZ zmian kodu

| Pole | Treść |
|---|---|
| **Analiza** | `frontend/src/router.ts:101,106` definiuje `/blog/:category(poradniki\|trendy\|case-study\|rynek-ooh\|prawo-i-regulacje\|lokalizacje)/:slug`. Miasta (Szczecin/Białystok/Rzeszów/Toruń/Gdynia) → kategoria `lokalizacje`; silos prawny → `prawo-i-regulacje`/`poradniki` — **wszystkie już w enumie**. `router.ts:111-130` obsługuje legacy `/blog/{slug}` przez lookup API + redirect. `backend/routes/web.php:175,181` wrzuca KAŻDY post `status=published` do sitemapy z URL `/blog/{category}/{slug}` (bez progu); l.60-67 dorzuca stronę kategorii. `BlogPost::booted` czyści cache sitemapy przy save/delete. |
| **Ryzyko SEO** | NISKIE. Jedyny haczyk: kategoria spoza enuma → router rzuca `not-found`. Strateg/Pisarz NIE mogą wymyślić nowej kategorii (np. `podaz`) bez dopisania w 4 miejscach (patrz F2). |
| **Rekomendacja** | BEZ zmian kodu dla planowanych klastrów. Procedura Pisarza: `category` w front-matter ∈ {poradniki, trendy, case-study, rynek-ooh, prawo-i-regulacje, lokalizacje}. |
| **Przewidywany zysk** | Zero długu inżynierskiego — odblokowanie Pisarza bez czekania na deploy. |
| **Stan** | juz-jest |

#### B2. ⚪ Strony transakcyjne miast tier-2 gated progiem ≥3 — ruch łapać artykułem, nie kategorią

| Pole | Treść |
|---|---|
| **Analiza** | DWA różne URL na miasto, których nie wolno mylić: **(A)** ARTYKUŁ `/blog/lokalizacje/{slug}` — wchodzi do sitemapy bez progu po publikacji. **(B)** STRONA KATEGORII `/powierzchnie-reklamowe/{city}` — `backend/routes/web.php:103,128` dodaje do sitemapy TYLKO przy `havingRaw('COUNT(*) >= ?')` (próg = `THIN_PAGE_THRESHOLD`=3); równolegle `ListingsPage.vue` ustawia noindex gdy `resultCount<3`. Miasta tier-2 w fazie budowania podaży = 0 ofert → strona (B) jest noindex I poza sitemapą. Ręczny tekst w `categoryDescriptions.ts` istnieje dla szczecin/bialystok/gdynia, BRAK dla **rzeszow i torun**. Próg ignoruje obecność opisu (celowo, anti-doorway). |
| **Ryzyko SEO** | To poprawne zachowanie, NIE bug (doorway/thin był przyczyną 149 „wykryta-niezindeksowana"). Ryzyko interpretacyjne: artykuł o mieście zafunkcjonuje od razu, ale strona transakcyjna NIE zindeksuje się bez ≥3 nośników. Liczenie na ruch transakcyjny z tier-2 przed pozyskaniem podaży = błąd planu. Matryca format×miasto (`router.ts` /{type}/{city}, `web.php:116-140`) — to samo: URL istnieją strukturalnie, ale gated; `typeCityDescriptions` ma tekst tylko dla ~10 dużych miast. |
| **Rekomendacja** | Architektonicznie BEZ ZMIAN (próg słuszny). Dane: (1) dopisać `rzeszow` i `torun` do `cityDescriptions` w `frontend/src/data/categoryDescriptions.ts` (wzorem szczecin/bialystok) — gotowy unikalny tekst w chwili przekroczenia progu. (2) Komunikat dla Stratega/Biznesowego: tier-2 i frazy `billboard {miasto}` przechwytywać sekcją H2 w artykule `/blog/lokalizacje/{miasto}`, NIE stroną kategorii. W artykule linkować do `/powierzchnie-reklamowe/{miasto}` (link zadziała po przekroczeniu progu). |
| **Przewidywany zysk** | Brak rozjazdu oczekiwań; zero opóźnienia indeksacji po osiągnięciu podaży; realistyczne planowanie treści. |
| **Stan** | czesciowo |

#### B3. ⚪ Pułapka `status=draft` — opublikowany ≠ odnajdywalny

| Pole | Treść |
|---|---|
| **Analiza** | `backend/routes/web.php:175` filtruje `where('status','published')`; `BlogPostsSeeder.php:68` ustawia `'status' => 'draft'` (świadoma decyzja — publikacja ręczna przez panel). `BlogController::show` wymaga `published` → bezpośredni link do draftu zwróci 404, scraper/bot dostanie not-found. |
| **Ryzyko SEO** | Średnie operacyjnie: artykuł rozesłany prospektowi w DM, pozostając draftem → martwy link (negatywny pierwszy kontakt + zmarnowany sygnał social). |
| **Rekomendacja** | Procedura (nie kod): po `php artisan db:seed --class=BlogPostsSeeder` — OBOWIĄZKOWO publikacja przez panel admina PRZED rozesłaniem. Weryfikacja: `curl -s https://api.reklamap.pl/sitemap.xml \| grep '{slug}'`. Cache sitemapy czyści się sam (`BlogPost.php`). Opcjonalnie: w panelu admina wizualny status „w sitemapie / poza". |
| **Przewidywany zysk** | Eliminacja ryzyka rozesłania linku 404 w cold callach. |
| **Stan** | juz-jest |

---

### C) Schema.org / encja marki (pytanie #4)

#### C1. 🟡 Organization/WebSite duplikowana (App.vue + HomePage), `sameAs` puste, tylko klient-side

| Pole | Treść |
|---|---|
| **Analiza** | Schema Organization+WebSite wstrzykiwana DWUKROTNIE: **(a)** `frontend/src/App.vue:62-103` — imperatywnie w `onMounted` na KAŻDEJ stronie (`document.createElement` + `appendChild`, **bez cleanup**), `logo='https://reklamap.pl/logo.png'`, **`sameAs: []`** (l.76); **(b)** `frontend/src/views/HomePage.vue:298-326` przez `useSeo` — kolejny komplet WebSite+Organization. Na home Google widzi **2× Organization i 2× WebSite**, z rozjeżdżającymi się polami (App.vue ma description+sameAs+email, HomePage nie) i dwoma URL logo (bezwzględny vs `${appUrl}`). **Brak `@id`** w obu — nie da się skonsolidować w graf. Cała encja powstaje dopiero po JS (index.html w surowym HTML NIE ma żadnego `ld+json`). `logo.png` istnieje (`public/logo.png`, 109 KB). |
| **Ryzyko SEO** | ŚREDNIE-WYSOKIE. Brief sekcja 1: marka `reklamap` kolizyjna (reklama.pl, reklamapl.pl, turecki reklamap.com) + brak Knowledge Graph. Zduplikowana, sprzeczna encja rozmywa sygnał tożsamości dokładnie tam, gdzie marka jest kolizyjna — Google może wybrać losowo jeden wariant lub zignorować oba. `sameAs:[]` = zero profili = zero szans na disambiguację i Knowledge Panel. Encja zależna od renderu JS jest najmiększym sygnałem tam, gdzie powinien być najtwardszy. |
| **Rekomendacja** | **(1)** Usunąć CAŁKOWICIE imperatywną injekcję z `frontend/src/App.vue:62-103` (cały blok org/website + appendChild). **(2)** Przenieść JEDEN połączony graf do **statycznego** `<script type="application/ld+json">` w `frontend/index.html` (`<head>`, przy reszcie og):<br>```json<br>{"@context":"https://schema.org","@graph":[<br> {"@type":"Organization","@id":"https://reklamap.pl/#organization","name":"ReklaMap","url":"https://reklamap.pl/","logo":{"@type":"ImageObject","url":"https://reklamap.pl/logo.png","width":512,"height":512},"foundingDate":"2026-04-01","areaServed":"PL","contactPoint":{"@type":"ContactPoint","email":"kontakt@reklamap.pl","contactType":"customer service","availableLanguage":"Polish"},"sameAs":["<FACEBOOK>","<LINKEDIN>","<INSTAGRAM>"]},<br> {"@type":"WebSite","@id":"https://reklamap.pl/#website","name":"ReklaMap","url":"https://reklamap.pl/","publisher":{"@id":"https://reklamap.pl/#organization"},"inLanguage":"pl-PL","potentialAction":{"@type":"SearchAction","target":{"@type":"EntryPoint","urlTemplate":"https://reklamap.pl/powierzchnie-reklamowe?q={search_term_string}"},"query-input":"required name=search_term_string"}}<br>]}<br>```<br>**(3)** Usunąć bloki Organization+WebSite z `HomePage.vue:298-326` (zostawić ew. WebPage/CollectionPage). **(4)** `sameAs` wypełnić TYLKO realnymi profilami — pusta/fikcyjna tablica gorsza niż brak; jeśli profili brak, to **zadanie usera/Marketera** (założyć profile) PRZED wpisaniem. |
| **Przewidywany zysk** | Jeden, render-niezależny, niezdublowany sygnał encji z `@id` → punkt zaczepienia pod Knowledge Graph i disambiguację od marek-kolizji; `@id` pozwala spiąć publisher na blogu i Product.brand bez powielania. |
| **Stan** | czesciowo |

#### C2. 🟡 Publisher logo na blogu + Product.brand jako trzecia kopia encji; podwójny BreadcrumbList na ogłoszeniu

| Pole | Treść |
|---|---|
| **Analiza** | `BlogPostPage.vue:7,134` używa `logoImage` z importu `../assets/logo.webp` jako `publisher.logo.url` → po buildzie zhashowany, zmienny URL `/assets/logo-[hash].webp`. `AdDetailPage.vue` ma `Product.brand` jako odrębny Organization inline (bez `@id`) — trzeci wariant encji. Na ogłoszeniu **BreadcrumbList renderowany 2×**: raz w `AdDetailPage` structuredData, raz przez komponent `Breadcrumbs.vue` (sam wstrzykuje JSON-LD). Reszta schematów poprawna: Product+Offer+UnitPriceSpecification+Place, ItemList na listingu, BlogPosting+Breadcrumb(3 poziomy)+FAQ na artykule. |
| **Ryzyko SEO** | Zmienny URL logo łamie ciągłość encji wydawcy między buildami. Podwójny BreadcrumbList → ostrzeżenie w Rich Results Test. `Product.brand` inline = kolejne rozmycie encji marki. |
| **Rekomendacja** | Po wdrożeniu `@id` (C1): `BlogPostPage.vue:131-135` zmienić `publisher` na `{ '@id': `${appUrl}/#organization` }` i usunąć import `logoImage` (l.7); `AdDetailPage.vue` `brand`/`offeredBy` → `{ '@id': appUrl+'/#organization' }`. BreadcrumbList: usunąć wstrzykiwanie JSON-LD z `frontend/src/components/Breadcrumbs.vue` (zostawić wizualny markup), zostawić blok w `AdDetailPage` (pełne 5 poziomów z item URL). Breadcrumb bloga: w `buildBreadcrumbSchema` (`BlogPostPage.vue:91-101`) dodać 4. poziom kategorii między Blog a artykuł (`/blog/{category}`) — wzmacnia sygnał silosu. |
| **Przewidywany zysk** | Stabilne logo wydawcy, jedna spójna encja referencjonowana z Product/blog, czyste breadcrumbs (mniej ostrzeżeń GSC). |
| **Stan** | czesciowo |

---

### D) Linkowanie / anti-kanibalizacja

#### D1. 🟡 Kafelki bloga to nie `<a href>` — bot bez JS nie widzi linków hub→artykuł

| Pole | Treść |
|---|---|
| **Analiza** | `frontend/src/views/BlogPage.vue:247-252` — karta posta to `<article @click="router.push(...)" style="cursor:pointer">` z `<button class=read-more>` w środku (l.278). To NIE `<a href>`. Nawigacja kategorii owszem używa `<router-link :to=category.path>` (l.235-243). Przejście `/blog → artykuł` wymaga JS. Posty są w sitemapie (`web.php:181`), więc Googlebot je znajdzie — ale on-page graf linków `/blog→post` jest pusty dla nie-JS. |
| **Ryzyko SEO** | Średnie/wysokie dla klastra: (1) zerowy on-page link equity z huba do artykułów (Google polega tylko na sitemapie); (2) scrapery social i część botów AI nie odkryją artykułów; (3) niespójność a11y (button-in-clickable). |
| **Rekomendacja** | `frontend/src/views/BlogPage.vue:247-252` — owinąć kartę w `<router-link class="blog-card" :to="`/blog/${post.category}/${post.slug}`">`, usunąć `@click` i `<button class=read-more>` (zagnieżdżony button-in-link). Vue Router wyrenderuje `<a href="/blog/...">` czytelny dla bota bez JS. |
| **Przewidywany zysk** | Graf linków hub→artykuł czytelny dla każdego bota; lepsza dystrybucja link equity do klastra; poprawa a11y. |
| **Stan** | do-zrobienia |

#### D2. ⚪ Linki w treści `v-html` powodują full reload zamiast SPA-nav

| Pole | Treść |
|---|---|
| **Analiza** | `BlogPostPage.vue:251` renderuje treść przez `v-html`. Linki w treści (`<a href="/blog/...">` z konwersji markdown) to surowe anchory DOM, których Vue Router NIE przechwytuje → klik = pełny reload SPA (re-bootstrap, ponowne wstrzykiwanie meta przez useSeo, utrata scroll). |
| **Ryzyko SEO** | NISKIE dla crawla (href poprawny, indeksowalny — plus). Ryzyko UX/CWV: pełny reload przy każdym przejściu w klastrze → wolniej, gorszy INP, wyższy bounce, krótsze sesje (pośredni sygnał jakości). |
| **Rekomendacja** | `frontend/src/views/BlogPostPage.vue` — delegowany handler na `.post-content`:<br>```ts<br>const onContentClick = (e: MouseEvent) => {<br>  const a = (e.target as HTMLElement).closest('a'); if (!a) return<br>  const href = a.getAttribute('href') || ''<br>  if (href.startsWith('/') && !href.startsWith('//')) { e.preventDefault(); router.push(href) }<br>}<br>```<br>w template: `<article class="post-content" @click="onContentClick"><div v-html="post.content"></div></article>`. Zachowuje href dla bota + SPA-nav dla usera. |
| **Przewidywany zysk** | Płynna nawigacja w klastrze bez utraty crawl-friendly href. |
| **Stan** | do-zrobienia |

#### D3. 🟡 Brak komponentu „powiązane artykuły" + linkowanie silosu jednokierunkowe

| Pole | Treść |
|---|---|
| **Analiza** | `BlogPostPage.vue` NIE renderuje sekcji related/next/prev (szablon kończy się na `.share-section`). `RelatedSilos.vue` istnieje, ale odpytuje `/api/silos` i jest dla LISTINGÓW, nie bloga. Całe linkowanie wewnętrzne jest ręczne w markdownie. Dystrybucja nierówna (hub-and-spoke ciąży do 2 starych artykułów z 30-34 linkami; świeże tematy startują od zera). Dodatkowo silos jednokierunkowy: artykuły linkują DO `/powierzchnie-reklamowe` (48× w md), ale strony kategorii (najsilniejsze, 157 URL sitemapy) NIE linkują do bloga — `ListingsPage.vue` grep `/blog` = 0. |
| **Ryzyko SEO** | Średnie-wysokie. Klaster (kotwica + 5 satelitów) wymaga gęstego, spójnego meshu; ręczne linkowanie zależy od pamięci Pisarza → orphan-risk dla net-new. Najmocniejsze strony serwisu nie karmią bloga → niepełny silos lokalny i podażowy. |
| **Rekomendacja** | **(1)** Backend: endpoint zwracający 3 posty tej samej kategorii (exclude bieżący) w `BlogController` (`index` już wspiera filtr kategorii). **(2)** Frontend: w `BlogPostPage.vue` po `.share-section` (~l.285) sekcja `<section class="related-section">` z `<router-link>` do sąsiadów. **(3)** Dwukierunkowość: rozszerzyć `CategoryDescription` w `frontend/src/data/categoryDescriptions.ts` o opcjonalne `relatedArticle?: { slug, category, title }` i wyrenderować link pod opisem kategorii w `ListingsPage.vue` (np. `cityDescriptions['szczecin'].relatedArticle`). **(4)** Na `/dodaj-powierzchnie-reklamowa` link do kotwicy podażowej. |
| **Przewidywany zysk** | Spójny, powtarzalny przepływ PageRank w silosie; eliminacja orphan-risk; zamknięcie obiegu link juice (kategoria↔blog); dłuższy dwell time. |
| **Stan** | do-zrobienia |

#### D4. 🟡 Anti-kanibalizacja silosu prawnego — brak egzekwowanej mapy intencji per-URL

| Pole | Treść |
|---|---|
| **Analiza** | Canonical poprawny i self-ref: `BlogPostPage.vue:149` (`canonical: url`, url z kategorią i slugiem), `router.ts:111-130` wymusza jeden kanoniczny URL per artykuł → na poziomie URL artykuły się NIE skleją. ALE brak artefaktu mapy intencji. Frazy-cel przyszłego silosu (`12 m²`/`bez pozwolenia`/`zgłoszenie budowlane`) są JUŻ rozsiane po ~10 istniejących artykułach (jak-wybrac, baner-cena, billboard, reklama-zewnetrzna, oplata-reklamowa, uchwala-krajobrazowa, totem, olsztyn…). Canonical NIE rozwiązuje kanibalizacji między różnymi URL o różnej treści — chroni tylko przed duplikatami. |
| **Ryzyko SEO** | WYSOKIE (główne z briefu pkt #3/#4): (a) nowe dedykowane artykuły konkurują o `reklama bez pozwolenia`/`próg 12 m²`; (b) rozsiane wzmianki w starych artykułach rozcieńczają autorytet dedykowanego URL (Google może wyświetlić starszy, gorszy artykuł). |
| **Rekomendacja** | Utworzyć artefakt `reklamap-os/status/INTENT_MAP_PRAWO.md` PRZED pisaniem silosu: tabela `URL(slug) \| fraza-kotwica (1 per URL) \| frazy-poboczne \| frazy ZAKAZANE`. Proponowany podział bez nakładania: `zgloszenie-vs-pozwolenie-tablica` = procedura admin. + próg 12 m² (WŁAŚCICIEL fraz „12 m²"/„bez pozwolenia"); `reklama-na-ogrodzeniu` = odległości od drogi wg klasy (NIE powtarza progu — tylko linkuje); `dzierzawa-gruntu` = umowa + stawki; `podatek-ryczalt-8.5` = PIT-28; `elewacja-wspolnoty` = uchwała + zgoda udziałów. ENFORCEMENT: każdy nowy artykuł linkuje do „właściciela" frazy zamiast ją rozwijać; audyt 10 starych artykułów — zamienić rozsiane wzmianki na link do dedykowanego URL. |
| **Przewidywany zysk** | Eliminacja kanibalizacji (jeden URL = jeden cel); skonsolidowany autorytet per fraza. Bezpośrednia odpowiedź na pytanie #3. |
| **Stan** | do-zrobienia |

---

### E) Plumbing klastra podażowego + CWV (pytanie #5)

#### E1. ✅ Canonical + BlogPosting/Breadcrumb/FAQ schema — fundament STOI (klient-side)

| Pole | Treść |
|---|---|
| **Analiza** | `BlogPostPage.vue:112-136` BlogPosting (headline, articleSection=category, author, datePublished/Modified z API), `91-101` BreadcrumbList, `50-88` warunkowy FAQPage (parsuje H2 „Najczęściej zadawane pytania"). Canonical self-ref per slug (l.149). Wszystko przez `useSeo` PO renderze JS. Distinct slug → distinct canonical. |
| **Ryzyko SEO** | NISKIE-ŚREDNIE — schema istnieje tylko po JS; Googlebot renderuje (recovery odrobione), ale scrapery/walidatory bez JS nie. To granica SSR, nie luka linkowania. Po A2 (og dla bloga) scrapery dostaną przynajmniej og. |
| **Rekomendacja** | Brak zmian architektonicznych — DZIAŁA. Drobne wzmocnienie: 4. poziom kategorii w breadcrumb (patrz C2). |
| **Przewidywany zysk** | Potwierdzenie, że plumbing canonical/schema pod anti-kanibalizację i klaster STOI bez dodatkowej pracy poza A2. |
| **Stan** | juz-jest |

#### E2. 🟡 CTA do `/dodaj-powierzchnie-reklamowa` — działa przez linki w treści, brak strukturalnego bloku

| Pole | Treść |
|---|---|
| **Analiza** | Trasa istnieje (`router.ts:19`, redirect z `/dodaj-ogloszenie`). Artykuły linkują markdownem (21× `dodaj-powierzchnie`/`dodaj-ogloszenie`), klik obsłużony przez router (po fixie D2 — SPA). Komponent `OwnerCallout.vue` (CTA „Wystaw bezpłatnie" → `/dodaj-powierzchnie-reklamowa`) istnieje, ale NIE używany na artykule (grep w BlogPostPage = 0). |
| **Ryzyko SEO** | NISKIE. CTA działa przez linki inline, ale brak ustandaryzowanego, mocnego bloku na końcu artykułu — w klastrze podażowym to klucz konwersji właściciela. |
| **Rekomendacja** | Osadzić gotowy `OwnerCallout` na dole artykułu (`BlogPostPage.vue` po `.post-content`), warunkowo dla kategorii podażowej: `import OwnerCallout from '../components/OwnerCallout.vue'`; `<OwnerCallout v-if="post && (post.category === 'poradniki' || post.category === 'dla-wlascicieli')" />`. Minimalnie inwazyjne. |
| **Przewidywany zysk** | Powtarzalny CTA konwertujący ruch organiczny na akwizycję podaży (główna konwersja klastra). |
| **Stan** | czesciowo |

#### E3. ⚪ CWV artykułu — hero jako CSS background (LCP), brak `<img>` w treści (CLS OK)

| Pole | Treść |
|---|---|
| **Analiza** | Artykuły .md NIE mają obrazów w treści (grep `![`/`<img>` = 0) → CLS niskie (tekst/tabele stabilne). Hero wstrzykiwany jako CSS `background-image` inline (`BlogPostPage.vue:230`) w sekcji 60vh — nie da się preloadować ani `fetchpriority=high`, odkrywany późno (po JS→fetch→post.image→parse CSS). `WebPImage.vue` (poprawny lazy/eager/width/height/fetchpriority) NIE używany na artykule. Dopóki posty seedera nie mają `image` → hero to tylko gradient, LCP = `<h1>` (lekkie, OK). |
| **Ryzyko SEO** | CLS niskie. LCP: dla artykułów Z hero ryzyko >2,5 s na mobile (obraz późno odkryty, niski priorytet). |
| **Rekomendacja** | PRZED publikacją klastra zdecydować, czy artykuły dostają hero. Jeśli TAK — render jako `<img>`/`<picture>` przez `WebPImage.vue` (`eager`+`width`/`height`) zamiast CSS-tła, lub `<link rel=preload as=image>`. Jeśli NIE — gradient jest CWV-bezpieczny. |
| **Przewidywany zysk** | Stabilny LCP <2,5 s na mobile przy skalowaniu klastra. |
| **Stan** | czesciowo |

---

### F) Pozostałe (niskie priorytety / decyzje produktowe)

#### F1. ⚪ Crawl budget — filtry noindex egzekwowane klient-side

| Pole | Treść |
|---|---|
| **Analiza** | `ListingsPage.vue` ustawia noindex dla stron z parametrami filtra (~50 kluczy); `robots.txt` blokuje `/porownaj` i `/*?_v=`. Filtrowane URL nie są blokowane w robots.txt — bot pobierze je zanim zobaczy noindex (wstrzykiwany po JS). Leak teoretyczny (filtry to JS, nie linki — bot raczej ich nie odkryje). Paginacja: canonical strony >1 → ścieżka bez `?page` + prev/next rel. |
| **Ryzyko SEO** | NISKIE-ŚREDNIE, znany kompromis SPA bez SSR (zgodny z notatką soft-404). |
| **Rekomendacja** | Bez akcji prewencyjnej. Jeśli GSC pokaże filtrowane URL w „wykryta-niezindeksowana" → selektywny `Disallow: /*?q=` itd. w `robots.txt` (NIE blokować `?page=`). Decyzja PO danych (Analityk). |
| **Przewidywany zysk** | Potencjalna oszczędność crawl-budgetu — tylko jeśli dane potwierdzą leak. |
| **Stan** | czesciowo |

#### F2. ⚪ Brak dedykowanej kategorii podażowej (`dla-wlascicieli`) — decyzja produktowa

| Pole | Treść |
|---|---|
| **Analiza** | Enum kategorii zamknięty w 4 miejscach: migracje (`2026_01_25..` i `2026_04_14_..`), `router.ts:101,106`, `BlogPostPage.vue:158-165`, `BlogPage.vue` categories. Klaster podażowy zmieści się w `poradniki`/`prawo-i-regulacje`, ale bez dedykowanego huba treść podażowa rozpłynie się w popytowych poradnikach (słabszy topical clustering, mylące UX właściciel vs reklamodawca). |
| **Ryzyko SEO** | Jeśli `poradniki`: rozmycie intencji. Jeśli nowa kategoria BEZ aktualizacji wszystkich 4 miejsc: 404/redirect-not-found lub sitemap pokaże kategorię, której front nie wyrenderuje. |
| **Rekomendacja** | Decyzja produktowa (Biznesowy). Na 6 artykułów osobny hub `dla-wlascicieli` ma sens — wymaga 4 SPÓJNYCH zmian: (1) migracja `ALTER TABLE blog_posts MODIFY COLUMN category ENUM(...,'dla-wlascicieli')`; (2) `router.ts:101,106` dopisać `\|dla-wlascicieli`; (3) `BlogPostPage.vue:158-165`; (4) `BlogPage.vue` categories + meta. Alternatywa (zero kodu): podażowe w `poradniki`, prawno-podażowe w `prawo-i-regulacje`. |
| **Przewidywany zysk** | Czytelny silos podażowy = lepszy topical authority pod akwizycję + jaśniejsze UX dla właściciela nośnika. |
| **Stan** | do-zrobienia |

---

### Rekomendowana kolejność wdrożeń

1. **KROK 0 (A1, dev, ~15 min):** fix `BlogPostPage.vue:104` (duplikat import) + `:147` (`base`→`appUrl`). Blocker — bez tego cały blog się nie renderuje.
2. **KROK 1 (A2, dev, ~1h):** gałąź bloga w `og-meta.php`. Blocker dystrybucji.
3. **KROK 2 (deploy, dev, ~30 min):** `npm run build` + redeploy `dist/` (KROK 0+1) + deploy `og-meta.php`. **Weryfikacja:** FB Sharing Debugger / LinkedIn Post Inspector na URL artykułu.
4. **KROK 3 (C1+C2, dev, ~1h):** konsolidacja encji marki (statyczny `@graph` w index.html, usunięcie duplikatów App.vue/HomePage, `@id` w publisher/brand, jeden BreadcrumbList).
5. **KROK 4 (C1 sameAs, user/Marketer → dev):** założyć profile social → dev wpisuje URL do `sameAs`. Zależność.
6. **KROK 5 (D1+D2+E2+D3, dev, ~2h):** kafelki→router-link, handler v-html, OwnerCallout, related-posts.
7. **KROK 6 (B2+D3, dev/data, ~1h):** rzeszow/torun w categoryDescriptions, relatedArticle dwukierunkowy.
8. **KROK 7 (D4+B3+E2, user/Strateg-Pisarz, proces):** INTENT_MAP_PRAWO.md, standard redakcyjny CTA/linki, obowiązkowa publikacja draft→published przed rozsyłką.
9. **KROK 8 (F1+F2, po danych):** kategoria `dla-wlascicieli` (decyzja Biznesowy), Disallow filtrów (po GSC).

### Zadania dev vs user

| Zadanie | Dev (kod/deploy) | User (GSC/proces/off-site) |
|---|---|---|
| A1 fix build-blocker | ✅ | — |
| A2 og-shim blog + deploy | ✅ | walidacja w FB/LinkedIn debugger |
| C1 graf @graph w index.html | ✅ | — |
| C1 sameAs (URL profili) | wpisanie URL | **założenie profili social** |
| C2 publisher/brand @id, BreadcrumbList | ✅ | — |
| D1/D2/D3/E2 plumbing | ✅ | — |
| B2 rzeszow/torun, relatedArticle | ✅ | — |
| D4 INTENT_MAP_PRAWO.md | — | **Strateg/Pisarz** |
| B3 publikacja draft→published | — | **panel admina przed rozsyłką** |
| F2 kategoria dla-wlascicieli | ✅ (4 zmiany) | **decyzja Biznesowy** |
| F1 Disallow filtrów | ✅ (warunkowo) | **eksport URL z GSC** |


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
