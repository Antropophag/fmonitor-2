<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
use FMonitor2\AssignmentOrderOriginal as O;

final class ProductionOriginalStartFactory
{
    public static function create(\mysqli$db,string$prefix,O\AssignmentOrderOriginalApplication$original,?SelectionClock$clock=null):MariaDbOriginalStartOwner
    {return new MariaDbOriginalStartOwner(new MariaDbAssignmentOrderApplicationSql($db,$prefix),$original,$clock??new SelectionSystemClock());}
}
