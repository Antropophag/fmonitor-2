<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Read-only exact metadata classification for the object-detail family. */
final class MariaDbObjectDetailSnapshotSchemaFingerprint
{
    public static function state(\mysqli $connection, string $table, array $columns, string $collation): string
    {
        $properties = self::rows($connection,
            'SELECT ENGINE,TABLE_COLLATION,TABLE_TYPE FROM information_schema.TABLES'
            . ' WHERE TABLE_SCHEMA=DATABASE() AND BINARY TABLE_NAME=BINARY ?', $table);
        if ($properties === []) return 'ABSENT';
        if (count($properties) !== 1
            || $properties[0] !== ['ENGINE'=>'InnoDB', 'TABLE_COLLATION'=>$collation, 'TABLE_TYPE'=>'BASE TABLE']
            || !self::matches($connection, $table, $columns, $collation)) return 'CONFLICT';
        return 'EXACT';
    }

    private static function matches(\mysqli $connection, string $table, array $expected, string $collation): bool
    {
        $columns = self::rows($connection,
            'SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,GENERATION_EXPRESSION,CHARACTER_SET_NAME,COLLATION_NAME'
            . ' FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND BINARY TABLE_NAME=BINARY ? ORDER BY ORDINAL_POSITION', $table);
        if (array_column($columns, 'COLUMN_NAME') !== array_keys($expected)) return false;
        foreach ($columns as $column) {
            $name = $column['COLUMN_NAME'];
            $type = strtolower($column['COLUMN_TYPE']);
            if ($type === 'bigint unsigned') $type = 'bigint(20) unsigned';
            $character = $name !== 'object_id';
            if ($type !== $expected[$name] || $column['IS_NULLABLE'] !== 'NO'
                || $column['COLUMN_DEFAULT'] !== null || $column['EXTRA'] !== ''
                || ($column['GENERATION_EXPRESSION'] ?? '') !== ''
                || $column['CHARACTER_SET_NAME'] !== ($character ? 'utf8mb4' : null)
                || $column['COLLATION_NAME'] !== ($character ? $collation : null)) return false;
        }
        $indexes = self::rows($connection,
            'SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART,COLLATION,INDEX_TYPE,IGNORED'
            . ' FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND BINARY TABLE_NAME=BINARY ? ORDER BY INDEX_NAME,SEQ_IN_INDEX', $table);
        if (count($indexes) !== 1) return false;
        $index = $indexes[0];
        if ($index['INDEX_NAME'] !== 'PRIMARY' || (int) $index['NON_UNIQUE'] !== 0
            || (int) $index['SEQ_IN_INDEX'] !== 1 || $index['COLUMN_NAME'] !== 'object_id'
            || $index['SUB_PART'] !== null || $index['COLLATION'] !== 'A'
            || $index['INDEX_TYPE'] !== 'BTREE' || $index['IGNORED'] !== 'NO') return false;
        return self::rows($connection,
            "SELECT CONSTRAINT_TYPE FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()"
            . " AND BINARY TABLE_NAME=BINARY ? AND CONSTRAINT_TYPE IN ('FOREIGN KEY','CHECK')", $table) === [];
    }

    private static function rows(\mysqli $connection, string $sql, string $table): array
    {
        $statement = $connection->prepare($sql);
        try {
            $statement->bind_param('s', $table);
            $statement->execute();
            return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        } finally {
            $statement->close();
        }
    }
}

