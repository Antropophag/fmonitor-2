<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionTerminalAttemptPersistence
{
    public function __construct(
        public SelectionRequestId $requestId,
        public SelectionNormalizedIntent $intent,
        public AssignmentOrderCompositionResult $terminalResult,
        public SelectionSafeAttemptAudit $audit
    ) {}
}
