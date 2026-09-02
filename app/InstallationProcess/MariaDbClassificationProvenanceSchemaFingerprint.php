<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Exact v11 fingerprint; secondary index presentation names are ignored. */
final class MariaDbClassificationProvenanceSchemaFingerprint
{
    public static function matches(\mysqli $connection, string $table, array $definition, string $collation): bool
    {
        $escaped = $connection->real_escape_string($table);
        $properties = MariaDbSchemaInspector::tableProperties($connection, $table);
        if ($properties === null || $properties['ENGINE'] !== 'InnoDB'
            || $properties['TABLE_COLLATION'] !== $collation) {
            return false;
        }

        $rows = $connection->query(
            'SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,IS_GENERATED,'
            . 'GENERATION_EXPRESSION,CHARACTER_SET_NAME,COLLATION_NAME '
            . "FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' "
            . 'ORDER BY ORDINAL_POSITION',
        )->fetch_all(MYSQLI_ASSOC);
        $columns = array_map(static fn (array $row): array => [
            'name'=>$row['COLUMN_NAME'], 'type'=>$row['COLUMN_TYPE'], 'nullable'=>$row['IS_NULLABLE'],
            'default'=>in_array($row['COLUMN_DEFAULT'], [null, 'NULL'], true)
                ? null : (string) $row['COLUMN_DEFAULT'],
            'extra'=>$row['EXTRA'], 'generated'=>$row['IS_GENERATED'],
            'generationExpression'=>$row['GENERATION_EXPRESSION'], 'charset'=>$row['CHARACTER_SET_NAME'],
            'collation'=>$row['COLLATION_NAME'],
        ], $rows);
        if ($columns !== $definition['columns']) {
            return false;
        }

        $rows = $connection->query(
            'SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART,COLLATION,INDEX_TYPE,IGNORED '
            . "FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' "
            . 'ORDER BY BINARY INDEX_NAME,SEQ_IN_INDEX',
        )->fetch_all(MYSQLI_ASSOC);
        $grouped = [];
        foreach ($rows as $row) {
            $name = (string) $row['INDEX_NAME'];
            $key = ($name === 'PRIMARY' ? 'PRIMARY' : 'SECONDARY') . "\0" . $name;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'identity'=>$name === 'PRIMARY' ? 'PRIMARY' : 'SECONDARY',
                    'nonUnique'=>(int) $row['NON_UNIQUE'], 'columns'=>[],
                ];
            }
            if ($row['SUB_PART'] !== null || $row['COLLATION'] !== 'A'
                || $row['INDEX_TYPE'] !== 'BTREE' || $row['IGNORED'] !== 'NO') {
                return false;
            }
            $grouped[$key]['columns'][] = (string) $row['COLUMN_NAME'];
        }
        $indexes = array_values($grouped);
        $sort = static fn (array $left, array $right): int =>
            strcmp($left['identity'], $right['identity'])
            ?: ($left['nonUnique'] <=> $right['nonUnique'])
            ?: strcmp(implode("\0", $left['columns']), implode("\0", $right['columns']));
        usort($indexes, $sort);
        $expected = $definition['indexes'];
        usort($expected, $sort);
        if ($indexes !== $expected) {
            return false;
        }

        $constraints = $connection->query(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() "
            . "AND TABLE_NAME='{$escaped}' AND CONSTRAINT_TYPE IN ('FOREIGN KEY','CHECK')",
        )->fetch_all(MYSQLI_ASSOC);
        return $constraints === [];
    }
}
