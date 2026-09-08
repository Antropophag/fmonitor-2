<?php
declare(strict_types=1);
namespace FMonitor2\InspectionEvidence;

/** Public construction of the shared read-only checklist progress projection. */
final class ProductionChecklistProgressFactory
{
    public static function create(\mysqli $db,string $prefix=''):MariaDbChecklistProgress
    {
        return new MariaDbChecklistProgress($db,$prefix);
    }
}
