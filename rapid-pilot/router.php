<?php

declare(strict_types=1);

require_once __DIR__ . '/ObjectDetails.php';
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
if ($path === '/pilot/assets/icons/file-pdf-default.svg' || $path === '/pilot/assets/icons/download.svg') {
    $icon = $path === '/pilot/assets/icons/file-pdf-default.svg' ? 'files/file-pdf-default.svg' : 'interface/download.svg';
    $bytes = file_get_contents(dirname(__DIR__, 2) . '/shlz-ui/packages/icons/normalized/' . $icon);
    if (!is_string($bytes)) { http_response_code(404); exit; }
    header('Content-Type: image/svg+xml; charset=UTF-8');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: public, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}
if ($path === '/pilot/assets/shlz-tabs.js') {
    $bytes = file_get_contents(dirname(__DIR__, 2) . '/shlz-ui/packages/behaviors/dist/tabs.js');
    if (!is_string($bytes)) { http_response_code(404); exit; }
    header('Content-Type: text/javascript; charset=UTF-8');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: public, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}
if ($path === '/pilot/assets/object-details.js') {
    $bytes = file_get_contents(__DIR__ . '/object-details.js');
    if (!is_string($bytes)) { http_response_code(404); exit; }
    header('Content-Type: text/javascript; charset=UTF-8');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}
if (is_string($path) && preg_match('#^/pilot/assets/fonts/(golos-text-(?:cyrillic|latin)-(?:400|500|600)-normal\.woff2)$#D', $path, $font) === 1) {
    $bytes = file_get_contents(__DIR__ . '/fonts/' . $font[1]);
    if (!is_string($bytes)) { http_response_code(404); exit; }
    header('Content-Type: font/woff2');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: public, max-age=31536000, immutable');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}
if (PHP_SAPI === 'cli-server' && !isset($_SERVER['REMOTE_USER'])) {
    $principal = getenv('REMOTE_USER');
    if (is_string($principal) && $principal !== '') $_SERVER['REMOTE_USER'] = $principal;
}
$localServerAddress = $_SERVER['SERVER_ADDR'] ?? $_SERVER['SERVER_NAME'] ?? null;
if (PHP_SAPI === 'cli-server' && $localServerAddress === '127.0.0.1') {
    $demoNonce = getenv('FMONITOR_DEMO_LOOPBACK_NONCE');
    $demoHost = getenv('FMONITOR_TRUSTED_REQUEST_HOST');
    if (getenv('FMONITOR_DEMO_LOOPBACK') === '1'
        && is_string($demoNonce) && preg_match('/^[0-9a-f]{32}$/D', $demoNonce) === 1
        && is_string($demoHost) && preg_match('/^127\.0\.0\.1:([1-9][0-9]{3,4})$/D', $demoHost, $parts) === 1
        && (int) $parts[1] >= 1024 && (int) $parts[1] <= 65535) {
        $_SERVER['FMONITOR_DEMO_LOOPBACK_NONCE'] = $demoNonce;
        $_SERVER['FMONITOR_DEMO_TRUSTED_REQUEST_HOST'] = $demoHost;
    }
}
$entrypoint = require dirname(__DIR__) . '/app/PilotHttp/production-entrypoint.php';

$response = $entrypoint->handle($_SERVER);
$body = $response->body;
$headers = $response->headers;
if ($response->status === 200 && is_string($path) && str_starts_with((string) ($response->headers['Content-Type'] ?? ''), 'text/html')) {
    $body = RapidPilotObjectDetails::enhance($body, $path);
    $headers['Content-Length'] = (string) strlen($body);
}

http_response_code($response->status);
header_remove('X-Powered-By');
header_remove('Server');
foreach ($headers as $name => $value) header($name . ': ' . $value);
echo $body;
