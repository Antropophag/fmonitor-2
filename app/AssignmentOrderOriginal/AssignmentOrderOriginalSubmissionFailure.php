<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal A selected pre-commit outcome, caught by the application seam. */
final class AssignmentOrderOriginalSubmissionFailure extends \RuntimeException
{
    private function __construct(
        public readonly AssignmentOrderOriginalStatus $status,
        public readonly AssignmentOrderOriginalReason $reason,
        public readonly bool $retryable,
    ) {
        parent::__construct('Assignment order original submission stopped.');
    }

    public static function technical(AssignmentOrderOriginalReason $reason): self
    {
        return new self(AssignmentOrderOriginalStatus::FAILED, $reason, true);
    }

    public static function conflict(AssignmentOrderOriginalReason $reason): self
    {
        return new self(AssignmentOrderOriginalStatus::CONFLICT, $reason, false);
    }

    public static function rejected(AssignmentOrderOriginalReason $reason): self
    {
        return new self(AssignmentOrderOriginalStatus::REJECTED, $reason, false);
    }
}
