<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final readonly class CompleteInspectionItem
{
    /** @param list<int> $installerTabIds */
    public function __construct(
        public int $actorUserId,
        public int $installationCaseId,
        public string $clientOperationId,
        public string $deviceInstallationId,
        public string $deviceTime,
        public int $expectedRevision,
        public int $sectionId,
        public int $itemId,
        public array $installerTabIds,
    ) {
    }
}
