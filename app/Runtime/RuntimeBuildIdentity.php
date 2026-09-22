<?php
declare(strict_types=1);
namespace FMonitor2\Runtime;

final class RuntimeBuildIdentity
{
    public static function read(RuntimeConfiguration $config):string
    {
        $path=$config->value('FMONITOR_RUNTIME_BUILD_ID_FILE');
        if($path==='')return self::sourceIdentity();
        clearstatcache(true,$path);$stat=@lstat($path);
        if(!is_array($stat)||is_link($path)||!is_file($path)||$stat['nlink']!==1||$stat['size']!==65||($stat['mode']&0222)!==0)self::fail();
        $identity=@file_get_contents($path);
        if(!is_string($identity)||preg_match('/^[0-9a-f]{64}\n$/D',$identity)!==1)self::fail();
        return substr($identity,0,64);
    }
    private static function sourceIdentity():string
    {
        $root=dirname(__DIR__,2);$paths=[];
        foreach(['app','bin','config','public','deploy/runtime']as$relative){$directory=$root.'/'.$relative;if(!is_dir($directory)||is_link($directory))self::fail();$iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory,\FilesystemIterator::SKIP_DOTS));foreach($iterator as$item){$path=$item->getPathname();if($item->isLink()||!$item->isFile()||!is_readable($path))self::fail();$paths[]=substr($path,strlen($root)+1);}}
        foreach(['composer.json','composer.lock']as$relative){$path=$root.'/'.$relative;if(is_link($path)||!is_file($path)||!is_readable($path))self::fail();$paths[]=$relative;}
        sort($paths,SORT_STRING);$hash=hash_init('sha256');
        foreach($paths as$relative){$path=$root.'/'.$relative;$handle=@fopen($path,'rb');if($handle===false)self::fail();$before=fstat($handle);if(!is_array($before)){fclose($handle);self::fail();}hash_update($hash,strlen($relative).':'.$relative.':'.$before['size'].':');while(!feof($handle)){$bytes=fread($handle,65536);if($bytes===false){fclose($handle);self::fail();}hash_update($hash,$bytes);}$after=fstat($handle);fclose($handle);if(!is_array($after)||$before['ino']!==$after['ino']||$before['size']!==$after['size']||$before['mtime']!==$after['mtime'])self::fail();}
        return hash_final($hash);
    }
    private static function fail():never{throw new \RuntimeException('STARTUP_NOT_READY');}
}
