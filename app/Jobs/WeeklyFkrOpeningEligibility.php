<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

interface WeeklyFkrOpeningEligibility
{
    public function reasons(string $objectId,string $at): array;
}
