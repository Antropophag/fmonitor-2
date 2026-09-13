<?php
declare(strict_types=1);

// The CLI owns this single-user fictional loopback identity, never request headers.
$actor = getenv('FMONITOR_AUTH_USER_ID');
$csrf = getenv('FMONITOR_AUTH_CSRF');
$nonce = getenv('FMONITOR_DEMO_LOOPBACK_NONCE');
if (PHP_SAPI !== 'cli-server' || getenv('FMONITOR_DEMO_LOOPBACK') !== '1'
    || !is_string($actor) || preg_match('/^[1-9][0-9]*$/D', $actor) !== 1
    || !is_string($csrf) || preg_match('/^[0-9a-f]{64}$/D', $csrf) !== 1
    || !is_string($nonce) || preg_match('/^[0-9a-f]{32}$/D', $nonce) !== 1) {
    http_response_code(503); exit("Service unavailable.\n");
}
// Retain the launcher's exact loopback Host/Origin boundary for current commands.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $host = getenv('FMONITOR_TRUSTED_REQUEST_HOST');
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    if (!is_string($host) || preg_match('/^127\.0\.0\.1:[1-9][0-9]{3,4}$/D', $host) !== 1
        || ($_SERVER['HTTP_HOST'] ?? null) !== $host || ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? null) !== 'same-origin'
        || ($origin !== 'http://' . $host && $origin !== 'null')) {
        http_response_code(403); header('Content-Type: text/plain; charset=UTF-8');
        header('X-Content-Type-Options: nosniff'); header('Cache-Control: no-store'); exit("Invalid request.\n");
    }
}
$_SERVER['FMONITOR_AUTH_USER_ID'] = $actor;
$_SERVER['FMONITOR_AUTH_CSRF'] = $csrf;
$principal = getenv('REMOTE_USER');
$trustedHost = getenv('FMONITOR_TRUSTED_REQUEST_HOST');
if (!is_string($principal) || $principal === '' || !is_string($trustedHost) || $trustedHost === '') {
    http_response_code(503); exit("Service unavailable.\n");
}
$_SERVER['REMOTE_USER'] = $principal;
$_SERVER['FMONITOR_DEMO_LOOPBACK_NONCE'] = $nonce;
$_SERVER['FMONITOR_DEMO_TRUSTED_REQUEST_HOST'] = $trustedHost;
require __DIR__ . '/native-router.php';
