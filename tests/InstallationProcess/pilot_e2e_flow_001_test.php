<?php
declare(strict_types=1);
// Protected PILOT-E2E-FLOW-001 current-flow verifier. Synthetic resources only.
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require_once dirname(__DIR__).'/Support/SelectionHttpFixture.php';
require_once dirname(__DIR__).'/Support/SelectedOriginalFixture.php';

function pefProtectedRun(array $command,int $timeoutSeconds=120):array
{
    $root=dirname(__DIR__,2);$pipes=[];$process=null;$ownedPid=null;$groupPid=null;$stdout='';$stderr='';
    try{
        $wrapped=[PHP_BINARY,$root.'/tests/Support/process_group_exec.php',...$command];
        $process=proc_open($wrapped,[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w'],3=>['pipe','w'],4=>['pipe','r']],$pipes,$root);
        if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: protected child start');$opened=proc_get_status($process);$ownedPid=(int)($opened['pid']??0);if($ownedPid<1)throw new TestFailure('SETUP_FAILURE: protected child pid');
        stream_set_timeout($pipes[3],5);$ready=fgets($pipes[3]);
        if(preg_match('/^READY ([1-9][0-9]*)\n$/D',(string)$ready,$match)!==1)throw new TestFailure('SETUP_FAILURE: protected process group readiness');
        $groupPid=(int)$match[1];if($groupPid!==$ownedPid||!function_exists('posix_getpgid')||posix_getpgid($ownedPid)!==$ownedPid)throw new TestFailure('SETUP_FAILURE: protected process group identity');fwrite($pipes[4],"RELEASE {$groupPid}\n");fflush($pipes[4]);fclose($pipes[3]);fclose($pipes[4]);unset($pipes[3],$pipes[4]);
        foreach([1,2]as$fd)stream_set_blocking($pipes[$fd],false);$deadline=microtime(true)+$timeoutSeconds;
        do{$stdout.=(string)stream_get_contents($pipes[1]);$stderr.=(string)stream_get_contents($pipes[2]);$status=proc_get_status($process);if(!$status['running'])break;usleep(10000);}while(microtime(true)<$deadline);
        if($status['running'])throw new TestFailure('SETUP_FAILURE: protected child timeout');
        $stdout.=(string)stream_get_contents($pipes[1]);$stderr.=(string)stream_get_contents($pipes[2]);$reported=(int)$status['exitcode'];
        foreach($pipes as$pipe)if(is_resource($pipe))fclose($pipe);$pipes=[];$closed=proc_close($process);$process=null;
        return['exit'=>$reported>=0?$reported:$closed,'stdout'=>$stdout,'stderr'=>$stderr];
    }finally{
        if(is_resource($process)){$status=proc_get_status($process);if($status['running']){if($groupPid!==null)@posix_kill(-$groupPid,SIGTERM);else@proc_terminate($process,SIGTERM);$deadline=microtime(true)+1;do{usleep(10000);$status=proc_get_status($process);}while($status['running']&&microtime(true)<$deadline);if($status['running']){if($groupPid!==null)@posix_kill(-$groupPid,SIGKILL);else@proc_terminate($process,SIGKILL);}}foreach($pipes as$pipe)if(is_resource($pipe))fclose($pipe);proc_close($process);}
    }
}

function pefProtectedContract(string $relative):void
{
    $host=(string)(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1');$port=(string)(getenv('FMONITOR_TEST_DB_PORT')?:'23306');$user=(string)(getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root');$password=(string)(getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local');
    $result=pefProtectedRun(['/usr/bin/env','FMONITOR_DB_HOST='.$host,'FMONITOR_DB_PORT='.$port,'FMONITOR_DB_USER='.$user,'FMONITOR_DB_PASSWORD='.$password,PHP_BINARY,dirname(__DIR__,2).'/'.$relative]);
    assertSameValue(0,$result['exit'],'retained contract '.$relative.' stderr='.$result['stderr'].' stdout='.$result['stdout']);
}

function pefProtectedPreflight():array
{
    $root=dirname(__DIR__,2);$autoload=$root.'/vendor/autoload.php';
    if(!is_file($autoload))throw new TestFailure('SETUP_FAILURE: checkout-local TCPDF vendor/autoload.php is required');
    require_once$autoload;
    if(!class_exists(TCPDF::class)||TCPDF_STATIC::getTCPDFVersion()!=='6.11.4')throw new TestFailure('SETUP_FAILURE: exact TCPDF 6.11.4 is required');
    $node=(string)(getenv('FMONITOR_TEST_NODE_BINARY')?:'');if($node!==''&&($node[0]!=='/'||!is_executable($node)))throw new TestFailure('SETUP_FAILURE: configured Node binary');
    if($node==='')foreach(explode(PATH_SEPARATOR,(string)getenv('PATH'))as$directory){$candidate=rtrim($directory,'/').'/node';if($directory!==''&&is_executable($candidate)){$node=(string)realpath($candidate);break;}}
    $playwright=(string)(getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($root).'/shlz-ui/node_modules/playwright');
    if($node===''||!is_dir($playwright))throw new TestFailure('SETUP_FAILURE: headless Playwright prerequisite');
    return[$root,$node,$playwright];
}

function pefProtectedRetainedContracts():void
{
    foreach([
        'tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php',
        'tests/AssignmentOrderComposition/selection_http_flow_001_test.php',
        'tests/AssignmentOrderComposition/original_upload_http_flow_001_test.php',
        'tests/AssignmentOrderComposition/original_upload_http_admission_001_test.php',
        'tests/AssignmentOrderComposition/original_upload_http_permissions_001_test.php',
        'tests/AssignmentOrderComposition/confirmed_original_opening_001_test.php',
        'tests/AssignmentOrderComposition/confirmed_original_opening_http_001_test.php',
        'tests/AssignmentOrderComposition/original_history_download_001_test.php',
        'tests/AssignmentOrderComposition/generated_template_passive_pdf_manual_pilot_test.php',
        'tests/InstallationProcess/checklist_bulk_online_sequence_manual_test.php',
        'tests/InstallationProcess/control_queue_bulk_protocol_manual_test.php',
        'rapid-pilot/verify-completion-flow.php',
    ]as$contract)pefProtectedContract($contract);
}

function pefProtectedFixture():FMonitor2\Tests\Support\SelectionHttpFixture
{
    return new FMonitor2\Tests\Support\SelectionHttpFixture(true,static fn($original,$port)=>[
        'FMONITOR_NOW'=>'2026-09-07T12:00:00+03:00','FMONITOR_DEMO_LOOPBACK'=>'1',
        'FMONITOR_TRUSTED_REQUEST_HOST'=>'127.0.0.1:'.$port,'FMONITOR_DEMO_LOOPBACK_NONCE'=>str_repeat('b',32),
        'FMONITOR_ORIGINAL_DB_PASSWORD_FILE'=>$original->control.'/password','FMONITOR_ORIGINAL_SAFE_LOG_FILE'=>$original->safeLog,
    ],'pef_');
}

function pefProtectedSeed(FMonitor2\Tests\Support\SelectionHttpFixture $fixture,string $password):void
{
    $native=$fixture->original->selection;$db=$native->db;$p=$native->prefix;
    $migration=FMonitor2\InstallationProcess\CanonicalMigrationApplication::run($db,$p,FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue::migrations());
    assertSameValue([0,true,24,true],[$migration['exitCode'],$migration['result']['ok']??null,$migration['result']['schemaVersion']??null,FMonitor2\InstallationProcess\InspectionPhotoContentIndexSchemaMigration::isCompleteCompatible($db,$p)],'protected fixture reaches exact canonical v24 frontier without conflict');
    $db->query("UPDATE `{$p}fm2_workforce_catalog` SET reconciliation_state='delivered'");
    foreach(range(7003,7027)as$tab)$native->schema->insert($p.'fm2_workforce_catalog',['installer_tab_id'=>$tab,'fio'=>'Монтажник '.$tab,'position'=>'Монтажник','employment_status'=>'employed','reconciliation_state'=>'delivered','employed_from'=>'2020-01-01','employed_to'=>null,'workforce_source'=>'synthetic-hr','workforce_source_updated_at'=>'2026-09-01T06:00:00Z']);
    $detail=json_encode(['schemaVersion'=>'technical-object-detail-v1','objectId'=>4512,'fields'=>[]],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);$native->schema->insert($p.'fm2_pilot_object_details',['object_id'=>4512,'schema_version'=>'technical-object-detail-v1','content_sha256'=>hash('sha256',$detail),'payload_json'=>$detail,'captured_at'=>'2026-09-01T06:00:00Z']);
    foreach(['objects.read','assignment_order.original.read','assignment_order.original.upload','assignment_order.original.correct','installation.completion.pto.record','installation.completion.declaration.record']as$permission)$db->query("INSERT IGNORE INTO `{$p}fm2_pilot_role_permissions`(role_id,permission)VALUES(1,'".$db->real_escape_string($permission)."')");
    foreach(['objects.read','construction_control.read','checklist.read','checklist.edit','inspection.item.complete','inspection.photo.revoke']as$permission)$db->query("INSERT IGNORE INTO `{$p}fm2_pilot_role_permissions`(role_id,permission)VALUES(2,'".$db->real_escape_string($permission)."')");
    $native->schema->insert($p.'fm2_pilot_users',['user_id'=>19,'full_name'=>'Открывающий сотрудник','email'=>'protected19@shlz.ru','status'=>1,'activation_state'=>'active','source_updated_at'=>'2026-09-01T06:00:00Z']);
    foreach(['objects.read','installation.open']as$permission)$db->query("INSERT IGNORE INTO `{$p}fm2_pilot_role_permissions`(role_id,permission)VALUES(3,'".$db->real_escape_string($permission)."')");
    $native->schema->insert($p.'fm2_pilot_user_roles',['user_id'=>19,'role_id'=>3,'origin'=>'fixture','assigned_at'=>'2026-09-01T06:00:00Z']);
    assertSameValue(0,(int)$db->query("SELECT COUNT(*) n FROM `{$p}fm2_pilot_role_permissions` WHERE role_id=3 AND permission IN('assignment_order.composition.apply','assignment_order.original.upload','assignment_order.original.correct')")->fetch_assoc()['n'],'opener has no standalone apply or original mutation grant');
    $db->query("UPDATE `{$p}fm2_pilot_users` SET email='protected73@shlz.ru' WHERE user_id=73");
    $hash=password_hash($password,PASSWORD_ARGON2ID);$update=$db->prepare("UPDATE `{$p}fm2_pilot_auth_credentials` SET password_hash=? WHERE user_id=18");$update->execute([$hash]);
    foreach([[19,'protected19@shlz.ru'],[73,'protected73@shlz.ru']]as[$user,$email])$native->schema->insert($p.'fm2_pilot_auth_credentials',['user_id'=>$user,'email_normalized'=>$email,'password_hash'=>$hash,'updated_at'=>'2026-09-01T06:00:00Z']);
    require_once dirname(__DIR__,2).'/app/PilotHttp/ChecklistView.php';$sections=(new ReflectionClass(FMonitor2\PilotHttp\ProductionChecklistRenderer::class))->getConstant('SECTIONS');$parts=[];$definitions=[];$sum=0;
    foreach($sections as$section){$parts[]=['id'=>$section['id'],'name'=>$section['name'],'rang'=>$section['id']];foreach($section['items']as[$id,$label,$weight]){$definitions[]=['id'=>$id,'part_id'=>$section['id'],'name'=>$label,'share'=>$weight,'rang'=>count($definitions)+1,'needphoto'=>1];if($id!==42)$sum+=$weight;}}
    assertSameValue(85,$sum,'approved protected work weight');$payload=json_encode(['snapshotVersion'=>'protected-current-v1','capturedAt'=>'2026-09-01 00:00:00','validFrom'=>'2026-09-01 00:00:00','validity'=>'synthetic','source'=>'synthetic','parts'=>$parts,'definitions'=>$definitions],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
    $native->schema->insert($p.'fm2_checklist_template_snapshots',['snapshot_version'=>'protected-current-v1','captured_at'=>'2026-09-01 00:00:00','valid_from'=>'2026-09-01 00:00:00','validity_scope'=>'active_baseline_and_future_native_only','source_label'=>'Protected current E2E','content_sha256'=>hash('sha256',$payload),'payload_json'=>$payload,'created_at'=>'2026-09-01 00:00:00']);
}

function pefProtectedBrowser(FMonitor2\Tests\Support\SelectionHttpFixture $fixture,string $root,string $node,string $playwright):array
{
    $rootResponse=$fixture->request('GET','/');assertSameValue([302,'/pilot/objects'],[$rootResponse['status'],$rootResponse['headers']['location']??null],'rapid root redirects to objects');$compatibility=$fixture->request('GET','/pilot/');assertSameValue(200,$compatibility['status'],'authenticated compatibility shell remains available');assertSameValue(true,str_contains($compatibility['body'],'href="/pilot/objects"'),'compatibility shell exposes exact objects navigation');
    $artifacts=$fixture->original->control.'/protected-current-flow';if(!mkdir($artifacts,0700,true)&&!is_dir($artifacts))throw new TestFailure('SETUP_FAILURE: protected artifact root');
    file_put_contents($artifacts.'/photo.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
    file_put_contents($artifacts.'/original.pdf',FMonitor2\Tests\Support\SelectedOriginalFixture::pdf());$resultPath=$artifacts.'/result.json';
    $run=pefProtectedRun([$node,$root.'/tests/Support/pilot_current_flow_browser.cjs',(string)$fixture->port,$playwright,$artifacts,$resultPath]);
    $diagnostic='';if(is_file($resultPath)){try{$failed=json_decode((string)file_get_contents($resultPath),true,512,JSON_THROW_ON_ERROR);$diagnostic=' failure='.(string)($failed['failure']??'').' errors='.json_encode($failed['errorDetails']??[],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).' requestFailures='.json_encode($failed['requestFailures']??[],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);}catch(Throwable){$diagnostic=' result.json unreadable';}}$httpLog=$fixture->original->control.'/http.log';if($run['exit']!==0&&is_file($httpLog)){$lines=array_slice(file($httpLog,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[],-8);$safe=preg_replace('/[0-9a-f]{12,}/i','<ID>',implode(' | ',$lines));$diagnostic.=' http='.str_replace([$fixture->original->control,"\n","\r"],['<FIXTURE>','',''],(string)$safe);}
    assertSameValue(0,$run['exit'],'current browser flow'.$diagnostic.' stderr='.$run['stderr'].' stdout='.$run['stdout']);
    $result=json_decode((string)file_get_contents($resultPath),true,512,JSON_THROW_ON_ERROR);$template=(string)file_get_contents($artifacts.'/template.pdf');
    assertSameValue(FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPdfStatus::PASSIVE_PDF,(new FMonitor2\AssignmentOrderOriginal\FMonitorPassivePdfInspector())->inspect($template)->status,'browser-downloaded template remains passive PDF');
    assertSameValue([strlen($template),hash('sha256',$template)],[$result['templateBytes'],$result['templateSha256']],'browser template exact bytes/hash evidence');
    return$result;
}

function pefProtectedAuthoritySnapshot(mysqli $db,string $p):array
{
    $queries=[
        'legacy objects'=>"SELECT * FROM `{$p}fm_maintable` ORDER BY id",
        'workforce catalog'=>"SELECT * FROM `{$p}fm2_workforce_catalog` ORDER BY installer_tab_id",
        'workforce runs'=>"SELECT * FROM `{$p}fm2_workforce_sync_runs` ORDER BY run_id",
        'workforce metadata'=>"SELECT * FROM `{$p}fm2_workforce_sync_metadata` ORDER BY singleton_id",
    ];$snapshot=[];
    foreach($queries as$name=>$sql){$result=$db->query($sql);$snapshot[$name]=$result->fetch_all(MYSQLI_ASSOC);}
    return$snapshot;
}

function pefProtectedOriginalReadOnly(FMonitor2\Tests\Support\SelectionHttpFixture $fixture):void
{
    $native=$fixture->original->selection;$p=$native->prefix;$revision=(string)$native->db->query("SELECT current_revision_id FROM `{$p}fm2_assignment_order_original_roots` WHERE assignment_order_id=81")->fetch_assoc()['current_revision_id'];
    $path='/pilot/objects/4512/assignment-orders/81/originals/'.$revision.'/download';$beforeRows=$native->rows();$beforeFiles=$fixture->original->privateFiles();
    $get=$fixture->request('GET',$path);$head=$fixture->request('HEAD',$path);
    assertSameValue([200,200,'application/pdf',''],[$get['status'],$head['status'],$get['headers']['content-type']??null,$head['body']],'current original GET/HEAD status media and empty HEAD body');
    foreach(['content-type','content-length','content-disposition','x-content-type-options','cache-control']as$header)assertSameValue($get['headers'][$header]??null,$head['headers'][$header]??null,'current original GET/HEAD exact '.$header);
    assertSameValue([FMonitor2\Tests\Support\SelectedOriginalFixture::pdf(),$beforeRows,$beforeFiles],[$get['body'],$native->rows(),$fixture->original->privateFiles()],'current original GET/HEAD exact bytes and zero DB/storage delta');
}

function pefProtectedFreshFacts(FMonitor2\Tests\Support\SelectionHttpFixture $fixture,array $authorityBefore,string $foreignSentinel):void
{
    $selection=$fixture->original->selection;$p=$selection->prefix;$source=$selection->schema->source;$fresh=$source->connect($source->name);
    try{
        $counts=[(int)$fresh->query("SELECT COUNT(*)n FROM `{$p}fm2_assignment_order_applications`")->fetch_assoc()['n'],(int)$fresh->query("SELECT COUNT(*)n FROM `{$p}fm2_process_events` WHERE event_type='installation_opened_from_original'")->fetch_assoc()['n'],(int)$fresh->query("SELECT COUNT(*)n FROM `{$p}fm2_assignment_order_original_revisions`")->fetch_assoc()['n'],(int)$fresh->query("SELECT COUNT(*)n FROM `{$p}fm2_checklist_operations` WHERE operation_type='item_completed'")->fetch_assoc()['n'],(int)$fresh->query("SELECT COUNT(*)n FROM `{$p}fm2_checklist_photos`")->fetch_assoc()['n'],(int)$fresh->query("SELECT COUNT(*)n FROM `{$p}fm2_pilot_completion_facts`")->fetch_assoc()['n']];
        assertSameValue([1,1,2,41,7,2],$counts,'fresh connection append-only facts');
        $case=$fresh->query("SELECT process_state,actual_start_date,opened_by_user_id FROM `{$p}fm2_installation_cases` WHERE legacy_installation_object_id=4512")->fetch_assoc();
        assertSameValue(['process_state'=>'working','actual_start_date'=>'2026-09-07','opened_by_user_id'=>'19'],$case,'distinct opener durable projection');
        assertSameValue($authorityBefore,pefProtectedAuthoritySnapshot($fresh,$p),'whole browser journey preserves exact legacy and workforce authority rows');
        assertSameValue('foreign protected sentinel',file_get_contents($foreignSentinel),'whole browser journey preserves foreign filesystem sibling');
    }finally{$fresh->close();}
}

[$root,$node,$playwright]=pefProtectedPreflight();pefProtectedRetainedContracts();$fixture=pefProtectedFixture();
try{
    pefProtectedSeed($fixture,'Synthetic protected E2E 2026');$authorityBefore=pefProtectedAuthoritySnapshot($fixture->original->selection->db,$fixture->original->selection->prefix);
    $foreignDirectory=dirname($fixture->original->control).'/protected-current-foreign-'.bin2hex(random_bytes(6));if(!mkdir($foreignDirectory,0700))throw new TestFailure('SETUP_FAILURE: foreign sentinel directory');$foreignSentinel=$foreignDirectory.'/keep';file_put_contents($foreignSentinel,'foreign protected sentinel');chmod($foreignSentinel,0600);
    $observed=pefProtectedBrowser($fixture,$root,$node,$playwright);
    assertSameValue(['PASS',85,'100',41,7,0,true],[$observed['result'],$observed['workProgress'],$observed['finalProgress'],$observed['workItems'],$observed['photos'],$observed['errors'],$observed['separateOpener']],'current protected browser result');
    assertSameValue([0,64,64],[($observed['downloads']??-1),strlen((string)($observed['templateSha256']??'')),strlen((string)($observed['originalSha256']??''))],'browser download and exact digest evidence present');
    pefProtectedOriginalReadOnly($fixture);
    pefProtectedFreshFacts($fixture,$authorityBefore,$foreignSentinel);echo"PASS: PILOT-E2E-FLOW-001 current protected browser journey\n";
}finally{if(isset($foreignSentinel)&&is_file($foreignSentinel))unlink($foreignSentinel);if(isset($foreignDirectory)&&is_dir($foreignDirectory))rmdir($foreignDirectory);$fixture->close();}
