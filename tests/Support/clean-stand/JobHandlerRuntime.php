<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;
use FMonitor2\YiiRuntime\WorkforceSyncConsole;

/** Acceptance-only transport composition mounted over the worker handler. */
final class JobHandlerRuntime
{
    public static function handle(array $job, JobsRuntimeConfiguration $config): array
    {
        if (($job['jobType'] ?? null) !== 'outbox.dispatch') {
            $result = WorkforceSyncConsole::runJob($job, $config);
            if ($result['status'] === 'completed') return ['status'=>'completed','result'=>['delivered'=>(int)$result['delivered']]];
            $code=(string)($result['failureCode']??'WORKFORCE_SYNC_FAILED');
            return ['status'=>'retryable','failureCode'=>preg_match('/^[A-Z][A-Z0-9_]{0,63}$/D',$code)===1?$code:'WORKFORCE_SYNC_FAILED'];
        }
        $db=MariaDbJobsConnection::open($config);
        try {
            $outbox=new MariaDbOutbox($db,$config->prefix());
            $transport=static fn(array $message): array=>[
                'status'=>'delivered',
                'providerReference'=>'acceptance-'.hash('sha256',(string)$message['eventId']),
            ];
            $outcome=(new OutboxDeliveryHandler($outbox,$transport,static fn():string=>(new MariaDbJobsSession($db,$config->prefix()))->now()))->handle($job);
            if($outcome['status']==='delivered')return ['status'=>'completed','result'=>['outcome'=>'delivered','providerReference'=>$outcome['providerReference']??null]];
            if($outcome['status']==='ambiguous_retryable')return ['status'=>'retryable','failureCode'=>$outcome['failureCode']??'TRANSPORT_UNAVAILABLE'];
            return ['status'=>'permanent','failureCode'=>$outcome['failureCode']??'OUTBOX_DELIVERY_FAILED'];
        } finally {
            $db->close();
        }
    }
}
