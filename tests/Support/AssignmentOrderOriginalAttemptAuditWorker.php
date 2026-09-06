<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;

final class OriginalAttemptAuditWorker
{
    public static function denied(OriginalIntegrityDatabase $f):array
    {
        $path=$f->control.'/audit-worker.json';
        $config=['databaseDsn'=>'host='.$f->host.';port='.$f->port.';database='.$f->database.';charset=utf8mb4',
            'databaseUser'=>$f->user,'databasePasswordFile'=>$f->passwordFile,'tablePrefix'=>$f->prefix,
            'privateStorageRoot'=>$f->privateRoot,'safeLogFile'=>$f->safeLog,'clockUtc'=>'2026-09-06T09:00:00Z',
            'rootIdSequenceCsv'=>'original-0071','revisionIdSequenceCsv'=>'revision-0071','inspectorMode'=>'injected_passive',
            'faultPoint'=>null,'barrierEvent'=>'after_fingerprint_miss_before_cas'];
        file_put_contents($path,json_encode($config,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES)."\n");chmod($path,0600);
        $command=['requestId'=>'00000000-0000-4000-8000-000000000711','mode'=>'initial','installationCaseId'=>4512,'assignmentOrderId'=>81,
            'actorUserId'=>999,'documentDate'=>'2026-09-01','compositionConfirmed'=>true,'rootOriginalId'=>null,'targetRevisionId'=>null,
            'expectedCurrentRevisionId'=>null,'correctionReason'=>null,'upload'=>['bytesBase64'=>base64_encode(OriginalIntegrityFixture::pdf()),'originalFilename'=>'original.pdf','declaredMediaType'=>'application/pdf']];
        $pairs=[];$pipes=[];$process=null;$exit=null;$out=['barrier'=>'','result'=>'','stdout'=>'','stderr'=>''];
        try {
            for($i=0;$i<4;$i++){ $pair=stream_socket_pair(STREAM_PF_UNIX,STREAM_SOCK_STREAM,STREAM_IPPROTO_IP);if($pair===false)throw new \TestFailure('Worker socket setup failed');$pairs[]=$pair; }
            $process=proc_open([PHP_BINARY,__DIR__.'/assignment_order_original_worker_entry.php',$path],
                [0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w'],3=>$pairs[0][1],4=>$pairs[1][1],5=>$pairs[2][1],6=>$pairs[3][1]],$pipes);
            if(!is_resource($process))throw new \TestFailure('Worker process unavailable');
            foreach($pairs as $pair)fclose($pair[1]);
            $line=json_encode($command,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES)."\n";
            if(fwrite($pairs[0][0],$line)!==strlen($line))throw new \TestFailure('Worker command write incomplete');
            stream_socket_shutdown($pairs[0][0],STREAM_SHUT_WR);stream_socket_shutdown($pairs[1][0],STREAM_SHUT_WR);
            $channels=['barrier'=>$pairs[2][0],'result'=>$pairs[3][0],'stdout'=>$pipes[1],'stderr'=>$pipes[2]];
            foreach($channels as $channel)stream_set_blocking($channel,false);$deadline=hrtime(true)+8_000_000_000;
            do {
                $read=array_values(array_filter($channels,static fn($channel)=>!feof($channel)));$write=$except=[];
                if($read!==[]){if(stream_select($read,$write,$except,0,100_000)===false)throw new \TestFailure('Worker select failed');foreach($read as $channel){$key=array_search($channel,$channels,true);$bytes=stream_get_contents($channel,4096);if($bytes===false)throw new \TestFailure('Worker output failed');$out[$key].=$bytes;}}
                $state=proc_get_status($process);if(!$state['running']&&$state['exitcode']!==-1)$exit=$state['exitcode'];
                if(hrtime(true)>=$deadline||strlen(implode('',$out))>32768)throw new \TestFailure('Worker time/output bound exceeded');
            }while($state['running']||count(array_filter($channels,static fn($channel)=>!feof($channel)))>0);
        } finally {
            if(is_resource($process)&&proc_get_status($process)['running'])proc_terminate($process,9);
            foreach($pipes as $pipe)if(is_resource($pipe))fclose($pipe);
            foreach($pairs as $pair)foreach($pair as $endpoint)if(is_resource($endpoint))fclose($endpoint);
            if(is_resource($process)){$closed=proc_close($process);$exit??=$closed;}
        }
        return ['exit'=>$exit]+$out;
    }
}
