<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Exact MariaDB metadata adapter for the structured planning-v9 definition. */
final class MariaDbInspectionPlanningSchemaFingerprint
{
    public static function manifest(array $schema, string $collation): array
    {
        $columns = array_map(static function(array $column) use ($collation): array {
            $type = strtolower($column['type']);
            $character = str_starts_with($type, 'varchar') || $type === 'longtext';
            return [
                'name'=>$column['name'],
                'type'=>$type === 'bigint unsigned' ? 'bigint(20) unsigned' : $type,
                'nullable'=>'NO', 'default'=>null, 'extra'=>$column['extra'],
                'generated'=>'NEVER', 'generationExpression'=>null,
                'charset'=>$character ? 'utf8mb4' : null,
                'collation'=>$character ? ($column['columnCollation'] ?? $collation) : null,
            ];
        }, $schema['columns']);
        $indexes = [];
        foreach ($schema['indexes'] as $index) {
            foreach ($index['columns'] as $offset => $column) {
                $indexes[] = [
                    'name'=>$index['name'], 'nonUnique'=>$index['unique'] ? 0 : 1,
                    'sequence'=>$offset + 1, 'column'=>$column, 'subPart'=>null,
                    'collation'=>'A', 'type'=>'BTREE', 'ignored'=>'NO',
                ];
            }
        }
        usort($indexes, static fn(array $left, array $right): int =>
            strcmp($left['name'], $right['name']) ?: $left['sequence'] <=> $right['sequence']);
        $checks = array_map([self::class, 'normalize'], $schema['checks']);
        sort($checks, SORT_STRING);
        return compact('columns', 'indexes', 'checks');
    }

    public static function matches(\mysqli $db,string $table,array $expected,string $collation):bool
    {
        $escaped = $db->real_escape_string($table);
        $properties = MariaDbSchemaInspector::tableProperties($db, $table);
        if ($properties === null || $properties['ENGINE'] !== 'InnoDB'
            || $properties['TABLE_COLLATION'] !== $collation) return false;
        $rows = $db->query('SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,'
            . 'IS_GENERATED,GENERATION_EXPRESSION,CHARACTER_SET_NAME,COLLATION_NAME '
            . "FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' "
            . 'ORDER BY ORDINAL_POSITION')->fetch_all(MYSQLI_ASSOC);
        $columns = array_map(static fn(array $row): array => [
            'name'=>$row['COLUMN_NAME'], 'type'=>$row['COLUMN_TYPE'],
            'nullable'=>$row['IS_NULLABLE'],
            'default'=>in_array($row['COLUMN_DEFAULT'], [null, 'NULL'], true)
                ? null : (string)$row['COLUMN_DEFAULT'],
            'extra'=>$row['EXTRA'], 'generated'=>$row['IS_GENERATED'],
            'generationExpression'=>$row['GENERATION_EXPRESSION'],
            'charset'=>$row['CHARACTER_SET_NAME'], 'collation'=>$row['COLLATION_NAME'],
        ], $rows);
        if ($columns !== $expected['columns']) return false;
        $rows = $db->query('SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART,'
            . "COLLATION,INDEX_TYPE,IGNORED FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() "
            . "AND TABLE_NAME='{$escaped}' ORDER BY BINARY INDEX_NAME,SEQ_IN_INDEX")->fetch_all(MYSQLI_ASSOC);
        $indexes = array_map(static fn(array $row): array => [
            'name'=>$row['INDEX_NAME'], 'nonUnique'=>(int)$row['NON_UNIQUE'],
            'sequence'=>(int)$row['SEQ_IN_INDEX'], 'column'=>$row['COLUMN_NAME'],
            'subPart'=>$row['SUB_PART'] === null ? null : (int)$row['SUB_PART'],
            'collation'=>$row['COLLATION'], 'type'=>$row['INDEX_TYPE'], 'ignored'=>$row['IGNORED'],
        ], $rows);
        if ($indexes !== $expected['indexes']) return false;
        $rows = $db->query('SELECT tc.CONSTRAINT_TYPE,cc.CHECK_CLAUSE '
            . 'FROM information_schema.TABLE_CONSTRAINTS tc LEFT JOIN information_schema.CHECK_CONSTRAINTS cc '
            . 'ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.TABLE_NAME=tc.TABLE_NAME '
            . "AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() "
            . "AND tc.TABLE_NAME='{$escaped}' AND tc.CONSTRAINT_TYPE IN ('FOREIGN KEY','CHECK')")
            ->fetch_all(MYSQLI_ASSOC);
        $checks = [];
        foreach ($rows as $row) {
            if ($row['CONSTRAINT_TYPE'] !== 'CHECK') return false;
            $checks[] = self::normalize((string)$row['CHECK_CLAUSE']);
        }
        sort($checks, SORT_STRING);
        return $checks === $expected['checks'];
    }

    private static function normalize(string $value): string
    {
        $value = strtolower(str_replace([" ","\n","\r","\t","\f","\v",'`'], '', $value));
        while (strlen($value) >= 2 && $value[0] === '(' && $value[strlen($value)-1] === ')') {
            $depth=0; $whole=true;
            for ($i=0, $length=strlen($value); $i<$length; $i++) {
                if ($value[$i] === '(') $depth++;
                elseif ($value[$i] === ')') $depth--;
                if ($depth === 0 && $i<$length-1) { $whole=false; break; }
                if ($depth < 0) { $whole=false; break; }
            }
            if (!$whole || $depth !== 0) break;
            $value = substr($value, 1, -1);
        }
        return $value;
    }
}
