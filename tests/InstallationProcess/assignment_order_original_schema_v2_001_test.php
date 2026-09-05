<?php

declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalDatabaseSetupV1.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigration;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigrationPhase;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigrationStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigrationUnavailable;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigrationVerificationFactory;
use FMonitor2\Tests\Support\AssignmentOrderOriginalDatabaseSetupV1 as Contract;
use FMonitor2\Tests\Support\AssignmentOrderOriginalSchemaMigrationObserverSpy;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v54, schema-v2 RED.
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';
$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);
$user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';
$password=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';
$token=bin2hex(random_bytes(6));
$admin=new mysqli($host,$user,$password,'',$port);
$admin->set_charset('utf8mb4');
$created=[];
$prefix='v2_';
$quote=static fn(string$value):string=>'`'.str_replace('`','``',$value).'`';
$newDb=static function(string$axis)use(&$created,$admin,$host,$port,$user,$password,$token,$quote):mysqli{
    $name="t_aoou_v2_{$axis}_{$token}";
    if(preg_match('/^t_aoou_v2_[a-z0-9]+_[0-9a-f]{12}$/D',$name)!==1)throw new TestFailure('Unsafe schema-v2 database identity.');
    $admin->query('CREATE DATABASE '.$quote($name).' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created[]=$name;
    $db=new mysqli($host,$user,$password,$name,$port);$db->set_charset('utf8mb4');return$db;
};
$prepareV4=static function(mysqli$db)use($prefix):void{
    FMonitor2\InstallationProcess\ProductionProcessSchemaMigration::apply($db,$prefix);
    FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration::apply($db,$prefix);
    FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration::apply($db,$prefix);
};
$contentIndexes=static function(mysqli$db)use($prefix):array{
    $table=$prefix.Contract::TABLES[1];
    $statement=$db->prepare("SELECT INDEX_NAME,NON_UNIQUE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') columns_csv FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? GROUP BY INDEX_NAME,NON_UNIQUE HAVING columns_csv LIKE '%private_content_identity%' ORDER BY BINARY INDEX_NAME");
    $statement->bind_param('s',$table);$statement->execute();return$statement->get_result()->fetch_all(MYSQLI_ASSOC);
};
$soleContentIndex=static function(mysqli$db)use($contentIndexes):array{
    $rows=array_values(array_filter($contentIndexes($db),static fn(array$row):bool=>$row['columns_csv']==='private_content_identity'));
    assertSameValue(1,count($rows),'Fixture resolves exactly one sole-column content index.');return$rows[0];
};
$toV1=static function(mysqli$db,string$name='legacy_content_key')use($prefix,$soleContentIndex,$quote):void{
    if(preg_match('/^[A-Za-z0-9_$]{1,64}$/D',$name)!==1)throw new TestFailure('V1 fixture requires a safe index name.');
    $index=$soleContentIndex($db);
    $db->query('ALTER TABLE '.$quote($prefix.Contract::TABLES[1]).' DROP INDEX '.$quote($index['INDEX_NAME']).', ADD UNIQUE INDEX '.$quote($name).' (private_content_identity)');
    assertSameValue([$name,'0','private_content_identity'],array_values($soleContentIndex($db)),'Fixture establishes exact historical v1 unique predecessor.');
};
$toV4=static function(mysqli$db)use($prefix,$quote):void{
    $table=$prefix.'fm2_process_user_capabilities';
    $statement=$db->prepare("SELECT CONSTRAINT_NAME FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND CHECK_CLAUSE LIKE '%capability%in%'");$statement->bind_param('s',$table);$statement->execute();$rows=$statement->get_result()->fetch_all(MYSQLI_ASSOC);
    assertSameValue(1,count($rows),'Fixture resolves one capability enum CHECK.');$name=(string)$rows[0]['CONSTRAINT_NAME'];assertSameValue(1,preg_match('/^[A-Za-z0-9_$]{1,64}$/D',$name),'Fixture capability CHECK name is safe.');
    $v4="capability IN ('assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer')";
    $db->query('ALTER TABLE '.$quote($table).' DROP CONSTRAINT '.$quote($name).', ADD CONSTRAINT '.$quote('v2_fixture_capability_v4').' CHECK ('.$v4.')');
};
$snapshot=static function(mysqli$db)use($quote):string{
    $tables=array_column($db->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY BINARY TABLE_NAME')->fetch_all(MYSQLI_ASSOC),'TABLE_NAME');$state=[];
    foreach($tables as$table){$rows=$db->query('SELECT * FROM '.$quote($table))->fetch_all(MYSQLI_ASSOC);foreach($rows as&$row)ksort($row,SORT_STRING);unset($row);usort($rows,static fn(array$a,array$b):int=>json_encode($a,JSON_THROW_ON_ERROR)<=>json_encode($b,JSON_THROW_ON_ERROR));$state[$table]=['ddl'=>$db->query('SHOW CREATE TABLE '.$quote($table))->fetch_row()[1],'rows'=>$rows];}
    return json_encode($state,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
};
$bootstrapV2=static function(mysqli$db)use($prepareV4,$prefix):void{$prepareV4($db);$result=AssignmentOrderOriginalSchemaMigration::apply($db,$prefix);assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::APPLIED,$result->status(),'Fixture bootstrap applies canonical schema.');};
$assertV2=static function(mysqli$db,string$claim)use($soleContentIndex):void{assertSameValue([Contract::REVISION_CONTENT_INDEX,'1','private_content_identity'],array_values($soleContentIndex($db)),"{$claim}: exact named non-unique v2 index.");};

try{
    $probe=$newDb('probe');assertSameValue('1',(string)$probe->query('SELECT 1 ready')->fetch_assoc()['ready'],'Schema-v2 verifier has live isolated MariaDB.');$probe->close();

    $clean=$newDb('clean');$prepareV4($clean);$cleanResult=AssignmentOrderOriginalSchemaMigration::apply($clean,$prefix);
    assertSameValue([AssignmentOrderOriginalSchemaMigrationStatus::APPLIED,2,[...Contract::TABLES,'fm2_process_user_capabilities']],[$cleanResult->status(),$cleanResult->schemaVersion(),$cleanResult->affectedTables()],'Clean V4 applies schema v2 in manifest order with capability last.');$assertV2($clean,'Clean');$before=$snapshot($clean);$repeat=AssignmentOrderOriginalSchemaMigration::apply($clean,$prefix);assertSameValue([AssignmentOrderOriginalSchemaMigrationStatus::UNCHANGED,2,[]],[$repeat->status(),$repeat->schemaVersion(),$repeat->affectedTables()],'Exact populated-capable v2 repeat is unchanged.');assertSameValue($before,$snapshot($clean),'V2 repeat performs zero DDL/DML.');$clean->close();

    $partial=$newDb('partial');$bootstrapV2($partial);foreach(array_reverse(array_slice(Contract::TABLES,2))as$table)$partial->query('DROP TABLE '.$quote($prefix.$table));$toV1($partial);$toV4($partial);$partialBeforeRows=$partial->query('SELECT COUNT(*) n FROM '.$quote($prefix.Contract::TABLES[1]))->fetch_assoc();$partialResult=AssignmentOrderOriginalSchemaMigration::apply($partial,$prefix);assertSameValue([AssignmentOrderOriginalSchemaMigrationStatus::APPLIED,[...array_slice(Contract::TABLES,1),'fm2_process_user_capabilities']],[$partialResult->status(),$partialResult->affectedTables()],'Roots plus v1 revisions upgrades at revisions manifest position, creates suffix, publishes capability last.');assertSameValue($partialBeforeRows,$partial->query('SELECT COUNT(*) n FROM '.$quote($prefix.Contract::TABLES[1]))->fetch_assoc(),'Partial v1 upgrade rewrites no rows.');$assertV2($partial,'Partial recovery');$partial->close();

    $populated=$newDb('populated');$bootstrapV2($populated);$toV1($populated,'historical_content_unique');
    $populated->query("INSERT INTO `{$prefix}fm2_assignment_order_original_roots` VALUES ('root-v1',4512,81,'revision-v1-1','composition-81-v1','".str_repeat('1',64)."','2026-09-02 09:00:00.000000')");
    $populated->query("INSERT INTO `{$prefix}fm2_assignment_order_original_revisions` VALUES ('revision-v1-1','root-v1',1,NULL,'2026-09-01','2026-09-02 09:00:00.000000',18,'".str_repeat('2',64)."',327,'content-sha256-".str_repeat('2',64)."',NULL,'00000000-0000-4000-8000-000000000501','".str_repeat('3',64)."','assignment_order_original_accepted')");
    $rowsBefore=$populated->query("SELECT * FROM `{$prefix}fm2_assignment_order_original_revisions` ORDER BY revision_number")->fetch_all(MYSQLI_ASSOC);$result=AssignmentOrderOriginalSchemaMigration::apply($populated,$prefix);assertSameValue([AssignmentOrderOriginalSchemaMigrationStatus::APPLIED,2,[Contract::TABLES[1]]],[$result->status(),$result->schemaVersion(),$result->affectedTables()],'Full populated exact v1 upgrades only revisions.');assertSameValue($rowsBefore,$populated->query("SELECT * FROM `{$prefix}fm2_assignment_order_original_revisions` ORDER BY revision_number")->fetch_all(MYSQLI_ASSOC),'Populated v1 row remains byte-identical.');$assertV2($populated,'Populated upgrade');
    $populated->query("INSERT INTO `{$prefix}fm2_assignment_order_original_revisions` VALUES ('revision-v1-2','root-v1',2,'revision-v1-1','2026-09-02','2026-09-02 09:01:00.000000',18,'".str_repeat('2',64)."',327,'content-sha256-".str_repeat('2',64)."','Дата исправлена','00000000-0000-4000-8000-000000000502','".str_repeat('4',64)."','assignment_order_original_corrected')");assertSameValue(2,(int)$populated->query("SELECT COUNT(*) n FROM `{$prefix}fm2_assignment_order_original_revisions` WHERE private_content_identity='content-sha256-".str_repeat('2',64)."'")->fetch_assoc()['n'],'Two immutable revisions may reference the same exact content identity.');$populated->close();

    $observerDb=$newDb('observer');$bootstrapV2($observerDb);$toV1($observerDb);$phase=AssignmentOrderOriginalSchemaMigrationPhase::AFTER_SCHEMA_V2_REVISION_INDEX_ALTER;$observer=new AssignmentOrderOriginalSchemaMigrationObserverSpy($phase,Contract::TABLES[1]);$application=AssignmentOrderOriginalSchemaMigrationVerificationFactory::create($observer);try{$application->apply($observerDb,$prefix);throw new TestFailure('Post-index-ALTER observer fault must be unavailable.');}catch(AssignmentOrderOriginalSchemaMigrationUnavailable$error){assertSameValue(['AssignmentOrderOriginalSchemaMigrationUnavailable',0,null],[$error->getMessage(),$error->getCode(),$error->getPrevious()],'Post-index-ALTER observer fault is fixed unavailable.');}$assertV2($observerDb,'Durable observer-fault state');assertSameValue([[$phase,Contract::TABLES[1]]],$observer->calls,'Index observer is invoked once with revisions logical name.');$retry=AssignmentOrderOriginalSchemaMigration::apply($observerDb,$prefix);assertSameValue([AssignmentOrderOriginalSchemaMigrationStatus::UNCHANGED,[]],[$retry->status(),$retry->affectedTables()],'Retry recognizes durable v2 without a second ALTER.');$observerDb->close();

    $observerV1=$newDb('observerv1');$bootstrapV2($observerV1);$toV1($observerV1);$observer=new AssignmentOrderOriginalSchemaMigrationObserverSpy($phase,Contract::TABLES[1],static fn()=>$toV1($observerV1,'restored_v1'));$application=AssignmentOrderOriginalSchemaMigrationVerificationFactory::create($observer);try{$application->apply($observerV1,$prefix);throw new TestFailure('Observer-restored v1 must report unavailable.');}catch(AssignmentOrderOriginalSchemaMigrationUnavailable){}assertSameValue('0',(string)$soleContentIndex($observerV1)['NON_UNIQUE'],'Injected recovery fixture leaves exact v1 predecessor.');$retry=AssignmentOrderOriginalSchemaMigration::apply($observerV1,$prefix);assertSameValue([AssignmentOrderOriginalSchemaMigrationStatus::APPLIED,[Contract::TABLES[1]]],[$retry->status(),$retry->affectedTables()],'Retry safely upgrades exact v1 predecessor once.');$assertV2($observerV1,'V1 observer recovery');$observerV1->close();

    $drifts=[
        'wrongname'=>static function(mysqli$db)use($prefix,$soleContentIndex,$quote):void{$index=$soleContentIndex($db);$db->query('ALTER TABLE '.$quote($prefix.Contract::TABLES[1]).' DROP INDEX '.$quote($index['INDEX_NAME']).', ADD INDEX wrong_v2_name(private_content_identity)');},
        'unsafe'=>static function(mysqli$db)use($toV1,$prefix,$soleContentIndex,$quote):void{$toV1($db);$index=$soleContentIndex($db);$db->query('ALTER TABLE '.$quote($prefix.Contract::TABLES[1]).' DROP INDEX '.$quote($index['INDEX_NAME']).', ADD UNIQUE INDEX '.$quote('unsafe-name').'(private_content_identity)');},
        'multiple'=>static function(mysqli$db)use($toV1,$prefix):void{$toV1($db);$db->query("ALTER TABLE `{$prefix}fm2_assignment_order_original_revisions` ADD INDEX duplicate_content_index(private_content_identity)");},
        'wrongcols'=>static function(mysqli$db)use($prefix,$soleContentIndex,$quote):void{$index=$soleContentIndex($db);$db->query('ALTER TABLE '.$quote($prefix.Contract::TABLES[1]).' DROP INDEX '.$quote($index['INDEX_NAME']).', ADD INDEX '.$quote(Contract::REVISION_CONTENT_INDEX).'(private_content_identity,revision_id)');},
        'mixed'=>static function(mysqli$db)use($toV1,$prefix):void{$toV1($db);$db->query("ALTER TABLE `{$prefix}fm2_assignment_order_original_roots` ADD COLUMN drift INT NULL");},
    ];
    foreach($drifts as$axis=>$mutate){$db=$newDb($axis);$bootstrapV2($db);$mutate($db);$before=$snapshot($db);$result=AssignmentOrderOriginalSchemaMigration::apply($db,$prefix);$expected=$axis==='mixed'?[Contract::TABLES[0]]:[Contract::TABLES[1]];assertSameValue([AssignmentOrderOriginalSchemaMigrationStatus::CONFLICT,$expected],[$result->status(),$result->affectedTables()],"{$axis} index/mixed drift conflicts exactly.");assertSameValue($before,$snapshot($db),"{$axis} conflict performs zero DDL and zero row rewrite.");$db->close();}

    fwrite(STDOUT,"ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_V2_001_OK\n");
}finally{
    foreach(array_reverse($created)as$name){try{$admin->query('DROP DATABASE '.$quote($name));}catch(Throwable){}}
    $admin->close();
}
