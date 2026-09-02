<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

// CHECKLIST-TEMPLATE-SCHEMA-001 sections 4.1, 6 and 7.1.
// Public seam: php bin/fmonitor2-migrate.php.

/** @return array{exitCode:int,stdout:string,stderr:string} */
function ctsRunRunner(string $database, string $prefix = ''): array
{
    $environment = [
        'FMONITOR_DB_HOST' => getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        'FMONITOR_DB_PORT' => getenv('FMONITOR_TEST_DB_PORT') ?: '23306',
        'FMONITOR_DB_NAME' => $database,
        'FMONITOR_DB_USER' => getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        'FMONITOR_DB_PASSWORD' => getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
        'FMONITOR_PROCESS_TABLE_PREFIX' => $prefix,
    ];
    $command = array_merge(['env'], array_map(
        static fn (string $name, string $value): string => $name . '=' . $value,
        array_keys($environment),
        array_values($environment),
    ), ['php', getenv('FMONITOR_CTS_RUNNER') ?: dirname(__DIR__, 2) . '/bin/fmonitor2-migrate.php']);

    $pipes = [];
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, dirname(__DIR__, 2));
    if (!is_resource($process)) {
        throw new TestFailure('Canonical migration runner must start.');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return ['exitCode' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

/** @return list<string> */
function ctsTables(mysqli $connection): array
{
    $result = $connection->query(
        "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('fm2_checklist_template_snapshots','fm2_checklist_template_associations') ORDER BY BINARY TABLE_NAME",
    );
    $tables = [];
    while ($row = $result->fetch_assoc()) {
        $tables[] = (string) $row['TABLE_NAME'];
    }
    $result->free();
    return $tables;
}

function ctsQuote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

/** Test-owned literal transcription of specification section 3. */
function ctsCreateSnapshots(mysqli $connection, string $prefix): void
{
    $connection->query('CREATE TABLE ' . ctsQuote($prefix . 'fm2_checklist_template_snapshots') . "(
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        snapshot_version VARCHAR(80) NOT NULL,
        captured_at DATETIME NOT NULL,
        valid_from DATETIME NOT NULL,
        validity_scope VARCHAR(120) NOT NULL,
        source_label VARCHAR(160) NOT NULL,
        content_sha256 CHAR(64) NOT NULL,
        payload_json LONGTEXT NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY(id), UNIQUE KEY uq_hash(content_sha256), UNIQUE KEY uq_valid_from(valid_from)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function ctsCreateAssociations(mysqli $connection, string $prefix): void
{
    $connection->query('CREATE TABLE ' . ctsQuote($prefix . 'fm2_checklist_template_associations') . "(
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        association_version VARCHAR(80) NOT NULL,
        subject_kind VARCHAR(40) NOT NULL,
        subject_id VARCHAR(160) NOT NULL,
        effective_at DATETIME NOT NULL,
        template_snapshot_id BIGINT UNSIGNED NOT NULL,
        template_snapshot_version VARCHAR(80) NOT NULL,
        template_content_sha256 CHAR(64) NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY(id), UNIQUE KEY uq_subject(subject_kind,subject_id), KEY snapshot_id(template_snapshot_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/** @return array<string,mixed> */
function ctsApply(mysqli $connection, string $prefix): array
{
    $class = 'FMonitor2\\InstallationProcess\\ChecklistTemplateSchemaMigration';
    if (!class_exists($class)) {
        throw new TestFailure('Approved public ChecklistTemplateSchemaMigration seam is missing.');
    }
    return $class::apply($connection, $prefix);
}

/** @return array<string,mixed> */
function ctsState(mysqli $connection, string $prefix): array
{
    $state = [];
    foreach (['fm2_checklist_template_snapshots', 'fm2_checklist_template_associations'] as $base) {
        $table = $prefix . $base;
        if (!in_array($table, ctsTablesForPrefix($connection, $prefix), true)) {
            $state[$table] = null;
            continue;
        }
        $quoted = ctsQuote($table);
        $create = $connection->query("SHOW CREATE TABLE {$quoted}")->fetch_assoc();
        $rows = $connection->query("SELECT * FROM {$quoted} ORDER BY id")->fetch_all(MYSQLI_ASSOC);
        $status = $connection->query("SHOW TABLE STATUS LIKE '" . $connection->real_escape_string($table) . "'")->fetch_assoc();
        $state[$table] = ['create' => $create['Create Table'], 'rows' => $rows, 'autoIncrement' => $status['Auto_increment']];
    }
    return $state;
}

/** @return array<string,mixed> */
function ctsSchemaState(mysqli $connection, string $prefix): array
{
    $state = ctsState($connection, $prefix);
    foreach ($state as &$table) {
        if (is_array($table)) {
            unset($table['rows'], $table['autoIncrement']);
            $table['create'] = preg_replace('/ AUTO_INCREMENT=\d+/', '', (string) $table['create']);
        }
    }
    return $state;
}

/** Runtime contract fingerprints schema and persisted rows, not allocator state. */
function ctsRuntimeState(mysqli $connection, string $prefix): array
{
    $state = ctsState($connection, $prefix);
    foreach ($state as &$table) {
        if (is_array($table)) {
            unset($table['autoIncrement']);
            $table['create'] = preg_replace('/ AUTO_INCREMENT=\d+/', '', (string) $table['create']);
        }
    }
    return $state;
}

/** @return list<string> */
function ctsTablesForPrefix(mysqli $connection, string $prefix): array
{
    $escaped = $connection->real_escape_string($prefix . 'fm2_checklist_template_%');
    return array_column($connection->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$escaped}' ORDER BY BINARY TABLE_NAME")->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
}

function ctsAssertExactFingerprint(mysqli $connection, string $prefix, string $expectedCollation = 'utf8mb4_unicode_ci'): void
{
    $expected = [
        'fm2_checklist_template_snapshots' => [
            'columns' => ['id|bigint(20) unsigned|NO|NULL|auto_increment|NEVER|NULL', 'snapshot_version|varchar(80)|NO|NULL||NEVER|NULL', 'captured_at|datetime|NO|NULL||NEVER|NULL', 'valid_from|datetime|NO|NULL||NEVER|NULL', 'validity_scope|varchar(120)|NO|NULL||NEVER|NULL', 'source_label|varchar(160)|NO|NULL||NEVER|NULL', 'content_sha256|char(64)|NO|NULL||NEVER|NULL', 'payload_json|longtext|NO|NULL||NEVER|NULL', 'created_at|datetime|NO|NULL||NEVER|NULL'],
            'indexes' => ['PRIMARY|0|BTREE|id', 'uq_hash|0|BTREE|content_sha256', 'uq_valid_from|0|BTREE|valid_from'],
        ],
        'fm2_checklist_template_associations' => [
            'columns' => ['id|bigint(20) unsigned|NO|NULL|auto_increment|NEVER|NULL', 'association_version|varchar(80)|NO|NULL||NEVER|NULL', 'subject_kind|varchar(40)|NO|NULL||NEVER|NULL', 'subject_id|varchar(160)|NO|NULL||NEVER|NULL', 'effective_at|datetime|NO|NULL||NEVER|NULL', 'template_snapshot_id|bigint(20) unsigned|NO|NULL||NEVER|NULL', 'template_snapshot_version|varchar(80)|NO|NULL||NEVER|NULL', 'template_content_sha256|char(64)|NO|NULL||NEVER|NULL', 'created_at|datetime|NO|NULL||NEVER|NULL'],
            'indexes' => ['PRIMARY|0|BTREE|id', 'snapshot_id|1|BTREE|template_snapshot_id', 'uq_subject|0|BTREE|subject_kind,subject_id'],
        ],
    ];
    foreach ($expected as $base => $manifest) {
        $table = $prefix . $base;
        $escaped = $connection->real_escape_string($table);
        $properties = $connection->query("SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}'")->fetch_assoc();
        assertSameValue(['ENGINE' => 'InnoDB', 'TABLE_COLLATION' => $expectedCollation], $properties, "{$table} exact engine/collation.");
        $rows = $connection->query("SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,IS_GENERATED,GENERATION_EXPRESSION,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' ORDER BY ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC);
        $columns = [];
        foreach ($rows as $row) {
            if ($row['CHARACTER_SET_NAME'] !== null) {
                assertSameValue('utf8mb4', $row['CHARACTER_SET_NAME'], "{$table} character charset.");
                assertSameValue($expectedCollation, $row['COLLATION_NAME'], "{$table} character collation.");
            }
            $columns[] = implode('|', [$row['COLUMN_NAME'], $row['COLUMN_TYPE'], $row['IS_NULLABLE'], $row['COLUMN_DEFAULT'] === null ? 'NULL' : (string) $row['COLUMN_DEFAULT'], $row['EXTRA'], $row['IS_GENERATED'], $row['GENERATION_EXPRESSION'] === null ? 'NULL' : (string) $row['GENERATION_EXPRESSION']]);
        }
        assertSameValue($manifest['columns'], $columns, "{$table} exact ordered columns/defaults/generation.");
        $indexes = array_map(static fn (array $row): string => implode('|', $row), $connection->query("SELECT INDEX_NAME,NON_UNIQUE,INDEX_TYPE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' GROUP BY INDEX_NAME,NON_UNIQUE,INDEX_TYPE ORDER BY BINARY INDEX_NAME")->fetch_all(MYSQLI_ASSOC));
        sort($indexes, SORT_STRING); $wanted = $manifest['indexes']; sort($wanted, SORT_STRING);
        assertSameValue($wanted, $indexes, "{$table} exact indexes.");
        $extraConstraints = $connection->query("SELECT CONSTRAINT_NAME,CONSTRAINT_TYPE FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' AND CONSTRAINT_TYPE IN ('FOREIGN KEY','CHECK')")->fetch_all(MYSQLI_ASSOC);
        assertSameValue([], $extraConstraints, "{$table} has no FK/CHECK.");
    }
}

function ctsExpectConflict(mysqli $connection, string $prefix, array $tables): void
{
    assertSameValue(['applied' => false, 'schemaVersion' => 7, 'reason' => 'SCHEMA_MIGRATION_CONFLICT', 'conflictingTables' => $tables], ctsApply($connection, $prefix), 'Incompatible family must fail with exact ordered conflicts.');
}

function ctsAssertArchitectureNoDdl(): void
{
    $root = dirname(__DIR__, 2); $violations = [];
    foreach (['app', 'rapid-pilot', 'public', 'bin'] as $area) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $area, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') continue;
            $relative = substr($file->getPathname(), strlen($root) + 1);
            if ($relative === 'app/InstallationProcess/ChecklistTemplateSchemaMigration.php' || str_starts_with($relative, 'rapid-pilot/verify-')) continue;
            $source = (string) file_get_contents($file->getPathname());
            if (preg_match('/\\b(?:CREATE|ALTER|DROP)\\s+TABLE\\b[^;]*fm2_checklist_template_(?:snapshots|associations)/is', $source) === 1) $violations[] = $relative;
        }
    }
    sort($violations, SORT_STRING);
    assertSameValue([], $violations, 'Only canonical v7 may own checklist-template DDL; runtime debt baseline is zero.');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: '23306');
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$database = 't_cts_001_' . bin2hex(random_bytes(6));
$admin = new mysqli($host, $user, $password, '', $port);
$admin->set_charset('utf8mb4');
$admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

try {
    $runner = ctsRunRunner($database);
    assertSameValue(0, $runner['exitCode'], 'Canonical runner must complete the composed v1-v11 catalogue.');
    assertSameValue('', $runner['stderr'], 'Successful canonical runner must keep stderr empty.');
    assertSameValue(
        ['ok' => true, 'schemaVersion' => 11, 'appliedVersions' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]],
        json_decode($runner['stdout'], true, flags: JSON_THROW_ON_ERROR),
        'Clean canonical runner must apply v7 and its landed v8-v10 successors.',
    );

    $connection = new mysqli($host, $user, $password, $database, $port);
    $connection->set_charset('utf8mb4');
    assertSameValue(
        ['fm2_checklist_template_associations', 'fm2_checklist_template_snapshots'],
        ctsTables($connection),
        'Canonical v7 must create both checklist-template tables on a clean database.',
    );
    ctsAssertExactFingerprint($connection, '');

    // Populated repeat: schema, rows and AUTO_INCREMENT are immutable.
    $hash = str_repeat('a', 64);
    $connection->query("INSERT INTO fm2_checklist_template_snapshots VALUES(41,'legacy-checklist-template-cutover-v1','2026-09-01 10:00:00','2026-09-01 10:00:00','scope','source','{$hash}','{\"fixture\":true}','2026-09-01 10:01:00')");
    $connection->query("INSERT INTO fm2_checklist_template_associations VALUES(51,'checklist-template-association-v1','operational_case','case-1','2026-09-01 10:00:00',41,'legacy-checklist-template-cutover-v1','{$hash}','2026-09-01 10:02:00')");
    $connection->query('ALTER TABLE fm2_checklist_template_snapshots AUTO_INCREMENT=80');
    $connection->query('ALTER TABLE fm2_checklist_template_associations AUTO_INCREMENT=90');
    $beforeRepeat = ctsState($connection, '');
    assertSameValue(['applied' => false, 'schemaVersion' => 7, 'tablesCreated' => []], ctsApply($connection, ''), 'Exact populated repeat is a no-op.');
    assertSameValue($beforeRepeat, ctsState($connection, ''), 'Repeat preserves schema/rows/AUTO_INCREMENT byte-for-byte.');

    // Both compatible partial directions preserve the existing sentinel.
    ctsCreateSnapshots($connection, 'ps_');
    $connection->query("INSERT INTO ps_fm2_checklist_template_snapshots VALUES(7,'v','2026-09-01 00:00:00','2026-09-01 00:00:00','s','l','" . str_repeat('b', 64) . "','{}','2026-09-01 00:00:01')");
    $partialBefore = ctsState($connection, 'ps_');
    assertSameValue(['applied' => true, 'schemaVersion' => 7, 'tablesCreated' => ['ps_fm2_checklist_template_associations']], ctsApply($connection, 'ps_'), 'Snapshots-only partial creates associations.');
    assertSameValue($partialBefore['ps_fm2_checklist_template_snapshots'], ctsState($connection, 'ps_')['ps_fm2_checklist_template_snapshots'], 'Snapshots partial sentinel is preserved.');
    ctsAssertExactFingerprint($connection, 'ps_');
    ctsCreateAssociations($connection, 'pa_');
    $connection->query("INSERT INTO pa_fm2_checklist_template_associations VALUES(9,'v','operational_case','same-id','2026-09-01 00:00:00',7,'v','" . str_repeat('c', 64) . "','2026-09-01 00:00:01')");
    $partialBefore = ctsState($connection, 'pa_');
    assertSameValue(['applied' => true, 'schemaVersion' => 7, 'tablesCreated' => ['pa_fm2_checklist_template_snapshots']], ctsApply($connection, 'pa_'), 'Associations-only partial creates snapshots.');
    assertSameValue($partialBefore['pa_fm2_checklist_template_associations'], ctsState($connection, 'pa_')['pa_fm2_checklist_template_associations'], 'Associations partial sentinel is preserved.');
    ctsAssertExactFingerprint($connection, 'pa_');

    // Independent near-match dimensions: column/type, SQL/string default,
    // generated expression, index name/order/kind, engine, collation, FK, CHECK.
    $mutations = [
        'col_' => 'ALTER TABLE %s MODIFY source_label VARCHAR(159) NOT NULL',
        'def_' => "ALTER TABLE %s MODIFY captured_at DATETIME NOT NULL DEFAULT '2026-01-01 00:00:00'",
        'sqlnull_' => "ALTER TABLE %s MODIFY source_label VARCHAR(160) NULL DEFAULT NULL",
        'strnull_' => "ALTER TABLE %s MODIFY source_label VARCHAR(160) NOT NULL DEFAULT 'NULL'",
        'gen_' => 'ALTER TABLE %s DROP COLUMN created_at, ADD COLUMN created_at DATETIME GENERATED ALWAYS AS (captured_at) VIRTUAL AFTER payload_json',
        'iname_' => 'ALTER TABLE %s DROP INDEX uq_hash, ADD UNIQUE KEY wrong_hash(content_sha256)',
        'iorder_' => 'ALTER TABLE %s DROP INDEX uq_subject, ADD UNIQUE KEY uq_subject(subject_id,subject_kind)',
        'ikind_' => 'ALTER TABLE %s DROP INDEX snapshot_id, ADD UNIQUE KEY snapshot_id(template_snapshot_id)',
        'engine_' => 'ALTER TABLE %s ENGINE=MyISAM',
        'coll_' => 'ALTER TABLE %s DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
        'check_' => "ALTER TABLE %s ADD CONSTRAINT extra_check CHECK (source_label <> '')",
    ];
    foreach ($mutations as $prefix => $sql) {
        ctsCreateSnapshots($connection, $prefix); ctsCreateAssociations($connection, $prefix);
        $target = str_contains($prefix, 'iorder') || str_contains($prefix, 'ikind') ? $prefix . 'fm2_checklist_template_associations' : $prefix . 'fm2_checklist_template_snapshots';
        $connection->query(sprintf($sql, ctsQuote($target)));
        $before = ctsState($connection, $prefix);
        ctsExpectConflict($connection, $prefix, [$target]);
        assertSameValue($before, ctsState($connection, $prefix), "{$prefix} conflict performs zero mutation.");
    }
    ctsCreateSnapshots($connection, 'fk_'); ctsCreateAssociations($connection, 'fk_');
    $connection->query('CREATE TABLE fk_parent(id BIGINT UNSIGNED PRIMARY KEY) ENGINE=InnoDB');
    $connection->query('ALTER TABLE fk_fm2_checklist_template_associations ADD CONSTRAINT extra_fk FOREIGN KEY(template_snapshot_id) REFERENCES fk_parent(id)');
    $before = ctsState($connection, 'fk_');
    ctsExpectConflict($connection, 'fk_', ['fk_fm2_checklist_template_associations']);
    assertSameValue($before, ctsState($connection, 'fk_'), 'Extra-FK conflict performs zero family mutation.');

    // Multi-conflict ordering and conflict+missing whole-family preflight.
    ctsCreateSnapshots($connection, 'multi_'); ctsCreateAssociations($connection, 'multi_');
    $connection->query('ALTER TABLE multi_fm2_checklist_template_snapshots ADD extra_col INT NULL');
    $connection->query('ALTER TABLE multi_fm2_checklist_template_associations ADD extra_col INT NULL');
    $before = ctsState($connection, 'multi_');
    ctsExpectConflict($connection, 'multi_', ['multi_fm2_checklist_template_snapshots', 'multi_fm2_checklist_template_associations']);
    assertSameValue($before, ctsState($connection, 'multi_'), 'Two-table conflict performs zero family mutation.');
    ctsCreateSnapshots($connection, 'cm_');
    $connection->query('ALTER TABLE cm_fm2_checklist_template_snapshots ADD extra_col INT NULL');
    $before = ctsState($connection, 'cm_');
    ctsExpectConflict($connection, 'cm_', ['cm_fm2_checklist_template_snapshots']);
    assertSameValue($before, ctsState($connection, 'cm_'), 'Conflict plus missing sibling performs zero family mutation.');

    // Prefix isolation includes identical IDs/facts and a decoy conflict.
    foreach (['one_', 'two_'] as $prefix) {
        assertSameValue(true, ctsApply($connection, $prefix)['applied'], "{$prefix} applies independently.");
        $connection->query("INSERT INTO {$prefix}fm2_checklist_template_snapshots VALUES(1,'v','2026-09-01 00:00:00','2026-09-01 00:00:00','s','l','" . str_repeat('d', 64) . "','{}','2026-09-01 00:00:01')");
        $connection->query("INSERT INTO {$prefix}fm2_checklist_template_associations VALUES(1,'v','operational_case','same','2026-09-01 00:00:00',1,'v','" . str_repeat('d', 64) . "','2026-09-01 00:00:01')");
    }
    $connection->query('ALTER TABLE two_fm2_checklist_template_snapshots ADD decoy INT NULL');
    $oneBefore = ctsState($connection, 'one_');
    assertSameValue(['applied' => false, 'schemaVersion' => 7, 'tablesCreated' => []], ctsApply($connection, 'one_'), 'Other-prefix decoy conflict is invisible.');
    assertSameValue($oneBefore, ctsState($connection, 'one_'), 'Other-prefix apply preserves namespace.');

    // Direct and composed prefix boundaries; rejected values must not touch DB.
    $prefix25 = str_repeat('p', 25);
    assertSameValue(true, ctsApply($connection, $prefix25)['applied'], '25-byte composed prefix is accepted.');
    $connection->close();
    foreach ([str_repeat('p', 26), 'invalid-prefix;'] as $invalidPrefix) {
        try { ctsApply($connection, $invalidPrefix); throw new TestFailure('Invalid prefix must be rejected.'); }
        catch (InvalidArgumentException) {} catch (Throwable) { throw new TestFailure('Invalid prefix reached closed DB access.'); }
        assertSameValue(
            ['exitCode' => 64, 'stdout' => "{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n", 'stderr' => ''],
            ctsRunRunner($database, $invalidPrefix),
            'Canonical CLI rejects invalid/26-byte prefix before DB access.',
        );
    }
    $connection = new mysqli($host, $user, $password, $database, $port); $connection->set_charset('utf8mb4');

    // Database-default validation: documented MariaDB UCA alias is accepted;
    // non-utf8mb4 default is rejected with no target DDL.
    $ucaDatabase = 't_cts_uca_' . bin2hex(random_bytes(4));
    $latinDatabase = 't_cts_latin_' . bin2hex(random_bytes(4));
    $admin->query("CREATE DATABASE `{$ucaDatabase}` DEFAULT CHARSET=utf8mb4 COLLATE=uca1400_ai_ci");
    $admin->query("CREATE DATABASE `{$latinDatabase}` DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci");
    try {
        $uca = new mysqli($host, $user, $password, $ucaDatabase, $port); $uca->set_charset('utf8mb4');
        assertSameValue(true, ctsApply($uca, 'uca_')['applied'], 'Documented nullable-character-set UCA alias is accepted.');
        $reportedUca = (string) $uca->query('SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()')->fetch_assoc()['DEFAULT_COLLATION_NAME'];
        ctsAssertExactFingerprint($uca, 'uca_', $reportedUca);
        $ucaBefore = ctsState($uca, 'uca_');
        assertSameValue(['applied' => false, 'schemaVersion' => 7, 'tablesCreated' => []], ctsApply($uca, 'uca_'), 'Exact UCA family repeat is a no-op.');
        assertSameValue($ucaBefore, ctsState($uca, 'uca_'), 'UCA repeat preserves exact state.');
        $uca->close();
        $latin = new mysqli($host, $user, $password, $latinDatabase, $port); $latin->set_charset('utf8mb4');
        try { ctsApply($latin, 'bad_'); throw new TestFailure('Non-utf8mb4 default must be rejected.'); } catch (UnexpectedValueException|RuntimeException) {}
        assertSameValue([], ctsTablesForPrefix($latin, 'bad_'), 'Invalid database default performs zero target DDL.'); $latin->close();
    } finally { $admin->query("DROP DATABASE IF EXISTS `{$ucaDatabase}`"); $admin->query("DROP DATABASE IF EXISTS `{$latinDatabase}`"); }
    // MariaDB itself refuses syntactically invalid and unknown defaults before
    // a selected-database public seam can exist; prove both impossible fixture
    // states independently and that neither creates a schema to mutate.
    foreach (['utf8mb4_invalid-name!', 'utf8mb4_definitely_unknown_ci'] as $invalidDefault) {
        $rejectedDatabase = 't_cts_reject_' . bin2hex(random_bytes(4));
        try {
            $admin->query('CREATE DATABASE ' . ctsQuote($rejectedDatabase) . ' DEFAULT CHARSET=utf8mb4 COLLATE ' . ctsQuote($invalidDefault));
            throw new TestFailure('MariaDB must reject invalid/unknown database default.');
        } catch (mysqli_sql_exception) {
            $escapedRejected = $admin->real_escape_string($rejectedDatabase);
            assertSameValue('0', (string) $admin->query("SELECT COUNT(*) n FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='{$escapedRejected}'")->fetch_assoc()['n'], 'Rejected default creates no database or target DDL.');
        }
    }

    // Runtime consumers: exact migrated family works with SELECT/INSERT only,
    // including replay/conflicts; absent/incompatible family fails closed.
    require_once dirname(__DIR__, 2) . '/rapid-pilot/legacy-migration/LegacyChecklistTemplateSnapshot.php';
    require_once dirname(__DIR__, 2) . '/rapid-pilot/legacy-migration/ChecklistTemplateAssociation.php';
    ctsApply($connection, 'rt_');
    $runtimeUser = 'cts_rt_' . bin2hex(random_bytes(3)); $runtimePassword = bin2hex(random_bytes(8));
    $admin->query("CREATE USER '{$runtimeUser}'@'%' IDENTIFIED BY '{$runtimePassword}'");
    $admin->query("GRANT SELECT,INSERT ON `{$database}`.`rt_fm2_checklist_template_snapshots` TO '{$runtimeUser}'@'%'");
    $admin->query("GRANT SELECT,INSERT ON `{$database}`.`rt_fm2_checklist_template_associations` TO '{$runtimeUser}'@'%'");
    try {
        $runtime = new mysqli($host, $runtimeUser, $runtimePassword, $database, $port); $runtime->set_charset('utf8mb4');
        $grantRows = $runtime->query('SHOW GRANTS FOR CURRENT_USER')->fetch_all(MYSQLI_NUM);
        $grantText = implode("\n", array_column($grantRows, 0));
        assertSameValue(0, preg_match('/\\b(?:CREATE|ALTER|DROP)\\b/i', $grantText), 'Runtime principal has no DDL privilege.');
        $snapshot = LegacyChecklistTemplateSnapshot::build([], [], '2026-09-01 12:00:00');
        $target = new LegacyChecklistTemplateMySqlTarget($runtime, 'rt_');
        $runtimeSchema = ctsSchemaState($connection, 'rt_');
        $created = $target->apply($snapshot, '2026-09-01 12:00:00', '2026-09-01 12:01:00');
        assertSameValue(['snapshotId' => 1, 'created' => true], $created, 'DDL-denied snapshot import succeeds.');
        assertSameValue([[
            'id' => '1',
            'snapshot_version' => 'legacy-checklist-template-cutover-v1',
            'captured_at' => '2026-09-01 12:00:00',
            'valid_from' => '2026-09-01 12:00:00',
            'validity_scope' => 'active_baseline_and_future_native_only',
            'source_label' => 'legacy_fmonitor_current_at_cutover',
            'content_sha256' => $snapshot['contentSha256'],
            'payload_json' => '{"snapshotVersion":"legacy-checklist-template-cutover-v1","capturedAt":"2026-09-01 12:00:00","validFrom":"2026-09-01 12:00:00","validity":"current_at_cutover_for_active_baselines_and_future_native_events","source":"legacy_fmonitor.fm_install_checklist+parts","parts":[],"definitions":[]}',
            'created_at' => '2026-09-01 12:01:00',
        ]], $connection->query('SELECT * FROM rt_fm2_checklist_template_snapshots ORDER BY id')->fetch_all(MYSQLI_ASSOC), 'Snapshot create persists every exact test-owned fact.');
        assertSameValue($runtimeSchema, ctsSchemaState($connection, 'rt_'), 'Snapshot import performs zero schema mutation.');
        $beforeReplay = ctsRuntimeState($connection, 'rt_');
        assertSameValue(['snapshotId' => 1, 'created' => false], $target->apply($snapshot, '2026-09-01 12:00:00', '2026-09-01 12:02:00'), 'DDL-denied snapshot replay is immutable.');
        assertSameValue($beforeReplay, ctsRuntimeState($connection, 'rt_'), 'Snapshot replay preserves schema and persisted rows.');
        $conflictingSnapshot = $snapshot; $conflictingSnapshot['contentSha256'] = str_repeat('f', 64);
        $beforeConflict = ctsRuntimeState($connection, 'rt_');
        try { $target->apply($conflictingSnapshot, '2026-09-01 12:00:00', '2026-09-01 12:02:00'); throw new TestFailure('Changed snapshot hash must conflict.'); }
        catch (RuntimeException $e) { assertSameValue('CHECKLIST_TEMPLATE_CAPTURE_CONFLICT', $e->getMessage(), 'Snapshot hash conflict code.'); }
        assertSameValue($beforeConflict, ctsRuntimeState($connection, 'rt_'), 'Snapshot conflict preserves schema and persisted rows.');
        $association = new ChecklistTemplateAssociationTarget($runtime, 'rt_');
        $runtimeSchema = ctsSchemaState($connection, 'rt_');
        $linked = $association->associate('operational_case', 'case-runtime', '2026-09-01 12:00:00', 1, $snapshot['contentSha256'], LegacyChecklistTemplateSnapshot::VERSION, '2026-09-01 12:03:00');
        assertSameValue(true, $linked['created'], 'DDL-denied association create succeeds.');
        assertSameValue([[
            'id' => '1',
            'association_version' => 'checklist-template-association-v1',
            'subject_kind' => 'operational_case',
            'subject_id' => 'case-runtime',
            'effective_at' => '2026-09-01 12:00:00',
            'template_snapshot_id' => '1',
            'template_snapshot_version' => 'legacy-checklist-template-cutover-v1',
            'template_content_sha256' => $snapshot['contentSha256'],
            'created_at' => '2026-09-01 12:03:00',
        ]], $connection->query('SELECT * FROM rt_fm2_checklist_template_associations ORDER BY id')->fetch_all(MYSQLI_ASSOC), 'Association create persists every exact test-owned fact.');
        assertSameValue($runtimeSchema, ctsSchemaState($connection, 'rt_'), 'Association create performs zero schema mutation.');
        $beforeReplay = ctsRuntimeState($connection, 'rt_');
        $replayed = $association->associate('operational_case', 'case-runtime', '2026-09-01 12:00:00', 1, $snapshot['contentSha256'], LegacyChecklistTemplateSnapshot::VERSION, '2026-09-01 12:04:00');
        $linkedComparable = $linked; $replayedComparable = $replayed; unset($linkedComparable['created'], $replayedComparable['created']);
        assertSameValue(false, $replayed['created'], 'DDL-denied association replay reports not created.');
        assertSameValue($linkedComparable, $replayedComparable, 'Association replay returns exact original identity and immutable facts.');
        assertSameValue($beforeReplay, ctsRuntimeState($connection, 'rt_'), 'Association replay preserves schema and persisted rows.');
        $beforeConflict = ctsRuntimeState($connection, 'rt_');
        try { $association->associate('operational_case', 'case-runtime', '2026-09-02 12:00:00', 1, $snapshot['contentSha256'], LegacyChecklistTemplateSnapshot::VERSION, '2026-09-01 12:05:00'); throw new TestFailure('Changed association must conflict.'); } catch (DomainException $e) { assertSameValue('CHECKLIST_TEMPLATE_ASSOCIATION_CONFLICT', $e->getMessage(), 'Immutable conflict code.'); }
        assertSameValue($beforeConflict, ctsRuntimeState($connection, 'rt_'), 'Association rebind conflict preserves schema and persisted rows.');
        foreach ([
            ['operational_case', 'missing-snapshot', 999, $snapshot['contentSha256'], LegacyChecklistTemplateSnapshot::VERSION, 'CHECKLIST_TEMPLATE_SNAPSHOT_MISMATCH'],
            ['operational_case', 'hash-mismatch', 1, str_repeat('0', 64), LegacyChecklistTemplateSnapshot::VERSION, 'CHECKLIST_TEMPLATE_SNAPSHOT_MISMATCH'],
            ['operational_case', 'version-mismatch', 1, $snapshot['contentSha256'], 'wrong-template-version', 'CHECKLIST_TEMPLATE_SNAPSHOT_MISMATCH'],
            ['unsupported_subject', 'policy-reject', 1, $snapshot['contentSha256'], LegacyChecklistTemplateSnapshot::VERSION, 'DEFINITION_VERSION_UNPROVEN'],
        ] as [$kind, $subject, $snapshotId, $expectedHash, $expectedVersion, $code]) {
            $beforeRejected = ctsRuntimeState($connection, 'rt_');
            try { $association->associate($kind, $subject, '2026-09-01 12:00:00', $snapshotId, $expectedHash, $expectedVersion, '2026-09-01 12:06:00'); throw new TestFailure("{$code} must reject."); }
            catch (DomainException $e) { assertSameValue($code, $e->getMessage(), "{$code} exact rejection."); }
            assertSameValue($beforeRejected, ctsRuntimeState($connection, 'rt_'), "{$code} preserves schema and persisted rows.");
        }
        $runtime->close();
    } finally { $admin->query("DROP USER IF EXISTS '{$runtimeUser}'@'%'"); }
    foreach (['abs_', 'badrt_'] as $runtimePrefix) {
        if ($runtimePrefix === 'badrt_') { ctsCreateSnapshots($connection, $runtimePrefix); ctsCreateAssociations($connection, $runtimePrefix); $connection->query('ALTER TABLE badrt_fm2_checklist_template_snapshots ADD extra_col INT NULL'); }
        $runtimeTarget = new LegacyChecklistTemplateMySqlTarget($connection, $runtimePrefix);
        $beforeRejected = ctsRuntimeState($connection, $runtimePrefix);
        try { $runtimeTarget->apply(LegacyChecklistTemplateSnapshot::build([], [], '2026-09-02 00:00:00'), '2026-09-02 00:00:00', '2026-09-02 00:00:01'); throw new TestFailure('Runtime schema precondition must reject.'); }
        catch (RuntimeException $e) { assertSameValue('CHECKLIST_TEMPLATE_SCHEMA_REQUIRED', $e->getMessage(), 'Absent/incompatible runtime schema fails closed.'); }
        assertSameValue($beforeRejected, ctsRuntimeState($connection, $runtimePrefix), 'Rejected snapshot consumer preserves schema and persisted rows.');
        $associationTarget = new ChecklistTemplateAssociationTarget($connection, $runtimePrefix);
        $beforeRejected = ctsRuntimeState($connection, $runtimePrefix);
        try { $associationTarget->associate('operational_case', 'schema-required', '2026-09-02 00:00:00', 1, str_repeat('e', 64), LegacyChecklistTemplateSnapshot::VERSION, '2026-09-02 00:00:01'); throw new TestFailure('Association runtime schema precondition must reject.'); }
        catch (RuntimeException $e) { assertSameValue('CHECKLIST_TEMPLATE_SCHEMA_REQUIRED', $e->getMessage(), 'Absent/incompatible association schema fails closed.'); }
        assertSameValue($beforeRejected, ctsRuntimeState($connection, $runtimePrefix), 'Rejected association consumer preserves schema and persisted rows.');
        $beforeRejected = ctsRuntimeState($connection, $runtimePrefix);
        try { $associationTarget->associateActiveBaseline(999, 1, '2026-09-02 00:00:01'); throw new TestFailure('Active-baseline association schema precondition must reject before baseline/snapshot lookup.'); }
        catch (RuntimeException $e) { assertSameValue('CHECKLIST_TEMPLATE_SCHEMA_REQUIRED', $e->getMessage(), 'Absent/incompatible active-baseline association fails closed before raw query details.'); }
        assertSameValue($beforeRejected, ctsRuntimeState($connection, $runtimePrefix), 'Rejected active-baseline consumer preserves schema and persisted rows.');
    }
    // Unexpected driver/schema-inspection failures are not compatibility
    // outcomes and must not be masked as CHECKLIST_TEMPLATE_SCHEMA_REQUIRED.
    $closed = new mysqli($host, $user, $password, $database, $port); $closed->set_charset('utf8mb4'); $closed->close();
    foreach ([
        static fn (): array => (new LegacyChecklistTemplateMySqlTarget($closed, 'closed_'))->apply(LegacyChecklistTemplateSnapshot::build([], [], '2026-09-03 00:00:00'), '2026-09-03 00:00:00', '2026-09-03 00:00:01'),
        static fn (): array => (new ChecklistTemplateAssociationTarget($closed, 'closed_'))->associate('operational_case', 'closed-driver', '2026-09-03 00:00:00', 1, str_repeat('e', 64), LegacyChecklistTemplateSnapshot::VERSION, '2026-09-03 00:00:01'),
    ] as $closedConnectionCall) {
        try { $closedConnectionCall(); throw new TestFailure('Closed-connection schema inspection must expose a driver failure.'); }
        catch (mysqli_sql_exception|Error $e) { assertSameValue(false, $e->getMessage() === 'CHECKLIST_TEMPLATE_SCHEMA_REQUIRED', 'Unexpected driver failure is not masked as schema-required.'); }
    }
    ctsAssertArchitectureNoDdl();
    $connection->close();
} finally {
    $admin->query("DROP DATABASE IF EXISTS `{$database}`");
    $admin->close();
}

echo "CHECKLIST-TEMPLATE-SCHEMA-001 migration runner test passed\n";
