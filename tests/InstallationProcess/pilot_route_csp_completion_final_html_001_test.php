<?php
declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/PilotHttp/PilotHttp.php';
require_once dirname(__DIR__,2).'/app/PilotHttp/PilotView.php';
require_once dirname(__DIR__,2).'/app/PilotHttp/ChecklistView.php';
require_once dirname(__DIR__,2).'/rapid-pilot/CompletionFlow.php';

use FMonitor2\PilotHttp\HttpUser;
use FMonitor2\PilotHttp\ProductionChecklistRenderer;

// Specification: PILOT-ROUTE-CSP-001 A8, A10 and A11.
// Final public representation seam: real checklist renderer followed by the
// deployed CompletionFlow HTML enhancer, with isolated persistence fixtures.
function prcfSafe(array &$failures,string $scenario,string $html,string $cap):void{
    if(!str_contains($html,'data-progress-cap="'.$cap.'"'))$failures[]="$scenario missing data-progress-cap=$cap";
    if(!str_contains($html,'src="/pilot/assets/checklist.js?'))$failures[]="$scenario missing external checklist asset";
    if(preg_match('#<script(?![^>]+\bsrc="/pilot/)[^>]*>#i',$html)===1)$failures[]="$scenario has inline/non-local script";
    if(preg_match('/\son[a-z]+\s*=/i',$html)===1)$failures[]="$scenario has inline event attribute";
    if(preg_match('/(?:href|src)\s*=\s*["\']\s*javascript:/i',$html)===1)$failures[]="$scenario has javascript URL";
    if(preg_match('/\b(?:eval|Function)\s*\(/',$html)===1)$failures[]="$scenario has executable string evaluation";
    if(preg_match('#<script[^>]+src=["\'](?:https?:)?//#i',$html)===1)$failures[]="$scenario has third-party script";
}
function prcfSnapshot(mysqli $db,string $prefix,array $tables):string{$snapshot=[];foreach($tables as$table){$full=$prefix.$table;$create=$db->query("SHOW CREATE TABLE `{$full}`")->fetch_assoc();$rows=$db->query("SELECT * FROM `{$full}` ORDER BY id")->fetch_all(MYSQLI_ASSOC);$snapshot[$table]=['schema'=>$create['Create Table']??null,'rows'=>$rows];}return hash('sha256',json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));}
$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);$adminUser=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$adminPassword=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';$database='t_prcf_'.bin2hex(random_bytes(5));$admin=new mysqli($host,$adminUser,$adminPassword,'',$port);$admin->query("CREATE DATABASE `$database` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");$db=new mysqli($host,$adminUser,$adminPassword,$database,$port);$db->set_charset('utf8mb4');$prefix='prcf_';putenv('FMONITOR_DB_HOST='.$host);putenv('FMONITOR_DB_PORT='.(string)$port);putenv('FMONITOR_DB_NAME='.$database);putenv('FMONITOR_DB_USER='.$adminUser);putenv('FMONITOR_DB_PASSWORD='.$adminPassword);putenv('FMONITOR_PROCESS_TABLE_PREFIX='.$prefix);$tables=['fm2_process_events','fm2_pilot_completion_fact_corrections','fm2_pilot_completion_facts','fm2_installation_cases'];
try{
 $db->query("CREATE TABLE `{$prefix}fm2_installation_cases`(id BIGINT UNSIGNED PRIMARY KEY,legacy_installation_object_id BIGINT UNSIGNED NOT NULL UNIQUE,process_state VARCHAR(32) NOT NULL)");$db->query("INSERT INTO `{$prefix}fm2_installation_cases` VALUES(1,4512,'working')");
 $migration=\FMonitor2\InstallationProcess\InstallationCompletionSchemaMigration::apply($db,$prefix);assertSameValue(true,$migration['applied']??false,'canonical v10 completion family established before rendering');
 $db->query("CREATE TABLE `{$prefix}fm2_process_events`(id BIGINT UNSIGNED PRIMARY KEY,installation_case_id BIGINT UNSIGNED NOT NULL,event_type VARCHAR(80) NOT NULL,occurred_at VARCHAR(40) NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,payload_json LONGTEXT NOT NULL)");$db->query("INSERT INTO `{$prefix}fm2_process_events` VALUES(1,1,'installation_opened','2026-08-20T10:00:00+03:00',17,'{}')");
 $renderer=new ProductionChecklistRenderer();$user=new HttpUser(17,'Тестовый Пользователь','fixture@example.test');$case=['id'=>4512,'address'=>'г. Тестоград, ул. Примерная, д. 1','entrance'=>'2','registrationNumber'=>'TEST-LIFT-4512','opened'=>true,'controlEngineer'=>['userId'=>17]];$base=$renderer->render($user,$case,true);$failures=[];
 $before=prcfSnapshot($db,$prefix,$tables);$incomplete=RapidPilotCompletionFlow::enhanceChecklist($base,4512);$after=prcfSnapshot($db,$prefix,$tables);if(!hash_equals($before,$after))$failures[]='A11 incomplete safe render changed schema, domain rows or audit history';prcfSafe($failures,'A10 incomplete final HTML',$incomplete,'85');
 $db->query("INSERT INTO `{$prefix}fm2_pilot_completion_facts`(installation_case_id,fact_type,fact_date,details,recorded_at,recorded_by_user_id)VALUES(1,'pto_act','2026-08-30','','2026-08-30T12:00:00+03:00',17),(1,'declaration','2026-08-31','TEST-DECLARATION','2026-08-31T12:00:00+03:00',17)");$factsBefore=prcfSnapshot($db,$prefix,$tables);$complete=RapidPilotCompletionFlow::enhanceChecklist($base,4512);$factsAfter=prcfSnapshot($db,$prefix,$tables);if(!hash_equals($factsBefore,$factsAfter))$failures[]='A11 complete safe render changed schema, domain rows or audit history';prcfSafe($failures,'A10 complete final HTML',$complete,'100');
 if($failures!==[])throw new TestFailure("PILOT-ROUTE-CSP-001 final HTML intended RED:\n- ".implode("\n- ",$failures));echo"pilot_route_csp_completion_final_html_001_test: PASS\n";
}finally{$db->close();$admin->query("DROP DATABASE IF EXISTS `$database`");$admin->close();}
