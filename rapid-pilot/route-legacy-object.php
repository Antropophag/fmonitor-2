<?php

declare(strict_types=1);

require_once __DIR__ . '/legacy-migration/LegacyMigrationRouter.php';

function requiredEnv(string $name): string { $value = getenv($name); if (!is_string($value) || $value === '') throw new InvalidArgumentException('CONFIGURATION_INVALID'); return $value; }

try {
    $options = getopt('', ['object-id:', 'cutover:', 'apply-baseline']);
    $objectId = filter_var($options['object-id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $cutover = (string)($options['cutover'] ?? '');
    if ($objectId === false || $cutover === '') throw new InvalidArgumentException('CONFIGURATION_INVALID');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $source = new mysqli(getenv('FMONITOR_SOURCE_HOST') ?: '127.0.0.1', requiredEnv('FMONITOR_SOURCE_USER'), requiredEnv('FMONITOR_SOURCE_PASSWORD'), getenv('FMONITOR_SOURCE_NAME') ?: 'c1_fmonitor', (int)(getenv('FMONITOR_SOURCE_PORT') ?: 13306));
    $source->set_charset('utf8mb4'); $row = (new LegacyObjectMySqlClassificationSource($source))->read($objectId, $cutover); $source->close();
    $classification = LegacyObjectClassification::classify($row); $result = LegacyMigrationRoute::decide($classification);
    $output = ['ok' => true, 'mode' => isset($options['apply-baseline']) ? 'apply' : 'dry-run', 'legacyObjectId' => $objectId] + $result;
    if (isset($options['apply-baseline'])) {
        if ($result['applyBlocked']) throw new DomainException('QUARANTINED_EVIDENCE');
        if ($result['route'] !== 'cutover_baseline') throw new DomainException('ROUTE_REQUIRES_DEDICATED_IMPORTER');
        $target = new mysqli(getenv('FMONITOR_DB_HOST') ?: '127.0.0.1', requiredEnv('FMONITOR_DB_USER'), requiredEnv('FMONITOR_DB_PASSWORD'), requiredEnv('FMONITOR_DB_NAME'), (int)(getenv('FMONITOR_DB_PORT') ?: 23306));
        $target->set_charset('utf8mb4'); $output += (new LegacyActiveBaselineTarget($target, requiredEnv('FMONITOR_PROCESS_TABLE_PREFIX')))->apply($row, $classification, $cutover, gmdate('Y-m-d H:i:s')); $target->close();
    }
    echo json_encode($output, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
} catch (Throwable $error) {
    $reason = in_array($error->getMessage(), ['QUARANTINED_EVIDENCE','ROUTE_REQUIRES_DEDICATED_IMPORTER','BASELINE_ALREADY_EXISTS_WITH_DIFFERENT_CONTENT'], true) ? $error->getMessage() : 'MIGRATION_ROUTING_UNAVAILABLE';
    echo json_encode(['ok' => false, 'reason' => $reason], JSON_THROW_ON_ERROR), "\n"; exit(2);
}
