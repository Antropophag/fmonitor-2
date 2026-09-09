<?php
declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\OtizSettlementSchemaMigration;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;

// OTIZ-SETTLEMENT-001 canonical additive schema lifecycle. Disposable DB only.
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);$user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$password=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';
$admin=new mysqli($host,$user,$password,'',$port);$database='t_otiz_settlement_schema_'.bin2hex(random_bytes(5));$db=null;$prefix='oss_';
try{
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");$db=new mysqli($host,$user,$password,$database,$port);
    $catalogue=ProductionPilotMigrationCatalogue::migrations();
    assertSameValue(OtizSettlementSchemaMigration::class,$catalogue[24]??null,'INTENDED_RED: OTIZ settlement schema is canonical successor v24');
    $first=CanonicalMigrationApplication::run($db,$prefix,$catalogue);assertSameValue([0,true,24,range(1,24)],[$first['exitCode'],$first['result']['ok']??null,$first['result']['schemaVersion']??null,$first['result']['appliedVersions']??null],'clean canonical run reaches v24');
    $db->query("INSERT INTO `{$prefix}fm2_otiz_settlement_locks` VALUES(7001)");$db->query("INSERT INTO `{$prefix}fm2_otiz_settlement_operations` VALUES(501,'00000000-0000-4000-8000-000000000501',REPEAT('a',64),'no_change','{}','2026-09-09T12:00:00+03:00')");
    $before=[$db->query("SHOW CREATE TABLE `{$prefix}fm2_otiz_settlement_locks`")->fetch_row()[1],$db->query("SHOW CREATE TABLE `{$prefix}fm2_otiz_settlement_operations`")->fetch_row()[1],$db->query("SELECT * FROM `{$prefix}fm2_otiz_settlement_operations`")->fetch_all(MYSQLI_ASSOC)];
    $repeat=CanonicalMigrationApplication::run($db,$prefix,$catalogue);assertSameValue([0,['ok'=>true,'schemaVersion'=>24,'appliedVersions'=>[]]],[$repeat['exitCode'],$repeat['result']],'compatible populated repeat is no-op');
    assertSameValue($before,[$db->query("SHOW CREATE TABLE `{$prefix}fm2_otiz_settlement_locks`")->fetch_row()[1],$db->query("SHOW CREATE TABLE `{$prefix}fm2_otiz_settlement_operations`")->fetch_row()[1],$db->query("SELECT * FROM `{$prefix}fm2_otiz_settlement_operations`")->fetch_all(MYSQLI_ASSOC)],'repeat preserves exact schema and append-only receipt');
    $db->query("ALTER TABLE `{$prefix}fm2_otiz_settlement_operations` MODIFY status VARCHAR(39) NOT NULL");$conflict=CanonicalMigrationApplication::run($db,$prefix,$catalogue);assertSameValue([2,['ok'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT','schemaVersion'=>24]],[$conflict['exitCode'],$conflict['result']],'incompatible existing table fails closed at v24');
    echo "PASS: OTIZ-SETTLEMENT-001 canonical v24 clean repeat and conflict\n";
}finally{if($db instanceof mysqli)$db->close();$admin->query("DROP DATABASE IF EXISTS `{$database}`");$admin->close();}
