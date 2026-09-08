<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Child-owned monotonic bounds over the admitted socket wrappers. */
final class AssignmentOrderOriginalWorkerBoundary
{
    public static function commandLine($stream):string
    {
        $line='';
        while(true){
            $remaining=29000000-strlen($line);
            if($remaining<=0)throw new \RuntimeException();
            $chunk=@fread($stream,min(65536,$remaining));
            if($chunk===false||$chunk==='')throw new \RuntimeException();
            $line.=$chunk;$lf=strpos($line,"\n");
            if($lf===false)continue;
            if($lf!==strlen($line)-1)throw new \RuntimeException();
            $deadline=hrtime(true)+5_000_000_000;
            while(!feof($stream)){
                self::readable($stream,$deadline);
                $extra=@fread($stream,1);
                if($extra===false||$extra!=='')throw new \RuntimeException();
            }
            return $line;
        }
    }
    public static function releaseLine($stream,string $expected):void
    {
        $deadline=hrtime(true)+5_000_000_000;$line='';
        while(!str_contains($line,"\n")){
            $remaining=strlen($expected)-strlen($line);
            if($remaining<=0)throw new \RuntimeException();
            self::readable($stream,$deadline);
            $chunk=@fread($stream,$remaining);
            if($chunk===false||$chunk==='')throw new \RuntimeException();
            $line.=$chunk;
        }
        if($line!==$expected)throw new \RuntimeException();
    }
    private static function readable($stream,int $deadline):void
    {
        $remaining=$deadline-hrtime(true);
        if($remaining<=0)throw new \RuntimeException();
        $read=[$stream];$write=$except=null;
        $ready=@stream_select($read,$write,$except,intdiv($remaining,1_000_000_000),intdiv($remaining%1_000_000_000,1000));
        if($ready!==1)throw new \RuntimeException();
    }
}
