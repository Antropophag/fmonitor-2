<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/AssignmentOrderOriginalRuntime.php';

final class AssignmentOrderOriginalApplicationReferenceFactory
{
    public static function create(\mysqli $db, string $prefix = ''): AssignmentOrderOriginalApplicationReferenceReader
    {
        if (\preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1)
            throw new AssignmentOrderOriginalApplicationReferenceConfigurationUnavailable();
        return new MariaDbOriginalApplicationReferenceReader(new AssignmentOrderOriginalSql($db, $prefix));
    }
}
