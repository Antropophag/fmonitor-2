<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;
use FMonitor2\InstallationProcess\MariaDbWorkforceSynchronization;
use FMonitor2\Workforce\{BitrixWorkforceDeliveryConfig,BitrixWorkforceDeliveryConfigurationUnavailable,BitrixWorkforceDeliveryFactory};

final class JobHandlerRuntime
{
    public static function handle(array $job,JobsRuntimeConfiguration $config): array
    {
        if($job['jobType']==='outbox.dispatch')return['status'=>'permanent','failureCode'=>'OUTBOX_TRANSPORT_UNCONFIGURED'];
        $id=filter_var($config->value('FMONITOR_BITRIX_WEBHOOK_USER_ID'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$departments=json_decode($config->value('FMONITOR_BITRIX_DEPARTMENT_IDS_JSON'),true,8,JSON_THROW_ON_ERROR);if($id===false||!is_array($departments))throw new \InvalidArgumentException();
        try{$delivery=BitrixWorkforceDeliveryFactory::create(new BitrixWorkforceDeliveryConfig($config->value('FMONITOR_BITRIX_ORIGIN'),$id,$config->value('FMONITOR_BITRIX_TOKEN_FILE'),$departments,caFile:$config->optionalValue('FMONITOR_BITRIX_CA_FILE')));}
        catch(BitrixWorkforceDeliveryConfigurationUnavailable){throw new \InvalidArgumentException();}
        $db=MariaDbJobsConnection::open($config);try{$result=(new MariaDbWorkforceJobHandler(new MariaDbWorkforceSynchronization($db,$config->prefix()),$delivery))->handle($job);}finally{$db->close();}
        if($result['status']==='completed')return['status'=>'completed','result'=>['delivered'=>(int)$result['delivered']]];
        $code=(string)($result['failureCode']??'WORKFORCE_SYNC_FAILED');return['status'=>'retryable','failureCode'=>preg_match('/^[A-Z][A-Z0-9_]{0,63}$/D',$code)===1?$code:'WORKFORCE_SYNC_FAILED'];
    }
}
