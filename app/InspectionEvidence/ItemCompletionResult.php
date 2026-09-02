<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final readonly class ItemCompletionResult
{
    public function __construct(
        public string $status,
        public int $revision,
    ) {
    }
}
