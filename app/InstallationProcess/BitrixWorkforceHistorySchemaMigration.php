<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;
final class BitrixWorkforceHistorySchemaMigration
{
    private const LOGICAL_TABLES = [
        'fm2_workforce_catalog',
        'fm2_workforce_observations',
        'fm2_workforce_sync_runs',
        'fm2_workforce_sync_metadata',
    ];

    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        if (preg_match('/^[A-Za-z0-9_]{0,37}$/D', $tablePrefix) !== 1) {
            throw new \InvalidArgumentException('Invalid table prefix.');
        }

        $tables = array_combine(
            self::LOGICAL_TABLES,
            array_map(static fn (string $name): string => $tablePrefix . $name, self::LOGICAL_TABLES),
        );
        $databaseCollation = self::rows($connection, 'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()')[0]['DEFAULT_COLLATION_NAME'];
        $states = [];
        $conflicts = [];

        foreach ($tables as $logicalName => $table) {
            $states[$logicalName] = self::classify($connection, $logicalName, $table, $tablePrefix);
            if ($states[$logicalName] === 'conflict') {
                $conflicts[] = $table;
            }
        }

        if ($conflicts !== []) {
            sort($conflicts, SORT_STRING);

            return [
                'applied' => false,
                'schemaVersion' => 5,
                'reason' => 'SCHEMA_MIGRATION_CONFLICT',
                'conflictingTables' => $conflicts,
            ];
        }

        $created = [];
        if ($states['fm2_workforce_sync_runs'] === 'absent') {
            self::createRuns($connection, $tables['fm2_workforce_sync_runs'], $tablePrefix, $databaseCollation);
            $created[] = $tables['fm2_workforce_sync_runs'];
        }
        if ($states['fm2_workforce_observations'] === 'absent') {
            self::createObservations($connection, $tables['fm2_workforce_observations'], $tables['fm2_workforce_sync_runs'], $tablePrefix, $databaseCollation);
            $created[] = $tables['fm2_workforce_observations'];
        }
        if ($states['fm2_workforce_sync_metadata'] === 'absent') {
            self::createMetadata($connection, $tables['fm2_workforce_sync_metadata'], $tables['fm2_workforce_sync_runs'], $tablePrefix, $databaseCollation);
            $created[] = $tables['fm2_workforce_sync_metadata'];
        }

        $altered = [];
        if ($states['fm2_workforce_catalog'] === 'v2') {
            self::upgradeCatalog($connection, $tables['fm2_workforce_catalog'], $tablePrefix);
            $altered[] = $tables['fm2_workforce_catalog'];
        }

        $seeded = false;
        if ($states['fm2_workforce_sync_metadata'] === 'absent' || $states['fm2_workforce_sync_metadata'] === 'empty') {
            $connection->query("INSERT INTO `{$tables['fm2_workforce_sync_metadata']}` (singleton_id, last_successful_run_id, last_successful_at) VALUES (1, NULL, NULL)");
            $seeded = true;
        }

        $reportOrder = [
            $tables['fm2_workforce_observations'],
            $tables['fm2_workforce_sync_runs'],
            $tables['fm2_workforce_sync_metadata'],
        ];
        $created = array_values(array_intersect($reportOrder, $created));

        return [
            'applied' => $created !== [] || $altered !== [] || $seeded,
            'schemaVersion' => 5,
            'tablesCreated' => $created,
            'tablesAltered' => $altered,
        ];
    }

    public static function classify(\mysqli $connection, string $logicalName, string $table, string $prefix): string
    {
        if (!MariaDbSchemaInspector::tableExists($connection, $table)) {
            return $logicalName === 'fm2_workforce_catalog' ? 'conflict' : 'absent';
        }

        if ($logicalName === 'fm2_workforce_catalog' && self::matches($connection, $table, self::v2Definition())) {
            return 'v2';
        }
        if (!self::matches($connection, $table, self::v5Definitions($prefix)[$logicalName])) {
            return 'conflict';
        }
        if ($logicalName !== 'fm2_workforce_sync_metadata') {
            return 'v5';
        }

        $rows = self::rows($connection, "SELECT singleton_id FROM `{$table}` ORDER BY singleton_id");
        if ($rows === []) {
            return 'empty';
        }

        return count($rows) === 1 && (string) $rows[0]['singleton_id'] === '1' ? 'v5' : 'conflict';
    }

    private static function matches(\mysqli $connection, string $table, array $expected): bool
    {
        $properties = MariaDbSchemaInspector::tableProperties($connection, $table);
        $databaseCollation = self::rows($connection, 'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()')[0]['DEFAULT_COLLATION_NAME'];
        if ($properties === null || $properties['ENGINE'] !== 'InnoDB' || $properties['TABLE_COLLATION'] !== $databaseCollation) {
            return false;
        }

        $columns = self::rows($connection, "SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,EXTRA,COLUMN_DEFAULT,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $connection->real_escape_string($table) . "' ORDER BY ORDINAL_POSITION");
        $columns = array_map(static function (array $column) use ($databaseCollation): array {
            $type = preg_replace('/^(bigint|int|tinyint)\(\d+\)/', '$1', $column['COLUMN_TYPE']);
            $collation = $column['COLLATION_NAME'];
            if ($collation !== null && $collation === $databaseCollation) {
                $collation = 'DATABASE';
            }

            return [$column['COLUMN_NAME'], $type, $column['IS_NULLABLE'], $column['EXTRA'], $column['COLUMN_DEFAULT'] === null ? 'NULL' : (string) $column['COLUMN_DEFAULT'], $column['CHARACTER_SET_NAME'], $collation];
        }, $columns);
        if ($columns !== $expected['columns']) {
            return false;
        }

        $indexes = self::rows($connection, "SELECT INDEX_NAME,NON_UNIQUE,INDEX_TYPE,GROUP_CONCAT(CONCAT(COLUMN_NAME,':',COALESCE(SUB_PART,'FULL'),':',COALESCE(COLLATION,'NULL'),':',COALESCE(IGNORED,'NO')) ORDER BY SEQ_IN_INDEX) COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $connection->real_escape_string($table) . "' GROUP BY INDEX_NAME,NON_UNIQUE,INDEX_TYPE");
        $indexes = array_map(static fn (array $index): string => implode('|', [$index['INDEX_NAME'], $index['NON_UNIQUE'], $index['INDEX_TYPE'], $index['COLUMNS']]), $indexes);
        sort($indexes, SORT_STRING);
        if ($indexes !== $expected['indexes']) {
            return false;
        }

        $checks = self::rows($connection, "SELECT tc.CONSTRAINT_NAME,cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME AND cc.TABLE_NAME=tc.TABLE_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME='" . $connection->real_escape_string($table) . "' AND tc.CONSTRAINT_TYPE='CHECK'");
        $checks = array_map(static function (array $check) use ($expected): string {
            $clause = self::normalizeCheckClause((string) $check['CHECK_CLAUSE']);
            return $expected['namedChecks'] ? $check['CONSTRAINT_NAME'] . '|' . $clause : $clause;
        }, $checks);
        sort($checks, SORT_STRING);
        if ($checks !== $expected['checks']) {
            return false;
        }

        $foreignKeys = self::rows($connection, "SELECT k.CONSTRAINT_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_SCHEMA,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE,DATABASE() CURRENT_SCHEMA FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.TABLE_NAME=k.TABLE_NAME AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME='" . $connection->real_escape_string($table) . "' AND k.REFERENCED_TABLE_NAME IS NOT NULL");
        $foreignKeys = array_map(static fn (array $fk): string => implode('|', [$fk['CONSTRAINT_NAME'], $fk['COLUMN_NAME'], $fk['REFERENCED_TABLE_SCHEMA'] === $fk['CURRENT_SCHEMA'] ? 'DATABASE' : $fk['REFERENCED_TABLE_SCHEMA'], $fk['REFERENCED_TABLE_NAME'], $fk['REFERENCED_COLUMN_NAME'], $fk['UPDATE_RULE'], $fk['DELETE_RULE'], 'DATABASE']), $foreignKeys);
        sort($foreignKeys, SORT_STRING);

        return $foreignKeys === $expected['foreignKeys'];
    }

    private static function normalizeCheckClause(string $clause): string
    {
        $normalized = '';
        $inLiteral = false;
        $length = strlen($clause);
        for ($offset = 0; $offset < $length; $offset++) {
            $character = $clause[$offset];
            if ($inLiteral) {
                $normalized .= $character;
                if ($character === '\\' && $offset + 1 < $length) {
                    $normalized .= $clause[++$offset];
                } elseif ($character === "'") {
                    if ($offset + 1 < $length && $clause[$offset + 1] === "'") {
                        $normalized .= $clause[++$offset];
                    } else {
                        $inLiteral = false;
                    }
                }
                continue;
            }

            if ($character === "'") {
                $inLiteral = true;
                $normalized .= $character;
            } elseif ($character !== '`' && !ctype_space($character)) {
                $normalized .= strtolower($character);
            }
        }

        while (self::hasRedundantWholeExpressionWrapper($normalized)) {
            $normalized = substr($normalized, 1, -1);
        }

        return $normalized;
    }

    private static function hasRedundantWholeExpressionWrapper(string $clause): bool
    {
        $length = strlen($clause);
        if ($length < 2 || $clause[0] !== '(' || $clause[$length - 1] !== ')') {
            return false;
        }

        $depth = 0;
        $inLiteral = false;
        for ($offset = 0; $offset < $length; $offset++) {
            $character = $clause[$offset];
            if ($inLiteral) {
                if ($character === '\\' && $offset + 1 < $length) {
                    $offset++;
                } elseif ($character === "'") {
                    if ($offset + 1 < $length && $clause[$offset + 1] === "'") {
                        $offset++;
                    } else {
                        $inLiteral = false;
                    }
                }
                continue;
            }
            if ($character === "'") {
                $inLiteral = true;
            } elseif ($character === '(') {
                $depth++;
            } elseif ($character === ')' && --$depth === 0 && $offset !== $length - 1) {
                return false;
            }
        }

        return $depth === 0 && !$inLiteral;
    }

    private static function v2Definition(): array
    {
        return self::definition(
            'installer_tab_id:bigint unsigned:NO::NULL;fio:varchar(300):NO::NULL;position:varchar(300):NO::NULL;employment_status:varchar(40):NO::NULL;employed_from:date:YES::NULL;employed_to:date:YES::NULL;workforce_source:varchar(80):NO::NULL;workforce_source_updated_at:varchar(40):NO::NULL',
            ['PRIMARY|0|BTREE|installer_tab_id:FULL:A:NO', 'employment_status|1|BTREE|employment_status:FULL:A:NO,employed_to:FULL:A:NO'],
            ["employment_statusin('employed','dismissed')"],
            [],
            false,
        );
    }

    private static function v5Definitions(string $prefix): array
    {
        $runs = $prefix . 'fm2_workforce_sync_runs';
        $catalogEmployment = self::symbol($prefix, 'ck_fm2_workforce_employment_status', 'ck_', 'wf_cat_emp');
        $catalogDismissal = self::symbol($prefix, 'ck_fm2_workforce_dismissal_quality', 'ck_', 'wf_cat_dq');
        $catalogReconciliation = self::symbol($prefix, 'ck_fm2_workforce_reconciliation_state', 'ck_', 'wf_cat_rec');
        $runsStatus = self::symbol($prefix, 'ck_fm2_workforce_sync_run_status', 'ck_', 'wf_run_status');
        $observationStatus = self::symbol($prefix, 'ck_fm2_workforce_observation_status', 'ck_', 'wf_obs_status');
        $observationReconciliation = self::symbol($prefix, 'ck_fm2_workforce_observation_reconciliation', 'ck_', 'wf_obs_rec');
        $observationDismissal = self::symbol($prefix, 'ck_fm2_workforce_observation_dismissal_quality', 'ck_', 'wf_obs_dq');
        $observationRun = self::symbol($prefix, 'fk_fm2_workforce_observation_run', 'fk_', 'wf_obs_run');
        $metadataSingleton = self::symbol($prefix, 'ck_fm2_workforce_sync_metadata_singleton', 'ck_', 'wf_meta_one');
        $metadataRun = self::symbol($prefix, 'fk_fm2_workforce_metadata_run', 'fk_', 'wf_meta_run');
        return [
            'fm2_workforce_catalog' => self::definition('installer_tab_id:bigint unsigned:NO::NULL;fio:varchar(300):NO::NULL;position:varchar(300):NO::NULL;employment_status:varchar(40):NO::NULL;employed_from:date:YES::NULL;employed_to:date:YES::NULL;workforce_source:varchar(80):NO::NULL;workforce_source_updated_at:varchar(40):NO::NULL;delivery_system:varchar(40):YES::NULL;delivery_person_id:bigint unsigned:YES::NULL;dismissal_effective_at:date:YES::NULL;first_observed_dismissed_at:varchar(40):YES::NULL;dismissal_time_quality:varchar(40):YES::NULL;reconciliation_state:varchar(40):YES::NULL;authority_system:varchar(40):YES::NULL;last_successful_sync_run_id:char(36):YES::NULL;last_successful_sync_at:varchar(40):YES::NULL', ['PRIMARY|0|BTREE|installer_tab_id:FULL:A:NO', 'ix_fm2_workforce_status_reconciliation_sync|1|BTREE|employment_status:FULL:A:NO,reconciliation_state:FULL:A:NO,last_successful_sync_at:FULL:A:NO', 'uq_fm2_workforce_delivery_identity|0|BTREE|delivery_system:FULL:A:NO,delivery_person_id:FULL:A:NO'], ["{$catalogDismissal}|dismissal_time_qualityisnullordismissal_time_qualityin('observed_only','effective_from_source')", "{$catalogEmployment}|employment_statusin('employed','dismissed')", "{$catalogReconciliation}|reconciliation_stateisnullorreconciliation_statein('delivered','missing_from_delivery')"], []),
            'fm2_workforce_sync_runs' => self::definition('run_id:char(36):NO::NULL;status:varchar(20):NO::NULL;started_at:varchar(40):NO::NULL;observed_at:varchar(40):YES::NULL;completed_at:varchar(40):YES::NULL;failure_code:varchar(80):YES::NULL;page_count:int unsigned:YES::NULL;delivered_count:int unsigned:YES::NULL;material_change_count:int unsigned:YES::NULL;missing_count:int unsigned:YES::NULL;normalized_checksum:char(64):YES::NULL', ['PRIMARY|0|BTREE|run_id:FULL:A:NO'], ["{$runsStatus}|statusin('started','completed','failed')"], []),
            'fm2_workforce_observations' => self::definition('id:bigint unsigned:NO:auto_increment:NULL;sync_run_id:char(36):NO::NULL;delivery_person_id:bigint unsigned:NO::NULL;employee_number:bigint unsigned:NO::NULL;full_name:varchar(300):NO::NULL;position:varchar(300):NO::NULL;employment_status:varchar(40):NO::NULL;employed_from:date:YES::NULL;dismissal_effective_at:date:YES::NULL;authority_system:varchar(40):NO::NULL;delivery_system:varchar(40):NO::NULL;source_modified_at:varchar(40):YES::NULL;reconciliation_state:varchar(40):NO::NULL;observed_at:varchar(40):NO::NULL;dismissal_time_quality:varchar(40):NO::NULL', ['PRIMARY|0|BTREE|id:FULL:A:NO', 'ix_fm2_workforce_observation_employee_time|1|BTREE|employee_number:FULL:A:NO,observed_at:FULL:A:NO', 'ix_fm2_workforce_observation_person_time|1|BTREE|delivery_system:FULL:A:NO,delivery_person_id:FULL:A:NO,observed_at:FULL:A:NO', 'uq_fm2_workforce_observation_run_person|0|BTREE|sync_run_id:FULL:A:NO,delivery_system:FULL:A:NO,delivery_person_id:FULL:A:NO'], ["{$observationDismissal}|dismissal_time_qualityin('observed_only','effective_from_source')", "{$observationReconciliation}|reconciliation_statein('delivered','missing_from_delivery')", "{$observationStatus}|employment_statusin('employed','dismissed')"], ["{$observationRun}|sync_run_id|DATABASE|{$runs}|run_id|RESTRICT|RESTRICT|DATABASE"]),
            'fm2_workforce_sync_metadata' => self::definition('singleton_id:tinyint unsigned:NO::NULL;last_successful_run_id:char(36):YES::NULL;last_successful_at:varchar(40):YES::NULL', ['PRIMARY|0|BTREE|singleton_id:FULL:A:NO', "{$metadataRun}|1|BTREE|last_successful_run_id:FULL:A:NO"], ["{$metadataSingleton}|singleton_id=1"], ["{$metadataRun}|last_successful_run_id|DATABASE|{$runs}|run_id|RESTRICT|RESTRICT|DATABASE"]),
        ];
    }

    private static function symbol(string $prefix, string $empty, string $category, string $token): string
    {
        return $prefix === '' ? $empty : $category . $prefix . $token;
    }

    private static function definition(string $columns, array $indexes, array $checks, array $foreignKeys, bool $namedChecks = true): array
    {
        $databaseMarker = 'DATABASE';
        $parsedColumns = array_map(static function (string $column) use ($databaseMarker): array {
            [$name, $type, $nullable, $extra, $default] = explode(':', $column, 5);
            $character = str_contains($type, 'char');
            return [$name, $type, $nullable, $extra, $default, $character ? 'utf8mb4' : null, $character ? $databaseMarker : null];
        }, explode(';', $columns));
        sort($indexes, SORT_STRING);
        sort($checks, SORT_STRING);
        sort($foreignKeys, SORT_STRING);

        return compact('parsedColumns', 'indexes', 'checks', 'foreignKeys', 'namedChecks') + ['columns' => $parsedColumns];
    }

    private static function createRuns(\mysqli $connection, string $table, string $prefix, string $collation): void
    {
        $statusCheck = self::symbol($prefix, 'ck_fm2_workforce_sync_run_status', 'ck_', 'wf_run_status');
        $connection->query("CREATE TABLE `{$table}` (run_id CHAR(36) NOT NULL,status VARCHAR(20) NOT NULL,started_at VARCHAR(40) NOT NULL,observed_at VARCHAR(40) NULL,completed_at VARCHAR(40) NULL,failure_code VARCHAR(80) NULL,page_count INT UNSIGNED NULL,delivered_count INT UNSIGNED NULL,material_change_count INT UNSIGNED NULL,missing_count INT UNSIGNED NULL,normalized_checksum CHAR(64) NULL,PRIMARY KEY (run_id),CONSTRAINT `{$statusCheck}` CHECK (status IN ('started','completed','failed'))) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE `{$collation}`");
    }

    private static function createObservations(\mysqli $connection, string $table, string $runs, string $prefix, string $collation): void
    {
        $statusCheck = self::symbol($prefix, 'ck_fm2_workforce_observation_status', 'ck_', 'wf_obs_status');
        $reconciliationCheck = self::symbol($prefix, 'ck_fm2_workforce_observation_reconciliation', 'ck_', 'wf_obs_rec');
        $dismissalCheck = self::symbol($prefix, 'ck_fm2_workforce_observation_dismissal_quality', 'ck_', 'wf_obs_dq');
        $runForeignKey = self::symbol($prefix, 'fk_fm2_workforce_observation_run', 'fk_', 'wf_obs_run');
        $connection->query("CREATE TABLE `{$table}` (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,sync_run_id CHAR(36) NOT NULL,delivery_person_id BIGINT UNSIGNED NOT NULL,employee_number BIGINT UNSIGNED NOT NULL,full_name VARCHAR(300) NOT NULL,position VARCHAR(300) NOT NULL,employment_status VARCHAR(40) NOT NULL,employed_from DATE NULL,dismissal_effective_at DATE NULL,authority_system VARCHAR(40) NOT NULL,delivery_system VARCHAR(40) NOT NULL,source_modified_at VARCHAR(40) NULL,reconciliation_state VARCHAR(40) NOT NULL,observed_at VARCHAR(40) NOT NULL,dismissal_time_quality VARCHAR(40) NOT NULL,PRIMARY KEY (id),UNIQUE KEY uq_fm2_workforce_observation_run_person (sync_run_id,delivery_system,delivery_person_id),KEY ix_fm2_workforce_observation_person_time (delivery_system,delivery_person_id,observed_at),KEY ix_fm2_workforce_observation_employee_time (employee_number,observed_at),CONSTRAINT `{$statusCheck}` CHECK (employment_status IN ('employed','dismissed')),CONSTRAINT `{$reconciliationCheck}` CHECK (reconciliation_state IN ('delivered','missing_from_delivery')),CONSTRAINT `{$dismissalCheck}` CHECK (dismissal_time_quality IN ('observed_only','effective_from_source')),CONSTRAINT `{$runForeignKey}` FOREIGN KEY (sync_run_id) REFERENCES `{$runs}` (run_id) ON UPDATE RESTRICT ON DELETE RESTRICT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE `{$collation}`");
    }

    private static function createMetadata(\mysqli $connection, string $table, string $runs, string $prefix, string $collation): void
    {
        $singletonCheck = self::symbol($prefix, 'ck_fm2_workforce_sync_metadata_singleton', 'ck_', 'wf_meta_one');
        $runForeignKey = self::symbol($prefix, 'fk_fm2_workforce_metadata_run', 'fk_', 'wf_meta_run');
        $connection->query("CREATE TABLE `{$table}` (singleton_id TINYINT UNSIGNED NOT NULL,last_successful_run_id CHAR(36) NULL,last_successful_at VARCHAR(40) NULL,PRIMARY KEY (singleton_id),KEY `{$runForeignKey}` (last_successful_run_id),CONSTRAINT `{$singletonCheck}` CHECK (singleton_id = 1),CONSTRAINT `{$runForeignKey}` FOREIGN KEY (last_successful_run_id) REFERENCES `{$runs}` (run_id) ON UPDATE RESTRICT ON DELETE RESTRICT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE `{$collation}`");
    }

    private static function upgradeCatalog(\mysqli $connection, string $table, string $prefix): void
    {
        $escapedTable = $connection->real_escape_string($table);
        $sourceCheck = self::rows($connection, "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$escapedTable}' AND CONSTRAINT_TYPE='CHECK'")[0]['CONSTRAINT_NAME'];
        $sourceCheck = str_replace('`', '``', (string) $sourceCheck);
        $employmentCheck = self::symbol($prefix, 'ck_fm2_workforce_employment_status', 'ck_', 'wf_cat_emp');
        $dismissalCheck = self::symbol($prefix, 'ck_fm2_workforce_dismissal_quality', 'ck_', 'wf_cat_dq');
        $reconciliationCheck = self::symbol($prefix, 'ck_fm2_workforce_reconciliation_state', 'ck_', 'wf_cat_rec');
        $connection->query("ALTER TABLE `{$table}` DROP INDEX employment_status,DROP CONSTRAINT `{$sourceCheck}`,ADD COLUMN delivery_system VARCHAR(40) NULL,ADD COLUMN delivery_person_id BIGINT UNSIGNED NULL,ADD COLUMN dismissal_effective_at DATE NULL,ADD COLUMN first_observed_dismissed_at VARCHAR(40) NULL,ADD COLUMN dismissal_time_quality VARCHAR(40) NULL,ADD COLUMN reconciliation_state VARCHAR(40) NULL,ADD COLUMN authority_system VARCHAR(40) NULL,ADD COLUMN last_successful_sync_run_id CHAR(36) NULL,ADD COLUMN last_successful_sync_at VARCHAR(40) NULL,ADD UNIQUE KEY uq_fm2_workforce_delivery_identity (delivery_system,delivery_person_id),ADD KEY ix_fm2_workforce_status_reconciliation_sync (employment_status,reconciliation_state,last_successful_sync_at),ADD CONSTRAINT `{$employmentCheck}` CHECK (employment_status IN ('employed','dismissed')),ADD CONSTRAINT `{$dismissalCheck}` CHECK (dismissal_time_quality IS NULL OR dismissal_time_quality IN ('observed_only','effective_from_source')),ADD CONSTRAINT `{$reconciliationCheck}` CHECK (reconciliation_state IS NULL OR reconciliation_state IN ('delivered','missing_from_delivery'))");
    }

    private static function rows(\mysqli $connection, string $sql): array
    {
        return $connection->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
}
