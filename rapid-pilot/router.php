<?php

declare(strict_types=1);

require_once __DIR__ . '/ObjectDetails.php';
require_once __DIR__ . '/LocalAuth.php';
require_once __DIR__ . '/Otiz.php';
require_once __DIR__ . '/Calendar.php';
require_once __DIR__ . '/Shell.php';
require_once __DIR__ . '/ObjectQueue.php';
require_once __DIR__ . '/CompletionFlow.php';
require_once __DIR__ . '/InspectionSchedule.php';
require_once __DIR__ . '/UserAccessView.php';require_once dirname(__DIR__) . '/app/PilotHttp/PilotRouteCsp.php';require_once dirname(__DIR__) . '/app/PilotHttp/PilotRouteAdmission.php';\FMonitor2\PilotHttp\PilotRouteCsp::installDirectHeaderPolicy();
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$host = (string) ($_SERVER['HTTP_HOST'] ?? '');
if ($path === false || !is_string($path) || preg_match('/[\x00-\x1f\x7f]/', rawurldecode($path)) === 1 || preg_match('/^[A-Za-z0-9.-]+(?::[1-9][0-9]{0,4})?$/D', $host) !== 1) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Length: 13');
    header('Cache-Control: no-store');
    echo "Bad request.\n";
    exit;
}
if ($path === '/') {
    header('Location: /pilot/objects', true, 302);
    header('Cache-Control: no-store');
    exit;
}
if ($path === '/favicon.ico' || $path === '/pilot/assets/favicon.svg') {
    $bytes = file_get_contents(__DIR__ . '/favicon.svg');
    if (!is_string($bytes)) { http_response_code(404); exit; }
    header('Content-Type: image/svg+xml; charset=UTF-8');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: public, max-age=31536000, immutable');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}
if ($path === '/pilot/assets/shlz.css' || $path === '/pilot/assets/pilot.css' || $path === '/pilot/assets/pilot-20260829-22.css' || $path === '/pilot/assets/pilot-20260829-23.css') {
    $shlzRoot = getenv('FMONITOR_SHLZ_UI_ROOT') ?: dirname(__DIR__, 2) . '/shlz-ui';
    $file = $path === '/pilot/assets/shlz.css'
        ? $shlzRoot . '/packages/styles/dist/shlz.css'
        : __DIR__ . '/pilot.css';
    $bytes = file_get_contents($file);
    if (!is_string($bytes)) { http_response_code(404); exit; }
    header('Content-Type: text/css; charset=UTF-8');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}
if ($path === '/pilot/assets/shlz-calendar-grid.js') {
    $shlzRoot = getenv('FMONITOR_SHLZ_UI_ROOT') ?: dirname(__DIR__, 2) . '/shlz-ui';
    $file = $shlzRoot . '/packages/behaviors/dist/calendar-grid.js';
    $bytes = file_get_contents($file);
    if (!is_string($bytes)) { http_response_code(503); header('Content-Type: text/plain; charset=UTF-8'); echo "Configured shlz-ui does not export Calendar Grid behavior. Set FMONITOR_SHLZ_UI_ROOT to a compatible public shlz-ui checkout.\n"; exit; }
    header('Content-Type: text/javascript; charset=UTF-8'); header('Content-Length: '.strlen($bytes)); header('Cache-Control: no-store'); header('X-Content-Type-Options: nosniff'); echo $bytes; exit;
}
if ($path === '/pilot/assets/shlz-icons.svg') {
    $shlzRoot = getenv('FMONITOR_SHLZ_UI_ROOT') ?: dirname(__DIR__, 2) . '/shlz-ui';
    $bytes = file_get_contents($shlzRoot . '/packages/icons/dist/sprite.svg');
    if (!is_string($bytes)) { http_response_code(503); header('Content-Type: text/plain; charset=UTF-8'); echo "Configured shlz-ui does not export its icon sprite. Set FMONITOR_SHLZ_UI_ROOT to a compatible public shlz-ui checkout.\n"; exit; }
    header('Content-Type: image/svg+xml; charset=UTF-8');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}
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
if ($path === '/pilot/assets/shlz-behaviors.js') {
    $bytes = file_get_contents(dirname(__DIR__, 2) . '/shlz-ui/packages/behaviors/dist/browser.js');
    if (!is_string($bytes)) { http_response_code(404); exit; }
    header('Content-Type: text/javascript; charset=UTF-8');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: public, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}
if (is_string($path) && preg_match('#^/pilot/assets/(checklist(?:-sw)?|picker|users|control-queue|navigation)\.js$#D', $path, $script) === 1) {
    $filename = $script[1] . '.js';
    $bytes = file_get_contents(dirname(__DIR__) . '/app/PilotHttp/' . $filename);
    if (!is_string($bytes)) { http_response_code(404); exit; }
    if ($filename === 'checklist.js') {
        $broken = 'request.onerror=()=>{paintSync("error","Локальное хранилище недоступно");restoreSection()}';
        $fixed = 'request.onerror=()=>{paintSync("error","Локальное хранилище недоступно");render();restoreSection()}';
        if (substr_count($bytes, $broken) !== 1) { http_response_code(503); exit; }
        $bytes = str_replace($broken, $fixed, $bytes);
    }
    if ($filename === 'users.js') $bytes = str_replace("(filter==='uf-blocked'&&row.classList.contains('fm2-user-row--blocked'))", "(filter==='uf-blocked'&&row.classList.contains('fm2-user-row--blocked'))||(filter==='uf-invited'&&row.classList.contains('fm2-user-row--invited'))", $bytes);
    header('Content-Type: text/javascript; charset=UTF-8');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    if ($filename === 'checklist-sw.js') {
        header('Service-Worker-Allowed: /pilot/');
        header("Content-Security-Policy: default-src 'self'; connect-src 'self'");
    }
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
if ($path === '/pilot/assets/object-queue.js') {
    $bytes = file_get_contents(__DIR__ . '/object-queue.js');
    if (!is_string($bytes)) { http_response_code(404); exit; }
    header('Content-Type: text/javascript; charset=UTF-8');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}
if ($path === '/pilot/assets/installer-directory.js') {
    $bytes = file_get_contents(__DIR__ . '/installer-directory.js');
    if (!is_string($bytes)) { http_response_code(404); exit; }
    header('Content-Type: text/javascript; charset=UTF-8');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}
if ($path === '/pilot/assets/otiz.js') {
    $bytes = file_get_contents(__DIR__ . '/otiz.js');
    if (!is_string($bytes)) { http_response_code(404); exit; }
    header('Content-Type: text/javascript; charset=UTF-8');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}
if ($path === '/pilot/assets/calendar.js') {
    $bytes = file_get_contents(__DIR__ . '/calendar.js');
    if (!is_string($bytes)) { http_response_code(404); exit; }
    header('Content-Type: text/javascript; charset=UTF-8'); header('Content-Length: '.strlen($bytes)); header('Cache-Control: no-store'); header('X-Content-Type-Options: nosniff'); echo $bytes; exit;
}
if ($path === '/pilot/assets/inspection-schedule.js') {
    $bytes = file_get_contents(__DIR__ . '/inspection-schedule.js');
    if (!is_string($bytes)) { http_response_code(404); exit; }
    header('Content-Type: text/javascript; charset=UTF-8'); header('Content-Length: '.strlen($bytes)); header('Cache-Control: no-store'); header('X-Content-Type-Options: nosniff'); echo $bytes; exit;
}
if ($path === '/pilot/assets/preloader.js') {
    $bytes = file_get_contents(__DIR__ . '/preloader.js');
    if (!is_string($bytes)) { http_response_code(404); exit; }
    header('Content-Type: text/javascript; charset=UTF-8');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}
if ($path === '/pilot/assets/invite.js') {
    $bytes = file_get_contents(__DIR__ . '/invite.js');
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
if (is_string($path) && str_starts_with($path, '/pilot/assets/')) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Length: 11');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo "Not found.\n";
    exit;
}
\FMonitor2\PilotHttp\PilotRouteAdmission::rejectIfUnknown((string)$path,RapidPilotInspectionSchedule::matches((string)$path)||RapidPilotCompletionFlow::matches((string)$path)||RapidPilotCompletionFlow::blocksLegacyCompletion((string)$path)||RapidPilotObjectQueue::matches((string)$path)||RapidPilotCalendar::matches((string)$path)||RapidPilotOtiz::matches((string)$path));try {
    (new RapidPilotLocalAuth())->handle(is_string($path) ? $path : '/');
} catch (Throwable) {
    $body = "Service unavailable.\n";
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Length: ' . strlen($body));
    header('Retry-After: 60');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header_remove('Set-Cookie');
    header_remove('Location');
    header_remove('X-Powered-By');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') echo $body;
    exit;
}
if (is_string($path) && preg_match('#^/pilot/admin/users/([1-9][0-9]*)/status$#D', $path, $statusRoute) === 1) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); header('Allow: POST'); exit; }
    RapidPilotUserAccessView::handleStatus((int) $statusRoute[1]);
}
if (is_string($path) && RapidPilotInspectionSchedule::matches($path)) RapidPilotInspectionSchedule::handle($path);
if (is_string($path) && RapidPilotCompletionFlow::matches($path)) RapidPilotCompletionFlow::handle($path);
if (is_string($path) && RapidPilotCompletionFlow::blocksLegacyCompletion($path)) exit;
if (is_string($path) && RapidPilotObjectQueue::matches($path)) RapidPilotObjectQueue::handle();
if (is_string($path) && RapidPilotCalendar::matches($path)) {
    require_once dirname(__DIR__) . '/app/PilotHttp/PilotHttp.php';
    require_once dirname(__DIR__) . '/app/PilotHttp/PilotView.php';
    (new RapidPilotCalendar())->handle();
}
if (is_string($path) && RapidPilotOtiz::matches($path)) {
    require_once dirname(__DIR__) . '/app/PilotHttp/PilotHttp.php';
    require_once dirname(__DIR__) . '/app/PilotHttp/PilotView.php';
    (new RapidPilotOtiz())->handle($path);
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
if ($path === '/pilot/admin/users/invite') $response = RapidPilotUserAccessView::invitationResponse($response);
$body = $response->body;
$headers = $response->headers;
if ($response->status === 200 && is_string($path) && str_starts_with((string) ($response->headers['Content-Type'] ?? ''), 'text/html')) {
    $body = RapidPilotUserAccessView::enhance($body, $path);
    $body = RapidPilotObjectDetails::enhance($body, $path);
    if (preg_match('#^/pilot/objects/([1-9][0-9]*)$#D', $path, $completionCard) === 1) $body = RapidPilotCompletionFlow::enhanceCard($body, (int) $completionCard[1]);
    if (preg_match('#^/pilot/objects/([1-9][0-9]*)/checklist$#D', $path, $completionChecklist) === 1) $body = RapidPilotCompletionFlow::enhanceChecklist($body, (int) $completionChecklist[1]);
    $body = RapidPilotCompletionFlow::paintStatuses($body);
    if ($path === '/pilot/construction-control') $body = RapidPilotInspectionSchedule::enhanceControl($body);
    $body = RapidPilotShell::decorate($body, (string) ($_SERVER['FMONITOR_AUTH_CSRF'] ?? ''), false, RapidPilotOtiz::currentUserCanAccess(), false);
    $body = str_replace('</head>', '<link rel="icon" type="image/svg+xml" href="/pilot/assets/favicon.svg"></head>', $body);
    $headers['Content-Length'] = (string) strlen($body);
}

http_response_code($response->status);
header_remove('X-Powered-By');
header_remove('Server');
foreach ($headers as $name => $value) header($name . ': ' . $value);
echo $body;
