<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class MariaDbProductionProcessSchemaReadiness
{
    /** @return list<string> */
    public static function tables(): array
    {
        return array_keys(self::columns());
    }

    public static function isCompleteCompatible(\mysqli $connection, string $prefix = ''): bool
    {
        MariaDbSchemaInspector::validateTablePrefix($prefix);
        foreach (self::tables() as $table) {
            if (!self::isTableCompatible($connection, $prefix, $table)) return false;
        }
        return true;
    }

    public static function isTableCompatible(\mysqli $connection, string $prefix, string $table): bool
    {
        $catalog = self::columns();
        if (!isset($catalog[$table])) return false;
        $name = $prefix . $table;
        $properties = MariaDbSchemaInspector::tableProperties($connection, $name);
        if ($properties === null || $properties['ENGINE'] !== 'InnoDB'
            || !str_starts_with((string) $properties['TABLE_COLLATION'], 'utf8mb4_')) return false;
        $rows = MariaDbSchemaInspector::columns($connection, $name);
        foreach ($rows as $row) {
            $character = [$row['CHARACTER_SET_NAME'], $row['COLLATION_NAME']];
            $isCharacter = preg_match('/^(?:varchar|char|longtext)/', strtolower((string) $row['COLUMN_TYPE'])) === 1;
            if ((!$isCharacter && $character !== [null, null])
                || ($isCharacter && ($row['CHARACTER_SET_NAME'] !== 'utf8mb4'
                    || !str_starts_with((string) $row['COLLATION_NAME'], 'utf8mb4_')))) return false;
        }
        $columns = array_map(static fn (array $row): array => [
            $row['COLUMN_NAME'], strtolower((string) $row['COLUMN_TYPE']), $row['IS_NULLABLE'], $row['EXTRA'],
        ], $rows);
        if ($columns !== $catalog[$table] || self::keyFingerprint($connection, $prefix, $table) !== self::keys()[$table]) return false;
        return MariaDbSchemaInspector::checks($connection, $name) === ($table === 'fm2_process_events' ? ['json_valid(payload_json)'] : []);
    }

    private static function keyFingerprint(\mysqli $connection, string $prefix, string $table): array
    {
        $name = $prefix . $table;
        $primary = [];$unique = [];$secondary = [];
        foreach (MariaDbSchemaInspector::indexes($connection, $name) as $row) {
            $columns = explode(',', (string) $row['COLUMNS']);
            if ($row['INDEX_NAME'] === 'PRIMARY') $primary = $columns;
            elseif ((int) $row['NON_UNIQUE'] === 0) $unique[] = $columns;
            else $secondary[] = $columns;
        }
        usort($unique, static fn (array $a, array $b): int => implode(',', $a) <=> implode(',', $b));
        usort($secondary, static fn (array $a, array $b): int => implode(',', $a) <=> implode(',', $b));
        $foreign = array_map(static fn (array $row): array => [
            $row['COLUMN_NAME'],
            $row['REFERENCED_TABLE_SCHEMA'] === $row['CURRENT_SCHEMA']
                ? substr((string) $row['REFERENCED_TABLE_NAME'], strlen($prefix))
                : $row['REFERENCED_TABLE_SCHEMA'] . '.' . $row['REFERENCED_TABLE_NAME'],
            $row['REFERENCED_COLUMN_NAME'], $row['DELETE_RULE'],
        ], MariaDbSchemaInspector::foreignKeys($connection, $name));
        return ['primary'=>$primary,'unique'=>$unique,'foreign'=>$foreign,'secondary'=>$secondary];
    }

    private static function columns(): array
    {
        return [
        'fm2_installation_cases'=>[['id','bigint(20) unsigned','NO','auto_increment'],['legacy_installation_object_id','bigint(20) unsigned','NO',''],['process_state','varchar(80)','NO',''],['actual_start_date','date','YES',''],['opened_at','varchar(40)','YES',''],['opened_by_user_id','bigint(20) unsigned','YES',''],['created_at','varchar(40)','NO',''],['updated_at','varchar(40)','NO',''],['lock_version','int(10) unsigned','NO','']],
        'fm2_assignment_orders'=>[['id','bigint(20) unsigned','NO','auto_increment'],['installation_case_id','bigint(20) unsigned','NO',''],['version_no','smallint(5) unsigned','NO',''],['kind','varchar(40)','NO',''],['status','varchar(40)','NO',''],['order_date','date','NO',''],['registration_number','varchar(120)','YES',''],['registered_at','varchar(40)','YES',''],['registration_actor_type','varchar(40)','YES',''],['registration_actor_id','varchar(120)','YES',''],['registration_source','varchar(40)','YES',''],['external_registration_id','varchar(120)','YES',''],['control_engineer_user_id','bigint(20) unsigned','NO',''],['control_engineer_fio_snapshot','varchar(300)','NO',''],['control_engineer_position_snapshot','varchar(300)','NO',''],['organization_form','varchar(40)','NO',''],['previous_assignment_order_id','bigint(20) unsigned','YES',''],['object_address_snapshot','varchar(500)','NO',''],['entrance_snapshot','varchar(80)','NO',''],['object_registration_number_snapshot','varchar(120)','NO',''],['planned_start_date_snapshot','date','NO',''],['planned_finish_date_snapshot','date','NO',''],['pto_act_date_snapshot','date','YES',''],['prepared_at','varchar(40)','NO',''],['prepared_by_user_id','bigint(20) unsigned','NO','']],
        'fm2_order_installers'=>[['assignment_order_id','bigint(20) unsigned','NO',''],['installer_tab_id','bigint(20) unsigned','NO',''],['fio_snapshot','varchar(300)','NO',''],['position_snapshot','varchar(300)','NO',''],['employment_status_snapshot','varchar(40)','NO',''],['employed_from_snapshot','date','NO',''],['employed_to_snapshot','date','YES',''],['workforce_source_snapshot','varchar(80)','NO',''],['workforce_source_updated_at_snapshot','varchar(40)','NO',''],['valid_from','date','NO',''],['valid_to','date','YES',''],['change_action','varchar(40)','NO','']],
        'fm2_order_artifacts'=>[['assignment_order_id','bigint(20) unsigned','NO',''],['artifact_type','varchar(40)','NO',''],['filename','varchar(500)','NO',''],['media_type','varchar(120)','NO',''],['byte_size','bigint(20) unsigned','NO',''],['sha256','char(64)','NO','']],
        'fm2_process_tasks'=>[['id','bigint(20) unsigned','NO','auto_increment'],['installation_case_id','bigint(20) unsigned','NO',''],['task_type','varchar(80)','NO',''],['assignee_user_id','bigint(20) unsigned','YES',''],['assignee_role','varchar(80)','YES',''],['due_date','date','YES',''],['status','varchar(40)','NO',''],['completed_at','varchar(40)','YES',''],['completed_by_user_id','bigint(20) unsigned','YES',''],['created_at','varchar(40)','NO','']],
        'fm2_process_events'=>[['id','bigint(20) unsigned','NO','auto_increment'],['installation_case_id','bigint(20) unsigned','NO',''],['event_type','varchar(80)','NO',''],['occurred_at','varchar(40)','NO',''],['actor_user_id','bigint(20) unsigned','NO',''],['payload_json','longtext','NO','']],
        ];
    }

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
}
