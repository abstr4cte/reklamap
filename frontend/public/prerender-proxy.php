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
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$logLine = date('Y-m-d H:i:s') . ' | IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '-')
    . ' | UA: ' . ($_SERVER['HTTP_USER_AGENT'] ?? '-')
    . ' | URL: ' . $url
    . ' | HTTP: ' . $httpCode
    . ' | Size: ' . strlen($response ?: '')
    . ' | Err: ' . ($curlError ?: 'none')
    . "\n";
@file_put_contents('/tmp/prerender_debug.log', $logLine, FILE_APPEND | LOCK_EX);

http_response_code($httpCode);
header('Content-Type: text/html; charset=UTF-8');
echo $response;
