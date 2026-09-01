<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** HARNESS-OTIZ-CANONICAL-COMPAT-001 v0.1 */

function hoccConfig(): array
{
    $config = [];
    foreach (['HOST'=>'127.0.0.1','PORT'=>'23306','NAME'=>'fmonitor2_test','USER'=>'fmonitor2_test','PASSWORD'=>'fmonitor2_test_local'] as $suffix => $default) {
        $verify = getenv("FMONITOR_VERIFY_DB_$suffix");
        $test = getenv("FMONITOR_TEST_DB_$suffix");
        $config[$suffix] = $verify !== false && $verify !== '' ? $verify : ($test !== false && $test !== '' ? $test : $default);
    }
    return $config;
}

function hoccRun(array $command, string $root, array $environment): array
{
    $process = proc_open(
        $command,
        [0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w']],
        $pipes,
        $root,
        $environment,
    );
    if (!is_resource($process)) {
        throw new TestFailure('SETUP_FAILURE: subprocess did not start: ' . implode(' ', $command));
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['status'=>proc_close($process), 'stdout'=>$stdout, 'stderr'=>$stderr];
}

function hoccDb(array $config): mysqli
{
    try {
        $db = new mysqli($config['HOST'], $config['USER'], $config['PASSWORD'], $config['NAME'], (int) $config['PORT']);
        $db->set_charset('utf8mb4');
        return $db;
    } catch (mysqli_sql_exception $exception) {
        throw new TestFailure('SETUP_FAILURE: disposable verification database is unavailable: ' . $exception->getMessage());
    }
}

function hoccCanonicalState(mysqli $db, array $tables): array
{
    $state = [];
    foreach ($tables as $table) {
        $quoted = '`' . str_replace('`', '``', $table) . '`';
        try {
            $definition = $db->query("SHOW CREATE TABLE $quoted")->fetch_row()[1];
            $columnRows = $db->query("SHOW COLUMNS FROM $quoted")->fetch_all(MYSQLI_ASSOC);
            $columns = array_column($columnRows, 'Field');
            $order = implode(',', array_map(static fn (string $column): string => '`' . str_replace('`', '``', $column) . '`', $columns));
            $rows = $db->query("SELECT * FROM $quoted" . ($order === '' ? '' : " ORDER BY $order"))->fetch_all(MYSQLI_ASSOC);
            $state[$table] = ['definition'=>$definition, 'rows'=>$rows];
        } catch (mysqli_sql_exception $exception) {
            throw new TestFailure("SETUP_FAILURE: canonical prerequisite `$table` is missing or unreadable: {$exception->getMessage()}");
        }
    }
    return $state;
}

function hoccAutoIncrementState(mysqli $db, array $tables): array
{
    $state = [];
    foreach ($tables as $table) {
        $escaped = $db->real_escape_string($table);
        $row = $db->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$escaped'")->fetch_assoc();
        if ($row === null) {
            throw new TestFailure("SETUP_FAILURE: canonical prerequisite `$table` is missing while recording AUTO_INCREMENT state");
        }
        $state[$table] = $row['AUTO_INCREMENT'] === null ? 1 : (int) $row['AUTO_INCREMENT'];
    }
    return $state;
}

function hoccRestoreAutoIncrementState(mysqli $db, array $state): void
{
    foreach ($state as $table => $nextId) {
        $quoted = '`' . str_replace('`', '``', $table) . '`';
        $db->query("ALTER TABLE $quoted AUTO_INCREMENT=" . (int) $nextId);
    }
}

function hoccPrivateTables(mysqli $db): array
{
    $result = $db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'otiz\\_verify\\_%' ESCAPE '\\\\' ORDER BY TABLE_NAME");
    return array_column($result->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
}

function hoccOwnedNoncanonicalTables(mysqli $db): array
{
    $result = $db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'harness\\_otiz\\_isolation\\_decoy\\_%' ESCAPE '\\\\' ORDER BY TABLE_NAME");
    return array_column($result->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
}

function hoccDropOwnedTables(mysqli $db, array $tables): void
{
    foreach ($tables as $table) {
        $db->query('DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . '`');
    }
}

function hoccMigrationResult(array $migration): array
{
    $lines = preg_split('/\R/', trim($migration['stdout']));
    $line = is_array($lines) && $lines !== [] ? end($lines) : false;
    try {
        $result = $line === false ? null : json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new TestFailure('SETUP_FAILURE: make migrate did not emit JSON: ' . $exception->getMessage());
    }
    if (!is_array($result)) {
        throw new TestFailure('SETUP_FAILURE: make migrate did not emit a JSON object');
    }
    return $result;
}

/** @return array{caseId: int, orderId: int, installerId: int, taskId: int, eventId: int, userId: int} */
function hoccSentinelIds(): array
{
    return [
        'caseId'=>900000000000000001,
        'orderId'=>900000000000000002,
        'installerId'=>900000000000000003,
        'taskId'=>900000000000000004,
        'eventId'=>900000000000000005,
        'userId'=>900000000000000006,
    ];
}

/** @param array{caseId: int, orderId: int, installerId: int, taskId: int, eventId: int, userId: int} $ids */
function hoccInstallSentinels(mysqli $db, array $ids): void
{
    foreach ([
        ['fm2_installation_cases', 'id', $ids['caseId']],
        ['fm2_assignment_orders', 'id', $ids['orderId']],
        ['fm2_workforce_catalog', 'installer_tab_id', $ids['installerId']],
        ['fm2_process_tasks', 'id', $ids['taskId']],
        ['fm2_process_events', 'id', $ids['eventId']],
        ['fm2_process_user_capabilities', 'user_id', $ids['userId']],
    ] as [$table, $column, $id]) {
        $count = (int) $db->query("SELECT COUNT(*) n FROM `$table` WHERE `$column`=$id")->fetch_assoc()['n'];
        if ($count !== 0) {
            throw new TestFailure("SETUP_FAILURE: deterministic sentinel key already exists in `$table`");
        }
    }

    $db->query("INSERT INTO fm2_installation_cases(id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version) VALUES ({$ids['caseId']},{$ids['caseId']},'working','2026-08-01','2026-08-01T08:00:00+03:00',{$ids['userId']},'2026-08-01T07:00:00+03:00','2026-08-01T08:00:00+03:00',7)");
    $db->query("INSERT INTO fm2_workforce_catalog(installer_tab_id,fio,position,employment_status,employed_from,employed_to,workforce_source,workforce_source_updated_at) VALUES ({$ids['installerId']},'Canonical Sentinel Installer','installer','employed','2020-01-01',NULL,'canonical-test','2026-08-01T07:00:00+03:00')");
    $db->query("INSERT INTO fm2_process_user_capabilities(user_id,capability,position_snapshot) VALUES ({$ids['userId']},'assignment_order.prepare',NULL)");
    $db->query("INSERT INTO fm2_assignment_orders(id,installation_case_id,version_no,kind,status,order_date,registration_number,registered_at,registration_actor_type,registration_actor_id,registration_source,external_registration_id,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,previous_assignment_order_id,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot,pto_act_date_snapshot,prepared_at,prepared_by_user_id) VALUES ({$ids['orderId']},{$ids['caseId']},1,'initial','registered','2026-08-01','SENTINEL-ORDER','2026-08-01T08:00:00+03:00','user','{$ids['userId']}','canonical-test',NULL,{$ids['userId']},'Canonical Sentinel Engineer','engineer','brigade',NULL,'Canonical sentinel address','1','SENTINEL-OBJECT','2026-08-01','2026-12-31',NULL,'2026-08-01T07:30:00+03:00',{$ids['userId']})");
    $db->query("INSERT INTO fm2_order_installers(assignment_order_id,installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot,employed_from_snapshot,employed_to_snapshot,workforce_source_snapshot,workforce_source_updated_at_snapshot,valid_from,valid_to,change_action) VALUES ({$ids['orderId']},{$ids['installerId']},'Canonical Sentinel Installer','installer','employed','2020-01-01',NULL,'canonical-test','2026-08-01T07:00:00+03:00','2026-08-01',NULL,'assign')");
    $db->query("INSERT INTO fm2_order_artifacts(assignment_order_id,artifact_type,filename,media_type,byte_size,sha256) VALUES ({$ids['orderId']},'signed_original','sentinel.pdf','application/pdf',8,'" . hash('sha256', 'sentinel') . "')");
    $db->query("INSERT INTO fm2_process_tasks(id,installation_case_id,task_type,assignee_user_id,assignee_role,due_date,status,completed_at,completed_by_user_id,created_at) VALUES ({$ids['taskId']},{$ids['caseId']},'canonical_sentinel',{$ids['userId']},'otiz','2026-09-01','open',NULL,NULL,'2026-08-01T08:00:00+03:00')");
    $db->query("INSERT INTO fm2_process_events(id,installation_case_id,event_type,occurred_at,actor_user_id,payload_json) VALUES ({$ids['eventId']},{$ids['caseId']},'canonical_sentinel','2026-08-01T08:00:00+03:00',{$ids['userId']},'{\"source\":\"HARNESS-OTIZ-CANONICAL-COMPAT-001\"}')");
}

/** @param array{caseId: int, orderId: int, installerId: int, taskId: int, eventId: int, userId: int} $ids */
function hoccRemoveSentinels(mysqli $db, array $ids): void
{
    $db->query("DELETE FROM fm2_process_events WHERE id={$ids['eventId']}");
    $db->query("DELETE FROM fm2_process_tasks WHERE id={$ids['taskId']}");
    $db->query("DELETE FROM fm2_order_artifacts WHERE assignment_order_id={$ids['orderId']} AND artifact_type='signed_original'");
    $db->query("DELETE FROM fm2_order_installers WHERE assignment_order_id={$ids['orderId']} AND installer_tab_id={$ids['installerId']}");
    $db->query("DELETE FROM fm2_assignment_orders WHERE id={$ids['orderId']}");
    $db->query("DELETE FROM fm2_process_user_capabilities WHERE user_id={$ids['userId']} AND capability='assignment_order.prepare'");
    $db->query("DELETE FROM fm2_workforce_catalog WHERE installer_tab_id={$ids['installerId']}");
    $db->query("DELETE FROM fm2_installation_cases WHERE id={$ids['caseId']}");
}

$root = dirname(__DIR__, 2);
$config = hoccConfig();
$environment = getenv();
if (!is_array($environment)) {
    $environment = $_ENV;
}
foreach ($config as $suffix => $value) {
    $environment["FMONITOR_TEST_DB_$suffix"] = (string) $value;
    $environment["FMONITOR_VERIFY_DB_$suffix"] = (string) $value;
}

$db = hoccDb($config);
hoccDropOwnedTables($db, array_merge(hoccPrivateTables($db), hoccOwnedNoncanonicalTables($db)));
$db->close();

$migration = hoccRun(['make', '--no-print-directory', 'migrate'], $root, $environment);
$migrationEvidence = json_encode($migration, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
if ($migration['status'] !== 0) {
    throw new TestFailure("SETUP_FAILURE: public make migrate did not establish canonical v1-v8; evidence=$migrationEvidence");
}
$migrationResult = hoccMigrationResult($migration);
$appliedVersions = $migrationResult['appliedVersions'] ?? null;
$validAppliedVersions = is_array($appliedVersions)
    && array_values(array_unique($appliedVersions, SORT_REGULAR)) === array_values($appliedVersions)
    && array_values(array_filter($appliedVersions, static fn (mixed $version): bool => !is_int($version) || $version < 1 || $version > 8)) === [];
assertSameValue(true, ($migrationResult['ok'] ?? null) === true, "SETUP_FAILURE: migration JSON must report ok=true; evidence=$migrationEvidence");
assertSameValue(8, $migrationResult['schemaVersion'] ?? null, "SETUP_FAILURE: migration JSON must report schemaVersion=8; evidence=$migrationEvidence");
assertSameValue(true, $validAppliedVersions, "SETUP_FAILURE: migration JSON appliedVersions must be [] or a unique subset of [1,2,3,4,5,6,7,8]; evidence=$migrationEvidence");

$canonicalTables = [
    'fm2_installation_cases',
    'fm2_assignment_orders',
    'fm2_checklist_template_associations',
    'fm2_checklist_template_snapshots',
    'fm2_checklist_revisions',
    'fm2_checklist_operations',
    'fm2_checklist_operation_installers',
    'fm2_checklist_photos',
    'fm2_order_installers',
    'fm2_order_artifacts',
    'fm2_process_tasks',
    'fm2_process_events',
    'fm2_workforce_catalog',
    'fm2_workforce_observations',
    'fm2_workforce_sync_runs',
    'fm2_workforce_sync_metadata',
    'fm2_process_user_capabilities',
];
$db = hoccDb($config);
$sentinelIds = hoccSentinelIds();
$original = [];
$originalAutoIncrement = [];

try {
    assertSameValue([], hoccPrivateTables($db), 'SETUP_FAILURE: private OTIZ namespace must be clean before canonical compatibility verification');
    $original = hoccCanonicalState($db, $canonicalTables);
    $originalAutoIncrement = hoccAutoIncrementState($db, ['fm2_installation_cases','fm2_assignment_orders','fm2_process_tasks','fm2_process_events']);
    hoccInstallSentinels($db, $sentinelIds);
    $before = hoccCanonicalState($db, $canonicalTables);

    $first = hoccRun([PHP_BINARY, 'tests/Verification/harness_otiz_isolation_001_test.php'], $root, $environment);
    $afterFirst = hoccCanonicalState($db, $canonicalTables);
    $leaksAfterFirst = hoccPrivateTables($db);
    $second = hoccRun([PHP_BINARY, 'tests/Verification/harness_otiz_isolation_001_test.php'], $root, $environment);
    $afterSecond = hoccCanonicalState($db, $canonicalTables);
    $leaksAfterSecond = hoccPrivateTables($db);
    $evidence = json_encode([
        'first'=>$first,
        'second'=>$second,
        'beforeSha256'=>hash('sha256', serialize($before)),
        'afterFirstSha256'=>hash('sha256', serialize($afterFirst)),
        'afterSecondSha256'=>hash('sha256', serialize($afterSecond)),
        'leaksAfterFirst'=>$leaksAfterFirst,
        'leaksAfterSecond'=>$leaksAfterSecond,
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    assertSameValue(0, $first['status'], "RED_ASSERTION: OTIZ isolation public seam must coexist with pre-existing canonical v1-v8 tables on its first invocation; evidence=$evidence");
    assertSameValue(0, $second['status'], "RED_ASSERTION: OTIZ isolation public seam must coexist with pre-existing canonical v1-v8 tables on its repeated invocation; evidence=$evidence");
    assertSameValue('', $first['stderr'], "OTIZ canonical compatibility first invocation must not emit setup or regression errors; evidence=$evidence");
    assertSameValue('', $second['stderr'], "OTIZ canonical compatibility repeated invocation must not emit setup or regression errors; evidence=$evidence");
    assertSameValue($first['stdout'], $second['stdout'], "Both canonical compatibility invocations must emit an identical stable transcript; evidence=$evidence");
    assertSameValue(1, preg_match('/\\Aok - HARNESS-OTIZ-ISOLATION-001 verifier runs twice with transcript sha256=[a-f0-9]{64}\\nok - no leaked private tables and ambient decoys sha256=[a-f0-9]{64}\\n\\z/D', $first['stdout']), "Existing OTIZ financial characterization transcript must remain complete; evidence=$evidence");
    assertSameValue($before, $afterFirst, "First OTIZ harness invocation must preserve every canonical definition and pre-existing row byte-for-byte; evidence=$evidence");
    assertSameValue($before, $afterSecond, "Repeated OTIZ harness invocation must preserve every canonical definition and pre-existing row byte-for-byte; evidence=$evidence");
    assertSameValue([], $leaksAfterFirst, "First OTIZ harness invocation must remove all private tables; evidence=$evidence");
    assertSameValue([], $leaksAfterSecond, "Repeated OTIZ harness invocation must remove all private tables; evidence=$evidence");

    $failureEnvironment = $environment;
    $failureEnvironment['FMONITOR_HARNESS_OTIZ_FAIL_AFTER_FIXTURES'] = '1';
    $injectedFailure = hoccRun([PHP_BINARY, 'tests/Verification/harness_otiz_isolation_001_test.php'], $root, $failureEnvironment);
    $afterInjectedFailure = hoccCanonicalState($db, $canonicalTables);
    $failurePrivateLeaks = hoccPrivateTables($db);
    $failureOwnedLeaks = hoccOwnedNoncanonicalTables($db);
    $failureEvidence = json_encode([
        'result'=>$injectedFailure,
        'beforeSha256'=>hash('sha256', serialize($before)),
        'afterSha256'=>hash('sha256', serialize($afterInjectedFailure)),
        'privateLeaks'=>$failurePrivateLeaks,
        'ownedNoncanonicalLeaks'=>$failureOwnedLeaks,
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    assertSameValue(true, $injectedFailure['status'] !== 0, "RED_ASSERTION: controlled post-fixture OTIZ harness failure must exit nonzero; evidence=$failureEvidence");
    assertSameValue('', $injectedFailure['stdout'], "Controlled post-fixture OTIZ harness failure must not emit a success transcript; evidence=$failureEvidence");
    assertSameValue("REGRESSION_FAILURE: injected after fixtures\n", $injectedFailure['stderr'], "Controlled post-fixture OTIZ harness failure must emit one stable explicit verdict; evidence=$failureEvidence");
    assertSameValue($before, $afterInjectedFailure, "Controlled post-fixture failure must preserve every canonical table definition and sentinel/original row byte-for-byte; evidence=$failureEvidence");
    assertSameValue([], $failurePrivateLeaks, "Controlled post-fixture failure must remove all private OTIZ tables; evidence=$failureEvidence");
    assertSameValue([], $failureOwnedLeaks, "Controlled post-fixture failure must remove every harness-owned noncanonical artifact; evidence=$failureEvidence");

    echo 'ok - HARNESS-OTIZ-CANONICAL-COMPAT-001 preserves canonical v1-v8 across repeated isolated OTIZ characterization', "\n";
} finally {
    hoccDropOwnedTables($db, array_merge(hoccPrivateTables($db), hoccOwnedNoncanonicalTables($db)));
    hoccRemoveSentinels($db, $sentinelIds);
    hoccRestoreAutoIncrementState($db, $originalAutoIncrement);
    if ($original !== []) {
        assertSameValue($original, hoccCanonicalState($db, $canonicalTables), 'CLEANUP_FAILURE: canonical pre-existing state was not restored after sentinel cleanup');
    }
    $db->close();
}
