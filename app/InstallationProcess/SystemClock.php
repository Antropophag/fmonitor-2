<?php
declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class SystemClock implements Clock
{
    public function now(): string
    {
        return (new \DateTimeImmutable('now'))->format('Y-m-d\TH:i:sP');
    }
}
