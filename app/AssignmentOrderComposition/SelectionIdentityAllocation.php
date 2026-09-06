<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionIdentityAllocation
{
    public function __construct(
        public int $assignmentOrderId,
        public int $caseId,
        public int $orderVersion,
        public SelectionSourceKind $sourceKind,
        public SelectionInstant $allocatedAt
    ) {}
}
