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

/**
 * Canonical v12 persistence names follow the existing fm2_<aggregate>_<fact>
 * convention. The shape is derived only from the approved commit DTOs and
 * evidence JSON; adapters remain free to hide this representation.
 *
 * @return array<string,array{columns:string,indexes:list<string>,foreignKeys:list<string>,checks:list<string>}>
 */
function aoosOriginalManifest(): array
{
    return [
        'fm2_assignment_order_original_roots' => [
            'columns'=>'root_original_id:varchar(160):NO;installation_case_id:bigint unsigned:NO;assignment_order_id:bigint unsigned:NO;composition_identity:varchar(255):NO;composition_sha256:char(64):NO;created_at:varchar(40):NO',
            'indexes'=>['PRIMARY|root_original_id','UNIQUE|assignment_order_id','INDEX|installation_case_id'],
            'foreignKeys'=>['assignment_order_id>fm2_assignment_orders.id:RESTRICT','installation_case_id>fm2_installation_cases.id:RESTRICT'],
            'checks'=>['char_length(composition_sha256)=64'],
        ],
        'fm2_assignment_order_original_revisions' => [
            'columns'=>'revision_id:varchar(160):NO;root_original_id:varchar(160):NO;revision_number:int unsigned:NO;previous_revision_id:varchar(160):YES;expected_current_revision_id:varchar(160):YES;current_marker:tinyint unsigned:YES;document_date:date:NO;uploaded_at:varchar(40):NO;actor_user_id:bigint unsigned:NO;pdf_sha256:char(64):NO;byte_size:bigint unsigned:NO;private_content_identity:varchar(255):NO;correction_reason:varchar(500):YES',
            'indexes'=>['PRIMARY|revision_id','UNIQUE|root_original_id,current_marker','UNIQUE|root_original_id,revision_id','UNIQUE|root_original_id,revision_number'],
            'foreignKeys'=>['root_original_id>fm2_assignment_order_original_roots.root_original_id:RESTRICT','root_original_id,previous_revision_id>fm2_assignment_order_original_revisions.root_original_id,revision_id:RESTRICT'],
            'checks'=>[
                'byte_size>=1andbyte_size<=20971520',
                'char_length(pdf_sha256)=64',
                'current_markerisnullorcurrent_marker=1',
                'revision_number=1andprevious_revision_idisnullandexpected_current_revision_idisnullandcorrection_reasonisnullorrevision_number>1andprevious_revision_idisnotnullandexpected_current_revision_id=previous_revision_idandchar_length(trim(correction_reason))between1and500',
            ],
        ],
        'fm2_assignment_order_original_requests' => [
            'columns'=>'request_id:char(36):NO;actor_user_id:bigint unsigned:NO;mode:varchar(20):NO;installation_case_id:bigint unsigned:NO;assignment_order_id:bigint unsigned:NO;status:varchar(20):NO;reason_code:varchar(80):YES;retryable:tinyint:NO;root_original_id:varchar(160):YES;current_revision_id:varchar(160):YES;revision_number:int unsigned:YES;document_date:date:YES;sha256:char(64):YES;byte_size:bigint unsigned:YES;uploaded_at:varchar(40):YES;attempted_at:varchar(40):NO',
            'indexes'=>['PRIMARY|request_id','INDEX|assignment_order_id,request_id','INDEX|current_revision_id','INDEX|installation_case_id','INDEX|root_original_id'],
            'foreignKeys'=>['assignment_order_id>fm2_assignment_orders.id:RESTRICT','current_revision_id>fm2_assignment_order_original_revisions.revision_id:RESTRICT','installation_case_id>fm2_installation_cases.id:RESTRICT','root_original_id>fm2_assignment_order_original_roots.root_original_id:RESTRICT'],
            'checks'=>[
                "modein('initial','correction')",
                "statusin('accepted','rejected','conflict')",
                'retryable=0',
                "status='accepted'andreason_codeisnullandroot_original_idisnotnullandcurrent_revision_idisnotnullandrevision_numberisnotnullanddocument_dateisnotnullandsha256isnotnullandbyte_sizeisnotnullanduploaded_atisnotnullorstatusin('rejected','conflict')andreason_codeisnotnullandroot_original_idisnullandcurrent_revision_idisnullandrevision_numberisnullanddocument_dateisnullandsha256isnullandbyte_sizeisnullanduploaded_atisnull",
            ],
        ],
        'fm2_assignment_order_original_fingerprints' => [
            'columns'=>'fingerprint:char(64):NO;request_id:char(36):NO;root_original_id:varchar(160):NO;revision_id:varchar(160):NO',
            'indexes'=>['PRIMARY|fingerprint','UNIQUE|request_id','INDEX|revision_id','INDEX|root_original_id,revision_id'],
            'foreignKeys'=>['request_id>fm2_assignment_order_original_requests.request_id:RESTRICT','revision_id>fm2_assignment_order_original_revisions.revision_id:RESTRICT','root_original_id>fm2_assignment_order_original_roots.root_original_id:RESTRICT'],
            'checks'=>['char_length(fingerprint)=64'],
        ],
        'fm2_assignment_order_original_events' => [
            'columns'=>'id:bigint unsigned:NO:auto_increment;event_type:varchar(80):NO;installation_case_id:bigint unsigned:NO;assignment_order_id:bigint unsigned:NO;root_original_id:varchar(160):NO;revision_id:varchar(160):NO;occurred_at:varchar(40):NO;actor_user_id:bigint unsigned:NO',
            'indexes'=>['PRIMARY|id','UNIQUE|revision_id','INDEX|assignment_order_id','INDEX|installation_case_id,assignment_order_id,id','INDEX|root_original_id'],
            'foreignKeys'=>['assignment_order_id>fm2_assignment_orders.id:RESTRICT','installation_case_id>fm2_installation_cases.id:RESTRICT','revision_id>fm2_assignment_order_original_revisions.revision_id:RESTRICT','root_original_id>fm2_assignment_order_original_roots.root_original_id:RESTRICT'],
            'checks'=>["event_typein('assignment_order_original_accepted','assignment_order_original_corrected')"],
        ],
        'fm2_assignment_order_original_attempt_audits' => [
            'columns'=>'id:bigint unsigned:NO:auto_increment;request_id:char(36):NO;actor_identity:varchar(120):NO;mode:varchar(20):NO;installation_case_id:bigint unsigned:NO;assignment_order_id:bigint unsigned:NO;status:varchar(20):NO;reason_code:varchar(80):NO;attempted_at:varchar(40):NO',
            'indexes'=>['PRIMARY|id','UNIQUE|request_id','INDEX|actor_identity,attempted_at'],
            'foreignKeys'=>['request_id>fm2_assignment_order_original_requests.request_id:RESTRICT'],
            'checks'=>["modein('initial','correction')", "statusin('rejected','conflict')"],
        ],
        'fm2_assignment_order_original_maintenance_results' => [
            'columns'=>'request_id:char(36):NO;system_principal_id:varchar(160):NO;status:varchar(20):NO;reason_code:varchar(80):YES;retryable:tinyint:NO;scanned:int unsigned:NO;deleted:int unsigned:NO;retained:int unsigned:NO;failed:int unsigned:NO;next_cursor:varchar(500):YES;attempted_at:varchar(40):NO',
            'indexes'=>['PRIMARY|request_id','INDEX|system_principal_id,attempted_at'],
            'foreignKeys'=>[],
            'checks'=>[
                'scanned=deleted+retained+failed',
                "statusin('completed','partial','rejected')",
                "status='completed'andreason_codeisnullandretryable=0orstatus='partial'andreason_codein('locked','storage_failure')andretryable=1orstatus='rejected'andreason_codein('invalid_command','authorization_denied')andretryable=0",
            ],
        ],
    ];
}

function aoosNormalizeSql(string $value): string
{
    $normalized = '';
    $quoted = false;
    for ($index = 0, $length = strlen($value); $index < $length; $index++) {
        $character = $value[$index];
        if ($character === "'") {
            $normalized .= $character;
            if ($quoted && $index + 1 < $length && $value[$index + 1] === "'") {
                $normalized .= "'";
                $index++;
            } else {
                $quoted = !$quoted;
            }
        } elseif ($quoted) {
            $normalized .= $character;
        } elseif ($character !== '`' && !ctype_space($character)) {
            $normalized .= strtolower($character);
        }
    }
    return $normalized;
}

/** @return array<string,mixed> */
function aoosOriginalFingerprint(mysqli $connection, string $prefix): array
{
    $manifest = aoosOriginalManifest();
    $fingerprint = [];
    foreach ($manifest as $base => $expected) {
        $table = $prefix . $base;
        $escaped = $connection->real_escape_string($table);
        $properties = $connection->query("SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}'")->fetch_assoc();
        if (!is_array($properties)) {
            throw new TestFailure("Canonical v12 table is missing: {$base}");
        }
        $columns = $connection->query("SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' ORDER BY ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC);
        $columnText = implode(';', array_map(static fn (array $row): string => $row['COLUMN_NAME'].':'.preg_replace('/^(bigint|int|tinyint)\\(\\d+\\)/', '$1', strtolower($row['COLUMN_TYPE'])).':'.$row['IS_NULLABLE'].':'.$row['EXTRA'], $columns));
        $indexes = $connection->query("SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART,COLLATION,INDEX_TYPE,IGNORED FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' ORDER BY BINARY INDEX_NAME,SEQ_IN_INDEX")->fetch_all(MYSQLI_ASSOC);
        $groups = [];
        foreach ($indexes as $index) {
            if ($index['SUB_PART'] !== null || $index['COLLATION'] !== 'A' || $index['INDEX_TYPE'] !== 'BTREE' || $index['IGNORED'] !== 'NO') {
                throw new TestFailure("Canonical index mechanics drifted for {$base}.");
            }
            $name = (string) $index['INDEX_NAME'];
            $identity = $name === 'PRIMARY' ? 'PRIMARY' : ((int) $index['NON_UNIQUE'] === 0 ? 'UNIQUE' : 'INDEX');
            $groups[$name] ??= ['identity'=>$identity, 'columns'=>[]];
            $groups[$name]['columns'][] = $index['COLUMN_NAME'];
        }
        $indexText = array_map(static fn (array $group): string => $group['identity'].'|'.implode(',', $group['columns']), array_values($groups));
        sort($indexText, SORT_STRING);
        $foreignKeys = $connection->query("SELECT k.CONSTRAINT_NAME,k.COLUMN_NAME,k.ORDINAL_POSITION,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.TABLE_NAME=k.TABLE_NAME AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME='{$escaped}' AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY BINARY k.CONSTRAINT_NAME,k.ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC);
        $fkGroups = [];
        foreach ($foreignKeys as $foreignKey) {
            $name = (string) $foreignKey['CONSTRAINT_NAME'];
            $fkGroups[$name] ??= ['columns'=>[], 'target'=>$foreignKey['REFERENCED_TABLE_NAME'], 'targets'=>[], 'delete'=>$foreignKey['DELETE_RULE']];
            $fkGroups[$name]['columns'][] = $foreignKey['COLUMN_NAME'];
            $fkGroups[$name]['targets'][] = $foreignKey['REFERENCED_COLUMN_NAME'];
        }
        $fkText = array_map(static fn (array $fk): string => implode(',', $fk['columns']).'>'.substr((string) $fk['target'], strlen($prefix)).'.'.implode(',', $fk['targets']).':'.$fk['delete'], array_values($fkGroups));
        sort($fkText, SORT_STRING);
        $checks = array_map(static fn (array $row): string => aoosNormalizeSql((string) $row['CHECK_CLAUSE']), $connection->query("SELECT cc.CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS cc JOIN information_schema.TABLE_CONSTRAINTS tc ON tc.CONSTRAINT_SCHEMA=cc.CONSTRAINT_SCHEMA AND tc.TABLE_NAME=cc.TABLE_NAME AND tc.CONSTRAINT_NAME=cc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME='{$escaped}' ORDER BY BINARY tc.CONSTRAINT_NAME")->fetch_all(MYSQLI_ASSOC));
        sort($checks, SORT_STRING);
        $expectedIndexes = $expected['indexes']; sort($expectedIndexes, SORT_STRING);
        $expectedFks = $expected['foreignKeys']; sort($expectedFks, SORT_STRING);
        $expectedChecks = array_map('aoosNormalizeSql', $expected['checks']); sort($expectedChecks, SORT_STRING);
        assertSameValue($expected['columns'], $columnText, "{$base} exact ordered columns/types/nullability.");
        assertSameValue($expectedIndexes, $indexText, "{$base} exact primary/unique/index semantics.");
        assertSameValue($expectedFks, $fkText, "{$base} exact lineage FK/delete semantics.");
        assertSameValue($expectedChecks, $checks, "{$base} exact CHECK semantics.");
        assertSameValue(['ENGINE'=>'InnoDB','TABLE_COLLATION'=>'utf8mb4_unicode_ci'], $properties, "{$base} exact engine/collation.");
        $fingerprint[$base] = compact('columnText', 'indexText', 'fkText', 'checks', 'properties');
    }
    return $fingerprint;
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
    aoosOriginalFingerprint($clean, 'clean_');
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
    aoosOriginalFingerprint($populated, 'pop_');
    assertSameValue($capabilityRowsBefore, $populated->query('SELECT * FROM pop_fm2_process_user_capabilities ORDER BY user_id,capability')->fetch_all(MYSQLI_ASSOC), 'Capability migration preserves all historical grants byte-for-byte.');
    assertSameValue($historicalBefore, $populated->query("SELECT status,order_date,registration_number,registered_at,registration_actor_type,registration_actor_id,registration_source,external_registration_id FROM pop_fm2_assignment_orders WHERE id=81")->fetch_assoc(), 'Historical manual-registration facts remain read-only and byte-identical.');
    $populated->query("INSERT INTO pop_fm2_assignment_order_original_roots VALUES('original-0001',71,81,'composition-81-v1',REPEAT('1',64),'2026-09-02T09:15:30Z')");
    $populated->query("INSERT INTO pop_fm2_assignment_order_original_revisions VALUES('revision-0001','original-0001',1,NULL,NULL,1,'2026-09-01','2026-09-02T09:15:30Z',18,REPEAT('4',64),327,'private-content-0001',NULL)");
    $populated->query("INSERT INTO pop_fm2_assignment_order_original_requests VALUES('00000000-0000-4000-8000-000000000001',18,'initial',71,81,'accepted',NULL,0,'original-0001','revision-0001',1,'2026-09-01',REPEAT('4',64),327,'2026-09-02T09:15:30Z','2026-09-02T09:15:30Z')");
    $populated->query("INSERT INTO pop_fm2_assignment_order_original_fingerprints VALUES(REPEAT('a',64),'00000000-0000-4000-8000-000000000001','original-0001','revision-0001')");
    $populated->query("INSERT INTO pop_fm2_assignment_order_original_events VALUES(41,'assignment_order_original_accepted',71,81,'original-0001','revision-0001','2026-09-02T09:15:30Z',18)");
    $populated->query("INSERT INTO pop_fm2_assignment_order_original_requests VALUES('00000000-0000-4000-8000-000000000002',19,'initial',71,81,'rejected','authorization_denied',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-02T09:16:30Z')");
    $populated->query("INSERT INTO pop_fm2_assignment_order_original_attempt_audits VALUES(51,'00000000-0000-4000-8000-000000000002','19','initial',71,81,'rejected','authorization_denied','2026-09-02T09:16:30Z')");
    $populated->query("INSERT INTO pop_fm2_assignment_order_original_maintenance_results VALUES('00000000-0000-4000-8000-000000000003','assignment-original-maintenance','completed',NULL,0,2,1,1,0,NULL,'2026-09-02T11:15:30Z')");
    $populated->query('ALTER TABLE pop_fm2_assignment_order_original_events AUTO_INCREMENT=60');
    $populated->query('ALTER TABLE pop_fm2_assignment_order_original_attempt_audits AUTO_INCREMENT=70');
    $originalFactsBeforeRepeat = aoosState($populated, 'pop_');
    assertSameValue(['exitCode'=>0, 'result'=>['ok'=>true,'schemaVersion'=>12,'appliedVersions'=>[]]], aoosApplyThrough(12, $populated, 'pop_'), 'Compatible populated original schema repeats without migration work.');
    assertSameValue($originalFactsBeforeRepeat, aoosState($populated, 'pop_'), 'Compatible root/revision/request/fingerprint/event/audit/maintenance facts and counters remain byte-identical.');
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

    foreach (['malformed_root', 'hostile_revision'] as $partialKind) {
        $partialName = 't_aoos_' . $partialKind . '_' . bin2hex(random_bytes(3));
        $databases[] = $partialName;
        $admin->query('CREATE DATABASE ' . aoosQuote($partialName) . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $partial = aoosAdmin($partialName);
        $prefix = $partialKind . '_';
        assertSameValue(0, aoosApplyThrough(11, $partial, $prefix)['exitCode'], 'SETUP_FAILURE: partial-schema fixture has exact v1-v11 predecessor.');
        if ($partialKind === 'malformed_root') {
            $partial->query('CREATE TABLE ' . aoosQuote($prefix . 'fm2_assignment_order_original_roots') . '(root_original_id VARCHAR(160) PRIMARY KEY, hostile_extra INT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            $partial->query('INSERT INTO ' . aoosQuote($prefix . 'fm2_assignment_order_original_roots') . " VALUES('hostile-root',7)");
        } else {
            $partial->query('CREATE TABLE ' . aoosQuote($prefix . 'fm2_assignment_order_original_revisions') . '(revision_id VARCHAR(160) PRIMARY KEY, root_original_id VARCHAR(160) NOT NULL, revision_number INT UNSIGNED NOT NULL, current_marker TINYINT UNSIGNED NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            $partial->query('INSERT INTO ' . aoosQuote($prefix . 'fm2_assignment_order_original_revisions') . " VALUES('hostile-revision','hostile-root',1,1)");
        }
        $partial->query('CREATE TABLE ' . aoosQuote($prefix . 'decoy') . '(id INT PRIMARY KEY,value VARCHAR(40)) ENGINE=InnoDB');
        $partial->query('INSERT INTO ' . aoosQuote($prefix . 'decoy') . " VALUES(1,'must remain untouched')");
        $partialBefore = aoosState($partial, $prefix);
        assertSameValue(
            ['exitCode'=>2, 'result'=>['ok'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT','schemaVersion'=>12]],
            aoosApplyThrough(12, $partial, $prefix),
            "{$partialKind} original-evidence partial family conflicts at v12.",
        );
        assertSameValue($partialBefore, aoosState($partial, $prefix), "{$partialKind} conflict performs zero repair/create/row/counter/decoy mutation.");
        $partial->close();
    }

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
