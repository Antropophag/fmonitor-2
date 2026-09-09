<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__, 2) . '/app/autoload.php';

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\MariaDbPilotLegacyObjectSchemaReadiness;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$database = 't_runtime_schema_' . bin2hex(random_bytes(5));
$admin = new mysqli($host, $user, $password, '', $port);
$db = null;

try {
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db = new mysqli($host, $user, $password, $database, $port);
    $db->set_charset('utf8mb4');
    $prefix = 'runtime_';
    $catalogue = ProductionPilotMigrationCatalogue::migrations();

    assertSameValue(range(1, 22), array_keys($catalogue), 'INTENTIONAL_RED: canonical production catalogue has a contiguous v22 frontier');
    $v22 = $catalogue[22];
    $applyV22 = static fn (mysqli $connection, string $tablePrefix): array => is_string($v22)
        ? $v22::apply($connection, $tablePrefix)
        : $v22($connection, $tablePrefix);

    $upgradePrefix = 'upgrade_';
    $upgradeTable = $upgradePrefix . 'fm_maintable';
    $db->query("CREATE TABLE `{$upgradeTable}`(id BIGINT UNSIGNED NOT NULL PRIMARY KEY,ordadr_address VARCHAR(500) NULL,entrance VARCHAR(80) NULL,regnumber VARCHAR(120) NULL,workdatestart VARCHAR(40) NULL,workdateendadjusted VARCHAR(40) NULL,plan_finish_date VARCHAR(40) NULL,workdatefinish VARCHAR(40) NULL,ptoactdate VARCHAR(40) NULL,responsstroicontrol VARCHAR(80) NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("INSERT INTO `{$upgradeTable}` VALUES(1450,'Москва','1','77-UPGRADE','2026-09-01','2026-10-01','2026-10-02',NULL,NULL,'73')");
    $upgradeBefore = $db->query("SELECT * FROM `{$upgradeTable}` WHERE id=1450")->fetch_assoc();
    assertSameValue(['applied' => true, 'columnsAdded' => 7], $applyV22($db, $upgradePrefix), 'v22 upgrades the accepted populated ten-column predecessor with exactly seven additive fields');
    assertSameValue($upgradeBefore, array_intersect_key($db->query("SELECT * FROM `{$upgradeTable}` WHERE id=1450")->fetch_assoc(), $upgradeBefore), 'v22 additive upgrade preserves every predecessor value');
    assertSameValue(['applied' => false], $applyV22($db, $upgradePrefix), 'compatible populated v22 repeat is a no-op');

    $conflictPrefix = 'conflict_';
    $conflictTable = $conflictPrefix . 'fm_maintable';
    $db->query("CREATE TABLE `{$conflictTable}`(id BIGINT UNSIGNED NOT NULL PRIMARY KEY,unexpected VARCHAR(20) NOT NULL) ENGINE=InnoDB");
    $db->query("INSERT INTO `{$conflictTable}` VALUES(7,'preserve')");
    $conflictBefore = $db->query("SHOW CREATE TABLE `{$conflictTable}`")->fetch_row()[1];
    $conflictRows = $db->query("SELECT * FROM `{$conflictTable}`")->fetch_all(MYSQLI_ASSOC);
    assertSameValue(['applied' => false, 'reason' => 'SCHEMA_MIGRATION_CONFLICT'], $applyV22($db, $conflictPrefix), 'v22 rejects an incompatible predecessor');
    assertSameValue([$conflictBefore, $conflictRows], [$db->query("SHOW CREATE TABLE `{$conflictTable}`")->fetch_row()[1], $db->query("SELECT * FROM `{$conflictTable}`")->fetch_all(MYSQLI_ASSOC)], 'v22 conflict performs no schema or row mutation');

    $first = CanonicalMigrationApplication::run($db, $prefix, $catalogue);
    assertSameValue([0, true, 22, range(1, 22)], [$first['exitCode'], $first['result']['ok'] ?? null, $first['result']['schemaVersion'] ?? null, $first['result']['appliedVersions'] ?? null], 'clean production migration creates the full runtime schema');
    MariaDbPilotLegacyObjectSchemaReadiness::assertReady($db, $prefix);

    $table = $prefix . 'fm_maintable';
    $db->query("INSERT INTO `{$table}`(id,ordadr_address,entrance,regnumber,workdatestart) VALUES(1450,'Москва, тестовый адрес','1','77-TEST','2026-09-09')");
    $before = $db->query("SELECT * FROM `{$table}` WHERE id=1450")->fetch_assoc();
    $repeat = CanonicalMigrationApplication::run($db, $prefix, $catalogue);
    assertSameValue([0, true, 22, []], [$repeat['exitCode'], $repeat['result']['ok'] ?? null, $repeat['result']['schemaVersion'] ?? null, $repeat['result']['appliedVersions'] ?? null], 'production migration replay is a no-op at v22');
    assertSameValue($before, $db->query("SELECT * FROM `{$table}` WHERE id=1450")->fetch_assoc(), 'migration replay preserves populated object facts exactly');

    echo "PASS: PRODUCTION-HTTP-RUNTIME-001 canonical v22 object schema frontier\n";
} finally {
    if ($db instanceof mysqli) {
        $db->close();
    }
    try {
        $admin->query("DROP DATABASE IF EXISTS `{$database}`");
    } finally {
        $admin->close();
    }
}
