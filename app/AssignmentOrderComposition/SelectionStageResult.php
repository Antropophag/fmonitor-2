<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final readonly class SelectionStageResult
{
    private function __construct(public SelectionStageStatus $status,public ?int $eventId,public ?int $auditId) {}
    public static function accepted(int $eventId,int $auditId): self
    { if($eventId<1 || $auditId<1)throw new \InvalidArgumentException('Invalid selection receipt.');return new self(SelectionStageStatus::STAGED,$eventId,$auditId); }
    public static function terminal(int $auditId): self
    { if($auditId<1)throw new \InvalidArgumentException('Invalid selection receipt.');return new self(SelectionStageStatus::STAGED,null,$auditId); }
    public static function requestRace(): self { return new self(SelectionStageStatus::REQUEST_RACE,null,null); }
    public static function persistenceError(): self { return new self(SelectionStageStatus::PERSISTENCE_ERROR,null,null); }
    public static function capacityExhausted(): self { return new self(SelectionStageStatus::CAPACITY_EXHAUSTED,null,null); }
}
