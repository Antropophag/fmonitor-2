<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

/** Fresh read-only metadata for the existing database-setup preservation oracle. */
final class BatchedSchemaSnapshot
{
    public static function read(\mysqli $db, callable $normalizeCheck): array
    {
        $structure = [];
        $tables = $db->query('SELECT TABLE_NAME,ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY BINARY TABLE_NAME')->fetch_all(MYSQLI_ASSOC);
        foreach ($tables as $row) {
            $structure[$row['TABLE_NAME']] = [
                'table' => [$row['ENGINE'], $row['TABLE_COLLATION']],
                'columns' => [], 'keys' => [], 'foreignKeys' => [], 'checks' => [],
            ];
        }

        $columns = $db->query('SELECT TABLE_NAME,COLUMN_NAME,LOWER(COLUMN_TYPE) COLUMN_TYPE,IS_NULLABLE,CHARACTER_SET_NAME,COLLATION_NAME,COLUMN_DEFAULT,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() ORDER BY BINARY TABLE_NAME,ORDINAL_POSITION')->fetch_all(MYSQLI_ASSOC);
        foreach ($columns as $row) {
            $table = $row['TABLE_NAME'];
            unset($row['TABLE_NAME']);
            if ($row['IS_NULLABLE'] === 'YES' && $row['COLUMN_DEFAULT'] === 'NULL') {
                $row['COLUMN_DEFAULT'] = null;
            }
            $structure[$table]['columns'][] = array_values($row);
        }

        $keys = $db->query("SELECT TABLE_NAME,INDEX_NAME,NON_UNIQUE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() GROUP BY BINARY TABLE_NAME,TABLE_NAME,INDEX_NAME,NON_UNIQUE")->fetch_all(MYSQLI_ASSOC);
        foreach ($keys as $row) {
            $kind = $row['INDEX_NAME'] === 'PRIMARY' ? 'PRIMARY' : ((int) $row['NON_UNIQUE'] === 0 ? 'UNIQUE' : 'INDEX');
            $structure[$row['TABLE_NAME']]['keys'][] = [$kind, $row['COLUMNS']];
        }

        $foreignKeys = $db->query('SELECT k.TABLE_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.REFERENCED_TABLE_NAME IS NOT NULL')->fetch_all(MYSQLI_ASSOC);
        foreach ($foreignKeys as $row) {
            $structure[$row['TABLE_NAME']]['foreignKeys'][] = [
                $row['COLUMN_NAME'], $row['REFERENCED_TABLE_NAME'], $row['REFERENCED_COLUMN_NAME'],
                $row['UPDATE_RULE'] . '/' . $row['DELETE_RULE'],
            ];
        }

        $checks = $db->query('SELECT TABLE_NAME,CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() ORDER BY BINARY TABLE_NAME,BINARY CHECK_CLAUSE')->fetch_all(MYSQLI_ASSOC);
        foreach ($checks as $row) {
            $structure[$row['TABLE_NAME']]['checks'][] = $normalizeCheck($row['CHECK_CLAUSE']);
        }
        foreach ($structure as &$table) {
            sort($table['keys']);
            sort($table['foreignKeys']);
            sort($table['checks'], SORT_STRING);
        }
        unset($table);
        return $structure;
    }
}
