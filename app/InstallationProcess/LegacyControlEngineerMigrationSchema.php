<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final class LegacyControlEngineerMigrationSchema
{
 public static function apply(\mysqli$db,string$p):array{
  $tables=[];$exists=static function(string$t)use($db):bool{$s=$db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$s->execute([$t]);return$s->get_result()->fetch_row()!==null;};
  $links=$p.'fm2_legacy_identity_links';if(!$exists($links)){$db->query("CREATE TABLE `$links`(link_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,local_user_id BIGINT UNSIGNED NOT NULL,legacy_user_id BIGINT UNSIGNED NOT NULL,legacy_name_snapshot VARCHAR(300) NOT NULL,legacy_email_snapshot VARCHAR(300) NOT NULL,legacy_status_snapshot TINYINT NOT NULL,legacy_role_id_snapshot BIGINT UNSIGNED NOT NULL,legacy_role_status_snapshot TINYINT NOT NULL,linked_by_user_id BIGINT UNSIGNED NOT NULL,linked_at_utc DATETIME NOT NULL,request_id CHAR(36) NOT NULL,request_fingerprint CHAR(64) NOT NULL,supersedes_link_id BIGINT UNSIGNED NULL,superseded_by_link_id BIGINT UNSIGNED NULL,correction_reason VARCHAR(500) NULL,UNIQUE KEY uq_lil_request(request_id),KEY ix_lil_local(local_user_id),KEY ix_lil_legacy(legacy_user_id))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");$tables[]=$links;}
  $events=$p.'fm2_legacy_identity_link_events';if(!$exists($events)){$db->query("CREATE TABLE `$events`(event_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,link_id BIGINT UNSIGNED NOT NULL,event_type VARCHAR(30) NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,occurred_at_utc DATETIME NOT NULL,request_id CHAR(36) NOT NULL,UNIQUE KEY uq_lile_request(request_id))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");$tables[]=$events;}
  $ops=$p.'fm2_engineer_migration_operations';if(!$exists($ops)){$db->query("CREATE TABLE `$ops`(operation_id CHAR(36) PRIMARY KEY,preview_digest CHAR(64) NOT NULL,source_fingerprint CHAR(64) NOT NULL,report_json LONGTEXT NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,applied_at_utc DATETIME NOT NULL)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");$tables[]=$ops;}
  $a=$p.'fm2_control_engineer_assignments';foreach(['assignment_source'=>"VARCHAR(30) NOT NULL DEFAULT 'manual'",'source_operation_id'=>'CHAR(36) NULL','source_legacy_object_id'=>'BIGINT UNSIGNED NULL','source_legacy_user_id'=>'BIGINT UNSIGNED NULL']as$c=>$def){$s=$db->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');$s->execute([$a,$c]);if($s->get_result()->fetch_row()===null)$db->query("ALTER TABLE `$a` ADD COLUMN `$c` $def");}
  return['applied'=>$tables!==[],'schemaVersion'=>29,'tablesCreated'=>$tables];
 }
}
