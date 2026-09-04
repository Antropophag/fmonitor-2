<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/AssignmentOrderOriginalDatabaseSetupV1.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigration;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigrationStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationDatabaseFixture;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationFixtureConflict;
use FMonitor2\Tests\Support\AssignmentOrderOriginalDatabaseSetupV1 as Contract;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v10, setup Gate 2.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$token = bin2hex(random_bytes(6));
$databases = array_map(static fn (string $axis): string => "t_aoou_{$axis}_{$token}", ['clean','partial','populated','conflict','fixture']);
$prefix = 'aoou_';
$admin = new mysqli($host, $user, $password, '', $port);
$admin->set_charset('utf8mb4');

$quote = static fn (string $name): string => '`' . str_replace('`', '``', $name) . '`';
$connect = static function (string $database) use ($host, $port, $user, $password): mysqli {
    $db = new mysqli($host, $user, $password, $database, $port);
    $db->set_charset('utf8mb4');
    return $db;
};
$tableNames = static function (mysqli $db, string $like = '%'): array {
    $statement = $db->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE ? ORDER BY BINARY TABLE_NAME');
    $statement->bind_param('s', $like); $statement->execute();
    return array_column($statement->get_result()->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
};
$columns = static function (mysqli $db, string $table): array {
    $statement = $db->prepare('SELECT COLUMN_NAME,LOWER(COLUMN_TYPE) COLUMN_TYPE,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');
    $statement->bind_param('s', $table); $statement->execute();
    return array_map('array_values', $statement->get_result()->fetch_all(MYSQLI_ASSOC));
};
$keys = static function (mysqli $db, string $table): array {
    $statement = $db->prepare("SELECT INDEX_NAME,NON_UNIQUE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? GROUP BY INDEX_NAME,NON_UNIQUE");
    $statement->bind_param('s', $table); $statement->execute();
    $rows = array_map(static fn (array $row): array => [$row['INDEX_NAME']==='PRIMARY'?'PRIMARY':((int)$row['NON_UNIQUE']===0?'UNIQUE':'INDEX'),$row['COLUMNS']], $statement->get_result()->fetch_all(MYSQLI_ASSOC));
    sort($rows); return $rows;
};
$foreignKeys = static function (mysqli $db, string $table, string $prefix): array {
    $statement = $db->prepare('SELECT k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME=? AND k.REFERENCED_TABLE_NAME IS NOT NULL');
    $statement->bind_param('s', $table); $statement->execute();
    $rows = array_map(static fn(array $row):array => [$row['COLUMN_NAME'],substr($row['REFERENCED_TABLE_NAME'],strlen($prefix)),$row['REFERENCED_COLUMN_NAME'],$row['UPDATE_RULE'].'/'.$row['DELETE_RULE']], $statement->get_result()->fetch_all(MYSQLI_ASSOC));
    sort($rows); return $rows;
};
$checkCount = static function (mysqli $db, string $table): int {
    $statement = $db->prepare('SELECT COUNT(*) n FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_TYPE=\'CHECK\'');
    $statement->bind_param('s', $table); $statement->execute();
    return (int) $statement->get_result()->fetch_assoc()['n'];
};
$tableProperties = static function (mysqli $db, string $table): array {
    $statement = $db->prepare('SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $statement->bind_param('s', $table); $statement->execute();
    return array_values($statement->get_result()->fetch_assoc());
};
$snapshot = static function (mysqli $db): string {
    $result = $db->query("SELECT TABLE_NAME,COLUMN_NAME,ORDINAL_POSITION,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() ORDER BY BINARY TABLE_NAME,ORDINAL_POSITION");
    return json_encode($result->fetch_all(MYSQLI_ASSOC), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
};
$stateSnapshot = static function (mysqli $db) use ($tableNames, $quote, $snapshot): string {
    $rows = [];
    foreach ($tableNames($db) as $table) {
        $tableRows = $db->query('SELECT * FROM ' . $quote($table))->fetch_all(MYSQLI_ASSOC);
        foreach ($tableRows as &$row) ksort($row, SORT_STRING);
        unset($row);
        usort($tableRows, static fn (array $left, array $right): int => json_encode($left, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) <=> json_encode($right, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $rows[$table] = $tableRows;
    }
    return json_encode(['schema' => $snapshot($db), 'rows' => $rows], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
};

$created = [];
try {
    foreach ($databases as $database) {
        assertSameValue(1, preg_match('/^t_aoou_[a-z]+_[0-9a-f]{12}$/D', $database), 'Database cleanup target is independently bounded.');
        $admin->query('CREATE DATABASE ' . $quote($database) . ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $created[] = $database;
    }
    $preflight = $connect($databases[0]);
    assertSameValue('1', (string) $preflight->query('SELECT 1 ready')->fetch_assoc()['ready'], 'Isolated MariaDB setup is live before intended RED.');
    $preflight->close();

    foreach (Contract::PROJECTIONS as $name => [$expectedHash, $literal]) {
        assertSameValue($expectedHash, hash('sha256', $literal), "{$name} is independently derived from its literal projection.");
    }
    if (!class_exists(AssignmentOrderOriginalSchemaMigration::class)) {
        throw new TestFailure('INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.');
    }
    if (!class_exists(AssignmentOrderOriginalVerificationDatabaseFixture::class)) {
        throw new TestFailure('INTENDED_RED: approved AssignmentOrderOriginalVerificationDatabaseFixture production seam is absent.');
    }

    $clean = $connect($databases[0]);
    $result = AssignmentOrderOriginalSchemaMigration::apply($clean, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::APPLIED, $result->status(), 'Clean schema applies.');
    assertSameValue(Contract::VERSION, $result->schemaVersion(), 'Migration reports exact version 1.');
    assertSameValue(Contract::TABLES, $result->affectedTables(), 'Clean migration reports exact manifest order.');
    $expectedOwnedTables = array_map(static fn ($name) => $prefix . $name, Contract::TABLES);
    sort($expectedOwnedTables, SORT_STRING);
    assertSameValue($expectedOwnedTables, $tableNames($clean, $prefix . '%'), 'Only exact owned manifest exists.');
    foreach (Contract::columns() as $table => $expected) {
        assertSameValue($expected, $columns($clean, $prefix . $table), "Exact ordered columns for {$table}.");
        assertSameValue(Contract::keys()[$table], $keys($clean, $prefix . $table), "Exact PK/unique/index column order for {$table}.");
        assertSameValue(Contract::foreignKeys()[$table], $foreignKeys($clean, $prefix . $table, $prefix), "Exact FK columns/actions for {$table}.");
        assertSameValue(Contract::checkCounts()[$table], $checkCount($clean, $prefix . $table), "Exact complete CHECK expression count for {$table}.");
        assertSameValue(['InnoDB','utf8mb4_unicode_ci'], $tableProperties($clean, $prefix . $table), "Exact engine and database-default collation for {$table}.");
    }
    $cleanBeforeRepeat = $snapshot($clean);
    $repeat = AssignmentOrderOriginalSchemaMigration::apply($clean, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::UNCHANGED, $repeat->status(), 'Exact repeat is unchanged.');
    assertSameValue([], $repeat->affectedTables(), 'Exact repeat has empty affected list.');
    assertSameValue($cleanBeforeRepeat, $snapshot($clean), 'Repeat preserves exact schema bytes.');
    $clean->close();

    $partial = $connect($databases[1]);
    $partial->query(Contract::rootsDdl($prefix));
    $partialBefore = $columns($partial, $prefix . Contract::TABLES[0]);
    $partialResult = AssignmentOrderOriginalSchemaMigration::apply($partial, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::APPLIED, $partialResult->status(), 'Leading compatible partial reconciles.');
    assertSameValue(array_slice(Contract::TABLES, 1), $partialResult->affectedTables(), 'Only missing trailing tables are affected.');
    assertSameValue($partialBefore, $columns($partial, $prefix . Contract::TABLES[0]), 'Existing leading table is preserved.');
    $partial->close();

    $populated = $connect($databases[2]);
    AssignmentOrderOriginalSchemaMigration::apply($populated, $prefix);
    $populated->query("INSERT INTO `{$prefix}fm2_assignment_order_original_roots` VALUES ('pop-root',77,88,'pop-revision','pop-composition','" . str_repeat('1',64) . "','2026-09-02 09:00:00.000000')");
    $populated->query("INSERT INTO `{$prefix}fm2_assignment_order_original_revisions` VALUES ('pop-revision','pop-root',1,NULL,'2026-09-01','2026-09-02 09:00:00.000000',18,'" . str_repeat('2',64) . "',327,'pop-content',NULL,'00000000-0000-4000-8000-000000000099','" . str_repeat('3',64) . "','assignment_order_original_accepted')");
    $rowsBefore = json_encode([$populated->query("SELECT * FROM `{$prefix}fm2_assignment_order_original_roots`")->fetch_all(MYSQLI_ASSOC),$populated->query("SELECT * FROM `{$prefix}fm2_assignment_order_original_revisions`")->fetch_all(MYSQLI_ASSOC)], JSON_THROW_ON_ERROR);
    $populatedResult = AssignmentOrderOriginalSchemaMigration::apply($populated, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::UNCHANGED, $populatedResult->status(), 'Populated exact schema is unchanged.');
    $rowsAfter = json_encode([$populated->query("SELECT * FROM `{$prefix}fm2_assignment_order_original_roots`")->fetch_all(MYSQLI_ASSOC),$populated->query("SELECT * FROM `{$prefix}fm2_assignment_order_original_revisions`")->fetch_all(MYSQLI_ASSOC)], JSON_THROW_ON_ERROR);
    assertSameValue($rowsBefore, $rowsAfter, 'Populated exact rows remain byte-identical.');
    $populated->close();

    $conflict = $connect($databases[3]);
    $conflict->query("CREATE TABLE `{$prefix}fm2_assignment_order_original_roots` (wrong INT NOT NULL) ENGINE=InnoDB");
    $conflictBefore = $snapshot($conflict);
    $conflictResult = AssignmentOrderOriginalSchemaMigration::apply($conflict, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::CONFLICT, $conflictResult->status(), 'Incompatible owned table conflicts.');
    assertSameValue([Contract::TABLES[0]], $conflictResult->affectedTables(), 'Conflict names only exact logical table.');
    assertSameValue($conflictBefore, $snapshot($conflict), 'Conflict performs zero DDL.');
    $conflict->close();

    $fixture = $connect($databases[4]);
    FMonitor2\InstallationProcess\ProductionProcessSchemaMigration::apply($fixture, $prefix);
    FMonitor2\InstallationProcess\IdentityAccessSchemaMigration::apply($fixture, $prefix);
    FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration::apply($fixture, $prefix);
    FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration::apply($fixture, $prefix);
    AssignmentOrderOriginalSchemaMigration::apply($fixture, $prefix);
    $fixtureBaseline = $stateSnapshot($fixture);
    AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($fixture, $prefix);
    assertSameValue(
        [
            ['user_id'=>'18','full_name'=>'Тестовый Оператор ФКР','email'=>'test-fkr@example.invalid','status'=>'1','activation_state'=>'active','session_version'=>'1','source_updated_at'=>'2026-09-02T09:00:00Z'],
            ['user_id'=>'31','full_name'=>'Тестовый Инженер','email'=>'test-engineer@example.invalid','status'=>'1','activation_state'=>'active','session_version'=>'1','source_updated_at'=>'2026-09-02T09:00:00Z'],
        ],
        $fixture->query("SELECT user_id,full_name,email,status,activation_state,session_version,source_updated_at FROM `{$prefix}fm2_pilot_users` WHERE user_id IN (18,31) ORDER BY user_id")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact fictional users without credentials.',
    );
    assertSameValue(
        [
            ['role_id'=>'5301','code'=>'fkr_operator','name'=>'Сотрудник ФКР','status'=>'1'],
            ['role_id'=>'5302','code'=>'control_engineer','name'=>'Инженер строительного контроля','status'=>'1'],
        ],
        $fixture->query("SELECT role_id,code,name,status FROM `{$prefix}fm2_pilot_roles` WHERE role_id IN (5301,5302) ORDER BY role_id")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact active role identities.',
    );
    assertSameValue(
        [
            ['user_id'=>'18','capability'=>'assignment_order.original.correct','position_snapshot'=>null],
            ['user_id'=>'18','capability'=>'assignment_order.original.upload','position_snapshot'=>null],
        ],
        $fixture->query("SELECT user_id,capability,position_snapshot FROM `{$prefix}fm2_process_user_capabilities` WHERE user_id=18 ORDER BY BINARY capability")->fetch_all(MYSQLI_ASSOC),
        'Actor has exactly upload and correct grants.',
    );
    assertSameValue(
        [['id'=>'4512','legacy_installation_object_id'=>'94512','process_state'=>'prepared','actual_start_date'=>null,'opened_at'=>null,'opened_by_user_id'=>null,'lock_version'=>'1']],
        $fixture->query("SELECT id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,lock_version FROM `{$prefix}fm2_installation_cases` WHERE id=4512")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact unopened prepared case.',
    );
    assertSameValue(
        [['id'=>'81','installation_case_id'=>'4512','version_no'=>'1','kind'=>'initial','status'=>'prepared','order_date'=>'2026-09-01','control_engineer_user_id'=>'31','organization_form'=>'brigade','previous_assignment_order_id'=>null]],
        $fixture->query("SELECT id,installation_case_id,version_no,kind,status,order_date,control_engineer_user_id,organization_form,previous_assignment_order_id FROM `{$prefix}fm2_assignment_orders` WHERE id=81")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact Example-A order.',
    );
    assertSameValue(
        [
            ['installer_tab_id'=>'7001','fio_snapshot'=>'Тестовый Монтажник 7001','position_snapshot'=>'Монтажник','employment_status_snapshot'=>'employed','employed_from_snapshot'=>'2026-01-01','employed_to_snapshot'=>null,'workforce_source_snapshot'=>'TEST-USER','workforce_source_updated_at_snapshot'=>'2026-09-01T00:00:00Z','valid_from'=>'2026-09-01','valid_to'=>null,'change_action'=>'assign'],
            ['installer_tab_id'=>'7002','fio_snapshot'=>'Тестовый Монтажник 7002','position_snapshot'=>'Монтажник','employment_status_snapshot'=>'employed','employed_from_snapshot'=>'2026-01-01','employed_to_snapshot'=>null,'workforce_source_snapshot'=>'TEST-USER','workforce_source_updated_at_snapshot'=>'2026-09-01T00:00:00Z','valid_from'=>'2026-09-01','valid_to'=>null,'change_action'=>'assign'],
        ],
        $fixture->query("SELECT installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot,employed_from_snapshot,employed_to_snapshot,workforce_source_snapshot,workforce_source_updated_at_snapshot,valid_from,valid_to,change_action FROM `{$prefix}fm2_order_installers` WHERE assignment_order_id=81 ORDER BY installer_tab_id")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact installer snapshots in binary identity order.',
    );
    assertSameValue(
        [['id'=>'9001','installation_case_id'=>'4512','task_type'=>'assignment_order_original_upload','assignee_role'=>'fkr_operator','status'=>'open']],
        $fixture->query("SELECT id,installation_case_id,task_type,assignee_role,status FROM `{$prefix}fm2_process_tasks` WHERE id=9001")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact open upload task.',
    );
    $seeded = $stateSnapshot($fixture);
    AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($fixture, $prefix);
    assertSameValue($seeded, $stateSnapshot($fixture), 'Exact fixture repeat is a no-op.');
    foreach (Contract::TABLES as $originalTable) {
        $count = (int) $fixture->query('SELECT COUNT(*) n FROM ' . $quote($prefix . $originalTable))->fetch_assoc()['n'];
        assertSameValue(0, $count, "Fixture creates no original fact in {$originalTable}.");
    }

    $fixture->query("UPDATE `{$prefix}fm2_installation_cases` SET process_state='foreign-drift' WHERE id=4512");
    $drift = $stateSnapshot($fixture);
    try {
        AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($fixture, $prefix);
        throw new TestFailure('Different occupied fixture identity must conflict.');
    } catch (AssignmentOrderOriginalVerificationFixtureConflict $error) {
        assertSameValue('AssignmentOrderOriginalVerificationFixtureConflict', $error->getMessage(), 'Conflict exception has fixed message.');
        assertSameValue(0, $error->getCode(), 'Conflict exception has fixed code.');
        assertSameValue(null, $error->getPrevious(), 'Conflict exception has no previous diagnostic.');
    }
    assertSameValue($drift, $stateSnapshot($fixture), 'Fixture conflict performs zero DML.');
    $fixture->query("UPDATE `{$prefix}fm2_installation_cases` SET process_state='prepared' WHERE id=4512");
    AssignmentOrderOriginalVerificationDatabaseFixture::cleanupExampleA($fixture, $prefix);
    AssignmentOrderOriginalVerificationDatabaseFixture::cleanupExampleA($fixture, $prefix);
    assertSameValue($fixtureBaseline, $stateSnapshot($fixture), 'Cleanup and exact repeat restore the full prerequisite database byte-for-byte.');
    foreach ([['fm2_pilot_users','user_id IN (18,31)'],['fm2_pilot_roles','role_id IN (5301,5302)'],['fm2_process_user_capabilities','user_id IN (18,31)'],['fm2_installation_cases','id IN (4512,9999)'],['fm2_assignment_orders','id=81'],['fm2_order_installers','assignment_order_id=81'],['fm2_process_tasks','id=9001']] as [$logicalTable,$where]) {
        $remaining = (int) $fixture->query("SELECT COUNT(*) n FROM `{$prefix}{$logicalTable}` WHERE {$where}")->fetch_assoc()['n'];
        assertSameValue(0, $remaining, "Bounded cleanup removes exact {$logicalTable} fixture identities.");
    }
    $fixture->close();

    fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK\n");
} finally {
    $failures = [];
    foreach (array_reverse($created) as $database) {
        try { $admin->query('DROP DATABASE ' . $quote($database)); } catch (Throwable $error) { $failures[] = $database; }
    }
    $admin->close();
    if ($failures !== []) throw new TestFailure('SETUP_CLEANUP_FAILURE: owned databases remain.');
}
