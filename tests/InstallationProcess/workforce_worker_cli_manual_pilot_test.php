<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\Workforce\WorkerConfiguration;

$root=dirname(__DIR__,2);$tmp=sys_get_temp_dir().'/fm2-worker-cli-'.bin2hex(random_bytes(5));
if(!mkdir($tmp.'/state/pilot-demo/one',0700,true))throw new RuntimeException('fixture setup failed');
try{
    $config=$tmp.'/config.json';file_put_contents($config,json_encode(['baseUrl'=>'https://example.invalid/rest/7/FAKE_TOKEN_123/','departments'=>['72',71]],JSON_THROW_ON_ERROR));
    $parsed=WorkerConfiguration::fromFile($config);assertSameValue(['https://example.invalid',7,[71,72]],[$parsed['origin'],$parsed['webhookUserId'],$parsed['departmentIds']],'private webhook document maps to bounded native inputs');
    foreach(['https://user@example.invalid/rest/7/TOKEN/','https://example.invalid/rest/7/TOKEN/?query=1','https://example.invalid/rest/7/TOKEN/#fragment']as$bad){file_put_contents($config,json_encode(['baseUrl'=>$bad,'departments'=>[71]],JSON_THROW_ON_ERROR));try{WorkerConfiguration::fromFile($config);throw new TestFailure('unauthorized URL component accepted');}catch(RuntimeException$e){assertSameValue('CONFIGURATION_INVALID',$e->getMessage(),'unauthorized URL component rejected safely');}}
    file_put_contents($config,json_encode(['baseUrl'=>'https://example.invalid/rest/7/FAKE_TOKEN_123/','departments'=>['72',71]],JSON_THROW_ON_ERROR));
    $token=WorkerConfiguration::stageToken($parsed['token']);$stat=lstat($token);assertSameValue([0600,1,posix_geteuid()],[$stat['mode']&07777,$stat['nlink'],$stat['uid']],'staged token exact private metadata');unlink($token);
    file_put_contents($tmp.'/state/pilot-demo/one/active.json',json_encode(['state'=>'ready','processPrefix'=>'pilot_'],JSON_THROW_ON_ERROR));
    $before=glob(sys_get_temp_dir().'/fm2-workforce-token-*')?:[];$ready=$tmp.'/ready';$command=['sh',$root.'/rapid-pilot/workforce-worker.sh','--once'];$env=$_ENV+['PATH'=>(string)getenv('PATH'),'FMONITOR_SESSION_STATE_ROOT'=>$tmp.'/state','FMONITOR_WORKFORCE_READY_FILE'=>$ready,'FMONITOR_BITRIX_CONFIG'=>$config,'FMONITOR_DB_HOST'=>'127.0.0.1','FMONITOR_DB_PORT'=>'1','FMONITOR_DB_NAME'=>'none','FMONITOR_DB_USER'=>'none','FMONITOR_DB_PASSWORD'=>'none'];$pipes=[];$process=proc_open($command,[['pipe','r'],['pipe','w'],['pipe','w']],$pipes,$root,$env);fclose($pipes[0]);$stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
    assertSameValue([1,"{\"status\":\"failed\",\"reason\":\"SYNC_UNAVAILABLE\"}\n",''],[$exit,$stdout,$stderr],'public hourly CLI reaches native command and fails safely before network when DB is unavailable');assertSameValue($before,glob(sys_get_temp_dir().'/fm2-workforce-token-*')?:[],'staged token removed after failed run');assertSameValue(false,file_exists($ready),'failed run does not publish readiness');
    echo "workforce_worker_cli_manual_pilot_test: PASS\n";
}finally{foreach(glob($tmp.'/state/pilot-demo/one/*')?:[]as$file)@unlink($file);@rmdir($tmp.'/state/pilot-demo/one');@rmdir($tmp.'/state/pilot-demo');@rmdir($tmp.'/state');@unlink($tmp.'/config.json');@rmdir($tmp);}
