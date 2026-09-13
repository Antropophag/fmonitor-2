<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Commands;
use FMonitor2\RuntimeRestore\StandBackupFilesystem;
use FMonitor2\RuntimeRestore\StandRestoreApplication;
use yii\console\Controller;
final class StandRestoreController extends Controller
{
    public $manifest=null;
    public $bundleDigest=null;
    public $operationId=null;
    public $fixtureDriver=null;
    public function options($actionID):array{return array_merge(parent::options($actionID),['manifest','bundleDigest','operationId','fixtureDriver']);}
    public function actionRun():int{$p=$this->arguments(array_slice($GLOBALS['FMONITOR2_RAW_ARGV']??[],1));if($p===null)return$this->finish(['ok'=>false,'reason'=>'CONFIGURATION_INVALID'],64);$o=(new StandRestoreApplication())->run($p['manifest'],$p['bundle-digest'],$p['operation-id'],$p['fixture-driver']??null);if(($o['result']['outcome']??null)==='RESTORE_VERIFIED')$o['result']['bundle_digest']=$p['bundle-digest'];return$this->finish($o['result'],$o['exitCode']);}
    private function arguments(array $args):?array{if(($args[0]??null)!=='stand-restore/run'||end($args)!=='--interactive=0')return null;$out=[];foreach(array_slice($args,1,-1)as$item){if(preg_match('/^--([a-z-]+)=(.*)$/Ds',$item,$m)!==1||!in_array($m[1],['manifest','bundle-digest','operation-id','fixture-driver'],true)||isset($out[$m[1]])||$m[2]==='')return null;$out[$m[1]]=$m[2];}foreach(['manifest','bundle-digest','operation-id']as$key)if(!isset($out[$key]))return null;return$out;}
    private function finish(array$result,int$code):int{echo StandBackupFilesystem::canonical($result);return$code;}
}
