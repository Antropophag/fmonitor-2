<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Validate every configured resource before opening password contents. */
final class AssignmentOrderOriginalEvidenceConfiguration
{
    public static function validate(AssignmentOrderOriginalEvidenceReaderConfig $c):void
    {
        if(preg_match('/^[A-Za-z0-9.\x3a\x5b\x5d_-]{1,255}$/D',$c->databaseHost)!==1
            ||$c->databasePort<1||$c->databasePort>65535
            ||preg_match('/^[A-Za-z0-9_]{1,64}$/D',$c->databaseName)!==1
            ||preg_match('/^[A-Za-z0-9_.-]{1,32}$/D',$c->databaseUser)!==1
            ||preg_match('/^[A-Za-z0-9_]{0,25}$/D',$c->tablePrefix)!==1) throw new AssignmentOrderOriginalEvidenceUnavailable();
        self::path($c->databasePasswordFile,false);
        self::path($c->privateStorageRoot,true);
        self::path($c->safeLogFile,false);
    }
    private static function path(string $path,bool $directory):array
    {
        $repository=dirname(__DIR__,2);
        if($path===''||$path[0]!=='/'||preg_match('/[\x00-\x1f\x7f]/',$path)!==0
            ||in_array('..',explode('/',$path),true)||@realpath($path)!==$path
            ||$path===$repository||str_starts_with($path,$repository.'/')) throw new AssignmentOrderOriginalEvidenceUnavailable();
        clearstatcache(true,$path);$s=@lstat($path);
        $uid=function_exists('posix_geteuid')?posix_geteuid():getmyuid();
        if($s===false||($s['mode']&0170000)!==($directory?0040000:0100000)
            ||!in_array($s['uid'],[0,$uid],true)
            ||!in_array($s['mode']&07777,$directory?[0700,0750]:[0600],true)) throw new AssignmentOrderOriginalEvidenceUnavailable();
        return $s;
    }
    public static function password(string $path):string
    {
        $before=self::path($path,false);$handle=@fopen($path,'rb');
        if($handle===false) throw new AssignmentOrderOriginalEvidenceUnavailable();
        $failed=false;$bytes='';
        try {
            $opened=@fstat($handle);
            foreach(['dev','ino','mode','uid'] as $key) if($opened===false||$opened[$key]!==$before[$key]) throw new AssignmentOrderOriginalEvidenceUnavailable();
            $value=@stream_get_contents($handle,1026);
            $after=self::path($path,false);
            foreach(['dev','ino','mode','uid'] as $key) if($after[$key]!==$opened[$key]) throw new AssignmentOrderOriginalEvidenceUnavailable();
            if(!is_string($value)) throw new AssignmentOrderOriginalEvidenceUnavailable();
            $bytes=str_ends_with($value,"\n")?substr($value,0,-1):$value;
            if(preg_match('/^[\x20-\x7e]{1,1024}$/D',$bytes)!==1) throw new AssignmentOrderOriginalEvidenceUnavailable();
        } catch(\Throwable) {$failed=true;}
        try {if(!@fclose($handle))$failed=true;} catch(\Throwable) {$failed=true;}
        if($failed) throw new AssignmentOrderOriginalEvidenceUnavailable();
        return $bytes;
    }
}
