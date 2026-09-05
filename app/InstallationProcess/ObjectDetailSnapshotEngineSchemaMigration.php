<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

require_once __DIR__ . '/DatabaseUnavailable.php';
require_once __DIR__ . '/ObjectDetailSnapshotSchemaObserver.php';
require_once __DIR__ . '/IdentityAccessDefinitionSchemaMigration.php';
require_once __DIR__ . '/MariaDbObjectDetailSnapshotSchemaFingerprint.php';

/** Canonical v12 owner for the data-free object-detail table family. */
final class ObjectDetailSnapshotEngineSchemaMigration
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

    public static function apply(\mysqli $connection, string $tablePrefix, ObjectDetailSnapshotSchemaObserver $observer): array
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
            $observer->observe(ObjectDetailSnapshotSchemaPhase::LOCK_ACQUIRED);
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
                if ($connection->query('CREATE TABLE `' . $table . '` ('
                    . implode(',', $definitions) . ',PRIMARY KEY (`object_id`))'
                    . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE `' . $collation . '`') !== true) {
                    throw new DatabaseUnavailable('Object-detail table creation unavailable.');
                }
                $created[] = $table;
                $observer->observe($member === 'fm2_pilot_object_details'
                    ? ObjectDetailSnapshotSchemaPhase::DETAILS_CREATED
                    : ObjectDetailSnapshotSchemaPhase::QUARANTINE_CREATED);
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
            $state = MariaDbObjectDetailSnapshotSchemaFingerprint::state($connection, $table, $columns, $collation);
            if ($state === 'ABSENT') {
                $missing[] = $table;
            } elseif ($state !== 'EXACT') {
                $conflicts[] = $table;
            }
        }
        sort($conflicts, SORT_STRING);
        return [$missing, $conflicts, $collation];
    }

}
