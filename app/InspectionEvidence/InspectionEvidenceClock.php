<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

interface InspectionEvidenceClock
{
    public function now(): \DateTimeImmutable;
}
