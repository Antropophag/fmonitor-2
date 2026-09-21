<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class ErpEquipmentFactsDelivery
{
    public static function run(callable$delivery,callable$owner,string$runId,string$observedAt):array
    {
        try{$result=$delivery();}catch(\Throwable){$result=['status'=>'failed','reason'=>'SOURCE_UNAVAILABLE'];}
        $base=['actor'=>['type'=>'system','id'=>'erp-equipment-facts-hourly-v1'],'kind'=>$result['status']==='complete'?'complete':'failed','runId'=>$runId,'observedAtUtc'=>$observedAt];$command=$result['status']==='complete'?$base+['records'=>$result['records']]:$base+['reason'=>in_array($result['reason']??null,['SOURCE_UNAVAILABLE','SOURCE_INVALID'],true)?$result['reason']:'SOURCE_UNAVAILABLE'];return$owner($command);
    }
}
