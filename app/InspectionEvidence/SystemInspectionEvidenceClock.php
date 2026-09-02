<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final class SystemInspectionEvidenceClock implements InspectionEvidenceClock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow'));
    }
}
