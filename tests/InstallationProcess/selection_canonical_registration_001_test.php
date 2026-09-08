<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Support/SelectionSchemaWorkerControl.php';
use FMonitor2\InstallationProcess as I;

// SELECTION-CANONICAL-REGISTRATION-001: actual CLI, inherited engines, literal frontier18.
function selectionCanonicalDb(?string $name=null):mysqli {
    $db=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',$name,(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));$db->set_charset('utf8mb4');return $db;
}
function selectionCanonicalPredecessor(mysqli $db,string $prefix):void {
    $catalog=[1=>I\ProductionProcessSchemaMigration::class,2=>I\WorkforceCatalogSchemaMigration::class,3=>I\ProcessUserCapabilitiesSchemaMigration::class,4=>I\ProcessCommandCapabilitiesSchemaMigration::class,5=>I\BitrixWorkforceHistorySchemaMigration::class,6=>I\IdentityAccessSchemaMigration::class,7=>I\ChecklistTemplateSchemaMigration::class,8=>I\InspectionEvidenceSchemaMigration::class,9=>I\InspectionPlanningSchemaMigration::class,10=>I\InstallationCompletionSchemaMigration::class,11=>static fn($db,$p)=>I\ClassificationProvenanceSchemaMigration::apply($db,$p,static function(){}),12=>I\ObjectDetailSnapshotSchemaMigration::class,13=>I\OriginalAttemptAuditSchemaMigration::class];
    $r=I\CanonicalMigrationApplication::run($db,$prefix,$catalog);assertSameValue([0,13],[$r['exitCode'],$r['result']['schemaVersion']??null],'real approved v13 prerequisite');
}
function selectionCanonicalRun(string $name,string $prefix):array {
    $env=['FMONITOR_DB_HOST'=>getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1','FMONITOR_DB_PORT'=>getenv('FMONITOR_TEST_DB_PORT')?:'23306','FMONITOR_DB_NAME'=>$name,'FMONITOR_DB_USER'=>getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root','FMONITOR_DB_PASSWORD'=>getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local','FMONITOR_PROCESS_TABLE_PREFIX'=>$prefix];
    $command=['/usr/bin/env','-i'];foreach($env as $key=>$value)$command[]=$key.'='.$value;$command[] = PHP_BINARY;$command[]=dirname(__DIR__,2).'/bin/fmonitor2-migrate.php';
    $process=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));if(!is_resource($process))throw new TestFailure('CLI start');
    stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$w=['process'=>$process,'pipes'=>$pipes,'stdout'=>'','stderr'=>'','started'=>hrtime(true),'status'=>null];
    try{$r=aossReapWorker($w,40);}finally{aossCleanupWorker($w);}
    assertSameValue('', $r['stderr'],'CLI stderr empty');assertSameValue(1,substr_count($r['stdout'],"\n"),'one JSON line');return [$r['exit'],json_decode($r['stdout'],true,512,JSON_THROW_ON_ERROR)];
}
function selectionCanonicalRows(mysqli $db):array {
    $rows=[];foreach($db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM) as [$table]){$v=$db->query("SELECT * FROM `$table`")->fetch_all(MYSQLI_ASSOC);usort($v,fn($a,$b)=>strcmp(json_encode($a),json_encode($b)));$rows[$table]=$v;}ksort($rows);return $rows;
}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$admin=selectionCanonicalDb();$failed=0;
foreach(['fresh','upgrade','registry_only','registry_conflict','selection_conflict','prefix25'] as $axis){$db=null;$created=false;$name='t_sel_canonical_'.bin2hex(random_bytes(6));$prefix=$axis==='prefix25'?str_repeat('p',25):'';$errors=[];
    try {
        $admin->query("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$created=true;$db=selectionCanonicalDb($name);
        if($axis!=='fresh')selectionCanonicalPredecessor($db,$prefix);
        if(in_array($axis,['registry_only','selection_conflict'],true))assertSameValue(['applied'=>true],I\AssignmentOrderIdentityRegistryMigration::apply($db,$prefix),'approved registry prerequisite');
        if($axis==='registry_conflict')$db->query('CREATE TABLE fm2_assignment_order_identities(id INT) ENGINE=InnoDB');
        if($axis==='selection_conflict')$db->query('CREATE TABLE fm2_assignment_order_selection_audits(id INT) ENGINE=InnoDB');
        $before=selectionCanonicalRows($db);echo "SETUP_OK $axis\n";$result=selectionCanonicalRun($name,$prefix);
        if(str_ends_with($axis,'conflict')){
            $version=$axis==='registry_conflict'?14:15;assertSameValue([2,['ok'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT','schemaVersion'=>$version]],$result,'first failing engine controls report');assertSameValue($before,selectionCanonicalRows($db),'conflict creates no later family or facts');
        }else{
            $versions=$axis==='fresh'?range(1,21):($axis==='registry_only'?[15,16,17,18,19,20,21]:[14,15,16,17,18,19,20,21]);assertSameValue([0,['ok'=>true,'schemaVersion' => 21,'appliedVersions'=>$versions]],$result,'RED_ASSERTION: canonical runner must register both engines');
            assertSameValue(true,I\AssignmentOrderIdentityRegistryMigration::isBackfillComplete($db,$prefix),'registry readiness');assertSameValue(true,I\AssignmentOrderSelectionSchemaMigration::isReady($db,$prefix),'selection readiness');
            $after=selectionCanonicalRows($db);foreach($before as $table=>$rows)assertSameValue($rows,$after[$table],'predecessor facts preserved');
            foreach(['fm2_assignment_order_identities','fm2_assignment_order_selections','fm2_assignment_order_selection_members','fm2_assignment_order_selection_requests','fm2_assignment_order_selection_events','fm2_assignment_order_selection_audits'] as $table)assertSameValue([],$after[$prefix.$table],'no domain facts minted');
            assertSameValue([0,['ok'=>true,'schemaVersion' => 21,'appliedVersions'=>[]]],selectionCanonicalRun($name,$prefix),'complete repeat noop');assertSameValue($after,selectionCanonicalRows($db),'repeat immutable rows/receipt');
        }
    }catch(Throwable $e){$errors[]=$e->getMessage();}
    if($db!==null)try{$db->close();}catch(Throwable $e){$errors[]='connection cleanup: '.$e->getMessage();}
    if($created)try{$admin->query("DROP DATABASE `$name`");echo "CLEANUP_OK $axis\n";}catch(Throwable $e){$errors[]='db cleanup: '.$e->getMessage();}
    if($errors!==[]){$failed++;echo "FAIL $axis: ".implode(' | ',$errors)."\n";}else echo "PASS $axis\n";
}$admin->close();exit($failed===0?0:1);
