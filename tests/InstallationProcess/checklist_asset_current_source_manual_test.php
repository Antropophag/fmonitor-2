<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

function checklistAssetPort():int{$s=stream_socket_server('tcp://127.0.0.1:0',$e,$m);if(!is_resource($s))throw new TestFailure('SETUP_FAILURE: port');$name=(string)stream_socket_get_name($s,false);fclose($s);return(int)substr($name,strrpos($name,':')+1);}
function checklistAssetRequest(int$port):array{$s=stream_socket_client("tcp://127.0.0.1:$port",$e,$m,2);if(!is_resource($s))throw new TestFailure('SETUP_FAILURE: request');fwrite($s,"GET /pilot/assets/checklist.js HTTP/1.1\r\nHost: 127.0.0.1:$port\r\nConnection: close\r\n\r\n");$raw=(string)stream_get_contents($s);fclose($s);[$head,$body]=array_pad(explode("\r\n\r\n",$raw,2),2,'');preg_match('#^HTTP/1\.1 ([0-9]{3})#',$head,$m);$headers=[];foreach(array_slice(explode("\r\n",$head),1)as$line){$at=strpos($line,':');if($at!==false)$headers[strtolower(substr($line,0,$at))]=trim(substr($line,$at+1));}return[(int)($m[1]??0),$headers,$body];}

$root=dirname(__DIR__,2);$port=checklistAssetPort();$server=null;
try{
 $server=proc_open([PHP_BINARY,'-d','display_errors=0','-S',"127.0.0.1:$port",$root.'/rapid-pilot/router.php'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,array_replace(getenv(),['FMONITOR_TRUSTED_REQUEST_SCHEME'=>'http']));
 if(!is_resource($server))throw new TestFailure('SETUP_FAILURE: server');foreach($pipes as$pipe)stream_set_blocking($pipe,false);$deadline=microtime(true)+3;do{$socket=@stream_socket_client("tcp://127.0.0.1:$port",$e,$m,.1);if(is_resource($socket)){fclose($socket);break;}usleep(20000);}while(microtime(true)<$deadline);
 [$status,$headers,$body]=checklistAssetRequest($port);$source=(string)file_get_contents($root.'/app/PilotHttp/checklist.js');
 assertSameValue(200,$status,'current checklist asset is served over the real pilot HTTP router');
 assertSameValue('text/javascript; charset=UTF-8',$headers['content-type']??null,'JavaScript content type');
 assertSameValue('no-store',$headers['cache-control']??null,'asset cache policy');
 assertSameValue('nosniff',$headers['x-content-type-options']??null,'asset MIME protection');
 assertSameValue((string)strlen($source),$headers['content-length']??null,'source byte length');
 assertSameValue(hash('sha256',$source),hash('sha256',$body),'router serves current committed checklist source unchanged');
 echo "PASS checklist asset serves current source unchanged\n";
}finally{
 if(is_resource($server)){if(proc_get_status($server)['running'])proc_terminate($server,15);foreach($pipes as$pipe){stream_get_contents($pipe);fclose($pipe);}proc_close($server);}
}
