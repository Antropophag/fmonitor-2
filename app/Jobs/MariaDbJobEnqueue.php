<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** @internal Shared insertion policy; the application operation owns its transaction. */
final class MariaDbJobEnqueue
{
    public function __construct(private MariaDbJobsSession $sql,private array $registry) {}
    public function command(array $command,?int $retryOf=null): \Closure
    {
        JobValues::keys($command,['jobType','payloadVersion','payload','availableAtUtc','idempotencyKey','actor']);
        $type=JobValues::text($command['jobType'],'/^[a-z][a-z0-9_.-]{2,79}$/D');
        $version=JobValues::number($command['payloadVersion'],1,65535);
        if(!in_array($version,$this->registry[$type]??[],true))throw new \InvalidArgumentException('Unsupported Jobs payload.');
        $payload=JobValues::json($command['payload']);$actor=JobValues::json($command['actor'],4096);
        $available=JobValues::date($command['availableAtUtc']);
        $identity=JobValues::text($command['idempotencyKey'],'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D');
        $fingerprint=hash('sha256',json_encode([$type,$version,$payload,$available,$identity,$actor],JSON_THROW_ON_ERROR));
        return function()use($type,$version,$payload,$actor,$available,$identity,$fingerprint,$retryOf): array{
            $s=$this->sql;$now=$s->now();
            $lookup=fn(): array=>$s->rows('SELECT job_id,fingerprint FROM '.$s->table('fm2_jobs').' WHERE job_type=? AND idempotency_key=? FOR UPDATE',[$type,$identity]);
            $existing=$lookup();
            if($existing===[]){
                try{
                    $s->execute('INSERT INTO '.$s->table('fm2_jobs').'(job_type,payload_version,payload_json,idempotency_key,fingerprint,actor_json,available_at_utc,created_at_utc,retry_of_job_id) VALUES(?,?,?,?,?,?,?,?,?)',[$type,$version,$payload,$identity,$fingerprint,$actor,$available,$now,$retryOf]);
                    $id=(int)$s->db->insert_id;
                    $s->event(['job_id'=>$id,'attempt'=>0],'created',$now);
                    return ['status'=>'created','jobId'=>$id];
                }catch(\mysqli_sql_exception $error){if($error->getCode()!==1062)throw $error;$existing=$lookup();}
            }
            if(count($existing)!==1)throw new \RuntimeException('Jobs identity unavailable.');
            return hash_equals($existing[0]['fingerprint'],$fingerprint)
                ?['status'=>'replayed','jobId'=>(int)$existing[0]['job_id']]:['status'=>'conflict'];
        };
    }
}
