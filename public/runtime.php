<?php

declare(strict_types=1);

define('FMONITOR_PRODUCTION_WEB_CUTOVER', true);

header_remove('X-Powered-By');
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
if ($path === '/health/live') {
    require_once dirname(__DIR__) . '/app/autoload.php';
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    require_once dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
    (new yii\web\Application(require dirname(__DIR__) . '/config/yii/web.php'))->run();
    exit;
}

$level = ob_get_level();
ob_start();
try {
    $trustedHost = getenv('FMONITOR_TRUSTED_REQUEST_HOST');
    $receivedHost = $_SERVER['HTTP_HOST'] ?? null;
    if (!is_string($trustedHost) || !is_string($receivedHost) || !hash_equals($trustedHost, $receivedHost)) {
        unset($_SERVER['HTTP_HOST']);
        while (ob_get_level() > $level) ob_end_clean();
        http_response_code(400);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header('X-Frame-Options: DENY');
        header("Content-Security-Policy: default-src 'none'; script-src 'self'; connect-src 'self'; style-src 'self'; img-src 'self'; font-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Host: fmonitor.invalid');
        echo "{\"ok\":false,\"reason\":\"REQUEST_REJECTED\"}\n";
        exit;
    }
    require_once dirname(__DIR__) . '/app/autoload.php';
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    require_once dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';

    $configuration = FMonitor2\Runtime\RuntimeConfiguration::fromEnvironment(getenv());
    $configuration->apply();
    $cookieKey = getenv('FMONITOR_YII_COOKIE_VALIDATION_KEY');
    if (!is_string($cookieKey) || $cookieKey === '') throw new RuntimeException('CONFIGURATION_INVALID');
    FMonitor2\Runtime\RuntimeStorage::assertReady($configuration);
    FMonitor2\Runtime\MariaDbRuntimeReadiness::assertAvailable($configuration);
    unset($_SERVER['REMOTE_USER'], $_SERVER['FMONITOR_AUTH_USER_ID'], $_SERVER['FMONITOR_AUTH_CSRF']);
    $_SERVER['FMONITOR_TRUSTED_REQUEST_HOST'] = $configuration->value('FMONITOR_TRUSTED_REQUEST_HOST');
    (new yii\web\Application(require dirname(__DIR__) . '/config/yii/web.php'))->run();
} catch (Throwable $error) {
    while (ob_get_level() > $level) ob_end_clean();
    header_remove('Location');
    header_remove('Content-Length');
    header_remove('Set-Cookie');
    $status=$error instanceof \yii\web\HttpException?$error->statusCode:503;
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'none'; script-src 'self'; connect-src 'self'; style-src 'self'; img-src 'self'; font-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    if ($status === 503) header('Retry-After: 60');
    if ($status === 405) header('Allow: POST');
    $reason = match ($status) {
        404 => 'NOT_FOUND', 405 => 'METHOD_NOT_ALLOWED', 400 => 'BAD_REQUEST', 403 => 'ACCESS_DENIED',
        503 => 'SERVICE_UNAVAILABLE', default => 'REQUEST_REJECTED',
    };
    echo json_encode(['ok' => false, 'reason' => $reason], JSON_THROW_ON_ERROR) . "\n";
}
