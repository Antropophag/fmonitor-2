<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInspectionEvidenceEnvironment.php';
require dirname(__DIR__) . '/Support/InspectionItemCompletionEvidenceProjection.php';

use FMonitor2\InspectionEvidence\CompleteInspectionItem;
use FMonitor2\InspectionEvidence\InspectionEvidence;
use FMonitor2\Tests\Support\InMemoryInspectionEvidenceEnvironment;

// Specification: INSPECTION-ITEM-COMPLETE-001 v0.1, authorization before replay.

/** @return array{InMemoryInspectionEvidenceEnvironment, InspectionEvidence, CompleteInspectionItem} */
function replayAuthorizationFixture(): array
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
    return [$environment, new InspectionEvidence($environment), new CompleteInspectionItem(
        7301, 4512,
        '13131313-1313-4313-8313-131313131313',
        '22222222-2222-4222-8222-222222222222',
        '2026-09-01T08:55:00+03:00',
        0, 1, 28, [1042],
    )];
}

$selected = getenv('FMONITOR_ITEM_TEST_CASE') ?: 'all';
foreach (['revoked', 'blocked'] as $scenario) {
    if ($selected !== 'all' && $selected !== $scenario) {
        continue;
    }
    [$environment, $recording, $command] = replayAuthorizationFixture();
    assertSameValue('ACCEPTED', $recording->completeItem($command)->status, 'Fixture command is accepted once.');
    $original = inspectionItemCompletionEvidenceProjection(
        $recording->getItemCompletion(4512, $command->clientOperationId),
    );
    assertSameValue(1, $original['currentChecklistRevision'], 'Replay baseline exposes current case checklist revision 1.');
    $environment->seedActor(
        7301,
        $scenario !== 'blocked',
        $scenario === 'revoked' ? [] : ['inspection.item.complete'],
    );
    $rejectedReplay = $recording->completeItem($command);
    assertSameValue('ACTOR_NOT_AUTHORIZED', $rejectedReplay->status, ucfirst($scenario) . ' current authority wins over exact replay.');
    assertSameValue($original, inspectionItemCompletionEvidenceProjection(
        $recording->getItemCompletion(4512, $command->clientOperationId),
    ), ucfirst($scenario) . ' replay rejection leaves original evidence unchanged.');
}

fwrite(STDOUT, "PASS: INSPECTION-ITEM-COMPLETE-001 authorization before replay {$selected}\n");
