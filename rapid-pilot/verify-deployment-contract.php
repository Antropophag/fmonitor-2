<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$makefile = (string) file_get_contents($root . '/Makefile');
$compose = (string) file_get_contents($root . '/compose.yaml');
$importer = (string) file_get_contents(__DIR__ . '/import-production-objects.php');
$initializer = (string) file_get_contents(__DIR__ . '/initialize-native-only.php');
$objectQueue = (string) file_get_contents(__DIR__ . '/ObjectQueue.php');
$readme = (string) file_get_contents(__DIR__ . '/README.md');
$envExample = (string) file_get_contents($root . '/.env.example');

$check = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};

preg_match('/^up:\s*\n(?<recipe>(?:\t.*\n)+)/m', $makefile, $up);
$check(isset($up['recipe']), 'make up target is missing');
$check(str_contains($up['recipe'], 'deploy/runtime/compose.yaml') || str_contains($up['recipe'], 'RUNTIME_COMPOSE'), 'make up does not own canonical Yii2 runtime');
$check(!str_contains($up['recipe'], 'fmonitor2-prepare-bitrix-config.php'), 'canonical make up still prepares legacy Bitrix configuration');
$check(!str_contains($makefile, '../fmonitor'), 'deployment still depends on sibling legacy checkout');
$check(!str_contains($makefile, 'export-legacy-bitrix-secret.php'), 'deployment still invokes the legacy Bitrix exporter');
$check(str_contains($makefile, 'import-production:'), 'production import command is missing');
$check(preg_match('/^import-production:\s+import-legacy$/m', $makefile) === 1, 'production import compatibility alias is not native');
$check(!str_contains($makefile, '-include .env'), 'production secrets must not be parsed as Make syntax');
$check(str_contains($makefile, 'legacy-import/run'), 'production import does not use native Yii command');
$check(!str_contains($envExample, 'FMONITOR_BITRIX_WEBHOOK_URL'), 'canonical local env still requires legacy Bitrix credentials');
$check(!str_contains($envExample, 'FMONITOR_SOURCE_PASSWORD=<'), 'production env template must not contain a real password');
$check(!str_contains($makefile, 'initialize-native-only.php'), 'production import still invokes rapid initializer');
$check(!str_contains($compose, 'profiles: ["bitrix"]'), 'Bitrix sync remains opt-in instead of part of standard startup');
$check(str_contains($compose, 'host.docker.internal:host-gateway'), 'container cannot address a host production tunnel');
$check(!preg_match('/LIMIT\s+(?:100|250)\b/i', $importer), 'production object selection is still truncated');
$check(str_contains($importer, 'NO_NEW_ELIGIBLE_OBJECTS'), 'empty idempotent import is not supported');
$check(str_contains($initializer, "str_replace(\$passwords, '<REDACTED>', \$diagnostics)"), 'initialization diagnostics do not redact configured passwords');
$check(str_contains($initializer, 'array_filter([$stderr, $stdout]'), 'initialization wrapper does not capture both PHP diagnostic streams');
$check(!str_contains($initializer, "initializationRun('import-production-installers.php')"), 'native bootstrap must not source installers from legacy production');
$check(str_contains($initializer, "'workforceSource' => 'bitrix_sync'"), 'native bootstrap does not declare Bitrix as the workforce authority');
$check(str_contains($objectQueue, "error_log('object_queue_failure '"), 'production object queue failures are hidden from container diagnostics');
$check(str_contains($initializer, '$detail = trim('), 'initialization wrapper still hides failed-step diagnostics');
$check(str_contains($readme, 'make import-production'), 'fresh-machine import is undocumented');

echo "PASS deployment contract\n";
