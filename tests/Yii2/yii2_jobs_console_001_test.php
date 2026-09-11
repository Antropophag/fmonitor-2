<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

/** YII2-JOBS-CONSOLE-001: real Yii console and production Compose boundary. */
$root=dirname(__DIR__,2);$tmp=sys_get_temp_dir().'/fm2-yii-jobs-'.bin2hex(random_bytes(6));
function yjcRun(array $command,array $environment,string $cwd,int $timeout=10):array{$pipes=[];$process=proc_open($command,[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd,$environment);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE process');foreach($pipes as$pipe)stream_set_blocking($pipe,false);$stdout='';$stderr='';$deadline=microtime(true)+$timeout;$exit=null;do{$stdout.=stream_get_contents($pipes[1]);$stderr.=stream_get_contents($pipes[2]);$status=proc_get_status($process);if(!$status['running']){$exit=$status['exitcode'];break;}if(microtime(true)>=$deadline){proc_terminate($process,9);throw new TestFailure('SETUP_FAILURE timeout');}usleep(10000);}while(true);$stdout.=stream_get_contents($pipes[1]);$stderr.=stream_get_contents($pipes[2]);foreach($pipes as$pipe)fclose($pipe);$closed=proc_close($process);return[$exit!==null&&$exit>=0?$exit:$closed,$stdout,$stderr];}
function yjcRemove(string $path):void{if(!is_dir($path))return;foreach(scandir($path)?:[]as$name){if($name==='.'||$name==='..')continue;$target=$path.'/'.$name;if(is_dir($target)&&!is_link($target))yjcRemove($target);else@unlink($target);}@rmdir($path);}

if(!mkdir($tmp.'/state/pilot-demo/one',0700,true)||!mkdir($tmp.'/temp',0700,true))throw new TestFailure('SETUP_FAILURE fixture');
try{
    $composeEnv=array_replace(getenv(),['FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD'=>'fixture']);
    [$code,$json,$error]=yjcRun(['docker','compose','-f','compose.yaml','config','--format','json'],$composeEnv,$root);
    assertSameValue([0,''],[$code,$error],'Compose resolves');$model=json_decode($json,true,512,JSON_THROW_ON_ERROR);
    $commands=[];
    foreach(['workforce-sync'=>'jobs/worker','workforce-scheduler'=>'jobs/scheduler']as$serviceName=>$route){
        $service=$model['services'][$serviceName]??null;assertSameValue(true,is_array($service),'service exists '.$serviceName);
        $expected=['php','bin/yii',$route,'--interactive=0'];
        assertSameValue($expected,$service['entrypoint']??null,'INTENTIONAL_RED: production process enters through Yii2 console '.$route);
        assertSameValue(['CMD','php','bin/yii','jobs/health','--interactive=0'],$service['healthcheck']['test']??null,'health enters through Yii2 console');
        assertSameValue(['SIGTERM',true],[$service['stop_signal']??null,in_array($service['stop_grace_period']??null,['60s','1m0s'],true)],'graceful shutdown is preserved');
        assertSameValue(false,str_contains(json_encode([$service['entrypoint'],$service['healthcheck']],JSON_THROW_ON_ERROR),'rapid-pilot'),'production Jobs boundary has no rapid-pilot entrypoint');
        $commands[$route]=$expected;
    }

    $secret='YII_JOBS_SECRET_'.bin2hex(random_bytes(8));$config=$tmp.'/config.json';file_put_contents($config,json_encode(['baseUrl'=>'https://example.invalid/rest/7/'.$secret.'/','departments'=>[72,71]],JSON_THROW_ON_ERROR));
    file_put_contents($tmp.'/state/pilot-demo/one/active.json',json_encode(['state'=>'ready','processPrefix'=>'pilot_'],JSON_THROW_ON_ERROR));
    $env=array_replace(getenv(),['TMPDIR'=>$tmp.'/temp','FMONITOR_SESSION_STATE_ROOT'=>$tmp.'/state','FMONITOR_BITRIX_CONFIG'=>$config,'FMONITOR_DB_HOST'=>'127.0.0.1','FMONITOR_DB_PORT'=>'1','FMONITOR_DB_NAME'=>'none','FMONITOR_DB_USER'=>'none','FMONITOR_DB_PASSWORD'=>'none','FMONITOR_SESSION_INSTANCE'=>'pilot']);
    foreach($commands as$route=>$command){[$exit,$out,$err]=yjcRun($command,$env,$root);assertSameValue([70,"{\"ok\":false,\"error\":\"JOBS_UNAVAILABLE\"}\n",''],[$exit,$out,$err],$route.' reaches existing Jobs owner with closed failure');assertSameValue(false,str_contains($out.$err,$secret),$route.' does not leak secret');}
    [$exit,$out,$err]=yjcRun(['php','bin/yii','jobs/health','--interactive=0'],$env,$root);assertSameValue([70,"{\"ok\":false,\"error\":\"JOBS_UNAVAILABLE\"}\n",''],[$exit,$out,$err],'health reaches Jobs owner');
    assertSameValue([],array_values(array_diff(scandir($tmp.'/temp')?:[],['.','..'])),'worker staged secret is removed after failure');

    @unlink($tmp.'/state/pilot-demo/one/active.json');
    foreach(['jobs/worker','jobs/scheduler','jobs/health']as$route){[$exit,$out,$err]=yjcRun(['php','bin/yii',$route,'--interactive=0'],$env,$root);assertSameValue([64,"{\"ok\":false,\"error\":\"CONFIGURATION_INVALID\"}\n",''],[$exit,$out,$err],$route.' rejects missing manifest closed');}
    foreach([['unknown/route'],['jobs/health','--unexpected=1']]as$args){[$exit,$out,$err]=yjcRun(['php','bin/yii',...$args,'--interactive=0'],$env,$root);assertSameValue([64,"{\"ok\":false,\"error\":\"CONFIGURATION_INVALID\"}\n",''],[$exit,$out,$err],'unknown route/options fail closed');}
    $invalid=$env;$invalid['FMONITOR_SESSION_STATE_ROOT']='relative/state';[$exit,$out,$err]=yjcRun(['php','bin/yii','jobs/health','--interactive=0'],$invalid,$root);assertSameValue([64,"{\"ok\":false,\"error\":\"CONFIGURATION_INVALID\"}\n",''],[$exit,$out,$err],'relative state root fails closed');
    foreach([['preparing','pilot_'],['ready',''],['ready','bad-prefix!']]as[$state,$prefix]){file_put_contents($tmp.'/state/pilot-demo/one/active.json',json_encode(['state'=>$state,'processPrefix'=>$prefix],JSON_THROW_ON_ERROR));[$exit,$out,$err]=yjcRun(['php','bin/yii','jobs/health','--interactive=0'],$env,$root);assertSameValue([64,"{\"ok\":false,\"error\":\"CONFIGURATION_INVALID\"}\n",''],[$exit,$out,$err],'manifest state/prefix rejects closed');}
    file_put_contents($tmp.'/state/pilot-demo/one/active.json',json_encode(['state'=>'ready','processPrefix'=>'pilot_'],JSON_THROW_ON_ERROR));mkdir($tmp.'/state/pilot-demo/two',0700);file_put_contents($tmp.'/state/pilot-demo/two/active.json',json_encode(['state'=>'ready','processPrefix'=>'other_'],JSON_THROW_ON_ERROR));[$exit,$out,$err]=yjcRun(['php','bin/yii','jobs/scheduler','--interactive=0'],$env,$root);assertSameValue([64,"{\"ok\":false,\"error\":\"CONFIGURATION_INVALID\"}\n",''],[$exit,$out,$err],'ambiguous ready manifests fail closed');@unlink($tmp.'/state/pilot-demo/two/active.json');@rmdir($tmp.'/state/pilot-demo/two');
    $missingSecret=$env;$missingSecret['FMONITOR_BITRIX_CONFIG']=$tmp.'/absent-secret';foreach(['jobs/scheduler','jobs/health']as$route){[$exit,$out,$err]=yjcRun(['php','bin/yii',$route,'--interactive=0'],$missingSecret,$root);assertSameValue(70,$exit,$route.' never reads worker-only Bitrix config and reaches unavailable DB');}[$exit,$out,$err]=yjcRun(['php','bin/yii','jobs/worker','--interactive=0'],$missingSecret,$root);assertSameValue(64,$exit,'worker requires its private config');
    assertSameValue([],array_values(array_diff(scandir($tmp.'/temp')?:[],['.','..'])),'rejection creates no staged secret');

    $trace=$tmp.'/included.json';$wrapper=$tmp.'/trace.php';file_put_contents($wrapper,'<?php register_shutdown_function(static function(){file_put_contents('.var_export($trace,true).',json_encode(get_included_files(),JSON_THROW_ON_ERROR));});require '.var_export($root.'/bin/yii',true).';');$traceEnv=$env;[$exit,$out,$err]=yjcRun(['php',$wrapper,'jobs/health','--interactive=0'],$traceEnv,$root);assertSameValue(70,$exit,'instrumented real Yii health reaches Jobs owner');$loaded=json_decode((string)file_get_contents($trace),true,512,JSON_THROW_ON_ERROR);foreach($loaded as$file)assertSameValue(false,str_contains(str_replace('\\','/',$file),'/rapid-pilot/'),'actual Yii subprocess loaded-file closure excludes rapid-pilot: '.$file);
    $owned='';foreach(array_merge(glob($root.'/app/Jobs/*.php')?:[],glob($root.'/app/YiiRuntime/Commands/*.php')?:[],[$root.'/bin/yii',$root.'/bin/fmonitor2-job-handler.php'])as$file)$owned.=(string)file_get_contents($file);assertSameValue(false,str_contains($owned,'rapid-pilot'),'complete repository-owned Yii/Jobs command-construction inventory excludes rapid-pilot entrypoints');
    echo "PASS: YII2-JOBS-CONSOLE-001 Yii console boundary\n";
}finally{yjcRemove($tmp);}
