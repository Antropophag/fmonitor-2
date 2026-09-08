<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionSelectedEvent
{
    public function __construct(
        public SelectionRequestId $requestId,
        public int $caseId,
        public int $orderId,
        public int $orderVersion,
        public int $selectionRevision,
        public ?int $previousSelectionOrderId,
        public ?int $replacesSelectionOrderId,
        public string $compositionSha256,
        public SelectionInstant $occurredAt,
        public UserId $actor
    ) {}
}
