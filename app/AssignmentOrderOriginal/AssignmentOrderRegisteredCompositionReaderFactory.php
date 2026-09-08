<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/AssignmentOrderOriginalRuntime.php';
final class AssignmentOrderRegisteredCompositionReaderFactory
{
    public static function create(\mysqli $connection, string $tablePrefix = ''): AssignmentOrderCompositionReader
    {
        AssignmentOrderRegisteredCompositionValues::prefix($tablePrefix);
        return new MariaDbRegisteredAssignmentOrderCompositionReader($connection, $tablePrefix, new NoOpAssignmentOrderRegisteredCompositionReadObserver());
    }
}
