<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/Yii2AuthFixture.php';
use FMonitor2\Tests\Yii2\Yii2AuthFixture;

// YII2-OTIZ-WORKFLOW-001 public seam: every retained OTIZ route is admitted by Yii2.
$root=dirname(__DIR__,2);$fixture=new Yii2AuthFixture($root);$socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$error);if(!is_resource($socket))throw new TestFailure('SETUP_FAILURE: port');preg_match('/:(\d+)$/D',(string)stream_socket_get_name($socket,false),$m);$port=(int)$m[1];fclose($socket);
$pipes=[];$env=getenv();foreach(array_keys($env)as$key)if(str_starts_with((string)$key,'FMONITOR_'))unset($env[$key]);$env=array_replace($env,$fixture->environment());$process=proc_open([PHP_BINARY,'-d','display_errors=0','-S',"127.0.0.1:$port",$root.'/public/yii.php'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$env);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: server');
try{$deadline=microtime(true)+5;$listening=false;do{$probe=@fsockopen('127.0.0.1',$port,$e,$s,.1);if(is_resource($probe)){fclose($probe);$listening=true;break;}usleep(20000);}while(microtime(true)<$deadline);if(!$listening)throw new TestFailure('SETUP_FAILURE: listen');
 $request=static function(string$method,string$path)use($port):array{$context=stream_context_create(['http'=>['ignore_errors'=>true,'follow_location'=>0,'method'=>$method,'header'=>"Host: fmonitor.example.test\r\nConnection: close"]]);$body=file_get_contents("http://127.0.0.1:$port$path",false,$context);$raw=$http_response_header??[];preg_match('#^HTTP/\S+ (\d+)#',$raw[0]??'',$match);$location=null;foreach($raw as$line)if(str_starts_with(strtolower($line),'location:'))$location=trim(substr($line,9));return[(int)($match[1]??0),$location,(string)$body];};
 assertSameValue(200,$request('GET','/pilot/login')[0],'setup: Yii2 login route responds');
 assertSameValue([303,'/pilot/login'],array_slice($request('GET','/pilot/otiz/payments'),0,2),'setup: existing Yii2 OTIZ route enforces guest admission');
 $failures=[];foreach([['GET','/pilot/otiz'],['GET','/pilot/otiz/objects'],['GET','/pilot/otiz/payments'],['GET','/pilot/otiz/history'],['GET','/pilot/otiz/snapshots/301'],['GET','/pilot/otiz/snapshots/301/export.xlsx'],['GET','/pilot/otiz/reconciliation'],['GET','/pilot/otiz/reconciliation/quarantine'],['GET','/pilot/otiz/active-baselines'],['GET','/pilot/otiz/historical-replay'],['GET','/pilot/assets/otiz.js'],['POST','/pilot/otiz/calculate'],['POST','/pilot/otiz/snapshots/301/accept'],['POST','/pilot/otiz/reconciliation/decisions'],['POST','/pilot/otiz/reconciliation/quarantine/decisions']]as[$method,$path]){[$status,$location]=$request($method,$path);$expected=$path==='/pilot/assets/otiz.js'?[200,null]:[303,'/pilot/login'];if([$status,$location]!==$expected)$failures[]="$method $path expected ".json_encode($expected)." got ".json_encode([$status,$location]);}
 assertSameValue([], $failures, 'INTENDED_RED: complete Yii2 OTIZ route/admission inventory');
 echo "PASS: YII2-OTIZ-WORKFLOW-001 Yii2 route admission\n";
}finally{proc_terminate($process);foreach($pipes as$pipe)if(is_resource($pipe))fclose($pipe);proc_close($process);$fixture->close();}
