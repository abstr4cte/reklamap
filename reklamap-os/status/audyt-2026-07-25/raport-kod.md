# Raport cząstkowy — Architekt SEO / senior dev (2026-07-25)

Wszystko poniżej zweryfikowane **na żywym prodzie** (`api.reklamap.pl`, `reklamap.pl`) i w kodzie.
Fix filtra województwa **napisany, uruchomiony i przetestowany** w izolowanym worktree
(`git worktree`, usunięty po weryfikacji — **repo główne nietknięte**, `git status` czysty).

Artefakty:
- `/home/dev/.cache/tmp/claude-1000/-var-www-html-reklamap/c5eba6f1-6c3b-4328-8daf-c28ac65521ea/scratchpad/fix-region.patch` — kompletny patch (6 plików, +314/−8), `git apply`-owalny na `master@73ce63b`
- `.../scratchpad/RegionCanonicalizer.php`, `.../scratchpad/CanonizeAdvertisementRegions.php` — nowe pliki
- `.../scratchpad/prod_ads.json` — pełny zrzut 827 ogłoszeń z prod API (do rachunków)

---

## 1. BUG FILTRA WOJEWÓDZTWA — potwierdzony, wyceniony, naprawiony

### Co się rozjeżdża (dokładnie)

| Warstwa | Wartość |
|---|---|
| Front — słownik | `frontend/src/data/polishLocations.json` → `voivodeships[].id` = **ASCII** (`dolnoslaskie`, `slaskie`, `warminsko-mazurskie`) |
| Front — wybór | `HeroBanner.vue:50-56` buduje listę z `v.id`; `useSearchStore.ts:490-492` zapisuje `targetFilters.region = matchingRegion?.id` |
| Front — wysyłka | `useSearchStore.ts:143` → `params.region = f.region` (czyli ASCII-id) |
| Backend | `AdvertisementController.php:256-257` → `where('region', $request->input('region'))` — **exact match** |
| Baza (prod) | `address.state` z Nominatim (`AddAdPage.vue:663,728`) — **dwa formaty naraz** |
| Front — filtr klienta | `useSearchStore.ts:735` → `ad.region === f.region` — **ta sama asymetria po drugiej stronie** |

Rozkład `region` na prodzie (827 ogłoszeń, `api.reklamap.pl/api/listings?per_page=200`, strony 1–5):

```
<NULL>                        480   (58,0%)
śląskie                       135     województwo mazowieckie        20
zachodniopomorskie             80     województwo śląskie            14
lubelskie                      32     województwo dolnośląskie       10
mazowieckie                    14     województwo zachodniopomorskie 10
małopolskie                     5     województwo wielkopolskie       9
                                      + 8 kolejnych „województwo X"  (12)
```

### Pomiar na żywym prod API (`X-App-Key` z `backend/.env`)

`GET /api/listings?region=<id>&per_page=1` → `total`, wszystkie 16 id z frontu:

```
dolnoslaskie=0   kujawsko-pomorskie=0  lubelskie=32   lubuskie=0
lodzkie=0        malopolskie=0         mazowieckie=14 opolskie=0
podkarpackie=0   podlaskie=0           pomorskie=0    slaskie=135
swietokrzyskie=0 warminsko-mazurskie=0 wielkopolskie=0 zachodniopomorskie=80
```

**13 z 16 województw zwraca 0.** Filtr sięga dziś **261 z 827 ogłoszeń (31,6%)**.

Ważna korekta do briefingu (poz. 1): `region=slaskie` **działa** (135), bo kolacja MySQL 8
`utf8mb4_0900_ai_ci` jest accent-insensitive (`ś`=`s`, `ą`=`a`). Ale **`ł` ma własną wagę
podstawową** — dlatego `malopolskie` → 0 przy 5 rekordach `małopolskie`. Czyli bug nie jest
„wszystko zero", tylko **losowo działa dla 3 województw** — najgorszy możliwy wariant, bo
przy ręcznym teście („Śląskie działa!") wygląda na sprawny.

Druga korekta: `region=dolnośląskie` → 0, `region=województwo dolnośląskie` → 10.
Prefiks „województwo " jest drugą, niezależną osią rozjazdu.

### Zasięg poza wyszukiwarką

- `SearchAlertService.php:25-26` — `whereNull('region')->orWhere('region', $ad->region)`.
  Alert zapisany z ASCII-id **nigdy** nie dopasuje się do ogłoszenia z `województwo dolnośląskie`
  ani do 480 ogłoszeń z `region=NULL`. Liczba alertów z ustawionym regionem na prodzie: **brak danych**
  (endpoint niepubliczny). GA4: `search_alert_create` = 1 zdarzenie / 1 user w 3 mies.
- **SEO: zerowy wpływ bezpośredni.** W `router.ts` nie ma trasy `/wojewodztwo/*`, a generator
  sitemapy (`backend/routes/web.php:13-207`) nie emituje URL-i województw. To bug **user-facing**
  (funnel), nie indeksacyjny — inaczej niż `city_strict` z 07.07.
- **Telemetria: brak danych.** `analytics.filterUsed` (`utils/analytics.ts:53`) nie jest wołane
  ani razu (patrz K-12), więc nie da się policzyć, ilu userów kliknęło w martwy filtr.

### Dlaczego to przeżyło do dziś (root cause procesowy)

Lokalna baza dev ma **inne dane niż prod**: `php artisan tinker` na tej maszynie zwraca
`dolnoslaskie=5, slaskie=141, mazowieckie=25` — same **ASCII-id**. Na lokalnym dev filtr działa
dla 12 z 14 wartości. Prod ma format z Nominatim. Klasyczne „u mnie działa" — dokładnie ten
scenariusz, przed którym ostrzega CLAUDE.md („Baza lokalna ≠ produkcja").

### FIX (zweryfikowany: `php artisan test --filter='region_filter|city_strict'` → 4 passed, pełny front `143/143`)

**backend/app/Http/Controllers/AdvertisementController.php**

```diff
@@ -197,19 +197,45 @@ private function foldPolish(string $s): string
     }
 
     /**
-     * Wyrażenie SQL zwijające kolumnę `city` do lowercase ASCII (zagnieżdżone REPLACE + LOWER).
-     * Działa na MySQL (prod) i SQLite (testy). Uwaga: wyłącza indeks na `city` — akceptowalne przy
-     * obecnej skali; przy wzroście rozważyć znormalizowaną kolumnę generowaną + indeks.
+     * Urzędowy prefiks w kolumnie `region`. Nominatim (`address.state`) zwraca raz
+     * „województwo śląskie", raz „śląskie" — oba formaty siedzą w bazie, a front wysyła
+     * ASCII-id ze słownika (`polishLocations.json`: „slaskie"). Fold porównuje obie formy.
      */
-    private function cityFoldedSql(): string
+    private const REGION_PREFIX = 'wojewodztwo ';
+
+    /**
+     * Wyrażenie SQL zwijające kolumnę tekstową do lowercase ASCII (zagnieżdżone REPLACE + LOWER).
+     * Działa na MySQL (prod) i SQLite (testy). Uwaga: wyłącza indeks na kolumnie — akceptowalne
+     * przy obecnej skali; przy wzroście rozważyć znormalizowaną kolumnę generowaną + indeks.
+     */
+    private function foldedSql(string $column): string
     {
-        $expr = 'city';
+        $expr = $column;
         foreach (self::PL_FOLD as $from => $to) {
             $expr = "REPLACE($expr, '$from', '$to')";
         }
         return "LOWER($expr)";
     }
 
+    private function cityFoldedSql(): string
+    {
+        return $this->foldedSql('city');
+    }
+
+    /**
+     * Fold nazwy województwa: diakrytyki → ASCII, myślnik → spacja, lowercase, zdjęty prefiks
+     * „województwo ". Dzięki temu „dolnoslaskie" (id z frontu), „Dolnośląskie" (label)
+     * i „województwo dolnośląskie" (zapis z Nominatim w bazie) dają tę samą wartość.
+     */
+    private function foldRegion(string $s): string
+    {
+        $folded = trim(preg_replace('/\s+/', ' ', $this->foldPolish($s)) ?? '');
+
+        return str_starts_with($folded, self::REGION_PREFIX)
+            ? trim(substr($folded, strlen(self::REGION_PREFIX)))
+            : $folded;
+    }
+
@@ -253,8 +279,16 @@ private function buildFilteredQuery(Request $request)
         // --- Region filter ---
+        // Ta sama klasa błędu co city_strict (naprawa 2026-07-07): front wysyła ASCII-id
+        // województwa z `polishLocations.json` („dolnoslaskie"), a w bazie leży to, co zwrócił
+        // Nominatim — raz „śląskie", raz „województwo dolnośląskie". Exact match `where('region', ?)`
+        // dawał 0 ofert dla 13 z 16 województw (prod 2026-07-25).
+        // Foldujemy obie strony (diakrytyki + myślnik) i porównujemy z prefiksem i bez.
         if ($request->filled('region')) {
-            $query->where('region', $request->input('region'));
+            $regionExpr = $this->foldedSql('region');
+            $folded = $this->foldRegion((string) $request->input('region'));
+            $query->whereRaw("($regionExpr = ? OR $regionExpr = ?)", [$folded, self::REGION_PREFIX . $folded]);
         }
```

Uwaga projektowa: prefiks zdejmowany **w PHP**, a w SQL porównanie z dwoma wariantami
(`= ? OR = ?`). Powód: `TRIM(LEADING 'x' FROM y)` jest MySQL-only — SQLite (testy) trimuje
zbiory znaków, nie prefiksy. Wariant dwuporównaniowy jest przenośny.

**frontend/src/utils/filterUtils.ts** (symetria — bez tego zostaje asymetria jak przy `city_strict` 07.07)

```diff
+/**
+ * Normalizacja nazwy WOJEWÓDZTWA do porównań — symetryczna z backendem
+ * (AdvertisementController::foldRegion).
+ */
+export function normalizeRegionMatch(text: string): string {
+  return normalizeCityMatch(text).replace(/^(wojewodztwo|woj\.?)\s+/, '').trim()
+}
```

**frontend/src/stores/useSearchStore.ts:735**

```diff
-      if (f.region) filtered = filtered.filter(ad => ad.region === f.region)
+      if (f.region) {
+        const regionQuery = normalizeRegionMatch(f.region)
+        filtered = filtered.filter(ad => normalizeRegionMatch(ad.region || '') === regionQuery)
+      }
```

**backend/tests/Feature/AdvertisementApiTest.php** (2 testy regresyjne, wzorowane na `test_city_strict_*`)

```php
public function test_region_filter_matches_ascii_id_diacritics_and_wojewodztwo_prefix(): void
{
    Advertisement::factory()->create(['region' => 'województwo dolnośląskie', 'is_active' => true, 'status' => 'active']);
    Advertisement::factory()->create(['region' => 'dolnośląskie', 'is_active' => true, 'status' => 'active']);
    Advertisement::factory()->create(['region' => 'śląskie', 'is_active' => true, 'status' => 'active']);

    $ascii = $this->getJson('/api/listings?region=dolnoslaskie', $this->appKeyHeaders());
    $ascii->assertStatus(200);
    $this->assertSame(2, $ascii->json('total'));

    $label = $this->getJson('/api/listings?' . http_build_query(['region' => 'Dolnośląskie']), $this->appKeyHeaders());
    $this->assertSame(2, $label->json('total'));

    $full = $this->getJson('/api/listings?' . http_build_query(['region' => 'województwo dolnośląskie']), $this->appKeyHeaders());
    $this->assertSame(2, $full->json('total'));

    $other = $this->getJson('/api/listings?region=slaskie', $this->appKeyHeaders());
    $this->assertSame(1, $other->json('total'), 'Śląskie nie może łapać dolnośląskiego.');
}

public function test_region_filter_matches_hyphenated_voivodeship(): void
{
    Advertisement::factory()->create(['region' => 'województwo warmińsko-mazurskie', 'is_active' => true, 'status' => 'active']);

    $res = $this->getJson('/api/listings?region=warminsko-mazurskie', $this->appKeyHeaders());
    $res->assertStatus(200);
    $this->assertSame(1, $res->json('total'));
}
```

### Efekt fixa policzony na realnych danych prod (symulacja na `prod_ads.json`)

| id | dziś | po fixie | po fixie + backfill (szac.) |
|---|---:|---:|---|
| dolnoslaskie | **0** | 10 | **~407** (bbox 50,10–51,80 N / 14,80–17,90 E) |
| slaskie | 135 | 149 | + część z 480 pustych |
| zachodniopomorskie | 80 | 90 | j.w. |
| mazowieckie | 14 | 34 | j.w. |
| malopolskie | **0** | 9 | j.w. |
| wielkopolskie | **0** | 9 | j.w. |
| lodzkie / lubuskie / warminsko-mazurskie / podlaskie / podkarpackie / swietokrzyskie | **0** | 3/2/4/1/1/1 | j.w. |
| **RAZEM** | **261 (31,6%)** | **347 (42,0%)** | **do 827 (100%)** |

Czyli: **sam fix kodu odblokowuje 13 województw, ale podnosi zasięg tylko o 86 ogłoszeń.**
Bez kanonizacji danych 58% bazy dalej jest niewidoczne dla filtra.

### Kanonizacja `region` — TAK, potrzebna. Gotowa komenda.

Źródłem prawdy **muszą być współrzędne, nie nazwa miasta** — dowód: `Ząbkowice` (7 nośników,
50.379/19.280) to dzielnica **Dąbrowy Górniczej (śląskie)**, oddalona o **176 km** od
`Ząbkowice Śląskie` (31 nośników, dolnośląskie). Reverse-geocode Nominatim dla tych współrzędnych
zwraca `{'city': 'Dąbrowa Górnicza', 'state': 'województwo śląskie'}` — sprawdzone. Słownik miast
przypisałby je błędnie.

Wszystkie 827 ogłoszeń mają `latitude`/`longitude` ≠ 0 → **100% pokrycia**.
Dedup po siatce 0,1° (~11 km): 480 pustych rekordów → **71 zapytań do Nominatim** (~80 s przy
1 req/s). Cała baza (827) → 141 zapytań.

Pliki: `scratchpad/RegionCanonicalizer.php` (czysta klasa, bez I/O, do reużycia w kontrolerze,
komendzie i przyszłym Form Requeście) + `scratchpad/CanonizeAdvertisementRegions.php`.

```
php artisan region:canonize                     # DRY-RUN (domyślnie) — sam raport
php artisan region:canonize --apply             # faza A: kanonizacja istniejących, BEZ sieci
php artisan region:canonize --apply --geocode   # + faza B: uzupełnienie pustych z lat/lng
php artisan region:canonize --geocode --limit=3 # próbka przed pełnym przebiegiem
```

Zabezpieczenia wpisane w komendę:
- **dry-run domyślnie**, zapis tylko z `--apply`;
- **UPDATE W MIEJSCU** przez query builder (`DB::table(...)->whereIn('id',...)->update([...])`) —
  nigdy delete+create; `id`, `slug`, URL i `advertisement_daily_stats` nietknięte (zasada z CLAUDE.md);
- **`updated_at` NIE jest ruszane** — query builder go nie dotyka. To celowe: `lastmod` w sitemapie
  bierze się z `updated_at` (`routes/web.php:197`), a przestawienie go na 480 URL-ach naraz wygląda
  dla Google jak masowa podmiana treści bez zmiany treści (dokładnie ten błąd zaufania, o którym
  mówi komentarz w `web.php:21-22`);
- **nierozpoznane wartości zostają bez zmian** (`canonicalize()` zwraca `null` → pomijamy) — lepiej
  puste niż źle przypisane;
- transakcja per grupa, `usleep(1_100_000)` zgodnie z polityką Nominatim, UA jak w
  `ImportReklamaAi.php:198`.

Smoke test na lokalnej bazie: faza A wykryła `"małopolskie" (5) → "malopolskie"`;
faza B z `--limit=3` rozwiązała 3 grupy = 64 rekordy. Komenda działa.

**Kolejność wdrożenia:** (1) patch kodu + testy → (2) `region:canonize` dry-run na prodzie i przegląd
raportu → (3) `--apply` → (4) `--apply --geocode`. Fold w kontrolerze zostaje **na stałe** (defense
in depth: stare i obce zapisy dalej się dopasują).

**Domknięcie na przyszłość (osobno, S):** kanonizacja przy ZAPISIE — `RegionCanonicalizer::canonicalize()`
w `store()`/`update()` (najlepiej w Form Requeście, zgodnie z „Senior Dev Standards"), żeby nowy
Nominatim-owy format nie wracał do bazy.

---

## 2. MARTWY `contact_email_click` + BRAK ścieżki mailowej do wystawcy

### Potwierdzenie

- `frontend/src/utils/analytics.ts:29-30` — `clickEmail` zdefiniowane, **0 wywołań** w całym froncie
  (`grep -rn "analytics.clickEmail" --include=*.vue --include=*.ts src` → tylko definicja).
- **Jedyne `mailto:` na całym serwisie** (`grep -rn "mailto:" src`): `ContactPage.vue:138`,
  `AppFooter.vue:56`, `RegulaminPage.vue:222`, `FaqPage.vue:362` — **wszystkie cztery na
  `kontakt@reklamap.pl`**. Zero `mailto:` do wystawcy.
- GA4 (3 mies., `zdarzenia.json`): `contact_phone_click` 39/7 userów, `contact_form_submit` **4/3**,
  `contact_email_click` **nie istnieje jako zdarzenie**.

### Co realnie widzi reklamodawca na stronie ogłoszenia (`AdSidebar.vue:73-98`)

1. Przycisk **„Pokaż numer"** → drugi klik = `tel:` (`AdDetailPage.vue:69-92`).
   Warunek: `ad.phone && ad.contact_preference !== 'form'`.
2. Przycisk **„Wyślij wiadomość"** → scroll do `AdContactForm.vue` (2 pola: e-mail + treść ≥10 znaków).
   Warunek: `ad.contact_preference !== 'phone'`.

Nic więcej. **Zero informacji, KTO jest wystawcą** — model `Advertisement` nie ma pola z nazwą
firmy/osoby (`$fillable`, `Advertisement.php:19-77`), `owner_email` jest w `$hidden`
(`Advertisement.php:126`) i **nie jest zwracany przez żaden publiczny endpoint** —
`show()` (`AdvertisementController.php:584`) odsłania tylko `phone` przez `makeVisible('phone')`.
Sprawdzone na prodzie: `GET /api/listings` nie zawiera `phone` ani `owner_email`;
`GET /api/listings/{id}` zawiera `phone` (39/40 losowych ogłoszeń **ma telefon**), `owner_email` nigdy.

### Trzy defekty spójności, które to wzmacniają

1. **`contact_preference = 'email'` u 683 z 827 ogłoszeń (82,6%)**, `both` 129, `phone` 15
   (policzone na `prod_ads.json`). 82,6% wystawców zadeklarowało **e-mail** jako preferowany kanał —
   a jedyna e-mailowa ścieżka to formularz, który w 3 miesiące odpalił **3 userów**.
2. **Warunek `ad.contact_preference !== 'form'` (`AdSidebar.vue:73`) jest martwy** — `AddAdPage.vue:104`
   dopuszcza tylko `'' | 'email' | 'phone' | 'both'`. Wartość `'form'` nie powstaje nigdy, więc
   telefon pokazuje się **zawsze**, gdy istnieje, także wystawcom z preferencją „email".
3. **Meta description obiecuje coś, czego strona nie dostarcza.** `AdDetailPage.vue:242` generuje na
   827 ogłoszeniach: *„…skontaktuj się **bezpośrednio z wystawcą** — bez prowizji i pośredników"*.
   Bezpośredniego kanału (adresu) nie ma. Zweryfikowane live na `…biala-podlaska-303`.

### Czy to tłumaczy zapytania na `kontakt@reklamap.pl`? TAK — łańcuch jest domknięty

`POST /api/listings/{id}/contact` (`AdvertisementController.php:1006-1049`) jest **architektonicznie
poprawny dla modelu „platforma, nie broker"**: `Mail::to($ad->owner_email)` + `replyTo` = nadawca
(`ContactAdvertisementOwner.php:38-44`), plus kopia potwierdzająca do nadawcy. Founder **nie jest**
w tej ścieżce.

Ale użytkownik, który chce napisać maila, na stronie ogłoszenia nie widzi żadnego adresu — jedyny
adres na całym serwisie to `kontakt@reklamap.pl` w stopce, obecnej na **każdej** podstronie
(`AppFooter.vue:56`). Zapytanie ofertowe na skrzynkę foundera to więc **przewidywalny wynik projektu
interfejsu**, nie przypadek. Sygnał porażki samoobsługi — dokładnie jak w briefingu.

### Rekomendacja (zgodna z „platforma, nie broker"; founder NIE w pętli)

Nie odsłaniać `owner_email` (`$hidden` to świadoma ochrona przed scrapingiem — klucz API jest w JS,
czyli praktycznie publiczny; komentarz `Advertisement.php:113-125`). Zamiast tego:

**(a) Trzeci przycisk „Napisz e-mail" obok „Pokaż numer" — ta sama mechanika dwóch kliknięć.**
Klik 1 odsłania **zamaskowany alias relay** (`ad-{id}@kontakt.reklamap.pl`), klik 2 = `mailto:` +
`analytics.clickEmail(...)` + `api.incrementEmailClicks(...)` (endpoint już istnieje, używa go
`AdContactForm.vue:65`). Relay przepisuje wiadomość na `owner_email` z `Reply-To` nadawcy — czyli
dokładnie to, co robi już `ContactAdvertisementOwner`, tylko wejściem jest zwykły mail, nie formularz.
Alias jest per-ogłoszenie i wygaszalny → scraping bazy maili wystawców pozostaje niemożliwy.
**Koszt: M** (potrzebny inbound routing na Hostido; przy strict DMARC — uwaga na `project_email_deliverability_hostido`).

**(b) Wariant tańszy, jeśli (a) jest za drogie: podnieść konwersję istniejącego formularza. Koszt: S.**
Formularz zbiera dziś tylko e-mail + treść. Dla zapytania kampanijnego brakuje: nazwy firmy, telefonu,
terminu (od–do) i liczby nośników — czyli tego, czego wystawca potrzebuje, żeby odpowiedzieć ofertą,
a nie pytaniem. Dodatkowo: podmienić martwy warunek `!== 'form'` na realny (respektować
`contact_preference='email'` → formularz pierwszy, telefon drugi) i **wywołać wreszcie `clickEmail`**,
żeby w ogóle mierzyć ten kanał.

**(c) Tożsamość wystawcy (Koszt: S, zależne od decyzji produktowej).** Bez pola „nazwa wystawcy"
strona ogłoszenia nie mówi, z kim się kontaktujesz. Przy transakcji B2B na kilka–kilkanaście tys. zł
to bariera zaufania — i prawdopodobnie część powodu, dla którego ludzie wolą napisać „do serwisu".
Wymaga nowego pola (procedura „Adding a New Field" z CLAUDE.md) — decyzja Biznesowego, nie moja.

---

## 3. Zapytania wieloobiektowe — ścieżki NIE MA (ale to nie było wąskie gardło tej sprawy)

Stan faktyczny:

| Element | Stan |
|---|---|
| Schowek/porównywarka | **jest** — `usePreferencesStore.ts` + trasa `/porownaj` (`router.ts:71`) |
| Limit | **max 5 ogłoszeń** (`usePreferencesStore.ts:63`) |
| Ograniczenie typu | **tylko ten sam typ** — `'Możesz porównywać tylko ogłoszenia tego samego typu'` (`usePreferencesStore.ts:74`) |
| CTA „zapytaj o wszystkie" na `/porownaj` | **BRAK** — `ComparisonPage.vue` ma wyłącznie: PDF, „wyczyść", „usuń", „wróć" (linie 335-471) |
| Ulubione | panel wysuwany `FavoritesPanel.vue`, **bez własnej trasy** → nie da się wysłać ani udostępnić linkiem |
| Endpoint zbiorczy w API | **BRAK** — `routes/api.php:43-44` ma tylko `listings/{id}/contact` (per-ogłoszenie) i `contact` (do foundera) |
| Telemetria schowka | `analytics.addToComparison` — **0 wywołań** → ile osób go używa: **brak danych** |

Klient chcący 6 nośników mieszanych typów w jednej dzielnicy **nie jest w stanie nawet zbudować
koszyka**, a gdyby zbudował — musiałby wysłać 6 osobnych formularzy po 2 pola każdy.

**Adwersaryjnie, dla Biznesowego:** w tej konkretnej sprawie (Warszawa Śródmieście) wąskim gardłem
był **brak podaży, nie brak formularza**. Na prodzie w Warszawie są **2 nośniki** (1 `active` banner
Al. KEN, 1 `reserved` billboard Połczyńska); w promieniu 15 km od Śródmieścia — **5** (4 billboardy
+ 1 baner). Zapytania kampanijnego na wiele nośników w Śródmieściu **nie dało się obsłużyć niezależnie
od UI**. Luka jest realna i warta zapisania w backlogu, ale **nie jest przyczyną utraty tego leada** —
i nie powinna wyprzedzić budowy podaży w miastach popytowych. Nie projektuję funkcji; szacunek
zakresu: koszyk bez limitu typu + trasa `/schowek` + jeden endpoint `POST /api/inquiries` rozsyłający
per-wystawca (jedna wiadomość na wystawcę, nie na nośnik) = **L**.

---

## 4. Duble miast — briefing poz. 9 jest w większości NIEPRAWDZIWY

Policzone na 827 ogłoszeniach z prod API; „odległość" = dystans między centroidami współrzędnych
ogłoszeń danej nazwy.

| Para | Nośniki | Odległość centroidów | Werdykt |
|---|---|---:|---|
| `Polanica Zdrój` / `Polanica-Zdrój` | 11 / 3 | **0,06 km** | ten sam byt — **już obsłużone** |
| `Wilków Wielki` / `Wilków wielki` | 4 / 1 | **0,60 km** | ten sam byt — **już obsłużone** |
| `Powodów Trzeci` / `Powoów Trzeci` | 3 / 1 | **identyczne lat/lng** (51.967455 / 19.101497) | **literówka — jedyny realny problem** |
| `Ząbkowice` / `Ząbkowice Śląskie` | 7 / 31 | **175,94 km** | **RÓŻNE MIASTA** — `Ząbkowice` to dzielnica Dąbrowy Górniczej (potwierdzone reverse-geocode Nominatim: `city: Dąbrowa Górnicza, state: województwo śląskie`) |
| `Duszniki` / `Duszniki-Zdrój` | 1 / 1 | **213,19 km** | **RÓŻNE MIASTA** |
| `Szklary` / `Szklary-Huta` | 2 / 3 | 43,88 km | RÓŻNE wsie |
| `Dzierżoniów` / `Dzierżoniów-Pieszyce` | 18 / 2 | 5,75 km | różne (sąsiednie miejscowości) |

Podaż realnie dzielona przez warianty zapisu: **19 nośników w 2 grupach** (Polanica 14, Wilków 5)
+ 4 na literówce = **23 z 827 (2,8%)**. Nie 5 par, jak zakładał briefing.

**Czy werdykt „kosmetyczne" z audytu 07.07 (poz. 11) nadal stoi? TAK — dla miast.** Sprawdzone
na żywej sitemapie (`reklamap.pl/sitemap.xml`, 983 `<loc>`): **0 duplikatów `<loc>`**, bo
`Str::slug` daje ten sam URL dla obu wariantów i dedup w `web.php:116-127` + `146-158` je scala.
Odczyt też scala — fold `city_strict` (PL_FOLD z `'-' => ' '`) łapie oba warianty. Kanibalizacji nie ma.

**Ale bug `region` NIE zmienia tego werdyktu — to inna klasa problemu.** Miasta mają fold przy odczycie
i dedup przy zapisie do sitemapy; `region` nie miał ani jednego, ani drugiego, i nie jest w URL-ach.
Wniosek: kanonizacja **miast** zostaje kosmetyczna, kanonizacja **regionu** jest konieczna (poz. 1).

**Bezpieczne rozwiązanie — tylko literówka, 1 rekord:**

```php
// jednorazowo, update W MIEJSCU, bez ruszania updated_at (lastmod w sitemapie)
DB::table('advertisements')->where('city', 'Powoów Trzeci')->update(['city' => 'Powodów Trzeci']);
```

Skutek: `powodow-trzeci` rośnie z 3 do 4 nośników (dalej ≥ progu thin, zostaje w sitemapie),
a sierocy URL `powoow-trzeci` (1 nośnik, poniżej progu, `noindex`, poza sitemapą) znika.
Zysk mały, ryzyko zerowe. **NIE robić** ślepego przepisywania nazw miast wg podobieństwa — na tych
danych zmergowałoby Ząbkowice z Ząbkowicami Śląskimi (176 km) i Duszniki z Dusznikami-Zdrój (213 km).
Jedyny bezpieczny automat to walidacja **nazwa ↔ współrzędne** przy zapisie, nie fuzzy-match nazw.

---

## 5. Otwarte pozycje z SEO_TECH_AUDIT.md — status na 2026-07-25 (weryfikacja live)

| # | Temat | Status | Dowód |
|---|---|---|---|
| **4** | near-duplikaty Big Group (identyczny title+desc) | 🔴 **OTWARTE, gorsze niż opisano** | **516/827 (62,4%)** ogłoszeń dzieli tytuł z innym (456 unikalnych tytułów); 356/827 dzieli opis. Trzy URL-e sprawdzone jako Googlebot (`…biala-podlaska-{299,302,303}`) mają **identyczny `<title>`, identyczny meta-description i identyczny `<h1>`**; rozmiary HTML 67 711 / 67 710 / 67 711 B. Fix #8 z 07.07 (ulica w tytule) **nie pomaga**, gdy 23 nośniki stoją przy tej samej ulicy („Celników Polskich"). Potrzebny dyskryminator: numer/kilometr/kierunek/strona. |
| **14** | szablonowe meta-desc + zła deklinacja miejscownika | 🟡 **OTWARTE** | W kodzie **nie ma żadnego helpera deklinacji** (`grep -rn "miejscownik\|declen\|locative" src` = 0 trafień). `AdDetailPage.vue:242` generuje `w ${newAd.city}` → live: *„Billboardy **w Biała Podlaska**"*. Strona miasta: *„138 ofert — Powierzchnie reklamowe **w Kłodzko**"*. Dotyczy **827 leafów + 118 stron kategorii = 945 URL-i**. Przy CTR kategorii **0,29%** (briefing) to tani lewar. |
| **16** | brak image-sitemap | ✅ **ZAMKNIĘTE** | `reklamap.pl/sitemap.xml`: **825 `<image:loc>`** przy 825 leafach. Kod: `routes/web.php:200-205`. Zdezaktualizowane. |
| **23** | geo-bucketing nośników | 🟡 **OTWARTE, teraz policzone** | **98 nośników (11,9%) leży w 78 miastach z <3 ofertami** → brak strony kategorii, poza sitemapą, `noindex`. Jednocześnie **339 z 827 (41%) leży w promieniu 30 km od miasta z top-20**: Katowice **140**, Koszalin 77, Wrocław 38, Poznań 34, Warszawa 13, Radom 13; w pasie 30–50 km kolejne 83. Zestawić z briefingiem poz. 7 (`reklama led poznań` 121 wyświetleń) — podaż aglomeracyjna istnieje, tylko nie ma URL-a, który by ją agregował. Decyzja produktowa (strona aglomeracji vs promień), nie czysto techniczna. |
| **15** | autor bloga „Admin" | 🟡 **OTWARTE** | Live `blog/poradniki/billboard-reklama`: `"author":{"@type":"Person","name":"Admin"}`. Przy 1 775 wyświetleń bloga w 3 mies. i deklarowanym E-E-A-T — sygnał ujemny. |
| **31** | `Offer` bez płaskiego `price` | 🟡 **OTWARTE** | Live JSON-LD leafa: `Offer` ma tylko `priceSpecification.UnitPriceSpecification` (`price: "950.00"`, `unitText: "za miesiąc"`), **bez `offers.price`**. Ryzyko utraty rich resultu — do potwierdzenia Rich Results Testem, nie zgaduję. |
| **32** | formularz `index` | ⚪ **PODWAŻAM — to nie defekt** | `/dodaj-powierzchnie-reklamowa` live: `robots = index, follow`, 1× `<h1>`, 41 147 B treści. To **landing pozyskania podaży**, a projekt jest w fazie budowy podaży. `noindex` odciąłby stronę, która ma sprzedawać wystawcom. Rekomendacja: **zostawić `index`**, zamknąć pozycję jako świadomą decyzję. |
| **33** | blog 2× `<h1>` | 🟡 **OTWARTE** | Live: dwa **identyczne** `<h1>` („Billboard reklama — cennik, formalności i efektywność") na tej samej stronie. Fix trywialny (drugi → `<h2>` albo `<p class="lead">`). |

---

## 6. Znaleziska poboczne (nie było w zakresie, ale blokują pracę)

**A. Suite testów jest CZERWONY na `master@73ce63b` — 5 testów pada.**
`cd backend && php artisan test` → **5 failed, 100 passed**. Zweryfikowane w głównym repo, nie tylko
w worktree (te same 5 przed i po moim patchu → mój patch niczego nie psuje).

| Test | Objaw | Przyczyna |
|---|---|---|
| `ManagementTest::can_send_management_link` (+3 pokrewne) | oczekiwane 200, jest **422** | `ManagementController.php:27-31` dołożył bramkę „brak ogłoszeń dla tego e-maila", a test nie tworzy ogłoszenia dla `owner@example.com`. **Test nieaktualny, kod OK.** |
| `SearchAlertTest::unsubscribing_with_invalid_token_returns_404` | oczekiwane 404, jest **302** | `SearchAlertController.php:60-62` zmieniono na `redirect(frontend_url . '/?blad=alert-token')`. **Test nieaktualny, kod OK.** |

CLAUDE.md deklaruje: *„Pre-commit hook: Runs all tests before every commit"*. Skoro `master` jest
czerwony, hook jest albo omijany (`--no-verify`), albo nie działa — czyli **sieć bezpieczeństwa dla
wszystkich powyższych fixów jest wyłączona**. Naprawa: dociągnąć 5 testów do obecnego kontraktu API
(XS, ~20 min).

**B. 6 z 14 helperów GA4 to martwy kod** (`grep -rn "analytics.<nazwa>"` po całym `frontend/src`,
z pominięciem definicji):

| Helper | Wywołania | Konsekwencja |
|---|---:|---|
| `clickEmail` | **0** | brak pomiaru kanału e-mail (poz. 2) |
| `search` | **0** | nie wiadomo, czego ludzie szukają w wyszukiwarce serwisu |
| `filterUsed` | **0** | **nie da się zmierzyć, ilu userów trafiło w zepsuty filtr województwa** |
| `newsletterSubscribe` | **0** | brak pomiaru zapisów |
| `mainContactFormSubmit` | **0** | brak pomiaru formularza `/kontakt` — czyli tej ścieżki, którą przyszły zapytania ofertowe |
| `addToComparison` | **0** | nie wiadomo, czy schowek ma jakichkolwiek użytkowników (poz. 3) |

Potwierdzenie w danych: `ga4-2026-07-25/zdarzenia.json` (3 mies.) — brak `contact_email_click`,
`filter_used`, `search`, `newsletter_subscribe`, `main_contact_submit`, `add_to_comparison`.
Widoczne `view_search_results` (20/6) to automatyczne zdarzenie GA4 (enhanced measurement), nie nasze.
Wpięcie 6 wywołań to **XS** i domyka trzy „brak danych" z tego raportu.
