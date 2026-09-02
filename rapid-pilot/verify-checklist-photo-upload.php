<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/PilotHttp/PilotHttp.php';
require_once dirname(__DIR__) . '/app/PilotHttp/ChecklistSync.php';

use FMonitor2\PilotHttp\ChecklistSync;
use FMonitor2\PilotHttp\HttpUser;
use FMonitor2\PilotHttp\PilotHttpInfrastructureUnavailable;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const PHOTO_HASH = '431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460';
const TRANSCRIPT_HASH = '7603606ad948a2bf464ccb02ee5e797c4daeb6580ca9b0fd85e07fb102d5067d';

function photoVerifyAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function photoVerifyRemoveTree(string $path): void
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

$runToken = getenv('FMONITOR_PHOTO_VERIFY_RUN_TOKEN');
if (!is_string($runToken) || preg_match('/\A[a-f0-9]{12}\z/D', $runToken) !== 1) {
    fwrite(STDERR, "SETUP_FAILURE: photo verifier run token is invalid\n");
    exit(2);
}

$artifactRoot = getenv('FMONITOR_PHOTO_VERIFY_ARTIFACT_ROOT');
$artifactRootReal = is_string($artifactRoot) ? realpath($artifactRoot) : false;
$artifactRootInfo = is_string($artifactRoot) ? @lstat($artifactRoot) : false;
if (
    !is_string($artifactRoot)
    || $artifactRoot === ''
    || $artifactRoot[0] !== '/'
    || str_contains($artifactRoot, "\0")
    || !is_string($artifactRootReal)
    || $artifactRoot !== $artifactRootReal
    || !is_array($artifactRootInfo)
    || is_link($artifactRoot)
    || !is_dir($artifactRoot)
    || $artifactRootReal === '/tmp'
    || str_starts_with($artifactRootReal, '/tmp/')
) {
    fwrite(STDERR, "SETUP_FAILURE: supplied photo artifact root is unsafe\n");
    exit(2);
}

$prefix = 'pu_' . $runToken . '_';
$privateRoot = $artifactRoot . '/photo-verify-' . $runToken;
$tables = [
    'fm2_checklist_operation_installers',
    'fm2_checklist_photos',
    'fm2_checklist_operations',
    'fm2_checklist_revisions',
    'fm2_checklist_template_associations',
    'fm2_checklist_template_snapshots',
    'fm2_workforce_catalog',
    'fm2_order_installers',
    'fm2_assignment_orders',
    'fm2_installation_cases',
];
$db = null;
$exit = 0;
$failure = null;
$ownsNamespace = false;

try {
    $host = getenv('FMONITOR_VERIFY_DB_HOST') ?: '127.0.0.1';
    $port = (int) (getenv('FMONITOR_VERIFY_DB_PORT') ?: 23306);
    $name = getenv('FMONITOR_VERIFY_DB_NAME') ?: 'fmonitor2_test';
    $user = getenv('FMONITOR_VERIFY_DB_USER') ?: 'fmonitor2_test';
    $password = getenv('FMONITOR_VERIFY_DB_PASSWORD') ?: 'fmonitor2_test_local';
    try {
        $db = new mysqli($host, $user, $password, $name, $port);
        $db->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $exception) {
        throw new UnexpectedValueException('SETUP_FAILURE: disposable verification database is unavailable: ' . $exception->getMessage(), 2);
    }

    $escapedPrefix = str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $prefix);
    $namespaceStatement = $db->prepare(
        "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE CONCAT(?, '%') ESCAPE '\\\\' LIMIT 1",
    );
    $namespaceStatement->bind_param('s', $escapedPrefix);
    $namespaceStatement->execute();
    $sqlNamespaceOccupied = $namespaceStatement->get_result()->fetch_row() !== null;
    if ($sqlNamespaceOccupied || file_exists($privateRoot) || is_link($privateRoot)) {
        throw new UnexpectedValueException('SETUP_FAILURE: photo verifier owned namespace is occupied', 2);
    }
    if (!mkdir($privateRoot, 0700)) {
        throw new UnexpectedValueException('SETUP_FAILURE: private photo artifact directory cannot be created', 2);
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
    $db->query("INSERT INTO `{$prefix}fm2_installation_cases` VALUES(71,1701,'working'),(72,1702,'working')");
    $db->query("INSERT INTO `{$prefix}fm2_checklist_template_snapshots` VALUES(91,'photo-fixture-v1','2026-08-01 00:00:00','{$templateHash}')");
    $db->query("INSERT INTO `{$prefix}fm2_checklist_template_associations` VALUES('operational_case','71','2026-08-02 00:00:00',91,'photo-fixture-v1','{$templateHash}'),('operational_case','72','2026-08-02 00:00:00',91,'photo-fixture-v1','{$templateHash}')");

    $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
    photoVerifyAssert(
        is_string($bytes)
        && strlen($bytes) === 68
        && (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes) === 'image/png'
        && hash('sha256', $bytes) === PHOTO_HASH,
        'approved PNG fixture drifted',
    );
    $actor = new HttpUser(901, 'Photo verifier', 'photo-verifier@example.invalid');
    $operation = [
        'clientOperationId' => '11111111-1111-4111-8111-111111111111',
        'deviceInstallationId' => '22222222-2222-4222-8222-222222222222',
        'type' => 'photo_uploaded',
        'deviceTime' => '2026-08-30T10:00:00+00:00',
        'baseRevision' => 0,
        'sectionId' => 3,
        'sha256' => PHOTO_HASH,
        'mime' => 'image/png',
        'size' => 68,
        'originalName' => 'section-3-evidence.png',
    ];
    $sync = new ChecklistSync($db, $prefix, $privateRoot, '2026-08-30T10:00:01+00:00');
    $accepted = $sync->accept(1701, $actor, $operation, $bytes);
    $projection = $sync->projection(1701);
    $photos = $projection['photos'];
    $blob = $privateRoot . '/checklist/' . PHOTO_HASH . '.bin';
    photoVerifyAssert($accepted === ['status' => 'accepted', 'revision' => 1], 'upload was not accepted at revision 1');
    photoVerifyAssert($projection['revision'] === 1 && count($photos) === 1, 'accepted photo projection drifted');
    photoVerifyAssert($photos[0] === [
        'id' => 1,
        'sectionId' => 3,
        'clientOperationId' => $operation['clientOperationId'],
        'sha256' => PHOTO_HASH,
        'mime' => 'image/png',
        'size' => 68,
        'originalName' => 'section-3-evidence.png',
        'actorUserId' => 901,
        'deviceTime' => '2026-08-30T10:00:00+00:00',
        'serverReceivedAt' => '2026-08-30T10:00:01+00:00',
    ], 'accepted photo metadata drifted');
    photoVerifyAssert(is_file($blob) && hash_file('sha256', $blob) === PHOTO_HASH, 'content-addressed photo blob is missing or changed');
    $blobCount = count(glob($privateRoot . '/checklist/*.bin') ?: []);
    echo 'PHOTO_UPLOAD accepted revision=1 active=1 blob_sha256=' . PHOTO_HASH . "\n";

    $replay = $sync->accept(1701, $actor, $operation, $bytes);
    $replayProjection = $sync->projection(1701);
    photoVerifyAssert($replay === ['status' => 'duplicate', 'revision' => 1], 'exact upload replay was not idempotent');
    photoVerifyAssert($replayProjection === $projection && count(glob($privateRoot . '/checklist/*.bin') ?: []) === $blobCount, 'replay changed projection or blob count');
    echo "PHOTO_UPLOAD replay duplicate revision=1 active=1\n";

    $blockedRoot = $privateRoot . '/storage-unavailable';
    photoVerifyAssert(file_put_contents($blockedRoot, 'regular-file', LOCK_EX) === 12, 'storage-failure fixture could not be created');
    $failureOperation = $operation;
    $failureOperation['clientOperationId'] = '33333333-3333-4333-8333-333333333333';
    $unavailable = new ChecklistSync($db, $prefix, $blockedRoot, '2026-08-30T10:00:02+00:00');
    $thrown = false;
    try {
        $unavailable->accept(1702, $actor, $failureOperation, $bytes);
    } catch (PilotHttpInfrastructureUnavailable) {
        $thrown = true;
    }
    $failureProjection = $unavailable->projection(1702);
    photoVerifyAssert($thrown, 'storage creation failure was not retryable infrastructure failure');
    photoVerifyAssert($failureProjection['revision'] === 0 && $failureProjection['photos'] === [], 'storage failure created an accepted photo fact');
    photoVerifyAssert(!is_dir($blockedRoot . '/checklist'), 'storage failure unexpectedly created blob storage');
    echo "PHOTO_UPLOAD storage-unavailable retryable revision=0 active=0\n";
    echo 'CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001 transcript_sha256=' . TRANSCRIPT_HASH . "\n";
} catch (UnexpectedValueException $exception) {
    $failure = $exception->getMessage();
    $exit = $exception->getCode() === 2 ? 2 : 1;
} catch (Throwable $exception) {
    $failure = 'REGRESSION_FAILURE: ' . $exception->getMessage();
    $exit = 1;
} finally {
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
        photoVerifyRemoveTree($privateRoot);
    }
}

if ($failure !== null) {
    fwrite(STDERR, $failure . "\n");
    exit($exit);
}
