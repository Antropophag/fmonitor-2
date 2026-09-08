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
    '$prefix=(string)(getenv(\'FMONITOR_BOOTSTRAP_PROCESS_PREFIX\')?:$defaultPrefix);' => '$prefix=(string)(getenv(\'FMONITOR_BOOTSTRAP_PROCESS_PREFIX\')?:$defaultPrefix);',
    '$legacy=(string)(getenv(\'FMONITOR_BOOTSTRAP_LEGACY_PREFIX\')?:$prefix);' => '$legacy=(string)(getenv(\'FMONITOR_BOOTSTRAP_LEGACY_PREFIX\')?:$prefix);',
    '$cfg=[\'host\'=>(string)(getenv(\'FMONITOR_DB_HOST\')?:\'127.0.0.1\'),\'port\'=>(int)(getenv(\'FMONITOR_DB_PORT\')?:23306),\'name\'=>(string)(getenv(\'FMONITOR_DB_NAME\')?:\'fmonitor2_demo\'),\'user\'=>(string)(getenv(\'FMONITOR_DB_USER\')?:\'fmonitor2_demo\'),\'password\'=>(string)(getenv(\'FMONITOR_DB_PASSWORD\')?:\'\')];' => '$cfg=[\'host\'=>(string)(getenv(\'FMONITOR_DB_HOST\')?:\'127.0.0.1\'),\'port\'=>(int)(getenv(\'FMONITOR_DB_PORT\')?:23306),\'name\'=>(string)(getenv(\'FMONITOR_DB_NAME\')?:\'fmonitor2_demo\'),\'user\'=>(string)(getenv(\'FMONITOR_DB_USER\')?:\'fmonitor2_demo\'),\'password\'=>(string)(getenv(\'FMONITOR_DB_PASSWORD\')?:\'\')];',
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
