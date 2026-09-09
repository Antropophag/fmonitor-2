<?php
declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\OtizSettlementSchemaMigration;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;

// OTIZ-SETTLEMENT-001 canonical additive schema lifecycle. Disposable DB only.
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);$user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$password=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';
$admin=new mysqli($host,$user,$password,'',$port);$token=bin2hex(random_bytes(5));$database='t_otiz_settlement_schema_'.$token;$predecessor='t_otiz_settlement_predecessor_'.$token;$db=null;$prefix='oss_';
try{
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");$db=new mysqli($host,$user,$password,$database,$port);
    $catalogue=ProductionPilotMigrationCatalogue::migrations();
    assertSameValue(OtizSettlementSchemaMigration::class,$catalogue[24]??null,'INTENDED_RED: OTIZ settlement schema is canonical successor v24');
    $admin->query("CREATE DATABASE `{$predecessor}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");$pred=new mysqli($host,$user,$password,$predecessor,$port);$through23=array_slice($catalogue,0,23,true);$v23=CanonicalMigrationApplication::run($pred,$prefix,$through23);assertSameValue([0,true,23],[$v23['exitCode'],$v23['result']['ok']??null,$v23['result']['schemaVersion']??null],'isolated predecessor reaches exact v23');$pred->query("INSERT INTO `{$prefix}fm2_pilot_otiz_payment_closures`(snapshot_id,object_id,closed_on,paid_cents,discipline_cents,deadline_cents,basis,artifact,created_by_user_id,created_at) VALUES(1,7001,'2026-09-09',100000,0,0,'predecessor closure','',501,'2026-09-09T10:00:00+03:00')");$closureBefore=$pred->query("SELECT * FROM `{$prefix}fm2_pilot_otiz_payment_closures`")->fetch_all(MYSQLI_ASSOC);$upgrade=CanonicalMigrationApplication::run($pred,$prefix,$catalogue);assertSameValue([0,['ok'=>true,'schemaVersion'=>24,'appliedVersions'=>[24]]],[$upgrade['exitCode'],$upgrade['result']],'populated v23 applies only additive settlement successor');assertSameValue($closureBefore,$pred->query("SELECT * FROM `{$prefix}fm2_pilot_otiz_payment_closures`")->fetch_all(MYSQLI_ASSOC),'v23 upgrade preserves exact financial history');$pred->close();
    $first=CanonicalMigrationApplication::run($db,$prefix,$catalogue);assertSameValue([0,true,24,range(1,24)],[$first['exitCode'],$first['result']['ok']??null,$first['result']['schemaVersion']??null,$first['result']['appliedVersions']??null],'clean canonical run reaches v24');
    $db->query("INSERT INTO `{$prefix}fm2_otiz_settlement_locks` VALUES(7001)");$db->query("INSERT INTO `{$prefix}fm2_otiz_settlement_operations` VALUES(501,'00000000-0000-4000-8000-000000000501',REPEAT('a',64),'no_change','{}','2026-09-09T12:00:00+03:00')");
    $before=[$db->query("SHOW CREATE TABLE `{$prefix}fm2_otiz_settlement_locks`")->fetch_row()[1],$db->query("SHOW CREATE TABLE `{$prefix}fm2_otiz_settlement_operations`")->fetch_row()[1],$db->query("SELECT * FROM `{$prefix}fm2_otiz_settlement_operations`")->fetch_all(MYSQLI_ASSOC)];
    $repeat=CanonicalMigrationApplication::run($db,$prefix,$catalogue);assertSameValue([0,['ok'=>true,'schemaVersion'=>24,'appliedVersions'=>[]]],[$repeat['exitCode'],$repeat['result']],'compatible populated repeat is no-op');
    assertSameValue($before,[$db->query("SHOW CREATE TABLE `{$prefix}fm2_otiz_settlement_locks`")->fetch_row()[1],$db->query("SHOW CREATE TABLE `{$prefix}fm2_otiz_settlement_operations`")->fetch_row()[1],$db->query("SELECT * FROM `{$prefix}fm2_otiz_settlement_operations`")->fetch_all(MYSQLI_ASSOC)],'repeat preserves exact schema and append-only receipt');
    $db->query("ALTER TABLE `{$prefix}fm2_otiz_settlement_operations` MODIFY status VARCHAR(39) NOT NULL");$conflict=CanonicalMigrationApplication::run($db,$prefix,$catalogue);assertSameValue([2,['ok'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT','schemaVersion'=>24]],[$conflict['exitCode'],$conflict['result']],'incompatible existing table fails closed at v24');
    echo "PASS: OTIZ-SETTLEMENT-001 canonical v24 clean repeat and conflict\n";
}finally{if($db instanceof mysqli)$db->close();$admin->query("DROP DATABASE IF EXISTS `{$database}`");$admin->query("DROP DATABASE IF EXISTS `{$predecessor}`");$admin->close();}
