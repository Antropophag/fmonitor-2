<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class MariaDbAssignmentOrderSelectionSchemaRows
{
    public static function capture(\mysqli $db, array $table): array
    {
        $primary = array_values(array_filter($table['indexes'], static fn ($key) => $key['name'] === 'PRIMARY'))[0]['columns'];
        $rows = MariaDbAssignmentOrderSelectionSchemaSql::rows($db, 'SELECT * FROM `' . $table['name'] . '` ORDER BY `' . implode('`,`', $primary) . '`');
        $hash = hash_init('sha256'); $max = '0';
        foreach ($rows as &$row) {
            foreach ($table['columns'] as $column) {
                $key = $column['name']; $value = $row[$key];
                if ($value === null) { AssignmentOrderSelectionSchemaValues::require($column['nullable']); continue; }
                $value = (string)$value; $row[$key] = $value;
                if (str_contains($column['type'], 'int')) {
                    $limit = str_starts_with($column['type'], 'smallint') ? '65535' : ($column['type'] === 'int unsigned' ? '4294967295' : AssignmentOrderIdentityRegistryValues::MAX);
                    AssignmentOrderSelectionSchemaValues::decimal($value, in_array($key, ['expected_selection_revision','retryable'], true), $limit);
                    if ($key === 'retryable') { AssignmentOrderSelectionSchemaValues::require($value === '0'); }
                }
                elseif ($column['type'] === 'date') { AssignmentOrderSelectionSchemaValues::date($value); }
                elseif ($column['type'] === 'datetime(6)') { AssignmentOrderSelectionSchemaValues::instant($value); }
                if ($column['extra'] === 'auto_increment') { $max = $value; }
            }
            hash_update($hash, AssignmentOrderSelectionSchemaValues::json(array_values($row)) . "\n");
        }
        unset($row); $next = null;
        if (in_array('auto_increment', array_column($table['columns'], 'extra'), true)) {
            $next = AssignmentOrderSelectionSchemaValues::decimal(MariaDbAssignmentOrderSelectionSchemaSql::one($db, 'SELECT CAST(AUTO_INCREMENT AS CHAR) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?', [$table['name']])['n'], false, AssignmentOrderIdentityRegistryValues::EXHAUSTED);
            AssignmentOrderSelectionSchemaValues::require(AssignmentOrderIdentityRegistryValues::compare($next, $max) > 0);
        }
        return [$rows, new AssignmentOrderSelectionSchemaTableSnapshot($table['name'], hash('sha256', AssignmentOrderSelectionSchemaValues::json($table)), (string)count($rows), hash_final($hash), $next)];
    }
}
