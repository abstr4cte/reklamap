<?php
/**
 * legacy-ad-redirect.php — 301 dla leaf-URL-i ogłoszeń BEZ prerenderu: stary format sluga
 * wymiarów sprzed 2026-06-15 (commit 2cecec4, np. „billboard-504238-m-..." zamiast aktualnego
 * „billboard-5-04-x-2-38-m-...") lub ogłoszenie jeszcze nieujęte w ostatnim buildzie sitemapy.
 *
 * Po co osobny PHP shim, a nie SPA (index.html)? AdDetailPage.vue potrafi rozwiązać ogłoszenie
 * po końcowym {id} niezależnie od treści sluga i sama nadaje canonical — ALE to wymaga fetchu
 * do api.reklamap.pl z przeglądarki bota, a Google render-IP bywa blokowany na CORS preflight
 * przez WAF Hostido (patrz CLAUDE.md → „SEO, Sitemap i Deploy"; pamięć projektu:
 * cors-render-ip-block — to był przyczyną deindeksu marki VII 2026). Wtedy JS nigdy nie
 * nadpisuje domyślnego canonical z index.html (wskazuje na stronę główną) — gorzej niż noindex.
 * Ten skrypt robi 301 server-to-server (zwykły curl, nie fetch z przeglądarki), więc nie zależy
 * od renderowania JS ani od CORS.
 *
 * .htaccess kieruje tu WYŁĄCZNIE niesprerenderowane URL-e pasujące do wzorca leaf ogłoszenia,
 * przed SPA-fallbackiem. Wołany tylko dla ścieżek zgodnych z /powierzchnia-reklamowa/{typ}/{miasto}/{cokolwiek}-{id}.
 */

const API_BASE = 'https://api.reklamap.pl/api';
const APP_URL  = 'https://reklamap.pl';
// Ten sam klucz co og-meta.php — host statyczny nie ma .env Laravela, PHP nie jest serwowany jako źródło.
const APP_KEY  = '0733bcc35673003f17ec162f4460cda924e2829a1845b3a7c0923c1aa3d0fdc9';
// Zgodne z generatorem sitemapy (backend/routes/web.php): tylko te statusy mają być indeksowalne.
const INDEXABLE_STATUSES = ['active', 'soon_available', 'reserved'];

$path = isset($_GET['path']) ? (string) $_GET['path'] : '';
$path = strtok($path, '?');
$path = trim($path, '/');

if (!preg_match('#^powierzchnia-reklamowa/[^/]+/[^/]+/.+-(\d+)$#', $path, $m)) {
    // Nie powinno się zdarzyć (ten sam wzorzec dopasowuje reguła w .htaccess) — bezpieczny fallback.
    serveFallback();
    exit;
}

$adId = (int) $m[1];
$ad   = fetchAd($adId);

if ($ad !== null && !empty($ad['full_url']) && in_array($ad['status'] ?? '', INDEXABLE_STATUSES, true)) {
    $targetPath = ltrim((string) $ad['full_url'], '/');

    if (rtrim($path, '/') !== rtrim($targetPath, '/')) {
        header('Location: ' . APP_URL . '/' . $targetPath, true, 301);
        exit;
    }

    // Edge case: żądana ścieżka JEST już aktualnym kanonicznym slugiem (nowe ogłoszenie
    // jeszcze nieujęte w ostatnim buildzie sitemapy/prerenderu) — 301 na siebie to pętla.
    // Oddaj zwykły index.html (indeksowalny SPA-shell); realną treść dowiezie kolejny prerender.
    $index = @file_get_contents(__DIR__ . '/index.html');
    if ($index !== false) {
        header('Content-Type: text/html; charset=UTF-8');
        echo $index;
        exit;
    }
}

serveFallback();
exit;

// ============================ FUNKCJE =========================================

/** GET /listings/{id} z nagłówkiem X-App-Key. Null przy błędzie/timeout/404. */
function fetchAd(int $id): ?array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => API_BASE . '/listings/' . $id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_HTTPHEADER     => ['X-App-Key: ' . APP_KEY, 'Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $code < 200 || $code >= 300) {
        return null;
    }
    $json = json_decode($body, true);
    if (!is_array($json)) {
        return null;
    }
    return isset($json['data']) && is_array($json['data']) ? $json['data'] : $json;
}

/** Ogłoszenie nie istnieje/nieaktywne/API padło — ten sam noindex-owy szkielet co reszta SPA. */
function serveFallback(): void
{
    header('Content-Type: text/html; charset=UTF-8');
    $fallback = @file_get_contents(__DIR__ . '/spa-fallback.html');
    if ($fallback !== false) {
        echo $fallback;
        return;
    }
    // Degradacja bezpieczeństwa (jak w .htaccess): brak spa-fallback.html → zwykła SPA.
    $index = @file_get_contents(__DIR__ . '/index.html');
    echo $index !== false ? $index : '';
}
