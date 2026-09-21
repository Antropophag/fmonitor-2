<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Commands;

require_once dirname(__DIR__,3).'/vendor/autoload.php';
use FMonitor2\InstallationProcess\{EquipmentFactsApplication,ErpEquipmentFactsDelivery,ErpEquipmentFactsDeliveryConfig,NativeErpEquipmentFactsDelivery};
use FMonitor2\Jobs\JobsRuntimeConfiguration;
use yii\console\Controller;

final class ErpEquipmentFactsSyncController extends Controller
{
    public function actionRun():int{$result=self::production(JobsRuntimeConfiguration::fromEnvironment());echo json_encode($result,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),"\n";return$result['status']==='completed'?0:1;}
    public static function runWith(callable$delivery,callable$owner,string$runId,string$observedAt):array{return ErpEquipmentFactsDelivery::run($delivery,$owner,$runId,$observedAt);}
    public static function runJob(JobsRuntimeConfiguration$config):array{return self::production($config);}
    private static function production(JobsRuntimeConfiguration$c):array
    {
        $run=self::uuid();try{$db=new \mysqli($c->value('FMONITOR_DB_HOST'),$c->value('FMONITOR_DB_USER'),$c->value('FMONITOR_DB_PASSWORD'),$c->value('FMONITOR_DB_NAME'),$c->port());$db->set_charset('utf8mb4');$owner=new EquipmentFactsApplication($db,$c->prefix(),$c->value('FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY'));$at=(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');$fetch=static function()use($c):array{$source=new ErpEquipmentFactsDeliveryConfig($c->value('FMONITOR_ERP_HOST'),$c->value('FMONITOR_ERP_DATABASE'),$c->value('FMONITOR_ERP_USER'),$c->value('FMONITOR_ERP_PASSWORD'));$pdo=new \PDO('sqlsrv:Server='.$source->host.';Database='.$source->database,$source->user,$source->password,[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION]);return(new NativeErpEquipmentFactsDelivery($source,static function(string$sql,array$options)use($pdo):array{$statement=$pdo->query($sql);$rows=$statement->fetchAll(\PDO::FETCH_ASSOC);if(count($rows)>$options['maxRows'])throw new \RuntimeException();return$rows;}))->fetch();};return self::runWith($fetch,$owner->execute(...),$run,$at);}catch(\Throwable){return['status'=>'failed','runId'=>$run,'reason'=>'SOURCE_UNAVAILABLE'];}finally{if(isset($db)&&$db instanceof \mysqli)$db->close();}
    }
    private static function uuid():string{$h=bin2hex(random_bytes(16));$h[12]='4';$h[16]=dechex((hexdec($h[16])&3)|8);return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20);}
}
