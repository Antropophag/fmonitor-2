<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;
use FMonitor2\YiiRuntime\WorkforceSyncConsole;
use FMonitor2\YiiRuntime\Commands\BitrixOrderDocumentLinksSyncController;

final class JobHandlerRuntime
{
    public static function handle(array $job,JobsRuntimeConfiguration $config,?callable$documentSync=null): array
    {
        if($job['jobType']==='outbox.dispatch')return['status'=>'permanent','failureCode'=>'OUTBOX_TRANSPORT_UNCONFIGURED'];
        if($job['jobType']==='bitrix.order-document-links.sync'){
            try{$result=$documentSync===null?BitrixOrderDocumentLinksSyncController::runJob($config):$documentSync();}catch(\Throwable){return['status'=>'retryable','failureCode'=>'BITRIX_ORDER_DOCUMENT_LINKS_SYNC_FAILED'];}
            if(($result['status']??null)==='published')return['status'=>'completed','result'=>['published'=>(int)($result['count']??0)]];
            return['status'=>'retryable','failureCode'=>'BITRIX_ORDER_DOCUMENT_LINKS_SYNC_FAILED'];
        }
        $result=WorkforceSyncConsole::runJob($job,$config);
        if($result['status']==='completed')return['status'=>'completed','result'=>['delivered'=>(int)$result['delivered']]];
        $code=(string)($result['failureCode']??'WORKFORCE_SYNC_FAILED');return['status'=>'retryable','failureCode'=>preg_match('/^[A-Z][A-Z0-9_]{0,63}$/D',$code)===1?$code:'WORKFORCE_SYNC_FAILED'];
    }
}
