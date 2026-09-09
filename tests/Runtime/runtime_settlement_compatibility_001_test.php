<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Support/RecoveryContainerNetwork.php';
use FMonitor2\Tests\Support\RecoveryContainerNetwork;

$root=dirname(__DIR__,2);
function rscCommand(array $command,string $directory,string $log):int {
    $process=proc_open($command,[0=>['file','/dev/null','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes,$directory);
    if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: process');
    return proc_close($process);
}
function rscRemove(string $path):void {
    if(is_file($path)||is_link($path)){unlink($path);return;}
    if(!is_dir($path))return;
    foreach(scandir($path)?:[] as $entry)if($entry!=='.'&&$entry!=='..')rscRemove($path.'/'.$entry);
    rmdir($path);
}
if(getenv('FMONITOR_RUNTIME_SETTLEMENT_CHILD')!=='1') {
    $network=RecoveryContainerNetwork::forHost(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1');
    $tag='fmonitor2-settlement-compat:'.bin2hex(random_bytes(5));
    $log=sys_get_temp_dir().'/fmonitor-runtime-settlement-'.bin2hex(random_bytes(5)).'.log';
    try {
        assertSameValue(0,rscCommand(['docker','build','-f','deploy/runtime/Dockerfile','-t',$tag,'.'],$root,$log),'runtime build; log '.$log);
        $command=['docker','run','--rm',...$network['arguments'],'-v',$root.'/tests:/workspace/fmonitor-2/tests:ro','-w','/workspace/fmonitor-2'];
        foreach(['FMONITOR_RUNTIME_SETTLEMENT_CHILD'=>'1','FMONITOR_TEST_EXPECTED_LOCK'=>hash_file('sha256',$root.'/composer.lock'),'FMONITOR_TEST_DB_HOST'=>$network['databaseHost'],'FMONITOR_TEST_DB_PORT'=>getenv('FMONITOR_TEST_DB_PORT')?:'23306','FMONITOR_TEST_DB_ADMIN_USER'=>getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root','FMONITOR_TEST_DB_ADMIN_PASSWORD'=>getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local'] as $key=>$value){$command[]='-e';$command[]=$key.'='.$value;}
        $exit=rscCommand([...$command,'--entrypoint','php',$tag,'-d','display_errors=1','-d','log_errors=0','tests/Runtime/runtime_settlement_compatibility_001_test.php'],$root,$log);
        assertSameValue(0,$exit,'packaged runtime public seam; log '.$log.'; '.substr((string)file_get_contents($log),-6000));
        echo "PASS: OTIZ-SETTLEMENT-001 locked runtime and retained HTTP owner; log {$log}\n";
    } finally {rscCommand(['docker','image','rm',$tag],$root,$log);}
    exit(0);
}

// Only tests are mounted. Composer lock, vendor, application and router must be in the built image.
assertSameValue(true,is_file($root.'/composer.lock'),'INTENDED_RED: runtime image carries the shared Composer lock');
assertSameValue(getenv('FMONITOR_TEST_EXPECTED_LOCK'),hash_file('sha256',$root.'/composer.lock'),'image lock equals the source lock');
require $root.'/vendor/autoload.php';
assertSameValue(true,class_exists(Composer\InstalledVersions::class),'real Composer autoloader');
foreach(['yiisoft/yii2','tecnickcom/tcpdf'] as $name) {
    $packages=json_decode((string)file_get_contents($root.'/composer.lock'),true,flags:JSON_THROW_ON_ERROR)['packages'];
    $expected=array_values(array_filter($packages,static fn(array $package):bool=>$package['name']===$name));
    assertSameValue(1,count($expected),'one locked dependency '.$name);
    assertSameValue($expected[0]['version'],Composer\InstalledVersions::getPrettyVersion($name),'installed locked version '.$name);
}
foreach(['mysqli','pdo_mysql','pcntl'] as $extension)assertSameValue(true,extension_loaded($extension),'packaged extension '.$extension);
assertSameValue(true,class_exists(TCPDF::class),'TCPDF remains autoloadable');
$platformLog=sys_get_temp_dir().'/runtime-platform-'.bin2hex(random_bytes(4)).'.log';
assertSameValue(0,rscCommand(['composer','check-platform-reqs','--no-dev'],$root,$platformLog),'real installed platform requirements');
require dirname(__DIR__).'/Yii2/Yii2AuthFixture.php';
use FMonitor2\Tests\Yii2\Yii2AuthFixture;

function rscRequest(int $port,string $method,string $path,array $fields,array &$cookies):array {
    $headers=['Connection: close'];
    if($cookies!==[])$headers[]='Cookie: '.implode('; ',array_map(static fn($key,$value)=>$key.'='.$value,array_keys($cookies),$cookies));
    if($fields!==[])$headers[]='Content-Type: application/x-www-form-urlencoded';
    $context=stream_context_create(['http'=>['ignore_errors'=>true,'follow_location'=>0,'timeout'=>8,'method'=>$method,'header'=>implode("\r\n",$headers),'content'=>$fields===[]?'':http_build_query($fields)]]);
    $body=file_get_contents('http://127.0.0.1:'.$port.$path,false,$context);$raw=$http_response_header??[];
    preg_match('#^HTTP/\S+ (\d+)#',$raw[0]??'',$match);$status=(int)($match[1]??0);$location=null;
    foreach(array_slice($raw,1) as $header){if(preg_match('/^Set-Cookie: ([^=;]+)=([^;]*)/i',$header,$match))$cookies[$match[1]]=$match[2];if(str_starts_with(strtolower($header),'location:'))$location=trim(substr($header,9));}
    return ['status'=>$status,'location'=>$location,'body'=>(string)$body];
}
function rscField(string $html,string $name):string {
    if(preg_match('/<input\\b[^>]*\\bname="'.preg_quote($name,'/').'"[^>]*\\bvalue="([^"]*)"/s',$html,$match)!==1)throw new TestFailure('rendered field missing: '.$name);
    return html_entity_decode($match[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
}
$fixture=null;$server=null;$accountCreated=false;
$private=sys_get_temp_dir().'/runtime-otiz-'.bin2hex(random_bytes(5));
$account='runtime_otiz_'.bin2hex(random_bytes(5));$password=bin2hex(random_bytes(20));
try {
    $fixture=new Yii2AuthFixture($root);$fixture->setPermission('otiz.manage');$db=$fixture->db;$p=$fixture->prefix;
    foreach([[301,100000],[302,150000]] as [$snapshot,$amount]) {
        $db->query("INSERT INTO {$p}fm2_pilot_otiz_snapshots(id,report_date,status,rules_version,calculated_at,calculated_by_user_id,accepted_at,accepted_by_user_id,total_pool_cents,total_closed_cents,total_available_cents,content_hash) VALUES({$snapshot},'2026-09-08','accepted','premium-calculation-v1','2026-09-09T09:00:00Z',9101,'2026-09-09T09:30:00Z',9101,{$amount},0,{$amount},REPEAT('d',64))");
        $db->query("INSERT INTO {$p}fm2_pilot_otiz_snapshot_objects(snapshot_id,object_id,regnumber,address,previous_progress_bp,current_progress_bp,progress_fact_date,premium_cents,shaft_bp,kss_bp,accrued_cents,fund_cents,closed_before_cents,remaining_cents,pool_cents,distributed_cents,undistributed_cents,calculation_state,inputs_json) VALUES({$snapshot},7301,'COMPAT-1','Synthetic compatibility object',0,10000,'2026-09-08',150000,10000,10000,{$amount},150000,0,{$amount},{$amount},{$amount},0,'ready','{}')");
    }
    $db->query("INSERT INTO {$p}fm2_pilot_otiz_payment_closures(id,snapshot_id,object_id,closed_on,paid_cents,discipline_cents,deadline_cents,basis,artifact,created_by_user_id,created_at) VALUES(401,301,7301,'2026-09-09',100000,0,0,'Earlier accepted payout','',9101,'2026-09-09T10:00:00Z')");
    $original=$db->query("SELECT * FROM {$p}fm2_pilot_otiz_payment_closures WHERE id=401")->fetch_assoc();
    $db->query("CREATE USER '{$account}'@'%' IDENTIFIED BY '{$password}'");$accountCreated=true;
    $db->query("GRANT SELECT,INSERT,UPDATE,DELETE ON `{$fixture->database}`.* TO '{$account}'@'%'");
    foreach(['','/state','/state/sessions','/state/sessions/compat','/state/artifacts','/state/log'] as $suffix)mkdir($private.$suffix,0700);
    file_put_contents($private.'/password',$password);chmod($private.'/password',0600);
    file_put_contents($private.'/state/log/safe.log','');chmod($private.'/state/log/safe.log',0600);
    $socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$error);if(!is_resource($socket))throw new TestFailure('SETUP_FAILURE: port');
    $address=stream_socket_get_name($socket,false);$port=(int)substr($address,strrpos($address,':')+1);fclose($socket);
    $environment=getenv();foreach(array_keys($environment) as $key)if(str_starts_with($key,'FMONITOR_'))unset($environment[$key]);
    $environment=array_replace($environment,$fixture->environment(),['FMONITOR_DB_USER'=>$account,'FMONITOR_DB_PASSWORD'=>$password,'FMONITOR_LEGACY_TABLE_PREFIX'=>$p,'FMONITOR_SESSION_STATE_ROOT'=>$private.'/state','FMONITOR_SESSION_INSTANCE'=>'compat','FMONITOR_ARTIFACT_STORAGE_ROOT'=>$private.'/state/artifacts','FMONITOR_ORIGINAL_DB_PASSWORD_FILE'=>$private.'/password','FMONITOR_ORIGINAL_SAFE_LOG_FILE'=>$private.'/state/log/safe.log','FMONITOR_TRUSTED_REQUEST_HOST'=>'127.0.0.1:'.$port,'FMONITOR_TRUSTED_REQUEST_SCHEME'=>'http']);
    $server=proc_open([PHP_BINARY,'-d','display_errors=0','-S','127.0.0.1:'.$port,$root.'/public/runtime.php'],[0=>['file','/dev/null','r'],1=>['file',$private.'/server.log','a'],2=>['file',$private.'/server.log','a']],$pipes,$root,$environment);
    if(!is_resource($server))throw new TestFailure('SETUP_FAILURE: test HTTP server');
    $ready=false;$deadline=microtime(true)+5;do{$socket=@fsockopen('127.0.0.1',$port,$errno,$error,.1);if(is_resource($socket)){fclose($socket);$ready=true;break;}usleep(20000);}while(microtime(true)<$deadline);
    assertSameValue(true,$ready,'test HTTP listener ready');
    $cookies=[];$page=rscRequest($port,'GET','/pilot/login',[],$cookies);assertSameValue(200,$page['status'],'retained login in packaged runtime');
    $page=rscRequest($port,'POST','/pilot/login',['csrfToken'=>rscField($page['body'],'csrfToken'),'email'=>$fixture->email],$cookies);
    $login=rscRequest($port,'POST','/pilot/login',['csrfToken'=>rscField($page['body'],'csrfToken'),'email'=>$fixture->email,'password'=>$fixture->password],$cookies);assertSameValue(303,$login['status'],'real retained authentication');
    $page=rscRequest($port,'GET','/pilot/otiz/snapshots/302',[],$cookies);assertSameValue(200,$page['status'],'retained accepted snapshot');
    $csrf=rscField($page['body'],'csrfToken');$operation='90000000-0000-4000-8000-000000000002';
    $counts=static fn():array=>[(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_pilot_otiz_payment_closures")->fetch_column(),(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_pilot_otiz_events")->fetch_column(),(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_otiz_settlement_operations")->fetch_column()];
    $before=$counts();$denied=rscRequest($port,'POST','/pilot/otiz/snapshots/302/payments/complete',['csrfToken'=>'invalid','operationId'=>$operation],$cookies);assertSameValue(403,$denied['status'],'retained CSRF refusal');assertSameValue($before,$counts(),'CSRF adds no facts');
    $paid=rscRequest($port,'POST','/pilot/otiz/snapshots/302/payments/complete',['csrfToken'=>$csrf,'operationId'=>$operation],$cookies);
    assertSameValue([303,'/pilot/otiz/snapshots/302?paid=1'],[$paid['status'],$paid['location']],'retained route reaches canonical owner in built runtime');
    assertSameValue([2,2,1],$counts(),'one new closure, two events and one receipt');
    assertSameValue(50000,(int)$db->query("SELECT paid_cents FROM {$p}fm2_pilot_otiz_payment_closures WHERE snapshot_id=302")->fetch_column(),'A02 appends only the cross-snapshot remainder');
    assertSameValue($original,$db->query("SELECT * FROM {$p}fm2_pilot_otiz_payment_closures WHERE id=401")->fetch_assoc(),'prior payout immutable');
    assertSameValue($operation,$db->query("SELECT operation_id FROM {$p}fm2_otiz_settlement_operations")->fetch_column(),'retained request persisted canonical receipt');
    $replay=rscRequest($port,'POST','/pilot/otiz/snapshots/302/payments/complete',['csrfToken'=>$csrf,'operationId'=>$operation],$cookies);assertSameValue([$paid['status'],$paid['location']],[$replay['status'],$replay['location']],'exact compatibility replay stable');assertSameValue([2,2,1],$counts(),'replay appends nothing');
    $page=rscRequest($port,'GET',$paid['location'],[],$cookies);assertSameValue(200,$page['status'],'retained return screen');assertSameValue(true,str_contains($page['body'],'Выплаты выполнены'),'retained view uses global budget');assertSameValue(false,str_contains($page['body'],'action="/pilot/otiz/snapshots/302/payments/complete"'),'no misleading remaining-payment form');
    echo "RUNTIME_SETTLEMENT_COMPATIBILITY_OK\n";
} finally {
    if(is_resource($server)){proc_terminate($server);proc_close($server);}
    if($fixture instanceof Yii2AuthFixture){if($accountCreated)$fixture->db->query("DROP USER '{$account}'@'%'");$fixture->close();}
    rscRemove($private);
}
