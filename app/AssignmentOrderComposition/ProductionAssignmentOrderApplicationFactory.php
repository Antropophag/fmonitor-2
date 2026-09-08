<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplicationReferenceFactory;

final class ProductionAssignmentOrderApplicationFactory
{
    public static function create(\mysqli $db, string $prefix = '', ?SelectionClock $clock = null): AssignmentOrderApplication
    {
        $sql=new MariaDbAssignmentOrderApplicationSql($db,$prefix);
        return new MariaDbAssignmentOrderApplication($sql,AssignmentOrderOriginalApplicationReferenceFactory::create($db,$prefix),$clock??new SelectionSystemClock());
    }
}
