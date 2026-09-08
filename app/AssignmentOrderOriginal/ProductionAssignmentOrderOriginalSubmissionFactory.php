<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/AssignmentOrderOriginalRuntime.php';
final class ProductionAssignmentOrderOriginalSubmissionFactory
{
    public static function create(\mysqli $db,string $prefix=''):AssignmentOrderOriginalSubmissionQuery
    { return new MariaDbOriginalSubmissionQuery(new AssignmentOrderOriginalSql($db,$prefix),$prefix); }
}
