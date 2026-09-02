<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInspectionEvidenceEnvironment.php';
require dirname(__DIR__) . '/Support/InspectionItemCompletionEvidenceProjection.php';

use FMonitor2\InspectionEvidence\CompleteInspectionItem;
use FMonitor2\InspectionEvidence\InspectionEvidence;
use FMonitor2\Tests\Support\InMemoryInspectionEvidenceEnvironment;

// Specification: INSPECTION-ITEM-COMPLETE-001 v0.1, installer-id normalization.

/** @return array{InspectionEvidence, CompleteInspectionItem} */
function normalizationFixture(): array
{
    $environment = new InMemoryInspectionEvidenceEnvironment('2026-09-01T09:05:00+03:00');
    $environment->seedActor(7301, true, ['inspection.item.complete']);
    $environment->seedTemplate(9101, ['version' => 1, 'sha256' => str_repeat('a', 64), 'items' => [1 => [28]]]);
    $environment->seedInstaller(1042, ['tabId' => 1042, 'fullName' => 'Иванов Иван Иванович', 'position' => 'Электромеханик']);
    $environment->seedInstaller(2048, ['tabId' => 2048, 'fullName' => 'Петров Пётр Петрович', 'position' => 'Электромеханик']);
    $environment->seedCase(4512, [
        'state' => 'working', 'revision' => 0, 'templateId' => 9101,
        'assignedControlEngineerUserId' => 7302,
        'registeredInstallerTabIds' => [1042, 2048], 'itemCompletions' => [],
    ]);
    return [new InspectionEvidence($environment), new CompleteInspectionItem(
        7301, 4512,
        '12121212-1212-4212-8212-121212121212',
        '22222222-2222-4222-8222-222222222222',
        '2026-09-01T08:55:00+03:00',
        0, 1, 28, [2048, 1042],
    )];
}

$selected = getenv('FMONITOR_ITEM_TEST_CASE') ?: 'all';

if ($selected === 'current_revision') {
    [$recording, $nonCanonical] = normalizationFixture();
    assertSameValue('ACCEPTED', $recording->completeItem($nonCanonical)->status, 'Fixture command is accepted once.');
    $acceptedEvidence = inspectionItemCompletionEvidenceProjection(
        $recording->getItemCompletion(4512, $nonCanonical->clientOperationId),
    );
    assertSameValue(1, $acceptedEvidence['currentChecklistRevision'], 'Accepted query exposes current case checklist revision 1 independently of base/accepted fields.');
}

if ($selected === 'all' || $selected === 'ordered_persistence') {
    [$recording, $nonCanonical] = normalizationFixture();
    assertSameValue('ACCEPTED', $recording->completeItem($nonCanonical)->status, 'A valid two-installer command is accepted.');
    $evidence = $recording->getItemCompletion(4512, $nonCanonical->clientOperationId);
    assertSameValue([1042, 2048], array_map(
        static fn (object $installer): int => $installer->tabId,
        $evidence?->installerSnapshots ?? [],
    ), 'Installer snapshots persist in ascending numeric identifier order.');
}

if ($selected === 'all' || $selected === 'reordered_replay') {
    [$recording, $nonCanonical] = normalizationFixture();
    assertSameValue('ACCEPTED', $recording->completeItem($nonCanonical)->status, 'Fixture command is accepted once.');
    $original = inspectionItemCompletionEvidenceProjection(
        $recording->getItemCompletion(4512, $nonCanonical->clientOperationId),
    );
    assertSameValue(1, $original['currentChecklistRevision'], 'Replay baseline exposes current case checklist revision 1.');
    $sameNormalizedPayload = new CompleteInspectionItem(
        7301, 4512, $nonCanonical->clientOperationId,
        $nonCanonical->deviceInstallationId, $nonCanonical->deviceTime,
        0, 1, 28, [1042, 2048],
    );
    $replay = $recording->completeItem($sameNormalizedPayload);
    assertSameValue('DUPLICATE', $replay->status, 'Reordered representation of the same installer set is exact replay.');
    assertSameValue(1, $replay->revision, 'Normalized replay returns the original revision.');
    assertSameValue($original, inspectionItemCompletionEvidenceProjection(
        $recording->getItemCompletion(4512, $nonCanonical->clientOperationId),
    ), 'Normalized replay leaves original evidence unchanged.');
}

fwrite(STDOUT, "PASS: INSPECTION-ITEM-COMPLETE-001 installer normalization {$selected}\n");
