<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final class ProductionAssignmentOrderSelectionPortalFactory
{
    public static function create(\mysqli $db,string $prefix=''):AssignmentOrderSelectionPortalQuery
    { return new MariaDbSelectionPortalQuery(new MariaDbSelectionSql($db,$prefix)); }
}
