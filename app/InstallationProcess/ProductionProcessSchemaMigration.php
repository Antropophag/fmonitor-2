<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class ProductionProcessSchemaMigration
{
    public static function apply(\mysqli $connection, string $prefix = ''): array
    {
        MariaDbSchemaInspector::validateTablePrefix($prefix);
        $existing = [];
        $conflicts = [];
        foreach (MariaDbProductionProcessSchemaReadiness::tables() as $table) {
            $name = $prefix . $table;
            if (!MariaDbSchemaInspector::tableExists($connection, $name)) continue;
            $existing[$table] = true;
            if (!MariaDbProductionProcessSchemaReadiness::isTableCompatible($connection, $prefix, $table)) $conflicts[] = $name;
        }
        if ($conflicts !== []) {
            return ['applied'=>false,'schemaVersion'=>1,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>$conflicts];
        }
        $created = [];
        foreach (self::definitions($prefix) as $table => $sql) {
            if (isset($existing[$table])) continue;
            $connection->query($sql);
            $created[] = $prefix . $table;
        }
        return ['applied'=>$created !== [],'schemaVersion'=>1,'tablesCreated'=>$created];
    }

    public static function isInstallationCasesCompatible(\mysqli $connection, string $prefix = ''): bool
    {
        MariaDbSchemaInspector::validateTablePrefix($prefix);
        return MariaDbProductionProcessSchemaReadiness::isTableCompatible($connection, $prefix, 'fm2_installation_cases');
    }

    private static function definitions(string $prefix): array
    {
        $t = static fn (string $name): string => '`' . $prefix . $name . '`';
        return [
            'fm2_installation_cases'=>"CREATE TABLE {$t('fm2_installation_cases')} (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,legacy_installation_object_id BIGINT UNSIGNED NOT NULL,process_state VARCHAR(80) NOT NULL,actual_start_date DATE NULL,opened_at VARCHAR(40) NULL,opened_by_user_id BIGINT UNSIGNED NULL,created_at VARCHAR(40) NOT NULL,updated_at VARCHAR(40) NOT NULL,lock_version INT UNSIGNED NOT NULL,PRIMARY KEY(id),UNIQUE KEY(legacy_installation_object_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'fm2_assignment_orders'=>"CREATE TABLE {$t('fm2_assignment_orders')} (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,installation_case_id BIGINT UNSIGNED NOT NULL,version_no SMALLINT UNSIGNED NOT NULL,kind VARCHAR(40) NOT NULL,status VARCHAR(40) NOT NULL,order_date DATE NOT NULL,registration_number VARCHAR(120) NULL,registered_at VARCHAR(40) NULL,registration_actor_type VARCHAR(40) NULL,registration_actor_id VARCHAR(120) NULL,registration_source VARCHAR(40) NULL,external_registration_id VARCHAR(120) NULL,control_engineer_user_id BIGINT UNSIGNED NOT NULL,control_engineer_fio_snapshot VARCHAR(300) NOT NULL,control_engineer_position_snapshot VARCHAR(300) NOT NULL,organization_form VARCHAR(40) NOT NULL,previous_assignment_order_id BIGINT UNSIGNED NULL,object_address_snapshot VARCHAR(500) NOT NULL,entrance_snapshot VARCHAR(80) NOT NULL,object_registration_number_snapshot VARCHAR(120) NOT NULL,planned_start_date_snapshot DATE NOT NULL,planned_finish_date_snapshot DATE NOT NULL,pto_act_date_snapshot DATE NULL,prepared_at VARCHAR(40) NOT NULL,prepared_by_user_id BIGINT UNSIGNED NOT NULL,PRIMARY KEY(id),UNIQUE KEY(installation_case_id,version_no),KEY(installation_case_id,status),FOREIGN KEY(installation_case_id) REFERENCES {$t('fm2_installation_cases')}(id),FOREIGN KEY(previous_assignment_order_id) REFERENCES {$t('fm2_assignment_orders')}(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'fm2_order_installers'=>"CREATE TABLE {$t('fm2_order_installers')} (assignment_order_id BIGINT UNSIGNED NOT NULL,installer_tab_id BIGINT UNSIGNED NOT NULL,fio_snapshot VARCHAR(300) NOT NULL,position_snapshot VARCHAR(300) NOT NULL,employment_status_snapshot VARCHAR(40) NOT NULL,employed_from_snapshot DATE NOT NULL,employed_to_snapshot DATE NULL,workforce_source_snapshot VARCHAR(80) NOT NULL,workforce_source_updated_at_snapshot VARCHAR(40) NOT NULL,valid_from DATE NOT NULL,valid_to DATE NULL,change_action VARCHAR(40) NOT NULL,PRIMARY KEY(assignment_order_id,installer_tab_id),FOREIGN KEY(assignment_order_id) REFERENCES {$t('fm2_assignment_orders')}(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'fm2_order_artifacts'=>"CREATE TABLE {$t('fm2_order_artifacts')} (assignment_order_id BIGINT UNSIGNED NOT NULL,artifact_type VARCHAR(40) NOT NULL,filename VARCHAR(500) NOT NULL,media_type VARCHAR(120) NOT NULL,byte_size BIGINT UNSIGNED NOT NULL,sha256 CHAR(64) NOT NULL,PRIMARY KEY(assignment_order_id,artifact_type),FOREIGN KEY(assignment_order_id) REFERENCES {$t('fm2_assignment_orders')}(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'fm2_process_tasks'=>"CREATE TABLE {$t('fm2_process_tasks')} (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,installation_case_id BIGINT UNSIGNED NOT NULL,task_type VARCHAR(80) NOT NULL,assignee_user_id BIGINT UNSIGNED NULL,assignee_role VARCHAR(80) NULL,due_date DATE NULL,status VARCHAR(40) NOT NULL,completed_at VARCHAR(40) NULL,completed_by_user_id BIGINT UNSIGNED NULL,created_at VARCHAR(40) NOT NULL,PRIMARY KEY(id),KEY(status,assignee_role,due_date),FOREIGN KEY(installation_case_id) REFERENCES {$t('fm2_installation_cases')}(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'fm2_process_events'=>"CREATE TABLE {$t('fm2_process_events')} (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,installation_case_id BIGINT UNSIGNED NOT NULL,event_type VARCHAR(80) NOT NULL,occurred_at VARCHAR(40) NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,payload_json JSON NOT NULL,PRIMARY KEY(id),KEY(installation_case_id,occurred_at),FOREIGN KEY(installation_case_id) REFERENCES {$t('fm2_installation_cases')}(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];
    }
}
