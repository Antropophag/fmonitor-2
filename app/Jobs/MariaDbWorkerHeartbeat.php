<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;
final class MariaDbWorkerHeartbeat
{
    private MariaDbJobsSession$sql;
    public function __construct(\mysqli$db,string$prefix){$this->sql=new MariaDbJobsSession($db,$prefix);}
    public function observe(string$workerId,string$instant):void{$worker=JobValues::text($workerId,'/^[A-Za-z0-9][A-Za-z0-9_.:-]{0,79}$/D');$at=JobValues::date($instant);$s=$this->sql;$s->execute('INSERT INTO '.$s->table('fm2_worker_heartbeats').'(worker_id,observed_at_utc)VALUES(?,?) ON DUPLICATE KEY UPDATE observed_at_utc=VALUES(observed_at_utc)',[$worker,$at]);}
}
