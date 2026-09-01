<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Exact final and additive predecessor definitions for operation evidence. */
final class InspectionEvidenceOperationDefinitionSchemaMigration
{
    public static function operations(string $prefix, string $collation): array
    {
        $definition = InspectionEvidenceDefinitionSchemaMigration::class;
        $column = static fn (...$arguments): array => $definition::column(...$arguments);
        $index = static fn (...$arguments): array => $definition::index(...$arguments);
        $character = $definition::character($collation);
        $table = $definition::table($prefix, 'fm2_checklist_operations');
        $baseColumns = [
            $column('id','bigint(20) unsigned','NO',null,'auto_increment'),
            $column('installation_case_id','bigint(20) unsigned','NO'),
            $column('client_operation_id','char(36)','NO',null,'',true,$collation),
            $column('device_installation_id','char(36)','NO',null,'',true,$collation),
            $column('operation_type','varchar(40)','NO',null,'',true,$collation),
            $column('section_id','tinyint(3) unsigned','NO'),
            $column('item_id','smallint(5) unsigned','YES'),
            $column('actor_user_id','bigint(20) unsigned','NO'),
            $column('device_time','varchar(40)','NO',null,'',true,$collation),
            $column('server_received_at','varchar(40)','NO',null,'',true,$collation),
            $column('base_revision','bigint(20) unsigned','NO'),
            $column('accepted_revision','bigint(20) unsigned','NO'),
            $column('payload_json','text','NO',null,'',true,$collation),
        ];
        $finalColumns = array_merge($baseColumns, [
            $column('template_snapshot_id','bigint(20) unsigned','YES'),
            $column('template_snapshot_version','varchar(80)','YES',null,'',true,$collation),
            $column('template_content_sha256','char(64)','YES',null,'',true,$collation),
        ]);
        $indexes = [
            $index('PRIMARY',0,1,'id'),
            $index('client_operation_id',0,1,'client_operation_id'),
            $index('installation_case_id',1,1,'installation_case_id'),
            $index('installation_case_id',1,2,'id'),
        ];
        $ddl = "CREATE TABLE {$table}(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,"
            . 'installation_case_id BIGINT UNSIGNED NOT NULL,'
            . "client_operation_id CHAR(36){$character} NOT NULL,"
            . "device_installation_id CHAR(36){$character} NOT NULL,"
            . "operation_type VARCHAR(40){$character} NOT NULL,"
            . 'section_id TINYINT UNSIGNED NOT NULL,item_id SMALLINT UNSIGNED NULL,'
            . "actor_user_id BIGINT UNSIGNED NOT NULL,device_time VARCHAR(40){$character} NOT NULL,"
            . "server_received_at VARCHAR(40){$character} NOT NULL,"
            . 'base_revision BIGINT UNSIGNED NOT NULL,accepted_revision BIGINT UNSIGNED NOT NULL,'
            . "payload_json TEXT{$character} NOT NULL,template_snapshot_id BIGINT UNSIGNED NULL,"
            . "template_snapshot_version VARCHAR(80){$character} NULL,"
            . "template_content_sha256 CHAR(64){$character} NULL,PRIMARY KEY(id),"
            . 'UNIQUE KEY client_operation_id(client_operation_id),'
            . 'KEY installation_case_id(installation_case_id,id))'
            . $definition::tail($collation);
        $upgrade = "ALTER TABLE {$table} ADD COLUMN template_snapshot_id BIGINT UNSIGNED NULL,"
            . " ADD COLUMN template_snapshot_version VARCHAR(80){$character} NULL,"
            . " ADD COLUMN template_content_sha256 CHAR(64){$character} NULL";

        return [
            'ddl'=>$ddl,
            'final'=>['columns'=>$finalColumns, 'indexes'=>$indexes],
            'predecessor'=>['columns'=>$baseColumns, 'indexes'=>$indexes],
            'upgrade'=>[$upgrade],
        ];
    }

    public static function installers(string $prefix, string $collation): array
    {
        $definition = InspectionEvidenceDefinitionSchemaMigration::class;
        $column = static fn (...$arguments): array => $definition::column(...$arguments);
        $index = static fn (...$arguments): array => $definition::index(...$arguments);
        $character = $definition::character($collation);
        $table = $definition::table($prefix, 'fm2_checklist_operation_installers');
        $baseColumns = [
            $column('client_operation_id','char(36)','NO',null,'',true,$collation),
            $column('installer_tab_id','bigint(20) unsigned','NO'),
            $column('fio_snapshot','varchar(300)','NO',null,'',true,$collation),
            $column('position_snapshot','varchar(300)','NO',null,'',true,$collation),
            $column('employment_status_snapshot','varchar(40)','NO',null,'',true,$collation),
            $column('dismissal_effective_at_snapshot','varchar(40)','YES',null,'',true,$collation),
            $column('workforce_source_updated_at_snapshot','varchar(40)','NO',null,'',true,$collation),
        ];
        $indexes = [
            $index('PRIMARY',0,1,'client_operation_id'),
            $index('PRIMARY',0,2,'installer_tab_id'),
            $index('installer_tab_id',1,1,'installer_tab_id'),
            $index('installer_tab_id',1,2,'client_operation_id'),
        ];
        $ddl = "CREATE TABLE {$table}(client_operation_id CHAR(36){$character} NOT NULL,"
            . 'installer_tab_id BIGINT UNSIGNED NOT NULL,'
            . "fio_snapshot VARCHAR(300){$character} NOT NULL,"
            . "position_snapshot VARCHAR(300){$character} NOT NULL,"
            . "employment_status_snapshot VARCHAR(40){$character} NOT NULL,"
            . "dismissal_effective_at_snapshot VARCHAR(40){$character} NULL,"
            . "workforce_source_updated_at_snapshot VARCHAR(40){$character} NOT NULL,"
            . "assignment_source VARCHAR(40){$character} NOT NULL,"
            . 'PRIMARY KEY(client_operation_id,installer_tab_id),'
            . 'KEY installer_tab_id(installer_tab_id,client_operation_id))'
            . $definition::tail($collation);
        $finalColumns = array_merge($baseColumns, [
            $column('assignment_source','varchar(40)','NO',null,'',true,$collation),
        ]);
        $add = "ALTER TABLE {$table} ADD COLUMN assignment_source VARCHAR(40){$character} "
            . "NOT NULL DEFAULT 'pilot_backfill_current_order'";
        $dropDefault = "ALTER TABLE {$table} ALTER COLUMN assignment_source DROP DEFAULT";

        return [
            'ddl'=>$ddl,
            'final'=>['columns'=>$finalColumns, 'indexes'=>$indexes],
            'predecessor'=>['columns'=>$baseColumns, 'indexes'=>$indexes],
            'upgrade'=>[$add, $dropDefault],
        ];
    }
}
