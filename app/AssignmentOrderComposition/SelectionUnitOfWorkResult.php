<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionUnitOfWorkResult
{
    private function __construct(private string $k,private ?SelectionResult $r,private ?SelectionRollbackCause $cause,private ?SelectionTerminalRequestRecord $record) {}
    public static function committed(SelectionResult $result): self
    {
        if(!in_array($result->status(),[AssignmentOrderCompositionStatus::SELECTED,AssignmentOrderCompositionStatus::REJECTED,AssignmentOrderCompositionStatus::CONFLICT],true))throw new \InvalidArgumentException('Invalid selection decision.');
        return new self('committed',$result,null,null);
    }
    public static function rolledBack(SelectionRollbackCause $cause): self { return new self('rolledBack',null,$cause,null); }
    public static function requestRace(): self { return new self('requestRace',null,null,null); }
    public static function observedTerminal(SelectionTerminalRequestRecord $record): self { return new self('observedTerminal',null,null,$record); }
    public static function outcomeUnknown(): self { return new self('outcomeUnknown',null,null,null); }
    public function kind(): string { return $this->k; }
    public function result(): ?SelectionResult { return $this->r; }
    public function rollbackCause(): ?SelectionRollbackCause { return $this->cause; }
    public function terminalRecord(): ?SelectionTerminalRequestRecord { return $this->record; }
}
