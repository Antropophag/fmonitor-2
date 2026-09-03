<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\AssignmentOrderOriginalSchemaMigration;
use FMonitor2\InstallationProcess\BitrixWorkforceHistorySchemaMigration;
use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\ChecklistTemplateSchemaMigration;
use FMonitor2\InstallationProcess\ClassificationProvenanceSchemaMigration;
use FMonitor2\InstallationProcess\IdentityAccessSchemaMigration;
use FMonitor2\InstallationProcess\InspectionEvidenceSchemaMigration;
use FMonitor2\InstallationProcess\InspectionPlanningSchemaMigration;
use FMonitor2\InstallationProcess\InstallationCompletionSchemaMigration;
use FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v4, sections 1, 10, 11 and 15.
// OpenSpec task: replace-pilot-registration-with-original-upload 3.1.

/** @return array<int, class-string|callable(mysqli,string):array<string,mixed>> */
function aoosCatalogue(): array
{
    return [
        1 => ProductionProcessSchemaMigration::class,
        2 => WorkforceCatalogSchemaMigration::class,
        3 => ProcessUserCapabilitiesSchemaMigration::class,
        4 => ProcessCommandCapabilitiesSchemaMigration::class,
        5 => BitrixWorkforceHistorySchemaMigration::class,
        6 => IdentityAccessSchemaMigration::class,
        7 => ChecklistTemplateSchemaMigration::class,
        8 => InspectionEvidenceSchemaMigration::class,
        9 => InspectionPlanningSchemaMigration::class,
        10 => InstallationCompletionSchemaMigration::class,
        11 => static fn (mysqli $connection, string $prefix): array =>
            ClassificationProvenanceSchemaMigration::apply($connection, $prefix, static function (): void {}),
        12 => AssignmentOrderOriginalSchemaMigration::class,
    ];
}

function aoosAdmin(?string $database = null): mysqli
{
    $connection = new mysqli(
        getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_demo_local',
        $database,
        (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306),
    );
    $connection->set_charset('utf8mb4');
    return $connection;
}

function aoosQuote(string $identifier): string
{
    if (preg_match('/^[A-Za-z0-9_]+$/D', $identifier) !== 1) {
        throw new TestFailure('SETUP_FAILURE: unsafe verifier-owned identifier.');
    }
    return '`' . $identifier . '`';
}

/** @return array{exitCode:int,stdout:string,stderr:string} */
function aoosCli(string $database, string $prefix): array
{
    $environment = [
        'FMONITOR_DB_HOST' => getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        'FMONITOR_DB_PORT' => getenv('FMONITOR_TEST_DB_PORT') ?: '23306',
        'FMONITOR_DB_NAME' => $database,
        'FMONITOR_DB_USER' => getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        'FMONITOR_DB_PASSWORD' => getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_demo_local',
        'FMONITOR_PROCESS_TABLE_PREFIX' => $prefix,
    ];
    $command = ['/usr/bin/env', '-i'];
    foreach ($environment as $name => $value) {
        $command[] = $name . '=' . $value;
    }
    $command[] = PHP_BINARY;
    $command[] = dirname(__DIR__, 2) . '/bin/fmonitor2-migrate.php';
    $process = proc_open($command, [0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w']], $pipes, dirname(__DIR__, 2));
    if (!is_resource($process)) {
        throw new TestFailure('SETUP_FAILURE: canonical migration CLI did not start.');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['exitCode'=>proc_close($process), 'stdout'=>$stdout, 'stderr'=>$stderr];
}

/** @return array<string,mixed> */
function aoosState(mysqli $connection, string $prefix): array
{
    $escaped = $connection->real_escape_string($prefix . '%');
    $tables = $connection->query(
        "SELECT TABLE_NAME,ENGINE,TABLE_COLLATION,AUTO_INCREMENT FROM information_schema.TABLES "
        . "WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$escaped}' ORDER BY BINARY TABLE_NAME",
    )->fetch_all(MYSQLI_ASSOC);
    $state = ['tables'=>$tables, 'definitions'=>[], 'rows'=>[]];
    foreach ($tables as $tableRow) {
        $table = (string) $tableRow['TABLE_NAME'];
        $create = $connection->query('SHOW CREATE TABLE ' . aoosQuote($table))->fetch_assoc();
        $state['definitions'][$table] = array_values($create ?: []);
        $state['rows'][$table] = $connection->query('SELECT * FROM ' . aoosQuote($table) . ' ORDER BY 1')->fetch_all(MYSQLI_ASSOC);
    }
    return $state;
}

/** @return list<string> */
function aoosCapabilityLiterals(mysqli $connection, string $prefix): array
{
    $table = $connection->real_escape_string($prefix . 'fm2_process_user_capabilities');
    $row = $connection->query(
        "SELECT cc.CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS cc "
        . "JOIN information_schema.TABLE_CONSTRAINTS tc ON tc.CONSTRAINT_SCHEMA=cc.CONSTRAINT_SCHEMA "
        . "AND tc.TABLE_NAME=cc.TABLE_NAME AND tc.CONSTRAINT_NAME=cc.CONSTRAINT_NAME "
        . "WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME='{$table}' "
        . "AND tc.CONSTRAINT_NAME='ck_fm2_process_user_capability'",
    )->fetch_assoc();
    if (!is_array($row) || !is_string($row['CHECK_CLAUSE'] ?? null)) {
        throw new TestFailure('Canonical capability CHECK must retain its normative public name.');
    }
    preg_match_all("/'((?:''|[^'])*)'/", $row['CHECK_CLAUSE'], $matches);
    $literals = array_map(static fn (string $literal): string => str_replace("''", "'", $literal), $matches[1]);
    sort($literals, SORT_STRING);
    return $literals;
}

function aoosApplyThrough(int $lastVersion, mysqli $connection, string $prefix): array
{
    return CanonicalMigrationApplication::run(
        $connection,
        $prefix,
        array_slice(aoosCatalogue(), 0, $lastVersion, true),
    );
}

$repositoryRoot = dirname(__DIR__, 2);
assertSameValue(
    '97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479',
    hash_file('sha256', $repositoryRoot . '/specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md'),
    'Gate 2 remains pinned to the owner-approved v4 executable specification.',
);
assertSameValue(
    '127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2',
    hash_file('sha256', $repositoryRoot . '/openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md'),
    'Gate 2 remains pinned to the owner-approved v4 OpenSpec delta.',
);

if (!class_exists(AssignmentOrderOriginalSchemaMigration::class)) {
    throw new TestFailure(
        'INTENDED_RED: canonical additive assignment-order-original schema migration v12 is missing.',
    );
}

$expectedCapabilities = [
    'assignment_order.confirm_registration',
    'assignment_order.original.correct',
    'assignment_order.original.storage.reconcile',
    'assignment_order.original.upload',
    'assignment_order.prepare',
    'construction_control_engineer',
    'installation.open',
];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$admin = aoosAdmin();
$databases = [];
try {
    $cleanName = 't_aoos_clean_' . bin2hex(random_bytes(4));
    $databases[] = $cleanName;
    $admin->query('CREATE DATABASE ' . aoosQuote($cleanName) . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    assertSameValue(
        ['exitCode'=>0, 'stdout'=>"{\"ok\":true,\"schemaVersion\":12,\"appliedVersions\":[1,2,3,4,5,6,7,8,9,10,11,12]}\n", 'stderr'=>''],
        aoosCli($cleanName, 'clean_'),
        'Clean canonical public runner advances the contiguous frontier through v12.',
    );
    $clean = aoosAdmin($cleanName);
    assertSameValue($expectedCapabilities, aoosCapabilityLiterals($clean, 'clean_'), 'v12 capability enum is the exact approved additive set.');
    $cleanBeforeRepeat = aoosState($clean, 'clean_');
    assertSameValue(
        ['exitCode'=>0, 'stdout'=>"{\"ok\":true,\"schemaVersion\":12,\"appliedVersions\":[]}\n", 'stderr'=>''],
        aoosCli($cleanName, 'clean_'),
        'Exact v12 repeat is a no-op through the canonical public runner.',
    );
    assertSameValue($cleanBeforeRepeat, aoosState($clean, 'clean_'), 'Repeat preserves every canonical row, definition and AUTO_INCREMENT.');
    $clean->close();

    $populatedName = 't_aoos_pop_' . bin2hex(random_bytes(4));
    $databases[] = $populatedName;
    $admin->query('CREATE DATABASE ' . aoosQuote($populatedName) . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $populated = aoosAdmin($populatedName);
    assertSameValue(0, aoosApplyThrough(11, $populated, 'pop_')['exitCode'], 'SETUP_FAILURE: public application creates exact v1-v11 predecessor.');
    $populated->query("INSERT INTO pop_fm2_installation_cases(id,legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES(71,4512,'requires_order','2026-09-01T00:00:00Z','2026-09-01T00:00:00Z',3)");
    $populated->query("INSERT INTO pop_fm2_assignment_orders(id,installation_case_id,version_no,kind,status,order_date,registration_number,registered_at,registration_actor_type,registration_actor_id,registration_source,external_registration_id,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,previous_assignment_order_id,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot,pto_act_date_snapshot,prepared_at,prepared_by_user_id) VALUES(81,71,1,'initial','registered','2026-09-01','LEGACY-12-R','2026-09-01T09:00:00Z','user','901','manual','legacy-external-1',901,'Legacy Engineer','Engineer','individual',NULL,'Legacy address','1','REG-4512','2026-10-01','2026-12-01',NULL,'2026-08-31T09:00:00Z',18)");
    $populated->query("INSERT INTO pop_fm2_process_user_capabilities(user_id,capability,position_snapshot) VALUES(18,'assignment_order.prepare',NULL),(18,'assignment_order.confirm_registration',NULL),(18,'installation.open',NULL),(901,'construction_control_engineer','Engineer')");
    $historicalBefore = $populated->query("SELECT status,order_date,registration_number,registered_at,registration_actor_type,registration_actor_id,registration_source,external_registration_id FROM pop_fm2_assignment_orders WHERE id=81")->fetch_assoc();
    $capabilityRowsBefore = $populated->query('SELECT * FROM pop_fm2_process_user_capabilities ORDER BY user_id,capability')->fetch_all(MYSQLI_ASSOC);
    $populatedOutcome = aoosApplyThrough(12, $populated, 'pop_');
    assertSameValue(['exitCode'=>0, 'result'=>['ok'=>true,'schemaVersion'=>12,'appliedVersions'=>[12]]], $populatedOutcome, 'Populated v11 predecessor advances only v12.');
    assertSameValue($expectedCapabilities, aoosCapabilityLiterals($populated, 'pop_'), 'Populated upgrade installs only the exact approved capability literals.');
    assertSameValue($capabilityRowsBefore, $populated->query('SELECT * FROM pop_fm2_process_user_capabilities ORDER BY user_id,capability')->fetch_all(MYSQLI_ASSOC), 'Capability migration preserves all historical grants byte-for-byte.');
    assertSameValue($historicalBefore, $populated->query("SELECT status,order_date,registration_number,registered_at,registration_actor_type,registration_actor_id,registration_source,external_registration_id FROM pop_fm2_assignment_orders WHERE id=81")->fetch_assoc(), 'Historical manual-registration facts remain read-only and byte-identical.');
    $populated->close();

    $conflictName = 't_aoos_conflict_' . bin2hex(random_bytes(4));
    $databases[] = $conflictName;
    $admin->query('CREATE DATABASE ' . aoosQuote($conflictName) . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $conflict = aoosAdmin($conflictName);
    assertSameValue(0, aoosApplyThrough(11, $conflict, 'conflict_')['exitCode'], 'SETUP_FAILURE: conflict fixture has exact v1-v11 predecessor.');
    $conflict->query('ALTER TABLE conflict_fm2_process_user_capabilities DROP CONSTRAINT ck_fm2_process_user_capability');
    $conflict->query("ALTER TABLE conflict_fm2_process_user_capabilities ADD CONSTRAINT ck_fm2_process_user_capability CHECK(capability IN ('assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer','hostile.unapproved'))");
    $conflict->query('CREATE TABLE conflict_decoy(id INT PRIMARY KEY,value VARCHAR(40)) ENGINE=InnoDB');
    $conflict->query("INSERT INTO conflict_decoy VALUES(1,'must remain untouched')");
    $conflictBefore = aoosState($conflict, 'conflict_');
    assertSameValue(
        ['exitCode'=>2, 'result'=>['ok'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT','schemaVersion'=>12]],
        aoosApplyThrough(12, $conflict, 'conflict_'),
        'Unknown capability semantics fail closed at v12 before any original schema creation or repair.',
    );
    assertSameValue($conflictBefore, aoosState($conflict, 'conflict_'), 'Conflict preserves target, decoy, rows, definitions and counters exactly.');
    $conflict->close();

    echo "ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_CLEAN_OK\n";
    echo "ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_REPEAT_OK\n";
    echo "ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_POPULATED_OK\n";
    echo "ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_CONFLICT_OK\n";
    echo "ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_001_OK\n";
} finally {
    foreach (array_reverse($databases) as $database) {
        try {
            $admin->query('DROP DATABASE IF EXISTS ' . aoosQuote($database));
        } catch (Throwable) {
        }
    }
    $admin->close();
}
