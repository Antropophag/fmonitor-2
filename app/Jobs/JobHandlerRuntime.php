<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;
use FMonitor2\YiiRuntime\WorkforceSyncConsole;

final class JobHandlerRuntime
{
    public static function handle(array $job,JobsRuntimeConfiguration $config): array
    {
        if($job['jobType']==='outbox.dispatch')return['status'=>'permanent','failureCode'=>'OUTBOX_TRANSPORT_UNCONFIGURED'];
        $result=WorkforceSyncConsole::runJob($job,$config);
        if($result['status']==='completed')return['status'=>'completed','result'=>['delivered'=>(int)$result['delivered']]];
        $code=(string)($result['failureCode']??'WORKFORCE_SYNC_FAILED');return['status'=>'retryable','failureCode'=>preg_match('/^[A-Z][A-Z0-9_]{0,63}$/D',$code)===1?$code:'WORKFORCE_SYNC_FAILED'];
    }
}
