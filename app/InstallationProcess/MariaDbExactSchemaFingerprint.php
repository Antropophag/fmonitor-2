<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Compares the exact metadata required by strict additive migrations. */
final class MariaDbExactSchemaFingerprint
{
    public static function matches(\mysqli $connection, string $table, array $expected, string $collation): bool
    {
        $escaped = $connection->real_escape_string($table);
        $properties = $connection->query(
            "SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}'",
        )->fetch_assoc();
        if ($properties === null || $properties['ENGINE'] !== 'InnoDB' || $properties['TABLE_COLLATION'] !== $collation) {
            return false;
        }

        $columns = $connection->query(
            'SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,IS_GENERATED,'
            . "GENERATION_EXPRESSION,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' ORDER BY ORDINAL_POSITION",
        )->fetch_all(MYSQLI_ASSOC);
        if (array_map([self::class, 'columnMetadata'], $columns) !== $expected['columns']) {
            return false;
        }

        $indexes = $connection->query(
            'SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART,COLLATION,INDEX_TYPE,IGNORED '
            . "FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' ORDER BY BINARY INDEX_NAME,SEQ_IN_INDEX",
        )->fetch_all(MYSQLI_ASSOC);
        $expectedIndexes = $expected['indexes'];
        usort($expectedIndexes, static fn (array $left, array $right): int =>
            strcmp($left['name'], $right['name']) ?: ($left['sequence'] <=> $right['sequence'])
        );
        if (array_map([self::class, 'indexMetadata'], $indexes) !== $expectedIndexes) {
            return false;
        }

        $constraints = $connection->query(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' AND CONSTRAINT_TYPE IN ('FOREIGN KEY','CHECK')",
        )->fetch_all(MYSQLI_ASSOC);
        return $constraints === [];
    }

    private static function columnMetadata(array $row): array
    {
        return [
            'name'=>$row['COLUMN_NAME'], 'type'=>$row['COLUMN_TYPE'], 'nullable'=>$row['IS_NULLABLE'],
            // MariaDB reports an implicit/explicit SQL NULL default either as
            // metadata NULL or the unquoted token NULL, depending on nullability.
            'default'=>in_array($row['COLUMN_DEFAULT'], [null, 'NULL'], true)
                ? null
                : (string) $row['COLUMN_DEFAULT'],
            'extra'=>$row['EXTRA'], 'generated'=>$row['IS_GENERATED'],
            'generationExpression'=>$row['GENERATION_EXPRESSION'], 'charset'=>$row['CHARACTER_SET_NAME'],
            'collation'=>$row['COLLATION_NAME'],
        ];
    }

    private static function indexMetadata(array $row): array
    {
        return [
            'name'=>$row['INDEX_NAME'], 'nonUnique'=>(int) $row['NON_UNIQUE'],
            'sequence'=>(int) $row['SEQ_IN_INDEX'], 'column'=>$row['COLUMN_NAME'],
            'subPart'=>$row['SUB_PART'] === null ? null : (int) $row['SUB_PART'],
            'collation'=>$row['COLLATION'], 'type'=>$row['INDEX_TYPE'], 'ignored'=>$row['IGNORED'],
        ];
    }
}
