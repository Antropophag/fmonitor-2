<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final readonly class ProductionInspectionEvidenceConfig
{
    public function __construct(public string $processTablePrefix)
    {
        if (
            strlen($processTablePrefix) > 25
            || preg_match('/^[A-Za-z0-9_]*$/D', $processTablePrefix) !== 1
        ) {
            throw new \InvalidArgumentException('Invalid inspection evidence configuration.');
        }
    }
}
