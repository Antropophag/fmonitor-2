<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLookupStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceApplication;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceReason;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceVerificationFactory;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalOrphanCandidate;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalOrphanKind;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageStatus;
use FMonitor2\AssignmentOrderOriginal\ReconcileAssignmentOrderOriginalPrivateOrphansCommand;
use FMonitor2\Tests\Support\InMemoryAssignmentOrderOriginalMaintenanceEnvironment;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v4, sections 10 and 16.
$requiredTypes = [
    AssignmentOrderOriginalMaintenanceApplication::class,
    AssignmentOrderOriginalMaintenanceVerificationFactory::class,
    ReconcileAssignmentOrderOriginalPrivateOrphansCommand::class,
];
foreach ($requiredTypes as $requiredType) {
    if (!class_exists($requiredType) && !interface_exists($requiredType)) {
        throw new TestFailure('INTENDED_RED: canonical maintenance public seam is missing: ' . $requiredType);
    }
}
require dirname(__DIR__) . '/Support/InMemoryAssignmentOrderOriginalMaintenanceEnvironment.php';

$request = static fn(string $id, string $principal='system:original-orphan-reconciler', string $cutoff='2026-09-02T08:15:30Z', int $limit=100, ?string $cursor=null): ReconcileAssignmentOrderOriginalPrivateOrphansCommand
    => new ReconcileAssignmentOrderOriginalPrivateOrphansCommand($id, $principal, $cutoff, $limit, $cursor);
$run = static function (InMemoryAssignmentOrderOriginalMaintenanceEnvironment $environment, ReconcileAssignmentOrderOriginalPrivateOrphansCommand $command) {
    return AssignmentOrderOriginalMaintenanceVerificationFactory::create($environment->dependencies())
        ->reconcileAssignmentOrderOriginalPrivateOrphans($command);
};
$tuple = static fn($result): array => [$result->status(),$result->reason(),$result->retryable(),$result->scanned(),$result->deleted(),$result->retained(),$result->failed(),$result->nextCursor()];

foreach ([
    $request('not-a-uuid'),
    $request('00000000-0000-4000-8000-000000000101', cutoff:'2026-09-02T08:15:31Z'),
    $request('00000000-0000-4000-8000-000000000102', limit:0),
    $request('00000000-0000-4000-8000-000000000103', limit:1001),
    $request('00000000-0000-4000-8000-000000000104', cursor:'not+canonical'),
] as $invalid) {
    $environment = new InMemoryAssignmentOrderOriginalMaintenanceEnvironment();
    $result = $run($environment, $invalid);
    assertSameValue([AssignmentOrderOriginalMaintenanceStatus::REJECTED,AssignmentOrderOriginalMaintenanceReason::INVALID_COMMAND,false,0,0,0,0,null],$tuple($result),'Invalid scalar input has exact result.');
    assertSameValue([], $environment->calls, 'Invalid batch/cutoff/cursor fails before authorization, repository and storage.');
}

$denied = new InMemoryAssignmentOrderOriginalMaintenanceEnvironment();
$denial = $run($denied, $request('00000000-0000-4000-8000-000000000105','system:unprivileged'));
assertSameValue([AssignmentOrderOriginalMaintenanceStatus::REJECTED,AssignmentOrderOriginalMaintenanceReason::AUTHORIZATION_DENIED,false,0,0,0,0,null],$tuple($denial),'Wrong system principal is denied.');
assertSameValue(['authorize:system:unprivileged:assignment_order.original.storage.reconcile'], $denied->calls, 'Denial performs no request lookup, clock or storage work.');

$complete = new InMemoryAssignmentOrderOriginalMaintenanceEnvironment();
$complete->nextCursor='MjAyNi0wOS0wMlQwNzowMDowMFp8ZmluYWwtcmVmZXJlbmNlZA';
$complete->candidates=[
    new AssignmentOrderOriginalOrphanCandidate(AssignmentOrderOriginalOrphanKind::ABANDONED_STAGE,'stage-delete',null,17,'2026-09-02T06:00:00Z'),
    new AssignmentOrderOriginalOrphanCandidate(AssignmentOrderOriginalOrphanKind::FINALIZED_CONTENT,'final-referenced',str_repeat('1',64),327,'2026-09-02T06:30:00Z'),
    new AssignmentOrderOriginalOrphanCandidate(AssignmentOrderOriginalOrphanKind::FINALIZED_CONTENT,'final-delete',str_repeat('2',64),328,'2026-09-02T07:00:00Z'),
    new AssignmentOrderOriginalOrphanCandidate(AssignmentOrderOriginalOrphanKind::FINALIZED_CONTENT,'final-absent',str_repeat('3',64),329,'2026-09-02T07:30:00Z'),
];
$complete->references['final-referenced']=true;
$complete->deletes['final-absent']=AssignmentOrderOriginalStorageStatus::ALREADY_PRESENT_VERIFIED;
$completed=$run($complete,$request('00000000-0000-4000-8000-000000000106',limit:4));
assertSameValue([AssignmentOrderOriginalMaintenanceStatus::COMPLETED,null,false,4,3,1,0,$complete->nextCursor],$tuple($completed),'Completed page counts delete, already-absent and referenced retention exactly.');
assertSameValue(true,in_array('list:2026-09-02T08:15:30Z:4:null',$complete->calls,true),'Storage receives exact bounded page request.');
assertSameValue(false,in_array('reference:stage-delete',$complete->calls,true),'Abandoned stage deletion does not query original references.');
assertSameValue(false,in_array('delete:final-referenced',$complete->calls,true),'Committed reference is retained under lock.');
assertSameValue([
    'lock:stage-delete','delete:stage-delete','unlock:stage-delete',
    'lock:final-referenced','reference:final-referenced','unlock:final-referenced',
    'lock:final-delete','reference:final-delete','delete:final-delete','unlock:final-delete',
    'lock:final-absent','reference:final-absent','delete:final-absent','unlock:final-absent',
],array_values(array_filter($complete->calls,static fn(string $call):bool=>preg_match('/^(lock|reference|delete|unlock):/',$call)===1)),'Every identity has the exact lock/reference/delete/release attempt sequence.');
assertSameValue(['requestId','systemPrincipalId','status','reason','retryable','scanned','deleted','retained','failed','nextCursor','attemptedAtUtc'],array_keys(get_object_vars($complete->commits[0])),'Atomic maintenance commit has the exact safe allowlist.');
assertSameValue('system:original-orphan-reconciler',$complete->commits[0]->systemPrincipalId,'Audit records exact system identity, not file identity.');

$locked = new InMemoryAssignmentOrderOriginalMaintenanceEnvironment();
$locked->candidates=[new AssignmentOrderOriginalOrphanCandidate(AssignmentOrderOriginalOrphanKind::FINALIZED_CONTENT,'leased',str_repeat('4',64),400,'2026-09-02T07:00:00Z')];
$locked->locks['leased']=AssignmentOrderOriginalStorageStatus::LOCKED;
$partialLock=$run($locked,$request('00000000-0000-4000-8000-000000000107'));
assertSameValue([AssignmentOrderOriginalMaintenanceStatus::PARTIAL,AssignmentOrderOriginalMaintenanceReason::LOCKED,true,1,0,1,0,null],$tuple($partialLock),'Upload lease lock is retained and reported as retryable PARTIAL/LOCKED.');
assertSameValue(false,in_array('reference:leased',$locked->calls,true)||in_array('delete:leased',$locked->calls,true),'Locked candidate is neither referenced nor deleted.');

$failedItem = new InMemoryAssignmentOrderOriginalMaintenanceEnvironment();
$failedItem->candidates=[
    new AssignmentOrderOriginalOrphanCandidate(AssignmentOrderOriginalOrphanKind::FINALIZED_CONTENT,'reference-unavailable',str_repeat('5',64),500,'2026-09-02T07:00:00Z'),
    new AssignmentOrderOriginalOrphanCandidate(AssignmentOrderOriginalOrphanKind::ABANDONED_STAGE,'delete-failed',null,20,'2026-09-02T07:01:00Z'),
    new AssignmentOrderOriginalOrphanCandidate(AssignmentOrderOriginalOrphanKind::ABANDONED_STAGE,'lock-failed',null,21,'2026-09-02T07:02:00Z'),
];
$failedItem->referenceStatuses['reference-unavailable']=AssignmentOrderOriginalLookupStatus::UNAVAILABLE;
$failedItem->deletes['delete-failed']=AssignmentOrderOriginalStorageStatus::FAILED;
$failedItem->locks['lock-failed']=AssignmentOrderOriginalStorageStatus::FAILED;
$partialFailure=$run($failedItem,$request('00000000-0000-4000-8000-000000000108'));
assertSameValue([AssignmentOrderOriginalMaintenanceStatus::PARTIAL,AssignmentOrderOriginalMaintenanceReason::STORAGE_FAILURE,true,3,0,0,3,null],$tuple($partialFailure),'Lock, reference and delete failures are exact per-item storage failures.');

$pagination = new InMemoryAssignmentOrderOriginalMaintenanceEnvironment();
$pagination->nextCursor=null;
$cursor='MjAyNi0wOS0wMlQwNzowMDowMFp8ZmluYWwtcmVmZXJlbmNlZA';
$pageAfterCursor=$run($pagination,$request('00000000-0000-4000-8000-000000000113',limit:7,cursor:$cursor));
assertSameValue([AssignmentOrderOriginalMaintenanceStatus::COMPLETED,null,false,0,0,0,0,null],$tuple($pageAfterCursor),'Canonical cursor can select the next empty page.');
assertSameValue(true,in_array('list:2026-09-02T08:15:30Z:7:'.$cursor,$pagination->calls,true),'Canonical cursor and batch are passed to candidate storage without reinterpretation.');

$repositoryDown = new InMemoryAssignmentOrderOriginalMaintenanceEnvironment();
$repositoryDown->requestLookupStatus=AssignmentOrderOriginalLookupStatus::UNAVAILABLE;
$down=$run($repositoryDown,$request('00000000-0000-4000-8000-000000000109'));
assertSameValue([AssignmentOrderOriginalMaintenanceStatus::FAILED,AssignmentOrderOriginalMaintenanceReason::PERSISTENCE_FAILURE,true,0,0,0,0,null],$tuple($down),'Maintenance request repository outage is retryable persistence failure.');
assertSameValue(false,(bool)array_filter($repositoryDown->calls,static fn(string $call):bool=>str_starts_with($call,'list:')),'Repository outage performs no candidate enumeration.');

$replay = new InMemoryAssignmentOrderOriginalMaintenanceEnvironment();
$first=$run($replay,$request('00000000-0000-4000-8000-000000000110'));
$callsAfterFirst=count($replay->calls);
$second=$run($replay,$request('00000000-0000-4000-8000-000000000110'));
assertSameValue([AssignmentOrderOriginalMaintenanceStatus::REPLAYED,null,false,0,0,0,0,null],$tuple($second),'Same authorized request replays terminal completed result.');
assertSameValue([],array_values(array_filter(array_slice($replay->calls,$callsAfterFirst),static fn(string $call):bool=>str_starts_with($call,'list:')||str_starts_with($call,'lock:')||str_starts_with($call,'reference:')||str_starts_with($call,'delete:'))),'Same-request replay has zero storage/reference operations.');

$atMostOnce = new InMemoryAssignmentOrderOriginalMaintenanceEnvironment();
$atMostOnce->candidates=[new AssignmentOrderOriginalOrphanCandidate(AssignmentOrderOriginalOrphanKind::ABANDONED_STAGE,'one-physical-delete',null,21,'2026-09-02T07:00:00Z')];
$one=$run($atMostOnce,$request('00000000-0000-4000-8000-000000000111'));
$atMostOnce->requestLookupStatus=AssignmentOrderOriginalLookupStatus::NOT_FOUND;
$two=$run($atMostOnce,$request('00000000-0000-4000-8000-000000000112'));
assertSameValue(1,$atMostOnce->physicalDeletes['one-physical-delete'] ?? 0,'Second request observes already-absent delete and causes at most one physical delete.');
assertSameValue([[AssignmentOrderOriginalMaintenanceStatus::COMPLETED,1],[AssignmentOrderOriginalMaintenanceStatus::COMPLETED,1]],[[$one->status(),$one->deleted()],[$two->status(),$two->deleted()]],'Already-absent delete remains idempotent success.');

fwrite(STDOUT,"ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_MAINTENANCE_OK\n");
