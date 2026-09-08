<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionSuccessPayload
{
    public function __construct(
        public int $caseId, public int $assignmentOrderId,
        public int $assignmentOrderVersion, public int $selectionRevision,
        public string $compositionIdentity, public string $compositionSha256,
        public string $selectionDate, public string $selectedAt,
    ) {}
}
