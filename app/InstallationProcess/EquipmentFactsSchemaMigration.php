<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class EquipmentFactsSchemaMigration
{
    private const TABLES=['fm2_equipment_fact_current','fm2_equipment_fact_history','fm2_equipment_fact_runs','fm2_equipment_fact_sync_metadata','fm2_equipment_fact_diagnostics'];
    public static function apply(\mysqli$db,string$prefix=''):array
    {
        MariaDbSchemaInspector::validateTablePrefix($prefix);$present=[];foreach(self::TABLES as$name)$present[$name]=MariaDbSchemaInspector::tableExists($db,$prefix.$name);
        if(in_array(true,$present,true)){if(in_array(false,$present,true)||!self::compatible($db,$prefix))throw new \RuntimeException('SCHEMA_MIGRATION_CONFLICT');return['applied'=>false,'schemaVersion'=>31,'tablesCreated'=>[]];}
        $db->begin_transaction();try{
            $db->query("CREATE TABLE `{$prefix}fm2_equipment_fact_current`(object_id BIGINT UNSIGNED NOT NULL,readiness_date DATE NULL,first_shipment_date DATE NULL,full_shipment_date DATE NULL,source VARCHAR(20) NOT NULL,source_order_hmac CHAR(64) NOT NULL,last_successful_run_id CHAR(36) NOT NULL,last_successful_observed_at DATETIME(6) NOT NULL,PRIMARY KEY(object_id),KEY last_success(last_successful_observed_at),CONSTRAINT ck_ef_current_source CHECK(source='1c_erp'),CONSTRAINT ck_ef_current_hmac CHECK(source_order_hmac REGEXP '^[0-9a-f]{64}$'))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
            $db->query("CREATE TABLE `{$prefix}fm2_equipment_fact_history`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,object_id BIGINT UNSIGNED NOT NULL,fact_type VARCHAR(24) NOT NULL,old_value DATE NULL,new_value DATE NULL,source VARCHAR(20) NOT NULL,source_order_hmac CHAR(64) NOT NULL,run_id CHAR(36) NOT NULL,observed_at DATETIME(6) NOT NULL,PRIMARY KEY(id),KEY object_history(object_id,id),KEY run_fact(run_id,fact_type),CONSTRAINT ck_ef_history_type CHECK(fact_type IN('readiness','first_shipment','full_shipment')),CONSTRAINT ck_ef_history_change CHECK(NOT(old_value <=> new_value)),CONSTRAINT ck_ef_history_source CHECK(source='1c_erp'),CONSTRAINT ck_ef_history_hmac CHECK(source_order_hmac REGEXP '^[0-9a-f]{64}$'))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
            $db->query("CREATE TABLE `{$prefix}fm2_equipment_fact_runs`(run_id CHAR(36) NOT NULL,command_hash CHAR(64) NOT NULL,kind VARCHAR(16) NOT NULL,status VARCHAR(16) NOT NULL,reason VARCHAR(40) NULL,observed_at DATETIME(6) NOT NULL,receipt_json JSON NOT NULL,created_at DATETIME(6) NOT NULL,PRIMARY KEY(run_id),KEY observed_status(observed_at,status),CONSTRAINT ck_ef_runs_hash CHECK(command_hash REGEXP '^[0-9a-f]{64}$'),CONSTRAINT ck_ef_runs_kind CHECK(kind IN('complete','failed')),CONSTRAINT ck_ef_runs_status CHECK(status IN('completed','failed')))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
            $db->query("CREATE TABLE `{$prefix}fm2_equipment_fact_sync_metadata`(singleton_id TINYINT UNSIGNED NOT NULL,last_successful_run_id CHAR(36) NULL,last_successful_observed_at DATETIME(6) NULL,latest_failure_reason VARCHAR(40) NULL,latest_failure_observed_at DATETIME(6) NULL,PRIMARY KEY(singleton_id),CONSTRAINT ck_ef_metadata_singleton CHECK(singleton_id=1))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
            $db->query("CREATE TABLE `{$prefix}fm2_equipment_fact_diagnostics`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,run_id CHAR(36) NOT NULL,source_order_hmac CHAR(64) NOT NULL,reason VARCHAR(40) NOT NULL,created_at DATETIME(6) NOT NULL,PRIMARY KEY(id),KEY run_reason(run_id,reason),CONSTRAINT ck_ef_diag_reason CHECK(reason IN('OBJECT_NOT_FOUND','OBJECT_AMBIGUOUS')),CONSTRAINT ck_ef_diag_hmac CHECK(source_order_hmac REGEXP '^[0-9a-f]{64}$'))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
            $db->query("INSERT INTO `{$prefix}fm2_equipment_fact_sync_metadata`(singleton_id)VALUES(1)");
            $db->query("CREATE TRIGGER `{$prefix}ef_history_no_update` BEFORE UPDATE ON `{$prefix}fm2_equipment_fact_history` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='EQUIPMENT_FACT_HISTORY_APPEND_ONLY'");
            $db->query("CREATE TRIGGER `{$prefix}ef_history_no_delete` BEFORE DELETE ON `{$prefix}fm2_equipment_fact_history` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='EQUIPMENT_FACT_HISTORY_APPEND_ONLY'");
            $db->commit();
        }catch(\Throwable$e){$db->rollback();throw$e;}
        return['applied'=>true,'schemaVersion'=>31,'tablesCreated'=>array_map(fn($x)=>$prefix.$x,self::TABLES)];
    }
    private static function compatible(\mysqli$db,string$p):bool
    {
        $expected=['fm2_equipment_fact_current'=>['object_id','readiness_date','first_shipment_date','full_shipment_date','source','source_order_hmac','last_successful_run_id','last_successful_observed_at'],'fm2_equipment_fact_history'=>['id','object_id','fact_type','old_value','new_value','source','source_order_hmac','run_id','observed_at'],'fm2_equipment_fact_runs'=>['run_id','command_hash','kind','status','reason','observed_at','receipt_json','created_at'],'fm2_equipment_fact_sync_metadata'=>['singleton_id','last_successful_run_id','last_successful_observed_at','latest_failure_reason','latest_failure_observed_at'],'fm2_equipment_fact_diagnostics'=>['id','run_id','source_order_hmac','reason','created_at']];
        foreach($expected as$t=>$columns)if(array_column(MariaDbSchemaInspector::columns($db,$p.$t),'COLUMN_NAME')!==$columns)return false;return true;
    }
}
