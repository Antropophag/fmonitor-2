<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use FMonitor2\Runtime\RuntimeConfiguration;
use FMonitor2\Runtime\RuntimeReadiness;

header_remove('X-Powered-By');
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
if ($path === '/health/live') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo "{\"ok\":true}\n";
    exit;
}

$level = ob_get_level();
ob_start();
try {
    $configuration = RuntimeConfiguration::fromEnvironment(getenv());
    $configuration->apply();
    if ($path === '/health/ready') {
        RuntimeReadiness::assertReady($configuration);
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        echo "{\"ok\":true}\n";
        exit;
    }
    if (($_SERVER['HTTP_HOST'] ?? '') !== $configuration->value('FMONITOR_TRUSTED_REQUEST_HOST')) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store');
        echo "Bad request.\n";
        exit;
    }
    unset($_SERVER['REMOTE_USER'], $_SERVER['FMONITOR_AUTH_USER_ID'], $_SERVER['FMONITOR_AUTH_CSRF']);
    $_SERVER['FMONITOR_TRUSTED_REQUEST_HOST'] = $configuration->value('FMONITOR_TRUSTED_REQUEST_HOST');
    $otizRoute= preg_match('#^/pilot/otiz(?:/|$)#D', (string)$path) === 1 || $path === '/pilot/assets/otiz.js';
    if ($otizRoute) {
        require __DIR__.'/yii.php';
        exit;
    }
    require dirname(__DIR__) . '/rapid-pilot/router.php';
} catch (Throwable $error) {
    while (ob_get_level() > $level) ob_end_clean();
    header_remove('Location');
    header_remove('Content-Length');
    $status=$error instanceof \yii\web\HttpException?$error->statusCode:503;
    http_response_code($status);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo $status===503?"{\"ok\":false,\"reason\":\"SERVICE_UNAVAILABLE\"}\n":"{\"ok\":false,\"reason\":\"REQUEST_REJECTED\"}\n";
}
