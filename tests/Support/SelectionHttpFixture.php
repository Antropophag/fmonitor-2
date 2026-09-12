<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
require_once dirname(__DIR__,2).'/app/autoload.php';
use FMonitor\IdentityAccess as S;

/** ASSIGNMENT-ORDER-COMPOSITION-HTTP-001: fictional DB and real router/session. */
final class SelectionHttpFixture
{
    public readonly SelectedOriginalFixture $original;
    public readonly int $port;
    public readonly string $stateRoot;
    public string $csrf;
    private mixed $server=null;
    private array $cookies=[];
    public function __construct(bool $enabled=true,?\Closure $extraEnvironment=null,string $prefix='',?string $router=null)
    {
        $this->original=new SelectedOriginalFixture($prefix);$this->csrf=str_repeat('c',64);
        try {
            $f=$this->original->selection;$db=$f->db;
            $db->query("UPDATE `{$prefix}fm_maintable` SET ordadr_address='Москва, Тестовая улица, 1',entrance='2',regnumber='77-000123',workdatestart='2026-10-05',plan_finish_date='2026-12-20'");
            $db->query("UPDATE `{$prefix}fm2_pilot_users` SET email='test18@shlz.ru' WHERE user_id=18");
            foreach([[31,3,'manager','Руководитель ФКР'],[99,4,'system_admin','Администратор']] as [$user,$role,$code,$name]) {
                $f->schema->insert($prefix.'fm2_pilot_users',['user_id'=>$user,'full_name'=>$name,'email'=>"test$user@shlz.ru",'status'=>1,'activation_state'=>'active','source_updated_at'=>'2026-09-01T06:00:00Z']);
                $f->schema->insert($prefix.'fm2_pilot_roles',['role_id'=>$role,'code'=>$code,'name'=>$name,'description'=>'fictional','status'=>1,'source_updated_at'=>'2026-09-01T06:00:00Z']);
                $f->schema->insert($prefix.'fm2_pilot_user_roles',['user_id'=>$user,'role_id'=>$role,'origin'=>'bootstrap','assigned_at'=>'2026-09-01T06:00:00Z']);
            }
            $f->schema->insert($prefix.'fm2_pilot_role_permissions',['role_id'=>3,'permission'=>'assignment_order.composition.select']);
            foreach([18,31,99] as $id)$f->schema->insert($prefix.'fm2_pilot_auth_credentials',['user_id'=>$id,'email_normalized'=>"test$id@shlz.ru",'password_hash'=>'fixture-not-used-for-login','updated_at'=>'2026-09-01T06:00:00Z']);
            $this->stateRoot=$this->original->control.'/session-state';if(!mkdir($this->stateRoot,0700))throw new \RuntimeException();
            $socket=stream_socket_server('tcp://127.0.0.1:0',$error,$message);if($socket===false)throw new \RuntimeException('Fixture port unavailable');
            $address=stream_socket_get_name($socket,false);$this->port=(int)substr($address,strrpos($address,':')+1);fclose($socket);
            foreach([18,31,99] as $id){
                $owner=(new S\PilotSessionStorageFactory())->create(new S\PilotSessionStorageConfig($this->stateRoot,'pilot'),new S\NativePilotSessionFilesystem(),new S\SystemPilotSessionClock(),new S\CsprngPilotSessionEntropy(),new S\NoOpPilotSessionLifecycleObserver());
                $session=$owner->start(null);$sid=$session->currentSessionId();
                $payload=serialize(['auth_user_id'=>$id,'auth_email'=>"test$id@shlz.ru",'auth_csrf'=>$this->csrf]);
                \assertSameValue('OK',$owner->writeCommit($sid,$payload)->status()->name,'native session setup');$owner->close();
                $this->cookies[$id]='fm2auth_'.$this->port.'='.$sid;
            }
            $root=dirname(__DIR__,2);
            $env=array_replace(getenv(),['FMONITOR_DB_HOST'=>getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',
                'FMONITOR_DB_PORT'=>getenv('FMONITOR_TEST_DB_PORT')?:'23306','FMONITOR_DB_NAME'=>$f->schema->source->name,
                'FMONITOR_DB_USER'=>getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root','FMONITOR_DB_PASSWORD'=>getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',
                'FMONITOR_PROCESS_TABLE_PREFIX'=>$prefix,'FMONITOR_LEGACY_TABLE_PREFIX'=>$prefix,'FMONITOR_SESSION_STATE_ROOT'=>$this->stateRoot,
                'FMONITOR_SESSION_INSTANCE'=>'pilot','FMONITOR_TRUSTED_REQUEST_SCHEME'=>'http','FMONITOR_FRESH_ORDER_FLOW'=>$enabled?'1':'0',
                'FMONITOR_PILOT_CSS_PATH'=>$root.'/rapid-pilot/pilot.css','FMONITOR_SHLZ_CSS_PATH'=>dirname($root).'/shlz-ui/packages/styles/dist/shlz.css',
                'FMONITOR_ARTIFACT_STORAGE_ROOT'=>$this->original->privateRoot,'PHP_CLI_SERVER_WORKERS'=>'1']);
            if($extraEnvironment!==null)$env=array_replace($env,$extraEnvironment($this->original,$this->port));
            $log=$this->original->control.'/http.log';
            // PHP proc_open drops empty environment values; env preserves explicit empty prefixes.
            $command=['env'];foreach($env as $key=>$value)if($value==='')$command[]=$key.'=';
            array_push($command,PHP_BINARY,'-d','display_errors=0','-d','log_errors=1','-d','post_max_size=22M','-S','127.0.0.1:'.$this->port,$router??$root.'/rapid-pilot/router.php');
            $this->server=proc_open($command,
                [0=>['file','/dev/null','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes,$root,$env);
            if(!is_resource($this->server))throw new \RuntimeException('Fixture server unavailable');
            $deadline=microtime(true)+5;
            do{$s=@stream_socket_client('tcp://127.0.0.1:'.$this->port,$error,$message,.1);if(is_resource($s)){fclose($s);break;}usleep(20000);}while(microtime(true)<$deadline);
            \assertSameValue(303,$this->request('GET','/pilot/login')['status'],'SETUP_OK real LocalAuth accepts native authenticated session');
        }catch(\Throwable $error){$this->close();throw $error;}
    }
    public function request(string $method,string $path,string $body='',?int $actor=18,array $headers=[]):array
    {
        $s=stream_socket_client('tcp://127.0.0.1:'.$this->port,$error,$message,3);if($s===false)throw new \RuntimeException('HTTP fixture connect failed');
        stream_set_timeout($s,15);
        $h=['Host'=>'127.0.0.1:'.$this->port,'Connection'=>'close','Content-Length'=>(string)strlen($body)];
        if($actor!==null)$h['Cookie']=$this->cookies[$actor];
        if($method==='POST')$h['Content-Type']='application/x-www-form-urlencoded';
        $h=array_filter(array_replace($h,$headers),static fn($value)=>$value!==null);$wire=$method.' '.$path." HTTP/1.1\r\n";
        foreach($h as $key=>$value)$wire.=$key.': '.$value."\r\n";
        try{if(fwrite($s,$wire."\r\n".$body)!==strlen($wire."\r\n".$body))throw new \RuntimeException('HTTP fixture short write');
            stream_socket_shutdown($s,STREAM_SHUT_WR);
            $raw=stream_get_contents($s,24*1024*1024);$meta=stream_get_meta_data($s);if($meta['timed_out'])throw new \RuntimeException('HTTP fixture timeout');
        }finally{fclose($s);}
        if($raw==='')return ['status'=>0,'headers'=>[],'body'=>'','connectionClosed'=>true];
        [$head,$bytes]=explode("\r\n\r\n",$raw,2);preg_match('#^HTTP/[0-9.]+ ([0-9]{3})#',$head,$match);$responseHeaders=[];
        foreach(explode("\r\n",$head) as $line)if(str_contains($line,':')){[$key,$value]=explode(':',$line,2);$responseHeaders[strtolower($key)]=trim($value);}
        return ['status'=>(int)($match[1]??0),'headers'=>$responseHeaders,'body'=>$bytes];
    }
    public function close():void
    {
        if(is_resource($this->server)){proc_terminate($this->server);$deadline=microtime(true)+2;while(proc_get_status($this->server)['running']&&microtime(true)<$deadline)usleep(10000);if(proc_get_status($this->server)['running'])proc_terminate($this->server,9);proc_close($this->server);$this->server=null;}
        $this->original->close();
    }
}
