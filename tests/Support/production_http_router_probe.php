<?php
declare(strict_types=1);

$_SERVER = [
    'REQUEST_METHOD' => 'GET',
    'REQUEST_URI' => '/pilot/unknown',
    'HTTP_HOST' => getenv('FMONITOR_TEST_UNTRUSTED_HTTP_HOST') ?: 'client-spoof.invalid',
    'REMOTE_USER' => 'not-used-before-unknown-route@example.test',
];

$handlerSentinel = getenv('FMONITOR_TEST_HANDLER_SENTINEL');
if (is_string($handlerSentinel) && $handlerSentinel !== '') {
    file_put_contents($handlerSentinel, json_encode([
        'trustedHost' => getenv('FMONITOR_TRUSTED_REQUEST_HOST'),
        'untrustedHttpHost' => $_SERVER['HTTP_HOST'],
    ], JSON_UNESCAPED_SLASHES) . "\n", LOCK_EX);
}

require dirname(__DIR__, 2) . '/public/router.php';
