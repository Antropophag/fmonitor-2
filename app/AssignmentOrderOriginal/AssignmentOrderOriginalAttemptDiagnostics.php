<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Exact redacted events through the invocation's guarded logger. */
final class AssignmentOrderOriginalAttemptDiagnostics
{
    public static function audit(AssignmentOrderOriginalDependencies $d, string $phase): void
    { $d->safeLog->record('ASSIGNMENT_ORDER_ORIGINAL_ATTEMPT_AUDIT_FAILED', ['phase'=>$phase]); }

    public static function persistence(AssignmentOrderOriginalDependencies $d, AssignmentOrderOriginalResult $result): AssignmentOrderOriginalResult
    {
        if ($result->status() === AssignmentOrderOriginalStatus::FAILED) {
            $event = match ($result->reasonCode()) {
                AssignmentOrderOriginalReason::PERSISTENCE_FAILURE => 'ASSIGNMENT_ORDER_ORIGINAL_PERSISTENCE_FAILED',
                AssignmentOrderOriginalReason::PERSISTENCE_OUTCOME_UNKNOWN => 'ASSIGNMENT_ORDER_ORIGINAL_PERSISTENCE_UNKNOWN',
                default => null,
            };
            if ($event !== null) $d->safeLog->record($event, ['phase'=>'submission']);
        }
        return $result;
    }
}
