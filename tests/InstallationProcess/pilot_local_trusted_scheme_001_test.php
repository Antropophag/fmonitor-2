<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/autoload.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\SelectionHttpFixture;
use FMonitor\IdentityAccess as S;
// PILOT-LOCAL-TRUSTED-SCHEME-001. FMONITOR_TEST_DB: task-owned real-router fixture.
$root=dirname(__DIR__,2);$errors=[];$pipes=[];
$environment=array_replace(getenv(),['FMONITOR_BOOTSTRAP_SUPERADMIN_EMAILS'=>'config-only@shlz.ru','FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD'=>'synthetic-config-only-password','FMONITOR_TRUSTED_REQUEST_SCHEME'=>'https']);
$process=proc_open(['docker','compose','--env-file','/dev/null','-f',$root.'/compose.yaml','config','--format','json'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$environment);
if(!is_resource($process))throw new TestFailure('Compose config setup');$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);assertSameValue(0,$exit,'healthy effective Compose config');assertSameValue('',$err,'no Compose config diagnostics');
$config=json_decode($out,true,512,JSON_THROW_ON_ERROR);$pilot=$config['services']['pilot'];$scheme=$pilot['environment']['FMONITOR_TRUSTED_REQUEST_SCHEME']??null;
assertSameValue([['mode'=>'ingress','host_ip'=>'127.0.0.1','target'=>8092,'published'=>'8092','protocol'=>'tcp']],$pilot['ports'],'existing exact loopback transport');
foreach(['compose'=>'effective','missing'=>''] as $name=>$mode){$f=null;
 try{
    $actual=$mode==='effective'?$scheme:'';
    $f=new SelectionHttpFixture(true,static fn()=>['FMONITOR_TRUSTED_REQUEST_SCHEME'=>$actual??'','FMONITOR_NOW'=>'2026-09-07T09:00:00+03:00']);
    $f->original->selection->schema->insert('fm2_pilot_role_permissions',['role_id'=>4,'permission'=>'access.administer']);
    assertSameValue(200,$f->request('GET','/pilot/admin/roles','',99)['status'],'SETUP_OK healthy authorized native directory, CSS and resources');echo "SETUP_OK $name\n";
    $before=$f->original->selection->rows();$files=$f->original->privateFiles();$sessions=[];foreach(glob($f->stateRoot.'/sessions/pilot/*.session') as $path)$sessions[$path]=hash_file('sha256',$path);
    $response=$f->request('GET','/pilot/admin/users','',99,['X-Forwarded-Proto'=>'http']);
    if($mode==='effective'){
        assertSameValue(200,$response['status'],'INTENDED_RED: effective local Compose scheme must permit native Users GET');assertSameValue('http',$scheme,'literal local trusted http ignores ambient https');
        assertSameValue(true,str_contains($response['body'],'Пользователи'),'actual users page');preg_match_all('/name="csrfToken" value="([0-9a-f]{32})"/',$response['body'],$m);$tokens=array_values(array_unique($m[1]));assertSameValue(true,count($tokens)>0,'real action tokens rendered');
        $found=false;foreach(array_keys($sessions) as $path){$id=substr(basename($path),2,-8);$owner=(new S\PilotSessionStorageFactory())->create(new S\PilotSessionStorageConfig($f->stateRoot,'pilot'),new S\NativePilotSessionFilesystem(),new S\SystemPilotSessionClock(),new S\CsprngPilotSessionEntropy(),new S\NoOpPilotSessionLifecycleObserver());
            try{$read=$owner->start($id);assertSameValue('OK',$read->status()->name,'public owner reads committed session');$state=unserialize($read->sessionPayload(),['allowed_classes'=>false]);if(($state['auth_user_id']??null)!==99)continue;$found=true;foreach($tokens as $token){assertSameValue(99,$state['tokens'][$token]['actor']??null,'each published action token was committed for actor');assertSameValue(true,in_array($state['tokens'][$token]['id']??null,[18,31,73,99],true),'token targets actual directory user');}}
            finally{$owner->close();}
        }assertSameValue(true,$found,'actual native administrator session observed');assertSameValue(200,$f->request('GET','/pilot/admin/users','',99)['status'],'repeat GET remains usable');
    }else{
        assertSameValue([503,"Service unavailable.\n"],[$response['status'],$response['body']],'missing trusted scheme remains failclosed despite forwarded header');
        foreach($sessions as $path=>$hash)assertSameValue($hash,hash_file('sha256',$path),'failed GET does not publish tokens or mutate sessions');
    }
    assertSameValue($before,$f->original->selection->rows(),'Users GET writes no DB facts, roles or grants');assertSameValue($files,$f->original->privateFiles(),'Users GET leaves original storage intact');
    echo "PASS $name\n";
 }catch(Throwable $e){$errors[]=$name.': '.$e->getMessage();}
 finally{if($f!==null){$f->close();echo "CLEANUP_OK $name\n";}}
}
foreach($errors as $error)echo "FAIL $error\n";exit($errors===[]?0:1);
