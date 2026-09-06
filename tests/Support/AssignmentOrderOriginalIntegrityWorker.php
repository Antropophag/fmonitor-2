<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;

/** Existing public worker entry/FD protocol, bounded and always reaped. */
final class OriginalIntegrityWorker
{
    private mixed $process=null;private array $pairs=[];private array $pipes=[];private bool $released=false;
    private array $buffers=['ready'=>'','result'=>'','stdout'=>'','stderr'=>''];private ?int $exit=null;
    private int $started;
    public function __construct(OriginalIntegrityDatabase $fixture,array $command,array $changes=[],bool $keepCommandOpen=false,bool $sendCommand=true)
    {
        $this->started=hrtime(true);$this->request=$command['requestId'];
        $config=['databaseDsn'=>"host={$fixture->host};port={$fixture->port};database={$fixture->database};charset=utf8mb4",'databaseUser'=>$fixture->user,'databasePasswordFile'=>$fixture->passwordFile,'tablePrefix'=>$fixture->prefix,
            'privateStorageRoot'=>$fixture->privateRoot,'safeLogFile'=>$fixture->safeLog,'clockUtc'=>'2026-09-02T09:15:30Z','rootIdSequenceCsv'=>'original-0001','revisionIdSequenceCsv'=>'revision-0001','inspectorMode'=>'real','faultPoint'=>null,'barrierEvent'=>'after_fingerprint_miss_before_cas'];
        foreach($changes as $key=>$value){if(!array_key_exists($key,$config))throw new \LogicException('unknown worker config');$config[$key]=$value;}
        $path=$fixture->control.'/worker-'.bin2hex(random_bytes(6)).'.json';file_put_contents($path,json_encode($config,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES)."\n");chmod($path,0600);
        try{
            for($i=0;$i<4;++$i){$pair=stream_socket_pair(STREAM_PF_UNIX,STREAM_SOCK_STREAM,STREAM_IPPROTO_IP);if($pair===false)throw new \RuntimeException('SETUP_FAILURE: worker socket pair');$this->pairs[]=$pair;}
            $this->process=proc_open([PHP_BINARY,__DIR__.'/assignment_order_original_worker_entry.php',$path],
                [0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w'],3=>$this->pairs[0][1],4=>$this->pairs[1][1],5=>$this->pairs[2][1],6=>$this->pairs[3][1]],$this->pipes,dirname(__DIR__,2));
            if(!is_resource($this->process))throw new \RuntimeException('SETUP_FAILURE: worker child');
            foreach($this->pairs as $pair)fclose($pair[1]);
            $input=json_encode($command,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
            if($sendCommand&&fwrite($this->pairs[0][0],$input)!==strlen($input))throw new \RuntimeException('SETUP_FAILURE: worker command write');if(!$keepCommandOpen)stream_socket_shutdown($this->pairs[0][0],STREAM_SHUT_WR);
            foreach($this->channels() as $channel)stream_set_blocking($channel,false);
        }catch(\Throwable $e){$this->stop();throw $e;}
    }
    private string $request;
    private function channels():array{return ['ready'=>$this->pairs[2][0],'result'=>$this->pairs[3][0],'stdout'=>$this->pipes[1],'stderr'=>$this->pipes[2]];}
    private function pump():bool
    {
        $channels=$this->channels();$read=[];
        foreach($channels as $name=>$channel){$this->buffers[$name].=(string)stream_get_contents($channel);if(strlen($this->buffers[$name])>65536)throw new \RuntimeException('worker output bound');if(!feof($channel))$read[]=$channel;}
        $status=proc_get_status($this->process);if(!$status['running']){$this->exit=$status['exitcode'];return false;}
        if(hrtime(true)-$this->started>15_000_000_000)throw new \RuntimeException('worker exceeded bounded deadline');
        if($read!==[]){$write=null;$except=null;stream_select($read,$write,$except,0,100000);}return true;
    }
    public function awaitReady():string
    {
        while(!str_contains($this->buffers['ready'],"\n")){if(!$this->pump())break;}
        return $this->buffers['ready'];
    }
    public function release():void
    {
        if($this->released)throw new \LogicException('worker release repeated');
        if($this->buffers['ready']!=='READY '.$this->request."\n")throw new \RuntimeException('worker did not reach exact barrier');
        $line='RELEASE '.$this->request."\n";if(fwrite($this->pairs[1][0],$line)!==strlen($line))throw new \RuntimeException('worker release write');stream_socket_shutdown($this->pairs[1][0],STREAM_SHUT_WR);$this->released=true;
    }
    public function finish(bool $autoRelease=true):array
    {
        try{do{if($autoRelease&&!$this->released&&str_contains($this->buffers['ready'],"\n"))$this->release();$running=$this->pump();}while($running);foreach($this->channels() as $name=>$channel)$this->buffers[$name].=(string)stream_get_contents($channel);$out=['exit'=>$this->exit]+$this->buffers;$this->stop();return $out;}catch(\Throwable $e){$this->stop();throw $e;}
    }
    public function stop():void
    {
        foreach($this->pipes as $pipe)if(is_resource($pipe))fclose($pipe);$this->pipes=[];
        foreach($this->pairs as $pair)foreach($pair as $channel)if(is_resource($channel))fclose($channel);$this->pairs=[];
        if(is_resource($this->process)){$status=proc_get_status($this->process);if($status['running'])proc_terminate($this->process,9);proc_close($this->process);}$this->process=null;
    }
    public function __destruct(){$this->stop();}
    public static function command(array $changes=[],?string $bytes=null):array
    {
        $base=['requestId'=>'00000000-0000-4000-8000-000000000001','mode'=>'initial','installationCaseId'=>4512,'assignmentOrderId'=>81,'actorUserId'=>18,'documentDate'=>'2026-09-01','compositionConfirmed'=>true,
            'rootOriginalId'=>null,'targetRevisionId'=>null,'expectedCurrentRevisionId'=>null,'correctionReason'=>null,'upload'=>['bytesBase64'=>base64_encode($bytes??OriginalIntegrityFixture::pdf()),'originalFilename'=>'original.pdf','declaredMediaType'=>'application/pdf']];
        foreach($changes as $key=>$value){if(!array_key_exists($key,$base))throw new \LogicException('unknown command field');$base[$key]=$value;}return $base;
    }
}
