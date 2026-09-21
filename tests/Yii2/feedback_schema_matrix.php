<?php
declare(strict_types=1);
require dirname(__DIR__).'/Support/CurrentProductionSchemaContract.php';
// A7 public canonical deployment and current backup inventory, no runtime DDL.
$catalogue=FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue::migrations();assertSameValue(range(1,31),array_keys($catalogue),'v29 canonical frontier');
$before=feedbackFacts($f);$repeat=FMonitor2\InstallationProcess\CanonicalMigrationApplication::run($f->db,$f->p,$catalogue);$expected=CurrentProductionSchemaContract::replayApplicationResult();assertSameValue([$expected[0],$expected[2],$expected[3]],[$repeat['exitCode'],$repeat['result']['schemaVersion'],$repeat['result']['appliedVersions']],'populated schema replay');assertSameValue($before,feedbackFacts($f),'schema replay preserves feedback');
$actual=array_column($f->db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM),0);sort($actual);
assertSameValue($actual,FMonitor2\RuntimeRestore\RuntimeRecoverySchemaV31::tables($f->p),'backup includes exact new inventory');
$auto=array_column($f->db->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND AUTO_INCREMENT IS NOT NULL ORDER BY BINARY TABLE_NAME')->fetch_all(MYSQLI_ASSOC),'TABLE_NAME');assertSameValue($auto,FMonitor2\RuntimeRestore\RuntimeRecoverySchemaV31::autoIncrement($f->p),'backup AI inventory');
$oldProfile=FMonitor2\RuntimeRestore\RuntimeRecoverySchemaV24::tables($f->p);assertSameValue(false,in_array($f->p.'fm2_feedback',$oldProfile,true),'historical v24 unchanged');
// Public v25 migration on partial/incompatible schema must preflight before any CREATE.
$migration=$catalogue[25];$prefix='badfeedback_';
$f->db->query("CREATE TABLE {$prefix}fm2_feedback_results(id BIGINT PRIMARY KEY,wrong VARCHAR(10))");
$f->db->query("INSERT INTO {$prefix}fm2_feedback_results VALUES(7,'preserve')");
$beforeBad=$f->db->query("SHOW CREATE TABLE {$prefix}fm2_feedback_results")->fetch_row();
$result=$migration::apply($f->db,$prefix);assertSameValue('SCHEMA_MIGRATION_CONFLICT',$result['reason'],'incompatible v25 denied');assertSameValue($beforeBad,$f->db->query("SHOW CREATE TABLE {$prefix}fm2_feedback_results")->fetch_row(),'bad schema unchanged');
assertSameValue(0,(int)$f->db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}fm2_feedback'")->fetch_column(),'preflight before creating sibling');
$f->db->query("DROP TABLE {$prefix}fm2_feedback_results");
// Independent literal metadata: automatic context has nowhere to store extra PII.
$expectedColumns=[
 'fm2_feedback'=>['id'=>['bigint(20) unsigned','NO'],'request_id'=>['char(36)','NO'],'request_fingerprint'=>['char(64)','NO'],'actor_user_id'=>['bigint(20) unsigned','NO'],'description'=>['text','NO'],'page_path'=>['varchar(255)','NO'],'object_id'=>['bigint(20) unsigned','YES'],'app_version'=>['varchar(80)','NO'],'created_at'=>['datetime(6)','NO']],
 'fm2_feedback_results'=>['id'=>['bigint(20) unsigned','NO'],'feedback_id'=>['bigint(20) unsigned','NO'],'request_id'=>['char(36)','NO'],'request_fingerprint'=>['char(64)','NO'],'actor_user_id'=>['bigint(20) unsigned','NO'],'result'=>['text','NO'],'created_at'=>['datetime(6)','NO']],
];
foreach ($expectedColumns as $table=>$expected) {
 $columns=[];foreach($f->db->query("SHOW COLUMNS FROM {$f->p}$table")->fetch_all(MYSQLI_ASSOC)as$c)$columns[$c['Field']]=[$c['Type'],$c['Null']];assertSameValue($expected,$columns,'literal feedback columns '.$table);
 $indexes=[];foreach($f->db->query("SHOW INDEX FROM {$f->p}$table")->fetch_all(MYSQLI_ASSOC)as$index)$indexes[$index['Key_name']][]=$index['Column_name'];
 assertSameValue(['id'],$indexes['PRIMARY'],'literal root/event identity');
 $unique=[];foreach($f->db->query("SHOW INDEX FROM {$f->p}$table WHERE Non_unique=0 AND Key_name<>'PRIMARY'")->fetch_all(MYSQLI_ASSOC)as$index)$unique[$index['Key_name']][]=$index['Column_name'];assertSameValue([['actor_user_id','request_id']],array_values($unique),'actor-scoped unique request identity');
 assertSameValue(true,in_array($f->p.$table,$actual,true),'literal backup table membership');assertSameValue(true,in_array($f->p.$table,$auto,true),'literal backup AI membership');
}
$fk=$f->db->query("SELECT COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$f->p}fm2_feedback_results' AND REFERENCED_TABLE_NAME IS NOT NULL")->fetch_all(MYSQLI_ASSOC);
assertSameValue([['COLUMN_NAME'=>'feedback_id','REFERENCED_TABLE_NAME'=>$f->p.'fm2_feedback','REFERENCED_COLUMN_NAME'=>'id']],$fk,'result belongs to immutable feedback root');
