<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInspectionEvidenceEnvironment.php';
require dirname(__DIR__) . '/Support/InspectionItemCompletionEvidenceProjection.php';

use FMonitor2\InspectionEvidence\CompleteInspectionItem;
use FMonitor2\InspectionEvidence\InspectionEvidence;
use FMonitor2\Tests\Support\InMemoryInspectionEvidenceEnvironment;

// Specification: INSPECTION-ITEM-COMPLETE-001 v0.1, examples D and D2.

$environment = new InMemoryInspectionEvidenceEnvironment('2026-09-01T09:05:00+03:00');
$environment->seedActor(7301, true, ['inspection.item.complete']);
$environment->seedTemplate(9101, ['version' => 1, 'sha256' => str_repeat('a', 64), 'items' => [1 => [28]]]);
$environment->seedTemplate(9201, ['version' => 2, 'sha256' => str_repeat('b', 64), 'items' => [2 => [99]]]);
$environment->seedInstaller(1042, ['tabId' => 1042, 'fullName' => 'Иванов Иван Иванович', 'position' => 'Электромеханик по лифтам']);
$environment->seedInstaller(2048, ['tabId' => 2048, 'fullName' => 'Петров Пётр Петрович', 'position' => 'Электромеханик по лифтам']);
$environment->seedCase(4512, [
    'state' => 'working', 'revision' => 0, 'templateId' => 9101,
    'assignedControlEngineerUserId' => 7302,
    'registeredInstallerTabIds' => [1042], 'itemCompletions' => [],
]);
$recording = new InspectionEvidence($environment);
$command = new CompleteInspectionItem(
    actorUserId: 7301,
    installationCaseId: 4512,
    clientOperationId: '11111111-1111-4111-8111-111111111111',
    deviceInstallationId: '22222222-2222-4222-8222-222222222222',
    deviceTime: '2026-09-01T08:55:00+03:00',
    expectedRevision: 0,
    sectionId: 1,
    itemId: 28,
    installerTabIds: [1042],
);

assertSameValue('ACCEPTED', $recording->completeItem($command)->status, 'Fixture command must be accepted once.');
$original = inspectionItemCompletionEvidenceProjection(
    $recording->getItemCompletion(4512, $command->clientOperationId),
);
assertSameValue(1, $original['currentChecklistRevision'], 'Accepted evidence exposes current case checklist revision 1.');

// Every mutable first-acceptance fact now conflicts with the original command.
$environment->changeCase(4512, [
    'state' => 'closed',
    'templateId' => 9201,
    'assignedControlEngineerUserId' => 7399,
    'registeredInstallerTabIds' => [2048],
]);

$replay = $recording->completeItem($command);
assertSameValue('DUPLICATE', $replay->status, 'Exact replay wins before mutable case/template/crew checks.');
assertSameValue(1, $replay->revision, 'Exact replay returns the original accepted revision.');

$afterReplay = $recording->getItemCompletion(4512, $command->clientOperationId);
assertSameValue($original, inspectionItemCompletionEvidenceProjection($afterReplay), 'Replay and query are read-only over the immutable accepted evidence.');
assertSameValue(7301, $afterReplay?->actorUserId, 'Replay does not replace the actual actor.');
assertSameValue(7302, $afterReplay?->assignedControlEngineerUserIdAtReceipt, 'Replay does not replace assignment-at-receipt audit.');
assertSameValue([1042], array_map(
    static fn (object $installer): int => $installer->tabId,
    $afterReplay?->installerSnapshots ?? [],
), 'Replay does not replace historical installer attribution with current crew.');

fwrite(STDOUT, "PASS: INSPECTION-ITEM-COMPLETE-001 exact replay after mutable changes\n");
