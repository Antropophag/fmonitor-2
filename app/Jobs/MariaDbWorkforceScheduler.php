<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** One native transaction owns a schedule slot and its generic queued job. */
final readonly class MariaDbWorkforceScheduler
{
    public function __construct(private \mysqli $db,private string $prefix)
    {
        JobValues::text($prefix,'/^[A-Za-z0-9_]{0,25}$/D');
    }
    public function tick(string $nowUtc): array
    {
        JobValues::date($nowUtc);
        $local=(new \DateTimeImmutable($nowUtc))->setTimezone(new \DateTimeZone('Europe/Moscow'));
        if((int)$local->format('i')<7)$local=$local->modify('-1 hour');
        $local=$local->setTime((int)$local->format('H'),7,0,0);
        $slot=$local->format('Y-m-d\TH');$key='workforce-hourly-v1/'.$slot;
        $due=$local->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');
        $s=new MariaDbJobsSession($this->db,$this->prefix,static fn(): string=>$nowUtc);
        return $s->transaction(function()use($s,$nowUtc,$slot,$key,$due): array{
            $slots=$s->table('fm2_scheduler_slots');
            $latestSql="SELECT * FROM {$slots} WHERE schedule_key LIKE 'workforce-hourly-v1/%' ORDER BY due_at_utc DESC LIMIT 1 FOR UPDATE";
            $latest=$s->rows($latestSql)[0]??null;
            // A waiter may have waited on the prior latest row; refresh after acquiring it.
            if($latest!==null)$latest=$s->rows($latestSql)[0]??null;
            $existing=$s->rows("SELECT * FROM {$slots} WHERE schedule_key=? FOR UPDATE",[$key])[0]??null;
            if($existing!==null)return $this->result($existing,false);
            if($latest!==null&&$latest['due_at_utc']>$due)return $this->result($latest,false);
            $skipped=$latest===null?0:max(0,intdiv((new \DateTimeImmutable($due))->getTimestamp()-(new \DateTimeImmutable($latest['due_at_utc']))->getTimestamp(),3600)-1);
            $identity=$this->identity($slot);
            $enqueue=(new MariaDbJobEnqueue($s,['workforce.sync'=>[1]]))->command([
                'jobType'=>'workforce.sync','payloadVersion'=>1,
                'payload'=>['scheduleSlot'=>$slot,'dueAtUtc'=>$due,'runIdentity'=>$identity],
                'availableAtUtc'=>$due,'idempotencyKey'=>$identity,'actor'=>['type'=>'system','id'=>'workforce-hourly-v1'],
            ]);
            $job=$enqueue();if($job['status']==='conflict')throw new \RuntimeException('SCHEDULER_JOB_CONFLICT');
            // On the first-ever slot there is no latest row: the unique job serializes replicas.
            $existing=$s->rows("SELECT * FROM {$slots} WHERE schedule_key=? FOR UPDATE",[$key])[0]??null;
            if($existing!==null){
                if((int)$existing['job_id']!==$job['jobId'])throw new \RuntimeException('SCHEDULER_JOB_CONFLICT');
                return $this->result($existing,false);
            }
            $s->execute("INSERT INTO {$slots}(schedule_key,due_at_utc,enqueued_at_utc,skipped_slots,job_id) VALUES(?,?,?,?,?)",[$key,$due,$nowUtc,$skipped,$job['jobId']]);
            return ['slot'=>$slot,'dueAt'=>$due,'created'=>true,'jobId'=>$job['jobId'],'skippedSlots'=>$skipped];
        });
    }
    private function result(array $row,bool $created): array
    {
        return ['slot'=>substr($row['schedule_key'],strlen('workforce-hourly-v1/')),'dueAt'=>$row['due_at_utc'],
            'created'=>$created,'jobId'=>(int)$row['job_id'],'skippedSlots'=>(int)$row['skipped_slots']];
    }
    private function identity(string $slot): string
    {
        $hex=substr(hash('sha256',"workforce-hourly-v1\0".$slot),0,32);
        $hex[12]='4';$hex[16]=dechex((hexdec($hex[16])&3)|8);
        return substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20,12);
    }
}
