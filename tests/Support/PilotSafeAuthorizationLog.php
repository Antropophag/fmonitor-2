<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
final class PilotSafeAuthorizationLog
{
    public static function events(string $log): array
    {
        $events=[];
        foreach(preg_split('/\R/',trim($log))?:[] as $line){
            if($line==='')continue;
            if(preg_match('/^FMONITOR_AUTHORIZATION_UNAVAILABLE category=([A-Z_]+) correlation_id=([0-9a-f]{12})$/D',$line,$m)===1){$events[]=['category'=>$m[1],'correlationId'=>$m[2]];continue;}
            $timestamp='\[[A-Z][a-z]{2} [A-Z][a-z]{2} [ 0-9][0-9] [0-9]{2}:[0-9]{2}:[0-9]{2} [0-9]{4}\]';$loopback='127\.0\.0\.1:[1-9][0-9]{0,4}';
            if(preg_match('/^'.$timestamp.' PHP [0-9]+\.[0-9]+\.[0-9]+ Development Server \(http:\/\/'.$loopback.'\) started$/D',$line)===1)continue;
            if(preg_match('/^'.$timestamp.' '.$loopback.' (?:Accepted|Closing|Closed without sending a request; it was probably just an unused speculative preconnection)$/D',$line)===1)continue;
            if(preg_match('/^'.$timestamp.' '.$loopback.' \[[0-9]{3}\]: (?:GET|HEAD) \/pilot\/objects$/D',$line)===1)continue;
            throw new \TestFailure('unexpected application stderr: '.$line);
        }
        return $events;
    }
}
