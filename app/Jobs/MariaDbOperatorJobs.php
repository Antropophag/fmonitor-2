<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** Deployment-authorized recovery; a retry creates a linked job, never an inline effect. */
final class MariaDbOperatorJobs
{
    private MariaDbJobsSession $sql;
    private \Closure $authorize;
    public function __construct(\mysqli $db,string $prefix,callable $authorize)
    {
        $this->sql=new MariaDbJobsSession($db,$prefix);$this->authorize=\Closure::fromCallable($authorize);
    }
    public function retry(array $command): array
    {
        if(!is_string($command['authority']??null)||!($this->authorize)($command['authority']))return ['status'=>'forbidden'];
        JobValues::keys($command,['authority','jobId','operationId','nowUtc']);$id=JobValues::number($command['jobId']);
        $operation=JobValues::text($command['operationId'],'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D');
        $now=JobValues::date($command['nowUtc']);
        $s=$this->sql;$database=$s->db->query('SELECT DATABASE()')->fetch_column();
        if(!is_string($database)||$database==='')throw new \RuntimeException('JOBS_UNAVAILABLE');
        $lock=hash('sha256',$database."\0".$s->prefix."\0operator-retry\0".$operation);
        $locked=$s->rows('SELECT GET_LOCK(?,0) acquired',[$lock])[0]['acquired']??null;
        if((string)$locked==='0')return ['status'=>'busy'];
        if((string)$locked!=='1')throw new \RuntimeException('JOBS_UNAVAILABLE');
        try{return $s->transaction(function()use($s,$command,$id,$operation,$now): array{
            $prior=$s->rows('SELECT job_id,retry_of_job_id FROM '.$s->table('fm2_jobs').' WHERE idempotency_key=? AND retry_of_job_id IS NOT NULL FOR UPDATE',[$operation]);
            if($prior!==[])return count($prior)===1&&(int)$prior[0]['retry_of_job_id']===$id
                ?['status'=>'created','jobId'=>(int)$prior[0]['job_id']]:['status'=>'conflict'];
            $source=$s->rows('SELECT * FROM '.$s->table('fm2_jobs').' WHERE job_id=? FOR UPDATE',[$id])[0]??null;
            if($source===null)return ['status'=>'not_found'];
            if($source['status']!=='dead')return ['status'=>'not_retryable'];
            $create=(new MariaDbJobEnqueue($s,[$source['job_type']=>[(int)$source['payload_version']]]))->command([
                'jobType'=>$source['job_type'],'payloadVersion'=>(int)$source['payload_version'],
                'payload'=>json_decode($source['payload_json'],true,512,JSON_THROW_ON_ERROR),
                'availableAtUtc'=>$now,'idempotencyKey'=>$operation,
                'actor'=>['type'=>'operator','id'=>$command['authority'],'retryOfJobId'=>$id],
            ],$id);
            $result=$create();return $result['status']==='created'?$result:['status'=>'conflict'];
        });}finally{
            if((string)($s->rows('SELECT RELEASE_LOCK(?) released',[$lock])[0]['released']??null)!=='1')throw new \RuntimeException('JOBS_UNAVAILABLE');
        }
    }
    public function listFailed(array $command): array
    {
        if(!is_string($command['authority']??null)||!($this->authorize)($command['authority']))return ['status'=>'forbidden'];
        JobValues::keys($command,['authority','page','limit']);
        $page=JobValues::number($command['page']);$limit=JobValues::number($command['limit'],1,100);
        if($page-1>intdiv(PHP_INT_MAX,$limit))throw new \InvalidArgumentException('Jobs page exceeds range.');
        $offset=($page-1)*$limit;$s=$this->sql;
        return $s->readOnly(function()use($s,$page,$limit,$offset): array{
            $table=$s->table('fm2_jobs');$total=(int)$s->rows("SELECT COUNT(*) total FROM {$table} WHERE status='dead'")[0]['total'];
            $rows=$s->rows("SELECT job_id,job_type,payload_version,status,attempt,failure_code,created_at_utc,completed_at_utc FROM {$table} WHERE status='dead' ORDER BY job_id LIMIT {$limit} OFFSET {$offset}");
            $items=array_map(static fn(array $r): array=>[
                'jobId'=>(int)$r['job_id'],'jobType'=>$r['job_type'],'payloadVersion'=>(int)$r['payload_version'],
                'status'=>$r['status'],'attempt'=>(int)$r['attempt'],'failureCode'=>$r['failure_code'],
                'createdAtUtc'=>$r['created_at_utc'],'completedAtUtc'=>$r['completed_at_utc'],
            ],$rows);
            return ['items'=>$items,'total'=>$total,'page'=>$page,'pages'=>intdiv($total,$limit)+($total%$limit===0?0:1),'limit'=>$limit];
        });
    }
}
