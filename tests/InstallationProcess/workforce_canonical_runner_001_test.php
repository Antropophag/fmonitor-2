<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/BitrixWorkforceSchemaV5Contract.php';

use FMonitor2\InstallationProcess\BitrixWorkforceHistorySchemaMigration;
use FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration;

// Specifications: WORKFORCE-CANONICAL-RUNNER-001 v0.1 and
// BITRIX-WORKFORCE-SCHEMA-001 v0.3.

function wcrConnection(?string $database = null): mysqli
{
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

function wcrRows(mysqli $connection, string $sql): array
{
    return $connection->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function wcrRun(array $environment): array
{
    $command = ['/usr/bin/env', '-i'];
    foreach ($environment as $name => $value) {
        if (preg_match('/^[A-Z0-9_]+$/D', (string) $name) !== 1 || str_contains((string) $value, "\0")) {
            throw new TestFailure('Test environment must be safe for direct argv execution.');
        }
        $command[] = $name . '=' . $value;
    }
    $command = [...$command, PHP_BINARY, dirname(__DIR__, 2) . '/bin/fmonitor2-migrate.php'];
    $pipes = [];
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2));
    if (!is_resource($process)) {
        throw new TestFailure('Canonical migration CLI must start.');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['exitCode' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

function wcrNormalizeCheck(string $clause): string
{
    $normalized = '';
    $quoted = false;
    for ($index = 0, $length = strlen($clause); $index < $length; $index++) {
        $character = $clause[$index];
        if ($character === "'") {
            $normalized .= $character;
            if ($quoted && $index + 1 < $length && $clause[$index + 1] === "'") {
                $normalized .= $clause[++$index];
            } else {
                $quoted = !$quoted;
            }
        } elseif ($quoted || ($character !== '`' && !ctype_space($character))) {
            $normalized .= $quoted ? $character : strtolower($character);
        }
    }
    if ($quoted) {
        throw new TestFailure('Unterminated CHECK literal returned by MariaDB.');
    }
    while (str_starts_with($normalized, '(') && str_ends_with($normalized, ')')) {
        $depth = 0;
        $quoted = false;
        $whole = true;
        $last = strlen($normalized) - 1;
        for ($index = 0; $index <= $last; $index++) {
            $character = $normalized[$index];
            if ($character === "'") {
                if ($quoted && $index < $last && $normalized[$index + 1] === "'") {
                    $index++;
                } else {
                    $quoted = !$quoted;
                }
            } elseif (!$quoted && $character === '(') {
                $depth++;
            } elseif (!$quoted && $character === ')' && --$depth === 0 && $index !== $last) {
                $whole = false;
                break;
            }
        }
        if (!$whole) {
            break;
        }
        $normalized = substr($normalized, 1, -1);
    }
    return $normalized;
}

function wcrAssertExactV5(mysqli $connection, string $prefix): void
{
    $databaseCollation = wcrRows($connection, 'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()')[0]['DEFAULT_COLLATION_NAME'];
    foreach (BitrixWorkforceSchemaV5Contract::columns() as $logicalTable => $expectedColumns) {
        $table = $prefix . $logicalTable;
        $quotedTable = '`' . str_replace('`', '``', $table) . '`';
        $properties = wcrRows($connection, "SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'")[0] ?? null;
        assertSameValue(true, $properties !== null, "{$table} must exist in the canonical v5 catalogue.");
        assertSameValue('InnoDB', $properties['ENGINE'], "{$table} engine.");
        assertSameValue(true, str_starts_with((string) $properties['TABLE_COLLATION'], 'utf8mb4_'), "{$table} charset.");

        $rawColumns = wcrRows($connection, "SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,EXTRA,COLUMN_DEFAULT,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' ORDER BY ORDINAL_POSITION");
        $actualColumns = array_map(static fn (array $column): array => [
            $column['COLUMN_NAME'],
            preg_replace('/^(bigint|int|tinyint)\(\d+\)/', '$1', $column['COLUMN_TYPE']),
            $column['IS_NULLABLE'],
            $column['EXTRA'],
            $column['COLUMN_DEFAULT'] === null ? 'NULL' : (string) $column['COLUMN_DEFAULT'],
        ], $rawColumns);
        assertSameValue($expectedColumns, $actualColumns, "{$table} exact ordered column manifest.");
        foreach ($rawColumns as $column) {
            if ($column['CHARACTER_SET_NAME'] !== null) {
                assertSameValue('utf8mb4', $column['CHARACTER_SET_NAME'], "{$table}.{$column['COLUMN_NAME']} charset.");
                assertSameValue($databaseCollation, $column['COLLATION_NAME'], "{$table}.{$column['COLUMN_NAME']} collation.");
            }
        }

        $indexes = array_map(
            static fn (array $index): string => $index['INDEX_NAME'] . '|' . $index['NON_UNIQUE'] . '|' . $index['INDEX_TYPE'] . '|' . $index['COLUMNS'],
            wcrRows($connection, "SELECT INDEX_NAME,NON_UNIQUE,INDEX_TYPE,GROUP_CONCAT(CONCAT(COLUMN_NAME,':',COALESCE(SUB_PART,'FULL'),':',COALESCE(COLLATION,'NULL'),':',COALESCE(IGNORED,'NO')) ORDER BY SEQ_IN_INDEX) COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' GROUP BY INDEX_NAME,NON_UNIQUE,INDEX_TYPE"),
        );
        sort($indexes, SORT_STRING);
        $expectedIndexes = BitrixWorkforceSchemaV5Contract::indexes($prefix)[$logicalTable];
        sort($expectedIndexes, SORT_STRING);
        assertSameValue($expectedIndexes, $indexes, "{$table} exact index manifest.");

        $checks = array_map(
            static fn (array $check): string => $check['CONSTRAINT_NAME'] . '|' . wcrNormalizeCheck((string) $check['CHECK_CLAUSE']),
            wcrRows($connection, "SELECT tc.CONSTRAINT_NAME,cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.TABLE_NAME=tc.TABLE_NAME AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME='{$table}' AND tc.CONSTRAINT_TYPE='CHECK'"),
        );
        sort($checks, SORT_STRING);
        assertSameValue(BitrixWorkforceSchemaV5Contract::checks($prefix)[$logicalTable], $checks, "{$table} exact CHECK manifest.");

        $foreignKeys = array_map(
            static fn (array $foreignKey): string => implode('|', [$foreignKey['CONSTRAINT_NAME'], $foreignKey['COLUMN_NAME'], $foreignKey['REFERENCED_TABLE_NAME'], $foreignKey['REFERENCED_COLUMN_NAME'], $foreignKey['UPDATE_RULE'], $foreignKey['DELETE_RULE']]),
            wcrRows($connection, "SELECT k.CONSTRAINT_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.TABLE_NAME=k.TABLE_NAME AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME='{$table}' AND k.REFERENCED_TABLE_NAME IS NOT NULL"),
        );
        sort($foreignKeys, SORT_STRING);
        assertSameValue(BitrixWorkforceSchemaV5Contract::foreignKeys($prefix)[$logicalTable], $foreignKeys, "{$table} exact foreign-key manifest.");

        // Ensure every expected identifier, including prefix-derived symbols, fits MariaDB's 64-byte limit.
        foreach (array_merge(array_column($rawColumns, 'COLUMN_NAME'), array_map(static fn (string $item): string => explode('|', $item, 2)[0], $indexes), array_map(static fn (string $item): string => explode('|', $item, 2)[0], $checks), array_map(static fn (string $item): string => explode('|', $item, 2)[0], $foreignKeys)) as $identifier) {
            assertSameValue(true, strlen($identifier) <= 64, "{$quotedTable} derived identifier {$identifier} must fit 64 bytes.");
        }
    }
}

function wcrState(mysqli $connection): array
{
    $state = [];
    foreach (wcrRows($connection, 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY BINARY TABLE_NAME') as $row) {
        $table = $row['TABLE_NAME'];
        $quoted = '`' . str_replace('`', '``', $table) . '`';
        $state[$table] = [
            'create' => wcrRows($connection, "SHOW CREATE TABLE {$quoted}")[0]['Create Table'],
            'rows' => wcrRows($connection, "SELECT * FROM {$quoted}"),
        ];
    }
    return $state;
}

function wcrApplyV1V4(mysqli $connection, string $prefix): void
{
    ProductionProcessSchemaMigration::apply($connection, $prefix);
    WorkforceCatalogSchemaMigration::apply($connection, $prefix);
    ProcessUserCapabilitiesSchemaMigration::apply($connection, $prefix);
    ProcessCommandCapabilitiesSchemaMigration::apply($connection, $prefix);
}

function wcrEnvironment(string $database, string $prefix, ?string $user = null, ?string $password = null): array
{
    return [
        'FMONITOR_DB_HOST' => getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        'FMONITOR_DB_PORT' => getenv('FMONITOR_TEST_DB_PORT') ?: '23306',
        'FMONITOR_DB_NAME' => $database,
        'FMONITOR_DB_USER' => $user ?? (getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root'),
        'FMONITOR_DB_PASSWORD' => $password ?? (getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local'),
        'FMONITOR_PROCESS_TABLE_PREFIX' => $prefix,
    ];
}

function wcrAssertResult(array $expected, array $actual, string $message): void
{
    assertSameValue(
        ['exitCode' => $expected[0], 'stdout' => $expected[1], 'stderr' => ''],
        $actual,
        $message,
    );
}

function wcrAssertFreshV5Rows(mysqli $connection, string $prefix): void
{
    assertSameValue([], wcrRows($connection, "SELECT * FROM `{$prefix}fm2_workforce_sync_runs`"), 'Fresh v5 runs are literally empty.');
    assertSameValue([], wcrRows($connection, "SELECT * FROM `{$prefix}fm2_workforce_observations`"), 'Fresh v5 observations are literally empty.');
    assertSameValue(
        [['singleton_id' => '1', 'last_successful_run_id' => null, 'last_successful_at' => null]],
        wcrRows($connection, "SELECT singleton_id,last_successful_run_id,last_successful_at FROM `{$prefix}fm2_workforce_sync_metadata` ORDER BY singleton_id"),
        'Fresh v5 metadata is the literal singleton (1, null, null).',
    );
}

function wcrAssertDirectFamilyBoundary(mysqli $connection): void
{
    $connection->close();
    try {
        BitrixWorkforceHistorySchemaMigration::apply($connection, str_repeat('b', 38));
        throw new TestFailure('Exact 38-byte direct-family prefix must be rejected before DB access.');
    } catch (InvalidArgumentException) {
    } catch (Throwable) {
        throw new TestFailure('Exact 38-byte direct-family prefix reached DB access.');
    }
    try {
        BitrixWorkforceHistorySchemaMigration::apply($connection, str_repeat('b', 37));
        throw new TestFailure('Exact 37-byte direct-family prefix must proceed to DB access.');
    } catch (InvalidArgumentException) {
        throw new TestFailure('Exact 37-byte direct-family prefix must remain accepted.');
    } catch (Throwable) {
    }
}

function wcrAssertRuntimeOwnership(): void
{
    $root = dirname(__DIR__, 2);
    $violations = [];
    foreach (['app', 'rapid-pilot', 'public', 'bin'] as $relativeRoot) {
        $directory = $root . '/' . $relativeRoot;
        if (!is_dir($directory)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($root) + 1);
            $basename = $file->getBasename();
            $isFixture = str_starts_with($relative, 'rapid-pilot/verify-') || str_contains($relative, '/demo/');
            $isMigrationOwner = str_starts_with($relative, 'app/InstallationProcess/') && str_ends_with($basename, 'SchemaMigration.php');
            $isCanonicalRunner = $relative === 'bin/fmonitor2-migrate.php';
            $source = (string) file_get_contents($file->getPathname());
            if (!$isMigrationOwner && !$isCanonicalRunner && !$isFixture && preg_match('/BitrixWorkforceHistorySchemaMigration\s*::\s*apply\s*\(/', $source) === 1) {
                $violations[] = $relative . ': direct workforce-v5 apply';
            }
            if (!$isMigrationOwner && !$isCanonicalRunner && !$isFixture && preg_match('/WorkforceCatalogSchemaMigration\s*::\s*apply\s*\(/', $source) === 1) {
                $violations[] = $relative . ': direct workforce-v2 apply';
            }
            if (!$isMigrationOwner && !$isFixture && preg_match('/\b(?:CREATE|ALTER|DROP)\s+TABLE\b[^;]*(?:fm2_workforce_|workforce_(?:catalog|observations|sync_runs|sync_metadata))/i', $source) === 1) {
                $violations[] = $relative . ': workforce DDL';
            }
        }
    }
    sort($violations, SORT_STRING);
    assertSameValue([], $violations, 'Runtime consumers must not own workforce migration calls or DDL.');

    $architecture = wcrRunCommand(['make', 'architecture-check'], $root);
    assertSameValue(0, $architecture['exitCode'], "Repository architecture ratchet must pass:\n" . $architecture['stdout'] . $architecture['stderr']);
}

function wcrRunCommand(array $command, string $workingDirectory): array
{
    $pipes = [];
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $workingDirectory);
    if (!is_resource($process)) {
        throw new TestFailure('Required verification command must start.');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['exitCode' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

$token = bin2hex(random_bytes(6));
$database = 't_wcr_001_' . $token;
$prefix25 = str_repeat('a', 25);
$prefix26 = str_repeat('a', 26);
$admin = wcrConnection();
$admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

try {
    $setupConnection = wcrConnection($database);
    $databaseCollation = wcrRows($setupConnection, 'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()')[0]['DEFAULT_COLLATION_NAME'];
    $charsetDefaultCollation = wcrRows($setupConnection, "SELECT DEFAULT_COLLATE_NAME FROM information_schema.CHARACTER_SETS WHERE CHARACTER_SET_NAME='utf8mb4'")[0]['DEFAULT_COLLATE_NAME'];
    assertSameValue('utf8mb4_unicode_ci', $databaseCollation, 'Clean fixture must use the explicit database-default collation.');
    assertSameValue(false, $databaseCollation === $charsetDefaultCollation, 'Clean fixture database default must differ from the utf8mb4 charset default.');
    $setupConnection->close();

    $environment = wcrEnvironment($database, $prefix25);
    $cleanResult = wcrRun($environment);
    $connection = wcrConnection($database);
    $cleanTables = array_column(wcrRows($connection, "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$prefix25}fm2\\_%' ORDER BY BINARY TABLE_NAME"), 'TABLE_NAME');
    assertSameValue(
        [
            'result' => ['exitCode' => 0, 'stdout' => "{\"ok\":true,\"schemaVersion\":5,\"appliedVersions\":[1,2,3,4,5]}\n", 'stderr' => ''],
            'tables' => array_map(static fn (string $table): string => $prefix25 . $table, [
                'fm2_assignment_orders',
                'fm2_installation_cases',
                'fm2_order_artifacts',
                'fm2_order_installers',
                'fm2_process_events',
                'fm2_process_tasks',
                'fm2_process_user_capabilities',
                'fm2_workforce_catalog',
                'fm2_workforce_observations',
                'fm2_workforce_sync_metadata',
                'fm2_workforce_sync_runs',
            ]),
        ],
        ['result' => $cleanResult, 'tables' => $cleanTables],
        'A clean exact 25-byte composed prefix must reach ordered canonical v5 and create the full literal catalogue.',
    );
    wcrAssertExactV5($connection, $prefix25);
    wcrAssertFreshV5Rows($connection, $prefix25);

    $connection->query("INSERT INTO `{$prefix25}fm2_installation_cases` (legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (4512,'needs_assignment_order','2026-09-02T09:00:00+03:00','2026-09-02T09:00:00+03:00',1)");
    $connection->query("INSERT INTO `{$prefix25}fm2_workforce_catalog` (installer_tab_id,fio,position,employment_status,employed_from,employed_to,workforce_source,workforce_source_updated_at,delivery_system,delivery_person_id,dismissal_effective_at,first_observed_dismissed_at,dismissal_time_quality,reconciliation_state,authority_system,last_successful_sync_run_id,last_successful_sync_at) VALUES (1042,'Иванов Иван Иванович','Электромеханик по лифтам','employed','2024-02-01',NULL,'one_c_zup_via_bitrix','2026-09-02T08:00:00+03:00','bitrix',1042,NULL,NULL,NULL,'delivered','one_c_zup',NULL,NULL)");
    $connection->query("INSERT INTO `{$prefix25}fm2_workforce_sync_runs` VALUES ('11111111-1111-1111-1111-111111111111','completed','2026-09-02T08:00:00+03:00','2026-09-02T08:01:00+03:00','2026-09-02T08:02:00+03:00',NULL,1,1,1,0,'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa')");
    $connection->query("INSERT INTO `{$prefix25}fm2_workforce_observations` (sync_run_id,delivery_person_id,employee_number,full_name,position,employment_status,employed_from,dismissal_effective_at,authority_system,delivery_system,source_modified_at,reconciliation_state,observed_at,dismissal_time_quality) VALUES ('11111111-1111-1111-1111-111111111111',1042,1042,'Иванов Иван Иванович','Электромеханик по лифтам','employed','2024-02-01',NULL,'one_c_zup','bitrix','2026-09-02T07:59:00+03:00','delivered','2026-09-02T08:01:00+03:00','observed_only')");
    $connection->query("UPDATE `{$prefix25}fm2_workforce_sync_metadata` SET last_successful_run_id='11111111-1111-1111-1111-111111111111',last_successful_at='2026-09-02T08:02:00+03:00' WHERE singleton_id=1");
    $repeatBefore = wcrState($connection);
    wcrAssertResult([0, "{\"ok\":true,\"schemaVersion\":5,\"appliedVersions\":[]}\n"], wcrRun($environment), 'Completed populated repeat must report no applied versions.');
    assertSameValue($repeatBefore, wcrState($connection), 'Completed repeat preserves every v1-v5 definition and row byte-for-byte.');

    $partialPrefix = 'wcr_partial_';
    wcrApplyV1V4($connection, $partialPrefix);
    BitrixWorkforceHistorySchemaMigration::apply($connection, $partialPrefix);
    $connection->query("INSERT INTO `{$partialPrefix}fm2_workforce_sync_runs` (run_id,status,started_at) VALUES ('22222222-2222-2222-2222-222222222222','started','2026-09-02T10:00:00+03:00')");
    $connection->query("DROP TABLE `{$partialPrefix}fm2_workforce_observations`");
    $partialBefore = wcrState($connection);
    wcrAssertResult([0, "{\"ok\":true,\"schemaVersion\":5,\"appliedVersions\":[5]}\n"], wcrRun(wcrEnvironment($database, $partialPrefix)), 'Compatible v5 partial state must recover only v5.');
    wcrAssertExactV5($connection, $partialPrefix);
    $partialAfter = wcrState($connection);
    foreach ($partialBefore as $table => $state) {
        assertSameValue($state, $partialAfter[$table], "Partial recovery preserves existing table {$table} and its rows.");
    }

    $conflictPrefix = 'wcr_v5_bad_';
    wcrApplyV1V4($connection, $conflictPrefix);
    $connection->query("CREATE TABLE `{$conflictPrefix}fm2_workforce_sync_runs` (sentinel INT NOT NULL PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("INSERT INTO `{$conflictPrefix}fm2_workforce_sync_runs` VALUES (7)");
    $conflictBefore = wcrState($connection);
    wcrAssertResult([2, "{\"ok\":false,\"reason\":\"SCHEMA_MIGRATION_CONFLICT\",\"schemaVersion\":5}\n"], wcrRun(wcrEnvironment($database, $conflictPrefix)), 'Incompatible workforce table must report the exact v5 conflict.');
    assertSameValue($conflictBefore, wcrState($connection), 'Workforce conflict performs zero schema or row mutation.');

    $earlyPrefix = 'wcr_v1_bad_';
    $connection->query("CREATE TABLE `{$earlyPrefix}fm2_installation_cases` (sentinel INT NOT NULL PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("INSERT INTO `{$earlyPrefix}fm2_installation_cases` VALUES (9)");
    $earlyBefore = wcrState($connection);
    wcrAssertResult([2, "{\"ok\":false,\"reason\":\"SCHEMA_MIGRATION_CONFLICT\",\"schemaVersion\":1}\n"], wcrRun(wcrEnvironment($database, $earlyPrefix)), 'Earlier conflict must short-circuit at v1 before workforce v5.');
    assertSameValue($earlyBefore, wcrState($connection), 'Earlier conflict performs zero mutation and never creates workforce targets.');

    $failurePrefix = 'wcr_v5_fail_';
    wcrApplyV1V4($connection, $failurePrefix);
    $limitedUser = 'wcr_' . $token;
    $limitedPassword = 'Wcr-' . $token . '-only';
    $admin->query("CREATE USER `{$limitedUser}`@`%` IDENTIFIED BY '{$limitedPassword}'");
    $admin->query("GRANT SELECT, REFERENCES ON `{$database}`.* TO `{$limitedUser}`@`%`");
    try {
        $failureBefore = wcrState($connection);
        wcrAssertResult([70, "{\"ok\":false,\"reason\":\"MIGRATION_FAILED\"}\n"], wcrRun(wcrEnvironment($database, $failurePrefix, $limitedUser, $limitedPassword)), 'Unexpected v5 DDL denial must be MIGRATION_FAILED, not conflict.');
        assertSameValue($failureBefore, wcrState($connection), 'Denied first v5 DDL leaves the prepared v1-v4 namespace unchanged.');
    } finally {
        $admin->query("DROP USER IF EXISTS `{$limitedUser}`@`%`");
    }

    $before26 = wcrState($connection);
    $invalidEnvironment = wcrEnvironment('must_not_be_opened', $prefix26, 'must_not_connect', 'must_not_connect');
    $invalidEnvironment['FMONITOR_DB_HOST'] = '127.0.0.1';
    $invalidEnvironment['FMONITOR_DB_PORT'] = '1';
    $invalidResult = wcrRun($invalidEnvironment);
    $after26 = wcrState($connection);
    assertSameValue(
        [
            'result' => ['exitCode' => 64, 'stdout' => "{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n", 'stderr' => ''],
            'stateUnchanged' => true,
        ],
        ['result' => $invalidResult, 'stateUnchanged' => $before26 === $after26],
        'An exact 26-byte composed prefix must be rejected even though its independently unreachable endpoint would expose any DB access as DATABASE_UNAVAILABLE.',
    );

    $directBoundaryConnection = wcrConnection($database);
    wcrAssertDirectFamilyBoundary($directBoundaryConnection);
    wcrAssertRuntimeOwnership();
    $connection->close();
} finally {
    if (isset($connection) && $connection instanceof mysqli) {
        try {
            $connection->close();
        } catch (Throwable) {
        }
    }
    $admin->query("DROP DATABASE IF EXISTS `{$database}`");
    $admin->close();
}

echo "PASS: WORKFORCE-CANONICAL-RUNNER-001 complete public-runner matrix.\n";
