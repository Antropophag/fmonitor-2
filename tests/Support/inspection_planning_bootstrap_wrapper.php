<?php
declare(strict_types=1);

// Test-owned configuration adapter for the otherwise hard-coded demo bootstrap.
// It executes an exact copy of the production script after replacing only the
// database endpoint and the two generated table prefixes. Every replacement is
// guarded so a production edit cannot silently make this contour vacuous.
$sourcePath = dirname(__DIR__, 2) . '/rapid-pilot/docker-bootstrap.php';
$source = file_get_contents($sourcePath);
if (!is_string($source)) throw new RuntimeException('Bootstrap source unavailable');

$required = [
    '$processPrefix = \'fm2d_\' . $fingerprint . \'_g\' . $generation . \'_\';' => '$processPrefix = (string) getenv(\'FMONITOR_BOOTSTRAP_PROCESS_PREFIX\');',
    '$legacyPrefix = \'fm2l_\' . $fingerprint . \'_g\' . $generation . \'_\';' => '$legacyPrefix = (string) getenv(\'FMONITOR_BOOTSTRAP_LEGACY_PREFIX\');',
    '$db = new mysqli(\'127.0.0.1\', \'fmonitor2_demo\', \'fmonitor2_demo_local\', \'fmonitor2_demo\', 23306);' => '$db = new mysqli((string) getenv(\'FMONITOR_DB_HOST\'), (string) getenv(\'FMONITOR_DB_USER\'), (string) getenv(\'FMONITOR_DB_PASSWORD\'), (string) getenv(\'FMONITOR_DB_NAME\'), (int) getenv(\'FMONITOR_DB_PORT\'));',
];
foreach ($required as $needle => $replacement) {
    if (substr_count($source, $needle) !== 1) throw new RuntimeException('Bootstrap configuration seam changed');
    $source = str_replace($needle, $replacement, $source);
}

$sandbox = getenv('FMONITOR_BOOTSTRAP_WRAPPER_ROOT');
if (!is_string($sandbox) || $sandbox === '') throw new RuntimeException('Wrapper root unavailable');
$rapid = $sandbox . '/rapid-pilot';
if (!mkdir($rapid, 0700, true) && !is_dir($rapid)) throw new RuntimeException('Wrapper directory unavailable');
if (!symlink(dirname(__DIR__, 2) . '/app', $sandbox . '/app')) throw new RuntimeException('Application link unavailable');
foreach (['Otiz.php', 'IdentityBootstrap.php', 'InspectionSchedule.php', 'CompletionFlow.php'] as $dependency) {
    if (!symlink(dirname(__DIR__, 2) . '/rapid-pilot/' . $dependency, $rapid . '/' . $dependency)) {
        throw new RuntimeException('Bootstrap dependency link unavailable');
    }
}
$configured = $rapid . '/docker-bootstrap.php';
if (file_put_contents($configured, $source, LOCK_EX) === false) throw new RuntimeException('Configured bootstrap unavailable');
require $configured;
