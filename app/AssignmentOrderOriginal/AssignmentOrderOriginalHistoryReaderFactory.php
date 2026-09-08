<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/AssignmentOrderOriginalRuntime.php';
final class AssignmentOrderOriginalHistoryReaderFactory
{
    public static function create(\mysqli $db, string $privateStorageRoot, string $prefix = ''): AssignmentOrderOriginalHistoryReader
    {
        if (\preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1 || !\str_starts_with($privateStorageRoot, '/')
            || \strlen($privateStorageRoot) > 4096 || \str_contains($privateStorageRoot, "\0"))
            throw new AssignmentOrderOriginalHistoryConfigurationUnavailable();
        return new MariaDbOriginalHistoryReader(new AssignmentOrderOriginalSql($db, $prefix), $privateStorageRoot);
    }
}
