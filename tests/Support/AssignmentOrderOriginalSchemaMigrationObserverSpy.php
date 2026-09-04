<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigrationObserver;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigrationPhase;

final class AssignmentOrderOriginalSchemaMigrationObserverSpy implements AssignmentOrderOriginalSchemaMigrationObserver
{
    /** @var list<array{AssignmentOrderOriginalSchemaMigrationPhase,?string}> */
    public array $calls=[];

    public function __construct(
        private readonly AssignmentOrderOriginalSchemaMigrationPhase $failurePhase,
        private readonly ?string $failureTable,
        private readonly ?\Closure $beforeFailure = null,
    ) {}

    public function observe(AssignmentOrderOriginalSchemaMigrationPhase $phase, ?string $logicalTable): void
    {
        $this->calls[]=[$phase,$logicalTable];
        if($phase===$this->failurePhase&&$logicalTable===$this->failureTable){if($this->beforeFailure!==null)($this->beforeFailure)();throw new \RuntimeException('verifier-only migration fault');}
    }
}
