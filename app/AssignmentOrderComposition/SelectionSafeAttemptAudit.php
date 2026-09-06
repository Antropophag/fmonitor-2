<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionSafeAttemptAudit
{
    public function __construct(
        public SelectionRequestId $requestId,
        public UserId $actor,
        public InstallationObjectId $objectId,
        public AssignmentOrderCompositionMode $mode,
        public AssignmentOrderCompositionStatus $status,
        public ?AssignmentOrderCompositionReason $reason,
        public SelectionInstant $attemptedAt
    ) {}
}
