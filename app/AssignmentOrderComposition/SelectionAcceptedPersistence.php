<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionAcceptedPersistence
{
    public function __construct(
        public SelectionIdentityAllocation $allocation,
        public int $selectionRevision,
        public AssignmentOrderCompositionMode $mode,
        public ?int $previousSelectionOrderId,
        public ?int $replacesSelectionOrderId,
        public EngineerSnapshot $engineer,
        public array $installers,
        public string $selectionDate,
        public SelectionInstant $selectedAt,
        public UserId $actor,
        public SelectionNormalizedIntent $intent,
        public AssignmentOrderCompositionResult $selectedResult,
        public SelectionSelectedEvent $event,
        public SelectionSafeAttemptAudit $audit
    ) {}
}
