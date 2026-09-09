<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** Durable queue owner. All handlers execute after these short transactions end. */
final class MariaDbJobQueue implements JobQueue
{
    private MariaDbJobsSession $sql;
    private \Closure $token;
    public function __construct(\mysqli $db,string $prefix,?callable $clock=null,?callable $token=null,private array $registry=[])
    {
        $this->sql=new MariaDbJobsSession($db,$prefix,$clock);
        $this->token=$token===null?static fn(): string=>bin2hex(random_bytes(32)):\Closure::fromCallable($token);
    }
    public function enqueue(array $command): array
    {
        $operation=(new MariaDbJobEnqueue($this->sql,$this->registry))->command($command);
        return $this->sql->transaction($operation);
    }
    public function claim(array $command): array
    {
        JobValues::keys($command,['workerId','batch']);
        $worker=JobValues::text($command['workerId'],'/^[A-Za-z0-9][A-Za-z0-9_.:-]{0,79}$/D');
        $batch=JobValues::number($command['batch'],1,50);
        return $this->sql->transaction(function()use($worker,$batch): array{
            $s=$this->sql;$now=$s->now();$out=[];
            $rows=$s->rows('SELECT * FROM '.$s->table('fm2_jobs')." WHERE (status='ready' AND available_at_utc<=?) OR (status='leased' AND lease_expires_at_utc<=?) ORDER BY available_at_utc,job_id LIMIT ".$batch.' FOR UPDATE',[$now,$now]);
            foreach($rows as $job){
                if($job['status']==='leased'){
                    $s->event($job,'expired',$now);
                    if((int)$job['attempt']>=5){
                        $s->execute('UPDATE '.$s->table('fm2_jobs')." SET status='dead',failure_code='LEASE_EXPIRED',completed_at_utc=?,".$this->clearLease().' WHERE job_id=?',[$now,(int)$job['job_id']]);
                        $s->event($job,'dead',$now,['failureCode'=>'LEASE_EXPIRED']);continue;
                    }
                    $s->event($job,'reclaimed',$now);
                }
                $token=JobValues::text(($this->token)(),'/^[0-9a-f]{64}$/D');
                if($job['lease_token']!==null&&hash_equals($job['lease_token'],$token))throw new \RuntimeException('Jobs lease identity collision.');
                $attempt=(int)$job['attempt']+1;$expires=JobValues::plus($now,300);
                $s->execute('UPDATE '.$s->table('fm2_jobs')." SET status='leased',attempt=?,worker_id=?,lease_token=?,leased_at_utc=?,lease_expires_at_utc=?,heartbeat_at_utc=?,failure_code=NULL,result_json=NULL,completed_at_utc=NULL WHERE job_id=?",[$attempt,$worker,$token,$now,$expires,$now,(int)$job['job_id']]);
                $job=array_replace($job,['attempt'=>$attempt,'worker_id'=>$worker,'lease_token'=>$token]);
                $s->event($job,'claimed',$now);
                $out[]=['jobId'=>(int)$job['job_id'],'jobIdentity'=>$job['idempotency_key'],'jobType'=>$job['job_type'],
                    'payloadVersion'=>(int)$job['payload_version'],'payload'=>json_decode($job['payload_json'],true,512,JSON_THROW_ON_ERROR),
                    'attempt'=>$attempt,'leaseToken'=>$token,'leasedAtUtc'=>$now,'leaseExpiresAtUtc'=>$expires];
            }
            return $out;
        });
    }
    public function heartbeat(array $command): array
    {
        $this->leaseCommand($command,[]);
        return $this->withLease($command,function(array $job,string $now): array{
            if($now<JobValues::plus($job['heartbeat_at_utc'],60))return ['status'=>'too_soon'];
            $s=$this->sql;$expires=JobValues::plus($now,300);
            $s->execute('UPDATE '.$s->table('fm2_jobs').' SET heartbeat_at_utc=?,lease_expires_at_utc=? WHERE job_id=?',[$now,$expires,(int)$job['job_id']]);
            $s->event($job,'heartbeat',$now);
            return ['status'=>'accepted','leaseExpiresAtUtc'=>$expires];
        });
    }
    public function complete(array $command): array
    {
        $this->leaseCommand($command,['result']);$result=JobValues::json($command['result']);
        return $this->withLease($command,function(array $job,string $now)use($result): array{
            $s=$this->sql;
            $s->execute('UPDATE '.$s->table('fm2_jobs')." SET status='completed',completed_at_utc=?,result_json=?,failure_code=NULL,".$this->clearLease().' WHERE job_id=?',[$now,$result,(int)$job['job_id']]);
            $s->event($job,'completed',$now);
            return ['status'=>'completed'];
        });
    }
    public function fail(array $command): array
    {
        $this->leaseCommand($command,['failureCode','retryable']);
        $code=JobValues::text($command['failureCode'],'/^[A-Z][A-Z0-9_]{2,79}$/D');
        if(!is_bool($command['retryable']))throw new \InvalidArgumentException('Invalid Jobs retry flag.');
        return $this->withLease($command,function(array $job,string $now)use($code,$command): array{
            $s=$this->sql;$terminal=!$command['retryable']||(int)$job['attempt']>=5;
            $available=$terminal?$job['available_at_utc']:JobValues::plus($now,[1=>60,2=>300,3=>900,4=>3600][(int)$job['attempt']]);
            $s->execute('UPDATE '.$s->table('fm2_jobs').' SET status=?,available_at_utc=?,failure_code=?,completed_at_utc=?,'.$this->clearLease().' WHERE job_id=?',[$terminal?'dead':'ready',$available,$code,$terminal?$now:null,(int)$job['job_id']]);
            $s->event($job,$terminal?'dead':'retry_scheduled',$now,['failureCode'=>$code]);
            return $terminal?['status'=>'dead']:['status'=>'retry_scheduled','availableAtUtc'=>$available];
        });
    }
    private function leaseCommand(array $command,array $extra): void
    {
        JobValues::keys($command,array_merge(['jobId','leaseToken'],$extra));
        JobValues::number($command['jobId']);JobValues::text($command['leaseToken'],'/^[0-9a-f]{64}$/D');
    }
    private function withLease(array $command,callable $operation): array
    {
        return $this->sql->transaction(function()use($command,$operation): array{
            $s=$this->sql;
            $rows=$s->rows('SELECT * FROM '.$s->table('fm2_jobs').' WHERE job_id=? FOR UPDATE',[$command['jobId']]);
            $now=$s->now();$job=$rows[0]??null;
            if($job===null||$job['status']!=='leased'||$job['lease_expires_at_utc']<=$now||!hash_equals($job['lease_token'],$command['leaseToken']))return ['status'=>'stale_lease'];
            return $operation($job,$now);
        });
    }
    private function clearLease(): string
    {
        return 'worker_id=NULL,lease_token=NULL,leased_at_utc=NULL,lease_expires_at_utc=NULL,heartbeat_at_utc=NULL';
    }
}
