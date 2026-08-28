<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Test-owned seam sentinel: any invocation is an observable contract violation. */
final class ProcessCommandCapabilitiesSchemaMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        $marker = getenv('PMR_V4_INVOCATION_MARKER');
        if ($marker !== false) {
            file_put_contents($marker, "v4 seam invoked\n", LOCK_EX);
        }
        return [
            'applied' => false,
            'schemaVersion' => 4,
            'reason' => 'SCHEMA_MIGRATION_CONFLICT',
            'conflictingTables' => [$tablePrefix . 'fm2_process_user_capabilities'],
        ];
    }
}
