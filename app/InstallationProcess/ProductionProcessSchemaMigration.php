<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class ProductionProcessSchemaMigration
{
    private const TABLES = [
        'fm2_installation_cases',
        'fm2_assignment_orders',
        'fm2_order_installers',
        'fm2_order_artifacts',
        'fm2_process_tasks',
        'fm2_process_events',
    ];

    /** @return array{applied: bool, schemaVersion: int, tablesCreated: list<string>}|array{applied: false, schemaVersion: int, reason: string, conflictingTables: list<string>} */
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        MariaDbSchemaInspector::validateTablePrefix($tablePrefix);

        $existing = [];
        $conflicts = [];
        foreach (self::TABLES as $table) {
            $name = $tablePrefix . $table;
            if (!MariaDbSchemaInspector::tableExists($connection, $name)) {
                continue;
            }
            $existing[$table] = true;
            if (!self::isCompatible($connection, $tablePrefix, $table)) {
                $conflicts[] = $name;
            }
        }

        if ($conflicts !== []) {
            return [
                'applied' => false,
                'schemaVersion' => 1,
                'reason' => 'SCHEMA_MIGRATION_CONFLICT',
                'conflictingTables' => $conflicts,
            ];
        }

        $created = [];
        foreach (self::definitions($tablePrefix) as $table => $sql) {
            if (isset($existing[$table])) {
                continue;
            }
            $connection->query($sql);
            $created[] = $tablePrefix . $table;
        }

        return [
            'applied' => $created !== [],
            'schemaVersion' => 1,
            'tablesCreated' => $created,
        ];
    }

    public static function isInstallationCasesCompatible(\mysqli $connection, string $tablePrefix = ''): bool
    {
        MariaDbSchemaInspector::validateTablePrefix($tablePrefix);

        return self::isCompatible($connection, $tablePrefix, 'fm2_installation_cases');
    }

    private static function isCompatible(\mysqli $connection, string $prefix, string $table): bool
    {
        $name = $prefix . $table;
        $properties = MariaDbSchemaInspector::tableProperties($connection, $name);
        if ($properties === null || $properties['ENGINE'] !== 'InnoDB' || !str_starts_with((string) $properties['TABLE_COLLATION'], 'utf8mb4_')) {
            return false;
        }

        $columnRows = MariaDbSchemaInspector::columns($connection, $name);
        foreach ($columnRows as $row) {
            $characterContract = [$row['CHARACTER_SET_NAME'], $row['COLLATION_NAME']];
            $isCharacterColumn = preg_match('/^(?:varchar|char|longtext)/', strtolower((string) $row['COLUMN_TYPE'])) === 1;
            if (!$isCharacterColumn && $characterContract !== [null, null]) {
                return false;
            }
            if ($isCharacterColumn
                && ($row['CHARACTER_SET_NAME'] !== 'utf8mb4'
                    || !str_starts_with((string) $row['COLLATION_NAME'], 'utf8mb4_'))) {
                return false;
            }
        }
        $columns = array_map(
            static fn (array $row): array => [$row['COLUMN_NAME'], strtolower((string) $row['COLUMN_TYPE']), $row['IS_NULLABLE'], $row['EXTRA']],
            $columnRows,
        );
        if ($columns !== self::columns()[$table]) {
            return false;
        }

        if (self::keyFingerprint($connection, $prefix, $table) !== self::keys()[$table]) {
            return false;
        }

        $expectedChecks = $table === 'fm2_process_events' ? ['json_valid(payload_json)'] : [];
        if (self::checkFingerprint($connection, $name) !== $expectedChecks) {
            return false;
        }

        return true;
    }

    /** @return array{primary: list<string>, unique: list<list<string>>, foreign: list<list<string>>, secondary: list<list<string>>} */
    private static function keyFingerprint(\mysqli $connection, string $prefix, string $table): array
    {
        $name = $prefix . $table;
        $primary = [];
        $unique = [];
        $secondary = [];
        foreach (MariaDbSchemaInspector::indexes($connection, $name) as $row) {
            $columns = explode(',', (string) $row['COLUMNS']);
            if ($row['INDEX_NAME'] === 'PRIMARY') {
                $primary = $columns;
            } elseif ((int) $row['NON_UNIQUE'] === 0) {
                $unique[] = $columns;
            } else {
                $secondary[] = $columns;
            }
        }
        usort($unique, static fn (array $left, array $right): int => implode(',', $left) <=> implode(',', $right));
        usort($secondary, static fn (array $left, array $right): int => implode(',', $left) <=> implode(',', $right));

        $foreign = array_map(
            static fn (array $row): array => [
                $row['COLUMN_NAME'],
                $row['REFERENCED_TABLE_SCHEMA'] === $row['CURRENT_SCHEMA']
                    ? substr((string) $row['REFERENCED_TABLE_NAME'], strlen($prefix))
                    : $row['REFERENCED_TABLE_SCHEMA'] . '.' . $row['REFERENCED_TABLE_NAME'],
                $row['REFERENCED_COLUMN_NAME'],
                $row['DELETE_RULE'],
            ],
            MariaDbSchemaInspector::foreignKeys($connection, $name),
        );

        return ['primary' => $primary, 'unique' => $unique, 'foreign' => $foreign, 'secondary' => $secondary];
    }

    /** @return list<string> */
    private static function checkFingerprint(\mysqli $connection, string $table): array
    {
        return MariaDbSchemaInspector::checks($connection, $table);
    }

    /** @return array<string, list<array{string, string, string, string}>> */
    private static function columns(): array
    {
        return [
            'fm2_installation_cases' => [['id','bigint(20) unsigned','NO','auto_increment'],['legacy_installation_object_id','bigint(20) unsigned','NO',''],['process_state','varchar(80)','NO',''],['actual_start_date','date','YES',''],['opened_at','varchar(40)','YES',''],['opened_by_user_id','bigint(20) unsigned','YES',''],['created_at','varchar(40)','NO',''],['updated_at','varchar(40)','NO',''],['lock_version','int(10) unsigned','NO','']],
            'fm2_assignment_orders' => [['id','bigint(20) unsigned','NO','auto_increment'],['installation_case_id','bigint(20) unsigned','NO',''],['version_no','smallint(5) unsigned','NO',''],['kind','varchar(40)','NO',''],['status','varchar(40)','NO',''],['order_date','date','NO',''],['registration_number','varchar(120)','YES',''],['registered_at','varchar(40)','YES',''],['registration_actor_type','varchar(40)','YES',''],['registration_actor_id','varchar(120)','YES',''],['registration_source','varchar(40)','YES',''],['external_registration_id','varchar(120)','YES',''],['control_engineer_user_id','bigint(20) unsigned','NO',''],['control_engineer_fio_snapshot','varchar(300)','NO',''],['control_engineer_position_snapshot','varchar(300)','NO',''],['organization_form','varchar(40)','NO',''],['previous_assignment_order_id','bigint(20) unsigned','YES',''],['object_address_snapshot','varchar(500)','NO',''],['entrance_snapshot','varchar(80)','NO',''],['object_registration_number_snapshot','varchar(120)','NO',''],['planned_start_date_snapshot','date','NO',''],['planned_finish_date_snapshot','date','NO',''],['pto_act_date_snapshot','date','YES',''],['prepared_at','varchar(40)','NO',''],['prepared_by_user_id','bigint(20) unsigned','NO','']],
            'fm2_order_installers' => [['assignment_order_id','bigint(20) unsigned','NO',''],['installer_tab_id','bigint(20) unsigned','NO',''],['fio_snapshot','varchar(300)','NO',''],['position_snapshot','varchar(300)','NO',''],['employment_status_snapshot','varchar(40)','NO',''],['employed_from_snapshot','date','NO',''],['employed_to_snapshot','date','YES',''],['workforce_source_snapshot','varchar(80)','NO',''],['workforce_source_updated_at_snapshot','varchar(40)','NO',''],['valid_from','date','NO',''],['valid_to','date','YES',''],['change_action','varchar(40)','NO','']],
            'fm2_order_artifacts' => [['assignment_order_id','bigint(20) unsigned','NO',''],['artifact_type','varchar(40)','NO',''],['filename','varchar(500)','NO',''],['media_type','varchar(120)','NO',''],['byte_size','bigint(20) unsigned','NO',''],['sha256','char(64)','NO','']],
            'fm2_process_tasks' => [['id','bigint(20) unsigned','NO','auto_increment'],['installation_case_id','bigint(20) unsigned','NO',''],['task_type','varchar(80)','NO',''],['assignee_user_id','bigint(20) unsigned','YES',''],['assignee_role','varchar(80)','YES',''],['due_date','date','YES',''],['status','varchar(40)','NO',''],['completed_at','varchar(40)','YES',''],['completed_by_user_id','bigint(20) unsigned','YES',''],['created_at','varchar(40)','NO','']],
            'fm2_process_events' => [['id','bigint(20) unsigned','NO','auto_increment'],['installation_case_id','bigint(20) unsigned','NO',''],['event_type','varchar(80)','NO',''],['occurred_at','varchar(40)','NO',''],['actor_user_id','bigint(20) unsigned','NO',''],['payload_json','longtext','NO','']],
        ];
    }

    /** @return array<string, array{primary: list<string>, unique: list<list<string>>, foreign: list<list<string>>, secondary: list<list<string>>}> */
    private static function keys(): array
    {
        return [
            'fm2_installation_cases'=>['primary'=>['id:FULL:A:NO'],'unique'=>[['legacy_installation_object_id:FULL:A:NO']],'foreign'=>[],'secondary'=>[]],
            'fm2_assignment_orders'=>['primary'=>['id:FULL:A:NO'],'unique'=>[['installation_case_id:FULL:A:NO','version_no:FULL:A:NO']],'foreign'=>[['installation_case_id','fm2_installation_cases','id','RESTRICT'],['previous_assignment_order_id','fm2_assignment_orders','id','RESTRICT']],'secondary'=>[['installation_case_id:FULL:A:NO','status:FULL:A:NO'],['previous_assignment_order_id:FULL:A:NO']]],
            'fm2_order_installers'=>['primary'=>['assignment_order_id:FULL:A:NO','installer_tab_id:FULL:A:NO'],'unique'=>[],'foreign'=>[['assignment_order_id','fm2_assignment_orders','id','RESTRICT']],'secondary'=>[]],
            'fm2_order_artifacts'=>['primary'=>['assignment_order_id:FULL:A:NO','artifact_type:FULL:A:NO'],'unique'=>[],'foreign'=>[['assignment_order_id','fm2_assignment_orders','id','RESTRICT']],'secondary'=>[]],
            'fm2_process_tasks'=>['primary'=>['id:FULL:A:NO'],'unique'=>[],'foreign'=>[['installation_case_id','fm2_installation_cases','id','RESTRICT']],'secondary'=>[['installation_case_id:FULL:A:NO'],['status:FULL:A:NO','assignee_role:FULL:A:NO','due_date:FULL:A:NO']]],
            'fm2_process_events'=>['primary'=>['id:FULL:A:NO'],'unique'=>[],'foreign'=>[['installation_case_id','fm2_installation_cases','id','RESTRICT']],'secondary'=>[['installation_case_id:FULL:A:NO','occurred_at:FULL:A:NO']]],
        ];
    }

    /** @return array<string, string> */
    private static function definitions(string $prefix): array
    {
        $t = static fn (string $name): string => '`' . $prefix . $name . '`';
        return [
            'fm2_installation_cases' => "CREATE TABLE {$t('fm2_installation_cases')} (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,legacy_installation_object_id BIGINT UNSIGNED NOT NULL,process_state VARCHAR(80) NOT NULL,actual_start_date DATE NULL,opened_at VARCHAR(40) NULL,opened_by_user_id BIGINT UNSIGNED NULL,created_at VARCHAR(40) NOT NULL,updated_at VARCHAR(40) NOT NULL,lock_version INT UNSIGNED NOT NULL,PRIMARY KEY(id),UNIQUE KEY(legacy_installation_object_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'fm2_assignment_orders' => "CREATE TABLE {$t('fm2_assignment_orders')} (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,installation_case_id BIGINT UNSIGNED NOT NULL,version_no SMALLINT UNSIGNED NOT NULL,kind VARCHAR(40) NOT NULL,status VARCHAR(40) NOT NULL,order_date DATE NOT NULL,registration_number VARCHAR(120) NULL,registered_at VARCHAR(40) NULL,registration_actor_type VARCHAR(40) NULL,registration_actor_id VARCHAR(120) NULL,registration_source VARCHAR(40) NULL,external_registration_id VARCHAR(120) NULL,control_engineer_user_id BIGINT UNSIGNED NOT NULL,control_engineer_fio_snapshot VARCHAR(300) NOT NULL,control_engineer_position_snapshot VARCHAR(300) NOT NULL,organization_form VARCHAR(40) NOT NULL,previous_assignment_order_id BIGINT UNSIGNED NULL,object_address_snapshot VARCHAR(500) NOT NULL,entrance_snapshot VARCHAR(80) NOT NULL,object_registration_number_snapshot VARCHAR(120) NOT NULL,planned_start_date_snapshot DATE NOT NULL,planned_finish_date_snapshot DATE NOT NULL,pto_act_date_snapshot DATE NULL,prepared_at VARCHAR(40) NOT NULL,prepared_by_user_id BIGINT UNSIGNED NOT NULL,PRIMARY KEY(id),UNIQUE KEY(installation_case_id,version_no),KEY(installation_case_id,status),FOREIGN KEY(installation_case_id) REFERENCES {$t('fm2_installation_cases')}(id),FOREIGN KEY(previous_assignment_order_id) REFERENCES {$t('fm2_assignment_orders')}(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'fm2_order_installers' => "CREATE TABLE {$t('fm2_order_installers')} (assignment_order_id BIGINT UNSIGNED NOT NULL,installer_tab_id BIGINT UNSIGNED NOT NULL,fio_snapshot VARCHAR(300) NOT NULL,position_snapshot VARCHAR(300) NOT NULL,employment_status_snapshot VARCHAR(40) NOT NULL,employed_from_snapshot DATE NOT NULL,employed_to_snapshot DATE NULL,workforce_source_snapshot VARCHAR(80) NOT NULL,workforce_source_updated_at_snapshot VARCHAR(40) NOT NULL,valid_from DATE NOT NULL,valid_to DATE NULL,change_action VARCHAR(40) NOT NULL,PRIMARY KEY(assignment_order_id,installer_tab_id),FOREIGN KEY(assignment_order_id) REFERENCES {$t('fm2_assignment_orders')}(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'fm2_order_artifacts' => "CREATE TABLE {$t('fm2_order_artifacts')} (assignment_order_id BIGINT UNSIGNED NOT NULL,artifact_type VARCHAR(40) NOT NULL,filename VARCHAR(500) NOT NULL,media_type VARCHAR(120) NOT NULL,byte_size BIGINT UNSIGNED NOT NULL,sha256 CHAR(64) NOT NULL,PRIMARY KEY(assignment_order_id,artifact_type),FOREIGN KEY(assignment_order_id) REFERENCES {$t('fm2_assignment_orders')}(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'fm2_process_tasks' => "CREATE TABLE {$t('fm2_process_tasks')} (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,installation_case_id BIGINT UNSIGNED NOT NULL,task_type VARCHAR(80) NOT NULL,assignee_user_id BIGINT UNSIGNED NULL,assignee_role VARCHAR(80) NULL,due_date DATE NULL,status VARCHAR(40) NOT NULL,completed_at VARCHAR(40) NULL,completed_by_user_id BIGINT UNSIGNED NULL,created_at VARCHAR(40) NOT NULL,PRIMARY KEY(id),KEY(status,assignee_role,due_date),FOREIGN KEY(installation_case_id) REFERENCES {$t('fm2_installation_cases')}(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'fm2_process_events' => "CREATE TABLE {$t('fm2_process_events')} (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,installation_case_id BIGINT UNSIGNED NOT NULL,event_type VARCHAR(80) NOT NULL,occurred_at VARCHAR(40) NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,payload_json JSON NOT NULL,PRIMARY KEY(id),KEY(installation_case_id,occurred_at),FOREIGN KEY(installation_case_id) REFERENCES {$t('fm2_installation_cases')}(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];
    }
}
