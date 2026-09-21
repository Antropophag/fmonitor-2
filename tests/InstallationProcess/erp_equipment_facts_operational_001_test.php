<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\ErpEquipmentFactsDelivery;

$commands = [];
$owner = static function (array $command) use (&$commands): array {
    $commands[] = $command;
    return $command['kind'] === 'failed'
        ? ['status' => 'failed', 'runId' => $command['runId'], 'reason' => $command['reason']]
        : ['status' => 'completed', 'runId' => $command['runId'], 'matched' => 0,
            'changed' => 0, 'unchanged' => 0, 'unmatched' => 0, 'ambiguous' => 0];
};

$failed = ErpEquipmentFactsDelivery::run(
    static fn(): never => throw new RuntimeException('sqlsrv:Server=private password=private SELECT secret-row'),
    $owner,
    '31313131-3131-4131-8131-313131313131',
    '2026-09-21T09:00:00.000000Z',
);
assertSameValue([
    'status' => 'failed',
    'runId' => '31313131-3131-4131-8131-313131313131',
    'reason' => 'SOURCE_UNAVAILABLE',
], $failed, 'SOURCE_UNAVAILABLE is recorded through the application owner');
assertSameValue('failed', $commands[0]['kind'] ?? null, 'failed run uses the same public owner seam');
assertSameValue(['actor', 'kind', 'runId', 'observedAtUtc', 'reason'], array_keys($commands[0]),
    'failed command has exact safe contract');
$serialized = json_encode([$commands, $failed], JSON_THROW_ON_ERROR);
foreach (['sqlsrv:', 'private', 'SELECT ', 'secret-row'] as $forbidden) {
    assertSameValue(false, str_contains($serialized, $forbidden), 'failed command/receipt excludes ' . $forbidden);
}

$completed = ErpEquipmentFactsDelivery::run(
    static fn(): array => ['status' => 'complete', 'records' => []],
    $owner,
    '32323232-3232-4232-8232-323232323232',
    '2026-09-21T10:00:00.000000Z',
);
assertSameValue('completed', $completed['status'] ?? null, 'successful canonical run completes');
assertSameValue('complete', $commands[1]['kind'] ?? null, 'successful run uses the same public owner seam');

echo "PASS: ERP-EQUIPMENT-FACTS-001 operational application receipts\n";
