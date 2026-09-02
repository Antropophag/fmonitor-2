<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInspectionEvidenceEnvironment.php';

use FMonitor2\InspectionEvidence\CompleteInspectionItem;
use FMonitor2\InspectionEvidence\InspectionEvidence;
use FMonitor2\InspectionEvidence\InspectionEvidenceView;
use FMonitor2\InspectionEvidence\InspectionRecording;
use FMonitor2\Tests\Support\InMemoryInspectionEvidenceEnvironment;

// Specification: INSPECTION-ITEM-COMPLETE-001 v0.1, acceptance example A.

$requiredTypes = [
    InspectionRecording::class,
    InspectionEvidenceView::class,
    CompleteInspectionItem::class,
    InspectionEvidence::class,
];

foreach ($requiredTypes as $requiredType) {
    if (!class_exists($requiredType) && !interface_exists($requiredType)) {
        throw new TestFailure(
            'INSPECTION-ITEM-COMPLETE-001 approved public application seam is missing: '
            . $requiredType,
        );
    }
}

$environment = new InMemoryInspectionEvidenceEnvironment('2026-09-01T09:05:00+03:00');
$environment->seedActor(7301, true, ['inspection.item.complete']);
$environment->seedTemplate(9101, [
    'version' => 1,
    'sha256' => str_repeat('a', 64),
    'items' => [1 => [28]],
]);
$environment->seedInstaller(1042, [
    'tabId' => 1042,
    'fullName' => 'Иванов Иван Иванович',
    'position' => 'Электромеханик по лифтам',
]);
$environment->seedCase(4512, [
    'state' => 'working',
    'revision' => 0,
    'templateId' => 9101,
    'assignedControlEngineerUserId' => 7302,
    'registeredInstallerTabIds' => [1042],
    'itemCompletions' => [],
]);

$recording = new InspectionEvidence($environment);
assertSameValue(true, $recording instanceof InspectionRecording, 'Command object must expose InspectionRecording.');
assertSameValue(true, $recording instanceof InspectionEvidenceView, 'Evidence query must expose InspectionEvidenceView.');

$result = $recording->completeItem(new CompleteInspectionItem(
    actorUserId: 7301,
    installationCaseId: 4512,
    clientOperationId: '11111111-1111-4111-8111-111111111111',
    deviceInstallationId: '22222222-2222-4222-8222-222222222222',
    deviceTime: '2026-09-01T08:55:00+03:00',
    expectedRevision: 0,
    sectionId: 1,
    itemId: 28,
    installerTabIds: [1042],
));

assertSameValue('ACCEPTED', $result->status, 'A capable engineer may complete an item on a colleague\'s object.');
assertSameValue(1, $result->revision, 'First completion advances revision from 0 to 1.');

$evidence = $recording->getItemCompletion(4512, '11111111-1111-4111-8111-111111111111');
assertSameValue(7301, $evidence?->actorUserId, 'Evidence preserves the actual actor.');
assertSameValue(7302, $evidence?->assignedControlEngineerUserIdAtReceipt, 'Evidence separately preserves the assigned engineer.');
assertSameValue(1, $evidence?->acceptedRevision, 'Evidence exposes the accepted revision.');
assertSameValue([1042], array_map(
    static fn (object $installer): int => $installer->tabId,
    $evidence?->installerSnapshots ?? [],
), 'Evidence contains exactly the selected installer snapshot.');

fwrite(STDOUT, "PASS: INSPECTION-ITEM-COMPLETE-001 example A\n");
