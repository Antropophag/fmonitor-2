<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

/** PILOT-JOBS-STARTUP-001: ordinary root startup uses native Jobs for the active pilot. */
$root=dirname(__DIR__,2);$tmp=sys_get_temp_dir().'/fm2-pilot-jobs-startup-'.bin2hex(random_bytes(6));

function pjsRun(array $command,array $environment,string $cwd,int $timeout=10):array
{
    $pipes=[];$process=proc_open($command,[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd,$environment);
    if(!is_resource($process))throw new TestFailure('SETUP_FAILURE process');
    foreach($pipes as$pipe)stream_set_blocking($pipe,false);$stdout='';$stderr='';$deadline=microtime(true)+$timeout;$exit=null;
    do{$stdout.=stream_get_contents($pipes[1]);$stderr.=stream_get_contents($pipes[2]);$status=proc_get_status($process);if(!$status['running']){$exit=$status['exitcode'];break;}if(microtime(true)>=$deadline){proc_terminate($process,9);throw new TestFailure('SETUP_FAILURE command timeout');}usleep(10000);}while(true);
    $stdout.=stream_get_contents($pipes[1]);$stderr.=stream_get_contents($pipes[2]);foreach($pipes as$pipe)fclose($pipe);$closed=proc_close($process);
    return[$exit!==null&&$exit>=0?$exit:$closed,$stdout,$stderr];
}
function pjsRemoveTree(string $path):void
{
    if(!is_dir($path))return;foreach(scandir($path)?:[]as$name){if($name==='.'||$name==='..')continue;$target=$path.'/'.$name;if(is_dir($target)&&!is_link($target))pjsRemoveTree($target);else@unlink($target);}@rmdir($path);
}

if(!mkdir($tmp.'/state/pilot-demo/one',0700,true)||!mkdir($tmp.'/temp',0700,true))throw new RuntimeException('fixture setup failed');
try{
    $composeEnvironment=array_replace(getenv(),['FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD'=>'fixture-only']);
    [$exit,$json,$stderr]=pjsRun(['docker','compose','-f','compose.yaml','config','--format','json'],$composeEnvironment,$root);
    assertSameValue([0,''],[$exit,$stderr],'root Compose resolves');$model=json_decode($json,true,512,JSON_THROW_ON_ERROR);
    $expected=['workforce-sync'=>'worker','workforce-scheduler'=>'scheduler'];$commands=[];
    foreach($expected as$serviceName=>$mode){
        $service=$model['services'][$serviceName]??null;assertSameValue(true,is_array($service),'ordinary root topology contains '.$serviceName);
        assertSameValue(['php','bin/yii','jobs/'.$mode,'--interactive=0'],$service['entrypoint']??null,$serviceName.' invokes exact Yii Jobs mode');
        assertSameValue(['SIGTERM',true],[$service['stop_signal']??null,in_array($service['stop_grace_period']??null,['60s','1m0s'],true)],$serviceName.' preserves native graceful shutdown');
        assertSameValue(['CMD','php','bin/yii','jobs/health','--interactive=0'],$service['healthcheck']['test']??null,$serviceName.' health uses native Jobs through Yii console');
        assertSameValue(false,str_contains(json_encode($service,JSON_THROW_ON_ERROR),'/tmp/workforce-ready'),$serviceName.' has no stale file readiness');
        $commands[$mode]=$service['entrypoint'];
    }
    $make=file_get_contents($root.'/Makefile')?:'';assertSameValue(true,(bool)preg_match('/\$\(COMPOSE\) up --detach --wait --no-deps --force-recreate workforce-sync workforce-scheduler/',$make),'make up waits for worker and scheduler together');

    $config=$tmp.'/config.json';file_put_contents($config,json_encode(['baseUrl'=>'https://example.invalid/rest/7/FIXTURE_TOKEN_123/','departments'=>[72,71]],JSON_THROW_ON_ERROR));
    file_put_contents($tmp.'/state/pilot-demo/one/active.json',json_encode(['state'=>'ready','processPrefix'=>'pilot_'],JSON_THROW_ON_ERROR));
    $base=array_replace(getenv(),['PATH'=>(string)getenv('PATH'),'TMPDIR'=>$tmp.'/temp','FMONITOR_SESSION_STATE_ROOT'=>$tmp.'/state','FMONITOR_BITRIX_CONFIG'=>$config,'FMONITOR_DB_HOST'=>'127.0.0.1','FMONITOR_DB_PORT'=>'1','FMONITOR_DB_NAME'=>'none','FMONITOR_DB_USER'=>'none','FMONITOR_DB_PASSWORD'=>'none','FMONITOR_SESSION_INSTANCE'=>'pilot']);
    foreach(['worker','scheduler']as$mode){[$code,$out,$err]=pjsRun($commands[$mode],$base,$root);assertSameValue([70,"{\"ok\":false,\"error\":\"JOBS_UNAVAILABLE\"}\n",''],[$code,$out,$err],$mode.' resolves the active prefix and reaches native Jobs');}
    [$code,$out,$err]=pjsRun(array_slice($model['services']['workforce-sync']['healthcheck']['test'],1),$base,$root);assertSameValue([70,"{\"ok\":false,\"error\":\"JOBS_UNAVAILABLE\"}\n",''],[$code,$out,$err],'health resolves the same active prefix and reaches native Jobs');
    assertSameValue([],array_values(array_diff(scandir($tmp.'/temp')?:[],['.','..'])),'worker removes its private staged token after native failure');

    @unlink($tmp.'/state/pilot-demo/one/active.json');
    foreach(['worker','scheduler','health']as$mode){$command=$mode==='health'?array_slice($model['services']['workforce-sync']['healthcheck']['test']??[],1):($commands[$mode]??[]);[$code,$out,$err]=pjsRun($command,$base,$root);assertSameValue([64,"{\"ok\":false,\"error\":\"CONFIGURATION_INVALID\"}\n",''],[$code,$out,$err],$mode.' rejects a missing manifest before Jobs');}
    file_put_contents($tmp.'/state/pilot-demo/one/active.json',json_encode(['state'=>'ready','processPrefix'=>'pilot_'],JSON_THROW_ON_ERROR));mkdir($tmp.'/state/pilot-demo/two',0700);file_put_contents($tmp.'/state/pilot-demo/two/active.json',json_encode(['state'=>'ready','processPrefix'=>'other_'],JSON_THROW_ON_ERROR));
    [$code,$out,$err]=pjsRun($commands['scheduler'],$base,$root);assertSameValue([64,"{\"ok\":false,\"error\":\"CONFIGURATION_INVALID\"}\n",''],[$code,$out,$err],'ambiguous ready manifests fail before Jobs');
    @unlink($tmp.'/state/pilot-demo/two/active.json');@rmdir($tmp.'/state/pilot-demo/two');file_put_contents($tmp.'/state/pilot-demo/one/active.json',json_encode(['state'=>'preparing','processPrefix'=>'pilot_'],JSON_THROW_ON_ERROR));
    [$code,$out,$err]=pjsRun($commands['worker'],$base,$root);assertSameValue([64,"{\"ok\":false,\"error\":\"CONFIGURATION_INVALID\"}\n",''],[$code,$out,$err],'non-ready manifest fails before config staging or Jobs');
    assertSameValue([],array_values(array_diff(scandir($tmp.'/temp')?:[],['.','..'])),'rejected manifest leaves no staged token');
    echo "pilot_jobs_startup_001_test: PASS\n";
}finally{pjsRemoveTree($tmp);}
