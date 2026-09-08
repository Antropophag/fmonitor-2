<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

enum AssignmentOrderOriginalAuditWriteStatus: string
{
    case COMMITTED = 'committed';
    case ROLLED_BACK = 'rolled_back';
    case OUTCOME_UNKNOWN = 'outcome_unknown';
}
final readonly class AssignmentOrderOriginalSafeAttemptAudit
{
    public function __construct(
        public string $requestId,
        public int $actorUserId,
        public AssignmentOrderOriginalMode $mode,
        public int $installationCaseId,
        public int $assignmentOrderId,
        public AssignmentOrderOriginalStatus $status,
        public AssignmentOrderOriginalReason $reason,
        public string $attemptedAtUtc,
    ) {}
}
interface AssignmentOrderOriginalAttemptAuditWriter
{
    public function recordDenied(AssignmentOrderOriginalSafeAttemptAudit $audit): AssignmentOrderOriginalAuditWriteStatus;
    public function appendFailure(AssignmentOrderOriginalSafeAttemptAudit $audit): AssignmentOrderOriginalAuditWriteStatus;
}
/** @internal Explicit degraded compatibility; production must bind its real audit writer. */
final class AssignmentOrderOriginalUnavailableAttemptAudits implements AssignmentOrderOriginalAttemptAuditWriter
{
    public function recordDenied(AssignmentOrderOriginalSafeAttemptAudit $audit): AssignmentOrderOriginalAuditWriteStatus
    { return AssignmentOrderOriginalAuditWriteStatus::ROLLED_BACK; }
    public function appendFailure(AssignmentOrderOriginalSafeAttemptAudit $audit): AssignmentOrderOriginalAuditWriteStatus
    { return AssignmentOrderOriginalAuditWriteStatus::ROLLED_BACK; }
}
