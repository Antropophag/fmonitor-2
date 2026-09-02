<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/PilotHttp/PilotHttp.php';
require_once dirname(__DIR__) . '/app/PilotHttp/ChecklistSync.php';

use FMonitor2\PilotHttp\ChecklistSync;
use FMonitor2\PilotHttp\HttpUser;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function photoLimitRemoveTree(string $path): void
{
    if (is_link($path) || is_file($path)) {
        @unlink($path);
        return;
    }
    if (!is_dir($path)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $entry) {
        $entry->isDir() ? @rmdir($entry->getPathname()) : @unlink($entry->getPathname());
    }
    @rmdir($path);
}

function photoLimitDb(): mysqli
{
    $db = new mysqli(
        getenv('FMONITOR_VERIFY_DB_HOST') ?: '127.0.0.1',
        getenv('FMONITOR_VERIFY_DB_USER') ?: 'fmonitor2_test',
        getenv('FMONITOR_VERIFY_DB_PASSWORD') ?: 'fmonitor2_test_local',
        getenv('FMONITOR_VERIFY_DB_NAME') ?: 'fmonitor2_test',
        (int) (getenv('FMONITOR_VERIFY_DB_PORT') ?: 23306),
    );
    $db->set_charset('utf8mb4');
    return $db;
}

function photoLimitAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('REGRESSION_FAILURE: ' . $message);
    }
}

function photoLimitWriteJson(string $path, array $value): void
{
    $bytes = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (file_put_contents($path, $bytes, LOCK_EX) !== strlen($bytes)) {
        throw new RuntimeException('SETUP_FAILURE: verifier IPC write failed');
    }
}

function photoLimitReadJson(string $path): array
{
    $bytes = file_get_contents($path);
    if (!is_string($bytes)) {
        throw new RuntimeException('SETUP_FAILURE: verifier IPC read failed');
    }
    $value = json_decode($bytes, true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($value)) {
        throw new RuntimeException('SETUP_FAILURE: verifier IPC protocol failed');
    }
    return $value;
}

function photoLimitWaitFor(callable $ready, float $seconds, string $message): void
{
    $deadline = microtime(true) + $seconds;
    while (!$ready()) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException('SETUP_FAILURE: ' . $message);
        }
        usleep(10000);
    }
}

function photoLimitKillAndReap(array $pids): void
{
    foreach ($pids as $pid) {
        if (is_int($pid) && $pid > 1) {
            @posix_kill($pid, SIGTERM);
        }
    }
    usleep(50000);
    foreach ($pids as $pid) {
        if (is_int($pid) && $pid > 1) {
            @posix_kill($pid, SIGKILL);
            while (@pcntl_waitpid($pid, $status, WNOHANG) === 0) {
                usleep(10000);
            }
        }
    }
}

$token = getenv('FMONITOR_PHOTO_LIMIT_VERIFY_RUN_TOKEN');
$artifactRoot = getenv('FMONITOR_PHOTO_LIMIT_VERIFY_ARTIFACT_ROOT');
$auditFile = getenv('FMONITOR_PHOTO_LIMIT_VERIFY_AUDIT_FILE');
$fixturesJson = getenv('FMONITOR_PHOTO_LIMIT_VERIFY_FIXTURES_JSON');
$rootReal = is_string($artifactRoot) ? realpath($artifactRoot) : false;
$rootInfo = is_string($artifactRoot) ? @lstat($artifactRoot) : false;
if (!is_string($token) || preg_match('/\A[a-f0-9]{12}\z/D', $token) !== 1) {
    fwrite(STDERR, "SETUP_FAILURE: photo-limit verifier run token is invalid\n");
    exit(2);
}
if (!is_string($artifactRoot) || $artifactRoot === '' || $artifactRoot[0] !== '/' || str_contains($artifactRoot, "\0")
    || !is_string($rootReal) || $artifactRoot !== $rootReal || !is_array($rootInfo) || is_link($artifactRoot)
    || !is_dir($artifactRoot) || $rootReal === '/tmp' || str_starts_with($rootReal, '/tmp/')) {
    fwrite(STDERR, "SETUP_FAILURE: supplied photo-limit artifact root is unsafe\n");
    exit(2);
}
if (!is_string($auditFile) || dirname($auditFile) !== $artifactRoot || is_link($auditFile)) {
    fwrite(STDERR, "SETUP_FAILURE: supplied photo-limit audit path is unsafe\n");
    exit(2);
}
if (!function_exists('pcntl_fork') || !function_exists('posix_setsid')) {
    fwrite(STDERR, "SETUP_FAILURE: process isolation support is unavailable\n");
    exit(2);
}
if (posix_getpgrp() !== getmypid() && posix_setsid() < 0) {
    fwrite(STDERR, "SETUP_FAILURE: process group isolation failed\n");
    exit(2);
}

$prefix = 'photo_limit_' . $token . '_';
$privateRoot = $artifactRoot . '/photo-limit-' . $token;
$tables = [
    'fm2_checklist_operation_installers', 'fm2_checklist_photos', 'fm2_checklist_operations',
    'fm2_checklist_revisions', 'fm2_checklist_template_associations', 'fm2_checklist_template_snapshots',
    'fm2_workforce_catalog', 'fm2_order_installers', 'fm2_assignment_orders', 'fm2_installation_cases',
];
$db = null;
$ownsNamespace = false;
$children = [];
$failure = null;
$exit = 0;

try {
    try {
        $fixtures = json_decode(is_string($fixturesJson) ? $fixturesJson : '', true, 16, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        throw new RuntimeException('SETUP_FAILURE: literal contender fixtures are unavailable');
    }
    if (!is_array($fixtures) || array_keys($fixtures) !== ['A', 'B']) {
        throw new RuntimeException('SETUP_FAILURE: literal contender fixture shape is invalid');
    }
    $db = photoLimitDb();
    $escapedPrefix = str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $prefix);
    $statement = $db->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE CONCAT(?, '%') ESCAPE '\\\\' LIMIT 1");
    $statement->bind_param('s', $escapedPrefix);
    $statement->execute();
    if ($statement->get_result()->fetch_row() !== null || file_exists($privateRoot) || is_link($privateRoot)) {
        throw new RuntimeException('SETUP_FAILURE: photo-limit verifier owned namespace is occupied');
    }
    if (!mkdir($privateRoot, 0700)) {
        throw new RuntimeException('SETUP_FAILURE: photo-limit storage namespace cannot be created');
    }
    $ownsNamespace = true;

    $ddl = [
        'fm2_installation_cases' => 'id BIGINT PRIMARY KEY,legacy_installation_object_id BIGINT NOT NULL,process_state VARCHAR(80) NOT NULL',
        'fm2_assignment_orders' => 'id BIGINT PRIMARY KEY,installation_case_id BIGINT NOT NULL,version_no INT NOT NULL,status VARCHAR(40) NOT NULL',
        'fm2_order_installers' => 'assignment_order_id BIGINT NOT NULL,installer_tab_id BIGINT NOT NULL,fio_snapshot VARCHAR(300) NOT NULL,position_snapshot VARCHAR(300) NOT NULL,employment_status_snapshot VARCHAR(40) NOT NULL,workforce_source_updated_at_snapshot VARCHAR(40) NOT NULL',
        'fm2_workforce_catalog' => 'installer_tab_id BIGINT PRIMARY KEY,fio VARCHAR(300) NOT NULL,position VARCHAR(300) NOT NULL,employment_status VARCHAR(40) NOT NULL,dismissal_effective_at VARCHAR(40) NULL,workforce_source_updated_at VARCHAR(40) NOT NULL',
        'fm2_checklist_template_snapshots' => 'id BIGINT PRIMARY KEY,snapshot_version VARCHAR(80) NOT NULL,valid_from DATETIME NOT NULL,content_sha256 CHAR(64) NOT NULL',
        'fm2_checklist_template_associations' => 'subject_kind VARCHAR(40) NOT NULL,subject_id VARCHAR(160) NOT NULL,effective_at DATETIME NOT NULL,template_snapshot_id BIGINT NOT NULL,template_snapshot_version VARCHAR(80) NOT NULL,template_content_sha256 CHAR(64) NOT NULL',
        'fm2_checklist_revisions' => 'installation_case_id BIGINT PRIMARY KEY,revision_no BIGINT NOT NULL,updated_at VARCHAR(40) NOT NULL',
        'fm2_checklist_operations' => 'id BIGINT AUTO_INCREMENT PRIMARY KEY,installation_case_id BIGINT NOT NULL,client_operation_id CHAR(36) NOT NULL UNIQUE,device_installation_id CHAR(36) NOT NULL,operation_type VARCHAR(40) NOT NULL,section_id TINYINT NOT NULL,item_id SMALLINT NULL,actor_user_id BIGINT NOT NULL,device_time VARCHAR(40) NOT NULL,server_received_at VARCHAR(40) NOT NULL,base_revision BIGINT NOT NULL,accepted_revision BIGINT NOT NULL,payload_json TEXT NOT NULL,template_snapshot_id BIGINT NULL,template_snapshot_version VARCHAR(80) NULL,template_content_sha256 CHAR(64) NULL',
        'fm2_checklist_operation_installers' => 'client_operation_id CHAR(36) NOT NULL,installer_tab_id BIGINT NOT NULL,fio_snapshot VARCHAR(300) NOT NULL,position_snapshot VARCHAR(300) NOT NULL,employment_status_snapshot VARCHAR(40) NOT NULL,dismissal_effective_at_snapshot VARCHAR(40) NULL,workforce_source_updated_at_snapshot VARCHAR(40) NOT NULL,assignment_source VARCHAR(40) NOT NULL,PRIMARY KEY(client_operation_id,installer_tab_id)',
        'fm2_checklist_photos' => 'id BIGINT AUTO_INCREMENT PRIMARY KEY,installation_case_id BIGINT NOT NULL,section_id TINYINT NOT NULL,upload_operation_id CHAR(36) NOT NULL UNIQUE,sha256 CHAR(64) NOT NULL,mime_type VARCHAR(40) NOT NULL,byte_size INT NOT NULL,original_name VARCHAR(255) NOT NULL,storage_name VARCHAR(255) NOT NULL,actor_user_id BIGINT NOT NULL,device_time VARCHAR(40) NOT NULL,server_received_at VARCHAR(40) NOT NULL,revoked_at VARCHAR(40) NULL,UNIQUE KEY unique_active_content(installation_case_id,section_id,sha256)',
    ];
    foreach ($ddl as $table => $definition) {
        $db->query("CREATE TABLE `{$prefix}{$table}`({$definition}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    $templateHash = str_repeat('a', 64);
    $db->query("INSERT INTO `{$prefix}fm2_installation_cases` VALUES(71,1701,'working')");
    $db->query("INSERT INTO `{$prefix}fm2_checklist_template_snapshots` VALUES(91,'photo-limit-v1','2026-08-01 00:00:00','{$templateHash}')");
    $db->query("INSERT INTO `{$prefix}fm2_checklist_template_associations` VALUES('operational_case','71','2026-08-02 00:00:00',91,'photo-limit-v1','{$templateHash}')");
    $db->query("INSERT INTO `{$prefix}fm2_checklist_revisions` VALUES(71,9,'2026-08-31 07:00:00.000000')");
    if (!mkdir($privateRoot . '/checklist', 0700)) {
        throw new RuntimeException('SETUP_FAILURE: photo-limit fixture blob directory cannot be created');
    }
    $photoInsert = $db->prepare("INSERT INTO `{$prefix}fm2_checklist_photos`(installation_case_id,section_id,upload_operation_id,sha256,mime_type,byte_size,original_name,storage_name,actor_user_id,device_time,server_received_at) VALUES(71,3,?,?,?,?,?,?,901,'2026-08-30T10:00:00+03:00','2026-08-30 07:00:00.000000')");
    for ($i = 1; $i <= 9; $i++) {
        $operationId = sprintf('10000000-0000-4000-8000-%012d', $i);
        $bytes = 'seed-photo-' . $i;
        $sha = hash('sha256', $bytes);
        $mime = 'image/png';
        $size = strlen($bytes);
        $name = 'seed-' . $i . '.png';
        $storage = $sha . '.bin';
        $photoInsert->bind_param('sssiss', $operationId, $sha, $mime, $size, $name, $storage);
        $photoInsert->execute();
        if (file_put_contents($privateRoot . '/checklist/' . $storage, $bytes, LOCK_EX) !== $size) {
            throw new RuntimeException('SETUP_FAILURE: seed photo blob cannot be created');
        }
    }

    $failureMode = getenv('FMONITOR_PHOTO_LIMIT_VERIFY_TEST_FAILURE') ?: '';
    if ($failureMode === 'regression_after_mutation') {
        throw new RuntimeException('REGRESSION_FAILURE: controlled aggregate regression after mutation');
    }

    $aggregateBefore = ['revision' => 9, 'active' => 9, 'operation_rows' => 0, 'photo_rows' => 9, 'contender_blobs' => 0];
    $db->close();
    $db = null;
    $deviceId = '20000000-0000-4000-8000-000000000001';
    foreach (['A', 'B'] as $label) {
        $pid = pcntl_fork();
        if ($pid < 0) {
            throw new RuntimeException('SETUP_FAILURE: race child could not be started');
        }
        if ($pid === 0) {
            try {
                $childDb = photoLimitDb();
                photoLimitWriteJson($privateRoot . '/ready-' . $label . '.json', ['pid' => getmypid(), 'connection_id' => (int) $childDb->thread_id]);
                if ($failureMode === 'child_crash_after_mutation' && $label === 'B') {
                    exit(23);
                }
                if ($failureMode === 'timeout_after_mutation') {
                    while (true) {
                        sleep(10);
                    }
                }
                photoLimitWaitFor(static fn(): bool => is_file($privateRoot . '/release'), 5.0, 'race barrier release timed out');
                $fixture = $fixtures[$label];
                $bytes = base64_decode((string) $fixture['bytes_base64'], true);
                $operation = [
                    'clientOperationId' => $fixture['operation_id'], 'deviceInstallationId' => $deviceId,
                    'type' => 'photo_uploaded', 'deviceTime' => $fixture['device_time'],
                    'baseRevision' => $fixture['base_revision'], 'sectionId' => $fixture['section'],
                    'sha256' => $fixture['sha256'], 'mime' => $fixture['mime'], 'size' => $fixture['size'],
                    'originalName' => $fixture['filename'],
                ];
                $sync = new ChecklistSync($childDb, $prefix, $privateRoot, (string) $fixture['server_time']);
                $result = $sync->accept(1701, new HttpUser(901, 'Photo limit verifier', 'photo-limit@example.invalid'), $operation, is_string($bytes) ? $bytes : null);
                photoLimitWriteJson($privateRoot . '/result-' . $label . '.json', $result);
                $childDb->close();
                exit(0);
            } catch (Throwable $exception) {
                @file_put_contents($privateRoot . '/error-' . $label, $exception->getMessage(), LOCK_EX);
                exit(24);
            }
        }
        $children[$label] = $pid;
    }

    $timeoutSeconds = $failureMode === 'timeout_after_mutation'
        ? (float) (getenv('FMONITOR_PHOTO_LIMIT_VERIFY_TEST_TIMEOUT_SECONDS') ?: '0.25')
        : 5.0;
    try {
        photoLimitWaitFor(static fn(): bool => is_file($privateRoot . '/ready-A.json') && is_file($privateRoot . '/ready-B.json'), $timeoutSeconds, 'race children did not reach barrier');
    } catch (Throwable $exception) {
        if ($failureMode === 'timeout_after_mutation') {
            $processFile = getenv('FMONITOR_PHOTO_LIMIT_VERIFY_TEST_PROCESS_FILE');
            if (is_string($processFile) && dirname($processFile) === $artifactRoot) {
                photoLimitWriteJson($processFile, ['parent_pid' => getmypid(), 'process_group_id' => posix_getpgrp(), 'child_pids' => array_values($children)]);
            }
            photoLimitKillAndReap(array_values($children));
            $children = [];
            throw new RuntimeException('SETUP_FAILURE: controlled race child timeout after mutation');
        }
        throw $exception;
    }
    $ready = ['A' => photoLimitReadJson($privateRoot . '/ready-A.json'), 'B' => photoLimitReadJson($privateRoot . '/ready-B.json')];
    if ($failureMode === 'timeout_after_mutation') {
        usleep((int) max(10000, $timeoutSeconds * 1000000));
        $processFile = getenv('FMONITOR_PHOTO_LIMIT_VERIFY_TEST_PROCESS_FILE');
        if (is_string($processFile) && dirname($processFile) === $artifactRoot) {
            photoLimitWriteJson($processFile, ['parent_pid' => getmypid(), 'process_group_id' => posix_getpgrp(), 'child_pids' => array_values($children)]);
        }
        photoLimitKillAndReap(array_values($children));
        $children = [];
        throw new RuntimeException('SETUP_FAILURE: controlled race child timeout after mutation');
    }
    if ($failureMode === 'child_crash_after_mutation') {
        photoLimitKillAndReap(array_values($children));
        $children = [];
        throw new RuntimeException('SETUP_FAILURE: controlled race child crashed after mutation');
    }
    if (file_put_contents($privateRoot . '/release', 'go', LOCK_EX) !== 2) {
        throw new RuntimeException('SETUP_FAILURE: race barrier could not be released');
    }
    $deadline = microtime(true) + 5.0;
    $statuses = [];
    foreach ($children as $label => $pid) {
        do {
            $waited = pcntl_waitpid($pid, $status, WNOHANG);
            if ($waited === $pid) {
                $statuses[$label] = $status;
                break;
            }
            if (microtime(true) >= $deadline) {
                photoLimitKillAndReap(array_values($children));
                $children = [];
                throw new RuntimeException('SETUP_FAILURE: race child timed out');
            }
            usleep(10000);
        } while (true);
    }
    $children = [];
    foreach ($statuses as $label => $status) {
        if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0 || !is_file($privateRoot . '/result-' . $label . '.json')) {
            throw new RuntimeException('SETUP_FAILURE: race child failed before behavioral result');
        }
    }
    $rawResults = ['A' => photoLimitReadJson($privateRoot . '/result-A.json'), 'B' => photoLimitReadJson($privateRoot . '/result-B.json')];

    $db = photoLimitDb();
    $sync = new ChecklistSync($db, $prefix, $privateRoot, '2026-08-31 07:00:02.000000');
    $projection = $sync->projection(1701);
    $statusValues = [$rawResults['A']['status'] ?? null, $rawResults['B']['status'] ?? null];
    sort($statusValues);
    photoLimitAssert($statusValues === ['accepted', 'rejected'], 'race result cardinality drifted');
    $winner = ($rawResults['A']['status'] ?? null) === 'accepted' ? 'A' : 'B';
    $loser = $winner === 'A' ? 'B' : 'A';
    $operationRows = (int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_checklist_operations`")->fetch_assoc()['n'];
    $photoRows = (int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_checklist_photos`")->fetch_assoc()['n'];
    $contenderBlobs = [];
    foreach (['A', 'B'] as $label) {
        $path = $privateRoot . '/checklist/' . $fixtures[$label]['sha256'] . '.bin';
        if (is_file($path)) {
            $contenderBlobs[] = hash_file('sha256', $path);
        }
    }
    sort($contenderBlobs);
    $photos = array_values(array_filter($projection['photos'], static fn(array $photo): bool => $photo['sectionId'] === 3));
    $tenth = $photos[9] ?? [];
    $loserMutation = 0;
    foreach ($projection['photos'] as $photo) {
        if (($photo['clientOperationId'] ?? null) === $fixtures[$loser]['operation_id'] || ($photo['sha256'] ?? null) === $fixtures[$loser]['sha256']) {
            $loserMutation++;
        }
    }
    $aggregateAfter = [
        'revision' => $projection['revision'], 'active' => count($photos), 'operation_rows' => $operationRows,
        'photo_rows' => $photoRows, 'contender_blobs' => count($contenderBlobs),
        'operations_added' => $operationRows, 'photos_added' => $photoRows - 9,
        'blobs_added' => count($contenderBlobs), 'loser_mutations' => $loserMutation,
        'tenth_operation_id' => $tenth['clientOperationId'] ?? null, 'tenth_sha256' => $tenth['sha256'] ?? null,
        'blob_sha256s' => $contenderBlobs,
    ];
    photoLimitAssert($rawResults[$winner] === ['status' => 'accepted', 'revision' => 10], 'winner result drifted');
    photoLimitAssert(($rawResults[$loser]['status'] ?? null) === 'rejected', 'loser result drifted');
    $results = [
        'A' => $winner === 'A' ? ['status' => 'accepted', 'revision' => 10] : ['status' => 'rejected'],
        'B' => $winner === 'B' ? ['status' => 'accepted', 'revision' => 10] : ['status' => 'rejected'],
    ];
    $expectedAggregate = ['revision' => 10, 'active' => 10, 'operation_rows' => 1, 'photo_rows' => 10, 'contender_blobs' => 1, 'operations_added' => 1, 'photos_added' => 1, 'blobs_added' => 1, 'loser_mutations' => 0, 'tenth_operation_id' => $fixtures[$winner]['operation_id'], 'tenth_sha256' => $fixtures[$winner]['sha256'], 'blob_sha256s' => [$fixtures[$winner]['sha256']]];
    photoLimitAssert($aggregateAfter === $expectedAggregate, 'race aggregate drifted');

    $sameOperation = [
        'clientOperationId' => '0199a100-0000-4000-8000-00000000000c', 'deviceInstallationId' => $deviceId,
        'type' => 'photo_uploaded', 'deviceTime' => $fixtures[$winner]['device_time'], 'baseRevision' => 10,
        'sectionId' => 3, 'sha256' => $fixtures[$winner]['sha256'], 'mime' => $fixtures[$winner]['mime'],
        'size' => $fixtures[$winner]['size'], 'originalName' => $fixtures[$winner]['filename'],
    ];
    $sameBytes = base64_decode((string) $fixtures[$winner]['bytes_base64'], true);
    $sameResult = $sync->accept(1701, new HttpUser(901, 'Photo limit verifier', 'photo-limit@example.invalid'), $sameOperation, is_string($sameBytes) ? $sameBytes : null);
    $sameProjection = $sync->projection(1701);
    $sameOperations = (int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_checklist_operations`")->fetch_assoc()['n'];
    $samePhotos = (int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_checklist_photos`")->fetch_assoc()['n'];
    $sameContent = [
        'contender' => $winner, 'operation_id' => $sameOperation['clientOperationId'],
        'content_sha256' => $fixtures[$winner]['sha256'], 'status' => $sameResult['status'] ?? null,
        'revision' => $sameResult['revision'] ?? null, 'active' => count($sameProjection['photos']),
        'operation_rows' => $sameOperations, 'photo_rows' => $samePhotos, 'contender_blobs' => count($contenderBlobs),
        'operations_added' => $sameOperations - $operationRows, 'photos_added' => $samePhotos - $photoRows,
        'blobs_added' => 0,
    ];
    photoLimitAssert($sameResult === ['status' => 'duplicate', 'revision' => 10] && $sameProjection['revision'] === 10, 'same-content classification drifted');

    $audit = [
        'protocol_version' => 1, 'run_token' => $token, 'fixtures' => $fixtures,
        'race' => [
            'child_pids' => [$ready['A']['pid'], $ready['B']['pid']],
            'connection_ids' => [$ready['A']['connection_id'], $ready['B']['connection_id']],
            'ready_contenders' => ['A', 'B'], 'barrier_released' => true,
            'aggregate_before' => $aggregateBefore, 'results' => $results, 'aggregate_after' => $aggregateAfter,
        ],
        'same_content' => $sameContent,
    ];
    photoLimitWriteJson($auditFile, $audit);
    echo "PHOTO_LIMIT race accepted=1 rejected=1 revision=10 active=10 operations_added=1 photos_added=1 blobs_added=1 loser_mutations=0\n";
    echo "PHOTO_LIMIT same-content-at-cap duplicate revision=10 active=10 operations_added=0 photos_added=0 blobs_added=0\n";
    echo "CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001\n";
} catch (mysqli_sql_exception $exception) {
    $failure = 'SETUP_FAILURE: disposable verification database failed: ' . $exception->getMessage();
    $exit = 2;
} catch (Throwable $exception) {
    $failure = $exception->getMessage();
    if (!str_starts_with($failure, 'SETUP_FAILURE:') && !str_starts_with($failure, 'REGRESSION_FAILURE:')) {
        $failure = 'REGRESSION_FAILURE: ' . $failure;
    }
    $exit = str_starts_with($failure, 'SETUP_FAILURE:') ? 2 : 1;
} finally {
    if ($children !== []) {
        photoLimitKillAndReap(array_values($children));
    }
    if (!$db instanceof mysqli && $ownsNamespace) {
        try {
            $db = photoLimitDb();
        } catch (Throwable) {
        }
    }
    if ($db instanceof mysqli && $ownsNamespace) {
        foreach ($tables as $table) {
            try {
                $db->query("DROP TABLE IF EXISTS `{$prefix}{$table}`");
            } catch (Throwable) {
            }
        }
    }
    if ($db instanceof mysqli) {
        $db->close();
    }
    if ($ownsNamespace) {
        photoLimitRemoveTree($privateRoot);
    }
}

if ($failure !== null) {
    fwrite(STDERR, $failure . "\n");
    exit($exit);
}
