<?php

declare(strict_types=1);

// Isolated HTTP-process adapter for INSPECTION-PLANNING-SCHEMA-001 Gate 2.
// It invokes the real rapid-pilot handlers after authentication has already
// supplied the same public request attributes that rapid-pilot/router.php uses.

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/rapid-pilot/ObjectDetails.php';
require_once dirname(__DIR__, 2) . '/rapid-pilot/Otiz.php';
require_once dirname(__DIR__, 2) . '/rapid-pilot/Calendar.php';
require_once dirname(__DIR__, 2) . '/rapid-pilot/Shell.php';
require_once dirname(__DIR__, 2) . '/rapid-pilot/ObjectQueue.php';
require_once dirname(__DIR__, 2) . '/rapid-pilot/CompletionFlow.php';
require_once dirname(__DIR__, 2) . '/rapid-pilot/InspectionSchedule.php';
require_once dirname(__DIR__, 2) . '/app/PilotHttp/ConstructionControlView.php';
require_once dirname(__DIR__, 2) . '/app/PilotHttp/ProductionPilotHttpEntrypointFactory.php';
$_SERVER['FMONITOR_AUTH_USER_ID'] = '901';
$_SERVER['FMONITOR_AUTH_CSRF'] = 'inspection-planning-csrf';
$_SERVER['REMOTE_USER'] = 'inspection-planning@example.invalid';
$path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);

if (RapidPilotInspectionSchedule::matches($path)) RapidPilotInspectionSchedule::handle($path);
if (RapidPilotObjectQueue::matches($path)) RapidPilotObjectQueue::handle();
if (RapidPilotCalendar::matches($path)) (new RapidPilotCalendar())->handle();

if ($path === '/pilot/construction-control') {
    $entrypoint = FMonitor2\PilotHttp\ProductionPilotHttpEntrypointFactory::create(
        new FMonitor2\PilotHttp\ProcessEnvironmentSource(),
    );
    $response = $entrypoint->handle($_SERVER);
    $body = $response->body;
    if ($response->status === 200 && str_starts_with((string) ($response->headers['Content-Type'] ?? ''), 'text/html')) {
        $body = RapidPilotInspectionSchedule::enhanceControl($body);
    }
    http_response_code($response->status);
    foreach ($response->headers as $name => $value) header($name . ': ' . $value);
    echo $body;
    exit;
}

http_response_code(404);
echo "Not found\n";
