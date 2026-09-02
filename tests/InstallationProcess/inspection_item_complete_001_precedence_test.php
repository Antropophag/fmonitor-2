<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInspectionEvidenceEnvironment.php';
require dirname(__DIR__) . '/Support/InspectionItemCompletionEvidenceProjection.php';

use FMonitor2\InspectionEvidence\CompleteInspectionItem;
use FMonitor2\InspectionEvidence\InspectionEvidence;
use FMonitor2\Tests\Support\InMemoryInspectionEvidenceEnvironment;

// Specification: INSPECTION-ITEM-COMPLETE-001 v0.1, observable validation precedence.

/** @return array{InMemoryInspectionEvidenceEnvironment, InspectionEvidence, CompleteInspectionItem} */
function precedenceFixture(): array
{
    $environment = new InMemoryInspectionEvidenceEnvironment('2026-09-01T09:05:00+03:00');
    $environment->seedActor(7301, true, ['inspection.item.complete']);
    $environment->seedTemplate(9101, ['version' => 1, 'sha256' => str_repeat('a', 64), 'items' => [1 => [28]]]);
    $environment->seedTemplate(9201, ['version' => 2, 'sha256' => str_repeat('b', 64), 'items' => [2 => [99]]]);
    $environment->seedInstaller(1042, ['tabId' => 1042, 'fullName' => 'Иванов Иван Иванович', 'position' => 'Электромеханик']);
    $environment->seedInstaller(2048, ['tabId' => 2048, 'fullName' => 'Петров Пётр Петрович', 'position' => 'Электромеханик']);
    $environment->seedCase(4512, [
        'state' => 'working', 'revision' => 0, 'templateId' => 9101,
        'assignedControlEngineerUserId' => 7302,
        'registeredInstallerTabIds' => [1042], 'itemCompletions' => [],
    ]);
    return [$environment, new InspectionEvidence($environment), new CompleteInspectionItem(
        7301, 4512,
        '14141414-1414-4414-8414-141414141414',
        '22222222-2222-4222-8222-222222222222',
        '2026-09-01T08:55:00+03:00',
        0, 1, 28, [1042],
    )];
}

$selected = getenv('FMONITOR_ITEM_TEST_CASE') ?: 'all';

if ($selected === 'all' || $selected === 'auth_over_syntax') {
    [$environment, $recording] = precedenceFixture();
    $environment->seedActor(7301, true, []);
    $combined = new CompleteInspectionItem(7301, 4512, 'malformed', 'also-malformed', 'not-a-time', -1, 0, 0, []);
    assertSameValue('ACTOR_NOT_AUTHORIZED', $recording->completeItem($combined)->status, 'Authorization rejection precedes malformed syntax.');
    assertSameValue(null, $recording->getItemCompletion(4512, 'malformed'), 'Combined authorization/syntax rejection creates no evidence.');
}

if ($selected === 'all' || $selected === 'syntax_over_schema') {
    [$environment, $recording] = precedenceFixture();
    $environment->setInspectionSchemaAvailable(false);
    $combined = new CompleteInspectionItem(7301, 4512, 'malformed', 'also-malformed', 'not-a-time', -1, 0, 0, []);
    assertSameValue('INVALID_COMMAND', $recording->completeItem($combined)->status, 'Malformed syntax precedes unavailable schema.');
    assertSameValue(null, $recording->getItemCompletion(4512, 'malformed'), 'Combined syntax/schema rejection creates no evidence.');
}

if ($selected === 'all' || $selected === 'schema_over_replay') {
    [$environment, $recording, $accepted] = precedenceFixture();
    assertSameValue('ACCEPTED', $recording->completeItem($accepted)->status, 'Fixture command is accepted once.');
    $original = inspectionItemCompletionEvidenceProjection(
        $recording->getItemCompletion(4512, $accepted->clientOperationId),
    );
    assertSameValue(1, $original['currentChecklistRevision'], 'Schema/replay baseline exposes current case checklist revision 1.');
    $environment->setInspectionSchemaAvailable(false);
    assertSameValue('INSPECTION_SCHEMA_UNAVAILABLE', $recording->completeItem($accepted)->status, 'Schema readiness precedes exact replay.');
    assertSameValue($original, inspectionItemCompletionEvidenceProjection(
        $recording->getItemCompletion(4512, $accepted->clientOperationId),
    ), 'Schema rejection leaves original evidence unchanged.');
}

if ($selected === 'all' || $selected === 'conflict_over_mutable') {
    [$environment, $recording, $accepted] = precedenceFixture();
    assertSameValue('ACCEPTED', $recording->completeItem($accepted)->status, 'Fixture command is accepted once.');
    $original = inspectionItemCompletionEvidenceProjection(
        $recording->getItemCompletion(4512, $accepted->clientOperationId),
    );
    assertSameValue(1, $original['currentChecklistRevision'], 'Conflict baseline exposes current case checklist revision 1.');
    $environment->changeCase(4512, [
        'state' => 'closed', 'templateId' => 9201,
        'assignedControlEngineerUserId' => 7399,
        'registeredInstallerTabIds' => [2048],
    ]);
    $changed = new CompleteInspectionItem(
        7301, 4512, $accepted->clientOperationId,
        $accepted->deviceInstallationId, $accepted->deviceTime,
        0, 1, 28, [2048],
    );
    assertSameValue('OPERATION_PAYLOAD_CONFLICT', $recording->completeItem($changed)->status, 'Payload conflict precedes mutable case/template/crew rejection.');
    assertSameValue($original, inspectionItemCompletionEvidenceProjection(
        $recording->getItemCompletion(4512, $accepted->clientOperationId),
    ), 'Conflict leaves original evidence unchanged.');
}

fwrite(STDOUT, "PASS: INSPECTION-ITEM-COMPLETE-001 precedence {$selected}\n");
