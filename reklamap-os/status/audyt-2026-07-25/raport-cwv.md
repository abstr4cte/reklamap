# Raport CWV — ReklaMap, 2026-07-25 (Architekt SEO)

Domyka lukę z audytu 2026-07-07: *„CWV: zero pomiarów — cały wymiar to inferencja strukturalna"*
(`reklamap-os/status/SEO_TECH_AUDIT.md:38,43`).

**Status pomiarów:** 12 przebiegów Lighthouse 13.4.1 na żywym prodzie (6 typów stron × mobile/desktop)
+ niezależna weryfikacja CLS przez `PerformanceObserver` w Chromium 150 (2 warianty: bez throttlingu i slow-4G+4× CPU).
Surowe raporty: `scratchpad/lhout/*.json`, skrypty: `scratchpad/run_lh.sh`, `scratchpad/cls_probe2.mjs`.

---

## 0. Czego NIE udało się zmierzyć (brak danych, nie zgadywanie)

| Co | Status | Powód |
|---|---|---|
| **CrUX / `loadingExperience` / `originLoadingExperience`** | **BRAK DANYCH** | PageSpeed Insights API zwraca **HTTP 429** („Quota exceeded … consumer project_number:583797351490") na anonimowym limicie — 3 próby w oknie ~70 min (15:12, 15:24, 16:05). CrUX API bez klucza: **HTTP 403** („Method doesn't allow unregistered callers"). Token konta usługi GSC/GA4 nie autoryzuje PSI. |
| **INP** | **BRAK DANYCH** | INP jest metryką **wyłącznie polową** (CrUX/RUM) — Lighthouse jej nie mierzy. Zastępczo raportuję **TBT** (lab proxy). |
| **Wynik Lighthouse SEO (0–100)** | **BRAK DANYCH** | Audyt `canonical` wywala się na tej maszynie: `URL.parse is not a function` (Node v21.5.0, LH wymaga ≥22.12) → kategoria bez wyniku. **Pozostałe 9/10 audytów SEO przechodzi na wszystkich 6 stronach.** |
| Podział urządzeń w GA4 | **BRAK DANYCH** | eksport `ga4-2026-07-25/` nie zawiera wymiaru device. |

**Jak founder może dobrać dane polowe (nie robię tego sam):** GSC → raport „Core Web Vitals" (czyta CrUX);
albo klucz API w GCP → `pagespeedonline` + `chromeuxreport`.

---

## 1. Pomiary — tabela zbiorcza

Lighthouse 13.4.1, prod, 2026-07-25 13:17–14:20 UTC. Mobile = Moto G Power, slow-4G symulowane, 4× CPU. LCP/FCP/SI w ms.

| Strona | perf | a11y | best-pr | FCP | **LCP** | TBT | **CLS** | SI | TTFB | KB | req |
|---|---|---|---|---|---|---|---|---|---|---|---|
| home mobile | 65 | 84 | 92 | 2933 | **17438** | 257 | **0,000** | 3018 | 21 | 3275 | 76 |
| kat_typ (billboardy) mobile | **40** | 86 | 92 | 3199 | **19675** | 210 | **0,879** | 3435 | 19 | 3748 | 58 |
| kat_miasto (klodzko) mobile | 46 | 86 | 100 | 2467 | **7722** | 150 | **0,879** | 3068 | 18 | 3616 | 58 |
| blog (billboard-reklama) mobile | 45 | 88 | 100 | 2741 | **9193** | 152 | **0,899** | 2741 | 18 | 2230 | 42 |
| leaf 996 (prerenderowany) mobile | 44 | 90 | 100 | 2859 | **10875** | 128 | **0,883** | 2859 | 20 | 2538 | 63 |
| leaf 997 (BEZ prerenderu) mobile | 44 | 90 | 100 | 2816 | **14248** | 101 | **0,902** | 3740 | 18 | 2334 | 51 |
| home desktop | 76 | 84 | 100 | 1153 | 2744 | 198 | **0,001** | 1370 | 19 | 3315 | 78 |
| kat_typ desktop | 56 | 85 | 100 | 756 | 3410 | 0 | **0,891** | 1100 | 17 | 4087 | 64 |
| kat_miasto desktop | 58 | 88 | 100 | 763 | 3050 | 0 | **0,932** | 1006 | 17 | 3933 | 61 |
| blog desktop | 61 | 88 | 100 | 728 | 2528 | 0 | **0,963** | 969 | 17 | 2228 | 42 |
| leaf 996 desktop | 60 | 90 | 100 | 766 | 2694 | 0 | **0,932** | 945 | 16 | 2536 | 75 |
| leaf 997 desktop | 63 | 90 | 100 | 670 | 2423 | 18 | **0,809** | 1234 | 17 | 2452 | 58 |

Progi Google: LCP dobry ≤2500 ms / zły >4000; CLS dobry ≤0,1 / zły >0,25; TBT (proxy INP) dobry ≤200 ms.

**Czytelne wnioski z tabeli:**
- **TTFB 16–21 ms na wszystkich 12 przebiegach** — serwowanie statyki (LiteSpeed/Hostido) jest bez zarzutu.
  `document-latency-insight`: `serverResponseIsFast` ✅, `usesCompression` ✅, `noRedirects` ✅. **Serwer nie jest wąskim gardłem.**
- **TBT 0–257 ms** — w normie albo blisko. Główny wątek nie jest wąskim gardłem.
- **CLS 0,809–0,963 na 10 z 12 przebiegów** — to jedyna metryka, która jest **czerwona wszędzie i na obu urządzeniach**. Próg „zły" to 0,25; jesteśmy **3,5×** wyżej.
- LCP mobilne 7,7–19,7 s to w dużej mierze pochodna wagi strony (2,2–4,1 MB) przy symulowanym slow-4G; desktop 2,4–3,4 s (LCP „do poprawy", nie „zły").

---

## 2. ZNALEZISKO #1 (nowe, największe): CLS 0,88 — Vue kasuje prerenderowany DOM, bo trasy są `import()`

Tego nie było w audycie 07.07. Zmierzone **dwoma niezależnymi narzędziami**.

### Mechanizm — zmierzony klatka po klatce (`scratchpad/cls_probe2.mjs`, kontekst incognito, cache off, SW off)

`/powierzchnie-reklamowe/billboardy`, viewport 412×823:

| moment | wysokość dokumentu | `.listing-card` | `footer.app-footer` offsetTop | długość HTML |
|---|---|---|---|---|
| `readyState=interactive` (t=1879 ms) | 3151 px | **24** | **2193 px** | 197 779 zn. |
| `DOMContentLoaded` (t=4843 ms) | **1058 px** | **0** | **100 px** | **86 116 zn.** |
| `readyState=complete` (t=18 535 ms) | 3131 px | 24 | 2174 px | 194 575 zn. |

Czyli: prerenderowana treść (24 kafle) **znika**, strona zwija się do „nagłówek + stopka", i dopiero po
**13,7 s** (slow-4G+4×CPU) / **~550 ms** (bez throttlingu) wraca. Zarejestrowane jako **jedno przesunięcie
`FOOTER.app-footer` y=100→0 o wartości 0,8785**.

### Wyniki niezależnej sondy (PerformanceObserver, nie Lighthouse)

| strona | CLS bez throttlingu | CLS slow-4G + 4× CPU | winowajca |
|---|---|---|---|
| home | 0,0000 | **0,0606** | cookie-banner + search-card |
| kat_typ | 0,0000 | **0,8792** | `FOOTER.app-footer` 100→0 (v=0,8785) |
| kat_miasto | 0,0000 | **0,8793** | `FOOTER.app-footer` 100→0 |
| blog | 0,0000 | **0,8994** | `FOOTER.app-footer` 100→0 |
| leaf | 0,0003 | **0,8837** | `FOOTER.app-footer` 100→0 |

**Uczciwe zastrzeżenie o zakresie szkody:** na łączu tej maszyny bez żadnego throttlingu CLS = 0,0000 —
kasowanie i odbudowa mieszczą się w jednej klatce renderu, więc przeglądarka nie zapisuje przesunięcia.
Szkoda jest więc **zależna od urządzenia i łącza**.

Ale próg jej wyzwolenia jest **niski, nie ekstremalny**: preset **desktop** Lighthouse'a (10 Mbps, RTT 40 ms,
**bez spowolnienia CPU** — czyli warunki lepsze niż przeciętny polski użytkownik) daje **CLS 0,891 / 0,932 /
0,963 / 0,932 / 0,809** na kategorii, mieście, blogu i obu leafach. Czyli wystarczy zwykłe łącze zamiast
laboratoryjnego, żeby przesunięcie było realne. Zgodność trzech niezależnych ustawień (LH mobile 6/6,
LH desktop 5/6, sonda throttlowana 4/5) wokół wartości ≈0,88 traktuję jako potwierdzenie.

Kontrola negatywna działa dokładnie tam, gdzie przewiduje mechanizm: **home ma CLS 0,000 (LH mobile),
0,001 (LH desktop), 0,061 (sonda throttlowana)** — bo `HomePage` jest importowane statycznie.

### Przyczyna źródłowa — dokładna linia

`frontend/src/router.ts:16` — `HomePage` jest importowany **statycznie**:
```ts
component: HomePage
```
`frontend/src/router.ts:31,37,43,49` (ListingsPage), `:59` (AdDetailPage), `:108,114` (BlogPostPage) — **leniwie**:
```ts
component: () => import('./views/ListingsPage.vue')
```
`frontend/src/main.ts:43` — `app.mount('#app')` **bez czekania na router**:
```ts
app.use(router)
app.mount('#app')
```

`mount()` natychmiast zastępuje zawartość `#app` powłoką aplikacji (header + `<router-view/>` pusty + footer),
bo dynamiczny `import()` komponentu trasy rozwiązuje się asynchronicznie. **Home jest odporne dokładnie dlatego,
że `HomePage` jest w głównym bundlu** — mount jest synchroniczny i treść nie znika (potwierdzone: home w DCL
nadal ma 24 kafle i footer na y=19351).

Chunk trasy **jest** preloadowany (`dist/powierzchnie-reklamowe/billboardy/index.html` zawiera
`<link rel="modulepreload" as="script" href="/js/ListingsPage-Ce76aqWv.js">`, w waterfallu 45→170 ms), więc to
nie problem sieci — to problem kolejności: `import()` i tak rozwiązuje się przez mikrozadanie po mountcie.

### Gotowy diff (XS w kodzie, M w weryfikacji — dotyka ścieżki krytycznej SEO)

```diff
--- a/frontend/src/main.ts
+++ b/frontend/src/main.ts
@@
 app.use(pinia)
 app.use(router)
-app.mount('#app')
+// Prerenderowany DOM zostaje na ekranie do czasu rozwiązania chunku trasy.
+// Bez tego mount() podmienia #app na pustą powłokę (router-view jeszcze nie ma
+// komponentu, bo trasy są () => import(...)), treść znika na 0,5–13 s,
+// a stopka skacze → zmierzone CLS 0,88 (Lighthouse 13.4.1 + PerformanceObserver, 2026-07-25).
+// catch: gdyby trasa nie dała się rozwiązać, montujemy mimo to — lepiej SPA niż biała strona.
+router.isReady().finally(() => app.mount('#app'))
```

**Weryfikacja obowiązkowa przed deployem** (niezmienniki z `CLAUDE.md`):
1. `php artisan seo:tripwire` po deployu (i tak wpięty w `deploy.sh`).
2. `curl -A Googlebot https://reklamap.pl/powierzchnie-reklamowe/billboardy` → treść + `index, follow` + `__INITIAL_STATE__`.
3. **GSC Live Test** na kategorii i artykule bloga — czy render Google nadal widzi oferty (to jest ten test,
   który w VII 2026 wykrył cichy deindeks).
4. `frontend/scripts/prerender.mjs` czyta `window.__collectSSRState` (`main.ts:50`) — kolektor odpala się po
   mouncie, więc prerender musi nadal poczekać na treść. Sprawdzić, czy build nie odpala `FAIL_RATE`.

**Alternatywa mniejszego ryzyka**, gdyby `isReady()` psuło prerender: statyczny import `ListingsPage`,
`BlogPostPage`, `AdDetailPage` w `router.ts` (koszt: `ListingsPage-Ce76aqWv.js` 142 KB + 42 KB CSS wchodzi
do głównego bundla — dziś główny bundel to 212 KB).

---

## 3. Konfrontacja z hipotezami z audytu 07.07 (poz. 7-9, 18-21)

### 3.1 „reCAPTCHA render-blocking w `frontend/index.html:148`" → **POTWIERDZONA**

`render-blocking-insight` wskazuje `https://www.google.com/recaptcha/api.js` na **12/12 przebiegów**:

| przebieg | wastedMs reCAPTCHA |
|---|---|
| home mobile | **918 ms** |
| kat_miasto mobile | 875 ms |
| kat_typ mobile | 807 ms |
| blog mobile | 806 ms |
| leaf996 mobile | 793 ms |
| desktop (wszystkie) | 252–305 ms |

Koszt pełny (waterfall `kat_typ__mobile`): **852 KB** transferu (2× `recaptcha__pl.js` 377 KB + 375 KB, ramka
`api2/anchor` 2×28 KB, `styles__ltr.css` 41 KB) oraz **429 ms czasu głównego wątku** (`bootup-time`:
`scripting=360 ms`, `parse=51 ms`) — druga pozycja po samym dokumencie. `unused-javascript` przypisuje
reCAPTCHA **445 KB martwego kodu** (256 917 + 187 956 B). Origin `www.google.com` figuruje jako kandydat do
`preconnect` (est. 95–122 ms) — zniknie razem ze skryptem.

reCAPTCHA jest potrzebne **wyłącznie po interakcji**: `EmailModal.vue:54`, `FeedbackModal.vue:111`,
`SearchAlertModal.vue:57`, `BlogPage.vue:204`, `AdContactForm`. **Nic na pierwszym renderze go nie używa.**

**Gotowy diff (effort M — dotyka 1 serwisu, 0 komponentów):**

```diff
--- a/frontend/index.html
+++ b/frontend/index.html
@@
-  <!-- Google reCAPTCHA v3 -->
-  <script src="https://www.google.com/recaptcha/api.js?render=%VITE_RECAPTCHA_SITE_KEY%"></script>
+  <!-- reCAPTCHA v3 ładowane leniwie z services/recaptchaService.ts — jako tag w <head> było
+       render-blocking 793–918 ms na mobile i 852 KB transferu na KAŻDYM wejściu, przy zerowym
+       użyciu przed interakcją (pomiar Lighthouse 13.4.1, 2026-07-25). -->
```

```diff
--- a/frontend/src/services/recaptchaService.ts
+++ b/frontend/src/services/recaptchaService.ts
@@
 const RECAPTCHA_SITE_KEY = import.meta.env.VITE_RECAPTCHA_SITE_KEY
 const RECAPTCHA_TIMEOUT_MS = 5000
+
+let loader: Promise<void> | null = null
+
+/** Wstrzykuje skrypt reCAPTCHA raz, na żądanie. Idempotentne. */
+function loadRecaptcha(): Promise<void> {
+  if (!RECAPTCHA_SITE_KEY) return Promise.reject(new Error('brak site key'))
+  if ((window as any).grecaptcha) return Promise.resolve()
+  if (loader) return loader
+  loader = new Promise<void>((resolve, reject) => {
+    const s = document.createElement('script')
+    s.src = `https://www.google.com/recaptcha/api.js?render=${RECAPTCHA_SITE_KEY}`
+    s.async = true
+    s.onload = () => resolve()
+    s.onerror = () => { loader = null; reject(new Error('reCAPTCHA load error')) }
+    document.head.appendChild(s)
+  })
+  return loader
+}
+
+/** Rozgrzewka: pierwszy realny gest użytkownika pobiera skrypt w tle,
+ *  żeby otwarcie modala nie czekało na 850 KB. */
+export function warmRecaptcha(): void {
+  if (!RECAPTCHA_SITE_KEY) return
+  const go = () => { loadRecaptcha().catch(() => {}) }
+  window.addEventListener('pointerdown', go, { once: true, passive: true })
+  window.addEventListener('keydown', go, { once: true })
+}
 
 export async function getRecaptchaToken(action: string): Promise<string> {
   if (!RECAPTCHA_SITE_KEY) return ''
 
   try {
+    await withTimeout(loadRecaptcha())
     const gr = (window as any).grecaptcha
     if (!gr) return ''
@@
 export function isRecaptchaAvailable(): boolean {
-  return !!(window as any).grecaptcha && !!RECAPTCHA_SITE_KEY
+  // Po przejściu na ładowanie leniwe „dostępne" = mamy klucz; skrypt dociągnie getRecaptchaToken().
+  return !!RECAPTCHA_SITE_KEY
 }
```
+ `warmRecaptcha()` wywołać raz w `main.ts` po mouncie.

**Sprawdzić przed wdrożeniem:** czy `VerifyRecaptcha` (backend) odrzuca pusty token — jeśli tak, timeout
5 s przy pierwszym otwarciu formularza na wolnym łączu może zablokować wysyłkę. `warmRecaptcha()` to
mitiguje, ale wymaga jednego testu E2E na formularzu kontaktowym.

**Zysk:** −852 KB (23% wagi strony kategorii), −793…918 ms render-blockingu na mobile, −429 ms CPU,
zniknięcie `www.google.com` z krytycznej ścieżki.

---

### 3.2 „Brak `preconnect` do `api.reklamap.pl`" → **OBALONA**

Lighthouse `network-dependency-tree-insight` → „Preconnect candidates" na **12 przebiegach**:
- **8×**: „No additional origins are good candidates for preconnecting"
- **4×** (blog mobile, leaf996 mobile, kat_typ desktop): kandydaci to wyłącznie `https://fonts.gstatic.com`
  (est. LCP savings **153–172 ms**) i `https://www.google.com` (**95–122 ms**).

**`api.reklamap.pl` nie pojawia się ani razu.** Powód jest strukturalny: prerender + seed
`__INITIAL_STATE__` zdejmują API z krytycznej ścieżki pierwszego renderu — połączenie do `api` powstaje
dopiero na odświeżenie danych. **Nie implementować.**

Poboczna obserwacja z waterfalla (`kat_typ__mobile`): 3 żądania **CORS preflight** `OPTIONS` do
`api.reklamap.pl` (`/api/silos`, `/api/listings`, `/api/listings/map-pins`), 290–303 → 418 ms.
To ~115 ms dodatkowej rundy, ale poza ścieżką LCP — nie warto ruszać (i to ten sam preflight, który
w VII 2026 obrywał od WAF-a, pamięć `cors_render_ip_block`).

---

### 3.3 „Brak `preconnect` do serwera kafli OSM" → **OBALONA jako preconnect, ale znalazłem coś innego**

`*.tile.openstreetmap.org` **nie pojawia się** wśród kandydatów do preconnectu w żadnym z 12 przebiegów.
Na home kafle są głęboko pod foldem (`image-delivery-insight` → boundingRect `top: 6515 px`).

**ALE:** na **desktopowych stronach kategorii element LCP to kafel mapy OSM**:
- `kat_typ__desktop`: LCP = `<img src="https://b.tile.openstreetmap.org/6/35/20.png" class="leaflet-tile">`, LCP 3410 ms
- `kat_miasto__desktop`: LCP = `https://a.tile.openstreetmap.org/12/2237/1381.png`, LCP 3050 ms

Waga kafli: home 980–1021 KB, kategorie 224–558 KB na wejście. `image-delivery-insight` (home mobile):
7 kafli po 38–52 KB, łącznie 332 KiB do odzyskania („Using a modern image format (WebP, AVIF)… could improve").
Kafle to PNG z zewnętrznego serwera — **nie mamy nad nimi kontroli**, więc jedyny lever to
**nie inicjalizować Leafleta nad foldem** (leniwy init na `IntersectionObserver` lub na klik „pokaż mapę").
Dodatkowo `leaflet-vendor` to 188 KB JS + 17 KB CSS, z czego `unused-javascript` odzyskuje 30–44 KB.
To osobne zadanie (effort M), nie preconnect.

---

### 3.4 „Lazy-loading na obrazie LCP" → **POTWIERDZONA, ale tylko dla stron kategorii na mobile**

`lcp-discovery-insight`, flaga `eagerlyLoaded` (per przebieg):

| strona | element LCP | `eagerlyLoaded` | `priorityHinted` | wynik |
|---|---|---|---|---|
| **kat_typ mobile** | `<img src="api.reklamap.pl/storage/…wJrIjKB….webp">` (1. kafel) | **FALSE** ❌ | FALSE ❌ | **0** |
| home mobile/desktop | `div.hero-image` (CSS background `banner-RDprR53l.webp`) | TRUE ✅ | FALSE ❌ | 0 |
| blog mobile/desktop | `div.hero-section` (gradient, bez obrazu) | TRUE ✅ | FALSE ❌ | 0 |
| **leaf996 mobile/desktop** | `<img …wd2E8iGd….webp>` | TRUE ✅ | **TRUE ✅** | **1** ✅ |
| leaf997 (bez prerenderu) | `<img …fLqV1mkR….webp>` | TRUE ✅ | TRUE ✅ | 0 (`requestDiscoverable: FALSE`) |

**Werdykt rozdzielony:**
- **POTWIERDZONA dla `/powierzchnie-reklamowe/*`**: `frontend/src/components/AdCard.vue:146-153` używa
  `<WebPImage>` **bez propa `eager`**, a `WebPImage.vue:36-37` mapuje to na `loading="lazy"` +
  `fetchpriority="auto"`. `AdGrid.vue:334` iteruje `v-for="ad in listings"` **bez indeksu**, więc nie da się
  dziś wyróżnić pierwszych kafli.
- **OBALONA dla leafa** — `AdDetailPage` już podaje `eager`, `lcp-discovery-insight` = **1/1** (jedyny zielony
  wynik tego audytu w całym zestawie). Nie ruszać.
- **OBALONA dla home** — hero jest tłem CSS, ładowanym eagerly; brakuje tylko priorytetu.

**Gotowy diff (effort S):**

```diff
--- a/frontend/src/components/AdGrid.vue
+++ b/frontend/src/components/AdGrid.vue
@@
         <AdCard
-          v-for="ad in listings"
+          v-for="(ad, i) in listings"
           :key="ad.id"
           :ad="ad"
+          :eager="i < 2"
           :view-mode="viewMode"
```

```diff
--- a/frontend/src/components/AdCard.vue
+++ b/frontend/src/components/AdCard.vue
@@ const props = defineProps<{
   ad: Advertisement
+  /** Pierwsze kafle nad foldem: eager + fetchpriority=high. Pierwszy kafel jest elementem
+   *  LCP na /powierzchnie-reklamowe/* (Lighthouse 13.4.1, 2026-07-25: eagerlyLoaded=false). */
+  eager?: boolean
   viewMode?: 'grid' | 'list'
@@
       <WebPImage
         v-if="ad.image_url"
         :src="ad.image_url"
         :alt="imageAlt"
+        :eager="props.eager"
         class="card-img"
         width="400"
         height="220"
       />
```

Dla home (`HeroBanner.vue:585-592`) tło CSS nie przyjmuje `fetchpriority`; najtaniej dołożyć w `index.html`:
```diff
+  <link rel="preload" as="image" href="/assets/banner-RDprR53l.webp" fetchpriority="high" />
```
— **ale tylko jeśli hash nazwy jest wstrzykiwany przy buildzie**; na sztywno zepsuje się przy następnym
buildzie. Alternatywa czysta: przerobić `hero-image` z tła CSS na `<img>` przez `WebPImage` z `eager`
(effort S, ale rusza efekt parallax na `transform: translateY`).

---

### 3.5 „Brak `srcset`" → **POTWIERDZONA, i to najdroższa pozycja w bajtach**

`WebPImage.vue:29-42` renderuje `<picture>` z jednym `<source :srcset="webpSrc">` — bez wariantów szerokości
i bez `sizes`. `image-delivery-insight` dla `kat_typ__mobile`: **„Est savings of 1 755 KiB"**.

| zasób | rozmiar | rzeczywiste wymiary | wyświetlany | do odzyskania |
|---|---|---|---|---|
| `assets/logo-text-DDHp-qKw.webp` | 465 KB | **8056×3303** | 359×147 | **464 KB** |
| `…wJrIjKB….webp` (kafel) | 452 KB | 1908×1081 | 559×315 | 423 KB |
| `…wd2E8iGd….webp` | 314 KB | 1474×835 | 650×315 | 281 KB |
| `…4kLRUM2X….webp` | 231 KB | 1908×1080 | 560×315 | 212 KB |
| `…fLqV1mkR….webp` | 201 KB | 1920×1085 | 557×417 | 178 KB |
| `…QlM1nHYa….webp` | 171 KB | 1067×604 | 591×315 | 141 KB |
| `assets/logo-Na9rtpht.webp` | 47 KB | **1024×1024** | 98×98 | 47 KB |

Pełny `srcset` dla zdjęć ogłoszeń wymaga **pipeline'u miniatur po stronie backendu** (`StorageController`
generuje dziś tylko `.webp` w oryginalnym rozmiarze) → **effort L**. Ale **dwa logo to czysty asset,
effort XS** — patrz #4 poniżej.

---

## 4. ZNALEZISKO #2 (nowe, effort XS, największy stosunek zysku do pracy): logo 8056×3303 / 465 KB na każdej stronie

`frontend/src/components/AppHeader.vue:109` i `AppFooter.vue:3` ładują `assets/logo-text.webp` —
plik **8056×3303 px, 465 KB** — a `AppHeader.vue:1173-1176` chowa go na mobile:
```css
@media (max-width: 480px) { .logo-image--full { display: none; } }
```
**Chrome i tak go pobiera** (`display:none` nie anuluje `src`). Potwierdzone w waterfallu **każdego z 12
przebiegów**, w tym mobilnych: `https://reklamap.pl/assets/logo-text-DDHp-qKw.webp` — **465 KB, priorytet High**,
131→440 ms — czyli konkuruje o pasmo z obrazem LCP. Drugie logo `logo-Na9rtpht.webp` to 47 KB przy
1024×1024 dla slotu 98×98.

Udział w wadze strony: **465 KB z 2230 KB na blogu = 21%**; z 3748 KB na kategorii = 12%.

**Rekomendacja (effort XS, zero ryzyka SEO):**
1. Przegenerować `frontend/src/assets/logo-text.webp` do **~800×328** (2× slotu 359×147) → oczekiwane 15–30 KB.
2. Przegenerować `logo.webp` do 224×224 (2× z 98×98) → ~5 KB.
3. Docelowo zamienić dwa `<img>` z `display:none` na jeden `<picture>` z `<source media="(max-width:480px)">`,
   żeby mobile w ogóle nie żądało wersji szerokiej.

**Zysk: ~500 KB na KAŻDE wejście na KAŻDĄ podstronę**, bez zmian w kodzie renderującym.

---

## 5. ZNALEZISKO #3 (nowe): Google Fonts przez `@import` — łańcuchowy render-block 791–831 ms

`frontend/src/style.css:1`:
```css
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
```
Po buildzie ląduje na początku `dist/css/index-B3KXQ6Vz.css` (zweryfikowane: pierwsze 80 bajtów pliku), co
tworzy **łańcuch render-blocking CSS → CSS → woff2**: przeglądarka musi najpierw pobrać 19,5 KB własnego CSS,
dopiero potem odkrywa arkusz Google.

Pomiar `render-blocking-insight`: `fonts.googleapis.com/css2` = **810–831 ms** na mobile (home 814,
kat_typ 831, kat_miasto 810, blog 797, leaf996 791), 243–301 ms na desktopie. Fonty ważą 165 KB na każdej
stronie. Do tego `font-display-insight` (home mobile): dodatkowe 30 ms na Roboto z reCAPTCHA.

`index.html:62-63` robi `preconnect`/`dns-prefetch` do **`fonts.googleapis.com`**, ale **nie do
`fonts.gstatic.com`** — a to `fonts.gstatic.com` serwuje właściwe pliki `.woff2` i to **jego** Lighthouse
wskazuje jako kandydata do preconnectu (est. LCP savings **153–172 ms**).

**Gotowy diff (effort XS):**
```diff
--- a/frontend/src/style.css
+++ b/frontend/src/style.css
-@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
+/* Arkusz Inter przeniesiony do index.html jako <link> — jako @import tworzył łańcuch
+   render-blocking CSS→CSS→woff2 kosztujący 791–831 ms na mobile (Lighthouse, 2026-07-25). */
```
```diff
--- a/frontend/index.html
+++ b/frontend/index.html
   <link rel="preconnect" href="https://fonts.googleapis.com" />
+  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
   <link rel="dns-prefetch" href="https://fonts.googleapis.com" />
+  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
```
Wariant docelowy (effort S, większy zysk): **self-host** dwóch plików `.woff2` (`UcC73Fwr…` 47 KB + 83 KB)
w `public/fonts/` + `@font-face` z `font-display: swap` — usuwa całe trzecie origin z krytycznej ścieżki.

---

## 6. ZNALEZISKO #4 (poza CWV, wyszło z pomiaru): ogłoszenia dodane po deployu frontu dostają `noindex`

Wykryte przy dobieraniu leafa do pomiaru. `https://api.reklamap.pl/api/listings` zwraca aktywne ogłoszenia
**997** i **998**; `https://reklamap.pl/sitemap.xml` ma **983 `<loc>`, w tym 825 leafów, max id = 996**
(rozmiar 319 282 B — identyczny z lokalnym `frontend/dist/sitemap.xml` z 13.07, czyli sitemap pochodzi
z ostatniego deployu frontu, 12 dni temu).

Skutek zmierzony `curl`-em:

| URL | bajty | `<meta robots>` | `__INITIAL_STATE__` | `<title>` |
|---|---|---|---|---|
| `…/tablica-reklamowa-…-996` (w sitemapie) | 69 264 | `index, follow` | **jest** | „Billboardy Pawłowice, Katowicka – wynajem \| ReklaMap" |
| `…/dwustronny-billboard-…-997` (poza sitemapą) | **7 929** | **`noindex, follow`** | **brak** | „Wynajem powierzchni reklamowych w Polsce \| ReklaMap" (generyczny) |

To działa **zgodnie z projektem** (`spa-fallback.html` dla tras bez prerenderu, `SEO_TECH_AUDIT.md:23`), ale
konsekwencja jest systemowa: **każde nowe ogłoszenie jest `noindex` z generycznym tytułem aż do następnego
deployu frontu**. Dziś dotyczy 2 z 827 ogłoszeń, ale skaluje się z tempem pozyskiwania podaży — a podaż
to obecna faza projektu. Widać to też w LCP: leaf 997 ma `requestDiscoverable: FALSE` i LCP 14 248 ms vs
10 875 ms dla prerenderowanego 996.

**Rekomendacja:** to decyzja procesu, nie kodu — albo cron przebudowujący prerender (np. nocny
`deploy.sh` gdy przybyły ogłoszenia), albo świadome przyjęcie opóźnienia. **Nie proponuję zmiany bez decyzji
foundera** (dotyka niezmienników deployu z `CLAUDE.md`).

---

## 7. Czy CWV to wąskie gardło przy CTR 0,29% na kategoriach? **NIE. To problem pozycji.**

Trzy niezależne argumenty, wszystkie z liczbami:

### (a) CTR na tej stronie jest funkcją pozycji — i to niemal idealnie monotonicznie

Przeliczone z `reklamap-os/stats/imports/gsc-2026-07-25/api/q3m__page.json` (3 mies., 26.04–25.07):

| segment | kliki | wyśw. | CTR | **poz. średnia (ważona)** | URL-i |
|---|---|---|---|---|---|
| hub `/powierzchnie-reklamowe` | 0 | 331 | 0,00% | **47,4** | 1 |
| **kategorie** | 11 | 3 758 | **0,29%** | **34,4** | 29 |
| kombinacje typ×miasto | 14 | 3 284 | 0,43% | 30,6 | 59 |
| blog | 8 | 1 775 | 0,45% | 26,3 | 18 |
| leafy | 11 | 538 | 2,04% | 16,8 | 59 |
| home (brand) | 112 | 634 | 17,67% | 19,3 | 1 |

CTR rośnie monotonicznie wraz z pozycją: 47,4→0%, 34,4→0,29%, 30,6→0,43%, 26,3→0,45%, 16,8→2,04%.
**0,29% przy pozycji 34,4 to dokładnie to, czego oczekuje krzywa CTR dla trzeciej strony wyników.**
Nie ma tu żadnej anomalii do wyjaśnienia wydajnością.

Mechanicznie: **CWV nie wchodzi do snippetu w SERP-ie**. Żadna zmiana LCP/CLS/INP nie zmienia CTR przy
ustalonej pozycji. Wpływ CWV na *ranking* (Page Experience) jest słabym sygnałem rozstrzygającym
i nie przenosi strony z pozycji 34 na 5.

### (b) Sygnał Page Experience liczy się z danych POLOWYCH, których ta domena prawdopodobnie nie ma

GA4 (3 mies., `ga4-2026-07-25/kanaly.json`, wg briefingu): Direct 467 + Organic 231 + Email 45 + Referral 15
+ Social 7 = **~765 sesji / 3 miesiące ≈ 8,5 sesji dziennie**. CrUX wymaga progu próby, żeby w ogóle
opublikować rekord dla originu. **Nie mam twardego potwierdzenia** (PSI 429, CrUX 403 — patrz sekcja 0), ale
przy tym wolumenie rekord CrUX dla `reklamap.pl` jest wysoce nieprawdopodobny → **sygnał CWV najpewniej nie
jest dla tej domeny w ogóle liczony**. Founder może to sprawdzić w 30 s: GSC → Core Web Vitals; jeśli widzi
„Nie ma wystarczających danych", sprawa zamknięta.

### (c) 89% wyświetleń przychodzi z DESKTOPU, gdzie wyniki są dwa razy lepsze

`q3m__device.json`: DESKTOP **9 129 wyśw.** (89,2%), poz. 33,1, CTR 0,64%; MOBILE **1 101** (10,8%),
poz. 16,7, CTR 9,90%; TABLET 3. Mobilne kliki (109 z 179) to praktycznie ruch brandowy (brand `reklamap`
= 110 klików wg briefingu). Czyli **odkrywanie non-brand dzieje się na desktopie**, gdzie perf = 56–76
i LCP 2,4–3,4 s. Priorytet „mobile first" z briefu jest w przypadku widoczności w Google odwrócony.

### Gdzie CWV JEDNAK ma znaczenie — i to jest prawdziwy argument za naprawami

Nie w rankingu, tylko w **konwersji już pozyskanego ruchu**:
- Strona zwija się do „nagłówek + stopka" na **~550 ms (szybkie łącze) do 13,7 s (slow-4G + 4× CPU)** —
  to nie jest subtelna metryka, to widoczna dla użytkownika pusta strona (sekcja 2).
- GA4 (briefing, ust. 12): `view_item` 255 zdarzeń / 72 userów → `contact_phone_click` **7 userów (~10%)`;
  `add_listing_start` 56 → `add_listing_success` 31 (**55%**). Oba lejki są wrażliwe na to, czy strona
  wygląda na zepsutą w pierwszej sekundzie.
- Ruch Direct (467 sesji) to **osobiste cold calle foundera** (pamięć `project_traffic_source_direct_coldcalls`).
  Właściciel nośnika, któremu founder właśnie powiedział „proszę wejść na reklamap.pl", **ogląda tę stronę
  na telefonie w trakcie rozmowy**. To jest realna cena znaleziska #1 — i jedyne uzasadnienie, którego
  potrzebują naprawy XS/S.

**Jedno zdanie wprost:** wydajność serwera jest bardzo dobra (TTFB 16–21 ms), obciążenie głównego wątku jest
w normie (TBT 0–257 ms), a wynik Lighthouse 40–76 nie jest przyczyną CTR 0,29%. Jedyne, co jest realnie
zepsute, to **CLS 0,88 z kasowania prerenderu** i **~1,3 MB zbędnych bajtów na wejście** — i naprawiać to
należy dla użytkowników i dla lejka podaży, **nie licząc na ruch z Google w zamian**.

---

## 8. Kolejność prac (koszt rosnąco, zysk malejąco)

| # | Zadanie | Plik | Effort | Zmierzony zysk |
|---|---|---|---|---|
| 1 | Przegenerować `logo-text.webp` (8056×3303 → ~800×328) i `logo.webp` (1024→224) | `frontend/src/assets/` | **XS** | −~500 KB na każde wejście |
| 2 | Google Fonts: `@import` → `<link>` + `preconnect fonts.gstatic.com` | `src/style.css:1`, `index.html:62` | **XS** | −791…831 ms render-block (mobile) |
| 3 | `router.isReady()` przed `mount()` | `src/main.ts:43` | **XS** kod / **M** weryfikacja | CLS 0,88 → ~0,06 |
| 4 | `eager` na 2 pierwszych kaflach | `AdGrid.vue:334`, `AdCard.vue:11,146` | **S** | LCP kategorii mobile: element LCP przestaje być lazy |
| 5 | reCAPTCHA na żądanie | `index.html:148`, `recaptchaService.ts` | **M** | −852 KB, −793…918 ms, −429 ms CPU |
| 6 | Leniwy init Leafleta (mapa poza pierwszym renderem) | `ListingsPage.vue:1017` | **M** | desktop kategorii: LCP przestaje być kaflem OSM; −224…1021 KB |
| 7 | `srcset` + miniatury po stronie backendu | `WebPImage.vue`, `StorageController` | **L** | −1 755 KiB na kategorii mobile |
| — | preconnect `api.reklamap.pl` / OSM | — | — | **NIE ROBIĆ** — obalone pomiarem |

Pozycje 1–4 to łącznie kilka godzin i zamykają wszystko, co jest realnie czerwone.
Pozycja 3 wymaga pełnej ścieżki weryfikacji SEO (tripwire + GSC Live Test) — nie wchodzi bez tego na prod.
