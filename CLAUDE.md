# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

> **Zasada (meta):** istotne, trwałe ustalenia — reguły SEO, architektury, deployu, niezmienniki kodu, decyzje produktowe — zapisujemy TUTAJ, w instrukcjach (wersjonowane, współdzielone, ładowane do każdej sesji), a NIE tylko w auto-pamięci Claude (per-maszyna, ulotna, niewspółdzielona). Ważne = do repo, żeby nie było lipy.

## Project Overview

**ReklaMap** is a Polish advertising surface marketplace ("OLX for advertising surfaces"). Users list and search for outdoor/mobile advertising space. The app is a decoupled SPA: a Laravel 12 backend API and a Vue 3 + TypeScript frontend.

---

## Commands

### Statystyki produkcji
```bash
php scripts/stats.php            # ostatnie 7 dni
php scripts/stats.php --days=30  # ostatnie 30 dni
php scripts/stats.php --url=http://localhost:8000  # lokalnie (dev)
```
Wynik ląduje w `reklamap-os/stats/stats-YYYY-MM-DD.md` — wklej zawartość do rozmowy.
Wymaga `INTERNAL_APP_KEY` w `backend/.env` zgodnego z produkcją.

### Frontend (`/frontend`)
```bash
npm run dev           # Dev server at localhost:5173 (proxies /api to localhost:8000)
npm run build         # Production build
npm run test          # Vitest in watch mode
npm run test -- --run # Run tests once (used by pre-commit hook)
npm run test:coverage # Coverage report
npm run test:ui       # Vitest UI
```

### Backend (`/backend`)
```bash
php artisan serve                  # Dev server at localhost:8000
php artisan test                   # Run all PHPUnit tests
php artisan test --testsuite=Unit  # Run only unit tests
php artisan test tests/Unit/AdvertisementTest.php  # Run a single test file
php artisan migrate                # Run pending migrations
php artisan migrate:fresh          # Wipe and re-run all migrations
php artisan make:migration <name>  # Create a new migration
```

### Combined dev (from `/backend`)
```bash
composer run dev  # Starts Laravel, queue, logs, and Vite concurrently
```

---

## Architecture

### Backend (Laravel 12, PHP 8.2+)
- **Auth**: No user accounts. Two authentication layers:
  - `VerifyAppKey` middleware — all routes require `X-App-Key` header (`INTERNAL_APP_KEY` env var)
  - `VerifyManagementToken` middleware — edit/delete routes require a time-limited token sent via email
- **API routes**: `backend/routes/api.php` — all under `/api/`, no auth:sanctum for most routes
- **Key controllers**: `AdvertisementController` (main CRUD + stats + PDF + contact), `ManagementController` (token send/validate), `StorageController` (image upload), `BlogController`, `SearchAlertController`, `NewsletterController`
- **reCAPTCHA**: `VerifyRecaptcha` middleware applied to contact/newsletter/management-link routes
- **PDF generation**: DomPDF via Blade templates in `resources/views/pdf/`
- **Testing**: PHPUnit with in-memory SQLite (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`)

### Frontend (Vue 3, TypeScript, Vite)
- **API calls**: All through `frontend/src/services/api.ts` — sets `X-App-Key` header on every request
- **State**: Three Pinia stores — `useSearchStore` (search/filter/listings), `usePreferencesStore` (favorites/comparison in localStorage), `useAuthStore`
- **Routing**: `frontend/src/router.ts`
- **Types**: All TypeScript interfaces in `frontend/src/types.ts`
- **Path alias**: `@` maps to `frontend/src/`
- **Testing**: Vitest + happy-dom, setup in `frontend/tests/setup.ts`
- **Test files**: `frontend/tests/unit/` — covers dimension conversion, price conversion, search store filtering/sorting

### CI/CD
- **Pre-commit hook**: Runs all tests (frontend + backend) before every commit
- **GitHub Actions**: Runs tests on push to `master`/`develop`

---

## Key Domain Logic

### Dimension normalization
All dimensions stored in the database as **meters**. LED screens are the exception: users input/see values in **mm**, which must be converted to meters before saving and back to mm for display. Filters must also convert mm → meters before comparison.

### Price display
One price + one unit stored per ad (`price` + `price_unit`). Frontend converts on-the-fly to display in any of the 6 units (`/dzień`, `/tydzień`, `/miesiąc`, `/rok`, `/m²`, `/kampania`). Estimated prices are marked with `~`.

### Stats tracking
Views, phone clicks, and email clicks are tracked in the `advertisement_daily_stats` table (daily granularity). There are **no** stats columns on the `advertisements` table. Always sum from `advertisement_daily_stats`.

### Traffic fields
`traffic_intensity`, `traffic_direction`, and `traffic_type` apply to **all outdoor types**: billboard, banner, wall, totem — not billboard only.

### Variants
Only types with meaningful physical configurations have variants. `banner` and `wall` have no variants.

---

## SEO, Sitemap i Deploy (KRYTYCZNE)

- **Prerender DZIAŁA (build-time).** Boty dostają pełny statyczny HTML, nie pusty SPA. `frontend/scripts/prerender.mjs` (puppeteer) renderuje trasy **z sitemapy** do `dist/<trasa>/index.html` podczas `frontend/deploy.sh` (`npm run build:seo`); `frontend/public/.htaccess` serwuje te pliki. Runtime'owy prerender.io wygasł 2026-05-18 i został zastąpiony. **Jeśli URL-a nie ma w sitemapie → nie zostaje sprerenderowany → `.htaccess` oddaje botowi generyczny szkielet strony głównej** (nie własną treść).
- **Prerender ZASZYWA stan Pinia (`window.__INITIAL_STATE__`) — NIE usuwać (naprawa deindeksu 2026-07-06).** Sama treść w prerenderowanym DOM NIE wystarcza: Googlebot wykonuje JS, `main.ts` robi `createApp().mount('#app')` (fresh mount, BEZ hydratacji SSR) → Vue kasuje prerenderowany DOM i re-fetchuje listingi z cross-origin `api.reklamap.pl`; w oknie renderowania WRS ten fetch bywa ucinany → bot widzi pustą stronę („Nie znaleziono ogłoszeń") mimo pełnego prerenderu (potwierdzone Live Testem GSC: `curl -A Googlebot` = 821 ofert, ale render Google = 0). Dlatego `prerender.mjs` wstrzykuje stan store (kolektor `window.__collectSSRState` z `main.ts`) jako inline `<script>window.__INITIAL_STATE__=…</script>`, a `useSearchStore` seeduje z niego `listings/serverTotal/hasLoaded` przy inicjalizacji (`_ssr`); runtime-fetch tylko odświeża, a jego `catch` zachowuje seed. **NIE usuwaj i nie zmieniaj bez zrozumienia:** kolektora w `main.ts`, wstrzyknięcia w `prerender.mjs`, seedu `_ssr` w `useSearchStore`, ani bramki `hasLoaded` w `AdGrid`/`ListingsPage` (empty-state „brak wyników" pokazuj TYLKO gdy `hasLoaded===true`, inaczej bot fałszywie widzi „0 ofert"). Objaw regresji: `curl -A Googlebot reklamap.pl` pokazuje oferty, ale Live Test GSC = 0.
- **`index.html` guard „stale deploy" NIE dotyczy botów.** Skrypt recovery po stale-deployu robi `location.replace('…?_v=…')`, a `?_v=` jest w `robots.txt` (`Disallow: /*?_v=`) — dla crawlerów to redirect na URL raportowany jako „zablokowany". Guard ma bramkę na UA bota (`/bot|crawl|spider|…/i` + puste UA) — **nie usuwaj jej**; boty i tak dostają treść z prerenderu, nie potrzebują recovery.
- **`reserved` MUSI zostawać w sitemapie.** Status `reserved` = nośnik zarezerwowany przez kogoś, ale to **realne, pełne ogłoszenie z treścią** → ma być indeksowane (`unavailable` = zdjęte/404 to osobna sprawa). Generator: `backend/routes/web.php` (`/sitemap.xml`, cache 1h pod kluczem `sitemap_xml`), pętla leaf: `whereIn('status', ['active','soon_available','reserved'])`. Wykluczaj tylko `draft` i `unavailable`. **NIE przywracaj filtra wycinającego `reserved`** — odcina je też od prerenderu.
- **Sitemap serwowany statycznie z frontu.** `reklamap.pl/sitemap.xml` to plik zapisywany do `dist/` przez `prerender.mjs` (nie 301 → api). `.htaccess` ma warunkowy fallback 301 na `api` tylko gdy pliku brak (`!-f`).
- **Dopasowanie miasta ze slugu MUSI być odporne na diakrytyki I myślniki (naprawa 2026-07-07).** URL-e kategorii miast są ASCII (`slugify`: `Kłodzko`→`klodzko`, `Szklary-Huta`→`szklary-huta`), a `deslugify` (frontend) odtwarza właściwą nazwę tylko dla garstki miast z `cityMap` — reszta (długi ogon) trafia do API jako ASCII ze spacją (`"Klodzko"`, `"Szklary Huta"`). Dlatego `AdvertisementController::buildFilteredQuery` **foldduje polskie znaki ORAZ myślnik→spacja po obu stronach** (stała `PL_FOLD`, `REPLACE`+`LOWER` w SQL / `strtr`+`mb_strtolower` w PHP). Bez tego strona kategorii miasta z diakrytykiem/myślnikiem (Kłodzko 138, Polanica-Zdrój, Szklary-Huta…) zwraca **0 ofert → pusta dla użytkownika I fałszywy `noindex` w prerenderze**. NIE cofaj folda do `LOWER(city)=?`. Test: `AdvertisementApiTest::test_city_strict_filter_matches_*`.
- **Próg cienkiej strony `THIN_PAGE_THRESHOLD=3` MUSI być spójny w 3 miejscach.** (1) `frontend/src/utils/listingsSeo.ts` — nadaje `noindex` stronom typ/miasto/typ×miasto poniżej progu; (2) generator sitemapy `backend/routes/web.php` — te same strony (w tym **pętla TYPÓW**, nie tylko miasta/kombinacje) MUSZĄ być poza sitemapą, inaczej GSC: „Submitted URL marked noindex"; (3) `prerender.mjs` — trasy stanowe bez danych nie są zapisywane (bramka noindex). Rozjazd tych trzech = sprzeczny sygnał indeksacji.
- **Kolejność deployu przy zmianie sitemapy:** najpierw backend na prod (`git pull` na Hostido + `php artisan cache:clear` — sitemap cache'owany 1h), DOPIERO POTEM front `cd frontend && ./deploy.sh` (prerender czyta świeżą prod-sitemapę). Odwrotnie = front sprerenderuje starą listę.
- **Tripwire deindeksu (`php artisan seo:tripwire`) — check przy KAŻDYM deployu frontu.** `deploy.sh` uruchamia go po rsyncu (miękko: nie przerywa, ale głośno sygnalizuje). Próbkuje z prod-sitemapy (home + combo + kategoria + leaf + artykuł bloga) i sprawdza niezmienniki cichego deindeksu: `index` (nie `noindex`), zaszyty `__INITIAL_STATE__` (seed), realna treść, brak fałszywego empty-state. Świadoma decyzja: **bez crona** (uruchamiamy przy deployu, nie codziennie); można też odpalić ręcznie w razie podejrzeń. Alert mailem na `mail.from.address` przy wykryciu problemu.
- **Baza lokalna ≠ produkcja.** Ta maszyna to dev/build; prod jest na Hostido. Liczby (np. liczba ogłoszeń) różnią się — diagnozy o realnym stanie rób przeciw prod (`curl` live `reklamap.pl`, prod API `api.reklamap.pl` z `X-App-Key`, SSH Hostido), nie lokalnym `php artisan tinker`.
- **`api.reklamap.pl` jest load-bearing dla SEO.** Render ogłoszeń (i historycznie sitemap) zależą od `api`; jego awarie SSL psują obraz w Google. `api.reklamap.pl` musi być w certyfikacie auto-odnawianym (SAN).

---

## Narzędzia SEO i analityki — stan (ost. przegląd 2026-07-25)

> Ta sekcja jest źródłem prawdy o tym, **co jest podpięte, co świadomie odpuściliśmy i dlaczego**. Zanim zaproponujesz „warto dodać narzędzie X" — sprawdź tutaj, czy nie zostało już odrzucone z powodem.

### Podpięte i działające

| Narzędzie | Stan | Uwagi |
|---|---|---|
| **Google Search Console** | ✅ + **dostęp API** | Właściwość typu **Domena** → `siteUrl = "sc-domain:reklamap.pl"` (www i non-www w jednej właściwości) |
| **Google Analytics 4** | ✅ + **dostęp API** | `properties/526431028` |
| **Bing Webmaster Tools** | ✅ + **dostęp API** | Weryfikacja NIE plikiem (`BingSiteAuth.xml` nie istnieje). Klucz: `/home/dev/.config/reklamap/bing-api-key.txt` |
| **Microsoft Clarity** | ✅ kod (2026-07-25) | `VITE_CLARITY_ID`; bez ID i dla botów **nie ładuje się** (`frontend/src/utils/clarity.ts`) |
| **Sentry** | ✅ | `VITE_SENTRY_DSN`, konfiguracja w `main.ts` |
| **Schema.org** | ✅ | `Product`/`Offer` (leaf), `BreadcrumbList`, `BlogPosting`, `ItemList` (kategorie), **`FAQPage`** (blog) |
| **Prerender build-time + seed** | ✅ | Patrz sekcja „SEO, Sitemap i Deploy" — niezmienniki krytyczne |
| **Tripwire deindeksu** | ✅ | `php artisan seo:tripwire`, wpięty w `deploy.sh` |
| **Image-sitemap** | ✅ | 825 `<image:image>` (commit `61705e3`) |

**Konto usługi Google** (czyta GSC + GA4): `claude-reader@gen-lang-client-0352328852.iam.gserviceaccount.com`, klucz JSON w `/home/dev/.config/reklamap/gsc-service-account.json` — **poza repo**, nigdy nie commituj. Wymaga włączonych: Search Console API, Analytics Data API, Analytics Admin API. Uwierzytelnianie ręcznie budowanym JWT (`PyJWT` + `cryptography` są w systemie, `google-auth` **nie jest potrzebne**).

> **Nie proś użytkownika o eksporty CSV ani zrzuty ekranu z GSC/GA4/Bing.** Wszystkie trzy źródła czytamy programowo. Ręczne klikanie po panelach było wyraźnym źródłem frustracji.

### Dodane 2026-07-25

- **Microsoft Clarity** — heatmapy i nagrania sesji (darmowe, bez limitu). Powód: GA4 mówi, **ile** osób odpadło (72 otwarcia ogłoszenia → 7 kliknięć w telefon), ale nie mówi **dlaczego**. Dwie firmy napisały zapytania na `kontakt@` zamiast użyć platformy — Clarity ma pokazać, gdzie szukały kontaktu. Bramka na UA bota jest celowa: prerender (puppeteer) nie ma zaśmiecać nagrań ani obciążać renderu dla botów.
- **Fix `FAQPage`** — schemat powstawał tylko przy nagłówku dokładnie „Najczęściej zadawane pytania"; artykuły z `## FAQ` traciły rich snippet (3 z 28: `citylight-reklama`, `czy-oplaca-sie-wynajmowac-powierzchnie-reklamowa`, `jak-zarobic-na-wynajmie-powierzchni-reklamowej`). Logika wydzielona z `BlogPostPage.vue` do **`frontend/src/utils/faqSchema.ts`** (testowalna, wzorzec `listingsSeo.ts`) + 15 testów regresyjnych. **Wymaga deployu frontu, żeby zadziałało na prodzie.**

### Świadomie ODPUSZCZONE (nie proponuj ponownie bez nowego argumentu)

- **Cloudflare — NIE teraz.** Trzy powody: (1) mamy nierozwiązane **24 strony z 5xx wyłącznie dla Googlebota** (Bing: `Code5xx=0` na 14 656 pobrań) — dokładanie warstwy przed diagnozą zaciemni obraz; (2) **Rocket Loader** i **HTML minification** w CF mogą uszkodzić inline `<script>window.__INITIAL_STATE__=…</script>`, czyli niezmiennik chroniący przed deindeksem; (3) tryb **„Under attack"** serwuje JS-owy challenge **wszystkim**, w tym Googlebotowi → natychmiastowy deindeks.
  **Uwaga o panelu Hostido:** zakładka „AntyDDoS → token CloudFlare" **nie jest integracją** i nie podpina Cloudflare. Zakłada, że CF już masz, i daje Hostido możliwość automatycznego przełączenia domeny w tryb „Under attack" — czyli dokładnie to, co dla SEO jest najgroźniejsze. Realne podpięcie CF wymaga przestawienia **nameserverów domeny** na Cloudflare (zmiana DNS, robiona w oknie serwisowym, z planem wycofania).
  Warunek powrotu do tematu: naprawione 5xx + potwierdzone, że `Rocket Loader`/`Auto Minify (HTML)`/`Bot Fight Mode` będą **wyłączone**.
- **Rank trackery (Semrush, Senuto, Ahrefs płatny)** — przy 179 kliknięciach na kwartał GSC + Bing API dają wszystko, czego potrzeba. Kilkaset zł/mies. za dane, które już mamy.
- **Google Business Profile** — ReklaMap to platforma ogólnopolska, nie lokalny biznes z adresem obsługującym klientów.

### Do rozważenia (nieodrzucone, niewdrożone)

- **IndexNow** — brak implementacji (`indexnow.txt` zwraca SPA-fallback). Bing stawia to jako rekomendację nr 1. Sens: 900 URL-i „wykryte, niezindeksowane" w GSC; IndexNow zgłasza URL w sekundy do Binga/Yandexa/Seznama, a więc pośrednio do ChatGPT Search i Copilota. Naturalne miejsca wpięcia: `deploy.sh` po rsyncu (jak tripwire) + hook przy publikacji ogłoszenia.
- **Ahrefs Webmaster Tools — ODROCZONE, nie zakładać teraz (ustalenie 2026-07-25).** Darmowe dla zweryfikowanego właściciela, ale **dziś nie ma czego mierzyć**: profil linków to **zero domen**, potwierdzone trzema metodami Bing API (`GetLinkCounts`, `GetConnectedPages`, `GetUrlLinks` — wszystkie puste) i niezależnie przez GA4 (100% referrali to webmaile, Facebook i podglądy linków w Teams). AWT zmierzyłby to samo zero. **Warunek powrotu:** kiedy ruszy budowa linków (Tier 3 poz. 23 raportu) i pojawi się co monitorować.
  **Priorytetem nie jest narzędzie do mierzenia linków, tylko same linki.** Najniższy opór: agencje, których nośniki importujemy (Big Group, Outdoor 3miasto, reklama.ai, Optokom — link jest dla nich darmową ekspozycją), katalogi branżowe OOH i izby gospodarcze, portale marketingowe (OOH Magazine, Nowy Marketing, Wirtualne Media) — mamy 827 realnych ofert z cenami, czyli dane branżowe, których nikt inny nie opublikuje. Przy śr. pozycji 31,3 treść dowozi wyświetlenia, **linki dowożą pozycje**.

---

## Adding a New Field to Advertisements

1. `php artisan make:migration add_<field>_to_advertisements` → update `backend/database/migrations/`
2. Update `$fillable` and `$casts` in `backend/app/Models/Advertisement.php`
3. Update validation in `backend/app/Http/Controllers/AdvertisementController.php`
4. Update `frontend/src/types.ts`
5. Update forms: `AddAdPage.vue`, `ManagementPage.vue`
6. Update display: `AdDetailPage.vue`, `ComparisonPage.vue`, `frontend/src/config/comparisonFields.ts`
7. Update PDFs: `backend/resources/views/pdf/`

---

## System Agentów AI — Zasada Priorytetu

### 🚨 KLUCZOWA ZASADA
Dla każdego zadania **wykraczającego poza czystą edycję kodu** — zacznij od odczytania `reklamap-os/ROUTING.md`, który zawiera pełną mapę agentów i procedury. Nie używaj trybu ogólnego (Explore) do spraw produktowych, SEO ani treści.

**Skrócona mapa kompetencji:**

| Temat zadania | Agent |
|---|---|
| Strategia, monetyzacja, backlog, sensowność biznesowa | `AGENT_BIZNESOWY` |
| Audyt techniczny SEO kodu (Laravel/Vue), URL, Schema.org | `AGENT_ARCHITEKT_SEO` |
| Research słów kluczowych, planowanie bloga | `AGENT_STRATEG_SEO` |
| Pisanie artykułu SEO | `AGENT_PISARZ` |
| Korekta tekstu, usuwanie AI-izmów, ocena naturalności | `AGENT_KOREKTOR` |
| Cold calling, skrypty sprzedażowe, pozyskiwanie nośników | `AGENT_MARKETER` |
| Analiza danych (GSC, GA4, `stats.php`), raporty, brief z tematami pod SEO, rekomendacje kanałów promocji | `AGENT_ANALITYK` |

**Procedury specjalne** (szczegóły w ROUTING.md):
- "Narada" → Biznesowy + Architekt jednocześnie
- Nowy artykuł → Strateg → Pisarz → Korektor
- Przegląd danych / "w którą stronę pod SEO" → Analityk → Strateg → Pisarz → Korektor
- Brak pasującego agenta → działam sam, rozważam czy zasugerować nowego agenta

---

## System Agentów AI (reklamap-os/)

Projekt posiada zespół wyspecjalizowanych agentów AI w `reklamap-os/agents/`. Punktem wejścia jest **Router** (`reklamap-os/ROUTING.md`) — wczytaj go, aby zarządzać całym zespołem.

### Agenci i ich role

| Plik | Rola |
|---|---|
| `AGENT_STRATEG_SEO.md` | Research słów kluczowych, planowanie tematów bloga |
| `AGENT_PISARZ.md` | Pisanie artykułów SEO na podstawie brudnopisu |
| `AGENT_KOREKTOR.md` | Audyt tekstu, usuwanie AI-izmów, weryfikacja faktów |
| `AGENT_ARCHITEKT_SEO.md` | Audyt techniczny SEO kodu (Laravel + Vue) |
| `AGENT_BIZNESOWY.md` | Strategia produktu, monetyzacja, backlog RICE |
| `AGENT_MARKETER.md` | Cold calling, skrypty sprzedażowe, pozyskiwanie nośników |
| `AGENT_ANALITYK.md` | Analiza danych (GSC, GA4, `stats.php`), raporty, brief z tematami/frazami dla Stratega |

### Workflow analityczny (Data → Treść)

1. **Wywołaj Agenta Analityka** — zbiera eksporty (GSC: zapytania/strony/wisienki 5–20, GA4: pozyskiwanie/strony/konwersje, `php scripts/stats.php`), robi raport i dopisuje brief do `reklamap-os/status/ANALYTICS_LOG.md` (blok `➡️ DLA STRATEGA`)
2. **Wywołaj Agenta Stratega** — bierze brief z `ANALYTICS_LOG.md` jako punkt wyjścia researchu
3. Dalej standardowy Content Pipeline (Pisarz → Korektor)

### Workflow bloga (Content Pipeline)

1. **Wywołaj Agenta Stratega** — research (AnswerThePublic → Ahrefs → Perplexity), zapisuje dane do `reklamap-os/status/BRUDNOPIS_SEO.md`
2. **Wywołaj Agenta Pisarza** — pisze artykuł z brudnopisu, zapisuje w `reklamap-os/blog/posts/`, aktualizuje `reklamap-os/blog/INDEX.md` i `backend/database/seeders/BlogPostsSeeder.php`
3. **Wywołaj Agenta Korektora** — audyt i korekta, oznacza artykuł jako `✅ ZRECENZOWANY` w INDEX.md
4. Uruchom `php artisan db:seed --class=BlogPostsSeeder` — synchronizuje z bazą danych (status: `draft`)
5. Publikuj ręcznie przez panel admina

### Pliki stanu systemu

- `reklamap-os/status/BRUDNOPIS_SEO.md` — dane z researchu dla bieżącego artykułu
- `reklamap-os/status/STRATEGY_LOG.md` — historia researchu SEO
- `reklamap-os/status/SALES_LOG.md` — wyniki rozmów sprzedażowych
- `reklamap-os/status/ANALYTICS_LOG.md` — historia przeglądów danych i briefów Analityka
- `reklamap-os/status/SEO_TECH_AUDIT.md` — log audytów technicznych SEO Architekta (statusy wdrożeń)
- `reklamap-os/docs/PRODUCT_BACKLOG.md` — backlog produktowy z RICE
- `reklamap-os/docs/MARKETING_ASSETS.md` — skrypty i szablony sprzedażowe
- `reklamap-os/blog/INDEX.md` — indeks wszystkich postów blogowych

---

## Conventions

- **Component naming**: PascalCase; views have `Page` suffix (`ListingsPage.vue`)
- **Store naming**: `use` prefix (`useSearchStore.ts`)
- **Vue SFC order**: `<script setup lang="ts">` → `<template>` → `<style scoped>`
- **Toast notifications**: Use the global `useToast` composable (set in `App.vue`), never create local instances
- **Mobile breakpoint**: 768px

---

## Senior Dev Standards

### Laravel / PHP

- **Typed everything**: zawsze deklaruj typy parametrów i zwracane wartości. Żadnych `mixed` bez uzasadnienia.
- **Validation w Form Request**: logika walidacji idzie do dedykowanej klasy `app/Http/Requests/`, nigdy inline w kontrolerze (`$request->validate([...])`).
- **Kontroler = ruch drogowy**: kontroler tylko przyjmuje request, deleguje i zwraca response. Logika biznesowa (np. kalkulacje, transformacje) ląduje w serwisie lub modelu.
- **HTTP status codes**: `201` przy tworzeniu zasobu, `204` przy usunięciu, `422` przy błędach walidacji, `404` gdy zasób nie istnieje — nigdy wszystko jako `200`.
- **`env()` tylko w `config/`**: nigdy nie wywołuj `env('COKOLWIEK')` bezpośrednio w kodzie poza plikami konfiguracyjnymi. Używaj `config('app.cokolwiek')`.
- **Transakcje przy multi-write**: jeśli jedna operacja pisze do kilku tabel, opakuj w `DB::transaction()`.
- **Brak `dd()` / `var_dump()`** w kodzie — debug tylko przez logi (`Log::debug()`).
- **Indeksy na foreign keys**: każda migracja dodająca `foreignId` musi mieć `->index()` lub `->constrained()`.
- **`$fillable` zamiast `$guarded = []`**: zawsze jawna lista pól do masowego przypisania.
- **Aktualizacja istniejących danych = update W MIEJSCU, nigdy delete+create**: zmiana pola w już istniejących (zwłaszcza produkcyjnych) rekordach — np. dodanie telefonu, korekta ceny/kategorii — przez `updateOrCreate` z naturalnym kluczem (np. `owner_email` + `title`). `delete()` + `create()` przedatuje `created_at` (fałszywe „Dodano dziś"), zmienia `id` → zmienia URL (`slug-{id}`) i osierica statystyki (`advertisement_daily_stats`). Pełny delete+create tylko przy PIERWSZYM imporcie (brak rekordów).

### Vue 3 / TypeScript

- **Żadnego `any`**: wszystkie typy z `frontend/src/types.ts`. Jeśli brakuje interfejsu — dopisz go.
- **`defineProps` i `defineEmits` zawsze typowane**: `defineProps<{ title: string }>()`, nie obiektowa składnia bez typów.
- **`computed` zamiast metod** dla wartości pochodnych — metoda przelicza się przy każdym renderze, computed jest memoizowany.
- **Nie mutuj propsów**: jeśli komponent potrzebuje modyfikować wartość z props, skopiuj do lokalnego `ref`.
- **Logika async = loading + error state**: każde wywołanie API musi obsługiwać stan ładowania i błędu — nigdy nie zakładaj że się uda.
- **Komponenty do 300 linii**: jeśli SFC rośnie powyżej, wydziel logikę do composable lub podziel komponent.
- **Brak logiki w szablonie**: `v-if="user.role === 'admin' && !loading && items.length > 0"` → wyrzuć to do `computed`.
- **Composables dla współdzielonej logiki**: jeśli ten sam pattern pojawia się w 2+ komponentach, ląduje w `composables/use*.ts`.
