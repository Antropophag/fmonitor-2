<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final class ProductionConfirmedOriginalOpeningFactory
{
    public static function create(\mysqli $db,string $prefix='',?SelectionClock $clock=null):MariaDbConfirmedOriginalOpening
    {
        return new MariaDbConfirmedOriginalOpening(new MariaDbAssignmentOrderApplicationSql($db,$prefix),$clock??new SelectionSystemClock());
    }
}
