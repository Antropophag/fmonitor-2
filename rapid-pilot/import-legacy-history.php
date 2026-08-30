<?php

declare(strict_types=1);

require_once __DIR__ . '/legacy-migration/LegacyHistoryMigration.php';
require_once __DIR__ . '/legacy-migration/LegacyMigrationRouter.php';

set_exception_handler(static function(Throwable $error):never {
    $allowed=['HISTORY_ROUTE_NOT_ALLOWED','HISTORY_EVIDENCE_QUARANTINED','PROVENANCE_CONFLICT'];
    $reason=in_array($error->getMessage(),$allowed,true)?$error->getMessage():'HISTORY_IMPORT_UNAVAILABLE';
    echo json_encode(['ok'=>false,'reason'=>$reason],JSON_THROW_ON_ERROR),PHP_EOL;exit(2);
});

function migrationEnv(string $name): string { $v = getenv($name); if (!is_string($v) || $v === '') throw new RuntimeException("Missing {$name}"); return $v; }

$options = getopt('', ['object-id:', 'cutoff:', 'apply']);
$objectId = filter_var($options['object-id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$cutoff = (string)($options['cutoff'] ?? '');
if ($objectId === false || $cutoff === '') throw new InvalidArgumentException('Usage: php import-legacy-history.php --object-id=N --cutoff="Y-m-d H:i:s" [--apply]');
$apply = array_key_exists('apply', $options);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$source = new mysqli(getenv('FMONITOR_SOURCE_HOST') ?: '127.0.0.1', migrationEnv('FMONITOR_SOURCE_USER'), migrationEnv('FMONITOR_SOURCE_PASSWORD'), getenv('FMONITOR_SOURCE_NAME') ?: 'c1_fmonitor', (int)(getenv('FMONITOR_SOURCE_PORT') ?: 13306));
$source->set_charset('utf8mb4');
$source->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
$source->query('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
$classificationRow = (new LegacyObjectMySqlClassificationSource($source))->read((int)$objectId, $cutoff, false);
$classification = LegacyObjectClassification::classify($classificationRow);
$route = LegacyMigrationRoute::decide($classification);
if ($route['route'] !== 'historical_reconstruction' || $route['applyBlocked']) throw new DomainException('HISTORY_ROUTE_NOT_ALLOWED');
$snapshot = (new LegacyHistoryMySqlSource($source))->extract((int)$objectId, $cutoff);
$source->commit();
$result = ['mode' => $apply ? 'apply' : 'dry-run', 'legacyObjectId' => (int)$objectId, 'route'=>$route['route'],'classification'=>$classification,'contentSha256' => $snapshot['contentSha256'], 'counts' => $snapshot['counts'], 'issues' => $snapshot['issues']];
if ($apply) {
    if ($snapshot['issues'] !== []) throw new DomainException('HISTORY_EVIDENCE_QUARANTINED');
    $manifest = json_decode((string)file_get_contents(migrationEnv('FMONITOR_PILOT_ACTIVE_MANIFEST')), true, flags: JSON_THROW_ON_ERROR);
    $prefix = (string)($manifest['processPrefix'] ?? '');
    $target = new mysqli(getenv('FMONITOR_DB_HOST') ?: '127.0.0.1', migrationEnv('FMONITOR_DB_USER'), migrationEnv('FMONITOR_DB_PASSWORD'), migrationEnv('FMONITOR_DB_NAME'), (int)(getenv('FMONITOR_DB_PORT') ?: 23306));
    $target->set_charset('utf8mb4');
    $now=gmdate('Y-m-d H:i:s');
    $result += (new LegacyHistoryMySqlTarget($target, $prefix))->apply($snapshot, (int)$objectId, $cutoff, $now);
    $result += (new MigrationClassificationProvenanceTarget($target,$prefix))->reconcile('historical_snapshot',(int)$objectId,(int)$result['snapshotId'],$cutoff,$classification,$now);
}
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
