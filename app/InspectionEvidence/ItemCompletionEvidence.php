<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final readonly class ItemCompletionEvidence
{
    /** @param list<InstallerEvidence> $installerSnapshots */
    public function __construct(
        public string $clientOperationId,
        public int $installationCaseId,
        public int $sectionId,
        public int $itemId,
        public int $actorUserId,
        public ?int $assignedControlEngineerUserIdAtReceipt,
        public string $deviceTime,
        public string $serverReceivedAt,
        public int $baseRevision,
        public int $acceptedRevision,
        public int $templateId,
        public string $templateVersion,
        public string $templateSha256,
        public int $currentChecklistRevision,
        public array $installerSnapshots,
    ) {
    }
}
