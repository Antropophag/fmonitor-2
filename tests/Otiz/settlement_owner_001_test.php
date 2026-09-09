<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/vendor/yiisoft/yii2/Yii.php';

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;
use FMonitor2\Otiz\OtizSettlement;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);
$user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$password=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';
$admin=new mysqli($host,$user,$password,'',$port);$database='t_otiz_settlement_'.bin2hex(random_bytes(5));$db=null;
try {
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db=new mysqli($host,$user,$password,$database,$port);$prefix='settle_';
    $migration=CanonicalMigrationApplication::run($db,$prefix,ProductionPilotMigrationCatalogue::migrations());
    assertSameValue([0,true,24],[$migration['exitCode'],$migration['result']['ok']??null,$migration['result']['schemaVersion']??null],'SETUP_FAILURE: isolated canonical DB reaches v24');
    $db->query('CREATE TABLE settlement_ambient(id INT NOT NULL PRIMARY KEY,marker VARCHAR(30) NOT NULL) ENGINE=InnoDB');
    $db->query("INSERT INTO settlement_ambient VALUES(1,'preserve')");
    assertSameValue(true,class_exists(OtizSettlement::class),'INTENTIONAL_RED: OTIZ-SETTLEMENT-001 public application owner is absent after valid isolated DB setup');
    $yii=new yii\db\Connection(['dsn'=>"mysql:host={$host};port={$port};dbname={$database}",'username'=>$user,'password'=>$password,'charset'=>'utf8mb4']);$yii->open();
    foreach(['recordDiscipline','completeSnapshotPayments','reverse']as$method)assertSameValue(true,method_exists(OtizSettlement::class,$method),"public operation {$method}");
    $db->query("INSERT INTO {$prefix}fm2_pilot_users(user_id,full_name,email,status,activation_state,source_updated_at)VALUES(501,'ОТиЗ','otiz501@example.test',1,'active','2026-09-09T10:00:00+03:00')");
    $db->query("INSERT INTO {$prefix}fm2_pilot_roles(role_id,code,name,description,status,source_updated_at)VALUES(51,'otiz','ОТиЗ','Settlement test',1,'2026-09-09T10:00:00+03:00')");
    $db->query("INSERT INTO {$prefix}fm2_pilot_role_permissions VALUES(51,'otiz.manage')");
    $db->query("INSERT INTO {$prefix}fm2_pilot_user_roles(user_id,role_id,origin,assigned_at)VALUES(501,51,'test','2026-09-09T10:00:00+03:00')");
    $hash=str_repeat('a',64);
    $db->query("INSERT INTO {$prefix}fm2_pilot_otiz_snapshots(id,report_date,status,rules_version,calculated_at,calculated_by_user_id,accepted_at,accepted_by_user_id,total_pool_cents,total_closed_cents,total_available_cents,content_hash)VALUES(101,'2026-08-31','accepted','premium-calculation-v1','2026-09-01T09:00:00+03:00',501,'2026-09-01T10:00:00+03:00',501,100000,0,100000,'{$hash}'),(102,'2026-09-30','accepted','premium-calculation-v1','2026-10-01T09:00:00+03:00',501,'2026-10-01T10:00:00+03:00',501,150000,0,150000,'{$hash}')");
    $object="(snapshot_id,object_id,regnumber,address,previous_progress_bp,current_progress_bp,progress_fact_date,premium_cents,shaft_bp,kss_bp,accrued_cents,fund_cents,closed_before_cents,remaining_cents,pool_cents,distributed_cents,undistributed_cents,calculation_state,inputs_json)VALUES";
    $db->query("INSERT INTO {$prefix}fm2_pilot_otiz_snapshot_objects {$object}(101,7001,'A02','Synthetic',0,8500,'2026-08-31',100000,10000,10000,100000,100000,0,100000,100000,100000,0,'ready','{}'),(102,7001,'A02','Synthetic',8500,10000,'2026-09-30',150000,10000,10000,150000,150000,0,150000,150000,150000,0,'ready','{}')");
    $acceptedBefore=$db->query("SELECT * FROM {$prefix}fm2_pilot_otiz_snapshots ORDER BY id")->fetch_all(MYSQLI_ASSOC);$objectsBefore=$db->query("SELECT * FROM {$prefix}fm2_pilot_otiz_snapshot_objects ORDER BY snapshot_id,object_id")->fetch_all(MYSQLI_ASSOC);
    $service=new OtizSettlement($yii,$prefix,static fn():string=>'2026-10-02T12:00:00+03:00');
    $op1='00000000-0000-4000-8000-000000000101';$op2='00000000-0000-4000-8000-000000000102';
    $first=$service->completeSnapshotPayments(501,101,$op1);$second=$service->completeSnapshotPayments(501,102,$op2);
    assertSameValue(['completed',1,100000],[$first['status'],$first['objectCount'],$first['paidCents']],'A02 S1 closes literal100000');
    assertSameValue(['completed',1,50000],[$second['status'],$second['objectCount'],$second['paidCents']],'A02 S2 closes only literal increment50000');
    $closures=$db->query("SELECT snapshot_id,object_id,paid_cents,discipline_cents,deadline_cents,basis,reverses_payment_closure_id FROM {$prefix}fm2_pilot_otiz_payment_closures ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    assertSameValue([[101,7001,100000,0,0],[102,7001,50000,0,0]],array_map(static fn(array$r):array=>[(int)$r['snapshot_id'],(int)$r['object_id'],(int)$r['paid_cents'],(int)$r['discipline_cents'],(int)$r['deadline_cents']],$closures),'global closures equal100000+50000');
    $eventCount=(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_events")->fetch_assoc()['n'];
    assertSameValue($second,$service->completeSnapshotPayments(501,102,$op2),'exact replay returns saved result');
    assertSameValue([2,$eventCount],[(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_payment_closures")->fetch_assoc()['n'],(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_events")->fetch_assoc()['n']],'replay adds no money/event facts');
    $db->query("DELETE FROM {$prefix}fm2_pilot_role_permissions WHERE role_id=51 AND permission='otiz.manage'");$revokedBefore=[(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_payment_closures")->fetch_assoc()['n'],(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_events")->fetch_assoc()['n'],(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_otiz_settlement_operations")->fetch_assoc()['n']];try{$service->completeSnapshotPayments(501,102,$op2);throw new TestFailure('revoked actor replayed prior success');}catch(DomainException$e){assertSameValue('FORBIDDEN',$e->getMessage(),'INTENDED_RED: exact replay rechecks current authority before returning saved success');}assertSameValue($revokedBefore,[(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_payment_closures")->fetch_assoc()['n'],(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_events")->fetch_assoc()['n'],(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_otiz_settlement_operations")->fetch_assoc()['n']],'revoked exact replay appends no closure, event or receipt');$db->query("INSERT INTO {$prefix}fm2_pilot_role_permissions VALUES(51,'otiz.manage')");
    $noopOp='00000000-0000-4000-8000-000000000105';$beforeNoop=[(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_payment_closures")->fetch_assoc()['n'],(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_events")->fetch_assoc()['n']];
    $noop=$service->completeSnapshotPayments(501,102,$noopOp);assertSameValue(['no_change',0,0],[$noop['status'],$noop['objectCount'],$noop['paidCents']],'exhausted accepted snapshot is successful no-op');
    assertSameValue($noop,$service->completeSnapshotPayments(501,102,$noopOp),'successful no-op has stable replay result');
    assertSameValue($beforeNoop,[(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_payment_closures")->fetch_assoc()['n'],(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_events")->fetch_assoc()['n']],'successful no-op and replay add no money/event facts');
    $firstClosure=(int)$db->query("SELECT id FROM {$prefix}fm2_pilot_otiz_payment_closures WHERE snapshot_id=101 AND object_id=7001")->fetch_assoc()['id'];
    $service->reverse(501,$firstClosure,'Отмена первой выплаты','00000000-0000-4000-8000-000000000106');
    $afterRelease=(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_payment_closures")->fetch_assoc()['n'];
    assertSameValue($noop,$service->completeSnapshotPayments(501,102,$noopOp),'stored no-change replay remains no-change after reversal releases budget');
    assertSameValue($afterRelease,(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_payment_closures")->fetch_assoc()['n'],'same no-change UUID cannot consume newly released budget');
    $freshAfterRelease=$service->completeSnapshotPayments(501,102,'00000000-0000-4000-8000-000000000107');
    assertSameValue(['completed',1,100000],[$freshAfterRelease['status'],$freshAfterRelease['objectCount'],$freshAfterRelease['paidCents']],'fresh UUID consumes exactly released100000');
    $beforeDenied=[(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_payment_closures")->fetch_assoc()['n'],(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_events")->fetch_assoc()['n']];
    try{$service->recordDiscipline(999,102,7001,1,'Denied','', '00000000-0000-4000-8000-000000000108');throw new TestFailure('unauthorized settlement accepted');}catch(DomainException$e){assertSameValue('FORBIDDEN',$e->getMessage(),'current database authority is required');}
    assertSameValue($beforeDenied,[(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_payment_closures")->fetch_assoc()['n'],(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_events")->fetch_assoc()['n']],'denial adds no money/event facts');
    try{$service->completeSnapshotPayments(501,101,$op2);throw new TestFailure('operation fingerprint conflict accepted');}catch(DomainException$e){assertSameValue('OPERATION_CONFLICT',$e->getMessage(),'same operation UUID cannot address another snapshot');}
    assertSameValue($beforeDenied,[(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_payment_closures")->fetch_assoc()['n'],(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_events")->fetch_assoc()['n']],'fingerprint conflict adds no money/event facts');
    assertSameValue($acceptedBefore,$db->query("SELECT * FROM {$prefix}fm2_pilot_otiz_snapshots ORDER BY id")->fetch_all(MYSQLI_ASSOC),'settlement never rewrites accepted snapshots');
    assertSameValue($objectsBefore,$db->query("SELECT * FROM {$prefix}fm2_pilot_otiz_snapshot_objects ORDER BY snapshot_id,object_id")->fetch_all(MYSQLI_ASSOC),'settlement never rewrites accepted objects');
    $beforeReject=[$db->query("SELECT * FROM {$prefix}fm2_pilot_otiz_payment_closures ORDER BY id")->fetch_all(MYSQLI_ASSOC),(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_events")->fetch_assoc()['n']];
    try{$service->recordDiscipline(501,102,7001,1,'Over budget','',$op='00000000-0000-4000-8000-000000000103');throw new TestFailure('over-budget discipline accepted');}catch(DomainException$e){assertSameValue('AMOUNT_UNAVAILABLE',$e->getMessage(),'over-budget discipline stable rejection');}
    assertSameValue($beforeReject,[$db->query("SELECT * FROM {$prefix}fm2_pilot_otiz_payment_closures ORDER BY id")->fetch_all(MYSQLI_ASSOC),(int)$db->query("SELECT COUNT(*) n FROM {$prefix}fm2_pilot_otiz_events")->fetch_assoc()['n']],'discipline rejection adds no facts');
    $db->query("INSERT INTO {$prefix}fm2_pilot_otiz_snapshots(id,report_date,status,rules_version,calculated_at,calculated_by_user_id,accepted_at,accepted_by_user_id,total_pool_cents,total_closed_cents,total_available_cents,content_hash)VALUES(103,'2026-10-01','accepted','premium-calculation-v1','2026-10-02T09:00:00+03:00',501,'2026-10-02T10:00:00+03:00',501,50000,100000,50000,'{$hash}')");
    $db->query("INSERT INTO {$prefix}fm2_pilot_otiz_snapshot_objects {$object}(103,7003,'AFTER','Built after closure',8500,10000,'2026-10-01',150000,10000,10000,150000,150000,100000,50000,50000,50000,0,'ready','{}')");
    $db->query("INSERT INTO {$prefix}fm2_pilot_otiz_payment_closures(snapshot_id,object_id,closed_on,paid_cents,discipline_cents,deadline_cents,basis,artifact,created_by_user_id,created_at)VALUES(101,7003,'2026-10-01',100000,0,0,'Prior accepted closure','',501,'2026-10-01T12:00:00+03:00')");
    $builtAfter=$service->completeSnapshotPayments(501,103,'00000000-0000-4000-8000-000000000109');
    assertSameValue(['completed',1,50000],[$builtAfter['status'],$builtAfter['objectCount'],$builtAfter['paidCents']],'snapshot built after prior closure does not subtract closed_before twice');
    $db->query("INSERT INTO {$prefix}fm2_pilot_otiz_snapshot_objects {$object}(102,7002,'REV','Synthetic',0,10000,'2026-09-30',100000,10000,10000,100000,100000,0,100000,100000,100000,0,'ready','{}')");
    $db->query("INSERT INTO {$prefix}fm2_pilot_otiz_payment_closures(snapshot_id,object_id,closed_on,paid_cents,discipline_cents,deadline_cents,basis,artifact,created_by_user_id,created_at)VALUES(102,7002,'2026-10-01',70000,20000,10000,'Historical mixed closure','artifact-7',501,'2026-10-01T12:00:00+03:00')");$original=(int)$db->insert_id;
    $reversed=$service->reverse(501,$original,'Исправление mixed closure','00000000-0000-4000-8000-000000000104');
    $reversal=$db->query("SELECT paid_cents,discipline_cents,deadline_cents,basis,artifact,reverses_payment_closure_id FROM {$prefix}fm2_pilot_otiz_payment_closures WHERE id=".(int)$reversed['closureId'])->fetch_assoc();
    assertSameValue(['reversed',-70000,-20000,-10000,'Исправление mixed closure','',$original],[$reversed['status'],(int)$reversal['paid_cents'],(int)$reversal['discipline_cents'],(int)$reversal['deadline_cents'],$reversal['basis'],$reversal['artifact'],(int)$reversal['reverses_payment_closure_id']],'reversal copies every historical component exactly and links original');
    $unchanged=$db->query("SELECT paid_cents,discipline_cents,deadline_cents,basis,artifact FROM {$prefix}fm2_pilot_otiz_payment_closures WHERE id={$original}")->fetch_assoc();
    assertSameValue([70000,20000,10000,'Historical mixed closure','artifact-7'],[(int)$unchanged['paid_cents'],(int)$unchanged['discipline_cents'],(int)$unchanged['deadline_cents'],$unchanged['basis'],$unchanged['artifact']],'reversal does not rewrite original');
    $yii->close();
    echo "PASS: OTIZ-SETTLEMENT-001 A02 settlement, replay, denial and reversal\n";
} finally {
    if($db instanceof mysqli)$db->close();
    $admin->query("DROP DATABASE IF EXISTS `{$database}`");$admin->close();
}
