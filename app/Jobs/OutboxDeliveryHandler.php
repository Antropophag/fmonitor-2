<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** A generic worker handler; the originating domain command is never called here. */
final class OutboxDeliveryHandler
{
    private \Closure $transport;
    private \Closure $clock;
    public function __construct(private MariaDbOutbox $outbox,callable $transport,callable $clock)
    {
        $this->transport=\Closure::fromCallable($transport);$this->clock=\Closure::fromCallable($clock);
    }
    public function handle(array $job): array
    {
        $this->outbox->assertIdle();
        if(($job['jobType']??null)!=='outbox.dispatch'||($job['payloadVersion']??null)!==1)throw new \InvalidArgumentException('Invalid outbox job.');
        $id=JobValues::number($job['payload']['intentId']??null);$jobId=JobValues::number($job['jobId']??null);
        $attempt=JobValues::number($job['attempt']??null,1,5);
        $prior=$this->outbox->attempt($id,$jobId,$attempt);if($prior!==null)return $prior;
        $intent=$this->outbox->read($id);if($intent===null)throw new \RuntimeException('OUTBOX_INTENT_MISSING');
        if($intent['status']==='delivered')return OutboxDeliveryOutcome::safe(['status'=>'delivered','providerReference'=>$intent['providerReference']]);
        if($intent['status']==='dead'&&!$this->outbox->canRetryDead($id,$jobId))return ['status'=>'permanent','failureCode'=>'OUTBOX_INTENT_DEAD'];
        $message=array_intersect_key($intent,array_flip(['intentId','eventId','channel','template','version','data','idempotencyReference']));
        try{$delivered=($this->transport)($message);}catch(\Throwable){$delivered=['status'=>'ambiguous_retryable','failureCode'=>'TRANSPORT_UNAVAILABLE'];}
        $safe=OutboxDeliveryOutcome::safe($delivered);
        return $this->outbox->recordAttempt($id,$jobId,$attempt,$safe,$intent['idempotencyReference'],JobValues::date(($this->clock)()));
    }
}
