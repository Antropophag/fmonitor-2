<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final class AssignmentOrderCurrentEligibility
{
    /** Caller owns a write transaction. Positive source rows are locked until caller commit. */
    public static function confirm(\mysqli $db,string $prefix,array $installerTabIds,int $engineerUserId,string $documentDate):string
    {
        return MariaDbAssignmentOrderCurrentEligibility::confirm($db,$prefix,$installerTabIds,$engineerUserId,$documentDate);
    }
}
