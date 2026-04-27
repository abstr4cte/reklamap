<?php
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
curl_close($ch);

http_response_code($httpCode);
header('Content-Type: text/html; charset=UTF-8');
echo $response;
