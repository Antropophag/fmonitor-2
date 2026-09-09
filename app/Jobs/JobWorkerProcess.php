<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

final class JobWorkerProcess
{
    private bool $stop=false;
    private ?string $lastHeartbeat=null;
    private \Closure $clock;
    public function __construct(private JobQueue $queue,private MariaDbWorkerHeartbeat $heartbeat,private array $registry,callable $clock,private string $workerId,private int $batch=1,private int $pollMicroseconds=50000,private int $graceSeconds=55)
    {
        $this->clock=\Closure::fromCallable($clock);
        if($batch<1||$batch>50||$pollMicroseconds<1000||$graceSeconds<1)throw new \InvalidArgumentException();
    }
    public function run():array
    {
        pcntl_async_signals(true);$stop=function():void{$this->stop=true;};pcntl_signal(SIGTERM,$stop);pcntl_signal(SIGQUIT,$stop);$completed=0;$this->maintain();
        while(!$this->stop){$jobs=$this->queue->claim(['workerId'=>$this->workerId,'batch'=>$this->batch]);if($jobs===[]){$this->maintain();usleep($this->pollMicroseconds);continue;}foreach($jobs as$job){$result=$this->execute($job);if($result===null){$this->maintain();return['status'=>'stopped','completed'=>$completed,'exitCode'=>0];}if(($result['status']??null)==='lost_lease'||!$this->settle($job,$result)){try{$this->maintain();}catch(\Throwable){}return['status'=>'lost_lease','completed'=>$completed,'exitCode'=>70];}$completed++;if($this->stop)break;}}
        $this->maintain();return['status'=>'stopped','completed'=>$completed,'exitCode'=>0];
    }
    private function execute(array $job):?array
    {
        $factory=$this->registry[$job['jobType']][$job['payloadVersion']]??null;if(!is_callable($factory))return['status'=>'permanent','failureCode'=>'JOB_HANDLER_UNAVAILABLE'];$command=$factory($job);if(!is_array($command)||$command===[])return['status'=>'permanent','failureCode'=>'JOB_HANDLER_UNAVAILABLE'];
        $pipes=[];$process=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($process))return['status'=>'retryable','failureCode'=>'JOB_HANDLER_UNAVAILABLE'];$input=json_encode($job,JSON_THROW_ON_ERROR);stream_set_blocking($pipes[0],false);$offset=0;$writeStarted=microtime(true);$stopAt=null;while($offset<strlen($input)){$written=fwrite($pipes[0],substr($input,$offset));if($written===false){fclose($pipes[0]);proc_terminate($process,9);$this->reap($process,$pipes);return['status'=>'retryable','failureCode'=>'JOB_HANDLER_FAILED'];}$offset+=$written;if($this->stop)$stopAt??=microtime(true);if(microtime(true)-$writeStarted>=5||($stopAt!==null&&microtime(true)-$stopAt>=$this->graceSeconds)){fclose($pipes[0]);proc_terminate($process,9);$this->reap($process,$pipes);return$this->stop?null:['status'=>'retryable','failureCode'=>'JOB_HANDLER_FAILED'];}if($written===0)usleep($this->pollMicroseconds);}fclose($pipes[0]);stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$out='';$err='';
        while(true){$out.=stream_get_contents($pipes[1]);$err.=stream_get_contents($pipes[2]);if(strlen($out)>65535||strlen($err)>65535){proc_terminate($process,9);$this->reap($process,$pipes);return['status'=>'retryable','failureCode'=>'JOB_HANDLER_FAILED'];}$status=proc_get_status($process);try{$owned=$this->maintain($job);}catch(\Throwable){$owned=false;}if(!$owned){if($status['running'])proc_terminate($process,9);$this->reap($process,$pipes);return['status'=>'lost_lease'];}if(!$status['running'])break;if($this->stop){$stopAt??=microtime(true);if(microtime(true)-$stopAt>=$this->graceSeconds){proc_terminate($process,9);$this->reap($process,$pipes);return null;}}usleep($this->pollMicroseconds);}
        $out.=stream_get_contents($pipes[1]);$err.=stream_get_contents($pipes[2]);$exit=$this->reap($process,$pipes);if($exit!==0||$err!==''||strlen($out)>65535)return['status'=>'retryable','failureCode'=>'JOB_HANDLER_FAILED'];try{$result=json_decode(trim($out),false,32,JSON_THROW_ON_ERROR);return$this->result($result);}catch(\Throwable){return['status'=>'retryable','failureCode'=>'JOB_HANDLER_FAILED'];}
    }
    private function maintain(?array $job=null):bool
    {
        $now=($this->clock)();if($this->lastHeartbeat!==null&&$now<JobValues::plus($this->lastHeartbeat,60))return true;$this->heartbeat->observe($this->workerId,$now);$this->lastHeartbeat=$now;if($job===null)return true;$result=$this->queue->heartbeat(['jobId'=>$job['jobId'],'leaseToken'=>$job['leaseToken']]);return in_array($result['status']??null,['accepted','too_soon'],true);
    }
    private function reap($process,array$pipes):int{foreach([1,2]as$fd)if(is_resource($pipes[$fd]))fclose($pipes[$fd]);return proc_close($process);}
    private function result(mixed$result):array{if(!$result instanceof \stdClass)throw new \InvalidArgumentException();$values=get_object_vars($result);$status=$values['status']??null;if($status==='completed'){JobValues::keys($values,['status','result']);if(!$values['result'] instanceof \stdClass)throw new \InvalidArgumentException();$payload=json_decode(json_encode($values['result'],JSON_THROW_ON_ERROR),true,32,JSON_THROW_ON_ERROR);JobValues::json($payload);return['status'=>'completed','result'=>$payload];}if(in_array($status,['retryable','permanent'],true)){JobValues::keys($values,['status','failureCode']);JobValues::text($values['failureCode'],'/^[A-Z][A-Z0-9_]{2,79}$/D');return['status'=>$status,'failureCode'=>$values['failureCode']];}throw new \InvalidArgumentException();}
    private function settle(array$job,array$result):bool
    {
        if(($result['status']??null)==='completed')$settled=$this->queue->complete(['jobId'=>$job['jobId'],'leaseToken'=>$job['leaseToken'],'result'=>$result['result']??[]]);
        else{$retryable=($result['status']??null)==='retryable';$code=is_string($result['failureCode']??null)?$result['failureCode']:'JOB_HANDLER_FAILED';$settled=$this->queue->fail(['jobId'=>$job['jobId'],'leaseToken'=>$job['leaseToken'],'failureCode'=>$code,'retryable'=>$retryable]);}
        return in_array($settled['status']??null,['completed','retry_scheduled','dead'],true);
    }
}
