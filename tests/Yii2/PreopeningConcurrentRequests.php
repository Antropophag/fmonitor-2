<?php
declare(strict_types=1);
/** Sends all requests before receiving any response, to independent Yii server processes. */
function preopeningConcurrent(array $requests):array
{
    $sockets=[];$results=[];
    try {
        foreach($requests as$r){
            $socket=fsockopen('127.0.0.1',$r['port'],$errno,$error,3);if(!is_resource($socket))throw new TestFailure('SETUP_FAILURE concurrent listener');
            stream_set_timeout($socket,30);$sockets[]=$socket;
            $lines=['POST '.$r['path'].' HTTP/1.1','Host: 127.0.0.1:'.$r['port'],'Connection: close','Content-Length: '.strlen($r['body']),'Cookie: '.implode('; ',array_map(static fn($k,$v)=>$k.'='.$v,array_keys($r['cookies']),$r['cookies']))];
            $bytes=implode("\r\n",array_merge($lines,$r['headers']))."\r\n\r\n".$r['body'];
            while($bytes!==''){$n=fwrite($socket,$bytes);if(!is_int($n)||$n<1)throw new TestFailure('concurrent send failed');$bytes=substr($bytes,$n);}
        }
        foreach($sockets as$socket){$raw=stream_get_contents($socket);assertSameValue(false,stream_get_meta_data($socket)['timed_out'],'concurrent response bounded');[$headers,$body]=explode("\r\n\r\n",$raw,2);preg_match('#^HTTP/\S+ (\d+)#',$headers,$m);$results[]=['status'=>(int)($m[1]??0),'headers'=>$headers,'body'=>$body];}
    }finally{foreach($sockets as$s)fclose($s);}
    return$results;
}
