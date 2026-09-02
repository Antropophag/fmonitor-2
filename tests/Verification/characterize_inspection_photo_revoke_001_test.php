<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001 v0.1, Gate 2 RED. */
const CIPRV_SPEC_SHA256 = '143665e8734fd86649622bc71c6da2331d2a4f3a5e2380a31f6eabae2729f154';
const CIPRV_TIMEOUT_SECONDS = 15.0;

function ciprvConfig(): array
{
    $config = [];
    foreach (['HOST'=>'127.0.0.1', 'PORT'=>'23306', 'NAME'=>'fmonitor2_test', 'USER'=>'fmonitor2_test', 'PASSWORD'=>'fmonitor2_test_local'] as $suffix=>$default) {
        $verify = getenv("FMONITOR_VERIFY_DB_$suffix");
        $test = getenv("FMONITOR_TEST_DB_$suffix");
        $config[$suffix] = $verify !== false && $verify !== '' ? $verify : ($test !== false && $test !== '' ? $test : $default);
    }
    return $config;
}

function ciprvDb(array $config): mysqli
{
    try {
        $db = new mysqli($config['HOST'], $config['USER'], $config['PASSWORD'], $config['NAME'], (int) $config['PORT']);
        $db->set_charset('utf8mb4');
        return $db;
    } catch (mysqli_sql_exception $exception) {
        throw new TestFailure('SETUP_FAILURE: disposable verification database is unavailable: ' . $exception->getMessage());
    }
}

function ciprvToken(string $token): string
{
    if (preg_match('/\A[a-f0-9]{12}\z/D', $token) !== 1) {
        throw new TestFailure('SETUP_FAILURE: unsafe photo-revoke verifier run token');
    }
    return $token;
}

/** @return array{status:int,stdout:string,stderr:string,timed_out:bool} */
function ciprvRun(string $root, array $environment, float $timeoutSeconds = CIPRV_TIMEOUT_SECONDS): array
{
    $process = proc_open(
        [PHP_BINARY, 'rapid-pilot/verify-checklist-photo-revoke.php'],
        [0=>['pipe', 'r'], 1=>['pipe', 'w'], 2=>['pipe', 'w']],
        $pipes,
        $root,
        $environment,
    );
    if (!is_resource($process)) {
        throw new TestFailure('SETUP_FAILURE: photo-revoke verifier process did not start');
    }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $stdout = $stderr = '';
    $deadline = microtime(true) + $timeoutSeconds;
    $timedOut = false;
    while (true) {
        $status = proc_get_status($process);
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        if (!$status['running']) {
            break;
        }
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) {
            $timedOut = true;
            proc_terminate($process, 15);
            usleep(100000);
            if (proc_get_status($process)['running']) {
                proc_terminate($process, 9);
            }
            break;
        }
        $read = [$pipes[1], $pipes[2]];
        $write = $except = null;
        $wait = min(0.2, $remaining);
        @stream_select($read, $write, $except, (int) $wait, (int) ($wait * 1000000));
    }
    stream_set_blocking($pipes[1], true);
    stream_set_blocking($pipes[2], true);
    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    return ['status'=>$timedOut ? 124 : $status, 'stdout'=>$stdout, 'stderr'=>$stderr, 'timed_out'=>$timedOut];
}

function ciprvOwnedTables(mysqli $db, string $token, array &$discovered): array
{
    $prefix = 'pr_' . ciprvToken($token) . '_';
    $escaped = str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $prefix);
    $statement = $db->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE CONCAT(?, '%') ESCAPE '\\\\' ORDER BY TABLE_NAME");
    $statement->bind_param('s', $escaped);
    $statement->execute();
    $tables = array_column($statement->get_result()->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
    foreach ($tables as $table) {
        if (!is_string($table) || preg_match('/\Apr_[a-f0-9]{12}_[A-Za-z0-9_]+\z/D', $table) !== 1) {
            throw new TestFailure('REGRESSION_FAILURE: unsafe owned photo-revoke table discovered');
        }
        $discovered[$table] = true;
    }
    return $tables;
}

function ciprvTableState(mysqli $db, string $table): array
{
    $quoted = '`' . str_replace('`', '``', $table) . '`';
    return [
        'definition'=>$db->query("SHOW CREATE TABLE $quoted")->fetch_row()[1],
        'rows'=>$db->query("SELECT decoy_key,decoy_value FROM $quoted ORDER BY decoy_key")->fetch_all(MYSQLI_ASSOC),
    ];
}

function ciprvArtifactState(string $root): array
{
    if (!is_dir($root)) {
        return [];
    }
    $state = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );
    foreach ($iterator as $entry) {
        $relative = substr($entry->getPathname(), strlen($root) + 1);
        $state[$relative] = $entry->isDir()
            ? ['type'=>'directory']
            : ['type'=>'file', 'sha256'=>hash_file('sha256', $entry->getPathname()), 'size'=>$entry->getSize()];
    }
    ksort($state);
    return $state;
}

function ciprvRemoveTree(string $root): void
{
    if (is_link($root) || is_file($root)) {
        unlink($root);
        return;
    }
    if (!is_dir($root)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $entry) {
        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }
    rmdir($root);
}

function ciprvFixture(): array
{
    $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    $common = [
        'case_id'=>'7b51f642-692f-4f4e-b911-a9826795f06b',
        'installation_case_db_id'=>71,
        'legacy_object_id'=>1701,
        'actor_id'=>901,
        'client_id'=>'22abca10-1773-48a0-baf3-5f9d55d411e7',
        'device_id'=>'8311cc91-a9ad-44ac-bfd7-0234ddaa8b09',
        'section'=>3,
    ];
    return [
        'png'=>['base64'=>$pngBase64, 'mime'=>'image/png', 'size'=>68, 'filename'=>'section-3-evidence.png', 'sha256'=>'431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460'],
        'common'=>$common,
        'envelopes'=>[
            'upload'=>['client_operation_id'=>'96f663c8-d5dc-4db2-8047-920875c769e4', 'kind'=>'photo_uploaded', 'base_revision'=>0, 'device_time'=>'2026-08-31T10:00:00+03:00', 'server_receipt_time'=>'2026-08-31 07:00:01.000000'],
            'revoke'=>['client_operation_id'=>'1494b408-d4da-40d2-8612-d871144b1302', 'kind'=>'photo_revoked', 'base_revision'=>1, 'device_time'=>'2026-08-31T10:01:00+03:00', 'server_receipt_time'=>'2026-08-31 07:01:01.000000'],
            'fresh_revoke'=>['client_operation_id'=>'6c0058e5-f093-4759-8561-d117f026a751', 'kind'=>'photo_revoked', 'base_revision'=>2, 'device_time'=>'2026-08-31T10:02:00+03:00', 'server_receipt_time'=>'2026-08-31 07:02:01.000000'],
            'identical_reupload'=>['client_operation_id'=>'8818b508-7ba4-4861-a2e8-ff48d8e17089', 'kind'=>'photo_uploaded', 'base_revision'=>2, 'device_time'=>'2026-08-31T10:03:00+03:00', 'server_receipt_time'=>'2026-08-31 07:03:01.000000'],
        ],
    ];
}

function ciprvAssertFixture(array $fixture): void
{
    $bytes = base64_decode($fixture['png']['base64'], true);
    assertSameValue(true, is_string($bytes), 'Approved PNG fixture must be strict base64');
    assertSameValue(68, strlen($bytes), 'Approved PNG fixture must contain exactly 68 bytes');
    assertSameValue('image/png', (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes), 'Approved PNG fixture must be detected as image/png');
    assertSameValue('431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460', hash('sha256', $bytes), 'Approved PNG fixture SHA-256 must remain literal');
    foreach (['case_id','client_id','device_id'] as $key) {
        assertSameValue(1, preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/D', $fixture['common'][$key]), "$key must be a literal UUIDv4");
    }
    foreach ($fixture['envelopes'] as $name=>$envelope) {
        assertSameValue(1, preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/D', $envelope['client_operation_id']), "$name operation id must be a literal UUIDv4");
    }
    assertSameValue(4, count(array_unique(array_column($fixture['envelopes'], 'client_operation_id'))), 'All logical command envelopes must own distinct operation UUIDs');
}

function ciprvReadAudit(string $path): array
{
    assertSameValue(true, is_file($path), 'Verifier must produce mandatory test-owned audit evidence; echo-only stdout is insufficient');
    $bytes = file_get_contents($path);
    assertSameValue(true, is_string($bytes), 'Verifier audit evidence must be readable');
    unlink($path);
    $audit = json_decode($bytes, true, 64, JSON_THROW_ON_ERROR);
    assertSameValue(true, is_array($audit), 'Verifier audit evidence must be a JSON object');
    return $audit;
}

function ciprvAssertAudit(array $audit, string $token, array $fixture): void
{
    assertSameValue(1, $audit['protocol_version'] ?? null, 'Audit protocol version must remain exact');
    assertSameValue($token, $audit['run_token'] ?? null, 'Audit must identify its isolated run');
    assertSameValue($fixture, $audit['fixture'] ?? null, 'Verifier must consume all test-owned literals unchanged');
    assertSameValue(5, $audit['accept_call_count'] ?? null, 'Four scenarios must prove all five public accept invocations, including upload then revoke');
    assertSameValue(5, $audit['projection_call_count'] ?? null, 'Accepted upload and every resulting scenario state must be observed through public projection');
    $photoId = $audit['scenarios']['upload_then_revoke']['upload_projection']['photos'][0]['id'] ?? null;
    assertSameValue(true, is_int($photoId) && $photoId > 0, 'Accepted upload projection must expose one positive integer photo id');
    $uploadPayload = [
        'sha256'=>$fixture['png']['sha256'],
        'mime'=>$fixture['png']['mime'],
        'size'=>$fixture['png']['size'],
        'originalName'=>$fixture['png']['filename'],
    ];
    $revokePayload = ['photoId'=>$photoId];
    $uploadPhoto = [
        'id'=>$photoId,
        'sectionId'=>$fixture['common']['section'],
        'clientOperationId'=>$fixture['envelopes']['upload']['client_operation_id'],
        'sha256'=>$fixture['png']['sha256'],
        'mime'=>$fixture['png']['mime'],
        'size'=>$fixture['png']['size'],
        'originalName'=>$fixture['png']['filename'],
        'actorUserId'=>$fixture['common']['actor_id'],
        'deviceTime'=>$fixture['envelopes']['upload']['device_time'],
        'serverReceivedAt'=>$fixture['envelopes']['upload']['server_receipt_time'],
    ];
    $photoRow = [
        'id'=>$photoId,
        'installation_case_id'=>$fixture['common']['installation_case_db_id'],
        'section_id'=>$fixture['common']['section'],
        'upload_operation_id'=>$fixture['envelopes']['upload']['client_operation_id'],
        'sha256'=>$fixture['png']['sha256'],
        'mime_type'=>$fixture['png']['mime'],
        'byte_size'=>$fixture['png']['size'],
        'original_name'=>$fixture['png']['filename'],
        'storage_name'=>$fixture['png']['sha256'] . '.bin',
        'actor_user_id'=>$fixture['common']['actor_id'],
        'device_time'=>$fixture['envelopes']['upload']['device_time'],
        'server_received_at'=>$fixture['envelopes']['upload']['server_receipt_time'],
        'revoked_at'=>$fixture['envelopes']['revoke']['server_receipt_time'],
    ];
    $history = [
        [
            'installation_case_id'=>$fixture['common']['installation_case_db_id'],
            'client_operation_id'=>$fixture['envelopes']['upload']['client_operation_id'],
            'device_installation_id'=>$fixture['common']['device_id'],
            'operation_type'=>'photo_uploaded',
            'section_id'=>$fixture['common']['section'],
            'actor_user_id'=>$fixture['common']['actor_id'],
            'device_time'=>$fixture['envelopes']['upload']['device_time'],
            'server_received_at'=>$fixture['envelopes']['upload']['server_receipt_time'],
            'base_revision'=>0,
            'accepted_revision'=>1,
            'payload'=>$uploadPayload,
        ],
        [
            'installation_case_id'=>$fixture['common']['installation_case_db_id'],
            'client_operation_id'=>$fixture['envelopes']['revoke']['client_operation_id'],
            'device_installation_id'=>$fixture['common']['device_id'],
            'operation_type'=>'photo_revoked',
            'section_id'=>$fixture['common']['section'],
            'actor_user_id'=>$fixture['common']['actor_id'],
            'device_time'=>$fixture['envelopes']['revoke']['device_time'],
            'server_received_at'=>$fixture['envelopes']['revoke']['server_receipt_time'],
            'base_revision'=>1,
            'accepted_revision'=>2,
            'payload'=>$revokePayload,
        ],
    ];
    assertSameValue([
        'upload_then_revoke'=>[
            'accept_calls'=>[
                ['kind'=>'photo_uploaded', 'operation_id'=>$fixture['envelopes']['upload']['client_operation_id'], 'result'=>['status'=>'accepted', 'revision'=>1]],
                ['kind'=>'photo_revoked', 'operation_id'=>$fixture['envelopes']['revoke']['client_operation_id'], 'result'=>['status'=>'accepted', 'revision'=>2]],
            ],
            'upload_projection'=>['revision'=>1, 'photos'=>[$uploadPhoto]],
            'projection'=>['revision'=>2, 'photos'=>[]],
            'sql'=>['revision'=>2, 'photos'=>[$photoRow], 'operations'=>$history],
            'blob'=>['count'=>1, 'sha256s'=>[$fixture['png']['sha256']]],
            'history'=>$history,
        ],
        'replay'=>[
            'accept_calls'=>[['kind'=>'photo_revoked', 'operation_id'=>$fixture['envelopes']['revoke']['client_operation_id'], 'result'=>['status'=>'duplicate', 'revision'=>2]]],
            'projection'=>['revision'=>2, 'photos'=>[]],
            'fingerprint_unchanged'=>true,
        ],
        'already_revoked'=>[
            'accept_calls'=>[['kind'=>'photo_revoked', 'operation_id'=>$fixture['envelopes']['fresh_revoke']['client_operation_id'], 'result'=>['status'=>'rejected']]],
            'projection'=>['revision'=>2, 'photos'=>[]],
            'fingerprint_unchanged'=>true,
        ],
        'identical_reupload'=>[
            'accept_calls'=>[['kind'=>'photo_uploaded', 'operation_id'=>$fixture['envelopes']['identical_reupload']['client_operation_id'], 'exception'=>['sqlstate'=>'23000', 'vendor_code'=>1062]]],
            'projection'=>['revision'=>2, 'photos'=>[]],
            'fingerprint_unchanged'=>true,
            'blob'=>['count'=>1, 'sha256s'=>[$fixture['png']['sha256']]],
        ],
    ], $audit['scenarios'] ?? null, 'Audit must prove exact public-seam results, projections, SQL/blob/history facts and SQL exception classification');
    $fingerprints = $audit['zero_mutation_fingerprints'] ?? null;
    assertSameValue(true, is_array($fingerprints), 'Audit must expose zero-mutation fingerprints');
    assertSameValue(['replay','already_revoked','identical_reupload'], array_keys($fingerprints), 'Audit fingerprint scenarios must remain exact and ordered');
    foreach ($fingerprints as $name=>$pair) {
        assertSameValue(['before','after'], array_keys($pair), "$name fingerprint must expose before and after");
        assertSameValue($pair['before'], $pair['after'], "$name must preserve the complete SQL/blob fingerprint");
        assertSameValue(['revision','operations','photos','blobs'], array_keys($pair['before']), "$name fingerprint must cover revision, operation payloads, photo facts and blobs");
    }
}

$root = dirname(__DIR__, 2);
$spec = $root . '/specs/CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001.md';
$verifier = $root . '/rapid-pilot/verify-checklist-photo-revoke.php';
$config = ciprvConfig();
$artifactRoot = $root . '/.local/test-artifacts/characterize-photo-revoke-' . bin2hex(random_bytes(8));
$tokens = ['first'=>bin2hex(random_bytes(6)), 'second'=>bin2hex(random_bytes(6)), 'sql-collision'=>bin2hex(random_bytes(6)), 'storage-collision'=>bin2hex(random_bytes(6)), 'failure'=>bin2hex(random_bytes(6)), 'unsafe'=>bin2hex(random_bytes(6)), 'unavailable-db'=>bin2hex(random_bytes(6))];
$ownedTokens = array_values($tokens);
$foreignToken = bin2hex(random_bytes(6));
$decoyTable = 'characterize_pr_decoy_' . bin2hex(random_bytes(8));
$foreignTable = 'pr_' . $foreignToken . '_foreign_decoy';
$foreignStorage = $artifactRoot . '/photo-revoke-' . $foreignToken;
$db = null;
$discovered = [];
$failureMessage = null;
$exitStatus = 0;

try {
    assertSameValue(true, is_file($spec), 'SETUP_FAILURE: approved executable specification is absent');
    assertSameValue(CIPRV_SPEC_SHA256, hash_file('sha256', $spec), 'Approved executable specification hash must remain pinned');
    $fixture = ciprvFixture();
    ciprvAssertFixture($fixture);
    foreach ($tokens as $token) {
        ciprvToken($token);
    }
    ciprvToken($foreignToken);
    if (!mkdir($artifactRoot, 0700, true) || !mkdir($foreignStorage, 0700)) {
        throw new TestFailure('SETUP_FAILURE: cannot create private artifact fixtures');
    }
    $decoyBytes = "CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001\0ambient\xffdecoy";
    if (file_put_contents($artifactRoot . '/ambient-decoy.bin', $decoyBytes, LOCK_EX) !== strlen($decoyBytes)
        || file_put_contents($foreignStorage . '/foreign.bin', $decoyBytes, LOCK_EX) !== strlen($decoyBytes)) {
        throw new TestFailure('SETUP_FAILURE: cannot create filesystem decoys');
    }
    $db = ciprvDb($config);
    foreach ([$decoyTable,$foreignTable] as $table) {
        $db->query("CREATE TABLE `$table`(decoy_key VARCHAR(80) PRIMARY KEY,decoy_value VARBINARY(255) NOT NULL)");
        $statement = $db->prepare("INSERT INTO `$table`(decoy_key,decoy_value) VALUES(?,?)");
        $key = 'ambient-state';
        $statement->bind_param('ss', $key, $decoyBytes);
        $statement->execute();
    }
    $beforeDb = ciprvTableState($db, $decoyTable);
    $beforeForeignDb = ciprvTableState($db, $foreignTable);
    $beforeArtifacts = ciprvArtifactState($artifactRoot);
    $beforeForeignStorage = ciprvArtifactState($foreignStorage);

    $environment = getenv();
    if (!is_array($environment)) {
        $environment = $_ENV;
    }
    foreach ($config as $suffix=>$value) {
        $environment["FMONITOR_VERIFY_DB_$suffix"] = (string) $value;
    }
    $environment['FMONITOR_PHOTO_REVOKE_VERIFY_ARTIFACT_ROOT'] = $artifactRoot;
    $environment['FMONITOR_PHOTO_REVOKE_VERIFY_FIXTURE_JSON'] = json_encode($fixture, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    $runs = [];
    foreach (['first','second'] as $name) {
        $token = $tokens[$name];
        $auditPath = $artifactRoot . '/audit-' . $token . '.json';
        assertSameValue([], ciprvOwnedTables($db, $token, $discovered), 'SETUP_FAILURE: owned SQL namespace must initially be empty');
        assertSameValue(false, file_exists($artifactRoot . '/photo-revoke-' . $token), 'SETUP_FAILURE: owned storage namespace must initially be absent');
        $environment['FMONITOR_PHOTO_REVOKE_VERIFY_RUN_TOKEN'] = $token;
        $environment['FMONITOR_PHOTO_REVOKE_VERIFY_AUDIT_FILE'] = $auditPath;
        unset($environment['FMONITOR_PHOTO_REVOKE_VERIFY_TEST_FAILURE']);
        $runs[$name] = ciprvRun($root, $environment);
        $evidence = json_encode($runs[$name], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        assertSameValue(false, $runs[$name]['timed_out'], "$name verifier run must finish within the bounded deadline; evidence=$evidence");
        assertSameValue(0, $runs[$name]['status'], "RED_ASSERTION: missing public photo-revoke verifier must become a successful $name run; evidence=$evidence");
        assertSameValue('', $runs[$name]['stderr'], "$name verifier run must keep stderr empty; evidence=$evidence");
        ciprvAssertAudit(ciprvReadAudit($auditPath), $token, $fixture);
        assertSameValue([], ciprvOwnedTables($db, $token, $discovered), "$name run must remove its exact owned SQL namespace");
        assertSameValue(false, file_exists($artifactRoot . '/photo-revoke-' . $token), "$name run must remove its exact owned storage namespace");
        assertSameValue($beforeDb, ciprvTableState($db, $decoyTable), "$name run must preserve ambient SQL");
        assertSameValue($beforeForeignDb, ciprvTableState($db, $foreignTable), "$name run must preserve foreign-token SQL");
        assertSameValue($beforeArtifacts, ciprvArtifactState($artifactRoot), "$name run must preserve ambient and foreign-token storage");
        assertSameValue($beforeForeignStorage, ciprvArtifactState($foreignStorage), "$name run must preserve foreign-token bytes");
    }
    $milestones =
        "PHOTO_REVOKE accepted revision=2 active=0 photo_rows=1 revoked_rows=1 operations=2 blobs=1\n"
        . "PHOTO_REVOKE replay duplicate revision=2 active=0 mutations=0\n"
        . "PHOTO_REVOKE already-revoked rejected revision=2 active=0 mutations=0\n"
        . "PHOTO_REVOKE identical-reupload sql-unique-violation revision=2 active=0 mutations=0 blobs=1\n";
    assertSameValue('60f1a4c65be2a4cedd05f170b243d34283560f480f37a2965fec7aeadd62b784', hash('sha256', $milestones), 'Specification transcript SHA-256 must be independently reproducible');
    $expectedStdout = $milestones . "CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001 transcript_sha256=60f1a4c65be2a4cedd05f170b243d34283560f480f37a2965fec7aeadd62b784\n";
    assertSameValue($expectedStdout, $runs['first']['stdout'], 'First run must emit the exact stable transcript');
    assertSameValue($runs['first']['stdout'], $runs['second']['stdout'], 'Distinct-token runs must emit byte-identical normalized stdout');

    $sqlToken = $tokens['sql-collision'];
    $sqlTable = 'pr_' . $sqlToken . '_occupied';
    $db->query("CREATE TABLE `$sqlTable`(decoy_key VARCHAR(80) PRIMARY KEY,decoy_value VARBINARY(255) NOT NULL)");
    $db->query("INSERT INTO `$sqlTable` VALUES('occupied',X'010203')");
    $discovered[$sqlTable] = true;
    $sqlBefore = ciprvTableState($db, $sqlTable);
    $environment['FMONITOR_PHOTO_REVOKE_VERIFY_RUN_TOKEN'] = $sqlToken;
    $environment['FMONITOR_PHOTO_REVOKE_VERIFY_AUDIT_FILE'] = $artifactRoot . '/audit-' . $sqlToken . '.json';
    $sqlCollision = ciprvRun($root, $environment);
    assertSameValue(2, $sqlCollision['status'], 'Occupied SQL namespace must be SETUP_FAILURE');
    assertSameValue('', $sqlCollision['stdout'], 'Occupied SQL namespace must emit no success stdout');
    assertSameValue(1, preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D', $sqlCollision['stderr']), 'Occupied SQL namespace must emit one setup line');
    assertSameValue($sqlBefore, ciprvTableState($db, $sqlTable), 'Occupied SQL namespace must remain unchanged');
    assertSameValue(false, file_exists($environment['FMONITOR_PHOTO_REVOKE_VERIFY_AUDIT_FILE']), 'SQL collision must not write audit evidence');
    $db->query("DROP TABLE `$sqlTable`");

    $storageToken = $tokens['storage-collision'];
    $occupiedStorage = $artifactRoot . '/photo-revoke-' . $storageToken;
    mkdir($occupiedStorage, 0700);
    file_put_contents($occupiedStorage . '/occupied.bin', "occupied\0storage", LOCK_EX);
    $storageBefore = ciprvArtifactState($occupiedStorage);
    $environment['FMONITOR_PHOTO_REVOKE_VERIFY_RUN_TOKEN'] = $storageToken;
    $environment['FMONITOR_PHOTO_REVOKE_VERIFY_AUDIT_FILE'] = $artifactRoot . '/audit-' . $storageToken . '.json';
    $storageCollision = ciprvRun($root, $environment);
    assertSameValue(2, $storageCollision['status'], 'Occupied storage namespace must be SETUP_FAILURE');
    assertSameValue('', $storageCollision['stdout'], 'Occupied storage namespace must emit no success stdout');
    assertSameValue(1, preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D', $storageCollision['stderr']), 'Occupied storage namespace must emit one setup line');
    assertSameValue($storageBefore, ciprvArtifactState($occupiedStorage), 'Occupied storage namespace must remain unchanged');
    assertSameValue([], ciprvOwnedTables($db, $storageToken, $discovered), 'Storage collision must create no SQL fixture');
    ciprvRemoveTree($occupiedStorage);

    foreach (['setup_after_mutation'=>2, 'regression_after_mutation'=>1] as $mode=>$expectedStatus) {
        $failureToken = bin2hex(random_bytes(6));
        $ownedTokens[] = $failureToken;
        $environment['FMONITOR_PHOTO_REVOKE_VERIFY_RUN_TOKEN'] = $failureToken;
        $environment['FMONITOR_PHOTO_REVOKE_VERIFY_AUDIT_FILE'] = $artifactRoot . '/audit-' . $failureToken . '.json';
        $environment['FMONITOR_PHOTO_REVOKE_VERIFY_TEST_FAILURE'] = $mode;
        $failed = ciprvRun($root, $environment);
        assertSameValue(false, $failed['timed_out'], "$mode must finish within the bounded deadline");
        assertSameValue($expectedStatus, $failed['status'], "$mode must retain its failure classification");
        assertSameValue('', $failed['stdout'], "$mode must emit no success stdout");
        $classification = $expectedStatus === 2 ? 'SETUP_FAILURE' : 'REGRESSION_FAILURE';
        assertSameValue(1, preg_match("/\\A$classification: [^\\r\\n]+\\n\\z/D", $failed['stderr']), "$mode must emit one classified line");
        assertSameValue([], ciprvOwnedTables($db, $failureToken, $discovered), "$mode must clean exact owned SQL state");
        assertSameValue(false, file_exists($artifactRoot . '/photo-revoke-' . $failureToken), "$mode must clean exact owned storage state");
        if (is_file($environment['FMONITOR_PHOTO_REVOKE_VERIFY_AUDIT_FILE'])) {
            unlink($environment['FMONITOR_PHOTO_REVOKE_VERIFY_AUDIT_FILE']);
        }
        assertSameValue($beforeDb, ciprvTableState($db, $decoyTable), "$mode must preserve ambient SQL");
        assertSameValue($beforeArtifacts, ciprvArtifactState($artifactRoot), "$mode must preserve ambient storage");
    }
    unset($environment['FMONITOR_PHOTO_REVOKE_VERIFY_TEST_FAILURE']);

    $missing = $environment;
    unset($missing['FMONITOR_PHOTO_REVOKE_VERIFY_RUN_TOKEN']);
    $missingResult = ciprvRun($root, $missing);
    assertSameValue(2, $missingResult['status'], 'Missing token must be SETUP_FAILURE');
    assertSameValue('', $missingResult['stdout'], 'Missing token must emit no stdout');
    assertSameValue(1, preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D', $missingResult['stderr']), 'Missing token must emit one setup line');

    $unavailable = $environment;
    $unavailable['FMONITOR_PHOTO_REVOKE_VERIFY_RUN_TOKEN'] = $tokens['unavailable-db'];
    $unavailable['FMONITOR_VERIFY_DB_HOST'] = '127.0.0.1';
    $unavailable['FMONITOR_VERIFY_DB_PORT'] = '1';
    $unavailableResult = ciprvRun($root, $unavailable);
    assertSameValue(false, $unavailableResult['timed_out'], 'Unavailable database probe must finish within the bounded deadline');
    assertSameValue(2, $unavailableResult['status'], 'Unavailable database must be SETUP_FAILURE');
    assertSameValue('', $unavailableResult['stdout'], 'Unavailable database must emit no stdout');
    assertSameValue(1, preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D', $unavailableResult['stderr']), 'Unavailable database must emit one setup line');

    $invalid = $environment;
    $invalid['FMONITOR_PHOTO_REVOKE_VERIFY_RUN_TOKEN'] = 'ABCDEF012345';
    $invalidResult = ciprvRun($root, $invalid);
    assertSameValue(2, $invalidResult['status'], 'Invalid token must be SETUP_FAILURE');
    assertSameValue('', $invalidResult['stdout'], 'Invalid token must emit no stdout');
    assertSameValue(1, preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D', $invalidResult['stderr']), 'Invalid token must emit one setup line');
    $unsafe = $environment;
    $unsafe['FMONITOR_PHOTO_REVOKE_VERIFY_RUN_TOKEN'] = $tokens['unsafe'];
    $unsafe['FMONITOR_PHOTO_REVOKE_VERIFY_ARTIFACT_ROOT'] = '/tmp';
    $unsafeResult = ciprvRun($root, $unsafe);
    assertSameValue(2, $unsafeResult['status'], 'Unsafe /tmp artifact root must be SETUP_FAILURE');
    assertSameValue('', $unsafeResult['stdout'], 'Unsafe root must emit no stdout');
    assertSameValue(1, preg_match('/\ASETUP_FAILURE: [^\r\n]+\n\z/D', $unsafeResult['stderr']), 'Unsafe root must emit one setup line');
    assertSameValue(true, is_file($verifier), 'Implemented verifier must exist before Gate 4 is GREEN');
    assertSameValue($beforeDb, ciprvTableState($db, $decoyTable), 'All probes must preserve ambient SQL');
    assertSameValue($beforeForeignDb, ciprvTableState($db, $foreignTable), 'All probes must preserve foreign-token SQL');
    assertSameValue($beforeArtifacts, ciprvArtifactState($artifactRoot), 'All probes must preserve ambient and foreign-token storage');
    echo "ok - CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001 oracle is deterministic, audited, isolated, and correctly classified\n";
} catch (TestFailure $exception) {
    $failureMessage = $exception->getMessage();
    $exitStatus = str_starts_with($failureMessage, 'SETUP_FAILURE:') ? 2 : 1;
} catch (Throwable $exception) {
    $failureMessage = 'REGRESSION_FAILURE: ' . $exception->getMessage();
    $exitStatus = 1;
} finally {
    if ($db instanceof mysqli) {
        foreach ($ownedTokens as $token) {
            try {
                foreach (ciprvOwnedTables($db, $token, $discovered) as $table) {
                    $discovered[$table] = true;
                }
            } catch (Throwable) {
            }
        }
        foreach (array_keys($discovered) as $table) {
            if (is_string($table) && preg_match('/\Apr_[a-f0-9]{12}_[A-Za-z0-9_]+\z/D', $table) === 1) {
                try {
                    $db->query("DROP TABLE IF EXISTS `$table`");
                } catch (Throwable) {
                }
            }
        }
        try {
            $db->query("DROP TABLE IF EXISTS `$decoyTable`");
            $db->query("DROP TABLE IF EXISTS `$foreignTable`");
        } catch (Throwable) {
        }
        $db->close();
    }
    foreach ($tokens as $token) {
        ciprvRemoveTree($artifactRoot . '/photo-revoke-' . $token);
    }
    ciprvRemoveTree($artifactRoot);
}

if ($failureMessage !== null) {
    fwrite(STDERR, $failureMessage . "\n");
    exit($exitStatus);
}
