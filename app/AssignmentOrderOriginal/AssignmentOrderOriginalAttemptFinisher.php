<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/AssignmentOrderOriginalAttemptDiagnostics.php';
require_once __DIR__.'/AssignmentOrderOriginalAuditFinisher.php';

/** @internal Finishes newly selected authorized terminal attempts exactly once. */
final class AssignmentOrderOriginalAttemptFinisher
{
    public static function finish(AssignmentOrderOriginalDependencies $d, SubmitAssignmentOrderOriginalCommand $c,
        AssignmentOrderOriginalStatus $status, AssignmentOrderOriginalReason $reason, bool $retry, ?string $at,
        AssignmentOrderOriginalResourceScope $resources): AssignmentOrderOriginalResult
    {
        $resources->cleanup();
        if ($status === AssignmentOrderOriginalStatus::REJECTED && $reason === AssignmentOrderOriginalReason::AUTHORIZATION_DENIED)
            return AssignmentOrderOriginalAuditFinisher::denied($d, $c, $at);
        if ($status === AssignmentOrderOriginalStatus::FAILED
            && in_array($reason, [AssignmentOrderOriginalReason::STREAM_FAILURE, AssignmentOrderOriginalReason::STORAGE_FAILURE], true))
            return AssignmentOrderOriginalAuditFinisher::fileFailure($d, $c, $reason, $at);
        if (!$retry && AssignmentOrderOriginalDataScalar::terminalReason($status, $reason)
            && $reason !== AssignmentOrderOriginalReason::AUTHORIZATION_DENIED) {
            $at ??= AssignmentOrderOriginalPortValues::nowUtc($d->clock);
            if ($at === null) return AssignmentOrderOriginalAttemptDiagnostics::persistence($d, self::failure($c));
            try {
                $audit = $d->repository->commitAttempt(new AssignmentOrderOriginalAttemptCommit($c->requestId, $c->actorUserId,
                    $c->mode, $c->installationCaseId, $c->assignmentOrderId, $status, $reason, false, $at));
            } catch (\Throwable) { $audit = AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN; }
            if ($audit !== AssignmentOrderOriginalCommitStatus::COMMITTED) {
                $result = $audit === AssignmentOrderOriginalCommitStatus::ROLLED_BACK
                    ? self::failure($c) : AssignmentOrderOriginalFreshRecovery::resolve($d, $c->requestId, true)[0];
                if ($result->status() === AssignmentOrderOriginalStatus::FAILED) AssignmentOrderOriginalAttemptDiagnostics::audit($d, 'terminal');
                return $result;
            }
        }
        return AssignmentOrderOriginalAttemptDiagnostics::persistence($d,
            new AssignmentOrderOriginalResultValue($status, $reason, $retry, $c->requestId));
    }

    private static function failure(SubmitAssignmentOrderOriginalCommand $c): AssignmentOrderOriginalResult
    { return new AssignmentOrderOriginalResultValue(AssignmentOrderOriginalStatus::FAILED, AssignmentOrderOriginalReason::PERSISTENCE_FAILURE, true, $c->requestId); }
}
