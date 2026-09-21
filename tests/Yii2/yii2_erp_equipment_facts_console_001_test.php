<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__, 2) . '/app/autoload.php';

use FMonitor2\Jobs\JobsRuntimeConfiguration;
use FMonitor2\YiiRuntime\Commands\ErpEquipmentFactsSyncController;

$root = dirname(__DIR__, 2);
$dockerfile = file_get_contents($root . '/deploy/runtime/Dockerfile');
$compose = file_get_contents($root . '/deploy/runtime/compose.yaml');
assertSameValue(true, str_contains($dockerfile, 'pdo_sqlsrv'), 'production image contains SQL Server PDO driver');
foreach (['FMONITOR_ERP_HOST', 'FMONITOR_ERP_DATABASE', 'FMONITOR_ERP_USER', 'FMONITOR_ERP_PASSWORD', 'FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY'] as $key) {
    assertSameValue(true, str_contains($compose, $key), 'jobs worker receives direct ERP configuration ' . $key);
}
foreach (['FMONITOR_ERP_PASSWORD_FILE', 'FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY_FILE'] as $key) {
    assertSameValue(false, str_contains($compose, $key), 'obsolete ERP secret file removed ' . $key);
}

$seen = [];
$delivery = static fn(): array => ['status' => 'complete', 'records' => [[
    'sourceOrderNumber' => 'ORD', 'readinessDate' => null, 'firstShipmentDate' => null, 'fullShipmentDate' => null,
]]];
$owner = static function (array $command) use (&$seen): array {
    $seen[] = $command;
    return $command['kind'] === 'failed'
        ? ['status' => 'failed', 'runId' => $command['runId'], 'reason' => $command['reason']]
        : ['status' => 'completed', 'runId' => $command['runId'], 'matched' => 1, 'changed' => 0,
            'unchanged' => 1, 'unmatched' => 0, 'ambiguous' => 0];
};
$result = ErpEquipmentFactsSyncController::runWith($delivery, $owner,
    '99999999-9999-4999-8999-999999999999', '2026-09-15T12:00:00.000000Z');
assertSameValue(['status' => 'completed', 'runId' => '99999999-9999-4999-8999-999999999999',
    'matched' => 1, 'changed' => 0, 'unchanged' => 1, 'unmatched' => 0, 'ambiguous' => 0], $result,
    'safe CLI result exposes run identity without source rows');
assertSameValue(['system', 'erp-equipment-facts-hourly-v1', 'complete'],
    [$seen[0]['actor']['type'], $seen[0]['actor']['id'], $seen[0]['kind']], 'canonical owner composition');
assertSameValue(true, str_contains(file_get_contents($root . '/config/yii/console.php'), 'erp-equipment-facts-sync'), 'Yii command registered');
$failed = ErpEquipmentFactsSyncController::runWith(static fn(): array => ['status' => 'failed', 'reason' => 'SOURCE_UNAVAILABLE'],
    $owner, 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '2026-09-15T12:00:00.000000Z');
assertSameValue(['status' => 'failed', 'runId' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'reason' => 'SOURCE_UNAVAILABLE'],
    $failed, 'safe failed output includes run identity');

$private = 'PRIVATE-ORDER-SECRET';
$thrown = ErpEquipmentFactsSyncController::runWith(static function () use ($private): never {
    throw new RuntimeException('PDO password SELECT ' . $private);
}, $owner, 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaab', '2026-09-15T13:00:00.000000Z');
assertSameValue(['status' => 'failed', 'runId' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaab', 'reason' => 'SOURCE_UNAVAILABLE'],
    $thrown, 'technical exception safely normalized with run identity');
assertSameValue(false, str_contains(json_encode([$failed, $thrown], JSON_THROW_ON_ERROR), $private), 'no exception/source leakage');

$names = [
    'FMONITOR_DB_HOST' => '127.0.0.1', 'FMONITOR_DB_PORT' => '1', 'FMONITOR_DB_NAME' => 'unreachable',
    'FMONITOR_DB_USER' => 'none', 'FMONITOR_DB_PASSWORD' => 'none', 'FMONITOR_PROCESS_TABLE_PREFIX' => 'erp_console_',
    'FMONITOR_ERP_HOST' => 'erp.invalid', 'FMONITOR_ERP_DATABASE' => '1c-erp', 'FMONITOR_ERP_USER' => 'reader',
    'FMONITOR_ERP_PASSWORD' => 'synthetic', 'FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY' => str_repeat('h', 40),
    'FMONITOR_ERP_EQUIPMENT_FACTS_MAX_ROWS' => '500', 'FMONITOR_ERP_EQUIPMENT_FACTS_TIMEOUT_SECONDS' => '5',
    'FMONITOR_ERP_EQUIPMENT_FACTS_CHUNK_SIZE' => '100',
];
foreach ($names as $name => $value) putenv($name . '=' . $value);
try {
    $productionFailed = ErpEquipmentFactsSyncController::runJob(JobsRuntimeConfiguration::fromEnvironment());
    assertSameValue(['failed', 'SOURCE_UNAVAILABLE'], [$productionFailed['status'], $productionFailed['reason']],
        'production composition access failure safe');
    assertSameValue(1, preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $productionFailed['runId'] ?? ''),
        'production composition failure retains invocation run identity');
} finally { foreach ($names as $name => $_) putenv($name); }

echo "PASS: ERP-EQUIPMENT-FACTS-001 console\n";
