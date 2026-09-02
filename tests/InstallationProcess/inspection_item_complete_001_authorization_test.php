<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInspectionEvidenceEnvironment.php';

use FMonitor2\InspectionEvidence\CompleteInspectionItem;
use FMonitor2\InspectionEvidence\InspectionEvidence;
use FMonitor2\Tests\Support\InMemoryInspectionEvidenceEnvironment;

// Specification: INSPECTION-ITEM-COMPLETE-001 v0.1, examples B and C.

$environment = new InMemoryInspectionEvidenceEnvironment('2026-09-01T09:05:00+03:00');
$environment->seedActor(7301, true, ['inspection.item.complete']);
$environment->seedTemplate(9101, ['version' => 1, 'sha256' => str_repeat('a', 64), 'items' => [1 => [28, 29]]]);
$environment->seedInstaller(1042, ['tabId' => 1042, 'fullName' => 'Иванов Иван Иванович', 'position' => 'Электромеханик по лифтам']);
$environment->seedCase(4512, [
    'state' => 'working', 'revision' => 0, 'templateId' => 9101,
    // 7302 became responsible after the offline command was created.
    'assignedControlEngineerUserId' => 7302,
    'registeredInstallerTabIds' => [1042], 'itemCompletions' => [],
]);
$recording = new InspectionEvidence($environment);

$stillAuthorized = new CompleteInspectionItem(
    7301, 4512,
    '33333333-3333-4333-8333-333333333333',
    '22222222-2222-4222-8222-222222222222',
    '2026-09-01T08:55:00+03:00',
    0, 1, 28, [1042],
);
assertSameValue('ACCEPTED', $recording->completeItem($stillAuthorized)->status, 'Reassignment alone does not revoke broad capability.');
assertSameValue(7302, $recording->getItemCompletion(4512, $stillAuthorized->clientOperationId)?->assignedControlEngineerUserIdAtReceipt, 'Current assigned engineer is audit context.');

// The earlier device time cannot restore authority revoked before server receipt.
$environment->seedActor(7301, true, []);
$revoked = new CompleteInspectionItem(
    7301, 4512,
    '44444444-4444-4444-8444-444444444444',
    '22222222-2222-4222-8222-222222222222',
    '2026-09-01T08:55:00+03:00',
    1, 1, 29, [1042],
);
$rejected = $recording->completeItem($revoked);
assertSameValue('ACTOR_NOT_AUTHORIZED', $rejected->status, 'Current capability is rechecked at server receipt.');
assertSameValue(null, $recording->getItemCompletion(4512, $revoked->clientOperationId), 'Revoked offline operation appends no evidence.');

$environment->seedActor(7301, false, ['inspection.item.complete']);
$blocked = new CompleteInspectionItem(
    7301, 4512,
    '45555555-5555-4555-8555-555555555555',
    '22222222-2222-4222-8222-222222222222',
    '2026-09-01T08:54:00+03:00',
    1, 1, 29, [1042],
);
assertSameValue('ACTOR_NOT_AUTHORIZED', $recording->completeItem($blocked)->status, 'Blocked actor is rejected despite retaining the capability assignment.');
assertSameValue(null, $recording->getItemCompletion(4512, $blocked->clientOperationId), 'Blocked operation appends no evidence.');

fwrite(STDOUT, "PASS: INSPECTION-ITEM-COMPLETE-001 receipt-time authorization\n");
