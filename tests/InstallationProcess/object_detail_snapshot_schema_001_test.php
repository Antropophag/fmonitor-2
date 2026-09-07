<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaMigration;

/**
 * OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4, Gate 2.
 *
 * Public seams: ObjectDetailSnapshotSchemaMigration::apply(),
 * ObjectDetailSnapshotSchemaMigration::isCompleteCompatible(), and the
 * production migration runner. Fixtures contain fictional preservation bytes
 * only and never invoke the importer or an external source.
 */

function odsQuote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function odsConnect(?string $database = null): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connection = new mysqli(
        getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
        $database,
        (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306),
    );
    $connection->set_charset('utf8mb4');
    return $connection;
}

/** @return list<array<string,mixed>> */
function odsRows(mysqli $connection, string $sql): array
{
    return $connection->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function odsCreateExact(mysqli $connection, string $prefix, string $member, string $collation = 'utf8mb4_unicode_ci'): void
{
    $table = odsQuote($prefix . ($member === 'details'
        ? 'fm2_pilot_object_details'
        : 'fm2_pilot_object_detail_quarantine'));
    $tail = $member === 'details'
        ? '`schema_version` VARCHAR(80) NOT NULL,`content_sha256` CHAR(64) NOT NULL,`payload_json` LONGTEXT NOT NULL,`captured_at` VARCHAR(40) NOT NULL'
        : '`code` VARCHAR(80) NOT NULL,`schema_version` VARCHAR(80) NOT NULL,`content_sha256` CHAR(64) NOT NULL,`captured_at` VARCHAR(40) NOT NULL';
    if (!in_array($collation, ['utf8mb4_unicode_ci', 'utf8mb4_general_ci'], true)) throw new TestFailure('Unknown fixture collation');
    $connection->query("CREATE TABLE {$table} (`object_id` BIGINT UNSIGNED NOT NULL,{$tail},PRIMARY KEY (`object_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE={$collation}");
}

/** @return array<string,mixed>|null */
function odsTableState(mysqli $connection, string $table, string $rowKey = 'object_id'): ?array
{
    if (!in_array($rowKey, ['object_id', 'id'], true)) throw new TestFailure('Unknown fixture row key');
    $escaped = $connection->real_escape_string($table);
    $tableMetadata = odsRows($connection, "SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}'");
    if ($tableMetadata === []) {
        return null;
    }
    return [
        'table' => $tableMetadata[0],
        'columns' => odsRows($connection, "SELECT COLUMN_NAME,LOWER(COLUMN_TYPE) COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,GENERATION_EXPRESSION,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' ORDER BY ORDINAL_POSITION"),
        'indexes' => odsRows($connection, "SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART,COLLATION,INDEX_TYPE,IGNORED FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' ORDER BY BINARY INDEX_NAME,SEQ_IN_INDEX"),
        'constraints' => odsRows($connection, "SELECT CONSTRAINT_TYPE FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' AND CONSTRAINT_TYPE IN ('FOREIGN KEY','CHECK') ORDER BY CONSTRAINT_TYPE"),
        'rows' => odsRows($connection, 'SELECT * FROM ' . odsQuote($table) . ' ORDER BY ' . odsQuote($rowKey)),
    ];
}

function odsAssertExact(mysqli $connection, string $prefix, string $member, string $collation = 'utf8mb4_unicode_ci'): void
{
    $name = $prefix . ($member === 'details' ? 'fm2_pilot_object_details' : 'fm2_pilot_object_detail_quarantine');
    $state = odsTableState($connection, $name);
    if ($state === null) {
        throw new TestFailure("{$name} must exist");
    }
    $names = $member === 'details'
        ? ['object_id', 'schema_version', 'content_sha256', 'payload_json', 'captured_at']
        : ['object_id', 'code', 'schema_version', 'content_sha256', 'captured_at'];
    assertSameValue($names, array_column($state['columns'], 'COLUMN_NAME'), "{$name} exact ordered columns");
    assertSameValue(['InnoDB', $collation], array_values($state['table']), "{$name} exact engine and database-default collation");
    assertSameValue([], $state['constraints'], "{$name} has no FK/CHECK constraints");
    assertSameValue(
        [['INDEX_NAME'=>'PRIMARY','NON_UNIQUE'=>'0','SEQ_IN_INDEX'=>'1','COLUMN_NAME'=>'object_id','SUB_PART'=>null,'COLLATION'=>'A','INDEX_TYPE'=>'BTREE','IGNORED'=>'NO']],
        $state['indexes'],
        "{$name} has only the exact primary index",
    );
    $types = ['object_id'=>'bigint(20) unsigned', 'schema_version'=>'varchar(80)', 'code'=>'varchar(80)', 'content_sha256'=>'char(64)', 'payload_json'=>'longtext', 'captured_at'=>'varchar(40)'];
    foreach ($state['columns'] as $column) {
        $type = $column['COLUMN_TYPE'] === 'bigint unsigned' ? 'bigint(20) unsigned' : $column['COLUMN_TYPE'];
        assertSameValue($types[$column['COLUMN_NAME']], $type, "{$name}.{$column['COLUMN_NAME']} exact type/unsigned/length");
        $character = $column['COLUMN_NAME'] !== 'object_id';
        assertSameValue($character ? 'utf8mb4' : null, $column['CHARACTER_SET_NAME'], "{$name}.{$column['COLUMN_NAME']} charset");
        assertSameValue($character ? $collation : null, $column['COLLATION_NAME'], "{$name}.{$column['COLUMN_NAME']} collation");
        assertSameValue('NO', $column['IS_NULLABLE'], "{$name}.{$column['COLUMN_NAME']} is NOT NULL");
        assertSameValue(null, $column['COLUMN_DEFAULT'], "{$name}.{$column['COLUMN_NAME']} has no default");
        assertSameValue('', $column['EXTRA'], "{$name}.{$column['COLUMN_NAME']} has no extra metadata");
        assertSameValue('', $column['GENERATION_EXPRESSION'] ?? '', "{$name}.{$column['COLUMN_NAME']} is not generated");
    }
}

/** @return array{exit:int,out:string,err:string} */
function odsRunCli(string $database, string $prefix, bool $unreachable = false): array
{
    $environment = [
        'FMONITOR_DB_HOST' => $unreachable ? '127.0.0.1' : (getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1'),
        'FMONITOR_DB_PORT' => $unreachable ? '1' : (getenv('FMONITOR_TEST_DB_PORT') ?: '23306'),
        'FMONITOR_DB_NAME' => $database,
        'FMONITOR_DB_USER' => getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        'FMONITOR_DB_PASSWORD' => $unreachable ? 'must-not-be-read' : (getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local'),
        'FMONITOR_PROCESS_TABLE_PREFIX' => $prefix,
    ];
    // Some PHP hosts omit empty values from proc_open's environment. Preserve
    // only explicit empty assignments through env; nonempty secrets stay in
    // the environment and never enter the command arguments.
    $command = ['/usr/bin/env'];
    foreach ($environment as $name => $value) if ($value === '') $command[] = $name . '=';
    $command[] = PHP_BINARY;
    $command[] = dirname(__DIR__, 2) . '/bin/fmonitor2-migrate.php';
    $process = proc_open($command, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, dirname(__DIR__, 2), $environment);
    if (!is_resource($process)) {
        throw new TestFailure('SETUP_FAILURE: canonical runner did not start');
    }
    $output = [1=>'', 2=>''];
    $exit = null;
    try {
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $deadline = hrtime(true) + 30_000_000_000;
        do {
            foreach ([1,2] as $fd) {
                $chunk = fread($pipes[$fd], 8192);
                if ($chunk === false) throw new TestFailure('SETUP_FAILURE: runner pipe read failed');
                $output[$fd] .= $chunk;
                if (strlen($output[$fd]) > 65536) throw new TestFailure('SETUP_FAILURE: runner output exceeded bound');
            }
            $status = proc_get_status($process);
            if (!$status['running'] && $exit === null) $exit = $status['exitcode'];
            if (!$status['running'] && feof($pipes[1]) && feof($pipes[2])) break;
            if (hrtime(true) >= $deadline) throw new TestFailure('SETUP_FAILURE: runner deadline exceeded');
            usleep(1000);
        } while (true);
    } finally {
        $status = proc_get_status($process);
        if ($status['running']) {
            proc_terminate($process, 15);
            $stopDeadline = hrtime(true) + 1_000_000_000;
            do {
                usleep(1000);
                $status = proc_get_status($process);
            } while ($status['running'] && hrtime(true) < $stopDeadline);
            if ($status['running']) proc_terminate($process, 9);
        }
        foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
        $reaped = proc_close($process);
        if ($exit === null || $exit < 0) $exit = $reaped;
    }
    if ($exit < 0) throw new TestFailure('SETUP_FAILURE: runner exit status unavailable');
    $out = $output[1];
    $err = $output[2];
    return ['exit'=>$exit,'out'=>$out,'err'=>$err];
}

$admin = odsConnect();
$database = 'fm2_ods_red_' . bin2hex(random_bytes(6));
$databaseCreated = false;

try {
    $admin->query('CREATE DATABASE ' . odsQuote($database) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $databaseCreated = true;
    $connection = odsConnect($database);
    try {
        // A real DB prerequisite proves this is not a class-loading/setup RED.
        $connection->query('CREATE TABLE `fixture_probe` (`id` INT NOT NULL PRIMARY KEY) ENGINE=InnoDB');
        $connection->query('INSERT INTO `fixture_probe` VALUES (1)');
        assertSameValue([['id'=>'1']], odsRows($connection, 'SELECT id FROM fixture_probe'), 'real MariaDB fixture prerequisite');
        // Calibrate every snapshot assertion against literal fixture DDL before
        // the missing-production-seam assertion can hide a harness failure.
        foreach (['details','quarantine'] as $member) {
            odsCreateExact($connection, 'calibrate_', $member);
            odsAssertExact($connection, 'calibrate_', $member);
            odsCreateExact($connection, 'calibrate_general_', $member, 'utf8mb4_general_ci');
            odsAssertExact($connection, 'calibrate_general_', $member, 'utf8mb4_general_ci');
        }
        $connection->query('CREATE TABLE calibration_decoy(id INT PRIMARY KEY, payload VARBINARY(20)) ENGINE=InnoDB');
        $connection->query("INSERT INTO calibration_decoy VALUES(1,X'006465636F79')");
        assertSameValue([['id'=>'1','payload'=>"\0decoy"]], odsTableState($connection, 'calibration_decoy', 'id')['rows'], 'decoy row-key snapshot works before production');
        $drifts = [
            'type'=>'MODIFY content_sha256 VARCHAR(64) NOT NULL',
            'unsigned'=>'MODIFY object_id BIGINT NOT NULL',
            'column_collation'=>'MODIFY schema_version VARCHAR(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL',
            'default'=>"ALTER COLUMN schema_version SET DEFAULT 'fixture-v1'",
            'index'=>'ADD INDEX extra_index(schema_version)',
            'nullable'=>'MODIFY captured_at VARCHAR(40) NULL',
            'engine'=>'ENGINE=MyISAM',
        ];
        foreach ($drifts as $kind => $alter) {
            $driftPrefix = 'drift_' . $kind . '_';
            odsCreateExact($connection, $driftPrefix, 'details');
            $connection->query('ALTER TABLE ' . odsQuote($driftPrefix . 'fm2_pilot_object_details') . ' ' . $alter);
            $detected = false;
            try { odsAssertExact($connection, $driftPrefix, 'details'); }
            catch (TestFailure) { $detected = true; }
            assertSameValue(true, $detected, 'independent oracle detects literal ' . $kind . ' drift before production');
        }
        odsCreateExact($connection, 'second_conflict_', 'quarantine');
        $connection->query('ALTER TABLE second_conflict_fm2_pilot_object_detail_quarantine MODIFY code VARCHAR(79) NOT NULL');
        assertSameValue(['exit'=>64,'out'=>"{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n",'err'=>''], odsRunCli('must_not_be_accessed', str_repeat('p',26), true), 'bounded CLI harness precondition');
        assertSameValue(['exit'=>69,'out'=>"{\"ok\":false,\"reason\":\"DATABASE_UNAVAILABLE\"}\n",'err'=>''], odsRunCli('must_not_be_accessed', '', true), 'explicit empty prefix survives child environment and reaches valid-config DB boundary');
        echo "PREREQUISITE PASS: isolated MariaDB fixture is writable and observable\n";

        assertSameValue(
            true,
            class_exists(ObjectDetailSnapshotSchemaMigration::class),
            'OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 requires the missing public v12 migration seam.',
        );

        assertSameValue(false, ObjectDetailSnapshotSchemaMigration::isCompleteCompatible($connection, 'clean_'), 'absent family is not compatible');
        foreach ($drifts as $kind => $_alter) {
            $driftPrefix = 'drift_' . $kind . '_';
            $table = $driftPrefix . 'fm2_pilot_object_details';
            $before = odsTableState($connection, $table);
            assertSameValue(['applied'=>false,'schemaVersion'=>12,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$table]], ObjectDetailSnapshotSchemaMigration::apply($connection, $driftPrefix), 'reject exact ' . $kind . ' metadata drift');
            assertSameValue($before, odsTableState($connection, $table), 'preserve ' . $kind . ' conflict');
            assertSameValue(null, odsTableState($connection, $driftPrefix . 'fm2_pilot_object_detail_quarantine'), 'no sibling creation for ' . $kind);
        }
        $secondConflictBefore = odsTableState($connection, 'second_conflict_fm2_pilot_object_detail_quarantine');
        assertSameValue(['applied'=>false,'schemaVersion'=>12,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>['second_conflict_fm2_pilot_object_detail_quarantine']], ObjectDetailSnapshotSchemaMigration::apply($connection, 'second_conflict_'), 'later-member conflict preflight before first member creation');
        assertSameValue(null, odsTableState($connection, 'second_conflict_fm2_pilot_object_details'), 'earlier missing member remains absent');
        assertSameValue($secondConflictBefore, odsTableState($connection, 'second_conflict_fm2_pilot_object_detail_quarantine'), 'later conflicting member preserved');

        $clean = ObjectDetailSnapshotSchemaMigration::apply($connection, 'clean_');
        assertSameValue(['applied'=>true,'schemaVersion'=>12,'tablesCreated'=>['clean_fm2_pilot_object_detail_quarantine','clean_fm2_pilot_object_details']], $clean, 'clean exact result');
        odsAssertExact($connection, 'clean_', 'details');
        odsAssertExact($connection, 'clean_', 'quarantine');
        assertSameValue(true, ObjectDetailSnapshotSchemaMigration::isCompleteCompatible($connection, 'clean_'), 'complete family is compatible');
        assertSameValue([], odsTableState($connection, 'clean_fm2_pilot_object_details')['rows'], 'clean details is data-free');
        assertSameValue([], odsTableState($connection, 'clean_fm2_pilot_object_detail_quarantine')['rows'], 'clean quarantine is data-free');

        $connection->query("INSERT INTO clean_fm2_pilot_object_details VALUES(7001,'fixture-v1',REPEAT('a',64),'{\"fixture\":true}','2026-09-05T09:00:00Z')");
        $connection->query("INSERT INTO clean_fm2_pilot_object_detail_quarantine VALUES(7001,'FIXTURE_ABSENT','fixture-v1',REPEAT('b',64),'2026-09-05T09:00:00Z')");
        $beforeRepeat = [odsTableState($connection, 'clean_fm2_pilot_object_details'), odsTableState($connection, 'clean_fm2_pilot_object_detail_quarantine')];
        assertSameValue(['applied'=>false,'schemaVersion'=>12,'tablesCreated'=>[]], ObjectDetailSnapshotSchemaMigration::apply($connection, 'clean_'), 'populated exact repeat result');
        assertSameValue($beforeRepeat, [odsTableState($connection, 'clean_fm2_pilot_object_details'), odsTableState($connection, 'clean_fm2_pilot_object_detail_quarantine')], 'repeat preserves both opaque rows and metadata byte-for-byte');

        odsCreateExact($connection, 'detail_', 'details');
        $connection->query("INSERT INTO detail_fm2_pilot_object_details VALUES(7001,'fixture-v1',REPEAT('a',64),'{\"fixture\":true}','2026-09-05T09:00:00Z')");
        $detailBefore = odsTableState($connection, 'detail_fm2_pilot_object_details');
        assertSameValue(false, ObjectDetailSnapshotSchemaMigration::isCompleteCompatible($connection, 'detail_'), 'details-only family is not compatible');
        assertSameValue(['applied'=>true,'schemaVersion'=>12,'tablesCreated'=>['detail_fm2_pilot_object_detail_quarantine']], ObjectDetailSnapshotSchemaMigration::apply($connection, 'detail_'), 'details-only recovery');
        assertSameValue($detailBefore, odsTableState($connection, 'detail_fm2_pilot_object_details'), 'details partial row and schema preserved');
        odsAssertExact($connection, 'detail_', 'quarantine');

        odsCreateExact($connection, 'quar_', 'quarantine');
        $connection->query("INSERT INTO quar_fm2_pilot_object_detail_quarantine VALUES(7001,'FIXTURE_ABSENT','fixture-v1',REPEAT('b',64),'2026-09-05T09:00:00Z')");
        $quarantineBefore = odsTableState($connection, 'quar_fm2_pilot_object_detail_quarantine');
        assertSameValue(false, ObjectDetailSnapshotSchemaMigration::isCompleteCompatible($connection, 'quar_'), 'quarantine-only family is not compatible');
        assertSameValue(['applied'=>true,'schemaVersion'=>12,'tablesCreated'=>['quar_fm2_pilot_object_details']], ObjectDetailSnapshotSchemaMigration::apply($connection, 'quar_'), 'quarantine-only recovery');
        assertSameValue($quarantineBefore, odsTableState($connection, 'quar_fm2_pilot_object_detail_quarantine'), 'quarantine partial row and schema preserved');
        odsAssertExact($connection, 'quar_', 'details');

        $connection->query('CREATE TABLE conflict_fm2_pilot_object_details(object_id BIGINT UNSIGNED NOT NULL PRIMARY KEY, incompatible INT NOT NULL) ENGINE=InnoDB');
        $connection->query('INSERT INTO conflict_fm2_pilot_object_details VALUES(7001,91)');
        $conflictBefore = odsTableState($connection, 'conflict_fm2_pilot_object_details');
        assertSameValue(['applied'=>false,'schemaVersion'=>12,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>['conflict_fm2_pilot_object_details']], ObjectDetailSnapshotSchemaMigration::apply($connection, 'conflict_'), 'incompatible member exact public conflict');
        assertSameValue($conflictBefore, odsTableState($connection, 'conflict_fm2_pilot_object_details'), 'conflicting table is unchanged');
        assertSameValue(null, odsTableState($connection, 'conflict_fm2_pilot_object_detail_quarantine'), 'absent sibling is not created after family conflict');
        assertSameValue(false, ObjectDetailSnapshotSchemaMigration::isCompleteCompatible($connection, 'conflict_'), 'conflict is not complete-compatible');

        foreach (['fm2_pilot_object_details', 'other_fm2_pilot_object_detail_quarantine'] as $decoy) {
            $connection->query('CREATE TABLE ' . odsQuote($decoy) . '(id INT PRIMARY KEY, payload VARBINARY(20) NOT NULL) ENGINE=InnoDB');
            $connection->query('INSERT INTO ' . odsQuote($decoy) . " VALUES(1,X'006465636F79')");
        }
        $decoyBefore = [odsTableState($connection, 'fm2_pilot_object_details', 'id'), odsTableState($connection, 'other_fm2_pilot_object_detail_quarantine', 'id')];
        ObjectDetailSnapshotSchemaMigration::apply($connection, 'decoy_');
        assertSameValue($decoyBefore, [odsTableState($connection, 'fm2_pilot_object_details', 'id'), odsTableState($connection, 'other_fm2_pilot_object_detail_quarantine', 'id')], 'unprefixed and other-prefix decoys are isolated');

        assertSameValue(['applied'=>true,'schemaVersion'=>12,'tablesCreated'=>[str_repeat('p',25).'fm2_pilot_object_detail_quarantine',str_repeat('p',25).'fm2_pilot_object_details']], ObjectDetailSnapshotSchemaMigration::apply($connection, str_repeat('p',25)), '25-byte composed prefix succeeds');
        odsAssertExact($connection, str_repeat('p',25), 'details');
        odsAssertExact($connection, str_repeat('p',25), 'quarantine');
        assertSameValue(true, ObjectDetailSnapshotSchemaMigration::isCompleteCompatible($connection, str_repeat('p',25)), '25-byte family is complete-compatible');
        assertSameValue([], odsTableState($connection, str_repeat('p',25).'fm2_pilot_object_details')['rows'], '25-byte details is empty');
        assertSameValue([], odsTableState($connection, str_repeat('p',25).'fm2_pilot_object_detail_quarantine')['rows'], '25-byte quarantine is empty');
        foreach ([str_repeat('p',26), 'bad-prefix', "é"] as $invalid) {
            $tablesBeforeInvalid = odsRows($connection, 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY BINARY TABLE_NAME');
            try {
                ObjectDetailSnapshotSchemaMigration::apply($connection, $invalid);
                throw new TestFailure('invalid prefix was accepted');
            } catch (InvalidArgumentException $error) {
                assertSameValue('Invalid table prefix.', $error->getMessage(), 'invalid direct prefix has stable diagnostic');
            }
            assertSameValue(false, ObjectDetailSnapshotSchemaMigration::isCompleteCompatible($connection, $invalid), 'invalid compatibility query is false');
            assertSameValue($tablesBeforeInvalid, odsRows($connection, 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY BINARY TABLE_NAME'), 'invalid prefix creates neither family member or any other table');
            $sentry = new class extends mysqli {
                public int $calls = 0;
                public function __construct() {}
                public function query(string $query, int $result_mode = MYSQLI_STORE_RESULT): mysqli_result|bool { ++$this->calls; return false; }
                public function prepare(string $query): mysqli_stmt|false { ++$this->calls; return false; }
                public function real_escape_string(string $string): string { ++$this->calls; return $string; }
            };
            try {
                ObjectDetailSnapshotSchemaMigration::apply($sentry, $invalid);
                throw new TestFailure('invalid prefix was accepted by DB sentry');
            } catch (InvalidArgumentException $error) {
                assertSameValue('Invalid table prefix.', $error->getMessage(), 'invalid prefix is validated before DB operations');
            }
            assertSameValue(false, ObjectDetailSnapshotSchemaMigration::isCompleteCompatible($sentry, $invalid), 'invalid compatibility short-circuits');
            assertSameValue(0, $sentry->calls, 'invalid prefix performs no query, prepare, or DB escaping');
        }

        assertSameValue(['exit'=>64,'out'=>"{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n",'err'=>''], odsRunCli('must_not_be_accessed', str_repeat('p',26), true), '26-byte runner prefix is rejected before DB access');
        assertSameValue(['exit'=>64,'out'=>"{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n",'err'=>''], odsRunCli('must_not_be_accessed', 'bad-prefix', true), 'invalid runner prefix is rejected before DB access');

        $alternateDatabase = 'fm2_ods_collation_' . bin2hex(random_bytes(5));
        $admin->query('CREATE DATABASE ' . odsQuote($alternateDatabase) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        try {
            $alternate = odsConnect($alternateDatabase);
            try {
                assertSameValue(['applied'=>true,'schemaVersion'=>12,'tablesCreated'=>['fm2_pilot_object_detail_quarantine','fm2_pilot_object_details']], ObjectDetailSnapshotSchemaMigration::apply($alternate), 'alternate database-default result');
                odsAssertExact($alternate, '', 'details', 'utf8mb4_general_ci');
                odsAssertExact($alternate, '', 'quarantine', 'utf8mb4_general_ci');
            } finally { $alternate->close(); }
        } finally { $admin->query('DROP DATABASE ' . odsQuote($alternateDatabase)); }

        $runnerDatabase = 'fm2_ods_runner_' . bin2hex(random_bytes(5));
        $admin->query('CREATE DATABASE ' . odsQuote($runnerDatabase) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        try {
            assertSameValue(['exit'=>0,'out'=>"{\"ok\":true,\"schemaVersion\":15,\"appliedVersions\":[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15]}\n",'err'=>''], odsRunCli($runnerDatabase, ''), 'production runner registers and applies through sequential v15');
            $runnerConnection = odsConnect($runnerDatabase);
            try {
                odsAssertExact($runnerConnection, '', 'details');
                odsAssertExact($runnerConnection, '', 'quarantine');
                assertSameValue([], odsTableState($runnerConnection, 'fm2_pilot_object_details')['rows'], 'runner creates empty details');
                assertSameValue([], odsTableState($runnerConnection, 'fm2_pilot_object_detail_quarantine')['rows'], 'runner creates empty quarantine');
            } finally { $runnerConnection->close(); }
            assertSameValue(['exit'=>0,'out'=>"{\"ok\":true,\"schemaVersion\":15,\"appliedVersions\":[]}\n",'err'=>''], odsRunCli($runnerDatabase, ''), 'production runner exact v15 repeat');
        } finally {
            $admin->query('DROP DATABASE IF EXISTS ' . odsQuote($runnerDatabase));
        }

        echo "PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4 canonical migration contract\n";
    } finally {
        $connection->close();
    }
} finally {
    try { if ($databaseCreated) $admin->query('DROP DATABASE ' . odsQuote($database)); }
    finally { $admin->close(); }
}
