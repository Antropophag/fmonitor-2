<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$home = getenv('HOME');
if (!is_string($home) || $home === '') throw new RuntimeException('Home directory unavailable');
$fingerprint = substr(hash('sha256', (string) realpath($root)), 0, 8);
$stateRoot = $home . '/.local/state/fmonitor2/pilot-demo/' . $fingerprint;
$manifest = json_decode((string) file_get_contents($stateRoot . '/active.json'), true, flags: JSON_THROW_ON_ERROR);
$generation = (int) ($manifest['generation'] ?? 0);
$processPrefix = (string) ($manifest['processPrefix'] ?? '');
$legacyPrefix = (string) ($manifest['legacyPrefix'] ?? '');
if ($generation < 1 || preg_match('/^[A-Za-z0-9_]+$/D', $processPrefix) !== 1 || preg_match('/^[A-Za-z0-9_]+$/D', $legacyPrefix) !== 1) throw new RuntimeException('Pilot generation unavailable');

$port = getenv('FMONITOR_DEMO_PORT') ?: '8092';
if (preg_match('/^[1-9][0-9]{3,4}$/D', $port) !== 1) throw new RuntimeException('Invalid pilot port');
$environment = array_merge($_ENV, [
    'FMONITOR_DB_HOST' => '127.0.0.1',
    'FMONITOR_DB_PORT' => '23306',
    'FMONITOR_DB_NAME' => 'fmonitor2_demo',
    'FMONITOR_DB_USER' => 'fmonitor2_demo',
    'FMONITOR_DB_PASSWORD' => 'fmonitor2_demo_local',
    'FMONITOR_PROCESS_TABLE_PREFIX' => $processPrefix,
    'FMONITOR_LEGACY_TABLE_PREFIX' => $legacyPrefix,
    'FMONITOR_ARTIFACT_STORAGE_ROOT' => $stateRoot . '/generations/' . $generation . '/artifacts',
    'FMONITOR_SHLZ_CSS_PATH' => (string) realpath($root . '/../shlz-ui/packages/styles/dist/shlz.css'),
    'FMONITOR_PILOT_CSS_PATH' => $root . '/rapid-pilot/pilot.css',
    'FMONITOR_NOW' => (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')))->format(DATE_ATOM),
    'FMONITOR_TRUSTED_REQUEST_HOST' => '127.0.0.1:' . $port,
    'REMOTE_USER' => getenv('FMONITOR_DEMO_REMOTE_USER') ?: 'sidorov@shlz.ru',
]);
foreach ($environment as $name => $value) {
    if (!is_string($name) || (!is_string($value) && !is_numeric($value))) continue;
    putenv($name . '=' . (string) $value);
}

echo "FMonitor rapid pilot: http://127.0.0.1:{$port}/pilot/objects\n";
passthru(escapeshellarg(PHP_BINARY) . ' -S ' . escapeshellarg('127.0.0.1:' . $port) . ' ' . escapeshellarg(__DIR__ . '/router.php'), $exitCode);
exit($exitCode);
