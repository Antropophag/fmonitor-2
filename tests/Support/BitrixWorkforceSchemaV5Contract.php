<?php

declare(strict_types=1);

/** Literal contract transcribed from BITRIX-WORKFORCE-SCHEMA-001 v0.3. */
final class BitrixWorkforceSchemaV5Contract
{
    public static function columns(): array
    {
        return [
            'fm2_workforce_catalog' => self::parse('installer_tab_id:bigint unsigned:NO::NULL;fio:varchar(300):NO::NULL;position:varchar(300):NO::NULL;employment_status:varchar(40):NO::NULL;employed_from:date:YES::NULL;employed_to:date:YES::NULL;workforce_source:varchar(80):NO::NULL;workforce_source_updated_at:varchar(40):NO::NULL;delivery_system:varchar(40):YES::NULL;delivery_person_id:bigint unsigned:YES::NULL;dismissal_effective_at:date:YES::NULL;first_observed_dismissed_at:varchar(40):YES::NULL;dismissal_time_quality:varchar(40):YES::NULL;reconciliation_state:varchar(40):YES::NULL;authority_system:varchar(40):YES::NULL;last_successful_sync_run_id:char(36):YES::NULL;last_successful_sync_at:varchar(40):YES::NULL'),
            'fm2_workforce_observations' => self::parse('id:bigint unsigned:NO:auto_increment:NULL;sync_run_id:char(36):NO::NULL;delivery_person_id:bigint unsigned:NO::NULL;employee_number:bigint unsigned:NO::NULL;full_name:varchar(300):NO::NULL;position:varchar(300):NO::NULL;employment_status:varchar(40):NO::NULL;employed_from:date:YES::NULL;dismissal_effective_at:date:YES::NULL;authority_system:varchar(40):NO::NULL;delivery_system:varchar(40):NO::NULL;source_modified_at:varchar(40):YES::NULL;reconciliation_state:varchar(40):NO::NULL;observed_at:varchar(40):NO::NULL;dismissal_time_quality:varchar(40):NO::NULL'),
            'fm2_workforce_sync_runs' => self::parse('run_id:char(36):NO::NULL;status:varchar(20):NO::NULL;started_at:varchar(40):NO::NULL;observed_at:varchar(40):YES::NULL;completed_at:varchar(40):YES::NULL;failure_code:varchar(80):YES::NULL;page_count:int unsigned:YES::NULL;delivered_count:int unsigned:YES::NULL;material_change_count:int unsigned:YES::NULL;missing_count:int unsigned:YES::NULL;normalized_checksum:char(64):YES::NULL'),
            'fm2_workforce_sync_metadata' => self::parse('singleton_id:tinyint unsigned:NO::NULL;last_successful_run_id:char(36):YES::NULL;last_successful_at:varchar(40):YES::NULL'),
        ];
    }

    public static function indexes(string $prefix): array
    {
        return [
            'fm2_workforce_catalog' => ['PRIMARY|0|BTREE|installer_tab_id:FULL:A:NO', 'uq_fm2_workforce_delivery_identity|0|BTREE|delivery_system:FULL:A:NO,delivery_person_id:FULL:A:NO', 'ix_fm2_workforce_status_reconciliation_sync|1|BTREE|employment_status:FULL:A:NO,reconciliation_state:FULL:A:NO,last_successful_sync_at:FULL:A:NO'],
            'fm2_workforce_observations' => ['PRIMARY|0|BTREE|id:FULL:A:NO', 'uq_fm2_workforce_observation_run_person|0|BTREE|sync_run_id:FULL:A:NO,delivery_system:FULL:A:NO,delivery_person_id:FULL:A:NO', 'ix_fm2_workforce_observation_person_time|1|BTREE|delivery_system:FULL:A:NO,delivery_person_id:FULL:A:NO,observed_at:FULL:A:NO', 'ix_fm2_workforce_observation_employee_time|1|BTREE|employee_number:FULL:A:NO,observed_at:FULL:A:NO'],
            'fm2_workforce_sync_runs' => ['PRIMARY|0|BTREE|run_id:FULL:A:NO'],
            'fm2_workforce_sync_metadata' => ['PRIMARY|0|BTREE|singleton_id:FULL:A:NO', self::symbol($prefix, 'fk_fm2_workforce_metadata_run', 'fk_', 'wf_meta_run') . '|1|BTREE|last_successful_run_id:FULL:A:NO'],
        ];
    }

    /** Exact test-owned source oracle transcribed from WORKFORCE-CATALOG-001 v0.1. */
    public static function v2Columns(): array
    {
        return self::parse('installer_tab_id:bigint unsigned:NO::NULL;fio:varchar(300):NO::NULL;position:varchar(300):NO::NULL;employment_status:varchar(40):NO::NULL;employed_from:date:YES::NULL;employed_to:date:YES::NULL;workforce_source:varchar(80):NO::NULL;workforce_source_updated_at:varchar(40):NO::NULL');
    }

    public static function v2Indexes(): array
    {
        return ['PRIMARY|0|BTREE|installer_tab_id:FULL:A:NO', 'employment_status|1|BTREE|employment_status:FULL:A:NO,employed_to:FULL:A:NO'];
    }

    public static function checks(string $prefix): array
    {
        return [
            'fm2_workforce_catalog' => [
                self::symbol($prefix, 'ck_fm2_workforce_dismissal_quality', 'ck_', 'wf_cat_dq') . "|dismissal_time_qualityisnullordismissal_time_qualityin('observed_only','effective_from_source')",
                self::symbol($prefix, 'ck_fm2_workforce_employment_status', 'ck_', 'wf_cat_emp') . "|employment_statusin('employed','dismissed')",
                self::symbol($prefix, 'ck_fm2_workforce_reconciliation_state', 'ck_', 'wf_cat_rec') . "|reconciliation_stateisnullorreconciliation_statein('delivered','missing_from_delivery')",
            ],
            'fm2_workforce_observations' => [
                self::symbol($prefix, 'ck_fm2_workforce_observation_dismissal_quality', 'ck_', 'wf_obs_dq') . "|dismissal_time_qualityin('observed_only','effective_from_source')",
                self::symbol($prefix, 'ck_fm2_workforce_observation_reconciliation', 'ck_', 'wf_obs_rec') . "|reconciliation_statein('delivered','missing_from_delivery')",
                self::symbol($prefix, 'ck_fm2_workforce_observation_status', 'ck_', 'wf_obs_status') . "|employment_statusin('employed','dismissed')",
            ],
            'fm2_workforce_sync_runs' => [self::symbol($prefix, 'ck_fm2_workforce_sync_run_status', 'ck_', 'wf_run_status') . "|statusin('started','completed','failed')"],
            'fm2_workforce_sync_metadata' => [self::symbol($prefix, 'ck_fm2_workforce_sync_metadata_singleton', 'ck_', 'wf_meta_one') . '|singleton_id=1'],
        ];
    }

    public static function foreignKeys(string $prefix): array
    {
        return [
            'fm2_workforce_catalog' => [],
            'fm2_workforce_observations' => [self::symbol($prefix, 'fk_fm2_workforce_observation_run', 'fk_', 'wf_obs_run') . '|sync_run_id|' . $prefix . 'fm2_workforce_sync_runs|run_id|RESTRICT|RESTRICT'],
            'fm2_workforce_sync_runs' => [],
            'fm2_workforce_sync_metadata' => [self::symbol($prefix, 'fk_fm2_workforce_metadata_run', 'fk_', 'wf_meta_run') . '|last_successful_run_id|' . $prefix . 'fm2_workforce_sync_runs|run_id|RESTRICT|RESTRICT'],
        ];
    }

    private static function symbol(string $prefix, string $empty, string $category, string $token): string
    {
        return $prefix === '' ? $empty : $category . $prefix . $token;
    }

    private static function parse(string $value): array
    {
        return array_map(static fn (string $column): array => explode(':', $column, 5), explode(';', $value));
    }
}
