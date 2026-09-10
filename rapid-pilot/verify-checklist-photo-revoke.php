<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/PilotHttp/PilotHttp.php';
require_once dirname(__DIR__) . '/app/PilotHttp/ChecklistSync.php';
require_once dirname(__DIR__) . '/app/autoload.php';

use FMonitor2\PilotHttp\ChecklistSync;
use FMonitor2\PilotHttp\HttpUser;
require_once dirname(__DIR__).'/tests/Support/InspectionPhotoIdentityFixture.php';
use FMonitor2\Tests\Support\InspectionPhotoIdentityFixture;
use FMonitor2\InstallationProcess\InspectionPhotoContentIndexSchemaMigration;
use FMonitor2\InstallationProcess\InspectionEvidenceSchemaMigration;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const PHOTO_REVOKE_TRANSCRIPT_HASH = '09b498760d9ed265d35372bdd6630e1959bb3073abf2ac2692ef0371d0ccfe0c';

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
$tables = array_merge(InspectionPhotoIdentityFixture::tables(), $tables);
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
    InspectionPhotoIdentityFixture::seed($db,$prefix,(int)$fixture['common']['actor_id'],true);

    if(!class_exists(InspectionPhotoContentIndexSchemaMigration::class))throw new RuntimeException('canonical photo content-index migration v19 is absent');
    $ddl = [
        'fm2_installation_cases' => 'id BIGINT PRIMARY KEY,legacy_installation_object_id BIGINT NOT NULL,process_state VARCHAR(80) NOT NULL',
        'fm2_assignment_orders' => 'id BIGINT PRIMARY KEY,installation_case_id BIGINT NOT NULL,version_no INT NOT NULL,status VARCHAR(40) NOT NULL,control_engineer_user_id BIGINT NOT NULL',
        'fm2_order_installers' => 'assignment_order_id BIGINT NOT NULL,installer_tab_id BIGINT NOT NULL,fio_snapshot VARCHAR(300) NOT NULL,position_snapshot VARCHAR(300) NOT NULL,employment_status_snapshot VARCHAR(40) NOT NULL,workforce_source_updated_at_snapshot VARCHAR(40) NOT NULL',
        'fm2_workforce_catalog' => 'installer_tab_id BIGINT PRIMARY KEY,fio VARCHAR(300) NOT NULL,position VARCHAR(300) NOT NULL,employment_status VARCHAR(40) NOT NULL,dismissal_effective_at VARCHAR(40) NULL,workforce_source_updated_at VARCHAR(40) NOT NULL',
        'fm2_checklist_template_snapshots' => 'id BIGINT PRIMARY KEY,snapshot_version VARCHAR(80) NOT NULL,valid_from DATETIME NOT NULL,content_sha256 CHAR(64) NOT NULL',
        'fm2_checklist_template_associations' => 'subject_kind VARCHAR(40) NOT NULL,subject_id VARCHAR(160) NOT NULL,effective_at DATETIME NOT NULL,template_snapshot_id BIGINT NOT NULL,template_snapshot_version VARCHAR(80) NOT NULL,template_content_sha256 CHAR(64) NOT NULL',
    ];
    try {
        foreach ($ddl as $table => $definition) {
            $db->query("CREATE TABLE `{$prefix}{$table}`({$definition}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        $v8=InspectionEvidenceSchemaMigration::apply($db,$prefix);if(($v8['applied']??null)!==true||($v8['schemaVersion']??null)!==8)throw new RuntimeException('canonical inspection evidence v8 fixture did not apply');
        $migration=InspectionPhotoContentIndexSchemaMigration::apply($db,$prefix);if($migration!==['applied'=>true,'schemaVersion'=>19,'indexesChanged'=>[$prefix.'fm2_checklist_photos.installation_case_id']])throw new RuntimeException('canonical photo content-index migration v19 did not apply exact successor');
        $common = $fixture['common'];
        $caseId = (int) $common['installation_case_db_id'];
        $legacyId = (int) $common['legacy_object_id'];
        $templateHash = str_repeat('a', 64);
        $db->query("INSERT INTO `{$prefix}fm2_installation_cases` VALUES({$caseId},{$legacyId},'working')");
        $db->query("INSERT INTO `{$prefix}fm2_assignment_orders` VALUES(81,{$caseId},1,'registered',".(int)$common['actor_id'].")");
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
    $actor = new HttpUser((int) $common['actor_id'], 'Photo revoke verifier', 'photo-revoke-verifier@example.invalid',['inspection.photo.revoke']);
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
        'photoId' => $photoId,'reason'=>'Replacement evidence required',
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
        'photoId' => $photoId,'reason'=>'Fresh revoke must remain rejected',
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
    $reuploadSync = new ChecklistSync($db, $prefix, $privateRoot, $reuploadEnvelope['server_receipt_time']);
    $reuploadResult = $reuploadSync->accept($legacyId, $actor, $reupload, $bytes);
    $reuploadProjection = photoRevokeProjection($reuploadSync->projection($legacyId));
    $reuploadPhotos = photoRevokePhotos($db, $prefix);
    $reuploadHistory = photoRevokeHistory($db, $prefix);
    $activeEnvelope=$fixture['envelopes']['active_duplicate'];$activeDuplicate=$base+['clientOperationId'=>$activeEnvelope['client_operation_id'],'type'=>'photo_uploaded','deviceTime'=>$activeEnvelope['device_time'],'baseRevision'=>(int)$activeEnvelope['base_revision'],'sha256'=>$fixture['png']['sha256'],'mime'=>$fixture['png']['mime'],'size'=>(int)$fixture['png']['size'],'originalName'=>$fixture['png']['filename']];$before=photoRevokeFingerprint($db,$prefix,$privateRoot);$activeSync=new ChecklistSync($db,$prefix,$privateRoot,$activeEnvelope['server_receipt_time']);$activeResult=$activeSync->accept($legacyId,$actor,$activeDuplicate,$bytes);$activeProjection=photoRevokeProjection($activeSync->projection($legacyId));$fingerprints['active_duplicate']=['before'=>$before,'after'=>photoRevokeFingerprint($db,$prefix,$privateRoot)];

    $audit = [
        'protocol_version' => 1,
        'run_token' => $runToken,
        'fixture' => $fixture,
        'accept_call_count' => 6,
        'projection_call_count' => 6,
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
                'accept_calls' => [['kind' => 'photo_uploaded', 'operation_id' => $reuploadEnvelope['client_operation_id'], 'result' => $reuploadResult]],
                'projection' => $reuploadProjection,
                'photos' => $reuploadPhotos,
                'history' => $reuploadHistory,
                'blob' => photoRevokeBlobs($privateRoot),
            ],
            'active_duplicate'=>['accept_calls'=>[['kind'=>'photo_uploaded','operation_id'=>$activeEnvelope['client_operation_id'],'result'=>$activeResult]],'projection'=>$activeProjection,'fingerprint_unchanged'=>$fingerprints['active_duplicate']['before']===$fingerprints['active_duplicate']['after']],
        ],
        'zero_mutation_fingerprints' => $fingerprints,
    ];
    photoRevokeAssert($reuploadResult === ['status'=>'accepted','revision'=>3], 'identical re-upload was not accepted at revision 3');
    photoRevokeAssert($reuploadProjection['revision']===3&&count($reuploadProjection['photos'])===1, 'identical re-upload projection is not one active photo at revision 3');
    photoRevokeAssert(count($reuploadPhotos)===2&&$reuploadPhotos[0]===$photos[0]&&$reuploadPhotos[1]['id']!==$photoId&&$reuploadPhotos[1]['upload_operation_id']===$reuploadEnvelope['client_operation_id'], 'identical re-upload did not preserve revoked row and append new identity');
    photoRevokeAssert(count($reuploadHistory)===3&&$reuploadHistory[2]['operation_type']==='photo_uploaded'&&$reuploadHistory[2]['accepted_revision']===3, 'identical re-upload history is not append-only revision 3');
    photoRevokeAssert($activeResult===['status'=>'duplicate','revision'=>3]&&$activeProjection===$reuploadProjection&&$fingerprints['active_duplicate']['before']===$fingerprints['active_duplicate']['after'],'active identical photo did not remain idempotent');
    foreach ($fingerprints as $pair) {
        photoRevokeAssert($pair['before'] === $pair['after'], 'zero-mutation scenario changed owned state');
    }
    $auditJson = json_encode($audit, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    photoRevokeAssert(file_put_contents($auditFile, $auditJson, LOCK_EX) === strlen($auditJson), 'audit evidence could not be written');

    $milestones =
        "PHOTO_REVOKE accepted revision=2 active=0 photo_rows=1 revoked_rows=1 operations=2 blobs=1\n"
        . "PHOTO_REVOKE replay duplicate revision=2 active=0 mutations=0\n"
        . "PHOTO_REVOKE already-revoked rejected revision=2 active=0 mutations=0\n"
        . "PHOTO_REVOKE identical-reupload accepted revision=3 active=1 photo_rows=2 revoked_rows=1 operations=3 blobs=1\n";
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
