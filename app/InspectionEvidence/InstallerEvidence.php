<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final readonly class InstallerEvidence
{
    public function __construct(
        public int $tabId,
        public string $fullName,
        public string $position,
    ) {
    }
}
