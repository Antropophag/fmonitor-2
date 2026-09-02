<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInspectionEvidenceEnvironment.php';

use FMonitor2\InspectionEvidence\CompleteInspectionItem;
use FMonitor2\InspectionEvidence\InspectionEvidence;
use FMonitor2\Tests\Support\InMemoryInspectionEvidenceEnvironment;

// Specification: INSPECTION-ITEM-COMPLETE-001 v0.1, stable typed rejections.

/** @return array{InMemoryInspectionEvidenceEnvironment, InspectionEvidence} */
function rejectionFixture(): array
{
    $environment = new InMemoryInspectionEvidenceEnvironment('2026-09-01T09:05:00+03:00');
    $environment->seedActor(7301, true, ['inspection.item.complete']);
    $environment->seedTemplate(9101, ['version' => 1, 'sha256' => str_repeat('a', 64), 'items' => [1 => [28]]]);
    $environment->seedInstaller(1042, ['tabId' => 1042, 'fullName' => 'Иванов Иван Иванович', 'position' => 'Электромеханик']);
    $environment->seedCase(4512, [
        'state' => 'working', 'revision' => 0, 'templateId' => 9101,
        'assignedControlEngineerUserId' => 7302,
        'registeredInstallerTabIds' => [1042], 'itemCompletions' => [],
    ]);
    return [$environment, new InspectionEvidence($environment)];
}

/** @param list<int> $installerTabIds */
function rejectionCommand(
    string $operationId = '66666666-6666-4666-8666-666666666666',
    int $caseId = 4512,
    int $sectionId = 1,
    int $itemId = 28,
    array $installerTabIds = [1042],
): CompleteInspectionItem {
    return new CompleteInspectionItem(
        7301, $caseId, $operationId,
        '22222222-2222-4222-8222-222222222222',
        '2026-09-01T08:55:00+03:00',
        0, $sectionId, $itemId, $installerTabIds,
    );
}

$selectedRejection = getenv('FMONITOR_ITEM_TEST_CASE') ?: 'all';
$invalidCommands = [
    'malformed operation UUID' => new CompleteInspectionItem(7301, 4512, 'not-a-uuid', '22222222-2222-4222-8222-222222222222', '2026-09-01T08:55:00+03:00', 0, 1, 28, [1042]),
    'malformed device UUID' => new CompleteInspectionItem(7301, 4512, '10000000-0000-4000-8000-000000000001', 'not-a-uuid', '2026-09-01T08:55:00+03:00', 0, 1, 28, [1042]),
    'timestamp without offset' => new CompleteInspectionItem(7301, 4512, '10000000-0000-4000-8000-000000000002', '22222222-2222-4222-8222-222222222222', '2026-09-01T08:55:00', 0, 1, 28, [1042]),
    'non-positive case id' => new CompleteInspectionItem(7301, 0, '10000000-0000-4000-8000-000000000004', '22222222-2222-4222-8222-222222222222', '2026-09-01T08:55:00+03:00', 0, 1, 28, [1042]),
    'negative revision' => new CompleteInspectionItem(7301, 4512, '10000000-0000-4000-8000-000000000005', '22222222-2222-4222-8222-222222222222', '2026-09-01T08:55:00+03:00', -1, 1, 28, [1042]),
    'non-positive section id' => new CompleteInspectionItem(7301, 4512, '10000000-0000-4000-8000-000000000006', '22222222-2222-4222-8222-222222222222', '2026-09-01T08:55:00+03:00', 0, 0, 28, [1042]),
    'non-positive item id' => new CompleteInspectionItem(7301, 4512, '10000000-0000-4000-8000-000000000007', '22222222-2222-4222-8222-222222222222', '2026-09-01T08:55:00+03:00', 0, 1, 0, [1042]),
    'empty installer list' => new CompleteInspectionItem(7301, 4512, '10000000-0000-4000-8000-000000000008', '22222222-2222-4222-8222-222222222222', '2026-09-01T08:55:00+03:00', 0, 1, 28, []),
    'duplicate installer list' => new CompleteInspectionItem(7301, 4512, '10000000-0000-4000-8000-000000000009', '22222222-2222-4222-8222-222222222222', '2026-09-01T08:55:00+03:00', 0, 1, 28, [1042, 1042]),
    'non-positive installer id' => new CompleteInspectionItem(7301, 4512, '10000000-0000-4000-8000-00000000000a', '22222222-2222-4222-8222-222222222222', '2026-09-01T08:55:00+03:00', 0, 1, 28, [0]),
];
foreach ($invalidCommands as $label => $invalid) {
    if ($selectedRejection === 'duplicate_installer' && $label !== 'duplicate installer list') {
        continue;
    }
    [$environment, $recording] = rejectionFixture();
    assertSameValue('INVALID_COMMAND', $recording->completeItem($invalid)->status, $label . ' has stable INVALID_COMMAND result.');
    assertSameValue(null, $recording->getItemCompletion($invalid->installationCaseId, $invalid->clientOperationId), $label . ' creates no evidence.');
}

if ($selectedRejection === 'duplicate_installer') {
    fwrite(STDOUT, "PASS: INSPECTION-ITEM-COMPLETE-001 duplicate installer rejection\n");
    return;
}

[$environment, $recording] = rejectionFixture();
$environment->setInspectionSchemaAvailable(false);
$schemaUnavailable = rejectionCommand(operationId: '77777777-7777-4777-8777-777777777777');
assertSameValue('INSPECTION_SCHEMA_UNAVAILABLE', $recording->completeItem($schemaUnavailable)->status, 'Missing canonical v8 fails closed.');
assertSameValue(null, $recording->getItemCompletion(4512, $schemaUnavailable->clientOperationId), 'Missing schema creates no evidence.');

[$environment, $recording] = rejectionFixture();
$caseMissing = rejectionCommand(operationId: '88888888-8888-4888-8888-888888888888', caseId: 9999);
assertSameValue('CASE_NOT_FOUND', $recording->completeItem($caseMissing)->status, 'Absent case is distinct from wrong state.');
assertSameValue(null, $recording->getItemCompletion(9999, $caseMissing->clientOperationId), 'Absent case creates no evidence.');

[$environment, $recording] = rejectionFixture();
$environment->changeCase(4512, ['state' => 'closed']);
$caseClosed = rejectionCommand(operationId: '99999999-9999-4999-8999-999999999999');
assertSameValue('CASE_NOT_WORKING', $recording->completeItem($caseClosed)->status, 'Closed case has stable CASE_NOT_WORKING result.');
assertSameValue(null, $recording->getItemCompletion(4512, $caseClosed->clientOperationId), 'Closed case creates no evidence.');

[$environment, $recording] = rejectionFixture();
$environment->changeCase(4512, ['templateId' => 9999]);
$templateMissing = rejectionCommand(operationId: 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
assertSameValue('CHECKLIST_TEMPLATE_UNAVAILABLE', $recording->completeItem($templateMissing)->status, 'Missing immutable template has stable rejection.');
assertSameValue(null, $recording->getItemCompletion(4512, $templateMissing->clientOperationId), 'Missing template creates no evidence.');

[$environment, $recording] = rejectionFixture();
$itemUnknown = rejectionCommand(operationId: 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', itemId: 99);
assertSameValue('CHECKLIST_ITEM_UNKNOWN', $recording->completeItem($itemUnknown)->status, 'Unknown item has stable rejection.');
assertSameValue(null, $recording->getItemCompletion(4512, $itemUnknown->clientOperationId), 'Unknown item creates no evidence.');

[$environment, $recording] = rejectionFixture();
$installerUnassigned = rejectionCommand(operationId: 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', installerTabIds: [2048]);
assertSameValue('INSTALLER_NOT_ASSIGNED', $recording->completeItem($installerUnassigned)->status, 'Unassigned installer has stable rejection.');
assertSameValue(null, $recording->getItemCompletion(4512, $installerUnassigned->clientOperationId), 'Unassigned installer creates no evidence.');

[$environment, $recording] = rejectionFixture();
$environment->seedInstaller(1042, ['tabId' => 1042, 'fullName' => 'Иванов Иван Иванович']);
$snapshotIncomplete = rejectionCommand(operationId: 'dddddddd-dddd-4ddd-8ddd-dddddddddddd');
assertSameValue('INSTALLER_SNAPSHOT_INCOMPLETE', $recording->completeItem($snapshotIncomplete)->status, 'Incomplete personnel evidence has stable rejection.');
assertSameValue(null, $recording->getItemCompletion(4512, $snapshotIncomplete->clientOperationId), 'Incomplete snapshot creates no evidence.');

fwrite(STDOUT, "PASS: INSPECTION-ITEM-COMPLETE-001 stable typed rejections\n");
