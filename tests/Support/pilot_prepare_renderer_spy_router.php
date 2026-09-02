<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require $root . '/app/PilotHttp/production-entrypoint.php';

use FMonitor2\PilotHttp\PrepareFormRenderer;
use FMonitor2\PilotHttp\ProcessEnvironmentSource;
use FMonitor2\PilotHttp\ProductionPilotHttpEntrypointFactory;

require __DIR__ . '/PrepareRendererInvocationSpy.php';

$counter = getenv('FMONITOR_TEST_PREPARE_RENDER_COUNTER');
$decorateCounter = getenv('FMONITOR_TEST_PREPARE_DECORATE_COUNTER');
if (!is_string($counter) || !is_file($counter) || is_link($counter)
    || !is_string($decorateCounter) || !is_file($decorateCounter) || is_link($decorateCounter)) {
    throw new RuntimeException('invalid renderer spy counter');
}

$principal = getenv('REMOTE_USER');
if ($principal !== false) {
    $_SERVER['REMOTE_USER'] = $principal;
}

$environment = new ProcessEnvironmentSource();
$decorator = new PrepareRendererInvocationSpyDecorator($decorateCounter, $counter, true);
$entrypoint = ProductionPilotHttpEntrypointFactory::create($environment, $decorator);
$response = $entrypoint->handle($_SERVER);

http_response_code($response->status);
header_remove('X-Powered-By');
header_remove('Server');
foreach ($response->headers as $name => $value) {
    header($name . ': ' . $value);
}
echo $response->body;
