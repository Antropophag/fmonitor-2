<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInspectionEvidenceEnvironment.php';

use FMonitor2\InspectionEvidence\CompleteInspectionItem;
use FMonitor2\InspectionEvidence\InspectionEvidence;
use FMonitor2\Tests\Support\InMemoryInspectionEvidenceEnvironment;

// Specification: INSPECTION-ITEM-COMPLETE-001 v0.1, examples E and stale revision contract.

$environment = new InMemoryInspectionEvidenceEnvironment('2026-09-01T09:05:00+03:00');
$environment->seedActor(7301, true, ['inspection.item.complete']);
$environment->seedTemplate(9101, ['version' => 1, 'sha256' => str_repeat('a', 64), 'items' => [1 => [28, 29]]]);
$environment->seedInstaller(1042, ['tabId' => 1042, 'fullName' => 'Иванов Иван Иванович', 'position' => 'Электромеханик']);
$environment->seedInstaller(2048, ['tabId' => 2048, 'fullName' => 'Петров Пётр Петрович', 'position' => 'Электромеханик']);
$environment->seedCase(4512, [
    'state' => 'working', 'revision' => 0, 'templateId' => 9101,
    'assignedControlEngineerUserId' => 7302,
    'registeredInstallerTabIds' => [1042, 2048], 'itemCompletions' => [],
]);
$recording = new InspectionEvidence($environment);
$accepted = new CompleteInspectionItem(
    7301, 4512,
    '11111111-1111-4111-8111-111111111111',
    '22222222-2222-4222-8222-222222222222',
    '2026-09-01T08:55:00+03:00',
    0, 1, 28, [1042],
);
assertSameValue('ACCEPTED', $recording->completeItem($accepted)->status, 'Fixture command must be accepted once.');

$payloadConflict = new CompleteInspectionItem(
    7301, 4512, $accepted->clientOperationId,
    '22222222-2222-4222-8222-222222222222',
    '2026-09-01T08:55:00+03:00',
    0, 1, 28, [2048],
);
assertSameValue('OPERATION_PAYLOAD_CONFLICT', $recording->completeItem($payloadConflict)->status, 'One operation id cannot bind changed installer attribution.');
assertSameValue([1042], array_map(
    static fn (object $installer): int => $installer->tabId,
    $recording->getItemCompletion(4512, $accepted->clientOperationId)?->installerSnapshots ?? [],
), 'Payload conflict leaves original evidence unchanged.');

$stale = new CompleteInspectionItem(
    7301, 4512,
    '55555555-5555-4555-8555-555555555555',
    '22222222-2222-4222-8222-222222222222',
    '2026-09-01T09:04:00+03:00',
    0, 1, 29, [1042],
);
$staleResult = $recording->completeItem($stale);
assertSameValue('STALE_REVISION', $staleResult->status, 'A new operation must present the exact current revision.');
assertSameValue(1, $staleResult->revision, 'Stale result exposes the locked current revision.');
assertSameValue(null, $recording->getItemCompletion(4512, $stale->clientOperationId), 'Stale loser has no evidence facts.');

fwrite(STDOUT, "PASS: INSPECTION-ITEM-COMPLETE-001 conflict and stale revision\n");
