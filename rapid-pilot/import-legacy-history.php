<?php

declare(strict_types=1);

require_once __DIR__ . '/legacy-migration/LegacyHistoryMigration.php';

function migrationEnv(string $name): string { $v = getenv($name); if (!is_string($v) || $v === '') throw new RuntimeException("Missing {$name}"); return $v; }

$options = getopt('', ['object-id:', 'cutoff:', 'apply']);
$objectId = filter_var($options['object-id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$cutoff = (string)($options['cutoff'] ?? '');
if ($objectId === false || $cutoff === '') throw new InvalidArgumentException('Usage: php import-legacy-history.php --object-id=N --cutoff="Y-m-d H:i:s" [--apply]');
$apply = array_key_exists('apply', $options);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$source = new mysqli(getenv('FMONITOR_SOURCE_HOST') ?: '127.0.0.1', migrationEnv('FMONITOR_SOURCE_USER'), migrationEnv('FMONITOR_SOURCE_PASSWORD'), getenv('FMONITOR_SOURCE_NAME') ?: 'c1_fmonitor', (int)(getenv('FMONITOR_SOURCE_PORT') ?: 13306));
$source->set_charset('utf8mb4');
$snapshot = (new LegacyHistoryMySqlSource($source))->extract((int)$objectId, $cutoff);
$result = ['mode' => $apply ? 'apply' : 'dry-run', 'legacyObjectId' => (int)$objectId, 'contentSha256' => $snapshot['contentSha256'], 'counts' => $snapshot['counts'], 'issues' => $snapshot['issues']];
if ($apply) {
    $manifest = json_decode((string)file_get_contents(migrationEnv('FMONITOR_PILOT_ACTIVE_MANIFEST')), true, flags: JSON_THROW_ON_ERROR);
    $prefix = (string)($manifest['processPrefix'] ?? '');
    $target = new mysqli(getenv('FMONITOR_DB_HOST') ?: '127.0.0.1', migrationEnv('FMONITOR_DB_USER'), migrationEnv('FMONITOR_DB_PASSWORD'), migrationEnv('FMONITOR_DB_NAME'), (int)(getenv('FMONITOR_DB_PORT') ?: 23306));
    $target->set_charset('utf8mb4');
    $result += (new LegacyHistoryMySqlTarget($target, $prefix))->apply($snapshot, (int)$objectId, $cutoff, gmdate('Y-m-d H:i:s'));
}
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
