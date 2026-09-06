<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final readonly class SelectionAuditWriteResult
{
    private function __construct(public SelectionAuditWriteStatus $status,public ?int $auditId) {}
    public static function committed(int $auditId): self
    { if($auditId<1)throw new \InvalidArgumentException('Invalid selection receipt.');return new self(SelectionAuditWriteStatus::COMMITTED,$auditId); }
    public static function rolledBack(): self { return new self(SelectionAuditWriteStatus::ROLLED_BACK,null); }
    public static function outcomeUnknown(): self { return new self(SelectionAuditWriteStatus::OUTCOME_UNKNOWN,null); }
    public static function capacityExhausted(): self { return new self(SelectionAuditWriteStatus::CAPACITY_EXHAUSTED,null); }
}
