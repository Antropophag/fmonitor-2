<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Commands;

require_once dirname(__DIR__,3).'/vendor/autoload.php';

use FMonitor2\InstallationProcess\BitrixOrderDocumentDelivery;
use FMonitor2\Jobs\JobsRuntimeConfiguration;
use yii\console\Controller;

final class BitrixOrderDocumentLinksSyncController extends Controller
{
    public function actionRun():int
    {
        if(array_slice($GLOBALS['FMONITOR2_RAW_ARGV']??[],1)!==['bitrix-order-document-links-sync/run','--interactive=0'])return$this->finish(['status'=>'failed','reason'=>'CONFIGURATION_UNAVAILABLE'],64);
        $result=BitrixOrderDocumentDelivery::runFromEnvironment();return$this->finish($result,$result['status']==='published'?0:1);
    }
    public static function runOnce(callable$delivery,callable$application):array
    {
        try{$result=$delivery();}catch(\Throwable){return['status'=>'failed','reason'=>'TRANSPORT_FAILED'];}
        if(!is_object($result)||($result->kind??null)!=='complete')return['status'=>'failed','reason'=>(is_object($result)&&is_string($result->reason??null))?$result->reason:'DELIVERY_FAILED'];
        return$application($result->links);
    }
    public static function runJob(JobsRuntimeConfiguration$config):array{return BitrixOrderDocumentDelivery::runForJob($config);}
    private function finish(array$result,int$code):int{echo json_encode($result,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),"\n";return$code;}
}
