<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionStoredAudit
{
    public function __construct(
        public int $auditId,
        public SelectionSafeAttemptAudit $payload
    ) {}
}
