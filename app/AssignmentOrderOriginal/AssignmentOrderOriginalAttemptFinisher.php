<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Finishes newly selected authorized terminal attempts exactly once. */
final class AssignmentOrderOriginalAttemptFinisher
{
    public static function finish(AssignmentOrderOriginalDependencies $d, SubmitAssignmentOrderOriginalCommand $c,
        AssignmentOrderOriginalStatus $status, AssignmentOrderOriginalReason $reason, bool $retry, ?string $at,
        AssignmentOrderOriginalResourceScope $resources): AssignmentOrderOriginalResult
    {
        $resources->cleanup();
        if (!$retry && AssignmentOrderOriginalDataScalar::terminalReason($status, $reason)
            && $reason !== AssignmentOrderOriginalReason::AUTHORIZATION_DENIED) {
            $at ??= AssignmentOrderOriginalPortValues::nowUtc($d->clock);
            if ($at === null) return self::failure($c);
            try {
                $audit = $d->repository->commitAttempt(new AssignmentOrderOriginalAttemptCommit($c->requestId, $c->actorUserId,
                    $c->mode, $c->installationCaseId, $c->assignmentOrderId, $status, $reason, false, $at));
            } catch (\Throwable) { $audit = AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN; }
            if ($audit === AssignmentOrderOriginalCommitStatus::ROLLED_BACK) return self::failure($c);
            if ($audit !== AssignmentOrderOriginalCommitStatus::COMMITTED)
                return AssignmentOrderOriginalFreshRecovery::resolve($d, $c->requestId, true)[0];
        }
        return new AssignmentOrderOriginalResultValue($status, $reason, $retry, $c->requestId);
    }

    private static function failure(SubmitAssignmentOrderOriginalCommand $c): AssignmentOrderOriginalResult
    { return new AssignmentOrderOriginalResultValue(AssignmentOrderOriginalStatus::FAILED, AssignmentOrderOriginalReason::PERSISTENCE_FAILURE, true, $c->requestId); }
}
