<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

interface WeeklyFkrReportSource
{
    public function allObjectsAsOf(string $start,string $end,string $generatedAt): array;
}
