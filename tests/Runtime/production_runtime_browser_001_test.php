<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/autoload.php';
require_once dirname(__DIR__, 2) . '/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require_once dirname(__DIR__) . '/Support/SelectionSchemaTestDatabase.php';
require_once dirname(__DIR__) . '/Support/SelectionNativeFixture.php';
require_once dirname(__DIR__) . '/Support/SelectedOriginalFixture.php';

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\InspectionPhotoContentIndexSchemaMigration;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;
use FMonitor2\PilotHttp\ProductionChecklistRenderer;
use FMonitor2\Tests\Support\SelectedOriginalFixture;

$repo = dirname(__DIR__, 2);
$composeFile = $repo . '/deploy/runtime/compose.yaml';
assertSameValue(true, is_file($composeFile), 'production Compose definition exists');
$token = bin2hex(random_bytes(6));
$project = 'fm2browser' . $token;
$runtimeImage = 'fmonitor2-runtime:' . $project;
$httpPort = random_int(20000, 30000);
$dbPort = random_int(30001, 40000);
$migrationPassword = 'migration-' . $token;
$runtimePassword = 'runtime-' . $token;
$runtimeUser = 'runtime_' . $token;
$overrideRoot = (realpath(sys_get_temp_dir()) ?: throw new TestFailure('SETUP_FAILURE: canonical temp root')) . '/fmonitor-runtime-browser-' . $token;
mkdir($overrideRoot, 0700);
$override = $overrideRoot . '/compose.override.yaml';
file_put_contents($override, "services:\n  db:\n    ports:\n      - \"127.0.0.1:{$dbPort}:3306\"\n", LOCK_EX);
chmod($override, 0600);
$base = ['docker', 'compose', '--progress', 'quiet', '--project-name', $project, '--file', $composeFile, '--file', $override];
$processEnvironment = getenv();
if (!is_array($processEnvironment)) throw new TestFailure('SETUP_FAILURE: process environment');
$environment = array_replace($processEnvironment, [
    'FMONITOR_RUNTIME_IMAGE' => $runtimeImage,
    'FMONITOR_HTTP_PORT' => (string) $httpPort,
    'FMONITOR_DB_NAME' => 'bootstrap_' . $token,
    'FMONITOR_DB_USER' => $runtimeUser,
    'FMONITOR_DB_PASSWORD' => $runtimePassword,
    'FMONITOR_MIGRATION_DB_USER' => 'root',
    'FMONITOR_MIGRATION_DB_PASSWORD' => $migrationPassword,
    'FMONITOR_PROCESS_TABLE_PREFIX' => 'pef_',
    'FMONITOR_LEGACY_TABLE_PREFIX' => 'pef_',
    'FMONITOR_SESSION_INSTANCE' => 'production_browser',
    'FMONITOR_TRUSTED_REQUEST_HOST' => '127.0.0.1:' . $httpPort,
    'FMONITOR_TRUSTED_REQUEST_SCHEME' => 'http',
]);

/** @return array{exit:int,stdout:string,stderr:string} */
function prbCommand(array $command, array $environment, string $cwd, int $timeout = 300): array
{
    $pipes=[];$process=proc_open($command,[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd,$environment);
    if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: process start');
    stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$out='';$err='';$deadline=microtime(true)+$timeout;
    do{$out.=stream_get_contents($pipes[1]);$err.=stream_get_contents($pipes[2]);$status=proc_get_status($process);if(!$status['running'])break;if(microtime(true)>=$deadline){proc_terminate($process,15);usleep(200000);proc_terminate($process,9);throw new TestFailure('SETUP_FAILURE: timeout '.implode(' ',$command));}usleep(50000);}while(true);
    $out.=stream_get_contents($pipes[1]);$err.=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$closed=proc_close($process);
    return ['exit'=>$status['exitcode']>=0?$status['exitcode']:$closed,'stdout'=>$out,'stderr'=>$err];
}

function prbOk(array $result,string $label):void
{
    assertSameValue(0,$result['exit'],$label.' stderr='.trim($result['stderr']).' stdout='.trim($result['stdout']));
}

function prbSeed(SelectedOriginalFixture $fixture,string $password):void
{
    $native=$fixture->selection;$db=$native->db;$p=$native->prefix;
    $migration=CanonicalMigrationApplication::run($db,$p,ProductionPilotMigrationCatalogue::migrations());
    assertSameValue([0,true,24,true],[$migration['exitCode'],$migration['result']['ok']??null,$migration['result']['schemaVersion']??null,InspectionPhotoContentIndexSchemaMigration::isCompleteCompatible($db,$p)],'fixture canonical v24');
    $db->query("UPDATE `{$p}fm_maintable` SET ordadr_address='Москва, Тестовая улица, 1',entrance='2',regnumber='77-000123',workdatestart='2026-09-05',plan_finish_date='2026-12-20' WHERE id=4512");
    $db->query("UPDATE `{$p}fm2_workforce_catalog` SET reconciliation_state='delivered'");
    foreach(range(7003,7027)as$tab)$native->schema->insert($p.'fm2_workforce_catalog',['installer_tab_id'=>$tab,'fio'=>'Монтажник '.$tab,'position'=>'Монтажник','employment_status'=>'employed','reconciliation_state'=>'delivered','employed_from'=>'2020-01-01','employed_to'=>null,'workforce_source'=>'synthetic-hr','workforce_source_updated_at'=>'2026-09-01T06:00:00Z']);
    $detail=json_encode(['schemaVersion'=>'technical-object-detail-v1','objectId'=>4512,'fields'=>['floors'=>['raw'=>9],'weight'=>['raw'=>1000],'pitmaterial'=>['display'=>'Железобетон'],'lift_type'=>['display'=>'Пассажирский']]],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);$native->schema->insert($p.'fm2_pilot_object_details',['object_id'=>4512,'schema_version'=>'technical-object-detail-v1','content_sha256'=>hash('sha256',$detail),'payload_json'=>$detail,'captured_at'=>'2026-09-01T06:00:00Z']);
    $caseId=(int)$db->query("SELECT id FROM `{$p}fm2_installation_cases` WHERE legacy_installation_object_id=4512")->fetch_assoc()['id'];
    $native->schema->insert($p.'fm2_migration_classification_provenance',['legacy_object_id'=>4512,'output_kind'=>'operational_case','output_id'=>$caseId,'source_cutoff_at'=>'2026-09-01 06:00:00','classification_version'=>'synthetic-v1','category'=>'native_candidate','reason_codes_json'=>'[]','classification_sha256'=>str_repeat('a',64),'created_at'=>'2026-09-01 06:00:00']);
    foreach(['objects.read','assignment_order.original.read','assignment_order.original.upload','assignment_order.original.correct','installation.completion.pto.record','installation.completion.declaration.record']as$permission)$db->query("INSERT IGNORE INTO `{$p}fm2_pilot_role_permissions`(role_id,permission)VALUES(1,'".$db->real_escape_string($permission)."')");
    foreach(['objects.read','construction_control.read','checklist.read','checklist.edit','inspection.item.complete','inspection.photo.revoke']as$permission)$db->query("INSERT IGNORE INTO `{$p}fm2_pilot_role_permissions`(role_id,permission)VALUES(2,'".$db->real_escape_string($permission)."')");
    $native->schema->insert($p.'fm2_pilot_users',['user_id'=>19,'full_name'=>'Открывающий сотрудник','email'=>'protected19@shlz.ru','status'=>1,'activation_state'=>'active','source_updated_at'=>'2026-09-01T06:00:00Z']);
    $native->schema->insert($p.'fm2_pilot_roles',['role_id'=>3,'code'=>'manager','name'=>'Открывающий','description'=>'synthetic','status'=>1,'source_updated_at'=>'2026-09-01T06:00:00Z']);
    foreach(['objects.read','installation.open']as$permission)$db->query("INSERT IGNORE INTO `{$p}fm2_pilot_role_permissions`(role_id,permission)VALUES(3,'".$db->real_escape_string($permission)."')");
    $native->schema->insert($p.'fm2_pilot_user_roles',['user_id'=>19,'role_id'=>3,'origin'=>'fixture','assigned_at'=>'2026-09-01T06:00:00Z']);
    $native->schema->insert($p.'fm2_process_user_capabilities',['user_id'=>19,'capability'=>'installation.open','position_snapshot'=>null]);
    $db->query("UPDATE `{$p}fm2_pilot_users` SET email='test18@shlz.ru' WHERE user_id=18");
    $db->query("UPDATE `{$p}fm2_pilot_users` SET email='protected73@shlz.ru' WHERE user_id=73");
    $hash=password_hash($password,PASSWORD_ARGON2ID);
    foreach([[18,'test18@shlz.ru'],[19,'protected19@shlz.ru'],[73,'protected73@shlz.ru']]as[$user,$email])$native->schema->insert($p.'fm2_pilot_auth_credentials',['user_id'=>$user,'email_normalized'=>$email,'password_hash'=>$hash,'updated_at'=>'2026-09-01T06:00:00Z']);
    $native->schema->insert($p.'fm2_pilot_users',['user_id'=>31,'full_name'=>'Руководитель ОТиЗ','email'=>'test31@shlz.ru','status'=>1,'activation_state'=>'active','source_updated_at'=>'2026-09-01T06:00:00Z']);
    $native->schema->insert($p.'fm2_pilot_roles',['role_id'=>4,'code'=>'otiz_manager','name'=>'ОТиЗ','description'=>'synthetic','status'=>1,'source_updated_at'=>'2026-09-01T06:00:00Z']);
    $native->schema->insert($p.'fm2_pilot_user_roles',['user_id'=>31,'role_id'=>4,'origin'=>'fixture','assigned_at'=>'2026-09-01T06:00:00Z']);
    foreach(['otiz.manage','objects.read']as$permission)$native->schema->insert($p.'fm2_pilot_role_permissions',['role_id'=>4,'permission'=>$permission]);
    $otizHash=password_hash('Synthetic-Otiz-Browser-2026!',PASSWORD_ARGON2ID);$native->schema->insert($p.'fm2_pilot_auth_credentials',['user_id'=>31,'email_normalized'=>'test31@shlz.ru','password_hash'=>$otizHash,'updated_at'=>'2026-09-01T06:00:00Z']);
    $native->schema->insert($p.'fm2_pilot_users',['user_id'=>99,'full_name'=>'Администратор','email'=>'admin99@shlz.ru','status'=>1,'activation_state'=>'active','source_updated_at'=>'2026-09-01T06:00:00Z']);
    $native->schema->insert($p.'fm2_pilot_roles',['role_id'=>5,'code'=>'system_admin','name'=>'Администратор','description'=>'synthetic','status'=>1,'source_updated_at'=>'2026-09-01T06:00:00Z']);
    $native->schema->insert($p.'fm2_pilot_user_roles',['user_id'=>99,'role_id'=>5,'origin'=>'fixture','assigned_at'=>'2026-09-01T06:00:00Z']);$native->schema->insert($p.'fm2_pilot_role_permissions',['role_id'=>5,'permission'=>'access.administer']);
    $native->schema->insert($p.'fm2_pilot_auth_credentials',['user_id'=>99,'email_normalized'=>'admin99@shlz.ru','password_hash'=>$hash,'updated_at'=>'2026-09-01T06:00:00Z']);
    require_once dirname(__DIR__,2).'/app/PilotHttp/ChecklistView.php';$sections=(new ReflectionClass(ProductionChecklistRenderer::class))->getConstant('SECTIONS');$parts=[];$definitions=[];$sum=0;
    foreach($sections as$section){$parts[]=['id'=>$section['id'],'name'=>$section['name'],'rang'=>$section['id']];foreach($section['items']as[$id,$label,$weight]){$definitions[]=['id'=>$id,'part_id'=>$section['id'],'name'=>$label,'share'=>$weight,'rang'=>count($definitions)+1,'needphoto'=>1];if($id!==42)$sum+=$weight;}}
    assertSameValue(85,$sum,'approved protected work weight');$payload=json_encode(['snapshotVersion'=>'protected-current-v1','capturedAt'=>'2026-09-01 00:00:00','validFrom'=>'2026-09-01 00:00:00','validity'=>'synthetic','source'=>'synthetic','parts'=>$parts,'definitions'=>$definitions],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
    $native->schema->insert($p.'fm2_checklist_template_snapshots',['snapshot_version'=>'protected-current-v1','captured_at'=>'2026-09-01 00:00:00','valid_from'=>'2026-09-01 00:00:00','validity_scope'=>'active_baseline_and_future_native_only','source_label'=>'Protected production runtime E2E','content_sha256'=>hash('sha256',$payload),'payload_json'=>$payload,'created_at'=>'2026-09-01 00:00:00']);
}

$fixture=null;$admin=null;
try{
    prbOk(prbCommand([...$base,'build'],$environment,$repo),'exact-source image build');
    prbOk(prbCommand([...$base,'up','--detach','--wait','db'],$environment,$repo),'isolated database start');
    foreach(['FMONITOR_TEST_DB_HOST'=>'127.0.0.1','FMONITOR_TEST_DB_PORT'=>(string)$dbPort,'FMONITOR_TEST_DB_ADMIN_USER'=>'root','FMONITOR_TEST_DB_ADMIN_PASSWORD'=>$migrationPassword]as$key=>$value)putenv($key.'='.$value);
    $fixture=new SelectedOriginalFixture('pef_',static function(mysqli$db,string$p):void{
        $db->query("INSERT INTO `{$p}fm2_installation_cases`(id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version)VALUES(4520,4520,'working','2026-08-01','2026-08-01T09:00:00Z',31,'2026-08-01T08:00:00Z','2026-08-01T09:00:00Z',1)");
        $db->query("INSERT INTO `{$p}fm2_assignment_orders`(id,installation_case_id,version_no,kind,status,order_date,registration_number,registered_at,registration_actor_type,registration_actor_id,registration_source,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot,prepared_at,prepared_by_user_id)VALUES(80,4520,1,'initial','registered','2026-08-01','OTIZ-80','2026-08-01T08:30:00Z','user','31','fixture',73,'Инженер теста','Инженер стройконтроля','contract','Москва, ОТиЗ, 20','1','77-OTIZ-20','2026-08-01','2026-09-30','2026-08-01T08:00:00Z',31)");
        $db->query("INSERT INTO `{$p}fm2_order_installers`(assignment_order_id,installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot,employed_from_snapshot,workforce_source_snapshot,workforce_source_updated_at_snapshot,valid_from,change_action)VALUES(80,7001,'Монтажник 7001','Монтажник','employed','2020-01-01','synthetic-hr','2026-09-01T06:00:00Z','2026-08-01','assigned')");
    });
    prbSeed($fixture,'Synthetic protected E2E 2026');
    $database=$fixture->selection->schema->source->name;$environment['FMONITOR_DB_NAME']=$database;
    $facts=static function()use($dbPort,$database,$migrationPassword):array{$connection=new mysqli('127.0.0.1','root',$migrationPassword,$database,$dbPort);try{$snapshot=[];foreach(['pef_fm2_assignment_order_original_roots','pef_fm2_assignment_order_original_revisions','pef_fm2_assignment_order_original_events','pef_fm2_checklist_operations','pef_fm2_checklist_operation_installers','pef_fm2_checklist_photos','pef_fm2_pilot_completion_facts']as$table){$result=$connection->query("SELECT * FROM `{$table}`");$positions=implode(',',range(1,$result->field_count));$snapshot[$table]=$connection->query("SELECT * FROM `{$table}` ORDER BY {$positions}")->fetch_all(MYSQLI_ASSOC);}return$snapshot;}finally{$connection->close();}};
    $admin=new mysqli('127.0.0.1','root',$migrationPassword,'',$dbPort);$escaped=$admin->real_escape_string($runtimePassword);$dbEscaped=str_replace('`','``',$database);
    $admin->query("CREATE USER `{$runtimeUser}`@'%' IDENTIFIED BY '{$escaped}'");
    $admin->query("GRANT SELECT,INSERT,UPDATE,DELETE ON `{$dbEscaped}`.* TO `{$runtimeUser}`@'%'");
    prbOk(prbCommand([...$base,'--profile','deployment','run','--rm','prepare'],$environment,$repo),'runtime prepare');
    prbOk(prbCommand([...$base,'--profile','deployment','run','--rm','migrate'],$environment,$repo),'canonical repeat through deployment seam');
    prbOk(prbCommand([...$base,'up','--detach','--wait','php','web'],$environment,$repo),'production nginx/FPM start');
    $artifacts=$overrideRoot.'/browser';mkdir($artifacts,0700);file_put_contents($artifacts.'/photo.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));file_put_contents($artifacts.'/original.pdf',SelectedOriginalFixture::pdf());
    $result=$artifacts.'/result.json';$node=getenv('FMONITOR_TEST_NODE_BINARY')?:trim((string)shell_exec('command -v node'));$playwright=getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($repo).'/shlz-ui/node_modules/playwright';
    $browser=prbCommand([$node,$repo.'/tests/Support/pilot_current_flow_browser.cjs',(string)$httpPort,$playwright,$artifacts,$result],$environment,$repo,180);
    $diagnostic=is_file($result)?(string)file_get_contents($result):'';
    $runtimeLogs=$browser['exit']===0?'':prbCommand([...$base,'logs','--no-color','--tail','80','php','web'],$environment,$repo,30)['stdout'];
    assertSameValue(0,$browser['exit'],'INTENTIONAL_RED: protected journey through production nginx/FPM stderr='.$browser['stderr'].' stdout='.$browser['stdout'].' result='.$diagnostic.' runtime='.preg_replace('/[0-9a-f]{12,}/i','<ID>',$runtimeLogs));
    $observed=json_decode($diagnostic,true,512,JSON_THROW_ON_ERROR);
    assertSameValue(['PASS',85,'100',41,7,0,true],[$observed['result'],$observed['workProgress'],$observed['finalProgress'],$observed['workItems'],$observed['photos'],$observed['errors'],$observed['separateOpener']],'production protected browser result');
    $factsBefore=$facts();
    $stateBefore=prbCommand([...$base,'--profile','deployment','run','--rm','--entrypoint','sh','prepare','-c','find /home/fmonitor/.local/state/fmonitor2/sessions -type f -name "s-*.session" -exec sha256sum {} + | sort; find /home/fmonitor/.local/state/fmonitor2/artifacts -type f -exec sha256sum {} + | sort'],$environment,$repo,30);prbOk($stateBefore,'persistent state snapshot');
    $ownerSession=prbCommand([...$base,'--profile','deployment','run','--rm','--entrypoint','sh','prepare','-c','for f in /home/fmonitor/.local/state/fmonitor2/sessions/production_browser/s-*.session; do grep -aq "auth_user_id.*i:18" "$f" && basename "$f" | sed -e "s/^s-//" -e "s/\\.session$//" && exit 0; done; exit 1'],$environment,$repo,30);prbOk($ownerSession,'owner session identity');$ownerSessionId=trim($ownerSession['stdout']);
    $ownerHashBefore=prbCommand([...$base,'--profile','deployment','run','--rm','--entrypoint','sha256sum','prepare','/home/fmonitor/.local/state/fmonitor2/sessions/production_browser/s-'.$ownerSessionId.'.session'],$environment,$repo,30);prbOk($ownerHashBefore,'owner session pre-restart hash');
    prbOk(prbCommand([...$base,'restart','php','web'],$environment,$repo,90),'production process restart');
    $stateRestarted=prbCommand([...$base,'--profile','deployment','run','--rm','--entrypoint','sh','prepare','-c','find /home/fmonitor/.local/state/fmonitor2/sessions -type f -name "s-*.session" -exec sha256sum {} + | sort; find /home/fmonitor/.local/state/fmonitor2/artifacts -type f -exec sha256sum {} + | sort'],$environment,$repo,30);prbOk($stateRestarted,'immediate post-restart state snapshot');assertSameValue($stateBefore['stdout'],$stateRestarted['stdout'],'process restart preserves session and private artifact bytes exactly');
    $restartResult=$artifacts.'/restart-result.json';$restartBrowser=prbCommand([$node,__DIR__.'/production_runtime_restart_browser.mjs',(string)$httpPort,$playwright,$ownerSessionId,$restartResult],$environment,$repo,60);assertSameValue(0,$restartBrowser['exit'],'persisted browser session/private read after restart '.(is_file($restartResult)?file_get_contents($restartResult):$restartBrowser['stderr']));
    $ownerHashAfter=prbCommand([...$base,'--profile','deployment','run','--rm','--entrypoint','sha256sum','prepare','/home/fmonitor/.local/state/fmonitor2/sessions/production_browser/s-'.$ownerSessionId.'.session'],$environment,$repo,30);prbOk($ownerHashAfter,'owner session post-read hash');assertSameValue($ownerHashBefore['stdout'],$ownerHashAfter['stdout'],'authorized restart read preserves exact committed owner session bytes');
    $adminResult=$artifacts.'/admin-result.json';$adminBrowser=prbCommand([$node,__DIR__.'/production_runtime_admin_browser.mjs',(string)$httpPort,$playwright,$adminResult],$environment,$repo,60);assertSameValue(0,$adminBrowser['exit'],'HTTP production admin route/cookie flags '.(is_file($adminResult)?file_get_contents($adminResult):$adminBrowser['stderr']));
    $factsAfter=$facts();
    $stateAfter=prbCommand([...$base,'--profile','deployment','run','--rm','--entrypoint','sh','prepare','-c','find /home/fmonitor/.local/state/fmonitor2/sessions -type f -name "s-*.session" -exec sha256sum {} + | sort; find /home/fmonitor/.local/state/fmonitor2/artifacts -type f -exec sha256sum {} + | sort'],$environment,$repo,30);prbOk($stateAfter,'post-restart state snapshot');
    $artifactLines=static fn(string$lines):array=>array_values(array_filter(explode("\n",trim($lines)),static fn(string$line):bool=>str_contains($line,'/artifacts/')));
    assertSameValue([$factsBefore,$artifactLines($stateBefore['stdout'])],[$factsAfter,$artifactLines($stateAfter['stdout'])],'restart and authorized reads preserve DB history and private bytes exactly');
    $eligibilityConnection=new mysqli('127.0.0.1','root',$migrationPassword,$database,$dbPort);
    $eligibilityConnection->query("INSERT INTO pef_fm_maintable(id,ordadr_address,entrance,regnumber,workdatestart,plan_finish_date,floors,weight,pitmaterial)VALUES(4520,'Москва, ОТиЗ, 20','1','77-OTIZ-20','2026-08-01','2026-09-30','9','1000','Железобетон')");
    $eligibilityConnection->query("INSERT INTO pef_fm2_migration_classification_provenance(legacy_object_id,output_kind,output_id,source_cutoff_at,classification_version,category,reason_codes_json,classification_sha256,created_at)VALUES(4520,'operational_case',4520,'2026-09-01 06:00:00','synthetic-v1','native_candidate','[]',REPEAT('b',64),'2026-09-01 06:00:00')");
    $otizPayload=json_encode(['snapshotVersion'=>'production-browser-otiz-v1','definitions'=>[['id'=>1,'share'=>85]]],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);$otizTemplateHash=hash('sha256',$otizPayload);$escapedOtizPayload=$eligibilityConnection->real_escape_string($otizPayload);
    $eligibilityConnection->query("INSERT INTO pef_fm2_checklist_template_snapshots(id,snapshot_version,captured_at,valid_from,validity_scope,source_label,content_sha256,payload_json,created_at)VALUES(9904,'production-browser-otiz-v1','2026-08-01 00:00:00','2026-08-01 00:00:00','synthetic','Production browser distinct OTIZ fixture','$otizTemplateHash','$escapedOtizPayload','2026-08-01 00:00:00')");
    $eligibilityConnection->query("INSERT INTO pef_fm2_checklist_operations(installation_case_id,client_operation_id,device_installation_id,operation_type,section_id,item_id,actor_user_id,device_time,server_received_at,base_revision,accepted_revision,payload_json,template_snapshot_id,template_snapshot_version,template_content_sha256)VALUES(4520,'82000000-0000-4000-8000-000000000001','82000000-0000-4000-8000-000000000002','item_completed',1,1,73,'2026-08-20T10:00:00+03:00','2026-08-20T10:00:01+03:00',0,1,'{}',9904,'production-browser-otiz-v1','$otizTemplateHash')");
    $eligibilityConnection->query("INSERT INTO pef_fm2_checklist_operation_installers(client_operation_id,installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot,workforce_source_updated_at_snapshot,assignment_source)VALUES('82000000-0000-4000-8000-000000000001',7001,'Монтажник 7001','Монтажник','employed','2026-09-01T06:00:00Z','completion')");
    $otizDetail=json_encode(['schemaVersion'=>'technical-object-detail-v1','objectId'=>4520,'fields'=>['floors'=>['raw'=>9],'weight'=>['raw'=>1000],'pitmaterial'=>['display'=>'Железобетон'],'lift_type'=>['display'=>'Пассажирский']]],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);$otizDetailHash=hash('sha256',$otizDetail);$escapedOtizDetail=$eligibilityConnection->real_escape_string($otizDetail);
    $eligibilityConnection->query("INSERT INTO pef_fm2_pilot_object_details(object_id,schema_version,content_sha256,payload_json,captured_at)VALUES(4520,'technical-object-detail-v1','$otizDetailHash','$escapedOtizDetail','2026-08-01T09:00:00Z')");
    $eligibilityRows=$eligibilityConnection->query("SELECT c.id,c.process_state,c.actual_start_date,o.status order_status,mp.output_id,mp.category,i.source_kind FROM pef_fm2_installation_cases c JOIN pef_fm2_assignment_orders o ON o.installation_case_id=c.id JOIN pef_fm2_migration_classification_provenance mp ON mp.output_id=c.id JOIN pef_fm2_assignment_order_identities i ON i.assignment_order_id=o.id WHERE c.legacy_installation_object_id=4520")->fetch_all(MYSQLI_ASSOC);$nativeOpposite=(int)$eligibilityConnection->query("SELECT COUNT(*) n FROM pef_fm2_assignment_orders WHERE id=81")->fetch_assoc()['n'];$eligibilityConnection->close();
    assertSameValue([[['id'=>'4520','process_state'=>'working','actual_start_date'=>'2026-08-01','order_status'=>'registered','output_id'=>'4520','category'=>'native_candidate','source_kind'=>'legacy_order']],0],[$eligibilityRows,$nativeOpposite],'distinct pre-backfill legacy OTIZ lineage preserves native selection identity');
    $reportDate=(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format('Y-m-d');$otizResult=$artifacts.'/otiz-result.json';$otizBrowser=prbCommand([$node,$repo.'/tests/Otiz/snapshot_publication_browser_001_test.mjs',(string)$httpPort,$playwright,$artifacts,$otizResult,$reportDate],$environment,$repo,90);assertSameValue(0,$otizBrowser['exit'],'OTIZ calculate/accept/XLSX through production runtime '.(is_file($otizResult)?file_get_contents($otizResult):$otizBrowser['stderr']));
    $otiz=json_decode((string)file_get_contents($otizResult),true,512,JSON_THROW_ON_ERROR);assertSameValue(true,($otiz['xlsxBytes']??0)>1000,'production OTIZ XLSX download has bytes');
    $postOtizOriginalResult=$artifacts.'/post-otiz-original-result.json';$postOtizOriginal=prbCommand([$node,__DIR__.'/production_runtime_restart_browser.mjs',(string)$httpPort,$playwright,$ownerSessionId,$postOtizOriginalResult],$environment,$repo,60);assertSameValue(0,$postOtizOriginal['exit'],'INTENTIONAL_RED: OTIZ eligibility must not corrupt native original lineage '.(is_file($postOtizOriginalResult)?file_get_contents($postOtizOriginalResult):$postOtizOriginal['stderr']));$postOtizOriginalObserved=json_decode((string)file_get_contents($postOtizOriginalResult),true,512,JSON_THROW_ON_ERROR);assertSameValue([true,327],[$postOtizOriginalObserved['success']??false,$postOtizOriginalObserved['originalBytes']??null],'post-OTIZ native private original remains readable');
    echo "PASS: PRODUCTION-HTTP-RUNTIME-001 protected production browser journey\n";
}finally{
    if($admin instanceof mysqli)$admin->close();
    if($fixture instanceof SelectedOriginalFixture){try{$fixture->close();}catch(Throwable){}}
    prbCommand([...$base,'--profile','deployment','down','--volumes','--remove-orphans'],$environment,$repo,60);
    prbCommand(['docker','image','rm',$runtimeImage],$environment,$repo,60);
    if(getenv('FMONITOR_TEST_KEEP_RUNTIME_EVIDENCE')==='1'){echo "RUNTIME_EVIDENCE_DIR=".$overrideRoot."\n";}
    elseif(is_file($override)||is_dir($overrideRoot)){foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($overrideRoot,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST)as$file){$file->isDir()?rmdir($file->getPathname()):unlink($file->getPathname());}rmdir($overrideRoot);}
}
