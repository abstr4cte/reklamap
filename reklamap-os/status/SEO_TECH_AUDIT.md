# Audyt techniczny SEO — ReklaMap

Prowadzony przez Agenta Architekta SEO. Najnowszy audyt na górze. Statusy aktualizować przy wdrożeniach.

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
