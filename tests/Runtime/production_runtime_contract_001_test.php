<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__, 2) . '/app/autoload.php';

use FMonitor2\Runtime\RuntimeConfiguration;

$root = dirname(__DIR__, 2);

/** @return string */
function runtimeContractFile(string $relative): string
{
    global $root;
    $path = $root . '/' . $relative;
    assertSameValue(true, is_file($path), "INTENTIONAL_RED: required production runtime file {$relative}");
    $contents = file_get_contents($path);
    if (!is_string($contents)) {
        throw new TestFailure("SETUP_FAILURE: cannot read {$relative}");
    }
    return $contents;
}

$dockerfile = runtimeContractFile('deploy/runtime/Dockerfile');
$compose = runtimeContractFile('deploy/runtime/compose.yaml');
$nginx = runtimeContractFile('deploy/runtime/nginx.conf');
$fpm = runtimeContractFile('deploy/runtime/php-fpm.conf');
runtimeContractFile('deploy/runtime/php.ini');
$frontController = runtimeContractFile('public/runtime.php');
$configuration = runtimeContractFile('app/Runtime/RuntimeConfiguration.php');

$allRuntime = implode("\n", [$dockerfile, $compose, $nginx, $fpm, $frontController, $configuration]);
assertSameValue(false, (bool) preg_match('/\bphp\s+-S\b/i', $allRuntime), 'production runtime never uses the PHP development server');
assertSameValue(false, (bool) preg_match('/\bsocat\b/i', $allRuntime), 'production runtime never uses a TCP compatibility proxy');
assertSameValue(false, (bool) preg_match('/(?:active\.json|FMONITOR_PILOT_ACTIVE_MANIFEST|pilot-demo|fmonitor2-pilot-demo|rapid-pilot\/start\.php)/i', $allRuntime), 'production runtime has no demo manifest or demo bootstrap dependency');

assertSameValue(true, (bool) preg_match('/php-fpm[^\n]*(?:-F|--nodaemonize)/i', $dockerfile . "\n" . $compose), 'application service runs PHP-FPM in the foreground');
assertSameValue(true, str_contains($nginx, 'fastcgi_pass'), 'nginx delegates PHP requests to PHP-FPM');
assertSameValue(true, str_contains($nginx, 'public/runtime.php'), 'nginx uses the production front controller');
assertSameValue(true, (bool) preg_match('/daemonize\s*=\s*no/i', $fpm), 'PHP-FPM remains attached to the container lifecycle');
assertSameValue(false, str_contains($frontController, "rapid-pilot/router.php"), 'production front controller excludes the retired rapid router');
assertSameValue(true, str_contains($frontController, 'new yii\\web\\Application'), 'production front controller runs the Yii application directly');
assertSameValue(false, (bool) preg_match('/(?:fmonitor2-migrate|SchemaMigration|CREATE\s+TABLE|ALTER\s+TABLE)/i', $frontController), 'ordinary HTTP front controller performs no migration or DDL');

foreach (['FMONITOR_DB_HOST', 'FMONITOR_DB_PORT', 'FMONITOR_DB_NAME', 'FMONITOR_DB_USER', 'FMONITOR_DB_PASSWORD', 'FMONITOR_PROCESS_TABLE_PREFIX'] as $name) {
    assertSameValue(true, str_contains($configuration, $name), "runtime configuration explicitly owns {$name}");
    assertSameValue(true, str_contains($compose, $name), "compose passes explicit {$name}");
}

$explicit = [
    'FMONITOR_DB_HOST' => 'db.internal',
    'FMONITOR_DB_PORT' => '3307',
    'FMONITOR_DB_NAME' => 'fmonitor_live',
    'FMONITOR_DB_USER' => 'fmonitor_runtime',
    'FMONITOR_DB_PASSWORD' => 'runtime-test-secret',
    'FMONITOR_PROCESS_TABLE_PREFIX' => 'live_',
    'FMONITOR_LEGACY_TABLE_PREFIX' => 'live_',
    'FMONITOR_SESSION_STATE_ROOT' => '/var/lib/fmonitor/sessions',
    'FMONITOR_SESSION_INSTANCE' => 'production',
    'FMONITOR_ARTIFACT_STORAGE_ROOT' => '/var/lib/fmonitor/artifacts',
    'FMONITOR_ORIGINAL_DB_PASSWORD_FILE' => '/run/secrets/original-db-password',
    'FMONITOR_ORIGINAL_SAFE_LOG_FILE' => '/var/log/fmonitor/original-safe.jsonl',
    'FMONITOR_TRUSTED_REQUEST_HOST' => 'fmonitor.example.test',
    'FMONITOR_TRUSTED_REQUEST_SCHEME' => 'https',
];
foreach (array_keys($explicit) as $name) {
    assertSameValue(true, str_contains($configuration, $name), "runtime configuration explicitly owns {$name}");
    assertSameValue(true, str_contains($compose, $name), "compose passes explicit {$name}");
}
$runtimeConfiguration = RuntimeConfiguration::fromEnvironment($explicit);
$runtimeConfiguration->apply();
foreach ($explicit as $name => $value) {
    assertSameValue($value, getenv($name), "runtime applies explicit {$name}");
}
foreach ([
    ['FMONITOR_DB_PORT', '0'],
    ['FMONITOR_DB_PORT', '65536'],
    ['FMONITOR_DB_HOST', "db.internal\nINJECTED=value"],
    ['FMONITOR_PROCESS_TABLE_PREFIX', 'bad-prefix'],
    ['FMONITOR_LEGACY_TABLE_PREFIX', 'other_'],
    ['FMONITOR_SESSION_STATE_ROOT', '/../tmp/session'],
    ['FMONITOR_ORIGINAL_DB_PASSWORD_FILE', '/run/secrets/../password'],
    ['FMONITOR_TRUSTED_REQUEST_HOST', "good.test\r\nX-Injected: yes"],
    ['FMONITOR_TRUSTED_REQUEST_SCHEME', 'ftp'],
] as [$name, $value]) {
    $invalid = array_replace($explicit, [$name => $value]);
    try {
        RuntimeConfiguration::fromEnvironment($invalid);
        throw new TestFailure("{$name} invalid value must fail closed");
    } catch (RuntimeException $error) {
        assertSameValue('CONFIGURATION_INVALID', $error->getMessage(), "{$name} invalid failure is stable and secret-free");
        assertSameValue(false, str_contains($error->getMessage(), 'runtime-test-secret'), "{$name} failure does not disclose password");
    }
}
foreach (array_keys($explicit) as $missing) {
    $invalid = $explicit;
    unset($invalid[$missing]);
    try {
        RuntimeConfiguration::fromEnvironment($invalid);
        throw new TestFailure("{$missing} absence must fail closed");
    } catch (RuntimeException $error) {
        assertSameValue('CONFIGURATION_INVALID', $error->getMessage(), "{$missing} failure is stable and does not disclose configuration");
    }
}

assertSameValue(true, (bool) preg_match('/\bmigrate:/i', $compose), 'compose exposes a separate migration service');
assertSameValue(true, str_contains($compose, 'bin/yii') && str_contains($compose, 'schema-migrate/run'), 'migration service invokes the canonical Yii deployment entrypoint');
assertSameValue(true, (bool) preg_match('/(?:restart:\s*["\']?no|profiles?:)/i', $compose), 'migration command is a one-shot deployment operation');
assertSameValue(true, (bool) preg_match('/(?:volume|type:\s*volume)/i', $compose), 'runtime declares persistent state independently from the image');
assertSameValue(true, str_contains($compose, 'FMONITOR_SESSION_STATE_ROOT'), 'runtime explicitly mounts and configures durable authenticated session state');
assertSameValue(true, str_contains($dockerfile . $compose, '10001'), 'runtime fixes the application UID/GID compatibility identity');
assertSameValue(true, str_contains($compose, 'FMONITOR_RUNTIME_IMAGE'), 'compose accepts an explicit immutable/task-owned runtime image reference');

echo "PASS: PRODUCTION-HTTP-RUNTIME-001 packaging and explicit configuration contract\n";
