<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/PilotHttp/PilotHttp.php';
require_once dirname(__DIR__) . '/app/PilotHttp/ChecklistSync.php';

use FMonitor2\PilotHttp\ChecklistSync;
use FMonitor2\PilotHttp\HttpUser;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const PHOTO_REVOKE_TRANSCRIPT_HASH = '60f1a4c65be2a4cedd05f170b243d34283560f480f37a2965fec7aeadd62b784';

function photoRevokeAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function photoRevokeRemoveTree(string $path): void
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

function photoRevokeProjection(array $projection): array
{
    return ['revision' => $projection['revision'], 'photos' => $projection['photos']];
}

function photoRevokeHistory(mysqli $db, string $prefix): array
{
    $rows = $db->query(
        "SELECT installation_case_id,client_operation_id,device_installation_id,operation_type,section_id,actor_user_id,device_time,server_received_at,base_revision,accepted_revision,payload_json FROM `{$prefix}fm2_checklist_operations` ORDER BY accepted_revision,id",
    )->fetch_all(MYSQLI_ASSOC);
    return array_map(static fn(array $row): array => [
        'installation_case_id' => (int) $row['installation_case_id'],
        'client_operation_id' => $row['client_operation_id'],
        'device_installation_id' => $row['device_installation_id'],
        'operation_type' => $row['operation_type'],
        'section_id' => (int) $row['section_id'],
        'actor_user_id' => (int) $row['actor_user_id'],
        'device_time' => $row['device_time'],
        'server_received_at' => $row['server_received_at'],
        'base_revision' => (int) $row['base_revision'],
        'accepted_revision' => (int) $row['accepted_revision'],
        'payload' => json_decode($row['payload_json'], true, 16, JSON_THROW_ON_ERROR),
    ], $rows);
}

function photoRevokePhotos(mysqli $db, string $prefix): array
{
    $rows = $db->query(
        "SELECT id,installation_case_id,section_id,upload_operation_id,sha256,mime_type,byte_size,original_name,storage_name,actor_user_id,device_time,server_received_at,revoked_at FROM `{$prefix}fm2_checklist_photos` ORDER BY id",
    )->fetch_all(MYSQLI_ASSOC);
    return array_map(static fn(array $row): array => [
        'id' => (int) $row['id'],
        'installation_case_id' => (int) $row['installation_case_id'],
        'section_id' => (int) $row['section_id'],
        'upload_operation_id' => $row['upload_operation_id'],
        'sha256' => $row['sha256'],
        'mime_type' => $row['mime_type'],
        'byte_size' => (int) $row['byte_size'],
        'original_name' => $row['original_name'],
        'storage_name' => $row['storage_name'],
        'actor_user_id' => (int) $row['actor_user_id'],
        'device_time' => $row['device_time'],
        'server_received_at' => $row['server_received_at'],
        'revoked_at' => $row['revoked_at'],
    ], $rows);
}

function photoRevokeBlobs(string $privateRoot): array
{
    $paths = glob($privateRoot . '/checklist/*.bin') ?: [];
    sort($paths, SORT_STRING);
    return [
        'count' => count($paths),
        'sha256s' => array_map(static fn(string $path): string => (string) hash_file('sha256', $path), $paths),
    ];
}

function photoRevokeFingerprint(mysqli $db, string $prefix, string $privateRoot): array
{
    $revision = (int) $db->query("SELECT revision_no FROM `{$prefix}fm2_checklist_revisions` WHERE installation_case_id=71")->fetch_assoc()['revision_no'];
    return [
        'revision' => $revision,
        'operations' => photoRevokeHistory($db, $prefix),
        'photos' => photoRevokePhotos($db, $prefix),
        'blobs' => photoRevokeBlobs($privateRoot),
    ];
}

$runToken = getenv('FMONITOR_PHOTO_REVOKE_VERIFY_RUN_TOKEN');
if (!is_string($runToken) || preg_match('/\A[a-f0-9]{12}\z/D', $runToken) !== 1) {
    fwrite(STDERR, "SETUP_FAILURE: photo-revoke verifier run token is invalid\n");
    exit(2);
}

$artifactRoot = getenv('FMONITOR_PHOTO_REVOKE_VERIFY_ARTIFACT_ROOT');
$artifactRootReal = is_string($artifactRoot) ? realpath($artifactRoot) : false;
$artifactRootInfo = is_string($artifactRoot) ? @lstat($artifactRoot) : false;
if (!is_string($artifactRoot) || $artifactRoot === '' || $artifactRoot[0] !== '/'
    || str_contains($artifactRoot, "\0") || !is_string($artifactRootReal)
    || $artifactRoot !== $artifactRootReal || !is_array($artifactRootInfo)
    || is_link($artifactRoot) || !is_dir($artifactRoot)
    || $artifactRootReal === '/tmp' || str_starts_with($artifactRootReal, '/tmp/')) {
    fwrite(STDERR, "SETUP_FAILURE: supplied photo-revoke artifact root is unsafe\n");
    exit(2);
}

$fixtureJson = getenv('FMONITOR_PHOTO_REVOKE_VERIFY_FIXTURE_JSON');
$auditFile = getenv('FMONITOR_PHOTO_REVOKE_VERIFY_AUDIT_FILE');
try {
    $fixture = is_string($fixtureJson) ? json_decode($fixtureJson, true, 32, JSON_THROW_ON_ERROR) : null;
} catch (Throwable) {
    $fixture = null;
}
if (!is_array($fixture) || !is_string($auditFile) || dirname($auditFile) !== $artifactRoot
    || basename($auditFile) !== 'audit-' . $runToken . '.json' || file_exists($auditFile) || is_link($auditFile)) {
    fwrite(STDERR, "SETUP_FAILURE: photo-revoke fixture or audit destination is invalid\n");
    exit(2);
}

$prefix = 'pr_' . $runToken . '_';
$privateRoot = $artifactRoot . '/photo-revoke-' . $runToken;
$tables = [
    'fm2_checklist_operation_installers', 'fm2_checklist_photos', 'fm2_checklist_operations',
    'fm2_checklist_revisions', 'fm2_checklist_template_associations', 'fm2_checklist_template_snapshots',
    'fm2_workforce_catalog', 'fm2_order_installers', 'fm2_assignment_orders', 'fm2_installation_cases',
];
$db = null;
$failure = null;
$exit = 0;
$ownsNamespace = false;

try {
    try {
        $db = new mysqli(
            getenv('FMONITOR_VERIFY_DB_HOST') ?: '127.0.0.1',
            getenv('FMONITOR_VERIFY_DB_USER') ?: 'fmonitor2_test',
            getenv('FMONITOR_VERIFY_DB_PASSWORD') ?: 'fmonitor2_test_local',
            getenv('FMONITOR_VERIFY_DB_NAME') ?: 'fmonitor2_test',
            (int) (getenv('FMONITOR_VERIFY_DB_PORT') ?: 23306),
        );
        $db->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $exception) {
        throw new UnexpectedValueException('SETUP_FAILURE: disposable verification database is unavailable: ' . $exception->getMessage(), 2);
    }

    $escapedPrefix = str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $prefix);
    $statement = $db->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE CONCAT(?, '%') ESCAPE '\\\\' LIMIT 1");
    $statement->bind_param('s', $escapedPrefix);
    $statement->execute();
    if ($statement->get_result()->fetch_row() !== null || file_exists($privateRoot) || is_link($privateRoot)) {
        throw new UnexpectedValueException('SETUP_FAILURE: photo-revoke verifier owned namespace is occupied', 2);
    }
    if (!mkdir($privateRoot, 0700)) {
        throw new UnexpectedValueException('SETUP_FAILURE: private photo-revoke artifact directory cannot be created', 2);
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
    try {
        foreach ($ddl as $table => $definition) {
            $db->query("CREATE TABLE `{$prefix}{$table}`({$definition}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        $common = $fixture['common'];
        $caseId = (int) $common['installation_case_db_id'];
        $legacyId = (int) $common['legacy_object_id'];
        $templateHash = str_repeat('a', 64);
        $db->query("INSERT INTO `{$prefix}fm2_installation_cases` VALUES({$caseId},{$legacyId},'working')");
        $db->query("INSERT INTO `{$prefix}fm2_checklist_template_snapshots` VALUES(91,'photo-revoke-v1','2026-08-01 00:00:00','{$templateHash}')");
        $db->query("INSERT INTO `{$prefix}fm2_checklist_template_associations` VALUES('operational_case','{$caseId}','2026-08-02 00:00:00',91,'photo-revoke-v1','{$templateHash}')");
        $db->query("INSERT INTO `{$prefix}fm2_checklist_revisions` VALUES({$caseId},0,'2026-08-31 07:00:00.000000')");
    } catch (Throwable $exception) {
        throw new UnexpectedValueException('SETUP_FAILURE: photo-revoke verification fixtures cannot be created: ' . $exception->getMessage(), 2, $exception);
    }

    $failureMode = getenv('FMONITOR_PHOTO_REVOKE_VERIFY_TEST_FAILURE');
    if ($failureMode === 'setup_after_mutation') {
        throw new UnexpectedValueException('SETUP_FAILURE: controlled post-mutation setup probe', 2);
    }
    if ($failureMode === 'regression_after_mutation') {
        throw new RuntimeException('controlled post-mutation regression probe');
    }

    $bytes = base64_decode((string) $fixture['png']['base64'], true);
    photoRevokeAssert(is_string($bytes), 'fixture PNG is not valid base64');
    $actor = new HttpUser((int) $common['actor_id'], 'Photo revoke verifier', 'photo-revoke-verifier@example.invalid');
    $base = [
        'deviceInstallationId' => $common['device_id'],
        'sectionId' => (int) $common['section'],
    ];
    $uploadEnvelope = $fixture['envelopes']['upload'];
    $upload = $base + [
        'clientOperationId' => $uploadEnvelope['client_operation_id'], 'type' => 'photo_uploaded',
        'deviceTime' => $uploadEnvelope['device_time'], 'baseRevision' => (int) $uploadEnvelope['base_revision'],
        'sha256' => $fixture['png']['sha256'], 'mime' => $fixture['png']['mime'],
        'size' => (int) $fixture['png']['size'], 'originalName' => $fixture['png']['filename'],
    ];
    $uploadSync = new ChecklistSync($db, $prefix, $privateRoot, $uploadEnvelope['server_receipt_time']);
    $uploadResult = $uploadSync->accept($legacyId, $actor, $upload, $bytes);
    $uploadProjection = photoRevokeProjection($uploadSync->projection($legacyId));
    photoRevokeAssert(count($uploadProjection['photos']) === 1, 'upload projection did not expose exactly one photo');
    $photoId = $uploadProjection['photos'][0]['id'];

    $revokeEnvelope = $fixture['envelopes']['revoke'];
    $revoke = $base + [
        'clientOperationId' => $revokeEnvelope['client_operation_id'], 'type' => 'photo_revoked',
        'deviceTime' => $revokeEnvelope['device_time'], 'baseRevision' => (int) $revokeEnvelope['base_revision'],
        'photoId' => $photoId,
    ];
    $revokeSync = new ChecklistSync($db, $prefix, $privateRoot, $revokeEnvelope['server_receipt_time']);
    $revokeResult = $revokeSync->accept($legacyId, $actor, $revoke);
    $revokeProjection = photoRevokeProjection($revokeSync->projection($legacyId));
    $history = photoRevokeHistory($db, $prefix);
    $photos = photoRevokePhotos($db, $prefix);
    $blobs = photoRevokeBlobs($privateRoot);

    $fingerprints = [];
    $before = photoRevokeFingerprint($db, $prefix, $privateRoot);
    $replayResult = $revokeSync->accept($legacyId, $actor, $revoke);
    $replayProjection = photoRevokeProjection($revokeSync->projection($legacyId));
    $fingerprints['replay'] = ['before' => $before, 'after' => photoRevokeFingerprint($db, $prefix, $privateRoot)];

    $freshEnvelope = $fixture['envelopes']['fresh_revoke'];
    $fresh = $base + [
        'clientOperationId' => $freshEnvelope['client_operation_id'], 'type' => 'photo_revoked',
        'deviceTime' => $freshEnvelope['device_time'], 'baseRevision' => (int) $freshEnvelope['base_revision'],
        'photoId' => $photoId,
    ];
    $before = photoRevokeFingerprint($db, $prefix, $privateRoot);
    $freshSync = new ChecklistSync($db, $prefix, $privateRoot, $freshEnvelope['server_receipt_time']);
    $freshResult = $freshSync->accept($legacyId, $actor, $fresh);
    $freshProjection = photoRevokeProjection($freshSync->projection($legacyId));
    $fingerprints['already_revoked'] = ['before' => $before, 'after' => photoRevokeFingerprint($db, $prefix, $privateRoot)];

    $reuploadEnvelope = $fixture['envelopes']['identical_reupload'];
    $reupload = $base + [
        'clientOperationId' => $reuploadEnvelope['client_operation_id'], 'type' => 'photo_uploaded',
        'deviceTime' => $reuploadEnvelope['device_time'], 'baseRevision' => (int) $reuploadEnvelope['base_revision'],
        'sha256' => $fixture['png']['sha256'], 'mime' => $fixture['png']['mime'],
        'size' => (int) $fixture['png']['size'], 'originalName' => $fixture['png']['filename'],
    ];
    $before = photoRevokeFingerprint($db, $prefix, $privateRoot);
    $sqlException = null;
    $reuploadSync = new ChecklistSync($db, $prefix, $privateRoot, $reuploadEnvelope['server_receipt_time']);
    try {
        $reuploadSync->accept($legacyId, $actor, $reupload, $bytes);
    } catch (mysqli_sql_exception $exception) {
        $sqlException = ['sqlstate' => $exception->getSqlState(), 'vendor_code' => $exception->getCode()];
    }
    $reuploadProjection = photoRevokeProjection($reuploadSync->projection($legacyId));
    $fingerprints['identical_reupload'] = ['before' => $before, 'after' => photoRevokeFingerprint($db, $prefix, $privateRoot)];

    $audit = [
        'protocol_version' => 1,
        'run_token' => $runToken,
        'fixture' => $fixture,
        'accept_call_count' => 5,
        'projection_call_count' => 5,
        'scenarios' => [
            'upload_then_revoke' => [
                'accept_calls' => [
                    ['kind' => 'photo_uploaded', 'operation_id' => $uploadEnvelope['client_operation_id'], 'result' => $uploadResult],
                    ['kind' => 'photo_revoked', 'operation_id' => $revokeEnvelope['client_operation_id'], 'result' => $revokeResult],
                ],
                'upload_projection' => $uploadProjection,
                'projection' => $revokeProjection,
                'sql' => ['revision' => $revokeProjection['revision'], 'photos' => $photos, 'operations' => $history],
                'blob' => $blobs,
                'history' => $history,
            ],
            'replay' => [
                'accept_calls' => [['kind' => 'photo_revoked', 'operation_id' => $revokeEnvelope['client_operation_id'], 'result' => $replayResult]],
                'projection' => $replayProjection,
                'fingerprint_unchanged' => $fingerprints['replay']['before'] === $fingerprints['replay']['after'],
            ],
            'already_revoked' => [
                'accept_calls' => [['kind' => 'photo_revoked', 'operation_id' => $freshEnvelope['client_operation_id'], 'result' => ['status' => $freshResult['status'] ?? null]]],
                'projection' => $freshProjection,
                'fingerprint_unchanged' => $fingerprints['already_revoked']['before'] === $fingerprints['already_revoked']['after'],
            ],
            'identical_reupload' => [
                'accept_calls' => [['kind' => 'photo_uploaded', 'operation_id' => $reuploadEnvelope['client_operation_id'], 'exception' => $sqlException]],
                'projection' => $reuploadProjection,
                'fingerprint_unchanged' => $fingerprints['identical_reupload']['before'] === $fingerprints['identical_reupload']['after'],
                'blob' => photoRevokeBlobs($privateRoot),
            ],
        ],
        'zero_mutation_fingerprints' => $fingerprints,
    ];
    photoRevokeAssert($sqlException === ['sqlstate' => '23000', 'vendor_code' => 1062], 'identical re-upload did not throw the characterized SQL uniqueness failure');
    foreach ($fingerprints as $pair) {
        photoRevokeAssert($pair['before'] === $pair['after'], 'zero-mutation scenario changed owned state');
    }
    $auditJson = json_encode($audit, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    photoRevokeAssert(file_put_contents($auditFile, $auditJson, LOCK_EX) === strlen($auditJson), 'audit evidence could not be written');

    $milestones =
        "PHOTO_REVOKE accepted revision=2 active=0 photo_rows=1 revoked_rows=1 operations=2 blobs=1\n"
        . "PHOTO_REVOKE replay duplicate revision=2 active=0 mutations=0\n"
        . "PHOTO_REVOKE already-revoked rejected revision=2 active=0 mutations=0\n"
        . "PHOTO_REVOKE identical-reupload sql-unique-violation revision=2 active=0 mutations=0 blobs=1\n";
    photoRevokeAssert(hash('sha256', $milestones) === PHOTO_REVOKE_TRANSCRIPT_HASH, 'milestone transcript drifted');
    echo $milestones;
    echo 'CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001 transcript_sha256=' . PHOTO_REVOKE_TRANSCRIPT_HASH . "\n";
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
        photoRevokeRemoveTree($privateRoot);
    }
}

if ($failure !== null) {
    fwrite(STDERR, $failure . "\n");
    exit($exit);
}
