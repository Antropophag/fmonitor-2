<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Adds exact object scope while preserving NULL as the historical calculation-wide scope. */
final class OtizPaymentDecisionObjectScopeSchemaMigration
{
    public static function apply(\mysqli $db, string $prefix): array
    {
        if (preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1) throw new \InvalidArgumentException('INVALID_TABLE_PREFIX');
        $table = $prefix.'fm2_otiz_payment_decisions';
        $escaped = $db->real_escape_string($table);
        if ((int)$db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}'")->fetch_column() !== 1) throw new \RuntimeException('OTIZ_PAYMENT_DECISIONS_SCHEMA_MISSING');
        $readColumn = static fn() => $db->query("SELECT COLUMN_TYPE,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' AND COLUMN_NAME='object_id'")->fetch_assoc();
        $readIndex = static fn() => $db->query("SELECT COLUMN_NAME,NON_UNIQUE,INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' AND INDEX_NAME='decision_scope' ORDER BY SEQ_IN_INDEX")->fetch_all(\MYSQLI_ASSOC);
        $validColumn = static fn(mixed $column): bool => is_array($column) && strtolower((string)$column['COLUMN_TYPE']) === 'bigint(20) unsigned' && $column['IS_NULLABLE'] === 'YES';
        $validIndex = static fn(array $index): bool => array_column($index, 'COLUMN_NAME') === ['calculation_id','object_id','employee_id','active']
            && array_unique(array_column($index, 'NON_UNIQUE')) === ['1'] && array_unique(array_column($index, 'INDEX_TYPE')) === ['BTREE'];
        $column = $readColumn();
        $index = $readIndex();
        if (($column !== null && !$validColumn($column)) || ($index !== [] && !$validIndex($index))) throw new \RuntimeException('SCHEMA_MIGRATION_CONFLICT');
        $changed = [];
        if ($column === null) {
            $db->query("ALTER TABLE `{$table}` ADD object_id BIGINT UNSIGNED NULL AFTER calculation_id");
            $changed[] = $table.'.object_id';
        }
        if ($index === []) {
            $db->query("ALTER TABLE `{$table}` ADD KEY decision_scope(calculation_id,object_id,employee_id,active)");
            $changed[] = $table.'.decision_scope';
        }
        if (!$validColumn($readColumn()) || !$validIndex($readIndex())) throw new \RuntimeException('SCHEMA_MIGRATION_CONFLICT');
        return ['applied'=>$changed!==[],'schemaVersion'=>38,'tablesCreated'=>[],'columnsChanged'=>$changed];
    }
}
