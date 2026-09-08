<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/AssignmentOrderOriginalMaintenanceValues.php';
require_once __DIR__.'/AssignmentOrderOriginalMaintenanceItems.php';

/** The single application owner for private-original maintenance. */
final readonly class AssignmentOrderOriginalMaintenanceOwner implements AssignmentOrderOriginalMaintenanceApplication
{
    public function __construct(private AssignmentOrderOriginalMaintenanceDependencies $dependencies) {}

    public function reconcileAssignmentOrderOriginalPrivateOrphans(ReconcileAssignmentOrderOriginalPrivateOrphansCommand $command): AssignmentOrderOriginalMaintenanceResult
    {
        if (!AssignmentOrderOriginalMaintenanceValues::shape($command)) return new AssignmentOrderOriginalMaintenanceResultValue(
            AssignmentOrderOriginalMaintenanceStatus::REJECTED, AssignmentOrderOriginalMaintenanceReason::INVALID_COMMAND, false);
        $d = $this->dependencies; $log = new AssignmentOrderOriginalBestEffortSafeLog($d->safeLog); $log->useRequest($command->requestId);
        try { $authorization = $d->authorizer->authorize($command->systemPrincipalId, 'assignment_order.original.storage.reconcile'); }
        catch (\Throwable) { return self::failure($log, 'authorization'); }
        if ($authorization === AssignmentOrderOriginalAuthorizationStatus::UNAVAILABLE) return self::failure($log, 'authorization');
        $denied = $authorization === AssignmentOrderOriginalAuthorizationStatus::DENIED;
        if (!$denied) {
            try {
                $lookup = $d->requests->findTerminalRequest($command->requestId);
                $status = $lookup->status(); $stored = $lookup->result();
                if ($status === AssignmentOrderOriginalLookupStatus::FOUND && $stored !== null) {
                    $tuple = AssignmentOrderOriginalMaintenanceValues::tuple($stored);
                    if (!AssignmentOrderOriginalMaintenanceValues::terminal($tuple)) throw new \UnexpectedValueException();
                    $tuple[0] = AssignmentOrderOriginalMaintenanceStatus::REPLAYED;
                    return new AssignmentOrderOriginalMaintenanceResultValue(...$tuple);
                }
                if ($status !== AssignmentOrderOriginalLookupStatus::NOT_FOUND || $stored !== null) throw new \UnexpectedValueException();
            } catch (\Throwable) { return self::failure($log, 'lookup'); }
        }
        try { $at = $d->clock->nowUtc(); if (!AssignmentOrderOriginalDataScalar::utc($at)) throw new \UnexpectedValueException(); }
        catch (\Throwable) { return self::failure($log, 'clock'); }
        $reason = $denied ? AssignmentOrderOriginalMaintenanceReason::AUTHORIZATION_DENIED : null;
        if (!$denied && (new \DateTimeImmutable($command->cutoffUtc))->getTimestamp() > (new \DateTimeImmutable($at))->getTimestamp()-3600)
            $reason = AssignmentOrderOriginalMaintenanceReason::INVALID_COMMAND;
        $tuple = [AssignmentOrderOriginalMaintenanceStatus::REJECTED,$reason,false,0,0,0,0,null];
        if ($reason === null) {
            try { [$items,$cursor] = AssignmentOrderOriginalMaintenanceValues::page($d->storage->listOrphans($command->cutoffUtc,$command->batchLimit,$command->cursor),$command); }
            catch (\Throwable) { return self::failure($log, 'candidate_page'); }
            [$deleted,$retained,$failed,$locked] = (new AssignmentOrderOriginalMaintenanceItems($d,$log))->process($items);
            $partial = $failed > 0 || $locked > 0;
            $tuple = [$partial ? AssignmentOrderOriginalMaintenanceStatus::PARTIAL : AssignmentOrderOriginalMaintenanceStatus::COMPLETED,
                $failed > 0 ? AssignmentOrderOriginalMaintenanceReason::STORAGE_FAILURE : ($locked > 0 ? AssignmentOrderOriginalMaintenanceReason::LOCKED : null),
                $partial,count($items),$deleted,$retained,$failed,$cursor];
        }
        $commit = new AssignmentOrderOriginalMaintenanceCommit($command->requestId,$command->systemPrincipalId,...[...$tuple,$at]);
        try { if ($d->requests->commitResultAndAudit($commit) !== AssignmentOrderOriginalCommitStatus::COMMITTED) throw new \RuntimeException(); }
        catch (\Throwable) { return self::failure($log, 'commit', array_slice($tuple,3)); }
        return new AssignmentOrderOriginalMaintenanceResultValue(...$tuple);
    }

    private static function failure(AssignmentOrderOriginalBestEffortSafeLog $log, string $phase, array $counts = [0,0,0,0,null]): AssignmentOrderOriginalMaintenanceResult
    {
        $log->record('ASSIGNMENT_ORDER_ORIGINAL_MAINTENANCE_PERSISTENCE_FAILED', ['phase'=>$phase]);
        return new AssignmentOrderOriginalMaintenanceResultValue(AssignmentOrderOriginalMaintenanceStatus::FAILED,AssignmentOrderOriginalMaintenanceReason::PERSISTENCE_FAILURE,true,...$counts);
    }
}
