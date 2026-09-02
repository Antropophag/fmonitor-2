<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InspectionEvidence\CompleteInspectionItem;
use FMonitor2\InspectionEvidence\InspectionRecording;
use FMonitor2\InspectionEvidence\ItemCompletionResult;
use FMonitor2\PilotHttp\ChecklistSync;
use FMonitor2\PilotHttp\HttpUser;

// Specification: INSPECTION-ITEM-COMPLETE-001 v0.1, HTTP adapter boundary.

final class InspectionRecordingHttpSpy implements InspectionRecording
{
    /** @var list<CompleteInspectionItem> */
    public array $commands = [];

    public function __construct(private ItemCompletionResult $nextResult)
    {
    }

    public function willReturn(ItemCompletionResult $result): void
    {
        $this->nextResult = $result;
    }

    public function completeItem(CompleteInspectionItem $command): ItemCompletionResult
    {
        $this->commands[] = $command;
        return $this->nextResult;
    }
}

final class InstallationCaseIdResolverSpy
{
    /** @var list<int> */
    public array $objectIds = [];

    public function __construct(private readonly string $mode, private readonly ?int $caseId = null)
    {
    }

    public function __invoke(int $objectId): ?int
    {
        $this->objectIds[] = $objectId;
        return match ($this->mode) {
            'found' => $this->caseId,
            'missing' => null,
            'ambiguous' => throw new FMonitor2\PilotHttp\PilotHttpInfrastructureUnavailable('AMBIGUOUS_INSTALLATION_CASE'),
            'failure' => throw new RuntimeException('resolver database failed'),
            default => throw new LogicException('Unknown resolver spy mode.'),
        };
    }
}

$db = mysqli_init();
$recording = new InspectionRecordingHttpSpy(new ItemCompletionResult('ACCEPTED', 41));
$resolver = new InstallationCaseIdResolverSpy('found', 9512);

// The database is deliberately not connected: item_completed is an HTTP
// translation branch and must reach only the public application seam.
$sync = new ChecklistSync(
    db: $db,
    prefix: 'http_red_',
    storageRoot: '',
    now: '2026-09-01T12:00:00+03:00',
    inspectionRecording: $recording,
    installationCaseIdResolver: $resolver,
);

$clientOperation = [
    'clientOperationId' => '11111111-1111-4111-8111-111111111111',
    'deviceInstallationId' => '22222222-2222-4222-8222-222222222222',
    'type' => 'item_completed',
    'deviceTime' => '2026-09-01T08:55:00+03:00',
    'baseRevision' => 40,
    'sectionId' => 1,
    'itemId' => 28,
    'installerTabIds' => ['2048', 1042],
    'actorUserId' => 9999,
];
$actor = new HttpUser(7301, 'Actual Actor', 'actor@example.invalid');

assertSameValue(
    ['status' => 'accepted', 'revision' => 41],
    $sync->accept(4512, $actor, $clientOperation),
    'HTTP item_completed maps ACCEPTED through the public application seam.',
);
assertSameValue(1, count($recording->commands), 'HTTP item_completed calls InspectionRecording exactly once.');
$command = $recording->commands[0];
assertSameValue([
    'actorUserId' => 7301,
    'installationCaseId' => 9512,
    'clientOperationId' => '11111111-1111-4111-8111-111111111111',
    'deviceInstallationId' => '22222222-2222-4222-8222-222222222222',
    'deviceTime' => '2026-09-01T08:55:00+03:00',
    'expectedRevision' => 40,
    'sectionId' => 1,
    'itemId' => 28,
    'installerTabIds' => [2048, 1042],
], [
    'actorUserId' => $command->actorUserId,
    'installationCaseId' => $command->installationCaseId,
    'clientOperationId' => $command->clientOperationId,
    'deviceInstallationId' => $command->deviceInstallationId,
    'deviceTime' => $command->deviceTime,
    'expectedRevision' => $command->expectedRevision,
    'sectionId' => $command->sectionId,
    'itemId' => $command->itemId,
    'installerTabIds' => $command->installerTabIds,
], 'HTTP adapter resolves object id to its distinct case id, translates the envelope, and takes actor only from trusted server context.');

$matrix = [
    ['DUPLICATE', 41, 'duplicate'],
    ['STALE_REVISION', 42, 'conflict'],
    ['OPERATION_PAYLOAD_CONFLICT', 41, 'conflict'],
    ['ACTOR_NOT_AUTHORIZED', 0, 'rejected'],
    ['INVALID_COMMAND', 0, 'rejected'],
    ['CASE_NOT_FOUND', 0, 'rejected'],
    ['CASE_NOT_WORKING', 4, 'rejected'],
    ['CHECKLIST_TEMPLATE_UNAVAILABLE', 4, 'rejected'],
    ['CHECKLIST_ITEM_UNKNOWN', 4, 'rejected'],
    ['INSTALLER_NOT_ASSIGNED', 4, 'rejected'],
    ['INSTALLER_SNAPSHOT_INCOMPLETE', 4, 'rejected'],
];
foreach ($matrix as $index => [$domainStatus, $revision, $adapterStatus]) {
    $recording->willReturn(new ItemCompletionResult($domainStatus, $revision));
    $operation = $clientOperation;
    $operation['clientOperationId'] = sprintf('33333333-3333-4333-8333-%012d', $index + 1);
    assertSameValue(
        ['status' => $adapterStatus, 'revision' => $revision],
        $sync->accept(4512, new HttpUser(7303, 'Current Actor', 'actor@example.invalid'), $operation),
        "HTTP maps {$domainStatus} to {$adapterStatus} with the application revision.",
    );
}
assertSameValue(1 + count($matrix), count($recording->commands), 'Every mapped result has exactly one seam call.');
assertSameValue(7303, $recording->commands[4]->actorUserId, 'Current authenticated actor reaches receipt-time authorization.');

$recording->willReturn(new ItemCompletionResult('INSPECTION_SCHEMA_UNAVAILABLE', 0));
$schemaUnavailable = $clientOperation;
$schemaUnavailable['clientOperationId'] = '44444444-4444-4444-8444-444444444444';
$beforeSchemaRecordingCalls = count($recording->commands);
try {
    $sync->accept(4512, $actor, $schemaUnavailable);
    throw new TestFailure('INSPECTION_SCHEMA_UNAVAILABLE must enter the retryable infrastructure path.');
} catch (FMonitor2\PilotHttp\PilotHttpInfrastructureUnavailable) {
}
assertSameValue(
    $beforeSchemaRecordingCalls + 1,
    count($recording->commands),
    'Schema unavailability is mapped only after exactly one recording call.',
);
assertSameValue(
    count($recording->commands),
    count($resolver->objectIds),
    'Every item command resolves its object exactly once before its one recording call.',
);
assertSameValue(
    array_fill(0, count($resolver->objectIds), 4512),
    $resolver->objectIds,
    'Resolver receives the external object id once per item command, without case-id or duplicate resolution.',
);

$resolverSpyCount = count($recording->commands);
$missingResolver = new InstallationCaseIdResolverSpy('missing');
$missingCaseSync = new ChecklistSync(
    db: $db,
    prefix: 'http_red_',
    storageRoot: '',
    now: '2026-09-01T12:00:00+03:00',
    inspectionRecording: $recording,
    installationCaseIdResolver: $missingResolver,
);
assertSameValue(
    ['status' => 'rejected', 'revision' => 0],
    $missingCaseSync->accept(84512, $actor, $clientOperation),
    'Zero current cases maps to the exact CASE_NOT_FOUND adapter result.',
);
assertSameValue($resolverSpyCount, count($recording->commands), 'Zero-case resolution does not invoke completeItem.');
assertSameValue([84512], $missingResolver->objectIds, 'Missing case resolves the external object exactly once.');

$ambiguousResolver = new InstallationCaseIdResolverSpy('ambiguous');
$ambiguousCaseSync = new ChecklistSync(
    db: $db,
    prefix: 'http_red_',
    storageRoot: '',
    now: '2026-09-01T12:00:00+03:00',
    inspectionRecording: $recording,
    installationCaseIdResolver: $ambiguousResolver,
);
try {
    $ambiguousCaseSync->accept(84513, $actor, $clientOperation);
    throw new TestFailure('Multiple current cases must use the retryable infrastructure path.');
} catch (FMonitor2\PilotHttp\PilotHttpInfrastructureUnavailable) {
}
assertSameValue([84513], $ambiguousResolver->objectIds, 'Ambiguous case resolves the external object exactly once.');

$failedResolver = new InstallationCaseIdResolverSpy('failure');
$failedResolverSync = new ChecklistSync(
    db: $db,
    prefix: 'http_red_',
    storageRoot: '',
    now: '2026-09-01T12:00:00+03:00',
    inspectionRecording: $recording,
    installationCaseIdResolver: $failedResolver,
);
try {
    $failedResolverSync->accept(84514, $actor, $clientOperation);
    throw new TestFailure('Resolver/database failure must use the retryable infrastructure path.');
} catch (FMonitor2\PilotHttp\PilotHttpInfrastructureUnavailable) {
}
assertSameValue([84514], $failedResolver->objectIds, 'Failed resolver receives the external object exactly once.');
assertSameValue($resolverSpyCount, count($recording->commands), 'Resolver absence, ambiguity, and failure never invoke completeItem.');

$recording->willReturn(new ItemCompletionResult('INVALID_COMMAND', 0));
$beforeMalformedRecording = count($recording->commands);
$beforeMalformedResolution = count($resolver->objectIds);
$malformedItem = $clientOperation;
$malformedItem['deviceInstallationId'] = 'not-a-uuid';
assertSameValue(
    ['status' => 'rejected', 'revision' => 0],
    $sync->accept(4512, $actor, $malformedItem),
    'Malformed item_completed is rejected only after delegation to the recording seam.',
);
assertSameValue($beforeMalformedRecording + 1, count($recording->commands), 'Malformed item calls recording exactly once.');
assertSameValue($beforeMalformedResolution + 1, count($resolver->objectIds), 'Malformed item resolves its object exactly once.');
assertSameValue(4512, $resolver->objectIds[array_key_last($resolver->objectIds)], 'Malformed item resolves the trusted route object id.');
$malformedCommand = $recording->commands[array_key_last($recording->commands)];
assertSameValue(7301, $malformedCommand->actorUserId, 'Malformed item retains the trusted authenticated actor.');
assertSameValue(9512, $malformedCommand->installationCaseId, 'Malformed item retains the explicitly resolved canonical case.');
assertSameValue('not-a-uuid', $malformedCommand->deviceInstallationId, 'The sole malformed raw field is preserved for application validation.');

$before = count($recording->commands);
$resolverCallsBeforeLegacy = count($resolver->objectIds);
$validLegacyPhoto = $clientOperation;
$validLegacyPhoto['clientOperationId'] = '55555555-5555-4555-8555-555555555555';
$validLegacyPhoto['type'] = 'photo_uploaded';
$validLegacyPhoto['sha256'] = str_repeat('a', 64);
$validLegacyPhoto['mime'] = 'image/png';
$validLegacyPhoto['size'] = 1;
$validLegacyPhoto['originalName'] = 'evidence.png';
try {
    $sync->accept(4512, $actor, $validLegacyPhoto, 'x');
    throw new TestFailure('Valid non-item operation must retain its legacy branch, including its DB dependency.');
} catch (mysqli_sql_exception|Error $legacyFailure) {
    assertSameValue(true, str_contains($legacyFailure->getMessage(), 'mysqli'), 'Valid photo reaches the legacy DB-backed branch.');
}
assertSameValue($before, count($recording->commands), 'Non-item branches never call completeItem.');
assertSameValue($resolverCallsBeforeLegacy, count($resolver->objectIds), 'Valid non-item branch makes zero resolver calls.');

echo "PASS: INSPECTION-ITEM-COMPLETE-001 HTTP wiring\n";
