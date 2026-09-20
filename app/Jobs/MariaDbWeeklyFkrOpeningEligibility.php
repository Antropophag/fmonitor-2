<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

use FMonitor2\AssignmentOrderComposition\MariaDbCurrentOpeningEligibility;

final readonly class MariaDbWeeklyFkrOpeningEligibility implements WeeklyFkrOpeningEligibility
{
    private MariaDbCurrentOpeningEligibility $canonical;
    public function __construct(\mysqli $db, string $prefix)
    {
        $this->canonical = new MariaDbCurrentOpeningEligibility($db, $prefix);
    }
    public function reasons(string $objectId, string $at): array
    {
        $date=(new \DateTimeImmutable($at))->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('Y-m-d');
        return $this->canonical->reasons((int)$objectId,$date);
    }
}
