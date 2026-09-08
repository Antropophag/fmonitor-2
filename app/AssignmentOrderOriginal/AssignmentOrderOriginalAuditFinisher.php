<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Audit-only paths never consult confidential terminal results. */
final class AssignmentOrderOriginalAuditFinisher
{
    public static function denied(AssignmentOrderOriginalDependencies $d, SubmitAssignmentOrderOriginalCommand $c, ?string $at): AssignmentOrderOriginalResult
    {
        $at ??= AssignmentOrderOriginalPortValues::nowUtc($d->clock);
        if ($at === null) return AssignmentOrderOriginalAttemptDiagnostics::persistence($d,
            self::result($c, AssignmentOrderOriginalReason::PERSISTENCE_FAILURE));
        $outcome = self::write($d, $c, AssignmentOrderOriginalReason::AUTHORIZATION_DENIED, $at);
        if ($outcome === AssignmentOrderOriginalAuditWriteStatus::COMMITTED)
            return self::result($c, AssignmentOrderOriginalReason::AUTHORIZATION_DENIED);
        AssignmentOrderOriginalAttemptDiagnostics::audit($d, 'denial');
        return self::result($c, $outcome === AssignmentOrderOriginalAuditWriteStatus::ROLLED_BACK
            ? AssignmentOrderOriginalReason::PERSISTENCE_FAILURE : AssignmentOrderOriginalReason::PERSISTENCE_OUTCOME_UNKNOWN);
    }

    public static function fileFailure(AssignmentOrderOriginalDependencies $d, SubmitAssignmentOrderOriginalCommand $c,
        AssignmentOrderOriginalReason $reason, ?string $at): AssignmentOrderOriginalResult
    {
        $at ??= AssignmentOrderOriginalPortValues::nowUtc($d->clock);
        if ($at === null || self::write($d, $c, $reason, $at) !== AssignmentOrderOriginalAuditWriteStatus::COMMITTED)
            AssignmentOrderOriginalAttemptDiagnostics::audit($d, 'file_failure');
        return self::result($c, $reason);
    }

    private static function write(AssignmentOrderOriginalDependencies $d, SubmitAssignmentOrderOriginalCommand $c,
        AssignmentOrderOriginalReason $reason, string $at): AssignmentOrderOriginalAuditWriteStatus
    {
        $denied = $reason === AssignmentOrderOriginalReason::AUTHORIZATION_DENIED;
        $audit = new AssignmentOrderOriginalSafeAttemptAudit($c->requestId, $c->actorUserId, $c->mode,
            $c->installationCaseId, $c->assignmentOrderId,
            $denied ? AssignmentOrderOriginalStatus::REJECTED : AssignmentOrderOriginalStatus::FAILED, $reason, $at);
        try { return $denied ? $d->attemptAudits->recordDenied($audit) : $d->attemptAudits->appendFailure($audit); }
        catch (\Throwable) { return AssignmentOrderOriginalAuditWriteStatus::OUTCOME_UNKNOWN; }
    }

    private static function result(SubmitAssignmentOrderOriginalCommand $c, AssignmentOrderOriginalReason $reason): AssignmentOrderOriginalResult
    {
        $denied = $reason === AssignmentOrderOriginalReason::AUTHORIZATION_DENIED;
        return new AssignmentOrderOriginalResultValue($denied ? AssignmentOrderOriginalStatus::REJECTED : AssignmentOrderOriginalStatus::FAILED,
            $reason, !$denied, $c->requestId);
    }
}
