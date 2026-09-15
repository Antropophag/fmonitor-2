<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

final readonly class MariaDbOrderDocumentLinksScheduler
{
    private const KEY='bitrix-order-document-links-hourly-v1/';
    public function __construct(private \mysqli$db,private string$prefix){JobValues::text($prefix,'/^[A-Za-z0-9_]{0,25}$/D');}
    public function tick(string$nowUtc):array
    {
        JobValues::date($nowUtc);$local=(new \DateTimeImmutable($nowUtc))->setTimezone(new \DateTimeZone('Europe/Moscow'))->setTime((int)(new \DateTimeImmutable($nowUtc))->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('H'),0,0,0);
        $slot=$local->format('Y-m-d\TH');$due=$local->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');$key=self::KEY.$slot;$s=new MariaDbJobsSession($this->db,$this->prefix,static fn():string=>$nowUtc);
        return$s->transaction(function()use($s,$slot,$due,$key,$nowUtc):array{
            $table=$s->table('fm2_scheduler_slots');$latestSql="SELECT * FROM {$table} WHERE schedule_key LIKE '".self::KEY."%' ORDER BY due_at_utc DESC LIMIT 1 FOR UPDATE";$latest=$s->rows($latestSql)[0]??null;if($latest!==null)$latest=$s->rows($latestSql)[0]??null;
            $existing=$s->rows("SELECT * FROM {$table} WHERE schedule_key=? FOR UPDATE",[$key])[0]??null;if($existing!==null)return$this->stored($existing,false);
            if($latest!==null&&$latest['due_at_utc']>$due)return$this->stored($latest,false);
            $skipped=$latest===null?0:max(0,intdiv((new \DateTimeImmutable($due))->getTimestamp()-(new \DateTimeImmutable($latest['due_at_utc']))->getTimestamp(),3600)-1);$identity=$this->identity($slot);
            $enqueue=(new MariaDbJobEnqueue($s,['bitrix.order-document-links.sync'=>[1]]))->command(['jobType'=>'bitrix.order-document-links.sync','payloadVersion'=>1,'payload'=>['scheduleSlot'=>$slot],'availableAtUtc'=>$due,'idempotencyKey'=>$identity,'actor'=>['type'=>'system','id'=>'bitrix-order-document-links-hourly-v1']]);$job=$enqueue();if($job['status']==='conflict')throw new \RuntimeException('SCHEDULER_JOB_CONFLICT');
            $existing=$s->rows("SELECT * FROM {$table} WHERE schedule_key=? FOR UPDATE",[$key])[0]??null;if($existing!==null){if((int)$existing['job_id']!==$job['jobId'])throw new \RuntimeException('SCHEDULER_JOB_CONFLICT');return$this->stored($existing,false);}
            $s->execute("INSERT INTO {$table}(schedule_key,due_at_utc,enqueued_at_utc,skipped_slots,job_id) VALUES(?,?,?,?,?)",[$key,$due,$nowUtc,$skipped,$job['jobId']]);return['slot'=>$slot,'dueAt'=>$due,'created'=>true,'jobId'=>$job['jobId'],'skippedSlots'=>$skipped];
        });
    }
    private function stored(array$row,bool$created):array{return['slot'=>substr($row['schedule_key'],strlen(self::KEY)),'dueAt'=>$row['due_at_utc'],'created'=>$created,'jobId'=>(int)$row['job_id'],'skippedSlots'=>(int)$row['skipped_slots']];}
    private function identity(string$slot):string{$hex=substr(hash('sha256',self::KEY."\0".$slot),0,32);$hex[12]='4';$hex[16]=dechex((hexdec($hex[16])&3)|8);return substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20,12);}
}
