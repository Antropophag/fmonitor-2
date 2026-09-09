<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** Post-commit adapter: schedules durable generic jobs and never invokes transport. */
final readonly class OutboxDispatchScheduler
{
    public function __construct(private MariaDbOutbox $outbox,private JobQueue $queue) {}
    public function enqueuePending(): array
    {
        $count=0;
        foreach($this->outbox->pending() as $intent){
            $identity=substr($intent['idempotencyReference'],0,32);
            $identity[12]='4';$identity[16]=dechex((hexdec($identity[16])&3)|8);
            $uuid=substr($identity,0,8).'-'.substr($identity,8,4).'-'.substr($identity,12,4).'-'.substr($identity,16,4).'-'.substr($identity,20,12);
            $result=$this->queue->enqueue(['jobType'=>'outbox.dispatch','payloadVersion'=>1,'payload'=>['intentId'=>$intent['intentId']],
                'availableAtUtc'=>$intent['createdAtUtc'],'idempotencyKey'=>$uuid,'actor'=>['type'=>'system','id'=>'outbox-dispatch']]);
            if($result['status']==='conflict')throw new \RuntimeException('OUTBOX_JOB_CONFLICT');
            if($result['status']==='created')$count++;
        }
        return ['enqueued'=>$count];
    }
}
