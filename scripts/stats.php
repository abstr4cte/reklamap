<?php

/**
 * Pobiera statystyki platformy ReklaMap z produkcji i zapisuje jako plik Markdown.
 *
 * Użycie:
 *   php scripts/stats.php
 *   php scripts/stats.php --days=30
 *
 * Wymagania: PHP z rozszerzeniem curl, plik backend/.env z INTERNAL_APP_KEY
 * Wynik: reklamap-os/stats/stats-YYYY-MM-DD.md
 */

// ── Konfiguracja ──────────────────────────────────────────────────────────────

define('PROD_URL', 'https://api.reklamap.pl');
define('SEED_EMAIL', 'test@test.pl');
define('OUTPUT_DIR', __DIR__ . '/../reklamap-os/stats');
define('ENV_FILE', __DIR__ . '/../backend/.env');

// ── Argumenty ─────────────────────────────────────────────────────────────────

$opts   = getopt('', ['days::', 'url::']);
$days   = isset($opts['days']) ? max(1, min(90, (int) $opts['days'])) : 7;
$prodUrl = rtrim($opts['url'] ?? PROD_URL, '/');

// ── Helpers ───────────────────────────────────────────────────────────────────

function readEnvKey(string $file, string $key): string
{
    if (!file_exists($file)) {
        die("Błąd: nie znaleziono pliku {$file}\n");
    }
    $contents = file_get_contents($file);
    if (preg_match('/^' . preg_quote($key, '/') . '=(.+)$/m', $contents, $m)) {
        return trim($m[1], " \t\r\n\"'");
    }
    die("Błąd: brak klucza {$key} w pliku {$file}\n");
}

function apiGet(string $url, string $appKey): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["X-App-Key: {$appKey}", 'Accept: application/json'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        die("Błąd curl (GET {$url}): {$error}\n");
    }
    if ($status !== 200) {
        die("Błąd HTTP {$status} dla: {$url}\n");
    }

    return json_decode($response, true) ?? [];
}

function apiPost(string $url, string $appKey, array $body): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_HTTPHEADER     => [
            "X-App-Key: {$appKey}",
            'Accept: application/json',
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        die("Błąd curl (POST {$url}): {$error}\n");
    }
    if ($status !== 200) {
        die("Błąd HTTP {$status} dla: {$url}\n");
    }

    return json_decode($response, true) ?? [];
}

// ── 1. Wczytaj klucz ─────────────────────────────────────────────────────────

$appKey = readEnvKey(ENV_FILE, 'INTERNAL_APP_KEY');
echo "Klucz wczytany. Pobieranie ogłoszeń...\n";

// ── 2. Pobierz wszystkie ogłoszenia (paginacja) ───────────────────────────────

$allAds  = [];
$page    = 1;
$perPage = 200;

do {
    $url  = $prodUrl . "/api/listings?per_page={$perPage}&page={$page}";
    $data = apiGet($url, $appKey);

    if (empty($data['data'])) {
        break;
    }

    $allAds    = array_merge($allAds, $data['data']);
    $lastPage  = $data['last_page'] ?? 1;
    $currentPage = $data['current_page'] ?? $page;

    echo "  Strona {$currentPage}/{$lastPage} — pobrano " . count($data['data']) . " ogłoszeń\n";

    $page++;
} while ($currentPage < $lastPage);

echo "Łącznie pobrano: " . count($allAds) . " ogłoszeń\n";

// ── 3. Pobierz statystyki (batche po 200 ID) ──────────────────────────────────

echo "Pobieranie statystyk (ostatnie {$days} dni)...\n";

$allIds    = array_column($allAds, 'id');
$statsByAd = [];

foreach (array_chunk($allIds, 200) as $batch) {
    $url     = $prodUrl . '/api/listings/daily-stats/multiple';
    $results = apiPost($url, $appKey, ['advertisement_ids' => $batch, 'days' => $days]);

    foreach ($results as $result) {
        $adId = $result['advertisement_id'];
        $totalViews  = 0;
        $totalEmail  = 0;
        $totalPhone  = 0;
        foreach ($result['stats'] ?? [] as $stat) {
            $totalViews += $stat['views'] ?? 0;
            $totalEmail += $stat['email_clicks'] ?? 0;
            $totalPhone += $stat['phone_clicks'] ?? 0;
        }
        $statsByAd[$adId] = [
            'views'        => $totalViews,
            'email_clicks' => $totalEmail,
            'phone_clicks' => $totalPhone,
        ];
    }
}

// ── 4. Podziel seed vs realne ─────────────────────────────────────────────────

$realAds = array_filter($allAds, fn($ad) => ($ad['owner_email'] ?? '') !== SEED_EMAIL);
$seedAds = array_filter($allAds, fn($ad) => ($ad['owner_email'] ?? '') === SEED_EMAIL);

$now        = new DateTimeImmutable();
$ago7days   = $now->modify('-7 days');
$ago30days  = $now->modify('-30 days');

$newReal7  = count(array_filter($realAds, fn($ad) => !empty($ad['created_at']) && new DateTimeImmutable($ad['created_at']) >= $ago7days));
$newReal30 = count(array_filter($realAds, fn($ad) => !empty($ad['created_at']) && new DateTimeImmutable($ad['created_at']) >= $ago30days));

$withImage = count(array_filter($allAds, fn($ad) => !empty($ad['has_image'])));
$active    = count(array_filter($allAds, fn($ad) => ($ad['status'] ?? '') === 'active'));

// Po typach
$byType = [];
foreach ($allAds as $ad) {
    $type = $ad['type'] ?? 'inne';
    $byType[$type] = ($byType[$type] ?? 0) + 1;
}
arsort($byType);

// Po miastach (top 10)
$byCity = [];
foreach ($allAds as $ad) {
    $city = $ad['city'] ?? 'nieznane';
    $byCity[$city] = ($byCity[$city] ?? 0) + 1;
}
arsort($byCity);
$byCity = array_slice($byCity, 0, 10, true);

// ── 5. Statystyki zbiorcze (tylko realne ogłoszenia) ─────────────────────────

$totalViews = 0;
$totalEmail = 0;

$adsWithStats = [];
foreach ($realAds as $ad) {
    $id    = $ad['id'];
    $views = $statsByAd[$id]['views'] ?? 0;
    $email = $statsByAd[$id]['email_clicks'] ?? 0;
    $totalViews += $views;
    $totalEmail += $email;

    if ($views > 0) {
        $adsWithStats[] = [
            'title'        => $ad['title'],
            'city'         => $ad['city'] ?? '',
            'views'        => $views,
            'email_clicks' => $email,
            'has_image'    => !empty($ad['has_image']),
        ];
    }
}

// Top 5
usort($adsWithStats, fn($a, $b) => $b['views'] - $a['views']);
$top5 = array_slice($adsWithStats, 0, 5);

// Alerty: >= 30 odsłon, 0 zapytań
$coldAds = array_filter($adsWithStats, fn($a) => $a['views'] >= 30 && $a['email_clicks'] === 0);
usort($coldAds, fn($a, $b) => $b['views'] - $a['views']);

$avgDaily = $days > 0 ? round($totalViews / $days) : 0;

// ── 6. Zbuduj Markdown ────────────────────────────────────────────────────────

$total    = count($allAds);
$realCount = count($realAds);
$seedCount = count($seedAds);
$noImage  = $total - $withImage;
$imgPct   = $total > 0 ? round($withImage / $total * 100) : 0;
$dateFrom = $now->modify("-{$days} days")->format('Y-m-d');
$dateNow  = $now->format('Y-m-d');

$md   = [];
$md[] = "# ReklaMap - Snapshot statystyk";
$md[] = "**Wygenerowano:** {$now->format('Y-m-d H:i')} | **Zakres:** ostatnie {$days} dni ({$dateFrom} - {$dateNow})";
$md[] = "";
$md[] = "## Ogłoszenia";
$md[] = "";
$md[] = "| | |";
$md[] = "|---|---|";
$md[] = "| Wszystkich | **{$total}** |";
$md[] = "| Aktywnych | **{$active}** |";
$md[] = "| Realnych (nie seed) | **{$realCount}** |";
$md[] = "| Startowych (seed) | **{$seedCount}** |";
$md[] = "| Nowe realne (7 dni) | **{$newReal7}** |";
$md[] = "| Nowe realne (30 dni) | **{$newReal30}** |";
$md[] = "| Ze zdjęciami | **{$withImage}** ({$imgPct}%) |";
$md[] = "| Bez zdjęć | **{$noImage}** |";
$md[] = "";
$md[] = "### Po typach";
$md[] = "";
$md[] = "| Typ | Liczba |";
$md[] = "|---|---|";
foreach ($byType as $type => $count) {
    $md[] = "| {$type} | {$count} |";
}
$md[] = "";
$md[] = "### Po miastach (top 10)";
$md[] = "";
$md[] = "| Miasto | Liczba |";
$md[] = "|---|---|";
foreach ($byCity as $city => $count) {
    $md[] = "| {$city} | {$count} |";
}
$md[] = "";
$md[] = "---";
$md[] = "";
$md[] = "## Wyświetlenia (ostatnie {$days} dni) — tylko realne ogłoszenia";
$md[] = "";
$md[] = "| | |";
$md[] = "|---|---|";
$md[] = "| Łącznie odsłon | **" . number_format($totalViews, 0, ',', ' ') . "** |";
$md[] = "| Średnio dziennie | **{$avgDaily}** |";
$md[] = "| Zapytań przez formularz | **{$totalEmail}** |";
$md[] = "";

if (empty($adsWithStats)) {
    $md[] = "*Brak danych — nie ma jeszcze realnych ogłoszeń z wyświetleniami.*";
} else {
    $md[] = "### Top 5 ogłoszeń";
    $md[] = "";
    $md[] = "| Ogłoszenie | Miasto | Odsłony | Zapytania |";
    $md[] = "|---|---|---|---|";
    foreach ($top5 as $ad) {
        $md[] = "| {$ad['title']} | {$ad['city']} | {$ad['views']} | {$ad['email_clicks']} |";
    }
}

if (!empty($coldAds)) {
    $md[] = "";
    $md[] = "---";
    $md[] = "";
    $md[] = "## Alerty - dużo odsłon, zero zapytań";
    $md[] = "";
    $md[] = "| Ogłoszenie | Miasto | Odsłony | Zdjęcie |";
    $md[] = "|---|---|---|---|";
    foreach ($coldAds as $ad) {
        $img  = $ad['has_image'] ? 'tak' : '**BRAK**';
        $md[] = "| {$ad['title']} | {$ad['city']} | {$ad['views']} | {$img} |";
    }
}

// ── 7. Zapisz plik ────────────────────────────────────────────────────────────

if (!is_dir(OUTPUT_DIR)) {
    mkdir(OUTPUT_DIR, 0755, true);
}

$filename = OUTPUT_DIR . '/stats-' . $now->format('Y-m-d') . '.md';
file_put_contents($filename, implode("\n", $md) . "\n");

echo "Zapisano: reklamap-os/stats/stats-" . $now->format('Y-m-d') . ".md\n";
