<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;

/** Bounded independent child; no sleep, shell interpolation or inherited DB connection. */
final class OriginalIntegrityFreshProcess
{
    public static function run(OriginalIntegrityDatabase $fixture,string $requestId,array $overrides=[]):array
    {
        $config=$fixture->control.'/fresh-reader-config.json';
        $values=['databaseHost'=>$fixture->host,'databasePort'=>$fixture->port,'databaseName'=>$fixture->database,'databaseUser'=>$fixture->user,'databasePasswordFile'=>$fixture->passwordFile,'tablePrefix'=>$fixture->prefix];
        foreach($overrides as $key=>$value){if(!array_key_exists($key,$values))throw new \LogicException('extra fresh config key');$values[$key]=$value;}
        file_put_contents($config,json_encode($values,JSON_THROW_ON_ERROR));chmod($config,0600);
        $pipes=[];$process=proc_open([PHP_BINARY,__DIR__.'/AssignmentOrderOriginalIntegrityFreshReaderChild.php',$config,$requestId],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($process))throw new \RuntimeException('SETUP_FAILURE: fresh child unavailable');
        fclose($pipes[0]);stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$out='';$error='';$exit=null;$start=hrtime(true);$timedOut=false;
        try{
            while(true){
                $out.=(string)stream_get_contents($pipes[1]);$error.=(string)stream_get_contents($pipes[2]);
                if(strlen($out)>65536||strlen($error)>4096)throw new \RuntimeException('fresh child protocol size');
                $status=proc_get_status($process);if(!$status['running']){$exit=$status['exitcode'];break;}
                if(hrtime(true)-$start>10_000_000_000){$timedOut=true;break;}
                $read=[];foreach([$pipes[1],$pipes[2]] as $pipe)if(!feof($pipe))$read[]=$pipe;
                if($read!==[]){$write=null;$except=null;stream_select($read,$write,$except,0,100000);}
            }
            if($timedOut)proc_terminate($process,9);
            $out.=(string)stream_get_contents($pipes[1]);$error.=(string)stream_get_contents($pipes[2]);
        }finally{foreach([1,2] as $i)if(is_resource($pipes[$i]))fclose($pipes[$i]);$status=proc_get_status($process);if($status['running'])proc_terminate($process,9);$closed=proc_close($process);if($exit===null||$exit<0)$exit=$closed;}
        return ['exit'=>$exit,'out'=>$out,'error'=>$error,'timedOut'=>$timedOut];
    }
}
