<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Commands;
use FMonitor2\RuntimeRestore\StandBackupApplication;
use FMonitor2\RuntimeRestore\StandBackupFilesystem;
use yii\console\Controller;
final class StandBackupController extends Controller
{
    public function actionCreate():int{return$this->dispatch('create');}
    public function actionVerify():int{return$this->dispatch('verify');}
    private function dispatch(string$c):int{$p=$this->arguments($c,array_slice($GLOBALS['FMONITOR2_RAW_ARGV']??[],1));if($p===null)return$this->finish(['ok'=>false,'reason'=>'CONFIGURATION_INVALID'],64);$o=(new StandBackupApplication())->run($c,$p['manifest'],$p['operation-id'],$p['fixture-driver']??null,$p['bundle-digest']??null);return$this->finish($o['result'],$o['exitCode']);}
    private function arguments(string$c,array$a):?array{if(($a[0]??null)!=='stand-backup/'.$c||end($a)!=='--interactive=0')return null;$allowed=$c==='create'?['manifest','operation-id','fixture-driver']:['manifest','operation-id','fixture-driver','bundle-digest'];$out=[];foreach(array_slice($a,1,-1)as$item){if(preg_match('/^--([a-z-]+)=(.*)$/Ds',$item,$m)!==1||!in_array($m[1],$allowed,true)||isset($out[$m[1]])||($m[2]===''&&$m[1]!=='operation-id'))return null;$out[$m[1]]=$m[2];}if(!isset($out['manifest'])||($c==='verify'&&!isset($out['bundle-digest'])))return null;$out['operation-id']=$out['operation-id']??null;return$out;}
    private function finish(array$r,int$c):int{echo StandBackupFilesystem::canonical($r);return$c;}
}
