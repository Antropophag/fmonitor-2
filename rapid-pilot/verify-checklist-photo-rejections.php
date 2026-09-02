<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/PilotHttp/PilotHttp.php';
require_once dirname(__DIR__) . '/app/PilotHttp/ChecklistSync.php';

use FMonitor2\PilotHttp\ChecklistSync;
use FMonitor2\PilotHttp\HttpUser;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const PHOTO_REJECTION_HASH = '431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460';
const PHOTO_REJECTION_TRANSCRIPT_HASH = 'd81a8b99ece0cfff99f32e0f5f535369349c6cce48fc6898ba7e7f193dc055b9';

function photoRejectionAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function photoRejectionRemoveTree(string $path): void
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

$runToken = getenv('FMONITOR_PHOTO_REJECTION_VERIFY_RUN_TOKEN');
if (!is_string($runToken) || preg_match('/\A[a-f0-9]{12}\z/D', $runToken) !== 1) {
    fwrite(STDERR, "SETUP_FAILURE: photo-rejection verifier run token is invalid\n");
    exit(2);
}

$artifactRoot = getenv('FMONITOR_PHOTO_REJECTION_VERIFY_ARTIFACT_ROOT');
$artifactRootReal = is_string($artifactRoot) ? realpath($artifactRoot) : false;
$artifactRootInfo = is_string($artifactRoot) ? @lstat($artifactRoot) : false;
if (!is_string($artifactRoot) || $artifactRoot === '' || $artifactRoot[0] !== '/'
    || str_contains($artifactRoot, "\0") || !is_string($artifactRootReal)
    || $artifactRoot !== $artifactRootReal || !is_array($artifactRootInfo)
    || is_link($artifactRoot) || !is_dir($artifactRoot)
    || $artifactRootReal === '/tmp' || str_starts_with($artifactRootReal, '/tmp/')) {
    fwrite(STDERR, "SETUP_FAILURE: supplied photo-rejection artifact root is unsafe\n");
    exit(2);
}

$prefix = 'pj_' . $runToken . '_';
$privateRoot = $artifactRoot . '/photo-reject-' . $runToken;
$tables = [
    'fm2_checklist_operation_installers', 'fm2_checklist_photos', 'fm2_checklist_operations',
    'fm2_checklist_revisions', 'fm2_checklist_template_associations', 'fm2_checklist_template_snapshots',
    'fm2_workforce_catalog', 'fm2_order_installers', 'fm2_assignment_orders', 'fm2_installation_cases',
];
$db = null;
$exit = 0;
$failure = null;
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
        throw new UnexpectedValueException('SETUP_FAILURE: photo-rejection verifier owned namespace is occupied', 2);
    }
    if (!mkdir($privateRoot, 0700)) {
        throw new UnexpectedValueException('SETUP_FAILURE: private photo-rejection artifact directory cannot be created', 2);
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

        $templateHash = str_repeat('a', 64);
        $db->query("INSERT INTO `{$prefix}fm2_installation_cases` VALUES(71,1701,'working'),(72,1702,'working'),(73,1703,'working'),(74,1704,'working')");
        $db->query("INSERT INTO `{$prefix}fm2_checklist_template_snapshots` VALUES(91,'photo-rejection-v1','2026-08-01 00:00:00','{$templateHash}')");
        foreach ([71, 72, 73, 74] as $caseId) {
            $db->query("INSERT INTO `{$prefix}fm2_checklist_template_associations` VALUES('operational_case','{$caseId}','2026-08-02 00:00:00',91,'photo-rejection-v1','{$templateHash}')");
            $db->query("INSERT INTO `{$prefix}fm2_checklist_revisions` VALUES({$caseId},0,'2026-08-30T10:00:01+00:00')");
        }
    } catch (Throwable $exception) {
        throw new UnexpectedValueException(
            'SETUP_FAILURE: photo-rejection verification fixtures cannot be created: ' . $exception->getMessage(),
            2,
            $exception,
        );
    }

    $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
    photoRejectionAssert(is_string($bytes) && strlen($bytes) === 68
        && (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes) === 'image/png'
        && hash('sha256', $bytes) === PHOTO_REJECTION_HASH, 'approved PNG fixture drifted');

    $actor = new HttpUser(901, 'Photo rejection verifier', 'photo-rejection-verifier@example.invalid');
    $base = [
        'deviceInstallationId' => '22222222-2222-4222-8222-222222222222',
        'type' => 'photo_uploaded', 'deviceTime' => '2026-08-30T10:00:00+00:00',
        'baseRevision' => 0, 'sectionId' => 3, 'sha256' => PHOTO_REJECTION_HASH,
        'mime' => 'image/png', 'size' => 68, 'originalName' => 'section-3-evidence.png',
    ];
    $scenarios = [
        ['mime-content-mismatch', 1701, '11111111-1111-4111-8111-111111111111', ['mime' => 'image/jpeg']],
        ['size-mismatch', 1702, '33333333-3333-4333-8333-333333333333', ['size' => 67]],
        ['hash-mismatch', 1703, '44444444-4444-4444-8444-444444444444', ['sha256' => str_repeat('0', 64)]],
        ['invalid-name', 1704, '55555555-5555-4555-8555-555555555555', ['originalName' => "section-3\nevidence.png"]],
    ];
    $sync = new ChecklistSync($db, $prefix, $privateRoot, '2026-08-30T10:00:01+00:00');
    $milestones = '';
    foreach ($scenarios as [$label, $objectId, $operationId, $change]) {
        $operation = array_replace($base, $change, ['clientOperationId' => $operationId]);
        $result = $sync->accept($objectId, $actor, $operation, $bytes);
        $projection = $sync->projection($objectId);
        $operationCount = (int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_checklist_operations`")->fetch_assoc()['n'];
        $photoCount = (int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_checklist_photos`")->fetch_assoc()['n'];
        $revisionSum = (int) $db->query("SELECT SUM(revision_no) n FROM `{$prefix}fm2_checklist_revisions`")->fetch_assoc()['n'];
        $blobs = count(glob($privateRoot . '/checklist/*.bin') ?: []);
        photoRejectionAssert(($result['status'] ?? null) === 'rejected', "{$label} was not rejected");
        photoRejectionAssert($projection['revision'] === 0 && $projection['photos'] === [], "{$label} changed public projection");
        photoRejectionAssert($operationCount === 0 && $photoCount === 0 && $revisionSum === 0 && $blobs === 0, "{$label} created a mutation");
        $line = "PHOTO_REJECTION {$label} rejected revision=0 active=0 blobs=0\n";
        echo $line;
        $milestones .= $line;
    }
    photoRejectionAssert(hash('sha256', $milestones) === PHOTO_REJECTION_TRANSCRIPT_HASH, 'milestone transcript drifted');
    echo 'CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001 transcript_sha256=' . PHOTO_REJECTION_TRANSCRIPT_HASH . "\n";
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
        photoRejectionRemoveTree($privateRoot);
    }
}

if ($failure !== null) {
    fwrite(STDERR, $failure . "\n");
    exit($exit);
}
