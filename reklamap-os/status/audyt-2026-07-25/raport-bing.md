# Kanał Bing / Copilot — audyt 2026-07-25

Agent: Analityk + Architekt SEO. Dane: `reklamap-os/stats/imports/bing-2026-07-25/*.json` (już pobrane)
+ metody dociągnięte w tej sesji przez `https://ssl.bing.com/webmaster/api.svc/json/{Method}`.
Skrypty pomocnicze: `scratchpad/bing/bapi.py`, `probe.py`, `probe2.py`, `kw.py`, `scratchpad/insp.py`.

---

## 0. KOREKTA BRIEFINGU — liczby crawla Binga to migawki, nie zdarzenia dzienne

**Ustalenie #3 z briefingu (`BlockedByRobotsTxt = 5 255` przy `Code2xx = 14 656`) jest artefaktem sumowania.**

`GetCrawlStats` zwraca 91 rekordów dziennych, ale pola mają MIESZANĄ semantykę:

| pole | semantyka | dowód |
|---|---|---|
| `CrawledPages`, `Code4xx`, `Code5xx`, `CrawlErrors` | **zdarzenia dzienne** | waha się 0–83; `Code4xx=15` = `CrawlErrors=15` w dniu 2026-04-25 |
| `Code2xx`, `BlockedByRobotsTxt`, `InIndex`, `AllOtherCodes` | **migawka stanu (kumulatyw)** | 2026-07-24: `CrawledPages=39`, ale `Code2xx=465` i `BlockedByRobotsTxt=101` — niemożliwe jako licznik dzienny |

**Realne wartości na 2026-07-24** (`GetCrawlStats.json`, ostatni wiersz):

| metryka | wartość |
|---|---|
| URL-e znane Bingowi z kodem 2xx | **465** |
| URL-e zablokowane przez robots.txt | **101** |
| URL-e w indeksie Binga (`InIndex`) | **418** |
| pozostałe kody (`AllOtherCodes`) | 125 |
| `Code5xx` | 0 (przez wszystkie 91 dni) |
| pobrań dziennie (śr. ost. 14 dni) | **34,4** (481 pobrań / 14 dni) |
| pobrań łącznie 91 dni | **1 570** |

Stosunek zablokowanych do znanych: **101 / 566 = 17,8 %** — nie 36 % (5 255/14 656). Skala problemu
jest o dwa rzędy mniejsza niż w briefingu, ale sam problem istnieje (patrz §2).

---

## 1. Profil backlinków — Bing zna **ZERO** linków przychodzących

| metoda | parametry | wynik |
|---|---|---|
| `GetLinkCounts` | `siteUrl=https://reklamap.pl/` | `{"Links": [], "TotalPages": 0}` |
| `GetLinkCounts` | `+page=0`, `+page=1` | to samo |
| `GetLinkCounts` | `siteUrl=https://www.reklamap.pl/` | to samo |
| `GetUrlLinks` | `siteUrl=…&link=https://reklamap.pl/&page=0` | `{"Details": [], "TotalPages": 0}` |
| `GetUrlLinks` | bez ukośnika końcowego | to samo |
| `GetConnectedPages` | `siteUrl=…` | `[]` |
| `GetInboundLinks`, `GetDisavowedLinks` | — | **HTTP 404** (metody nie istnieją w API) |

**Wniosek: Bing nie zna ani jednej domeny linkującej do reklamap.pl.** To jedyne darmowe źródło danych
o linkach (GSC nie daje ich w tej formie) i mówi wprost: profil linków = pusty. Rekomendacja Binga
„not enough inbound links" jest dosłownie prawdziwa, a nie ostrzegawcza.

Weryfikacja krzyżowa z GA4 (`ga4-2026-07-25/source_med.json`) — wszystkie referrale za 3 mies.:

| źródło | sesje | userzy | co to jest |
|---|---|---|---|
| `zasobygwp.pl / referral` | 11 | **1** | domena zasobów webmaila WP — otwarcia własnego maila outreach |
| `poczta.onet.pl / referral` | 6 | 3 | webmail |
| `m.facebook.com` + `facebook.com` | 7 | 7 | social |
| `poczta.wp.pl / referral` | 3 | 1 | webmail |
| `pl.search.yahoo.com / referral` | 1 | 1 | Yahoo PL = silnik Binga |
| `teams.public.onecdn.static.microsoft` | 1 | 1 | podgląd linku w Teams |

**Zero domen redakcyjnych/branżowych.** 100 % „referrali" to webmail, social i podglądy linków — czyli
ślady własnej dystrybucji, nie cudze linki. Dwa niezależne źródła (Bing API + GA4) zgadzają się co do zera.

**Skutek praktyczny:** cała widoczność (Google 9 790 wyśw / Bing 150) jest budowana wyłącznie treścią,
bez żadnego autorytetu domeny. Przy pozycji średniej 31,4 w Google i 0 linkach zewnętrznych, dalsze
skalowanie treścią ma malejący zwrot — brakuje drugiej nogi.

---

## 2. Crawl i przeciek `?_v=` — hipoteza POTWIERDZONA, ale skala ×50 mniejsza

### 2.1 Oś czasu (git + `GetCrawlStats.json`)

| data | zdarzenie | źródło | `BlockedByRobotsTxt` |
|---|---|---|---|
| 2026-04-14 | `cbe4bb9` — do `index.html` wchodzi guard stale-deploy z `location.replace(…?_v=…)`, **bez bramki UA** | `git log -S'_v=' -- frontend/index.html` | 0 |
| 2026-04-27 | robots.txt: `Disallow: /api/`, `/zarzadzaj/`, `/porownaj` (bez `_v`) | `git show 8ca4061:frontend/public/robots.txt` | 0 |
| **2026-05-03** | pierwsze 2 URL-e zablokowane | `GetCrawlStats.json` | **2** |
| 2026-05-12 | `90598cd` — dopisane `Disallow: /*?_v=` i `/*&_v=` | `git show 90598cd -- frontend/public/robots.txt` | 11 |
| 2026-06-15 01:32 UTC | wygenerowany `_v=1781487146877`, który Bing później zaindeksował | `GetPageStats.json` (timestamp z URL-a) | ~75 |
| 2026-07-04 | szczyt | `GetCrawlStats.json` | **101** |
| **2026-07-07** | `8f02064` — bramka UA (`/bot\|crawl\|spider\|…/i` + puste UA) w `frontend/index.html:36` | `git log -S'_v='` | 103 |
| 2026-07-24 | stan dziś | `GetCrawlStats.json` | **101** |

### 2.2 Tempo przyrostu — bramka UA zadziałała

| okres | dni | Δ zablokowanych | tempo |
|---|---|---|---|
| 05-03 → 05-12 (przed `Disallow _v`) | 9 | +9 | +1,00 / dzień |
| 05-12 → 07-04 (`_v` w robots, brak bramki UA) | 53 | +90 | **+1,70 / dzień** |
| 07-07 → 07-24 (po bramce UA) | 17 | **−2** | **−0,12 / dzień** |

Przyrost zatrzymał się **dokładnie** na commicie z bramką UA. To potwierdzenie, że boty faktycznie
wchodziły w redirect `?_v=`.

### 2.3 Dowód bezpośredni

`GetPageStats.json` zawiera zaindeksowany URL:
`https://reklamap.pl/blog/prawo-i-regulacje/oplata-reklamowa?_v=1781487146877` — 2 wyświetlenia, poz. 4,0.
Timestamp `1781487146877` = **2026-06-15 01:32 UTC**, czyli okno między `Disallow` (05-12) a bramką UA (07-07).

Kontrola alternatywnych źródeł blokad: prerenderowany HTML strony głównej **nie zawiera żadnego linku
do `/porownaj`** (`curl -A Bingbot https://reklamap.pl/ | grep porownaj` → 0 trafień), a `/api/` leży na
osobnym hoście (nie liczy się do crawla `reklamap.pl`). `/zarzadzaj/{token}` nie jest nigdzie publicznie
linkowane. **Nie ma innego systemowego kandydata na ~100 zablokowanych URL-i poza wariantami `?_v=`.**

### 2.4 Czego briefing nie zauważył: `Disallow` to ZŁE narzędzie na `_v`

`Disallow: /*?_v=` **nie usuwa** tych URL-i z indeksu — gwarantuje, że tam zostaną. Bing (i Google) nie
mogą pobrać strony, więc nigdy nie zobaczą `noindex`; URL zostaje w indeksie jako wpis bez treści.
Dowód: URL `?_v=…` ma w Bingu 2 **wyświetlenia** mimo blokady.

Bramka UA zatrzymała napływ nowych, ale **101 istniejących URL-i zostanie w indeksie Binga na stałe**
(dziś = 24 % wielkości indeksu: 101 vs `InIndex` 418). Żeby je wyczyścić, trzeba je ODBLOKOWAĆ i podać
`noindex`. Klasyczny playbook:

```apache
# frontend/public/.htaccess — PRZED regułami prerenderu/SPA-fallback
# Purge URL-i ?_v= z indeksu: robots.txt Disallow tylko je zamraża (bot nie pobierze → nie zobaczy
# noindex). Podajemy noindex nagłówkiem i USUWAMY Disallow z robots.txt na ~60 dni.
<IfModule mod_setenvif.c>
  SetEnvIf Query_String "(^|&)_v=" REKLAMAP_VERSIONED=1
</IfModule>
<IfModule mod_headers.c>
  Header always set X-Robots-Tag "noindex, follow" env=REKLAMAP_VERSIONED
</IfModule>
```

…i równolegle **usunięcie samego źródła** — recovery po stale-deployu nie potrzebuje query stringa.
Fragment (`#`) nie trafia na serwer i nie jest indeksowany:

```js
// frontend/index.html — zamiast location.replace(path + '?_v=' + now)
// Fragment nie jest wysyłany do serwera ani indeksowany, a wymusza pełną nawigację tak samo.
sessionStorage.setItem(RELOAD_KEY, String(now));
location.replace(location.pathname + location.search + '#_rmv' + now);
location.reload();
```

Kolejność: (1) hash zamiast `?_v=` → 0 nowych URL-i, (2) `X-Robots-Tag: noindex` + zdjęcie `Disallow`
na ~60 dni, (3) po wypadnięciu 101 URL-i z indeksu przywrócić `Disallow` (lub nie — nie będzie już czego blokować).
**Bramki UA w `index.html:36` NIE ruszać** — zostaje jako druga linia obrony.

---

## 3. `Code5xx = 0` w Bingu vs 24 strony 5xx w Google

### 3.1 Test na żywo — 18 URL-i z sitemapy × 3 UA (Googlebot / Bingbot / zwykły curl)

Kandydaci: home, hub `/powierzchnie-reklamowe`, 6 kategorii miast/typów, 6 leafów, 4 kategorie bloga.
**Wynik: 54/54 odpowiedzi HTTP 200. Zero różnic między UA.** (`scratchpad/cands.txt`)

### 3.2 `api.reklamap.pl` i warianty hostów

| URL | Googlebot | Bingbot | curl |
|---|---|---|---|
| `https://api.reklamap.pl/` | 200 | 200 | 200 |
| `https://api.reklamap.pl/api/listings?per_page=1` | 403 | 403 | 403 (brak `X-App-Key` — poprawnie) |
| `https://api.reklamap.pl/sitemap.xml` | 200 | 200 | 200 |
| `https://api.reklamap.pl/robots.txt` | 200 | 200 | 200 |
| `https://www.api.reklamap.pl/sitemap.xml` | 200 | 200 | 200 |
| `https://www.reklamap.pl/` | 301 | 301 | 301 |
| `…/oplata-reklamowa?_v=1781487146877` | 200 | 200 | 200 |

Burst 30 równoległych żądań na `api.reklamap.pl/storage/advertisements/reklama-ai/305_01.jpg` → **30 × 200**.
Brak 5xx pod obciążeniem (na tym poziomie).

### 3.3 Hipoteza „to api.reklamap.pl generuje 5xx" — OBALONA

Właściwość GSC to `sc-domain:reklamap.pl`, czyli obejmuje wszystkie subdomeny — `api.reklamap.pl`
mogłoby więc trafić do raportu 5xx. Inspekcja URL-i (`gapi.gsc_inspect`, `scratchpad/insp.py`):

| URL | `coverageState` |
|---|---|
| `https://api.reklamap.pl/` | **„Adres URL jest Google nieznany"** |
| `https://api.reklamap.pl/sitemap.xml` | **„Adres URL jest Google nieznany"** |
| `https://api.reklamap.pl/storage/advertisements/reklama-ai/305_01.jpg` | **„Adres URL jest Google nieznany"** |

Google nigdy nie odwiedził `api.reklamap.pl` (robots.txt tego hosta: `Disallow: /`, `Allow: /sitemap.xml`,
`Allow: /storage/`). **24 strony 5xx nie pochodzą z api.**

### 3.4 Dlaczego Bing pokazuje 0 — bo prawie nie crawluje

| metryka | Bing | źródło |
|---|---|---|
| `CrawlRate` (24 sloty godzinowe) | `[5,5,5,…]` — poziom domyślny | `GetCrawlSettings` |
| `CrawlBoostAvailable` | **false** | `GetCrawlSettings` |
| pobrań / dobę (ost. 14 dni) | **34,4** | `GetCrawlStats.json` |
| pełny cykl recrawl 983 URL-i | **~29 dni** | 983 / 34,4 |

Bing pobiera średnio **1 stronę na 42 minuty**. Przy takim wolumenie prawdopodobieństwo trafienia
w chwilowy 5xx shared-hostingu jest bliskie zeru — `Code5xx = 0` **nie jest dowodem zdrowia serwera**,
tylko dowodem, że Bing nie crawluje. Googlebot crawluje wielokrotnie intensywniej i to on wywołuje
limity współbieżności LiteSpeed/Hostido.

**Wniosek:** dwa najbardziej prawdopodobne wyjaśnienia 24×5xx w GSC (brak danych, żeby rozstrzygnąć
między nimi bez raportu Crawl Stats z UI GSC, który nie ma API):
1. **stan historyczny** — awaria SSL/WAF `api.reklamap.pl` z lipca (`cors_render_ip_block`); raport GSC
   „Pages" pokazuje ostatni znany status i utrzymuje go miesiącami do recrawlu;
2. **limit współbieżności hostingu** przy burstach Googlebota (LiteSpeed 503/508) — nie do odtworzenia
   z jednego IP przy 30 równoległych żądaniach.

Nie da się tego rozstrzygnąć zdalnie. **Rozstrzygnięcie: eksport listy 24 URL-i z GSC → „Pages → Server
error (5xx) → Export"**, potem `curl` po każdym. To 10 minut roboty i zamyka temat — dziś zgadujemy.

---

## 4. Weryfikacja konta Bing — import z GSC, bez pliku i bez DNS

`GetSiteRoles` (`siteUrl=https://reklamap.pl/`):

```json
{"DelegatorEmail": "GSCImport", "Email": "abstr4cte@gmail.com", "Role": 0,
 "Site": "https://reklamap.pl/", "Expired": false, "Date": "2026-07-09T09:16 UTC"}
```

`GetUserSites`:
```json
{"AuthenticationCode": "C99CC63E7F736A91B0855C7613D85CBB",
 "DnsVerificationCode": "4f52f95a835d65670f45cf307d716999.reklamap.pl",
 "IsVerified": true, "Url": "https://reklamap.pl/"}
```

Kontrola trzech kanonicznych metod weryfikacji:

| metoda | stan | dowód |
|---|---|---|
| plik `BingSiteAuth.xml` | **NIE ISTNIEJE** | `curl https://reklamap.pl/BingSiteAuth.xml` → 200 `text/html` 7 929 B = SPA-fallback, nie XML |
| meta `msvalidate.01` | **BRAK** | `curl https://reklamap.pl/ \| grep -i msvalidate` → 0 trafień; `grep -rn msvalidate frontend/ backend/` → 0 |
| DNS TXT / CNAME | **BRAK** | `dig +short TXT reklamap.pl` → tylko `google-site-verification=…` i SPF; `dig +short TXT _bing.reklamap.pl` → puste |

**Odpowiedź: konto zweryfikowano przez „Import from Google Search Console"** (`DelegatorEmail: GSCImport`).
Weryfikacja jest **delegowana** — Bing ufa, że konto `abstr4cte@gmail.com` ma dostęp do GSC.

**Ryzyko:** utrata/zmiana dostępu do właściwości GSC może pociągnąć za sobą utratę weryfikacji w Bing WMT
(a wraz z nią klucza API i dostępu do IndexNow z tym kluczem). Kody na własność są już wygenerowane
i czekają — wystarczy dodać **jeden** z nich jako niezależny dowód (effort XS, ~2 min):

```
frontend/public/BingSiteAuth.xml:
<?xml version="1.0"?><users><user>C99CC63E7F736A91B0855C7613D85CBB</user></users>
```
Plik z `frontend/public/` trafia do `dist/` i jest serwowany dosłownie — `.htaccess` ma
`RewriteCond %{REQUEST_FILENAME} !-f` przed każdą regułą przepisania (`frontend/public/.htaccess:82,101,105`),
dokładnie tak jak działający `robots.txt`.

---

## 5. Sitemapy zgłoszone w Bingu — trzy, w tym jedna z hosta-widma

`GetFeeds`:

| URL | `UrlCount` | `LastCrawled` | `Submitted` |
|---|---|---|---|
| `https://api.reklamap.pl/sitemap.xml` | **987** | 2026-07-23 12:25 | 2026-04-21 (ręcznie) |
| `https://reklamap.pl/sitemap.xml` | 979 | **2026-07-06 05:22** | auto (z robots.txt) |
| `https://www.api.reklamap.pl/sitemap.xml` | 250 | 2026-05-26 05:27 | auto |

Trzy uwagi:
1. **`www.api.reklamap.pl` to czwarty żywy hostname** — `dig` → ten sam IP `185.110.48.51`, `curl` →
   200 `application/xml`, 320 440 B (bajt w bajt to samo co `api`), **bez 301**. Powinien 301-ować
   na `api.reklamap.pl` tak jak `www.reklamap.pl` → `reklamap.pl`.
2. Kanoniczną sitemapę frontu (`reklamap.pl/sitemap.xml`) Bing pobrał ostatnio **19 dni temu**.
3. Rozjazd 987 vs 983 — patrz §6.

---

## 6. ⚠ ZNALEZISKO KRYTYCZNE: 4 URL-e w sitemapie są dziś serwowane z `noindex`

`reklamap.pl/sitemap.xml` (serwowany statycznie z `dist/`, zapisany przez `prerender.mjs`) ma
**983 URL-e, max `lastmod` = 2026-07-13T11:51:58Z**.
`api.reklamap.pl/sitemap.xml` (świeży, generowany z bazy) ma **987 URL-i, max `lastmod` = 2026-07-23T11:10:48Z**.

Nagłówki prod: `Last-Modified: Mon, 13 Jul 2026 11:52:28 GMT` dla `/sitemap.xml` i `11:52:30 GMT` dla
`/index.html` → **ostatni deploy frontu: 2026-07-13 11:52 UTC, 12 dni temu.**

4 URL-e obecne w sitemapie backendu, których front nie ma sprerenderowanych:

| URL | `lastmod` w sitemapie |
|---|---|
| `/blog/prawo-i-regulacje/pozwolenie-na-tablice-reklamowa` | 2026-07-14T08:33 |
| `/blog/prawo-i-regulacje/reklama-bez-pozwolenia-kary` | 2026-07-22T10:09 |
| `/powierzchnia-reklamowa/billboardy/jablonowo/dwustronny-billboard-36m2-…-997` | — |
| `/powierzchnia-reklamowa/billboardy/nowa-wies-elcka/billboard-blisko-elku-998` | — |

Sprawdzenie na żywo (`curl -A Googlebot`):

```
/blog/prawo-i-regulacje/pozwolenie-na-tablice-reklamowa   robots=['noindex, follow']  title='Wynajem powierzchni reklamowych w Polsce | ReklaMap'  7832 B  seed=False
/blog/prawo-i-regulacje/reklama-bez-pozwolenia-kary       robots=['noindex, follow']  title=(jw.)  7832 B  seed=False
/powierzchnia-reklamowa/billboardy/nowa-wies-elcka/…-998  robots=['noindex, follow']  title=(jw.)  7832 B  seed=False
```

To dokładnie klasa błędu, przed którą ostrzega `CLAUDE.md` („Submitted URL marked noindex"), i jest
**żywa na produkcji**. Potwierdza GSC: `pozwolenie-na-tablice-reklamowa` →
`coverageState: "Strona wykryta – obecnie niezindeksowana"`, `lastCrawlTime: null`.

**Przyczyna systemowa:** publikacja artykułu z panelu admina odpala `BlogPost::saved` →
`Cache::forget('sitemap_xml')` (`backend/app/Models/BlogPost.php:39`), więc URL **natychmiast** wchodzi
do sitemapy — ale prerender powstaje wyłącznie podczas `frontend/deploy.sh`. Między publikacją a deployem
frontu każdy nowy URL jest reklamowany w sitemapie i serwowany jako `spa-fallback.html` z `noindex`.
Ta luka jest tym dotkliwsza, że **blog jest jedynym, co realnie rankuje w Bingu** (§8).

`php artisan seo:tripwire` tego nie łapie — próbkuje 5 URL-i (`SeoTripwire.php:93` `$samples`), a problem
dotyczy 4 konkretnych z 987. Domknięcie systemowe (effort XS):

```php
// backend/app/Console/Commands/SeoTripwire.php — dodatkowy check po pobraniu sitemapy
// Wykrywa rozjazd „sitemap backendu vs sprerenderowany front": URL zgłoszony, ale bez prerenderu
// dostaje spa-fallback z noindex → GSC „Submitted URL marked noindex".
$front = Http::withHeaders(['User-Agent' => self::UA])->timeout(20)
    ->get(config('app.frontend_url') . '/sitemap.xml')->body();
preg_match_all('/<loc>([^<]+)<\/loc>/', $front, $fm);
$missing = array_values(array_diff($locs, $fm[1] ?? []));
if ($missing) {
    $problems[] = sprintf('sitemap backendu ma %d URL-i bez prerenderu (np. %s) — uruchom frontend/deploy.sh',
        count($missing), $missing[0]);
}
```

**Akcja natychmiastowa (wymaga zgody founder'a — nie wykonuję):** `cd frontend && ./deploy.sh`.

---

## 7. IndexNow — plan wdrożenia + uczciwa wycena zysku

### 7.1 Stan obecny

- `https://reklamap.pl/indexnow.txt` → HTTP 200, ale **7 929 B `text/html`** = SPA-fallback. Klucza nie ma.
- `grep -rn "indexnow\|IndexNow" frontend/ backend/` → **0 trafień**. Zero kodu.
- Alternatywa działająca już dziś: `GetUrlSubmissionQuota` → `{"DailyQuota": 100, "MonthlyQuota": 700}`.
  Metoda `SubmitUrlBatch` działa na istniejącym kluczu API, bez pliku klucza. **Nie wywoływałem** (zapis na prod).

### 7.2 Czego dokładnie wymaga wdrożenie

1. **Klucz** — 8–128 znaków `[a-zA-Z0-9-]`, np. `openssl rand -hex 16`.
2. **Plik klucza** pod adresem hosta, którego URL-e zgłaszasz →
   `frontend/public/<KEY>.txt`, treść = sam klucz, bez znaku nowej linii.
   Vite kopiuje `public/` → `dist/`, a `.htaccess` przepuszcza realne pliki
   (`RewriteCond %{REQUEST_FILENAME} !-f`, `frontend/public/.htaccess:82,101,105`) — czyli zadziała jak `robots.txt`.
3. **POST JSON** na `https://api.indexnow.org/indexnow` (rozgłasza do Bing, Yandex, Seznam, Naver;
   `https://www.bing.com/indexnow` to wariant tylko-Bing), do 10 000 URL-i na żądanie, wszystkie z jednego hosta.

### 7.3 Kod — 4 pliki, ~70 linii

```php
// backend/config/services.php  (env() TYLKO w config/ — Senior Dev Standards)
'indexnow' => [
    'key'      => env('INDEXNOW_KEY'),
    'endpoint' => env('INDEXNOW_ENDPOINT', 'https://api.indexnow.org/indexnow'),
    'host'     => env('INDEXNOW_HOST', 'reklamap.pl'),
],
```

```php
<?php
// backend/app/Services/IndexNowService.php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class IndexNowService
{
    /** IndexNow przyjmuje max 10 000 URL-i na żądanie, wszystkie z jednego hosta. */
    private const BATCH = 10000;

    /**
     * @param list<string> $urls kanoniczne, absolutne URL-e hosta z config('services.indexnow.host')
     * @return bool czy WSZYSTKIE paczki poszły (2xx). false = miękka porażka, nigdy wyjątek do kontrolera.
     */
    public function submit(array $urls): bool
    {
        $key  = (string) config('services.indexnow.key');
        $host = (string) config('services.indexnow.host');
        if ($key === '' || $urls === []) {
            return false;
        }

        $urls = array_values(array_unique(array_filter(
            $urls,
            static fn (string $u): bool => str_starts_with($u, "https://{$host}/"),
        )));

        $ok = true;
        foreach (array_chunk($urls, self::BATCH) as $chunk) {
            $res = Http::timeout(15)->acceptJson()->post(config('services.indexnow.endpoint'), [
                'host'        => $host,
                'key'         => $key,
                'keyLocation' => "https://{$host}/{$key}.txt",
                'urlList'     => $chunk,
            ]);
            // 200 = przyjęte, 202 = przyjęte, klucz w trakcie walidacji.
            if (! $res->successful()) {
                $ok = false;
                Log::warning('IndexNow odrzucił paczkę', [
                    'status' => $res->status(), 'count' => count($chunk), 'body' => $res->body(),
                ]);
            }
        }

        return $ok;
    }
}
```

```php
<?php
// backend/app/Jobs/SubmitToIndexNow.php  — kolejka, żeby nie blokować requestu użytkownika
declare(strict_types=1);

namespace App\Jobs;

use App\Services\IndexNowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SubmitToIndexNow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /** @param list<string> $urls */
    public function __construct(private readonly array $urls) {}

    public function handle(IndexNowService $service): void
    {
        $service->submit($this->urls);
    }
}
```

Wpięcie w istniejące punkty inwalidacji sitemapy (te same miejsca, nic nowego do pilnowania):

```php
// backend/app/Models/BlogPost.php:39 — obok Cache::forget('sitemap_xml')
static::saved(function (self $post): void {
    Cache::forget('sitemap_xml');
    if ($post->status === 'published') {
        SubmitToIndexNow::dispatch([
            config('app.frontend_url') . '/blog/' . $post->category . '/' . $post->slug,
        ]);
    }
});
```

```php
// backend/app/Http/Controllers/AdvertisementController.php:1194 — obok Cache::forget('sitemap_xml')
Cache::forget('sitemap_xml');
SubmitToIndexNow::dispatch([$advertisement->canonicalUrl()]);
```

Komenda bulk (jednorazowo przy starcie i po dużych importach):

```php
// backend/app/Console/Commands/IndexNowSubmitSitemap.php
protected $signature = 'indexnow:submit-sitemap {--limit=10000}';
public function handle(IndexNowService $service): int
{
    $xml = \Illuminate\Support\Facades\Http::timeout(30)
        ->get(config('app.url') . '/sitemap.xml')->body();
    preg_match_all('/<loc>([^<]+)<\/loc>/', $xml, $m);
    $urls = array_slice($m[1] ?? [], 0, (int) $this->option('limit'));
    $this->info(sprintf('IndexNow: zgłaszam %d URL-i', count($urls)));
    return $service->submit($urls) ? self::SUCCESS : self::FAILURE;
}
```

Wpięcie w deploy — po tripwire, tak jak on: miękko, nie przerywa (`frontend/deploy.sh`, po linii z `seo:tripwire`):

```bash
# IndexNow — po deployu prerender istnieje dla całej sitemapy, więc dopiero TERAZ wolno zgłaszać.
# Miękko: nieudane zgłoszenie nie psuje deployu (URL-e i tak wejdą crawlem).
echo "==> IndexNow: zgłoszenie sitemapy"
( cd ../backend && php artisan indexnow:submit-sitemap ) || echo "⚠️ IndexNow nie przyjął — sprawdź plik klucza"
```

**Kolejność jest istotna** i wynika z §6: IndexNow zgłasza URL-e, które muszą już mieć prerender.
Zgłoszenie przed deployem frontu = zaproszenie bota na `noindex`. Dlatego wywołanie idzie **po** rsyncu.

### 7.4 Realny zysk — bez ściemy

| co | dziś | po IndexNow |
|---|---|---|
| indeks Binga | **418 / 983 URL-i (42,5 %)** | docelowo ~983 |
| tempo indeksowania | +18,25 URL/dzień (126 → 418 między 07-08 a 07-24) | — |
| czas do zaindeksowania nowego ogłoszenia | **~29 dni** (983 URL / 34,4 pobrań dziennie) | **< 24 h** |
| ruch z Binga (91 dni) | **150 wyśw / 3 kliki** | jeśli liniowo od wielkości indeksu: ~350 wyśw / ~7 klików / kwartał |

**Uczciwie: bezpośredni zysk ruchowy to ~2–3 kliki miesięcznie.** Bing to 0,9 % organiki
(GA4: `bing / organic` 2 sesje vs `google / organic` 222). Argumenty ZA mimo to:

1. **Świeżość dla marketplace'u.** 29 dni do indeksacji nowego nośnika jest bez sensu przy bazie,
   która żyje. To jedyna dźwignia — `CrawlBoostAvailable: false`, `CrawlRate` już maksymalny domyślny.
2. **Indeks Binga zasila więcej niż Bing:** Copilot, Yahoo PL (GA4: 1 sesja z `pl.search.yahoo.com`),
   DuckDuckGo, Ecosia. Jeden protokół dowozi też Yandex/Seznam/Naver.
3. **Koszt ~zero:** ~70 linii, brak zależności zewnętrznych, brak utrzymania, wpięcie w istniejące
   `Cache::forget('sitemap_xml')`.

**Nie robić tego zamiast §6 ani zamiast linków (§1).** To porządkowanie, nie dźwignia wzrostu.
Jeśli trzeba wybrać jedno — najpierw §6 (żywy `noindex` na 2 artykułach), potem §1.

### 7.5 AI Performance / Copilot — brak API

Sprawdzone metody (wszystkie **HTTP 404 — nie istnieją**):
`GetAiPerformance`, `GetAIPerformanceStats`, `GetCopilotStats`, `GetChatStats`, `GetAiTrafficStats`,
`GetAIStats`, `GetGenerativeSearchStats`.

Odpowiedź: **zakładka AI Performance (BETA) jest wyłącznie w UI Bing Webmaster Tools, bez odpowiednika
w API.** Dane o cytowaniach w Copilocie trzeba wyklikać i wyeksportować ręcznie. To jedyna luka
w tym audycie, której nie da się zamknąć skryptem — **brak danych o Copilocie**.

Przy okazji odrzucone jako nieistniejące: `GetActiveFilters`, `GetQuerySuggestions`, `GetSiteMoves`,
`GetGeoTargetings`, `GetSimilarKeywords`, `GetInboundLinks`, `GetDisavowedLinks` (404).
Zwróciły puste: `GetBlockedUrls` `[]` (brak ręcznych blokad w BWT), `GetCrawlIssues` `[]`,
`GetDeepLinkBlocks` `[]`, `GetConnectedPages` `[]`, `GetRelatedKeywords` `[]`.

---

## 8. Blog lokalizacyjny w Bingu vs Google — to NIE jest lepszy ranking

### 8.1 Fakty

Bing 91 dni (`GetQueryStats.json`, `GetPageStats.json`): **145 wyświetleń / 3 kliki / 48 fraz**.
Blog lokalizacyjny to **83 z 145 wyświetleń (57 %)**.

| fraza (Bing) | wyśw | kliki | poz. |
|---|---|---|---|
| `reklama outdoor baner billboard krakow` | 53 | 0 | **1,2** |
| `reklama outdoor baner billboard wroclaw` | 26 | 0 | 3,5 |
| `reklama outdoor baner billboard poznan` | 6 | 0 | 7,0 |
| `reklama outdoor baner billboard gdansk` | 3 | 0 | 2,0 |
| `reklama outdoor baner billboard sosnowiec` | 2 | 0 | 6,0 |
| **razem szablon** | **90** | **0** | — |

### 8.2 Dlaczego tego NIE da się przenieść

Fraza `reklama outdoor baner billboard {miasto}` ma w Google **0 wyświetleń** — nie występuje ani razu
w `gsc-2026-07-25/api/q3m__query.json` (grep po `baner billboard` → pusto, 695 fraz non-brand).

To nie jest przypadek „w Bingu rankujemy wyżej na to samo zapytanie". To zapytanie, którego Google
w praktyce nie obsługuje. Trzy przesłanki, że nie stoi za nim popyt:

1. **Kształt szablonowy** — identyczna 5-wyrazowa fraza dla 5 miast, w tym Sosnowca, dla którego nie
   mamy artykułu (`ls reklamap-os/blog/posts/ | grep reklama-outdoor-` → 11 miast, bez Sosnowca).
2. **0 klików na 90 wyświetleń przy pozycji 1,2–3,5.** Pozycja 1 z zerowym CTR to sygnatura ruchu
   nie-ludzkiego (rank-tracker / scraper), nie zapytania użytkownika.
3. **W zbiorze są jawne śmieci** — jedna z fraz to wklejony snippet SERP-a:
   `"ę.pl#N#www.znajdzreklame.pl › wynajem › billboardów#N##N#baner reklamowy cena wynajmu…"` (2 wyśw).

Dopasowanie jest workiem słów: `<h1>Reklama outdoor Kraków — lokalizacje i ceny 2026</h1>` + nawigacja
serwisu („Billboardy Citylighty Ekrany LED **Banery** …", potwierdzone `curl -A Bingbot`).
**Werdykt: nie ma czego przenosić.** Rekomendacja pisania pod frazy typu „reklama outdoor baner billboard X"
byłaby optymalizacją pod bota.

### 8.3 Co JEST warte przeniesienia — realny popyt Google na tym samym artykule

Ten sam URL w Google (`q3m__query_page.json`, 3 mies.):

| fraza | wyśw | kliki | poz. |
|---|---|---|---|
| `nośniki reklamowe kraków outdoor` | 25 | 0 | **8,1** |
| `reklama na tramwajach kraków koszt` | 19 | 0 | 19,4 |
| `reklama na autobusach kraków cena` | 17 | 0 | 21,5 |
| `reklama outdoorowa kraków` | 16 | 0 | 18,6 |
| `kampania reklamowa kraków outdoor` | 15 | 0 | 31,9 |
| `reklama w tramwajach kraków` | 14 | 0 | 37,2 |
| `reklama w tramwaju kraków` | 12 | 0 | 24,5 |
| `reklama mpk kraków` | 11 | 0 | 38,7 |
| `tablice reklamowe kraków cennik` | 11 | 0 | 27,8 |
| `reklama citylight kraków` | 9 | 0 | 43,7 |

Cały blog lokalizacyjny w Google: **598 wyświetleń / 3 kliki / poz. 26,8–30,7** na 4 głównych artykułach
(Kraków 238, Poznań 122, Gdańsk 115, Łódź 107).

**Sygnał dla Stratega:** transport miejski (tramwaje/autobusy/MPK) to **~84 wyświetlenia w samym
artykule krakowskim**, na pozycjach 19–41 — czyli klaster, którego artykuł dotyka mimochodem i który
nie ma własnej strony. To realne zapytanie z realnym wolumenem, w przeciwieństwie do frazy z Binga.

---

## 9. `GetKeywordStats` działa — darmowe wolumeny Bing PL (head-terms)

Parametry: `q=`, `country=pl`, `language=pl-PL`. Zwraca szereg tygodniowy (25 punktów ≈ 6 mies.):

| fraza | punktów | suma exact | suma broad | 3 ostatnie tyg. (exact) |
|---|---|---|---|---|
| `billboard` | 25 | **1 707** | 2 467 | 34, 39, 43 |
| `baner reklamowy` | 25 | **1 205** | 1 205 | 33, 37, 45 |
| `citylight` | 25 | **352** | 352 | 4, 7, 11 |

**Brak danych** (pusta odpowiedź) dla 22 z 25 sprawdzonych fraz, m.in.: `billboardy`, `reklama zewnętrzna`,
`powierzchnie reklamowe`, `wynajem billboardu`, `totem reklamowy`, `reklama outdoor`, `billboardy warszawa`,
`nośniki reklamowe`, `ile kosztuje billboard`, `opłata reklamowa`, `uchwała krajobrazowa`.

Wniosek: narzędzie użyteczne **tylko dla head-terms**, do walidacji rzędu wielkości i sezonowości.
Long-tail (którym żyjemy) jest poza zasięgiem. `GetRelatedKeywords` → `[]`, `GetSimilarKeywords` → 404.
Do rozważenia jako darmowy sanity-check w workflow Stratega, nie jako zamiennik Ahrefs.

---

## 10. Drobiazgi

- **Duplikat `<h1>`** na `/blog/lokalizacje/reklama-outdoor-krakow` — dwa elementy `<h1>` z identyczną
  treścią „Reklama outdoor Kraków — lokalizacje i ceny 2026" w sprerenderowanym HTML.
- Cały ruch z Binga (3 kliki / 91 dni) domyka się z GA4 (`bing / organic` = 2 sesje) — dane spójne.
- `AllOtherCodes` stabilnie ~120–128 (migawka). `CrawlErrors` bywa niezerowe (12 w dniach 06-23/24, 10 w 06-25,
  6 w 07-22) przy `Code4xx = Code5xx = 0` — Bing liczy tam coś innego niż kody HTTP; **brak danych**, co.

---

## Priorytety

| # | akcja | effort | dlaczego teraz |
|---|---|---|---|
| 1 | `cd frontend && ./deploy.sh` — 4 URL-e z sitemapy serwują `noindex` od 12 dni | XS | żywy błąd, 2 artykuły bloga (jedyne, co rankuje) |
| 2 | Check „sitemap backendu vs prerender" w `seo:tripwire` | XS | żeby #1 nie wróciło po każdej publikacji |
| 3 | Eksport 24 URL-i z GSC „Server error (5xx)" i `curl` po nich | XS | dziś zgadujemy; §3 nie rozstrzyga |
| 4 | `?_v=` → fragment `#` + `X-Robots-Tag: noindex` zamiast `Disallow` | S | 101 URL-i zamrożonych w indeksie Binga (24 % indeksu) |
| 5 | `BingSiteAuth.xml` jako niezależna weryfikacja | XS | dziś weryfikacja wisi na `GSCImport` |
| 6 | 301 z `www.api.reklamap.pl` → `api.reklamap.pl` | XS | czwarty żywy hostname, 200 bez przekierowania |
| 7 | IndexNow (kod w §7.3) | S | świeżość, nie ruch; ~2–3 kliki/mies. |
| 8 | Linki przychodzące — 0 domen (§1) | L | jedyne, co realnie ruszy pozycję 31,4 w Google |
| 9 | Brief dla Stratega: klaster „reklama w transporcie miejskim" (§8.3) | M | 84 wyśw na jednym artykule, poz. 19–41, brak własnej strony |
