<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\Workforce as W;
require_once __DIR__.'/SelectionSchemaWorkerControl.php';
final class BitrixDeliveryFixture
{
    public string $root;public int $port;private mixed $server=null;private ?array $worker=null;private int $sequence=0;private int $step=0;private array $config=[];
    public function __construct(string $mode='full',bool $wrongHost=false)
    {
        $this->root=(string)realpath(sys_get_temp_dir()).'/fmonitor2-bitrix-delivery-fixture-'.bin2hex(random_bytes(6));mkdir($this->root,0700);$this->root=realpath($this->root);
        try{
            $v=curl_version();\assertSameValue(true,($v['features']&CURL_VERSION_ASYNCHDNS)!==0&&defined('CURLOPT_TIMEOUT_MS')&&defined('CURLOPT_CONNECTTIMEOUT_MS')&&defined('CURLINFO_PRETRANSFER_TIME_T')&&function_exists('posix_geteuid'),'required native runtime capabilities');
            file_put_contents($this->root.'/token','FAKE_TOKEN_123456789');chmod($this->root.'/token',0600);
            file_put_contents($this->root.'/ca.cnf',"[req]\nprompt=no\ndistinguished_name=dn\nx509_extensions=ca\n[dn]\nCN=FMonitor Fixture CA\n[ca]\nbasicConstraints=critical,CA:TRUE\nkeyUsage=critical,keyCertSign,cRLSign\n");
            $this->command(['openssl','req','-new','-x509','-newkey','rsa:2048','-nodes','-days','2','-config',$this->root.'/ca.cnf','-keyout',$this->root.'/ca.key','-out',$this->root.'/ca.crt']);
            $this->command(['openssl','req','-new','-newkey','rsa:2048','-nodes','-subj','/CN=FMonitor Fixture','-keyout',$this->root.'/server.key','-out',$this->root.'/server.csr']);
            file_put_contents($this->root.'/leaf.ext','subjectAltName='.($wrongHost?'DNS:wrong.invalid':'IP:127.0.0.1')."\nextendedKeyUsage=serverAuth\n");
            $this->command(['openssl','x509','-req','-in',$this->root.'/server.csr','-CA',$this->root.'/ca.crt','-CAkey',$this->root.'/ca.key','-CAcreateserial','-days','2','-extfile',$this->root.'/leaf.ext','-out',$this->root.'/server.crt']);
            chmod($this->root.'/ca.key',0600);chmod($this->root.'/server.key',0600);$this->scenario($mode);
            $this->server=proc_open(['python3',__DIR__.'/bitrix_delivery_https_server.py',$this->root],[0=>['file','/dev/null','r'],1=>['file',$this->root.'/server.log','a'],2=>['file',$this->root.'/server.log','a']],$pipes,dirname(__DIR__,2));if(!is_resource($this->server))throw new \TestFailure('TLS server start');
            $deadline=hrtime(true)+5_000_000_000;while(!is_file($this->root.'/ready.json')){if(!proc_get_status($this->server)['running']||hrtime(true)>$deadline)throw new \TestFailure('TLS server readiness');usleep(20000);}
            $this->port=json_decode(file_get_contents($this->root.'/ready.json'),true,512,JSON_THROW_ON_ERROR)['port'];
            $host=$wrongHost?'wrong.invalid':'127.0.0.1';$curl=curl_init('https://'.$host.':'.$this->port.'/fixture-health');curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_PROXY=>'',CURLOPT_CAINFO=>$this->root.'/ca.crt',CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_TIMEOUT=>3,CURLOPT_RESOLVE=>[$host.':'.$this->port.':127.0.0.1']]);
            $body=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);unset($curl);\assertSameValue([200,'{"ready":true}'],[$status,$body],'healthy native verified TLS prerequisite');
        }catch(\Throwable $error){$this->close();throw $error;}
    }
    private function command(array $command):void
    {
        $process=proc_open($command,[0=>['file','/dev/null','r'],1=>['file',$this->root.'/openssl.log','a'],2=>['file',$this->root.'/openssl.log','a']],$pipes);if(!is_resource($process))throw new \TestFailure('Certificate process start');
        $deadline=hrtime(true)+10_000_000_000;do{$state=proc_get_status($process);if(!$state['running'])break;usleep(20000);}while(hrtime(true)<$deadline);
        if($state['running']){proc_terminate($process,9);proc_close($process);throw new \TestFailure('Certificate process exceeded deadline');}
        $closed=proc_close($process);$exit=$state['exitcode']>=0?$state['exitcode']:$closed;if($exit!==0)throw new \TestFailure('Synthetic certificate generation');
    }
    public function scenario(string $mode):void {file_put_contents($this->root.'/scenario.json',json_encode(['mode'=>$mode],JSON_THROW_ON_ERROR));}
    public function parameters(array $overrides=[]):array {return array_replace(['origin'=>'https://127.0.0.1:'.$this->port,'webhookUserId'=>7,'tokenFile'=>$this->root.'/token','departmentIds'=>[71],'connectTimeoutSeconds'=>1,'requestTimeoutSeconds'=>2,'deadlineSeconds'=>30,'caFile'=>$this->root.'/ca.crt'],$overrides);}
    public function client(array $overrides=[],?string $disabledFunction=null):void
    {
        \assertSameValue(true,is_callable([W\BitrixWorkforceDeliveryFactory::class,'create']),'INTENDED_RED: production Bitrix delivery factory missing after healthy native TLS setup');$this->stopClient();$this->config=$this->parameters($overrides);$this->sequence++;$this->step=0;$connections=$this->connections();
        $path=$this->root.'/client-'.$this->sequence.'.json';file_put_contents($path,json_encode(['disabledFunction'=>$disabledFunction,'config'=>$this->config,'output'=>$this->root.'/records-'.$this->sequence],JSON_THROW_ON_ERROR));chmod($path,0600);
        $process=proc_open([PHP_BINARY,'-d','disable_functions='.($disabledFunction??''),'-d','error_reporting=-1','-d','display_errors=stderr',__DIR__.'/bitrix_delivery_client_worker.php',$path],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));if(!is_resource($process))throw new \TestFailure('Delivery worker start');stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$this->worker=['process'=>$process,'pipes'=>$pipes,'stdout'=>'','stderr'=>'','started'=>hrtime(true),'status'=>null];$this->wait('ready',5);\assertSameValue($connections,$this->connections(),'factory construction performs no network');
    }
    private function wait(string $phase,int $seconds):void
    {
        $deadline=hrtime(true)+$seconds*1_000_000_000;do{\aossDrainWorker($this->worker);if(in_array('PHASE '.$phase,explode("\n",$this->worker['stdout']),true))return;if(!$this->worker['status']['running'])throw new \TestFailure('Delivery worker exited before result');}while(hrtime(true)<$deadline);throw new \TestFailure('Delivery worker exceeded bounded deadline');
    }
    public function fetch(bool $expireDuringAttempt=false):array
    {
        $connections=$this->connections();$this->step++;if(fwrite($this->worker['pipes'][0],"fetch\n")!==6)throw new \TestFailure('Worker command');if($expireDuringAttempt)$this->expireNativeAttempt();$this->wait('done'.$this->step,$this->config['deadlineSeconds']+3);
        \assertSameValue('',$this->worker['stderr'],'native client emits no stderr');$matches=[];if(preg_match('/^RESULT '.$this->step.' (.+)$/m',$this->worker['stdout'],$matches)!==1)throw new \TestFailure('Safe worker result');
        $lines=explode("\n",$this->worker['stdout']);if(end($lines)==='')array_pop($lines);\assertSameValue(1+2*$this->step,count($lines),'only harness protocol lines on stdout');
        foreach(['FAKE_TOKEN_123456789','ROTATED_FAKE_TOKEN_987654321','Работник','tab1@example.invalid',$this->root] as $private)\assertSameValue(false,str_contains($this->worker['stdout'],$private),'safe result/output excludes secret/PII/path');
        $summary=json_decode($matches[1],true,512,JSON_THROW_ON_ERROR);$keys=array_keys($summary);sort($keys);\assertSameValue(['attempts','batch','pages','reason','status'],$keys,'safe summary whitelist');
        \assertSameValue($summary['attempts'],$this->connections()-$connections,'attempt counter matches actual native TCP connections');
        return [$summary,json_decode(file_get_contents($this->root.'/records-'.$this->sequence.'-'.$this->step.'.json'),true,512,JSON_THROW_ON_ERROR)];
    }
    private function expireNativeAttempt():void
    {
        // Pause only this task-owned worker after the real server has received its request.
        $deadline=hrtime(true)+2_000_000_000;
        while(!is_file($this->root.'/pause-ready')){if(hrtime(true)>$deadline)throw new \TestFailure('native pause readiness');usleep(1000);}
        $pid=proc_get_status($this->worker['process'])['pid'];
        if(!posix_kill($pid,SIGSTOP))throw new \TestFailure('owned worker pause');
        try{file_put_contents($this->root.'/pause-release','1');usleep(1_150_000);}
        finally{posix_kill($pid,SIGCONT);}
    }
    public function connections():int {return is_file($this->root.'/connections.jsonl')?count(file($this->root.'/connections.jsonl',FILE_SKIP_EMPTY_LINES)):0;}
    public function requests():array {if(!is_file($this->root.'/requests.jsonl'))return[];return array_map(static fn($s)=>json_decode($s,true,512,JSON_THROW_ON_ERROR),file($this->root.'/requests.jsonl',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES));}
    public function stopClient():void {if($this->worker===null)return;try{$before=$this->worker['stdout'];fwrite($this->worker['pipes'][0],"stop\n");$r=\aossReapWorker($this->worker,2);\assertSameValue([0,''],[$r['exit'],$r['stderr']],'worker stops cleanly');\assertSameValue($before,$r['stdout'],'no destructor output');}finally{\aossCleanupWorker($this->worker);$this->worker=null;}}
    public function close():void
    {
        $errors=[];try{$this->stopClient();}catch(\Throwable $e){$errors[]=$e->getMessage();}
        if(is_resource($this->server)){proc_terminate($this->server);$deadline=hrtime(true)+2_000_000_000;while(proc_get_status($this->server)['running']&&hrtime(true)<$deadline)usleep(20000);if(proc_get_status($this->server)['running'])proc_terminate($this->server,9);proc_close($this->server);$this->server=null;}
        if(is_dir($this->root)){foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST) as $file){if($file->isDir()&&!$file->isLink())rmdir($file->getPathname());else unlink($file->getPathname());}rmdir($this->root);}
        if($errors!==[])throw new \TestFailure(implode(' | ',$errors));
    }
}
