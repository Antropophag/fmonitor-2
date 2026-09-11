<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
/** YII2-CANONICAL-MIGRATIONS-001 A2: one application and persistence owner. */
$root = dirname(__DIR__, 2);
$controller = (string) @file_get_contents($root . '/app/YiiRuntime/Commands/SchemaMigrateController.php');
$adapter = (string) @file_get_contents($root . '/app/YiiRuntime/CanonicalMigrationConsole.php');
assertSameValue(true, $controller !== '' && $adapter !== '', 'INTENDED_RED: migration Yii adapter exists.');
$combined = $controller . $adapter;
assertSameValue(1, substr_count($combined, 'CanonicalMigrationApplication::run'), 'Exactly one application invocation is composed.');
assertSameValue(1, substr_count($combined, 'ProductionPilotMigrationCatalogue::migrations'), 'Exactly one canonical catalogue is selected.');
foreach (['yii\\db\\Connection', 'ActiveRecord', 'MigrateController', "'migration'", 'rapid-pilot', 'app/demo'] as $forbidden) assertSameValue(false, str_contains($combined, $forbidden), 'No parallel owner/dependency: ' . $forbidden);
assertSameValue(false, preg_match('/\b(?:CREATE|ALTER|DROP|TRUNCATE)\b/i', $combined) === 1, 'Yii adapter owns no DDL.');
assertSameValue(true, str_contains($adapter, '$connection = null'), 'INTENDED_RED: connection lifecycle starts explicitly unowned.');
assertSameValue(true, preg_match('/finally\s*\{[^}]*\$connection\s+instanceof\s+\\\\mysqli[^}]*->close\(\)/s', $adapter) === 1, 'Connection constructed before charset confirmation is explicitly closed by the outer lifecycle finally.');
echo "PASS: YII2-CANONICAL-MIGRATIONS-001 single migration owner\n";
