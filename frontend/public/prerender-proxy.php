<?php
if (isset($_GET['showlog']) && $_GET['showlog'] === 'rm2024debug') {
    header('Content-Type: text/plain');
    echo @file_get_contents('/tmp/prerender_debug.log') ?: 'brak logów';
    exit;
}

$url = $_GET['url'] ?? '';
if (empty($url) || !str_starts_with($url, 'https://reklamap.pl')) {
    http_response_code(400);
    exit;
}

$token = 'BpQQORSsGLyICHuIPbuz';
$prerenderUrl = 'https://service.prerender.io/' . $url;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $prerenderUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Prerender-Token: ' . $token,
    'User-Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'bot'),
]);
curl_setopt($ch, CURLOPT_ENCODING, '');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Prerender uznajemy za udany tylko przy 2xx/3xx z niepustą treścią.
// Wszystko inne (5xx od prerender.io, timeout curl => httpCode 0, pusta odpowiedź)
// NIE może wyciekać do Googlebota jako 5xx — to powoduje deindeks i utratę pozycji.
$prerenderOk = $response !== false
    && $response !== ''
    && $httpCode >= 200
    && $httpCode < 400;

$logLine = date('Y-m-d H:i:s') . ' | IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '-')
    . ' | UA: ' . ($_SERVER['HTTP_USER_AGENT'] ?? '-')
    . ' | URL: ' . $url
    . ' | HTTP: ' . $httpCode
    . ' | Size: ' . strlen($response ?: '')
    . ' | Err: ' . ($curlError ?: 'none')
    . ' | Served: ' . ($prerenderOk ? 'prerender' : 'fallback-spa')
    . "\n";
@file_put_contents('/tmp/prerender_debug.log', $logLine, FILE_APPEND | LOCK_EX);

header('Content-Type: text/html; charset=UTF-8');

if ($prerenderOk) {
    http_response_code($httpCode);
    echo $response;
    exit;
}

// Graceful fallback: oddaj statyczny SPA z kodem 200.
// Słabsze SEO niż prerender, ale Google dostaje działającą stronę zamiast 5xx.
$fallback = @file_get_contents(__DIR__ . '/index.html');
if ($fallback !== false) {
    http_response_code(200);
    echo $fallback;
} else {
    // Ostateczność: 503 z Retry-After (a NIE goły 5xx) — sygnał „spróbuj później",
    // nie „strona martwa". W praktyce nieosiągalne, bo index.html jest obok.
    http_response_code(503);
    header('Retry-After: 3600');
}
