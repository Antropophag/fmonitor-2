<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

final readonly class MariaDbEquipmentFactsScheduler
{
    private const KEY='erp-equipment-facts-hourly-v1/';
    public function __construct(private \mysqli$db,private string$prefix){JobValues::text($prefix,'/^[A-Za-z0-9_]{0,25}$/D');}
    public function tick(string$nowUtc):array
    {
        JobValues::date($nowUtc);$instant=new \DateTimeImmutable($nowUtc);$local=$instant->setTimezone(new \DateTimeZone('Europe/Moscow'));$local=$local->setTime((int)$local->format('H'),0,0,0);$slot=$local->format('Y-m-d\TH');$due=$local->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');$key=self::KEY.$slot;$s=new MariaDbJobsSession($this->db,$this->prefix,static fn():string=>$nowUtc);
        return$s->transaction(function()use($s,$slot,$due,$key,$nowUtc):array{$table=$s->table('fm2_scheduler_slots');$latestSql="SELECT * FROM {$table} WHERE schedule_key LIKE '".self::KEY."%' ORDER BY due_at_utc DESC LIMIT 1 FOR UPDATE";$latest=$s->rows($latestSql)[0]??null;$existing=$s->rows("SELECT * FROM {$table} WHERE schedule_key=? FOR UPDATE",[$key])[0]??null;if($existing!==null)return$this->stored($existing,false);if($latest!==null&&$latest['due_at_utc']>$due)return$this->stored($latest,false);$skipped=$latest===null?0:max(0,intdiv((new \DateTimeImmutable($due))->getTimestamp()-(new \DateTimeImmutable($latest['due_at_utc']))->getTimestamp(),3600)-1);$identity=$this->identity($slot);$enqueue=(new MariaDbJobEnqueue($s,['erp.equipment-facts.sync'=>[1]]))->command(['jobType'=>'erp.equipment-facts.sync','payloadVersion'=>1,'payload'=>['scheduleSlot'=>$slot],'availableAtUtc'=>$due,'idempotencyKey'=>$identity,'actor'=>['type'=>'system','id'=>'erp-equipment-facts-hourly-v1']]);$job=$enqueue();if($job['status']==='conflict')throw new \RuntimeException('SCHEDULER_JOB_CONFLICT');$existing=$s->rows("SELECT * FROM {$table} WHERE schedule_key=? FOR UPDATE",[$key])[0]??null;if($existing!==null){if((int)$existing['job_id']!==$job['jobId'])throw new \RuntimeException('SCHEDULER_JOB_CONFLICT');return$this->stored($existing,false);}$s->execute("INSERT INTO {$table}(schedule_key,due_at_utc,enqueued_at_utc,skipped_slots,job_id) VALUES(?,?,?,?,?)",[$key,$due,$nowUtc,$skipped,$job['jobId']]);return['slot'=>$slot,'dueAt'=>$due,'created'=>true,'jobId'=>$job['jobId'],'skippedSlots'=>$skipped];});
    }
    private function stored(array$r,bool$c):array{return['slot'=>substr($r['schedule_key'],strlen(self::KEY)),'dueAt'=>$r['due_at_utc'],'created'=>$c,'jobId'=>(int)$r['job_id'],'skippedSlots'=>(int)$r['skipped_slots']];}
    private function identity(string$s):string{$h=substr(hash('sha256',self::KEY."\0".$s),0,32);$h[12]='4';$h[16]=dechex((hexdec($h[16])&3)|8);return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20,12);}
}
