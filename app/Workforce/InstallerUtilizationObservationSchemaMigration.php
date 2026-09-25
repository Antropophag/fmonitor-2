<?php declare(strict_types=1);namespace FMonitor2\Workforce;
final class InstallerUtilizationObservationSchemaMigration
{
 public static function apply(\mysqli$db,string$p):array
 {
  if(strlen($p)>25||preg_match('/^[A-Za-z0-9_]*$/D',$p)!==1)throw new \InvalidArgumentException();$header=$p.'fm2_installer_utilization_observations';$members=$p.'fm2_installer_utilization_observation_members';$created=[];
  foreach([$header,$members]as$t){$q=$db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$q->execute([$t]);if((int)$q->get_result()->fetch_column()===0)$created[]=$t;}
  $db->query("CREATE TABLE IF NOT EXISTS `$header` (observation_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,observation_date_msk DATE NOT NULL,captured_at VARCHAR(40) NOT NULL,denominator INT UNSIGNED NOT NULL,working_count INT UNSIGNED NOT NULL,awaiting_start_count INT UNSIGNED NOT NULL,unassigned_count INT UNSIGNED NOT NULL,without_current_count INT UNSIGNED NOT NULL,without_next_count INT UNSIGNED NOT NULL,PRIMARY KEY(observation_id),UNIQUE KEY uq_observation_date_msk(observation_date_msk)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
  $db->query("CREATE TABLE IF NOT EXISTS `$members` (observation_id BIGINT UNSIGNED NOT NULL,installer_tab_id BIGINT UNSIGNED NOT NULL,fio VARCHAR(500) NOT NULL,employment_status VARCHAR(32) NOT NULL,state VARCHAR(32) NOT NULL,without_current TINYINT UNSIGNED NOT NULL,without_next TINYINT UNSIGNED NOT NULL,reasons_json LONGTEXT NOT NULL,PRIMARY KEY(observation_id,installer_tab_id),CONSTRAINT `{$p}iuom_observation_fk` FOREIGN KEY(observation_id) REFERENCES `$header`(observation_id) ON UPDATE RESTRICT ON DELETE RESTRICT,CHECK(employment_status IN('employed','dismissed')),CHECK(state IN('working','awaiting_start','unassigned')),CHECK(without_current IN(0,1)),CHECK(without_next IN(0,1)),CHECK(JSON_VALID(reasons_json))) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
  return['applied'=>$created!==[],'schemaVersion'=>35,'tablesCreated'=>$created,'tablesUpgraded'=>[]];
 }
}
