<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** One consistent read-only view of current process freshness and queue counters. */
final class MariaDbJobsHealth
{
    private MariaDbJobsSession $sql;
    public function __construct(\mysqli $db,string $prefix,callable $clock,private array $config)
    {
        JobValues::keys($config,['workerHeartbeatId','schedulerHeartbeatId','workerFreshSeconds','schedulerFreshSeconds','readyMaxAgeSeconds']);
        foreach(['workerHeartbeatId','schedulerHeartbeatId'] as $key)JobValues::text($config[$key],'/^[A-Za-z0-9][A-Za-z0-9_.:-]{0,79}$/D');
        foreach(['workerFreshSeconds','schedulerFreshSeconds','readyMaxAgeSeconds'] as $key)JobValues::number($config[$key],1,86400);
        $this->sql=new MariaDbJobsSession($db,$prefix,$clock);
    }
    public function read(): array
    {
        $s=$this->sql;
        return $s->readOnly(function()use($s): array{
            $now=$s->now();$table=$s->table('fm2_jobs');
            $cutoff=$this->before($now,$this->config['readyMaxAgeSeconds']);
            $dead=(int)$s->rows("SELECT COUNT(*) n FROM {$table} WHERE status='dead'")[0]['n'];
            $expired=(int)$s->rows("SELECT COUNT(*) n FROM {$table} WHERE status='leased' AND lease_expires_at_utc<=?",[$now])[0]['n'];
            $overdue=(int)$s->rows("SELECT COUNT(*) n FROM {$table} WHERE status='ready' AND available_at_utc<?",[$cutoff])[0]['n'];
            $oldest=$s->rows("SELECT MIN(available_at_utc) oldest FROM {$table} WHERE status='ready'")[0]['oldest'];
            $worker=$this->heartbeat($this->config['workerHeartbeatId']);$scheduler=$this->heartbeat($this->config['schedulerHeartbeatId']);
            $reasons=[];
            if($dead>0)$reasons[]='dead_jobs';if($expired>0)$reasons[]='expired_leases';if($overdue>0)$reasons[]='overdue_ready';
            if(!$this->fresh($scheduler,$now,$this->config['schedulerFreshSeconds']))$reasons[]='stale_scheduler';
            if(!$this->fresh($worker,$now,$this->config['workerFreshSeconds']))$reasons[]='stale_worker';
            return ['ok'=>$reasons===[],'reasons'=>$reasons,'counters'=>['deadJobs'=>$dead,'expiredLeases'=>$expired,'overdueReady'=>$overdue],
                'oldestReadyAtUtc'=>$oldest,'workerHeartbeatAtUtc'=>$worker,'schedulerHeartbeatAtUtc'=>$scheduler];
        });
    }
    private function heartbeat(string $identity): ?string
    {
        $s=$this->sql;return $s->rows('SELECT observed_at_utc FROM '.$s->table('fm2_worker_heartbeats').' WHERE worker_id=?',[$identity])[0]['observed_at_utc']??null;
    }
    private function fresh(?string $instant,string $now,int $seconds): bool
    {
        return $instant!==null&&$instant<=$now&&$instant>=$this->before($now,$seconds);
    }
    private function before(string $now,int $seconds): string
    {
        return (new \DateTimeImmutable($now))->modify('-'.$seconds.' seconds')->format('Y-m-d\TH:i:s.u\Z');
    }
}
