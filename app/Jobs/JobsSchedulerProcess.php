<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** Foreground scheduler orchestration; transport is never constructed here. */
final class JobsSchedulerProcess
{
    private bool $stop=false;
    private \Closure $clock;
    public function __construct(private MariaDbWorkforceScheduler $scheduler,private OutboxDispatchScheduler $outbox,
        private MariaDbWorkerHeartbeat $heartbeat,callable $clock,private string $identity)
    {
        $this->clock=\Closure::fromCallable($clock);
    }
    public function run(): array
    {
        pcntl_async_signals(true);$stop=function(): void{$this->stop=true;};
        pcntl_signal(SIGTERM,$stop);pcntl_signal(SIGQUIT,$stop);$last=null;
        while(!$this->stop){
            $now=JobValues::date(($this->clock)());
            $this->scheduler->tick($now);$this->outbox->enqueuePending();
            if($last===null||$now>=JobValues::plus($last,60)){$this->heartbeat->observe($this->identity,$now);$last=$now;}
            if(!$this->stop)usleep(1_000_000);
        }
        return ['status'=>'stopped','exitCode'=>0];
    }
}
