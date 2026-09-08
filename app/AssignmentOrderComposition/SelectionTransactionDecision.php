<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionTransactionDecision
{
    private function __construct(private string $k,private ?SelectionResult $r,private ?SelectionRollbackCause $cause,private ?SelectionTerminalRequestRecord $record) {}
    public static function commit(SelectionResult $result): self
    {
        if(!in_array($result->status(),[AssignmentOrderCompositionStatus::SELECTED,AssignmentOrderCompositionStatus::REJECTED,AssignmentOrderCompositionStatus::CONFLICT],true))throw new \InvalidArgumentException('Invalid selection decision.');
        return new self('commit',$result,null,null);
    }
    public static function rollback(SelectionRollbackCause $cause): self { return new self('rollback',null,$cause,null); }
    public static function requestRace(): self { return new self('requestRace',null,null,null); }
    public static function observedTerminal(SelectionTerminalRequestRecord $record): self { return new self('observedTerminal',null,null,$record); }
    public function kind(): string { return $this->k; }
    public function result(): ?SelectionResult { return $this->r; }
    public function rollbackCause(): ?SelectionRollbackCause { return $this->cause; }
    public function terminalRecord(): ?SelectionTerminalRequestRecord { return $this->record; }
}
