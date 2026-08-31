<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$makefile = (string) file_get_contents($root . '/Makefile');
$compose = (string) file_get_contents($root . '/compose.yaml');
$importer = (string) file_get_contents(__DIR__ . '/import-production-objects.php');
$readme = (string) file_get_contents(__DIR__ . '/README.md');
$envExample = (string) file_get_contents($root . '/.env.example');

$check = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};

preg_match('/^up:\s*\n(?<recipe>(?:\t.*\n)+)/m', $makefile, $up);
$check(isset($up['recipe']), 'make up target is missing');
$check(!str_contains($up['recipe'], '_bitrix-secret'), 'make up depends on sibling legacy checkout');
$check(str_contains($makefile, 'import-production:'), 'production import command is missing');
$check(str_contains($makefile, '-include .env'), 'Makefile does not load the local .env file');
$check(str_contains($envExample, 'FMONITOR_SOURCE_USER=replace_with_read_only_user'), 'production env template is missing safe credential placeholders');
$check(!str_contains($envExample, 'FMONITOR_SOURCE_PASSWORD=<'), 'production env template must not contain a real password');
$check(str_contains($makefile, 'initialize-native-only.php'), 'production import does not use the guarded initializer');
$check(str_contains($compose, 'profiles: ["bitrix"]'), 'Bitrix sync is not opt-in');
$check(str_contains($compose, 'host.docker.internal:host-gateway'), 'container cannot address a host production tunnel');
$check(!preg_match('/LIMIT\s+(?:100|250)\b/i', $importer), 'production object selection is still truncated');
$check(str_contains($importer, 'NO_NEW_ELIGIBLE_OBJECTS'), 'empty idempotent import is not supported');
$check(str_contains($readme, 'make import-production'), 'fresh-machine import is undocumented');

echo "PASS deployment contract\n";
