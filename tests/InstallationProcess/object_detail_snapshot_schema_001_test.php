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

function odsCreateExact(mysqli $connection, string $prefix, string $member): void
{
    $table = odsQuote($prefix . ($member === 'details'
        ? 'fm2_pilot_object_details'
        : 'fm2_pilot_object_detail_quarantine'));
    $tail = $member === 'details'
        ? '`schema_version` VARCHAR(80) NOT NULL,`content_sha256` CHAR(64) NOT NULL,`payload_json` LONGTEXT NOT NULL,`captured_at` VARCHAR(40) NOT NULL'
        : '`code` VARCHAR(80) NOT NULL,`schema_version` VARCHAR(80) NOT NULL,`content_sha256` CHAR(64) NOT NULL,`captured_at` VARCHAR(40) NOT NULL';
    $connection->query("CREATE TABLE {$table} (`object_id` BIGINT UNSIGNED NOT NULL,{$tail},PRIMARY KEY (`object_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/** @return array<string,mixed>|null */
function odsTableState(mysqli $connection, string $table): ?array
{
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
        'rows' => odsRows($connection, 'SELECT * FROM ' . odsQuote($table) . ' ORDER BY object_id'),
    ];
}

function odsAssertExact(mysqli $connection, string $prefix, string $member): void
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
    assertSameValue(['InnoDB', 'utf8mb4_unicode_ci'], array_values($state['table']), "{$name} exact engine and database-default collation");
    assertSameValue([], $state['constraints'], "{$name} has no FK/CHECK constraints");
    assertSameValue(
        [['INDEX_NAME'=>'PRIMARY','NON_UNIQUE'=>'0','SEQ_IN_INDEX'=>'1','COLUMN_NAME'=>'object_id','SUB_PART'=>null,'COLLATION'=>'A','INDEX_TYPE'=>'BTREE','IGNORED'=>'NO']],
        $state['indexes'],
        "{$name} has only the exact primary index",
    );
    foreach ($state['columns'] as $column) {
        assertSameValue('NO', $column['IS_NULLABLE'], "{$name}.{$column['COLUMN_NAME']} is NOT NULL");
        assertSameValue(null, $column['COLUMN_DEFAULT'], "{$name}.{$column['COLUMN_NAME']} has no default");
        assertSameValue('', $column['EXTRA'], "{$name}.{$column['COLUMN_NAME']} has no extra metadata");
        assertSameValue('', $column['GENERATION_EXPRESSION'], "{$name}.{$column['COLUMN_NAME']} is not generated");
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
    $command = array_merge(['env'], array_map(static fn (string $key, string $value): string => "{$key}={$value}", array_keys($environment), $environment), [PHP_BINARY, dirname(__DIR__, 2) . '/bin/fmonitor2-migrate.php']);
    $process = proc_open($command, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, dirname(__DIR__, 2));
    if (!is_resource($process)) {
        throw new TestFailure('SETUP_FAILURE: canonical runner did not start');
    }
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    return ['exit'=>$exit,'out'=>$out,'err'=>$err];
}

$admin = odsConnect();
$database = 'fm2_ods_red_' . bin2hex(random_bytes(6));
$prefixes = ['clean_', 'detail_', 'quar_', 'conflict_', 'decoy_', str_repeat('p', 25)];

try {
    $admin->query('CREATE DATABASE ' . odsQuote($database) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $connection = odsConnect($database);
    try {
        // A real DB prerequisite proves this is not a class-loading/setup RED.
        $connection->query('CREATE TABLE `fixture_probe` (`id` INT NOT NULL PRIMARY KEY) ENGINE=InnoDB');
        $connection->query('INSERT INTO `fixture_probe` VALUES (1)');
        assertSameValue([['id'=>'1']], odsRows($connection, 'SELECT id FROM fixture_probe'), 'real MariaDB fixture prerequisite');
        echo "PREREQUISITE PASS: isolated MariaDB fixture is writable and observable\n";

        assertSameValue(
            true,
            class_exists(ObjectDetailSnapshotSchemaMigration::class),
            'OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 requires the missing public v12 migration seam.',
        );

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
        assertSameValue(['applied'=>true,'schemaVersion'=>12,'tablesCreated'=>['detail_fm2_pilot_object_detail_quarantine']], ObjectDetailSnapshotSchemaMigration::apply($connection, 'detail_'), 'details-only recovery');
        assertSameValue($detailBefore, odsTableState($connection, 'detail_fm2_pilot_object_details'), 'details partial row and schema preserved');
        odsAssertExact($connection, 'detail_', 'quarantine');

        odsCreateExact($connection, 'quar_', 'quarantine');
        $connection->query("INSERT INTO quar_fm2_pilot_object_detail_quarantine VALUES(7001,'FIXTURE_ABSENT','fixture-v1',REPEAT('b',64),'2026-09-05T09:00:00Z')");
        $quarantineBefore = odsTableState($connection, 'quar_fm2_pilot_object_detail_quarantine');
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
        $decoyBefore = [odsTableState($connection, 'fm2_pilot_object_details'), odsTableState($connection, 'other_fm2_pilot_object_detail_quarantine')];
        ObjectDetailSnapshotSchemaMigration::apply($connection, 'decoy_');
        assertSameValue($decoyBefore, [odsTableState($connection, 'fm2_pilot_object_details'), odsTableState($connection, 'other_fm2_pilot_object_detail_quarantine')], 'unprefixed and other-prefix decoys are isolated');

        assertSameValue(['applied'=>true,'schemaVersion'=>12,'tablesCreated'=>[str_repeat('p',25).'fm2_pilot_object_detail_quarantine',str_repeat('p',25).'fm2_pilot_object_details']], ObjectDetailSnapshotSchemaMigration::apply($connection, str_repeat('p',25)), '25-byte composed prefix succeeds');
        foreach ([str_repeat('p',26), 'bad-prefix', "é"] as $invalid) {
            try {
                ObjectDetailSnapshotSchemaMigration::apply($connection, $invalid);
                throw new TestFailure('invalid prefix was accepted');
            } catch (InvalidArgumentException $error) {
                assertSameValue('Invalid table prefix.', $error->getMessage(), 'invalid direct prefix has stable diagnostic');
            }
            assertSameValue(false, ObjectDetailSnapshotSchemaMigration::isCompleteCompatible($connection, $invalid), 'invalid compatibility query is false');
        }

        assertSameValue(['exit'=>64,'out'=>"{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n",'err'=>''], odsRunCli('must_not_be_accessed', str_repeat('p',26), true), '26-byte runner prefix is rejected before DB access');
        assertSameValue(['exit'=>64,'out'=>"{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n",'err'=>''], odsRunCli('must_not_be_accessed', 'bad-prefix', true), 'invalid runner prefix is rejected before DB access');

        $runnerDatabase = 'fm2_ods_runner_' . bin2hex(random_bytes(5));
        $admin->query('CREATE DATABASE ' . odsQuote($runnerDatabase) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        try {
            assertSameValue(['exit'=>0,'out'=>"{\"ok\":true,\"schemaVersion\":12,\"appliedVersions\":[1,2,3,4,5,6,7,8,9,10,11,12]}\n",'err'=>''], odsRunCli($runnerDatabase, ''), 'production runner registers and applies sequential v12');
            assertSameValue(['exit'=>0,'out'=>"{\"ok\":true,\"schemaVersion\":12,\"appliedVersions\":[]}\n",'err'=>''], odsRunCli($runnerDatabase, ''), 'production runner exact v12 repeat');
        } finally {
            $admin->query('DROP DATABASE IF EXISTS ' . odsQuote($runnerDatabase));
        }

        echo "PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4 canonical migration contract\n";
    } finally {
        $connection->close();
    }
} finally {
    $admin->query('DROP DATABASE IF EXISTS ' . odsQuote($database));
    $admin->close();
}
