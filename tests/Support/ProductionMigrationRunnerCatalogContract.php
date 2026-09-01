<?php

declare(strict_types=1);

/**
 * Literal catalog contract transcribed from the approved v1-v8 specifications.
 * It deliberately does not load migration classes or production SQL.
 */
final class ProductionMigrationRunnerCatalogContract
{
    /** @return array<string, list<array{0:string,1:string,2:string,3:string}>> */
    public static function columns(): array
    {
        return [
            'fm2_checklist_operation_installers' => self::parseColumns(
                'client_operation_id:char(36):NO:;installer_tab_id:bigint unsigned:NO:;fio_snapshot:varchar(300):NO:;position_snapshot:varchar(300):NO:;employment_status_snapshot:varchar(40):NO:;dismissal_effective_at_snapshot:varchar(40):YES:;workforce_source_updated_at_snapshot:varchar(40):NO:;assignment_source:varchar(40):NO:'
            ),
            'fm2_checklist_operations' => self::parseColumns(
                'id:bigint unsigned:NO:auto_increment;installation_case_id:bigint unsigned:NO:;client_operation_id:char(36):NO:;device_installation_id:char(36):NO:;operation_type:varchar(40):NO:;section_id:tinyint unsigned:NO:;item_id:smallint unsigned:YES:;actor_user_id:bigint unsigned:NO:;device_time:varchar(40):NO:;server_received_at:varchar(40):NO:;base_revision:bigint unsigned:NO:;accepted_revision:bigint unsigned:NO:;payload_json:text:NO:;template_snapshot_id:bigint unsigned:YES:;template_snapshot_version:varchar(80):YES:;template_content_sha256:char(64):YES:'
            ),
            'fm2_checklist_photos' => self::parseColumns(
                'id:bigint unsigned:NO:auto_increment;installation_case_id:bigint unsigned:NO:;section_id:tinyint unsigned:NO:;upload_operation_id:char(36):NO:;sha256:char(64):NO:;mime_type:varchar(40):NO:;byte_size:int unsigned:NO:;original_name:varchar(255):NO:;storage_name:varchar(255):NO:;actor_user_id:bigint unsigned:NO:;device_time:varchar(40):NO:;server_received_at:varchar(40):NO:;revoked_at:varchar(40):YES:'
            ),
            'fm2_checklist_revisions' => self::parseColumns(
                'installation_case_id:bigint unsigned:NO:;revision_no:bigint unsigned:NO:;updated_at:varchar(40):NO:'
            ),
            'fm2_assignment_orders' => self::parseColumns(
                'id:bigint unsigned:NO:auto_increment;installation_case_id:bigint unsigned:NO:;version_no:smallint unsigned:NO:;kind:varchar(40):NO:;status:varchar(40):NO:;order_date:date:NO:;registration_number:varchar(120):YES:;registered_at:varchar(40):YES:;registration_actor_type:varchar(40):YES:;registration_actor_id:varchar(120):YES:;registration_source:varchar(40):YES:;external_registration_id:varchar(120):YES:;control_engineer_user_id:bigint unsigned:NO:;control_engineer_fio_snapshot:varchar(300):NO:;control_engineer_position_snapshot:varchar(300):NO:;organization_form:varchar(40):NO:;previous_assignment_order_id:bigint unsigned:YES:;object_address_snapshot:varchar(500):NO:;entrance_snapshot:varchar(80):NO:;object_registration_number_snapshot:varchar(120):NO:;planned_start_date_snapshot:date:NO:;planned_finish_date_snapshot:date:NO:;pto_act_date_snapshot:date:YES:;prepared_at:varchar(40):NO:;prepared_by_user_id:bigint unsigned:NO:'
            ),
            'fm2_checklist_template_associations' => self::parseColumns(
                'id:bigint unsigned:NO:auto_increment;association_version:varchar(80):NO:;subject_kind:varchar(40):NO:;subject_id:varchar(160):NO:;effective_at:datetime:NO:;template_snapshot_id:bigint unsigned:NO:;template_snapshot_version:varchar(80):NO:;template_content_sha256:char(64):NO:;created_at:datetime:NO:'
            ),
            'fm2_checklist_template_snapshots' => self::parseColumns(
                'id:bigint unsigned:NO:auto_increment;snapshot_version:varchar(80):NO:;captured_at:datetime:NO:;valid_from:datetime:NO:;validity_scope:varchar(120):NO:;source_label:varchar(160):NO:;content_sha256:char(64):NO:;payload_json:longtext:NO:;created_at:datetime:NO:'
            ),
            'fm2_installation_cases' => self::parseColumns(
                'id:bigint unsigned:NO:auto_increment;legacy_installation_object_id:bigint unsigned:NO:;process_state:varchar(80):NO:;actual_start_date:date:YES:;opened_at:varchar(40):YES:;opened_by_user_id:bigint unsigned:YES:;created_at:varchar(40):NO:;updated_at:varchar(40):NO:;lock_version:int unsigned:NO:'
            ),
            'fm2_pilot_auth_attempts' => self::parseColumns(
                'id:bigint unsigned:NO:auto_increment;email_normalized:varchar(254):NO:;succeeded:tinyint:NO:;attempted_at:datetime(6):NO:'
            ),
            'fm2_pilot_auth_credentials' => self::parseColumns(
                'user_id:bigint unsigned:NO:;email_normalized:varchar(254):NO:;password_hash:varchar(255):YES:;password_set_at:varchar(40):YES:;updated_at:varchar(40):NO:'
            ),
            'fm2_pilot_invitations' => self::parseColumns(
                'id:bigint unsigned:NO:auto_increment;user_id:bigint unsigned:NO:;token_hash:binary(32):NO:;expires_at:datetime(6):NO:;used_at:datetime(6):YES:;revoked_at:datetime(6):YES:;created_by_user_id:bigint unsigned:YES:;created_at:datetime(6):NO:'
            ),
            'fm2_pilot_role_permissions' => self::parseColumns(
                'role_id:bigint unsigned:NO:;permission:varchar(100):NO:'
            ),
            'fm2_pilot_roles' => self::parseColumns(
                'role_id:bigint unsigned:NO:auto_increment;code:varchar(64):NO:;name:varchar(300):NO:;description:varchar(500):NO:;status:tinyint:NO:;source_updated_at:varchar(40):NO:'
            ),
            'fm2_pilot_user_role_events' => self::parseColumns(
                'id:bigint unsigned:NO:auto_increment;user_id:bigint unsigned:NO:;role_id:bigint unsigned:NO:;action:varchar(40):NO:;occurred_at:varchar(40):NO:;actor_user_id:bigint unsigned:YES:'
            ),
            'fm2_pilot_user_roles' => self::parseColumns(
                'user_id:bigint unsigned:NO:;role_id:bigint unsigned:NO:;origin:varchar(40):NO:;assigned_at:varchar(40):NO:;assigned_by_user_id:bigint unsigned:YES:'
            ),
            'fm2_pilot_user_status_events' => self::parseColumns(
                'id:bigint unsigned:NO:auto_increment;user_id:bigint unsigned:NO:;action:varchar(40):NO:;occurred_at:varchar(40):NO:;actor_user_id:bigint unsigned:NO:'
            ),
            'fm2_pilot_users' => self::parseColumns(
                'user_id:bigint unsigned:NO:auto_increment;full_name:varchar(300):NO:;email:varchar(254):NO:;phone:varchar(100):NO:;status:tinyint:NO:;activation_state:enum(\'invited\',\'active\',\'blocked\'):NO:;session_version:int unsigned:NO:;source_updated_at:varchar(40):NO:'
            ),
            'fm2_order_artifacts' => self::parseColumns(
                'assignment_order_id:bigint unsigned:NO:;artifact_type:varchar(40):NO:;filename:varchar(500):NO:;media_type:varchar(120):NO:;byte_size:bigint unsigned:NO:;sha256:char(64):NO:'
            ),
            'fm2_order_installers' => self::parseColumns(
                'assignment_order_id:bigint unsigned:NO:;installer_tab_id:bigint unsigned:NO:;fio_snapshot:varchar(300):NO:;position_snapshot:varchar(300):NO:;employment_status_snapshot:varchar(40):NO:;employed_from_snapshot:date:NO:;employed_to_snapshot:date:YES:;workforce_source_snapshot:varchar(80):NO:;workforce_source_updated_at_snapshot:varchar(40):NO:;valid_from:date:NO:;valid_to:date:YES:;change_action:varchar(40):NO:'
            ),
            'fm2_process_events' => self::parseColumns(
                'id:bigint unsigned:NO:auto_increment;installation_case_id:bigint unsigned:NO:;event_type:varchar(80):NO:;occurred_at:varchar(40):NO:;actor_user_id:bigint unsigned:NO:;payload_json:json:NO:'
            ),
            'fm2_process_tasks' => self::parseColumns(
                'id:bigint unsigned:NO:auto_increment;installation_case_id:bigint unsigned:NO:;task_type:varchar(80):NO:;assignee_user_id:bigint unsigned:YES:;assignee_role:varchar(80):YES:;due_date:date:YES:;status:varchar(40):NO:;completed_at:varchar(40):YES:;completed_by_user_id:bigint unsigned:YES:;created_at:varchar(40):NO:'
            ),
            'fm2_process_user_capabilities' => self::parseColumns(
                'user_id:bigint unsigned:NO:;capability:varchar(80):NO:;position_snapshot:varchar(300):YES:'
            ),
            'fm2_workforce_catalog' => self::parseColumns(
                'installer_tab_id:bigint unsigned:NO:;fio:varchar(300):NO:;position:varchar(300):NO:;employment_status:varchar(40):NO:;employed_from:date:YES:;employed_to:date:YES:;workforce_source:varchar(80):NO:;workforce_source_updated_at:varchar(40):NO:;delivery_system:varchar(40):YES:;delivery_person_id:bigint unsigned:YES:;dismissal_effective_at:date:YES:;first_observed_dismissed_at:varchar(40):YES:;dismissal_time_quality:varchar(40):YES:;reconciliation_state:varchar(40):YES:;authority_system:varchar(40):YES:;last_successful_sync_run_id:char(36):YES:;last_successful_sync_at:varchar(40):YES:'
            ),
            'fm2_workforce_observations' => self::parseColumns(
                'id:bigint unsigned:NO:auto_increment;sync_run_id:char(36):NO:;delivery_person_id:bigint unsigned:NO:;employee_number:bigint unsigned:NO:;full_name:varchar(300):NO:;position:varchar(300):NO:;employment_status:varchar(40):NO:;employed_from:date:YES:;dismissal_effective_at:date:YES:;authority_system:varchar(40):NO:;delivery_system:varchar(40):NO:;source_modified_at:varchar(40):YES:;reconciliation_state:varchar(40):NO:;observed_at:varchar(40):NO:;dismissal_time_quality:varchar(40):NO:'
            ),
            'fm2_workforce_sync_metadata' => self::parseColumns(
                'singleton_id:tinyint unsigned:NO:;last_successful_run_id:char(36):YES:;last_successful_at:varchar(40):YES:'
            ),
            'fm2_workforce_sync_runs' => self::parseColumns(
                'run_id:char(36):NO:;status:varchar(20):NO:;started_at:varchar(40):NO:;observed_at:varchar(40):YES:;completed_at:varchar(40):YES:;failure_code:varchar(80):YES:;page_count:int unsigned:YES:;delivered_count:int unsigned:YES:;material_change_count:int unsigned:YES:;missing_count:int unsigned:YES:;normalized_checksum:char(64):YES:'
            ),
        ];
    }

    /** @return list<string> */
    public static function indexes(): array
    {
        return [
            'fm2_checklist_operation_installers|PRIMARY|client_operation_id,installer_tab_id',
            'fm2_checklist_operation_installers|INDEX|installer_tab_id,client_operation_id',
            'fm2_checklist_operations|PRIMARY|id',
            'fm2_checklist_operations|UNIQUE|client_operation_id',
            'fm2_checklist_operations|INDEX|installation_case_id,id',
            'fm2_checklist_photos|PRIMARY|id',
            'fm2_checklist_photos|UNIQUE|upload_operation_id',
            'fm2_checklist_photos|UNIQUE|installation_case_id,section_id,sha256',
            'fm2_checklist_photos|INDEX|installation_case_id,section_id',
            'fm2_checklist_revisions|PRIMARY|installation_case_id',
            'fm2_assignment_orders|PRIMARY|id',
            'fm2_assignment_orders|UNIQUE|installation_case_id,version_no',
            'fm2_assignment_orders|INDEX|installation_case_id,status',
            'fm2_assignment_orders|INDEX|previous_assignment_order_id',
            'fm2_checklist_template_associations|PRIMARY|id',
            'fm2_checklist_template_associations|UNIQUE|subject_kind,subject_id',
            'fm2_checklist_template_associations|INDEX|template_snapshot_id',
            'fm2_checklist_template_snapshots|PRIMARY|id',
            'fm2_checklist_template_snapshots|UNIQUE|content_sha256',
            'fm2_checklist_template_snapshots|UNIQUE|valid_from',
            'fm2_installation_cases|PRIMARY|id',
            'fm2_installation_cases|UNIQUE|legacy_installation_object_id',
            'fm2_pilot_auth_attempts|PRIMARY|id',
            'fm2_pilot_auth_attempts|INDEX|email_normalized,attempted_at',
            'fm2_pilot_auth_credentials|PRIMARY|user_id',
            'fm2_pilot_auth_credentials|UNIQUE|email_normalized',
            'fm2_pilot_invitations|PRIMARY|id',
            'fm2_pilot_invitations|UNIQUE|token_hash',
            'fm2_pilot_invitations|INDEX|user_id,expires_at',
            'fm2_pilot_role_permissions|PRIMARY|role_id,permission',
            'fm2_pilot_roles|PRIMARY|role_id',
            'fm2_pilot_roles|UNIQUE|code',
            'fm2_pilot_user_role_events|PRIMARY|id',
            'fm2_pilot_user_role_events|INDEX|user_id,id',
            'fm2_pilot_user_roles|PRIMARY|user_id,role_id',
            'fm2_pilot_user_roles|INDEX|role_id',
            'fm2_pilot_user_status_events|PRIMARY|id',
            'fm2_pilot_user_status_events|INDEX|user_id,id',
            'fm2_pilot_users|PRIMARY|user_id',
            'fm2_pilot_users|UNIQUE|email',
            'fm2_pilot_users|INDEX|status,full_name',
            'fm2_order_artifacts|PRIMARY|assignment_order_id,artifact_type',
            'fm2_order_installers|PRIMARY|assignment_order_id,installer_tab_id',
            'fm2_process_events|PRIMARY|id',
            'fm2_process_events|INDEX|installation_case_id,occurred_at',
            'fm2_process_tasks|PRIMARY|id',
            'fm2_process_tasks|INDEX|installation_case_id',
            'fm2_process_tasks|INDEX|status,assignee_role,due_date',
            'fm2_process_user_capabilities|PRIMARY|user_id,capability',
            'fm2_process_user_capabilities|INDEX|capability,user_id',
            'fm2_workforce_catalog|PRIMARY|installer_tab_id',
            'fm2_workforce_catalog|UNIQUE|delivery_system,delivery_person_id',
            'fm2_workforce_catalog|INDEX|employment_status,reconciliation_state,last_successful_sync_at',
            'fm2_workforce_observations|PRIMARY|id',
            'fm2_workforce_observations|UNIQUE|sync_run_id,delivery_system,delivery_person_id',
            'fm2_workforce_observations|INDEX|delivery_system,delivery_person_id,observed_at',
            'fm2_workforce_observations|INDEX|employee_number,observed_at',
            'fm2_workforce_sync_metadata|PRIMARY|singleton_id',
            'fm2_workforce_sync_metadata|INDEX|last_successful_run_id',
            'fm2_workforce_sync_runs|PRIMARY|run_id',
        ];
    }

    /** @return list<string> */
    public static function foreignKeys(): array
    {
        return [
            'fm2_assignment_orders|installation_case_id|fm2_installation_cases|id|RESTRICT',
            'fm2_assignment_orders|previous_assignment_order_id|fm2_assignment_orders|id|RESTRICT',
            'fm2_order_artifacts|assignment_order_id|fm2_assignment_orders|id|RESTRICT',
            'fm2_order_installers|assignment_order_id|fm2_assignment_orders|id|RESTRICT',
            'fm2_pilot_auth_credentials|user_id|fm2_pilot_users|user_id|CASCADE',
            'fm2_pilot_invitations|user_id|fm2_pilot_users|user_id|CASCADE',
            'fm2_pilot_role_permissions|role_id|fm2_pilot_roles|role_id|CASCADE',
            'fm2_pilot_user_roles|role_id|fm2_pilot_roles|role_id|RESTRICT',
            'fm2_pilot_user_roles|user_id|fm2_pilot_users|user_id|CASCADE',
            'fm2_process_events|installation_case_id|fm2_installation_cases|id|RESTRICT',
            'fm2_process_tasks|installation_case_id|fm2_installation_cases|id|RESTRICT',
            'fm2_workforce_observations|sync_run_id|fm2_workforce_sync_runs|run_id|RESTRICT',
            'fm2_workforce_sync_metadata|last_successful_run_id|fm2_workforce_sync_runs|run_id|RESTRICT',
        ];
    }

    /** @return list<array{table:string,constraint:?string,clause:string}> */
    public static function checks(): array
    {
        return [
            ['table' => 'fm2_process_events', 'constraint' => null, 'clause' => 'json_valid(payload_json)'],
            ['table' => 'fm2_process_user_capabilities', 'constraint' => null, 'clause' => "OR(capability<>'construction_control_engineer',AND(position_snapshotisnotnull,trim(position_snapshot)<>''))"],
            ['table' => 'fm2_process_user_capabilities', 'constraint' => 'ck_fm2_process_user_capability', 'clause' => "capabilityin('assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer')"],
            ['table' => 'fm2_workforce_catalog', 'constraint' => null, 'clause' => "dismissal_time_qualityisnullordismissal_time_qualityin('observed_only','effective_from_source')"],
            ['table' => 'fm2_workforce_catalog', 'constraint' => null, 'clause' => "employment_statusin('employed','dismissed')"],
            ['table' => 'fm2_workforce_catalog', 'constraint' => null, 'clause' => "reconciliation_stateisnullorreconciliation_statein('delivered','missing_from_delivery')"],
            ['table' => 'fm2_workforce_observations', 'constraint' => null, 'clause' => "dismissal_time_qualityin('observed_only','effective_from_source')"],
            ['table' => 'fm2_workforce_observations', 'constraint' => null, 'clause' => "employment_statusin('employed','dismissed')"],
            ['table' => 'fm2_workforce_observations', 'constraint' => null, 'clause' => "reconciliation_statein('delivered','missing_from_delivery')"],
            ['table' => 'fm2_workforce_sync_metadata', 'constraint' => null, 'clause' => 'singleton_id=1'],
            ['table' => 'fm2_workforce_sync_runs', 'constraint' => null, 'clause' => "statusin('started','completed','failed')"],
        ];
    }

    /** @return list<array{0:string,1:string,2:string,3:string}> */
    private static function parseColumns(string $contract): array
    {
        return array_map(
            static fn (string $column): array => explode(':', $column, 4),
            explode(';', $contract),
        );
    }
}
