<?php
declare(strict_types=1);
namespace FMonitor2\Runtime;

final class RuntimeStartupAttestation
{
    private const FORMAT='fmonitor-runtime-startup-readiness-v1';
    public static function path(RuntimeConfiguration $config):string{return$config->value('FMONITOR_SESSION_STATE_ROOT').'/runtime-startup-readiness.json';}
    public static function invalidate(RuntimeConfiguration $config):void{$path=self::path($config);if(is_link($path)||is_file($path))@unlink($path);}
    public static function publish(RuntimeConfiguration $config,array $marker):void
    {
        $path=self::path($config);$temporary=dirname($path).'/.runtime-startup-readiness.'.bin2hex(random_bytes(12));$payload=json_encode(self::expected($config,$marker),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";$mask=umask(0077);
        try{$handle=@fopen($temporary,'xb');if($handle===false)self::fail();try{if(fwrite($handle,$payload)!==strlen($payload)||!fflush($handle)||!fsync($handle))self::fail();}finally{fclose($handle);}if(!chmod($temporary,0600)||!rename($temporary,$path))self::fail();}finally{umask($mask);if(is_file($temporary))@unlink($temporary);}
    }
    public static function assertApplicable(RuntimeConfiguration $config,array $marker):void
    {
        $path=self::path($config);clearstatcache(true,$path);$stat=@lstat($path);if(!is_array($stat)||is_link($path)||!is_file($path)||($stat['mode']&0777)!==0600||$stat['uid']!==posix_geteuid()||$stat['gid']!==posix_getegid()||$stat['nlink']!==1)self::fail();
        $bytes=@file_get_contents($path);try{$actual=is_string($bytes)?json_decode($bytes,true,16,JSON_THROW_ON_ERROR):null;}catch(\Throwable){self::fail();}if(!is_array($actual)||$actual!==self::expected($config,$marker))self::fail();
    }
    private static function expected(RuntimeConfiguration $config,array $marker):array{return['formatVersion'=>self::FORMAT,'databaseId'=>$marker['databaseId']??null,'schemaVersion'=>$marker['schemaVersion']??null,'buildId'=>$config->value('FMONITOR_RUNTIME_BUILD_ID'),'database'=>$config->value('FMONITOR_DB_NAME'),'tablePrefix'=>$config->value('FMONITOR_PROCESS_TABLE_PREFIX')];}
    private static function fail():never{throw new \RuntimeException('STARTUP_NOT_READY');}
}
