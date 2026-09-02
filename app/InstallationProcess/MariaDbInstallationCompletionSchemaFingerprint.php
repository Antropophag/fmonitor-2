<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Renders and compares exact MariaDB metadata from the structured v10 schema. */
final class MariaDbInstallationCompletionSchemaFingerprint
{
    public static function manifest(array $schema, string $collation): array
    {
        $columns = array_map(static function(array $column) use ($collation): array {
            $character = preg_match('/^(?:VARCHAR|ENUM)/', $column['type']) === 1;
            return ['name'=>$column['name'],'type'=>self::metadataType($column['type']),
                'nullable'=>$column['nullable']?'YES':'NO','default'=>$column['default'],
                'extra'=>$column['extra'],'generated'=>'NEVER','generationExpression'=>null,
                'charset'=>$character?'utf8mb4':null,'collation'=>$character?$collation:null];
        }, $schema['columns']);
        $indexes = [];
        foreach ($schema['indexes'] as $index) foreach ($index['columns'] as $offset => $column) {
            $indexes[]=['name'=>$index['name'],'nonUnique'=>$index['unique']?0:1,
                'sequence'=>$offset+1,'column'=>$column,'subPart'=>null,'collation'=>'A',
                'type'=>'BTREE','ignored'=>'NO'];
        }
        usort($indexes, static fn(array $a,array $b):int =>
            strcmp($a['name'],$b['name']) ?: $a['sequence'] <=> $b['sequence']);
        $foreignKeys=[];
        foreach($schema['foreignKeys'] as$foreignKey)foreach($foreignKey['columns']as$offset=>$column){
            $foreignKeys[]=['name'=>$foreignKey['name'],'sequence'=>$offset+1,'column'=>$column,
                'target'=>$foreignKey['target'],'targetColumn'=>$foreignKey['targetColumns'][$offset],
                'update'=>'RESTRICT','delete'=>'RESTRICT'];
        }
        usort($foreignKeys,static fn(array$a,array$b):int=>
            strcmp($a['name'],$b['name'])?:$a['sequence']<=>$b['sequence']);
        $checks = array_map([self::class, 'normalize'], $schema['checks']);
        sort($checks, SORT_STRING);
        return compact('columns', 'indexes', 'foreignKeys', 'checks');
    }

    public static function matches(\mysqli $db, string $table, array $expected, string $collation): bool
    {
        $escaped = $db->real_escape_string($table);
        $properties = MariaDbSchemaInspector::tableProperties($db, $table);
        if ($properties === null || $properties['ENGINE'] !== 'InnoDB'
            || $properties['TABLE_COLLATION'] !== $collation) return false;
        $rows = $db->query('SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,'
            . 'IS_GENERATED,GENERATION_EXPRESSION,CHARACTER_SET_NAME,COLLATION_NAME '
            . "FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' "
            . 'ORDER BY ORDINAL_POSITION')->fetch_all(MYSQLI_ASSOC);
        $columns = array_map(static fn(array $r): array => [
            'name'=>$r['COLUMN_NAME'], 'type'=>$r['COLUMN_TYPE'], 'nullable'=>$r['IS_NULLABLE'],
            'default'=>in_array($r['COLUMN_DEFAULT'], [null, 'NULL'], true) ? null : (string)$r['COLUMN_DEFAULT'],
            'extra'=>$r['EXTRA'], 'generated'=>$r['IS_GENERATED'],
            'generationExpression'=>$r['GENERATION_EXPRESSION'], 'charset'=>$r['CHARACTER_SET_NAME'],
            'collation'=>$r['COLLATION_NAME'],
        ], $rows);
        if ($columns !== $expected['columns']) return false;
        $rows = $db->query('SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART,COLLATION,'
            . "INDEX_TYPE,IGNORED FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() "
            . "AND TABLE_NAME='{$escaped}' ORDER BY BINARY INDEX_NAME,SEQ_IN_INDEX")->fetch_all(MYSQLI_ASSOC);
        $indexes = array_map(static fn(array $r): array => [
            'name'=>$r['INDEX_NAME'], 'nonUnique'=>(int)$r['NON_UNIQUE'],
            'sequence'=>(int)$r['SEQ_IN_INDEX'], 'column'=>$r['COLUMN_NAME'],
            'subPart'=>$r['SUB_PART'] === null ? null : (int)$r['SUB_PART'],
            'collation'=>$r['COLLATION'], 'type'=>$r['INDEX_TYPE'], 'ignored'=>$r['IGNORED'],
        ], $rows);
        if ($indexes !== $expected['indexes']) return false;
        $rows = $db->query('SELECT rc.CONSTRAINT_NAME,kcu.ORDINAL_POSITION,kcu.COLUMN_NAME,'
            . 'kcu.REFERENCED_TABLE_NAME,kcu.REFERENCED_COLUMN_NAME,rc.UPDATE_RULE,rc.DELETE_RULE '
            . 'FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE kcu '
            . 'ON kcu.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND kcu.CONSTRAINT_NAME=rc.CONSTRAINT_NAME '
            . "AND kcu.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=DATABASE() AND rc.TABLE_NAME='{$escaped}' "
            . 'ORDER BY BINARY rc.CONSTRAINT_NAME,kcu.ORDINAL_POSITION')->fetch_all(MYSQLI_ASSOC);
        $foreignKeys = array_map(static fn(array $r): array => [
            'name'=>$r['CONSTRAINT_NAME'], 'sequence'=>(int)$r['ORDINAL_POSITION'],
            'column'=>$r['COLUMN_NAME'], 'target'=>$r['REFERENCED_TABLE_NAME'],
            'targetColumn'=>$r['REFERENCED_COLUMN_NAME'], 'update'=>$r['UPDATE_RULE'],
            'delete'=>$r['DELETE_RULE'],
        ], $rows);
        if ($foreignKeys !== $expected['foreignKeys']) return false;
        $rows = $db->query('SELECT cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc '
            . 'JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA '
            . 'AND cc.TABLE_NAME=tc.TABLE_NAME AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME '
            . "WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME='{$escaped}' "
            . "AND tc.CONSTRAINT_TYPE='CHECK'")->fetch_all(MYSQLI_ASSOC);
        $checks = array_map(static fn(array $r): string => self::normalize((string)$r['CHECK_CLAUSE']), $rows);
        sort($checks, SORT_STRING);
        return $checks === $expected['checks'];
    }

    private static function metadataType(string $type):string
    {
        $type = strtolower($type);
        return match ($type) {
            'bigint unsigned' => 'bigint(20) unsigned',
            'int unsigned' => 'int(10) unsigned',
            default => $type,
        };
    }

    private static function normalize(string $value): string
    {
        $value = strtolower(str_replace([" ","\n","\r","\t","\f","\v",'`'], '', $value));
        while (strlen($value) > 1 && $value[0] === '(' && $value[strlen($value)-1] === ')') {
            $depth = 0; $whole = true;
            for ($i=0, $length=strlen($value); $i<$length; $i++) {
                if ($value[$i] === '(') $depth++;
                elseif ($value[$i] === ')') $depth--;
                if ($depth === 0 && $i < $length-1) { $whole=false; break; }
            }
            if (!$whole || $depth !== 0) break;
            $value = substr($value, 1, -1);
        }
        return $value;
    }
}
