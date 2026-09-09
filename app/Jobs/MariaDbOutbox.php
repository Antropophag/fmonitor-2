<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** Intent persistence on the domain owner's connection; append never commits it. */
final class MariaDbOutbox
{
    private MariaDbJobsSession $sql;
    public function __construct(\mysqli $db,string $prefix,?callable $clock=null)
    {
        $this->sql=new MariaDbJobsSession($db,$prefix,$clock);
    }
    public function append(array $intent): array
    {
        JobValues::keys($intent,['eventId','channel','template','version','data']);
        $event=JobValues::text($intent['eventId'],'/^[A-Za-z0-9][A-Za-z0-9_.:\/-]{0,119}$/D');
        $channel=JobValues::text($intent['channel'],'/^[a-z][a-z0-9_.-]{0,39}$/D');
        $template=JobValues::text($intent['template'],'/^[A-Za-z0-9][A-Za-z0-9_.:\/-]{0,119}$/D');
        $version=JobValues::number($intent['version'],1,65535);$data=JobValues::json($intent['data']);
        $s=$this->sql;
        if((int)$s->db->query('SELECT @@in_transaction')->fetch_column()!==1)throw new \LogicException('OUTBOX_TRANSACTION_REQUIRED');
        $fingerprint=hash('sha256',json_encode([$event,$channel,$template,$version,$data],JSON_THROW_ON_ERROR));
        $lookup=fn(): array=>$s->rows('SELECT intent_id,fingerprint FROM '.$s->table('fm2_outbox_intents').' WHERE domain_event_id=? AND channel=? FOR UPDATE',[$event,$channel]);
        $existing=$lookup();
        if($existing===[]){
            try{
                $s->execute('INSERT INTO '.$s->table('fm2_outbox_intents').'(domain_event_id,channel,template_reference,payload_version,data_json,fingerprint,created_at_utc) VALUES(?,?,?,?,?,?,?)',[$event,$channel,$template,$version,$data,$fingerprint,$s->now()]);
                return ['status'=>'created','intentId'=>(int)$s->db->insert_id];
            }catch(\mysqli_sql_exception $error){if($error->getCode()!==1062)throw $error;$existing=$lookup();}
        }
        if(count($existing)!==1)throw new \RuntimeException('OUTBOX_UNAVAILABLE');
        return hash_equals($existing[0]['fingerprint'],$fingerprint)
            ?['status'=>'replayed','intentId'=>(int)$existing[0]['intent_id']]:['status'=>'conflict'];
    }
    public function assertIdle(): void
    {
        if((int)$this->sql->db->query('SELECT @@in_transaction')->fetch_column()!==0)throw new \LogicException('AMBIENT_TRANSACTION');
    }
    public function pending(int $limit=100): array
    {
        JobValues::number($limit,1,100);$this->assertIdle();$s=$this->sql;
        // Already scheduled pending intents must not starve later committed intents.
        $rows=$s->rows('SELECT i.* FROM '.$s->table('fm2_outbox_intents').' i WHERE i.status=\'pending\' AND NOT EXISTS(SELECT 1 FROM '.$s->table('fm2_jobs')." j WHERE j.job_type='outbox.dispatch' AND JSON_EXTRACT(j.payload_json,'$.intentId')=i.intent_id) ORDER BY i.intent_id LIMIT ".$limit);
        return array_map($this->message(...),$rows);
    }
    public function read(int $id): ?array
    {
        JobValues::number($id);$this->assertIdle();$s=$this->sql;
        $row=$s->rows('SELECT * FROM '.$s->table('fm2_outbox_intents').' WHERE intent_id=?',[$id])[0]??null;
        return $row===null?null:$this->message($row);
    }
    public function attempt(int $intent,int $job,int $attempt): ?array
    {
        JobValues::number($intent);JobValues::number($job);JobValues::number($attempt,1,5);$this->assertIdle();
        $s=$this->sql;$row=$s->rows('SELECT * FROM '.$s->table('fm2_outbox_attempt_events').' WHERE intent_id=? AND job_id=? AND attempt=?',[$intent,$job,$attempt])[0]??null;
        return $row===null?null:$this->attemptResult($row);
    }
    public function canRetryDead(int $intent,int $job): bool
    {
        JobValues::number($intent);JobValues::number($job);$this->assertIdle();
        return $this->linkedRetry($intent,$job);
    }
    private function linkedRetry(int $intent,int $job): bool
    {
        $s=$this->sql;$table=$s->table('fm2_jobs');
        return $s->rows("SELECT j.job_id FROM {$table} j JOIN {$table} prior ON prior.job_id=j.retry_of_job_id WHERE j.job_id=? AND j.status='leased' AND prior.status='dead' AND j.job_type='outbox.dispatch' AND prior.job_type='outbox.dispatch' AND j.payload_version=1 AND prior.payload_version=1 AND JSON_EXTRACT(j.payload_json,'$.intentId')=? AND JSON_EXTRACT(prior.payload_json,'$.intentId')=?",[$job,$intent,$intent])!==[];
    }
    public function recordAttempt(int $intent,int $job,int $attempt,array $result,string $reference,string $now): array
    {
        JobValues::number($intent);JobValues::number($job);JobValues::number($attempt,1,5);
        JobValues::date($now);JobValues::text($reference,'/^[0-9a-f]{64}$/D');$this->assertIdle();
        $result=OutboxDeliveryOutcome::safe($result);
        return $this->sql->transaction(function()use($intent,$job,$attempt,$result,$reference,$now): array{
            $s=$this->sql;
            $row=$s->rows('SELECT intent_id,status FROM '.$s->table('fm2_outbox_intents').' WHERE intent_id=? FOR UPDATE',[$intent]);
            if($row===[])throw new \RuntimeException('OUTBOX_INTENT_MISSING');
            $prior=$s->rows('SELECT * FROM '.$s->table('fm2_outbox_attempt_events').' WHERE intent_id=? AND job_id=? AND attempt=?',[$intent,$job,$attempt])[0]??null;
            if($prior!==null)return $this->attemptResult($prior);
            if($row[0]['status']==='dead'&&!$this->linkedRetry($intent,$job))return ['status'=>'permanent','failureCode'=>'OUTBOX_INTENT_DEAD'];
            $s->execute('INSERT INTO '.$s->table('fm2_outbox_attempt_events').'(intent_id,job_id,attempt,outcome,failure_code,safe_detail,provider_reference,idempotency_reference,occurred_at_utc) VALUES(?,?,?,?,?,NULL,?,?,?)',[
                $intent,$job,$attempt,$result['status'],$result['failureCode']??null,$result['providerReference']??null,$reference,$now,
            ]);
            if($row[0]['status']!=='delivered'){
                if($result['status']==='delivered')$s->execute('UPDATE '.$s->table('fm2_outbox_intents')." SET status='delivered',provider_reference=?,delivered_at_utc=? WHERE intent_id=?",[$result['providerReference']??null,$now,$intent]);
                else $s->execute('UPDATE '.$s->table('fm2_outbox_intents').' SET status=? WHERE intent_id=?',[$result['status']==='permanent'?'dead':'pending',$intent]);
            }
            return $result;
        });
    }
    private function attemptResult(array $row): array
    {
        $result=['status'=>$row['outcome']];if($row['failure_code']!==null)$result['failureCode']=$row['failure_code'];
        if($row['provider_reference']!==null)$result['providerReference']=$row['provider_reference'];
        return $result;
    }
    private function message(array $row): array
    {
        return ['intentId'=>(int)$row['intent_id'],'eventId'=>$row['domain_event_id'],'channel'=>$row['channel'],
            'template'=>$row['template_reference'],'version'=>(int)$row['payload_version'],
            'data'=>json_decode($row['data_json'],true,512,JSON_THROW_ON_ERROR),'createdAtUtc'=>$row['created_at_utc'],
            'status'=>$row['status'],'providerReference'=>$row['provider_reference'],
            'idempotencyReference'=>hash('sha256',"fmonitor2-outbox-v1\0".$row['domain_event_id']."\0".$row['channel'])];
    }
}
