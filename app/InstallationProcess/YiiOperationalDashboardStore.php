<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

interface YiiOperationalDashboardStore
{
    public function authorized(int $actorId): bool;

    /** @param list<array{0:string,1:string}> $weeks */
    public function read(string $cutoff, string $windowEnd, array $weeks): array;
}
