<?php
declare(strict_types=1);require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__).'/Yii2/Yii2AuthFixture.php';
use FMonitor2\Tests\Yii2\Yii2AuthFixture;use FMonitor2\InstallationProcess\OtizPaymentDecisionObjectScopeSchemaMigration as Migration;
$f=new Yii2AuthFixture(dirname(__DIR__,2));$d=$f->db;$p=$f->prefix;$t=$p.'fm2_otiz_payment_decisions';
try{
 $d->query("INSERT INTO `{$p}fm2_otiz_calculation_revisions`(id,revision,status,report_date,actor_user_id,input_json,projection_json,created_at,updated_at)VALUES(77,1,'draft','2026-10-30',9101,'{}','{}','2026-10-30T09:00:00+03:00','2026-10-30T09:00:00+03:00')");
 $d->query("INSERT INTO `$t`(calculation_id,employee_id,decision,reason,created_at)VALUES(77,'101','do_not_pay','Legacy global','2026-10-01T09:00:00+03:00')");
 $d->query("ALTER TABLE `$t` DROP INDEX decision_scope,DROP COLUMN object_id");$before=$d->query("SELECT * FROM `$t`")->fetch_all(MYSQLI_ASSOC);
 assertSameValue(true,Migration::apply($d,$p)['applied'],'predecessor migrates');$after=$d->query("SELECT * FROM `$t`")->fetch_all(MYSQLI_ASSOC);foreach($after as&$r){assertSameValue(null,$r['object_id'],'legacy scope NULL');unset($r['object_id']);}unset($r);assertSameValue($before,$after,'legacy bytes preserved');assertSameValue(false,Migration::apply($d,$p)['applied'],'canonical repeat no-op');
 $ddl=static fn():string=>(string)$d->query("SHOW CREATE TABLE `$t`")->fetch_row()[1];
 $d->query("ALTER TABLE `$t` DROP INDEX decision_scope,MODIFY object_id VARCHAR(20) NULL");$before=$ddl();$failed=false;try{Migration::apply($d,$p);}catch(RuntimeException){$failed=true;}assertSameValue(true,$failed,'wrong existing column rejected');assertSameValue($before,$ddl(),'INTENDED_RED wrong column must not create index before rejecting');
 $d->query("ALTER TABLE `$t` DROP COLUMN object_id,ADD KEY decision_scope(employee_id)");$before=$ddl();$failed=false;try{Migration::apply($d,$p);}catch(RuntimeException){$failed=true;}assertSameValue(true,$failed,'wrong existing index rejected');assertSameValue($before,$ddl(),'missing column plus wrong named index must not add column before rejection');
 $d->query("ALTER TABLE `$t` DROP INDEX decision_scope");Migration::apply($d,$p);$result=FMonitor2\InstallationProcess\CanonicalMigrationApplication::run($d,$p,FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue::migrations());assertSameValue(0,$result['exitCode'],'entire canonical chain remains repeatable with upgraded decision shape');
 echo "PASS: scoped decision migration preserves predecessor and rejects incompatible partial shapes without DDL\n";
}finally{$f->close();}
