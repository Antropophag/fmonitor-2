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
if ($generation !== 1 || $legacyPrefix !== $processPrefix || preg_match('/^[A-Za-z0-9_]{1,25}$/D', $processPrefix) !== 1) throw new RuntimeException('Pilot generation unavailable');

$port = getenv('FMONITOR_DEMO_PORT') ?: '8092';
if (preg_match('/^[1-9][0-9]{3,4}$/D', $port) !== 1) throw new RuntimeException('Invalid pilot port');
$generationRoot=$stateRoot.'/generations/'.$generation;$passwordFile=$generationRoot.'/database-password';$safeLog=$generationRoot.'/original-safe.log';$artifactRoot=$generationRoot.'/artifacts';
foreach([$passwordFile,$safeLog]as$file){$info=lstat($file);if($info===false||is_link($file)||!is_file($file)||($info['mode']&0777)!==0600)throw new RuntimeException('Pilot secret unavailable');}
$endpoint=$manifest['dbEndpoint']??null;if(!is_array($endpoint))throw new RuntimeException('Pilot database unavailable');$dbPassword=getenv('FMONITOR_DEMO_DB_PASSWORD')?:getenv('FMONITOR_DB_PASSWORD');if(!is_string($dbPassword)||$dbPassword===''||!hash_equals((string)file_get_contents($passwordFile),$dbPassword))throw new RuntimeException('Pilot database unavailable');
$environment = array_merge($_ENV, [
    'FMONITOR_DB_HOST' => (string)($endpoint['host']??''),
    'FMONITOR_DB_PORT' => (string)($endpoint['port']??''),
    'FMONITOR_DB_NAME' => (string)($endpoint['name']??''),
    'FMONITOR_DB_USER' => (string)(getenv('FMONITOR_DEMO_DB_USER')?:getenv('FMONITOR_DB_USER')),
    'FMONITOR_DB_PASSWORD' => $dbPassword,
    'FMONITOR_PROCESS_TABLE_PREFIX' => $processPrefix,
    'FMONITOR_LEGACY_TABLE_PREFIX' => $legacyPrefix,
    'FMONITOR_ARTIFACT_STORAGE_ROOT' => $artifactRoot,
    'FMONITOR_ORIGINAL_DB_PASSWORD_FILE' => $passwordFile,
    'FMONITOR_ORIGINAL_SAFE_LOG_FILE' => $safeLog,
    'FMONITOR_SESSION_STATE_ROOT' => $generationRoot,
    'FMONITOR_SESSION_INSTANCE' => 'pilot_'.$fingerprint.'_'.substr((string)$manifest['manifestNonce'],0,12),
    'FMONITOR_FRESH_ORDER_FLOW' => '1',
    'FMONITOR_LIVE_CLOCK' => '1',
    'FMONITOR_SHLZ_CSS_PATH' => (string) realpath($root . '/../shlz-ui/packages/styles/dist/shlz.css'),
    'FMONITOR_PILOT_CSS_PATH' => $root . '/rapid-pilot/pilot.css',
    'FMONITOR_NOW' => (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')))->format(DATE_ATOM),
    'FMONITOR_TRUSTED_REQUEST_HOST' => '127.0.0.1:' . $port,
    'FMONITOR_DEMO_LOOPBACK' => '1',
    'FMONITOR_DEMO_LOOPBACK_NONCE' => substr((string)$manifest['manifestNonce'],0,32),
]);
foreach ($environment as $name => $value) {
    if (!is_string($name) || (!is_string($value) && !is_numeric($value))) continue;
    putenv($name . '=' . (string) $value);
}
if (getenv('PHP_CLI_SERVER_WORKERS') === false) putenv('PHP_CLI_SERVER_WORKERS=4');

echo "FMonitor rapid pilot: http://127.0.0.1:{$port}/pilot/objects\n";
$runtimeOptions = ' -d post_max_size=28M -d upload_max_filesize=25M -d display_errors=0 -d log_errors=1';
passthru(escapeshellarg(PHP_BINARY) . $runtimeOptions . ' -S ' . escapeshellarg('127.0.0.1:' . $port) . ' ' . escapeshellarg(__DIR__ . '/router.php'), $exitCode);
exit($exitCode);
