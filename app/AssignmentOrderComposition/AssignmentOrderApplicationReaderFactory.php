<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final class AssignmentOrderApplicationReaderFactory
{
    public static function create(\mysqli $db, string $prefix = ''): AssignmentOrderApplicationReader
    { return new MariaDbAssignmentOrderApplicationReader(new MariaDbAssignmentOrderApplicationSql($db,$prefix)); }
}
