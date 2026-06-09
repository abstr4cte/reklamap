<?php
/**
 * og-meta.php — lekki shim Open Graph dla scraperów social (NIE Googlebot).
 *
 * Po co: front to SPA. Meta og:/twitter wstrzykuje dopiero JS (useSeo.ts), więc
 * scrapery, które NIE renderują JS (Facebook, LinkedIn, WhatsApp, Slack, Discord,
 * Twitter/X, Telegram...), dostają surowy index.html z generycznym kartonem.
 * Ten shim — wołany tylko dla tych UA z `.htaccess` — pobiera dane ogłoszenia LUB
 * artykułu bloga z API i wstrzykuje og:title/description/image z realnych pól
 * (ogłoszenie: typ, miasto, cena, zdjęcie; artykuł: tytuł, zajawka, hero).
 *
 * Googlebot/Bingbot TU NIE TRAFIAJĄ (renderują JS samodzielnie) — patrz `.htaccess`.
 * Zero headless/Node/quota → brak ryzyka 429 jak przy prerender.io.
 * Każda ścieżka oddaje 200 + index.html (z og lub bez) — nigdy 5xx do scrapera.
 *
 * Etykiety/szablony lustrzane wobec `AdDetailPage.vue` (watcher [ad, similarAds])
 * i `useSeo.ts`. Przy zmianie tamtych — zsynchronizować tutaj.
 */

// --- Podgląd logu (diagnostyka): /og-meta.php?showlog=rm2024debug -------------
if (isset($_GET['showlog']) && $_GET['showlog'] === 'rm2024debug') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo @file_get_contents('/tmp/og_meta_debug.log') ?: 'brak logów';
    exit;
}

// --- Konfiguracja (host statyczny nie ma .env Laravela; klucz embedowany jak w
//     prerender-proxy.php — PHP nie jest serwowany jako źródło) ----------------
const APP_URL     = 'https://reklamap.pl';
const API_BASE    = 'https://api.reklamap.pl/api';
const STORAGE_URL = 'https://api.reklamap.pl/storage';
const APP_KEY     = '0733bcc35673003f17ec162f4460cda924e2829a1845b3a7c0923c1aa3d0fdc9';
const OG_FALLBACK_IMAGE = APP_URL . '/og-image.png';

// enum z bazy → etykieta (kopia typeLabels z useSearchStore.ts)
const TYPE_LABELS = [
    'billboard' => 'Billboardy', 'citylight' => 'Citylighty', 'led_screen' => 'Ekrany LED',
    'banner' => 'Banery', 'wall' => 'Ściany reklamowe', 'totem' => 'Totemy reklamowe',
    'transport' => 'Reklama w transporcie', 'mobile' => 'Reklama mobilna', 'other' => 'Inne',
];

// --- Wczytaj bazowy index.html (zawsze go oddamy, z og lub bez) ---------------
$indexHtml = @file_get_contents(__DIR__ . '/index.html');
if ($indexHtml === false) {
    // Nieosiągalne w praktyce (index.html jest obok). Ostateczność: 503, nie goły 5xx.
    http_response_code(503);
    header('Retry-After: 3600');
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
http_response_code(200);

// --- Ścieżka żądania ----------------------------------------------------------
$path = isset($_GET['path']) ? (string) $_GET['path'] : '';
$path = strtok($path, '?');          // odetnij ewentualny query string
$path = ltrim($path, '/');
$path = rtrim($path, '/');

$served = 'fallback';
$adId   = null;

// Tylko strona ogłoszenia ma sens wzbogacać per-rekord:
// powierzchnia-reklamowa/{typ}/{miasto}/{slug}-{id}
if (preg_match('#^powierzchnia-reklamowa/[^/]+/[^/]+/.+-(\d+)$#', $path, $m)) {
    $adId = (int) $m[1];
    $ad   = fetchAd($adId);

    if ($ad !== null && isAdPublic($ad)) {
        $indexHtml = injectAdMeta($indexHtml, $ad, $path);
        $served = 'og';
    }
}
// Strona artykułu bloga: blog/{kategoria}/{slug} lub legacy blog/{slug}.
// API (BlogController::show) zwraca 404 dla draftów/nieistniejących → null → baseline.
elseif (preg_match('#^blog/(?:[a-z0-9-]+/)?([a-z0-9-]+)$#', $path, $mb)) {
    $post = fetchBlogPost($mb[1]);
    if ($post !== null) {
        $indexHtml = injectBlogMeta($indexHtml, $post, $path);
        $served = 'og-blog';
    }
}

logLine($path, $adId, $served);
echo $indexHtml;
exit;

// ============================ FUNKCJE =========================================

/** GET /listings/{id} z nagłówkiem X-App-Key. Null przy błędzie/timeout. */
function fetchAd(int $id): ?array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => API_BASE . '/listings/' . $id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,          // scraper nie może wisieć
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
    // API może zwracać { data: {...} } albo płaski obiekt
    return isset($json['data']) && is_array($json['data']) ? $json['data'] : $json;
}

/** Nie wzbogacaj usuniętych/nieaktywnych — wtedy baseline (jak 404-meta we froncie). */
function isAdPublic(array $ad): bool
{
    $active = $ad['is_active'] ?? true;
    $status = $ad['status'] ?? 'active';
    return ($active === true || $active === 1 || $active === '1') && $status === 'active';
}

/** Zbuduj og:/twitter/title/description z pól ogłoszenia i wstrzyknij do <head>. */
function injectAdMeta(string $html, array $ad, string $path): string
{
    $type  = (string) ($ad['type'] ?? 'other');
    $city  = trim((string) ($ad['city'] ?? ''));
    $label = TYPE_LABELS[$type] ?? ucfirst($type);        // etykiety już są z dużej

    // title: "{Typ} {Miasto} – wynajem | ReklaMap" (jak AdDetailPage)
    $title = trim("$label $city") . ' – wynajem | ReklaMap';

    // description: "{Typ} w {Miasto}( (lokalizacja)). Wymiary: ... Cena: X PLN. ..."
    $rawLoc = trim((string) ($ad['location'] ?? ''));
    $locForDesc = ($rawLoc !== '' && mb_strtolower($rawLoc) !== mb_strtolower($city))
        ? ' (' . truncateAtWord($rawLoc, 60) . ')'
        : '';
    $dimsSentence = '';
    $dims = formatDimsForSeo($type, $ad['width'] ?? null, $ad['height'] ?? null);
    if ($dims !== null) {
        $dimsSentence = " Wymiary: $dims.";
    }
    $price = formatPriceVal($ad['price'] ?? 0);
    $description = "$label w $city$locForDesc.$dimsSentence Cena: $price PLN. "
        . 'Sprawdź dokładną lokalizację na mapie i skontaktuj się bezpośrednio z wystawcą — bez prowizji i pośredników.';

    // og:image — pierwsze zdjęcie ogłoszenia, fallback statyczny baner
    $image = OG_FALLBACK_IMAGE;
    if (!empty($ad['images']) && is_array($ad['images']) && !empty($ad['images'][0])) {
        $image = fullImageUrl((string) $ad['images'][0]);
    } elseif (!empty($ad['image_url'])) {
        $image = fullImageUrl((string) $ad['image_url']);
    }

    $url = APP_URL . '/' . $path;

    // Title (treść elementu, nie atrybut)
    $html = preg_replace('#<title>[^<]*</title>#i',
        '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>', $html, 1);

    // Nadpisz/wstaw meta (działa na statycznych defaultach z index.html i bez nich)
    $html = setMeta($html, 'name', 'description', $description);
    $html = setMeta($html, 'property', 'og:title', $title);
    $html = setMeta($html, 'property', 'og:description', $description);
    $html = setMeta($html, 'property', 'og:type', 'product');
    $html = setMeta($html, 'property', 'og:url', $url);
    $html = setMeta($html, 'property', 'og:image', $image);
    $html = setMeta($html, 'property', 'og:image:width', '1200');
    $html = setMeta($html, 'property', 'og:image:height', '630');
    $html = setMeta($html, 'name', 'twitter:card', 'summary_large_image');
    $html = setMeta($html, 'name', 'twitter:title', $title);
    $html = setMeta($html, 'name', 'twitter:description', $description);
    $html = setMeta($html, 'name', 'twitter:image', $image);

    return $html;
}

/** GET /blog/{slug} z nagłówkiem X-App-Key. Null przy błędzie/404 (draft/nieistniejący). */
function fetchBlogPost(string $slug): ?array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => API_BASE . '/blog/' . rawurlencode($slug),
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
    return is_array($json) ? $json : null;
}

/** og:/twitter dla artykułu bloga — lustrzane wobec BlogPostPage.vue (seoOptions).
 *  Pola z BlogController::show: title, excerpt, image (pełny URL lub null), dateIso, dateModifiedIso. */
function injectBlogMeta(string $html, array $post, string $path): string
{
    $title       = trim((string) ($post['title'] ?? '')) . ' | Blog ReklaMap';
    $description = trim((string) ($post['excerpt'] ?? ''));
    $image       = !empty($post['image']) ? (string) $post['image'] : OG_FALLBACK_IMAGE;
    $url         = APP_URL . '/' . $path;

    $html = preg_replace('#<title>[^<]*</title>#i',
        '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>', $html, 1);

    $html = setMeta($html, 'name', 'description', $description);
    $html = setMeta($html, 'property', 'og:title', $title);
    $html = setMeta($html, 'property', 'og:description', $description);
    $html = setMeta($html, 'property', 'og:type', 'article');
    $html = setMeta($html, 'property', 'og:url', $url);
    $html = setMeta($html, 'property', 'og:image', $image);
    // Wymiary deklarujemy tylko dla znanego fallbacku (1200×630). Realne hero o nieznanych
    // wymiarach zostawiamy scraperowi do wykrycia — lepiej brak niż błędne wartości.
    if ($image === OG_FALLBACK_IMAGE) {
        $html = setMeta($html, 'property', 'og:image:width', '1200');
        $html = setMeta($html, 'property', 'og:image:height', '630');
    }
    if (!empty($post['dateIso'])) {
        $html = setMeta($html, 'property', 'article:published_time', (string) $post['dateIso']);
    }
    if (!empty($post['dateModifiedIso'])) {
        $html = setMeta($html, 'property', 'article:modified_time', (string) $post['dateModifiedIso']);
    }
    $html = setMeta($html, 'name', 'twitter:card', 'summary_large_image');
    $html = setMeta($html, 'name', 'twitter:title', $title);
    $html = setMeta($html, 'name', 'twitter:description', $description);
    $html = setMeta($html, 'name', 'twitter:image', $image);

    return $html;
}

/** Nadpisuje <meta {attr}="{key}" content="..."> albo wstawia go przed </head>. */
function setMeta(string $html, string $attr, string $key, string $value): string
{
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    // content="..."> — [^"]* łapie też nowe linie (index.html łamie description na 2 linie)
    $pattern = '#<meta\s+' . preg_quote($attr, '#') . '="' . preg_quote($key, '#')
        . '"\s+content="[^"]*"\s*/?>#i';
    $tag = '<meta ' . $attr . '="' . $key . '" content="' . $value . '" />';

    if (preg_match($pattern, $html)) {
        return preg_replace($pattern, $tag, $html, 1);
    }
    return str_replace('</head>', '  ' . $tag . "\n</head>", $html);
}

/** Wymiary do SEO: LED w mm (w bazie metry), reszta w m. Null gdy brak. */
function formatDimsForSeo(string $type, $width, $height): ?string
{
    if (empty($width) || empty($height)) {
        return null;
    }
    $w = (float) $width;
    $h = (float) $height;
    if ($type === 'led_screen') {
        return round($w * 1000) . '×' . round($h * 1000) . 'mm';
    }
    return stripZeros($w) . '×' . stripZeros($h) . 'm';
}

/** 5.00 → "5", 5.50 → "5.5" (jak formatDim). */
function stripZeros(float $n): string
{
    $s = rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    return $s === '' ? '0' : $s;
}

/** 9500 → "9 500" (jak formatPrice: round + separator tysięcy pl-PL). */
function formatPriceVal($price): string
{
    return number_format((int) round((float) $price), 0, '', ' ');
}

/** Względna ścieżka zdjęcia → pełny URL (jak getFullImageUrl). */
function fullImageUrl(string $path): string
{
    if ($path === '') {
        return OG_FALLBACK_IMAGE;
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    return STORAGE_URL . '/' . ltrim($path, '/');
}

/** Przytnij na granicy słowa (kopia truncateAtWord z utils/text.ts). */
function truncateAtWord(string $text, int $maxLen, string $ellipsis = '…'): string
{
    $trimmed = trim($text);
    if (mb_strlen($trimmed) <= $maxLen) {
        return $trimmed;
    }
    $slice = mb_substr($trimmed, 0, $maxLen);
    $lastSpace = mb_strrpos($slice, ' ');
    $base = ($lastSpace !== false && $lastSpace > $maxLen * 0.6)
        ? mb_substr($slice, 0, $lastSpace)
        : $slice;
    $base = preg_replace('/[\s.,;:–-]+$/u', '', $base);
    return $base . $ellipsis;
}

function logLine(string $path, ?int $adId, string $served): void
{
    $line = date('Y-m-d H:i:s')
        . ' | IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '-')
        . ' | UA: ' . ($_SERVER['HTTP_USER_AGENT'] ?? '-')
        . ' | Path: /' . $path
        . ' | AdId: ' . ($adId ?? '-')
        . ' | Served: ' . $served
        . "\n";
    @file_put_contents('/tmp/og_meta_debug.log', $line, FILE_APPEND | LOCK_EX);
}
