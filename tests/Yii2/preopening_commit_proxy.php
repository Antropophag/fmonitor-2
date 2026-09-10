<?php
declare(strict_types=1);
// Test-only transparent MySQL relay: drop exactly one acknowledged opening COMMIT.
$listen=stream_socket_server('tcp://127.0.0.1:0',$errno,$error);
if(!is_resource($listen))exit(1);
$address=stream_socket_get_name($listen,false);echo substr($address,strrpos($address,':')+1)."\n";flush();
pcntl_async_signals(true);pcntl_signal(SIGCHLD,static function(){while(pcntl_waitpid(-1,$status,WNOHANG)>0){}});
while($client=stream_socket_accept($listen,60)) {
    $pid=pcntl_fork();if($pid===-1)exit(2);if($pid>0){fclose($client);continue;}
    fclose($listen);
    $backend=stream_socket_client('tcp://'.getenv('FMONITOR_TEST_DB_HOST').':'.getenv('FMONITOR_TEST_DB_PORT'),$errno,$error,5);
    if(!is_resource($backend))exit(3);
    stream_set_blocking($client,false);stream_set_blocking($backend,false);$buffer='';$opening=false;
    $send=static function($stream,string $bytes):void {
        while($bytes!==''){$n=fwrite($stream,$bytes);if($n===false||$n===0)throw new RuntimeException('relay write');$bytes=substr($bytes,$n);}
    };
    try {
        while(true) {
            $read=[$client,$backend];$write=$except=[];
            if(stream_select($read,$write,$except,20)<1)break;
            foreach($read as $stream) {
                $chunk=fread($stream,65536);if($chunk===false||($chunk===''&&feof($stream)))exit(0);if($chunk==='')continue;
                if($stream===$backend){$send($client,$chunk);continue;}
                $buffer.=$chunk;
                while(strlen($buffer)>=4) {
                    $length=ord($buffer[0])|(ord($buffer[1])<<8)|(ord($buffer[2])<<16);
                    if(strlen($buffer)<$length+4)break;
                    $packet=substr($buffer,0,$length+4);$buffer=substr($buffer,$length+4);$payload=substr($packet,4);
                    // Only the compound opening performs this UPDATE; read snapshots/login cannot arm it.
                    if($payload!==''&&in_array(ord($payload[0]),[3,22],true)&&str_contains(strtolower($payload),'update')&&str_contains($payload,'actual_start_date'))$opening=true;
                    if($opening&&$payload!==''&&ord($payload[0])===3&&strtoupper(trim(substr($payload,1)))==='COMMIT') {
                        $claim=@fopen(getenv('PREOPENING_COMMIT_TRANSCRIPT'),'x');
                        if(is_resource($claim)) {
                            $send($backend,$packet);stream_set_blocking($backend,true);stream_set_timeout($backend,5);
                            $ack=fread($backend,65536);
                            if(!is_string($ack)||strlen($ack)<5||ord($ack[4])!==0)throw new RuntimeException('COMMIT not acknowledged');
                            fwrite($claim,"OPENING_COMMIT_ACKNOWLEDGED_RESPONSE_DROPPED\n");fclose($claim);exit(0);
                        }
                    }
                    $send($backend,$packet);
                }
            }
        }
    }finally{fclose($backend);fclose($client);}
    exit(0);
}
