<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

require_once __DIR__ . '/DatabaseUnavailable.php';
require_once __DIR__ . '/IdentityAccessDefinitionSchemaMigration.php';

/** Canonical v12 owner for the data-free object-detail table family. */
final class ObjectDetailSnapshotSchemaMigration
{
    private const MEMBERS = [
        'fm2_pilot_object_details' => [
            'object_id' => 'bigint(20) unsigned',
            'schema_version' => 'varchar(80)',
            'content_sha256' => 'char(64)',
            'payload_json' => 'longtext',
            'captured_at' => 'varchar(40)',
        ],
        'fm2_pilot_object_detail_quarantine' => [
            'object_id' => 'bigint(20) unsigned',
            'code' => 'varchar(80)',
            'schema_version' => 'varchar(80)',
            'content_sha256' => 'char(64)',
            'captured_at' => 'varchar(40)',
        ],
    ];

    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        self::assertPrefix($tablePrefix);
        $lock = null;
        $locked = false;
        try {
            $database = $connection->query('SELECT DATABASE()')->fetch_column();
            if (!is_string($database) || $database === '') {
                throw new DatabaseUnavailable('Object-detail database identity unavailable.');
            }
            $lock = hash('sha256', "object-detail-schema-v1\0{$database}\0{$tablePrefix}");
            if ((string)$connection->query("SELECT GET_LOCK('{$lock}',5)")->fetch_column() !== '1') {
                throw new DatabaseUnavailable('Object-detail migration lock unavailable.');
            }
            $locked = true;
            [$missing, $conflicts, $collation] = self::inspect($connection, $tablePrefix);
            if ($conflicts !== []) {
                return ['applied'=>false, 'schemaVersion'=>12,
                    'reason'=>'SCHEMA_MIGRATION_CONFLICT', 'conflictingTables'=>$conflicts];
            }
            $created = [];
            foreach (self::MEMBERS as $member => $columns) {
                $table = $tablePrefix . $member;
                if (!in_array($table, $missing, true)) continue;
                $definitions = [];
                foreach ($columns as $name => $type) {
                    $definitions[] = "`{$name}` {$type} NOT NULL";
                }
                $connection->query('CREATE TABLE `' . $table . '` ('
                    . implode(',', $definitions) . ',PRIMARY KEY (`object_id`))'
                    . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE `' . $collation . '`');
                $created[] = $table;
            }
            [$missingAfter, $conflictsAfter] = self::inspect($connection, $tablePrefix);
            if ($missingAfter !== [] || $conflictsAfter !== []) {
                throw new DatabaseUnavailable('Object-detail schema verification unavailable.');
            }
            sort($created, SORT_STRING);
            return ['applied'=>$created !== [], 'schemaVersion'=>12, 'tablesCreated'=>$created];
        } catch (DatabaseUnavailable $error) {
            throw $error;
        } catch (\Throwable) {
            throw new DatabaseUnavailable('Object-detail schema unavailable.');
        } finally {
            if ($locked) {
                try {
                    if ((string)$connection->query("SELECT RELEASE_LOCK('{$lock}')")->fetch_column() !== '1') {
                        throw new DatabaseUnavailable('Object-detail migration lock release unavailable.');
                    }
                } catch (\Throwable) {
                    throw new DatabaseUnavailable('Object-detail migration lock release unavailable.');
                }
            }
        }
    }

    public static function isCompleteCompatible(\mysqli $connection, string $tablePrefix = ''): bool
    {
        try {
            self::assertPrefix($tablePrefix);
            [$missing, $conflicts] = self::inspect($connection, $tablePrefix);
            return $missing === [] && $conflicts === [];
        } catch (\Throwable) {
            return false;
        }
    }

    private static function assertPrefix(string $prefix): void
    {
        if (strlen($prefix) > 25 || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException('Invalid table prefix.');
        }
    }

    /** @return array{list<string>,list<string>,string} */
    private static function inspect(\mysqli $connection, string $prefix): array
    {
        $collation = IdentityAccessDefinitionSchemaMigration::databaseCollation($connection);
        $missing = [];
        $conflicts = [];
        foreach (self::MEMBERS as $member => $columns) {
            $table = $prefix . $member;
            $properties = self::rows($connection,
                'SELECT ENGINE,TABLE_COLLATION,TABLE_TYPE FROM information_schema.TABLES'
                . ' WHERE TABLE_SCHEMA=DATABASE() AND BINARY TABLE_NAME=BINARY ?', $table);
            if ($properties === []) {
                $missing[] = $table;
            } elseif (count($properties) !== 1
                || $properties[0] !== ['ENGINE'=>'InnoDB', 'TABLE_COLLATION'=>$collation, 'TABLE_TYPE'=>'BASE TABLE']
                || !self::matches($connection, $table, $columns, $collation)) {
                $conflicts[] = $table;
            }
        }
        sort($conflicts, SORT_STRING);
        return [$missing, $conflicts, $collation];
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
