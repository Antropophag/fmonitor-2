<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
use yii\db\Connection;
final class MariaDbYiiSchemaFingerprint
{
    public static function planningReady(Connection $db, string $prefix): bool
    {
        $collation = self::collation($db);
        foreach (InspectionPlanningDefinitionSchemaMigration::definitions($prefix, $collation) as $name => $definition) {
            if (!self::matches($db, $prefix . $name, $definition['manifest'], $collation)) return false;
        }
        return true;
    }

    public static function queueFamiliesReady(Connection $db, string $prefix): bool
    {
        if (!self::planningReady($db, $prefix)) return false;
        $collation = self::collation($db);
        foreach (InstallationCompletionDetailsSchemaMigration::currentDefinitions($prefix, $collation) as $name => $alternatives) {
            if (!self::matchesAny($db, $prefix . $name, $alternatives, $collation)) return false;
        }
        foreach (InspectionPhotoContentIndexSchemaMigration::currentDefinitions($prefix, $collation) as $name => $manifest) {
            if (!self::matches($db, $prefix . $name, $manifest, $collation)) return false;
        }
        return true;
    }

    public static function matches(Connection $db, string $table, array $expected, string $collation): bool
    {
        $properties = $db->createCommand(
            'SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES '
            . 'WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table',
            [':table'=>$table],
        )->queryOne();
        return $properties !== false
            && $properties['ENGINE'] === 'InnoDB'
            && $properties['TABLE_COLLATION'] === $collation
            && self::columns($db, $table) === $expected['columns']
            && self::indexes($db, $table) === $expected['indexes']
            && self::foreignKeys($db, $table) === ($expected['foreignKeys'] ?? [])
            && self::checks($db, $table) === self::expectedChecks($expected);
    }

    private static function matchesAny(Connection $db, string $table, array $alternatives, string $collation): bool
    {
        foreach ($alternatives as $manifest) {
            if (self::matches($db, $table, $manifest, $collation)) return true;
        }
        return false;
    }

    private static function columns(Connection $db, string $table): array
    {
        $rows = $db->createCommand(
            'SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,IS_GENERATED,'
            . 'GENERATION_EXPRESSION,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS '
            . 'WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table ORDER BY ORDINAL_POSITION',
            [':table'=>$table],
        )->queryAll();
        return array_map(static fn (array $row): array => [
            'name'=>$row['COLUMN_NAME'], 'type'=>$row['COLUMN_TYPE'], 'nullable'=>$row['IS_NULLABLE'],
            'default'=>in_array($row['COLUMN_DEFAULT'], [null, 'NULL'], true) ? null : (string)$row['COLUMN_DEFAULT'],
            'extra'=>$row['EXTRA'], 'generated'=>$row['IS_GENERATED'],
            'generationExpression'=>$row['GENERATION_EXPRESSION'], 'charset'=>$row['CHARACTER_SET_NAME'],
            'collation'=>$row['COLLATION_NAME'],
        ], $rows);
    }

    private static function indexes(Connection $db, string $table): array
    {
        $rows = $db->createCommand(
            'SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART,COLLATION,INDEX_TYPE,IGNORED '
            . 'FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table '
            . 'ORDER BY BINARY INDEX_NAME,SEQ_IN_INDEX',
            [':table'=>$table],
        )->queryAll();
        return array_map(static fn (array $row): array => [
            'name'=>$row['INDEX_NAME'], 'nonUnique'=>(int)$row['NON_UNIQUE'],
            'sequence'=>(int)$row['SEQ_IN_INDEX'], 'column'=>$row['COLUMN_NAME'],
            'subPart'=>$row['SUB_PART'] === null ? null : (int)$row['SUB_PART'],
            'collation'=>$row['COLLATION'], 'type'=>$row['INDEX_TYPE'], 'ignored'=>$row['IGNORED'],
        ], $rows);
    }

    private static function foreignKeys(Connection $db, string $table): array
    {
        $rows = $db->createCommand(
            'SELECT rc.CONSTRAINT_NAME,kcu.ORDINAL_POSITION,kcu.COLUMN_NAME,kcu.REFERENCED_TABLE_NAME,'
            . 'kcu.REFERENCED_COLUMN_NAME,rc.UPDATE_RULE,rc.DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS rc '
            . 'JOIN information_schema.KEY_COLUMN_USAGE kcu ON kcu.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA '
            . 'AND kcu.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND kcu.TABLE_NAME=rc.TABLE_NAME '
            . 'WHERE rc.CONSTRAINT_SCHEMA=DATABASE() AND rc.TABLE_NAME=:table '
            . 'ORDER BY BINARY rc.CONSTRAINT_NAME,kcu.ORDINAL_POSITION',
            [':table'=>$table],
        )->queryAll();
        return array_map(static fn (array $row): array => [
            'name'=>$row['CONSTRAINT_NAME'], 'sequence'=>(int)$row['ORDINAL_POSITION'],
            'column'=>$row['COLUMN_NAME'], 'target'=>$row['REFERENCED_TABLE_NAME'],
            'targetColumn'=>$row['REFERENCED_COLUMN_NAME'], 'update'=>$row['UPDATE_RULE'],
            'delete'=>$row['DELETE_RULE'],
        ], $rows);
    }

    private static function checks(Connection $db, string $table): array
    {
        $rows = $db->createCommand(
            'SELECT cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc '
            . 'JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA '
            . 'AND cc.TABLE_NAME=tc.TABLE_NAME AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME '
            . "WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME=:table AND tc.CONSTRAINT_TYPE='CHECK'",
            [':table'=>$table],
        )->queryColumn();
        $checks = array_map([self::class, 'normalizeCheck'], $rows);
        sort($checks, SORT_STRING);
        return $checks;
    }

    private static function expectedChecks(array $manifest): array
    {
        $checks = array_map([self::class, 'normalizeCheck'], $manifest['checks'] ?? []);
        sort($checks, SORT_STRING);
        return $checks;
    }

    private static function normalizeCheck(string $value): string
    {
        $value = strtolower(str_replace([" ", "\n", "\r", "\t", "\f", "\v", '`'], '', $value));
        while (strlen($value) >= 2 && $value[0] === '(' && $value[strlen($value) - 1] === ')') {
            $depth = 0;
            $whole = true;
            for ($index = 0, $length = strlen($value); $index < $length; $index++) {
                if ($value[$index] === '(') $depth++;
                elseif ($value[$index] === ')') $depth--;
                if ($depth === 0 && $index < $length - 1) { $whole = false; break; }
                if ($depth < 0) { $whole = false; break; }
            }
            if (!$whole || $depth !== 0) break;
            $value = substr($value, 1, -1);
        }
        return $value;
    }

    private static function collation(Connection $db): string
    {
        return (string)$db->createCommand('SELECT @@collation_database')->queryScalar();
    }
}
