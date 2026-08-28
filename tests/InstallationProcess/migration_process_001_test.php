<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\InstallationProcess\MariaDbInstallationProcessEnvironment;

// Specification: MIGRATION-PROCESS-001 v0.1, production MariaDB schema migration.

function migrationConnection(): mysqli
{
    $connection = new mysqli(
        getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        getenv('FMONITOR_TEST_DB_USER') ?: 'fmonitor2_demo',
        getenv('FMONITOR_TEST_DB_PASSWORD') ?: 'fmonitor2_demo_local',
        getenv('FMONITOR_TEST_DB_NAME') ?: 'fmonitor2_demo',
        (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306),
    );
    $connection->set_charset('utf8mb4');

    return $connection;
}

function migrationAdminConnection(): mysqli
{
    $connection = new mysqli(
        getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_demo_local',
        getenv('FMONITOR_TEST_DB_NAME') ?: 'fmonitor2_demo',
        (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306),
    );
    $connection->set_charset('utf8mb4');

    return $connection;
}

/** @return list<array<string, mixed>> */
function migrationRows(mysqli $connection, string $sql): array
{
    $result = $connection->query($sql);

    return $result->fetch_all(MYSQLI_ASSOC);
}

function migrationDropTables(mysqli $connection, string $prefix): void
{
    $connection->query('SET FOREIGN_KEY_CHECKS=0');
    foreach (array_reverse([
        'fm2_installation_cases',
        'fm2_assignment_orders',
        'fm2_order_installers',
        'fm2_order_artifacts',
        'fm2_process_tasks',
        'fm2_process_events',
    ]) as $table) {
        $connection->query("DROP TABLE IF EXISTS {$prefix}{$table}");
    }
    $connection->query('SET FOREIGN_KEY_CHECKS=1');
}

/** @return array<string, list<array<string, mixed>>> */
function migrationSchemaFingerprint(mysqli $connection, string $prefix): array
{
    $prefixLength = strlen($prefix) + 1;
    return [
        'tables' => migrationRows($connection, "SELECT SUBSTRING(TABLE_NAME,{$prefixLength}) AS TABLE_NAME,ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$prefix}fm2\\_%' ORDER BY TABLE_NAME"),
        'columns' => migrationRows($connection, "SELECT SUBSTRING(TABLE_NAME,{$prefixLength}) AS TABLE_NAME,COLUMN_NAME,ORDINAL_POSITION,DATA_TYPE,COLUMN_TYPE,IS_NULLABLE,EXTRA,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$prefix}fm2\\_%' ORDER BY TABLE_NAME,ORDINAL_POSITION"),
        'keys' => migrationRows($connection, "SELECT SUBSTRING(k.TABLE_NAME,{$prefixLength}) AS TABLE_NAME,tc.CONSTRAINT_TYPE,k.COLUMN_NAME,k.ORDINAL_POSITION,k.REFERENCED_TABLE_SCHEMA,CASE WHEN k.REFERENCED_TABLE_NAME IS NULL THEN NULL ELSE SUBSTRING(k.REFERENCED_TABLE_NAME,{$prefixLength}) END AS REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,rc.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.TABLE_CONSTRAINTS tc ON tc.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND tc.TABLE_NAME=k.TABLE_NAME AND tc.CONSTRAINT_NAME=k.CONSTRAINT_NAME LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND rc.TABLE_NAME=k.TABLE_NAME AND rc.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME LIKE '{$prefix}fm2\\_%' AND tc.CONSTRAINT_TYPE IN ('PRIMARY KEY','UNIQUE','FOREIGN KEY') ORDER BY TABLE_NAME,tc.CONSTRAINT_TYPE,k.COLUMN_NAME"),
        'indexes' => migrationRows($connection, "SELECT SUBSTRING(TABLE_NAME,{$prefixLength}) AS TABLE_NAME,NON_UNIQUE,GROUP_CONCAT(CONCAT(COLUMN_NAME,':',COALESCE(SUB_PART,'FULL'),':',COALESCE(COLLATION,'NULL'),':',IGNORED) ORDER BY SEQ_IN_INDEX) AS COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$prefix}fm2\\_%' GROUP BY TABLE_NAME,INDEX_NAME,NON_UNIQUE ORDER BY TABLE_NAME,NON_UNIQUE,COLUMNS"),
        'checks' => migrationRows($connection, "SELECT SUBSTRING(tc.TABLE_NAME,{$prefixLength}) AS TABLE_NAME,cc.CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS cc JOIN information_schema.TABLE_CONSTRAINTS tc ON tc.CONSTRAINT_SCHEMA=cc.CONSTRAINT_SCHEMA AND tc.TABLE_NAME=cc.TABLE_NAME AND tc.CONSTRAINT_NAME=cc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME LIKE '{$prefix}fm2\\_%' AND tc.CONSTRAINT_TYPE='CHECK' ORDER BY TABLE_NAME,cc.CHECK_CLAUSE"),
    ];
}

$tables = [
    'fm2_installation_cases',
    'fm2_assignment_orders',
    'fm2_order_installers',
    'fm2_order_artifacts',
    'fm2_process_tasks',
    'fm2_process_events',
];
$expectedColumns = [
    'fm2_installation_cases' => [
        ['id', 'bigint', 'unsigned', 'NO', 'auto_increment'],
        ['legacy_installation_object_id', 'bigint', 'unsigned', 'NO'],
        ['process_state', 'varchar', 'varchar(80)', 'NO'],
        ['actual_start_date', 'date', 'date', 'YES'],
        ['opened_at', 'varchar', 'varchar(40)', 'YES'],
        ['opened_by_user_id', 'bigint', 'unsigned', 'YES'],
        ['created_at', 'varchar', 'varchar(40)', 'NO'],
        ['updated_at', 'varchar', 'varchar(40)', 'NO'],
        ['lock_version', 'int', 'unsigned', 'NO'],
    ],
    'fm2_assignment_orders' => [
        ['id', 'bigint', 'unsigned', 'NO', 'auto_increment'], ['installation_case_id', 'bigint', 'unsigned', 'NO'],
        ['version_no', 'smallint', 'unsigned', 'NO'], ['kind', 'varchar', 'varchar(40)', 'NO'],
        ['status', 'varchar', 'varchar(40)', 'NO'], ['order_date', 'date', 'date', 'NO'],
        ['registration_number', 'varchar', 'varchar(120)', 'YES'], ['registered_at', 'varchar', 'varchar(40)', 'YES'],
        ['registration_actor_type', 'varchar', 'varchar(40)', 'YES'], ['registration_actor_id', 'varchar', 'varchar(120)', 'YES'],
        ['registration_source', 'varchar', 'varchar(40)', 'YES'], ['external_registration_id', 'varchar', 'varchar(120)', 'YES'],
        ['control_engineer_user_id', 'bigint', 'unsigned', 'NO'], ['control_engineer_fio_snapshot', 'varchar', 'varchar(300)', 'NO'],
        ['control_engineer_position_snapshot', 'varchar', 'varchar(300)', 'NO'], ['organization_form', 'varchar', 'varchar(40)', 'NO'],
        ['previous_assignment_order_id', 'bigint', 'unsigned', 'YES'], ['object_address_snapshot', 'varchar', 'varchar(500)', 'NO'],
        ['entrance_snapshot', 'varchar', 'varchar(80)', 'NO'], ['object_registration_number_snapshot', 'varchar', 'varchar(120)', 'NO'],
        ['planned_start_date_snapshot', 'date', 'date', 'NO'], ['planned_finish_date_snapshot', 'date', 'date', 'NO'],
        ['pto_act_date_snapshot', 'date', 'date', 'YES'], ['prepared_at', 'varchar', 'varchar(40)', 'NO'],
        ['prepared_by_user_id', 'bigint', 'unsigned', 'NO'],
    ],
    'fm2_order_installers' => [
        ['assignment_order_id', 'bigint', 'unsigned', 'NO'], ['installer_tab_id', 'bigint', 'unsigned', 'NO'],
        ['fio_snapshot', 'varchar', 'varchar(300)', 'NO'], ['position_snapshot', 'varchar', 'varchar(300)', 'NO'],
        ['employment_status_snapshot', 'varchar', 'varchar(40)', 'NO'], ['employed_from_snapshot', 'date', 'date', 'NO'],
        ['employed_to_snapshot', 'date', 'date', 'YES'], ['workforce_source_snapshot', 'varchar', 'varchar(80)', 'NO'],
        ['workforce_source_updated_at_snapshot', 'varchar', 'varchar(40)', 'NO'], ['valid_from', 'date', 'date', 'NO'],
        ['valid_to', 'date', 'date', 'YES'], ['change_action', 'varchar', 'varchar(40)', 'NO'],
    ],
    'fm2_order_artifacts' => [
        ['assignment_order_id', 'bigint', 'unsigned', 'NO'], ['artifact_type', 'varchar', 'varchar(40)', 'NO'],
        ['filename', 'varchar', 'varchar(500)', 'NO'], ['media_type', 'varchar', 'varchar(120)', 'NO'],
        ['byte_size', 'bigint', 'unsigned', 'NO'], ['sha256', 'char', 'char(64)', 'NO'],
    ],
    'fm2_process_tasks' => [
        ['id', 'bigint', 'unsigned', 'NO', 'auto_increment'], ['installation_case_id', 'bigint', 'unsigned', 'NO'],
        ['task_type', 'varchar', 'varchar(80)', 'NO'], ['assignee_user_id', 'bigint', 'unsigned', 'YES'],
        ['assignee_role', 'varchar', 'varchar(80)', 'YES'], ['due_date', 'date', 'date', 'YES'],
        ['status', 'varchar', 'varchar(40)', 'NO'], ['completed_at', 'varchar', 'varchar(40)', 'YES'],
        ['completed_by_user_id', 'bigint', 'unsigned', 'YES'], ['created_at', 'varchar', 'varchar(40)', 'NO'],
    ],
    'fm2_process_events' => [
        ['id', 'bigint', 'unsigned', 'NO', 'auto_increment'], ['installation_case_id', 'bigint', 'unsigned', 'NO'],
        ['event_type', 'varchar', 'varchar(80)', 'NO'], ['occurred_at', 'varchar', 'varchar(40)', 'NO'],
        ['actor_user_id', 'bigint', 'unsigned', 'NO'], ['payload_json', 'longtext', 'json', 'NO'],
    ],
];

$connection = migrationConnection();
$prefix = 't_mp001_' . bin2hex(random_bytes(6)) . '_';
$conflictPrefix = 't_mp001_conflict_' . bin2hex(random_bytes(6)) . '_';
$partialPrefix = 't_mp001_partial_' . bin2hex(random_bytes(6)) . '_';
$extraCheckPrefix = 't_mp001_check_' . bin2hex(random_bytes(6)) . '_';
$crossSchemaPrefix = 't_mp001_cross_' . bin2hex(random_bytes(6)) . '_';
$foreignSchema = 't_mp001_foreign_' . bin2hex(random_bytes(6));
$prefixIndexPrefix = 't_mp001_subpart_' . bin2hex(random_bytes(6)) . '_';
$descendingIndexPrefix = 't_mp001_desc_' . bin2hex(random_bytes(6)) . '_';
$ignoredIndexPrefix = 't_mp001_ignored_' . bin2hex(random_bytes(6)) . '_';
$latinSnapshotPrefix = 't_mp001_latin_' . bin2hex(random_bytes(6)) . '_';
$utf8mb4CollationPrefix = 't_mp001_utf8coll_' . bin2hex(random_bytes(6)) . '_';
$adminConnection = null;

try {
    $result = ProductionProcessSchemaMigration::apply($connection, $prefix);
    assertSameValue([
        'applied' => true,
        'schemaVersion' => 1,
        'tablesCreated' => array_map(static fn (string $table): string => $prefix . $table, $tables),
    ], $result, 'MIGRATION-PROCESS-001 must create the six production process tables in dependency order.');

    $catalogTables = migrationRows($connection, "SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$prefix}fm2\\_%' ORDER BY TABLE_NAME");
    assertSameValue(6, count($catalogTables), 'The migration must create exactly six prefixed process tables.');
    foreach ($catalogTables as $table) {
        assertSameValue('InnoDB', $table['ENGINE'], 'Every process table must use InnoDB.');
        assertSameValue(true, str_starts_with((string) $table['TABLE_COLLATION'], 'utf8mb4_'), 'Every process table must use utf8mb4.');
    }

    foreach ($expectedColumns as $table => $columns) {
        $actual = migrationRows($connection, "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}{$table}' ORDER BY ORDINAL_POSITION");
        $normalized = array_map(static function (array $column): array {
            $typeContract = match ($column['DATA_TYPE']) {
                'bigint', 'int', 'smallint' => str_contains((string) $column['COLUMN_TYPE'], 'unsigned') ? 'unsigned' : (string) $column['COLUMN_TYPE'],
                'longtext' => str_contains(strtolower((string) $column['COLUMN_TYPE']), 'longtext') ? 'json' : (string) $column['COLUMN_TYPE'],
                default => (string) $column['COLUMN_TYPE'],
            };
            return [$column['COLUMN_NAME'], $column['DATA_TYPE'], $typeContract, $column['IS_NULLABLE'], $column['EXTRA']];
        }, $actual);
        $columnsWithExtra = array_map(static fn (array $column): array => [
            $column[0], $column[1], $column[2], $column[3], $column[4] ?? '',
        ], $columns);
        assertSameValue($columnsWithExtra, $normalized, "{$table} columns, types, nullability and auto-increment must match the approved schema literally.");
    }

    $primaryKeys = migrationRows($connection, "SELECT TABLE_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY ORDINAL_POSITION) AS COLUMNS FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$prefix}fm2\\_%' AND CONSTRAINT_NAME='PRIMARY' GROUP BY TABLE_NAME ORDER BY TABLE_NAME");
    assertSameValue([
        ['TABLE_NAME' => $prefix . 'fm2_assignment_orders', 'COLUMNS' => 'id'],
        ['TABLE_NAME' => $prefix . 'fm2_installation_cases', 'COLUMNS' => 'id'],
        ['TABLE_NAME' => $prefix . 'fm2_order_artifacts', 'COLUMNS' => 'assignment_order_id,artifact_type'],
        ['TABLE_NAME' => $prefix . 'fm2_order_installers', 'COLUMNS' => 'assignment_order_id,installer_tab_id'],
        ['TABLE_NAME' => $prefix . 'fm2_process_events', 'COLUMNS' => 'id'],
        ['TABLE_NAME' => $prefix . 'fm2_process_tasks', 'COLUMNS' => 'id'],
    ], $primaryKeys, 'Primary-key columns and order must match the approved schema.');
    $uniqueKeys = migrationRows($connection, "SELECT TABLE_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY ORDINAL_POSITION) AS COLUMNS FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$prefix}fm2\\_%' AND CONSTRAINT_NAME<>'PRIMARY' AND CONSTRAINT_NAME IN (SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_TYPE='UNIQUE') GROUP BY TABLE_NAME,CONSTRAINT_NAME ORDER BY TABLE_NAME,COLUMNS");
    assertSameValue([
        ['TABLE_NAME' => $prefix . 'fm2_assignment_orders', 'COLUMNS' => 'installation_case_id,version_no'],
        ['TABLE_NAME' => $prefix . 'fm2_installation_cases', 'COLUMNS' => 'legacy_installation_object_id'],
    ], $uniqueKeys, 'Unique-key columns and order must match the approved schema.');
    $foreignKeys = migrationRows($connection, "SELECT k.TABLE_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME AND r.TABLE_NAME=k.TABLE_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME LIKE '{$prefix}fm2\\_%' AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.TABLE_NAME,k.COLUMN_NAME");
    assertSameValue([
        ['TABLE_NAME'=>$prefix.'fm2_assignment_orders','COLUMN_NAME'=>'installation_case_id','REFERENCED_TABLE_NAME'=>$prefix.'fm2_installation_cases','REFERENCED_COLUMN_NAME'=>'id','DELETE_RULE'=>'RESTRICT'],
        ['TABLE_NAME'=>$prefix.'fm2_assignment_orders','COLUMN_NAME'=>'previous_assignment_order_id','REFERENCED_TABLE_NAME'=>$prefix.'fm2_assignment_orders','REFERENCED_COLUMN_NAME'=>'id','DELETE_RULE'=>'RESTRICT'],
        ['TABLE_NAME'=>$prefix.'fm2_order_artifacts','COLUMN_NAME'=>'assignment_order_id','REFERENCED_TABLE_NAME'=>$prefix.'fm2_assignment_orders','REFERENCED_COLUMN_NAME'=>'id','DELETE_RULE'=>'RESTRICT'],
        ['TABLE_NAME'=>$prefix.'fm2_order_installers','COLUMN_NAME'=>'assignment_order_id','REFERENCED_TABLE_NAME'=>$prefix.'fm2_assignment_orders','REFERENCED_COLUMN_NAME'=>'id','DELETE_RULE'=>'RESTRICT'],
        ['TABLE_NAME'=>$prefix.'fm2_process_events','COLUMN_NAME'=>'installation_case_id','REFERENCED_TABLE_NAME'=>$prefix.'fm2_installation_cases','REFERENCED_COLUMN_NAME'=>'id','DELETE_RULE'=>'RESTRICT'],
        ['TABLE_NAME'=>$prefix.'fm2_process_tasks','COLUMN_NAME'=>'installation_case_id','REFERENCED_TABLE_NAME'=>$prefix.'fm2_installation_cases','REFERENCED_COLUMN_NAME'=>'id','DELETE_RULE'=>'RESTRICT'],
    ], $foreignKeys, 'Foreign-key mappings and rejecting delete rules must match the approved schema.');
    $secondaryIndexes = migrationRows($connection, "SELECT TABLE_NAME, GROUP_CONCAT(CONCAT(COLUMN_NAME,':',COALESCE(SUB_PART,'FULL'),':',COALESCE(COLLATION,'NULL'),':',IGNORED) ORDER BY SEQ_IN_INDEX) AS COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$prefix}fm2\\_%' AND INDEX_NAME <> 'PRIMARY' AND NON_UNIQUE=1 GROUP BY TABLE_NAME, INDEX_NAME");
    usort($secondaryIndexes, static fn (array $left, array $right): int => [$left['TABLE_NAME'],$left['COLUMNS']] <=> [$right['TABLE_NAME'],$right['COLUMNS']]);
    assertSameValue([
        ['TABLE_NAME'=>$prefix.'fm2_assignment_orders','COLUMNS'=>'installation_case_id:FULL:A:NO,status:FULL:A:NO'],
        ['TABLE_NAME'=>$prefix.'fm2_assignment_orders','COLUMNS'=>'previous_assignment_order_id:FULL:A:NO'],
        ['TABLE_NAME'=>$prefix.'fm2_process_events','COLUMNS'=>'installation_case_id:FULL:A:NO,occurred_at:FULL:A:NO'],
        ['TABLE_NAME'=>$prefix.'fm2_process_tasks','COLUMNS'=>'installation_case_id:FULL:A:NO'],
        ['TABLE_NAME'=>$prefix.'fm2_process_tasks','COLUMNS'=>'status:FULL:A:NO,assignee_role:FULL:A:NO,due_date:FULL:A:NO'],
    ], $secondaryIndexes, 'Only the three contracted indexes and two MariaDB-required foreign-key support indexes may exist.');
    $jsonChecks = migrationRows($connection, "SELECT cc.CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS cc JOIN information_schema.TABLE_CONSTRAINTS tc ON tc.CONSTRAINT_SCHEMA=cc.CONSTRAINT_SCHEMA AND tc.CONSTRAINT_NAME=cc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME='{$prefix}fm2_process_events' AND tc.CONSTRAINT_TYPE='CHECK'");
    $hasJsonValidityCheck = false;
    foreach ($jsonChecks as $check) {
        $hasJsonValidityCheck = $hasJsonValidityCheck || str_contains(
            str_replace(['`', ' '], '', strtolower((string) $check['CHECK_CLAUSE'])),
            'json_valid(payload_json)',
        );
    }
    assertSameValue(true, $hasJsonValidityCheck, 'payload_json must have MariaDB JSON validity enforcement.');

    $connection->query("INSERT INTO {$prefix}fm2_installation_cases (legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (4512,'needs_assignment_order','2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)");
    $externalFacts = new class {
        public function actorCanPrepareAssignmentOrder(int $actorId): bool { return $actorId === 18; }
        public function getInstallationObjectSnapshot(int $id): array { return ['address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','objectRegistrationNumber'=>'77-000123','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2026-12-20','ptoActDate'=>null]; }
        public function findInstallerSnapshot(int|string $tabId): ?array { return $tabId === 1042 ? ['tabId'=>1042,'fullName'=>'Иванов Иван Иванович','position'=>'Электромеханик по лифтам','status'=>'employed','employedFrom'=>'2024-02-01','employedTo'=>null,'source'=>'one_c_zup_via_bitrix','sourceUpdatedAt'=>'2026-08-26T18:00:00+03:00'] : null; }
        public function findEngineerSnapshot(int $userId): ?array { return $userId === 73 ? ['userId'=>73,'fullName'=>'Петров Пётр Петрович','position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer'] : null; }
        public function renderAssignmentOrder(array $input): array { return [
            ['type'=>'order','filename'=>'assignment-order-4512-v1.pdf','mediaType'=>'application/pdf','bytes'=>'ORDER-PREPARE-002 example A order document'],
            ['type'=>'appendix','filename'=>'assignment-order-4512-v1-appendix.pdf','mediaType'=>'application/pdf','bytes'=>'ORDER-PREPARE-002 example A appendix'],
        ]; }
        public function now(): string { return '2026-08-26T21:30:00+00:00'; }
    };
    $process = new InstallationProcess(new MariaDbInstallationProcessEnvironment($connection, $externalFacts, $prefix));
    assertSameValue(['accepted'=>true,'assignmentOrderVersion'=>1,'status'=>'prepared','assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual'], $process->prepareAssignmentOrder(4512, [1042], 73, 18), 'The approved persistence command must work on the migrated schema.');

    assertSameValue([
        'applied' => false,
        'schemaVersion' => 1,
        'tablesCreated' => [],
    ], ProductionProcessSchemaMigration::apply($connection, $prefix), 'A compatible repeated migration must perform no DDL.');
    $connection->close();
    unset($process);
    $connection = migrationConnection();
    $forbiddenExternalFacts = new class { public function __call(string $name, array $arguments): never { throw new LogicException("External fact {$name} must not be read on reload."); } };
    $reloadedProcess = new InstallationProcess(new MariaDbInstallationProcessEnvironment($connection, $forbiddenExternalFacts, $prefix));
    assertSameValue([
        'installationObjectId'=>4512,
        'processState'=>'assignment_order_prepared',
        'assignmentOrders'=>[[
            'version'=>1,'status'=>'prepared','registrationNumber'=>null,'assignmentOrderDate'=>'2026-08-27','organizationType'=>'individual',
            'installationObjectSnapshot'=>['address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','objectRegistrationNumber'=>'77-000123','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2026-12-20','ptoActDate'=>null],
            'installers'=>[['tabId'=>1042,'fullName'=>'Иванов Иван Иванович','position'=>'Электромеханик по лифтам','status'=>'employed','employedFrom'=>'2024-02-01','employedTo'=>null,'source'=>'one_c_zup_via_bitrix','sourceUpdatedAt'=>'2026-08-26T18:00:00+03:00']],
            'controlEngineer'=>['userId'=>73,'fullName'=>'Петров Пётр Петрович','position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer'],
            'artifacts'=>[
                ['type'=>'order','filename'=>'assignment-order-4512-v1.pdf','mediaType'=>'application/pdf','size'=>42,'sha256'=>'71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4'],
                ['type'=>'appendix','filename'=>'assignment-order-4512-v1-appendix.pdf','mediaType'=>'application/pdf','size'=>36,'sha256'=>'6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7'],
            ],
        ]],
        'assignments'=>[
            ['role'=>'installer','tabId'=>1042,'assignmentOrderVersion'=>1,'status'=>'preliminary'],
            ['role'=>'control_engineer','userId'=>73,'assignmentOrderVersion'=>1,'status'=>'preliminary'],
        ],
        'openTasks'=>[],'installationOpened'=>false,'checklistAvailable'=>false,
        'events'=>[['type'=>'assignment_order_prepared','occurredAt'=>'2026-08-26T21:30:00+00:00','actorId'=>18,'payload'=>[
            'assignmentOrderVersion'=>1,'assignmentOrderDate'=>'2026-08-27','installerTabIds'=>[1042],'controlEngineerUserId'=>73,'organizationType'=>'individual',
            'artifactSha256'=>['order'=>'71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4','appendix'=>'6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7'],
        ]]],
    ], $reloadedProcess->getInstallationObjectProcess(4512), 'The full approved projection must survive repeat migration and a new connection without external reads.');

    $connection->query("CREATE TABLE {$partialPrefix}fm2_installation_cases (
        id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
        legacy_installation_object_id BIGINT UNSIGNED NOT NULL,
        process_state VARCHAR(80) NOT NULL,
        actual_start_date DATE NULL,
        opened_at VARCHAR(40) NULL,
        opened_by_user_id BIGINT UNSIGNED NULL,
        created_at VARCHAR(40) NOT NULL,
        updated_at VARCHAR(40) NOT NULL,
        lock_version INT UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY (legacy_installation_object_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    assertSameValue([
        'applied'=>true,
        'schemaVersion'=>1,
        'tablesCreated'=>array_map(static fn (string $table): string => $partialPrefix . $table, array_slice($tables, 1)),
    ], ProductionProcessSchemaMigration::apply($connection, $partialPrefix), 'A compatible partial deployment must create only missing tables in dependency order.');
    assertSameValue(
        migrationSchemaFingerprint($connection, $prefix),
        migrationSchemaFingerprint($connection, $partialPrefix),
        'Recovered partial deployment must expose the complete exact catalog contract.',
    );

    $connection->query("CREATE TABLE {$conflictPrefix}fm2_installation_cases (id BIGINT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    assertSameValue([
        'applied' => false,
        'schemaVersion' => 1,
        'reason' => 'SCHEMA_MIGRATION_CONFLICT',
        'conflictingTables' => [$conflictPrefix . 'fm2_installation_cases'],
    ], ProductionProcessSchemaMigration::apply($connection, $conflictPrefix), 'An incompatible existing table must be rejected before DDL.');
    foreach (array_slice($tables, 1) as $table) {
        assertSameValue([], migrationRows($connection, "SHOW TABLES LIKE '{$conflictPrefix}{$table}'"), 'Conflict preflight must not create missing tables.');
    }

    try {
        ProductionProcessSchemaMigration::apply($connection, 'invalid-prefix;');
        throw new TestFailure('An invalid table prefix must be rejected.');
    } catch (InvalidArgumentException) {
    }

    ProductionProcessSchemaMigration::apply($connection, $extraCheckPrefix);
    $connection->query("ALTER TABLE {$extraCheckPrefix}fm2_installation_cases ADD CONSTRAINT restrictive_process_state CHECK (process_state = 'needs_assignment_order')");
    $connection->query("INSERT INTO {$extraCheckPrefix}fm2_installation_cases (id,legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (7001,57001,'needs_assignment_order','2026-08-28T01:00:00+03:00','2026-08-28T01:00:00+03:00',1)");
    $extraCheckCatalogBefore = migrationSchemaFingerprint($connection, $extraCheckPrefix);
    $extraCheckRowsBefore = migrationRows($connection, "SELECT * FROM {$extraCheckPrefix}fm2_installation_cases ORDER BY id");
    $extraCheckResult = ProductionProcessSchemaMigration::apply($connection, $extraCheckPrefix);
    $extraCheckCatalogAfter = migrationSchemaFingerprint($connection, $extraCheckPrefix);
    $extraCheckRowsAfter = migrationRows($connection, "SELECT * FROM {$extraCheckPrefix}fm2_installation_cases ORDER BY id");

    $adminConnection = migrationAdminConnection();
    ProductionProcessSchemaMigration::apply($adminConnection, $crossSchemaPrefix);
    $adminConnection->query("CREATE DATABASE `{$foreignSchema}` DEFAULT CHARSET=utf8mb4");
    $adminConnection->query("CREATE TABLE `{$foreignSchema}`.`{$crossSchemaPrefix}fm2_installation_cases` (id BIGINT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $fkRow = migrationRows($adminConnection, "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$crossSchemaPrefix}fm2_process_events' AND COLUMN_NAME='installation_case_id' AND REFERENCED_TABLE_NAME IS NOT NULL")[0];
    $eventFk = (string) $fkRow['CONSTRAINT_NAME'];
    $adminConnection->query("ALTER TABLE {$crossSchemaPrefix}fm2_process_events DROP FOREIGN KEY `{$eventFk}`");
    $adminConnection->query("ALTER TABLE {$crossSchemaPrefix}fm2_process_events ADD FOREIGN KEY (installation_case_id) REFERENCES `{$foreignSchema}`.`{$crossSchemaPrefix}fm2_installation_cases` (id)");
    $adminConnection->query("INSERT INTO `{$foreignSchema}`.`{$crossSchemaPrefix}fm2_installation_cases` (id) VALUES (8001)");
    $adminConnection->query("INSERT INTO {$crossSchemaPrefix}fm2_process_events (id,installation_case_id,event_type,occurred_at,actor_user_id,payload_json) VALUES (8101,8001,'assignment_order_prepared','2026-08-28T02:00:00+03:00',18,'{\"sentinel\":true}')");
    $crossSchemaCatalogBefore = migrationSchemaFingerprint($adminConnection, $crossSchemaPrefix);
    $crossSchemaRowsBefore = migrationRows($adminConnection, "SELECT * FROM {$crossSchemaPrefix}fm2_process_events ORDER BY id");
    $foreignParentRowsBefore = migrationRows($adminConnection, "SELECT * FROM `{$foreignSchema}`.`{$crossSchemaPrefix}fm2_installation_cases` ORDER BY id");
    $crossSchemaResult = ProductionProcessSchemaMigration::apply($adminConnection, $crossSchemaPrefix);
    $crossSchemaCatalogAfter = migrationSchemaFingerprint($adminConnection, $crossSchemaPrefix);
    $crossSchemaRowsAfter = migrationRows($adminConnection, "SELECT * FROM {$crossSchemaPrefix}fm2_process_events ORDER BY id");
    $foreignParentRowsAfter = migrationRows($adminConnection, "SELECT * FROM `{$foreignSchema}`.`{$crossSchemaPrefix}fm2_installation_cases` ORDER BY id");

    assertSameValue($extraCheckCatalogBefore, $extraCheckCatalogAfter, 'Extra-CHECK conflict must leave the complete catalog, including CHECK clauses, unchanged.');
    assertSameValue($extraCheckRowsBefore, $extraCheckRowsAfter, 'Extra-CHECK conflict must leave sentinel process data unchanged.');
    assertSameValue($crossSchemaCatalogBefore, $crossSchemaCatalogAfter, 'Cross-schema-FK conflict must leave the complete catalog, including referenced schema, unchanged.');
    assertSameValue($crossSchemaRowsBefore, $crossSchemaRowsAfter, 'Cross-schema-FK conflict must leave local sentinel event data unchanged.');
    assertSameValue($foreignParentRowsBefore, $foreignParentRowsAfter, 'Cross-schema-FK conflict must leave foreign sentinel parent data unchanged.');

    ProductionProcessSchemaMigration::apply($connection, $prefixIndexPrefix);
    $prefixIndexRow = migrationRows($connection, "SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefixIndexPrefix}fm2_process_tasks' AND NON_UNIQUE=1 GROUP BY INDEX_NAME HAVING GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)='status,assignee_role,due_date'")[0];
    $prefixIndexName = (string) $prefixIndexRow['INDEX_NAME'];
    $connection->query("ALTER TABLE {$prefixIndexPrefix}fm2_process_tasks DROP INDEX `{$prefixIndexName}`, ADD KEY (status(1),assignee_role,due_date)");
    $connection->query("INSERT INTO {$prefixIndexPrefix}fm2_installation_cases (id,legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (9001,59001,'needs_assignment_order','2026-08-28T03:00:00+03:00','2026-08-28T03:00:00+03:00',1)");
    $connection->query("INSERT INTO {$prefixIndexPrefix}fm2_process_tasks (id,installation_case_id,task_type,status,created_at) VALUES (9101,9001,'prepare_assignment_order','open','2026-08-28T03:00:00+03:00')");
    $prefixIndexCatalogBefore = migrationSchemaFingerprint($connection, $prefixIndexPrefix);
    $prefixIndexObserved = array_filter($prefixIndexCatalogBefore['indexes'], static fn (array $index): bool => str_contains((string) $index['COLUMNS'], 'status:1:A'));
    assertSameValue(1, count($prefixIndexObserved), 'Catalog fingerprint must observe the literal one-character ascending index prefix.');
    $prefixIndexRowsBefore = migrationRows($connection, "SELECT * FROM {$prefixIndexPrefix}fm2_process_tasks ORDER BY id");
    $prefixIndexResult = ProductionProcessSchemaMigration::apply($connection, $prefixIndexPrefix);
    assertSameValue($prefixIndexCatalogBefore, migrationSchemaFingerprint($connection, $prefixIndexPrefix), 'Prefix-index conflict must leave SUB_PART-sensitive catalog unchanged.');
    assertSameValue($prefixIndexRowsBefore, migrationRows($connection, "SELECT * FROM {$prefixIndexPrefix}fm2_process_tasks ORDER BY id"), 'Prefix-index conflict must leave sentinel task unchanged.');

    ProductionProcessSchemaMigration::apply($connection, $descendingIndexPrefix);
    $descendingIndexRow = migrationRows($connection, "SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$descendingIndexPrefix}fm2_process_events' AND NON_UNIQUE=1 GROUP BY INDEX_NAME HAVING GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)='installation_case_id,occurred_at'")[0];
    $descendingIndexName = (string) $descendingIndexRow['INDEX_NAME'];
    $connection->query("ALTER TABLE {$descendingIndexPrefix}fm2_process_events DROP INDEX `{$descendingIndexName}`, ADD KEY (installation_case_id,occurred_at DESC)");
    $connection->query("INSERT INTO {$descendingIndexPrefix}fm2_installation_cases (id,legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (9201,59201,'needs_assignment_order','2026-08-28T04:00:00+03:00','2026-08-28T04:00:00+03:00',1)");
    $connection->query("INSERT INTO {$descendingIndexPrefix}fm2_process_events (id,installation_case_id,event_type,occurred_at,actor_user_id,payload_json) VALUES (9301,9201,'assignment_order_prepared','2026-08-28T04:00:00+03:00',18,'{\"sentinel\":true}')");
    $descendingIndexCatalogBefore = migrationSchemaFingerprint($connection, $descendingIndexPrefix);
    $descendingIndexObserved = array_filter($descendingIndexCatalogBefore['indexes'], static fn (array $index): bool => str_contains((string) $index['COLUMNS'], 'occurred_at:FULL:D'));
    assertSameValue(1, count($descendingIndexObserved), 'Catalog fingerprint must observe the literal full-column descending index direction.');
    $descendingIndexRowsBefore = migrationRows($connection, "SELECT * FROM {$descendingIndexPrefix}fm2_process_events ORDER BY id");
    $descendingIndexResult = ProductionProcessSchemaMigration::apply($connection, $descendingIndexPrefix);
    assertSameValue($descendingIndexCatalogBefore, migrationSchemaFingerprint($connection, $descendingIndexPrefix), 'Descending-index conflict must leave COLLATION-sensitive catalog unchanged.');
    assertSameValue($descendingIndexRowsBefore, migrationRows($connection, "SELECT * FROM {$descendingIndexPrefix}fm2_process_events ORDER BY id"), 'Descending-index conflict must leave sentinel event unchanged.');

    ProductionProcessSchemaMigration::apply($connection, $ignoredIndexPrefix);
    $ignoredIndexRow = migrationRows($connection, "SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$ignoredIndexPrefix}fm2_process_tasks' AND NON_UNIQUE=1 GROUP BY INDEX_NAME HAVING GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)='status,assignee_role,due_date'")[0];
    $ignoredIndexName = (string) $ignoredIndexRow['INDEX_NAME'];
    $connection->query("ALTER TABLE {$ignoredIndexPrefix}fm2_process_tasks ALTER INDEX `{$ignoredIndexName}` IGNORED");
    $connection->query("INSERT INTO {$ignoredIndexPrefix}fm2_installation_cases (id,legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (9401,59401,'needs_assignment_order','2026-08-28T05:00:00+03:00','2026-08-28T05:00:00+03:00',1)");
    $connection->query("INSERT INTO {$ignoredIndexPrefix}fm2_process_tasks (id,installation_case_id,task_type,status,created_at) VALUES (9501,9401,'prepare_assignment_order','open','2026-08-28T05:00:00+03:00')");
    $ignoredIndexCatalogBefore = migrationSchemaFingerprint($connection, $ignoredIndexPrefix);
    $ignoredIndexObserved = array_filter($ignoredIndexCatalogBefore['indexes'], static fn (array $index): bool => str_contains((string) $index['COLUMNS'], 'status:FULL:A:YES'));
    assertSameValue(1, count($ignoredIndexObserved), 'Catalog fingerprint must directly observe the required index as IGNORED.');
    $ignoredIndexRowsBefore = migrationRows($connection, "SELECT * FROM {$ignoredIndexPrefix}fm2_process_tasks ORDER BY id");
    $ignoredIndexResult = ProductionProcessSchemaMigration::apply($connection, $ignoredIndexPrefix);
    assertSameValue($ignoredIndexCatalogBefore, migrationSchemaFingerprint($connection, $ignoredIndexPrefix), 'Ignored-index conflict must leave IGNORED-sensitive catalog unchanged.');
    assertSameValue($ignoredIndexRowsBefore, migrationRows($connection, "SELECT * FROM {$ignoredIndexPrefix}fm2_process_tasks ORDER BY id"), 'Ignored-index conflict must leave sentinel task unchanged.');

    ProductionProcessSchemaMigration::apply($connection, $latinSnapshotPrefix);
    $connection->query("ALTER TABLE {$latinSnapshotPrefix}fm2_assignment_orders MODIFY control_engineer_fio_snapshot VARCHAR(300) CHARACTER SET latin1 NOT NULL");
    $connection->query("INSERT INTO {$latinSnapshotPrefix}fm2_installation_cases (id,legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (9601,59601,'assignment_order_prepared','2026-08-28T06:00:00+03:00','2026-08-28T06:00:00+03:00',2)");
    $connection->query("INSERT INTO {$latinSnapshotPrefix}fm2_assignment_orders (id,installation_case_id,version_no,kind,status,order_date,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot,prepared_at,prepared_by_user_id) VALUES (9701,9601,1,'initial','prepared','2026-08-28',73,'Sentinel Engineer','Construction Control Engineer','individual','Sentinel address','2','77-0059601','2026-10-05','2026-12-20','2026-08-28T06:00:00+03:00',18)");
    $latinSnapshotCatalogBefore = migrationSchemaFingerprint($connection, $latinSnapshotPrefix);
    $latinSnapshotObserved = array_filter($latinSnapshotCatalogBefore['columns'], static fn (array $column): bool => $column['TABLE_NAME']==='fm2_assignment_orders' && $column['COLUMN_NAME']==='control_engineer_fio_snapshot' && $column['CHARACTER_SET_NAME']==='latin1' && str_starts_with((string)$column['COLLATION_NAME'], 'latin1_'));
    assertSameValue(1, count($latinSnapshotObserved), 'Catalog fingerprint must directly observe latin1 charset and collation on the Cyrillic-relevant engineer-name snapshot.');
    $latinSnapshotRowsBefore = migrationRows($connection, "SELECT * FROM {$latinSnapshotPrefix}fm2_assignment_orders ORDER BY id");
    $latinSnapshotResult = ProductionProcessSchemaMigration::apply($connection, $latinSnapshotPrefix);
    assertSameValue($latinSnapshotCatalogBefore, migrationSchemaFingerprint($connection, $latinSnapshotPrefix), 'Per-column charset conflict must leave charset/collation-sensitive catalog unchanged.');
    assertSameValue($latinSnapshotRowsBefore, migrationRows($connection, "SELECT * FROM {$latinSnapshotPrefix}fm2_assignment_orders ORDER BY id"), 'Per-column charset conflict must leave sentinel assignment order unchanged.');

    ProductionProcessSchemaMigration::apply($connection, $utf8mb4CollationPrefix);
    $connection->query("ALTER TABLE {$utf8mb4CollationPrefix}fm2_installation_cases MODIFY process_state VARCHAR(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL");
    $connection->query("INSERT INTO {$utf8mb4CollationPrefix}fm2_installation_cases (id,legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (9801,59801,'needs_assignment_order','2026-08-28T07:00:00+03:00','2026-08-28T07:00:00+03:00',1)");
    $utf8mb4CollationCatalogBefore = migrationSchemaFingerprint($connection, $utf8mb4CollationPrefix);
    $utf8mb4CollationObserved = array_filter($utf8mb4CollationCatalogBefore['columns'], static fn (array $column): bool => $column['TABLE_NAME']==='fm2_installation_cases' && $column['COLUMN_NAME']==='process_state' && $column['CHARACTER_SET_NAME']==='utf8mb4' && $column['COLLATION_NAME']==='utf8mb4_bin');
    assertSameValue(1, count($utf8mb4CollationObserved), 'Catalog fingerprint must directly observe the alternate available utf8mb4 column collation.');
    $utf8mb4CollationRowsBefore = migrationRows($connection, "SELECT * FROM {$utf8mb4CollationPrefix}fm2_installation_cases ORDER BY id");
    $utf8mb4CollationResult = ProductionProcessSchemaMigration::apply($connection, $utf8mb4CollationPrefix);
    assertSameValue($utf8mb4CollationCatalogBefore, migrationSchemaFingerprint($connection, $utf8mb4CollationPrefix), 'Compatible utf8mb4 collation repeat must leave catalog unchanged.');
    assertSameValue($utf8mb4CollationRowsBefore, migrationRows($connection, "SELECT * FROM {$utf8mb4CollationPrefix}fm2_installation_cases ORDER BY id"), 'Compatible utf8mb4 collation repeat must leave sentinel case unchanged.');

    assertSameValue([
        [
            'applied'=>false,'schemaVersion'=>1,'reason'=>'SCHEMA_MIGRATION_CONFLICT',
            'conflictingTables'=>[$extraCheckPrefix . 'fm2_installation_cases'],
        ],
        [
            'applied'=>false,'schemaVersion'=>1,'reason'=>'SCHEMA_MIGRATION_CONFLICT',
            'conflictingTables'=>[$crossSchemaPrefix . 'fm2_process_events'],
        ],
        [
            'applied'=>false,'schemaVersion'=>1,'reason'=>'SCHEMA_MIGRATION_CONFLICT',
            'conflictingTables'=>[$prefixIndexPrefix . 'fm2_process_tasks'],
        ],
        [
            'applied'=>false,'schemaVersion'=>1,'reason'=>'SCHEMA_MIGRATION_CONFLICT',
            'conflictingTables'=>[$descendingIndexPrefix . 'fm2_process_events'],
        ],
        [
            'applied'=>false,'schemaVersion'=>1,'reason'=>'SCHEMA_MIGRATION_CONFLICT',
            'conflictingTables'=>[$ignoredIndexPrefix . 'fm2_process_tasks'],
        ],
        [
            'applied'=>false,'schemaVersion'=>1,'reason'=>'SCHEMA_MIGRATION_CONFLICT',
            'conflictingTables'=>[$latinSnapshotPrefix . 'fm2_assignment_orders'],
        ],
        [
            'applied'=>false,'schemaVersion'=>1,'tablesCreated'=>[],
        ],
    ], [$extraCheckResult, $crossSchemaResult, $prefixIndexResult, $descendingIndexResult, $ignoredIndexResult, $latinSnapshotResult, $utf8mb4CollationResult], 'Compatibility must reject non-utf8mb4 columns while accepting an alternate utf8mb4 collation.');

    echo "PASS: MIGRATION-PROCESS-001 production process schema migration\n";
} finally {
    try { $connection->close(); } catch (Throwable) {}
    try { $adminConnection?->close(); } catch (Throwable) {}
    $cleanup = migrationConnection();
    migrationDropTables($cleanup, $prefix);
    migrationDropTables($cleanup, $conflictPrefix);
    migrationDropTables($cleanup, $partialPrefix);
    migrationDropTables($cleanup, $extraCheckPrefix);
    migrationDropTables($cleanup, $prefixIndexPrefix);
    migrationDropTables($cleanup, $descendingIndexPrefix);
    migrationDropTables($cleanup, $ignoredIndexPrefix);
    migrationDropTables($cleanup, $latinSnapshotPrefix);
    migrationDropTables($cleanup, $utf8mb4CollationPrefix);
    $cleanup->close();
    $adminCleanup = migrationAdminConnection();
    migrationDropTables($adminCleanup, $crossSchemaPrefix);
    $adminCleanup->query("DROP DATABASE IF EXISTS `{$foreignSchema}`");
    $adminCleanup->close();
}
