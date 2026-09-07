<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final class ProductionOriginalOpeningFactory
{
    public static function create(\mysqli $db, string $prefix = '', ?SelectionClock $clock = null): MariaDbOriginalOpening
    {
        if (preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1) throw new \InvalidArgumentException('Invalid opening configuration.');
        return new MariaDbOriginalOpening($db, $prefix, $clock ?? new SelectionSystemClock());
    }
}
