<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final readonly class SelectionIdentityAllocationResult
{
    private function __construct(public SelectionIdentityAllocationStatus $status,public ?SelectionIdentityAllocation $allocation) {}
    public static function allocated(SelectionIdentityAllocation $allocation): self
    {
        if($allocation->assignmentOrderId<1 || $allocation->caseId<1 || $allocation->orderVersion<1 || $allocation->orderVersion>65535 || $allocation->sourceKind!==SelectionSourceKind::SELECTION || !SelectionScalar::utc($allocation->allocatedAt->utcRfc3339Seconds))throw new \InvalidArgumentException('Invalid selection allocation.');
        return new self(SelectionIdentityAllocationStatus::ALLOCATED,$allocation);
    }
    public static function capacityExhausted(): self { return new self(SelectionIdentityAllocationStatus::CAPACITY_EXHAUSTED,null); }
    public static function persistenceError(): self { return new self(SelectionIdentityAllocationStatus::PERSISTENCE_ERROR,null); }
}
