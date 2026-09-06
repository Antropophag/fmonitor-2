<?php
declare(strict_types=1);
// PILOT-HEALTHCHECK-SESSION-001: operational anonymous liveness, no process grants.
set_error_handler(static function():never { throw new RuntimeException('Health unavailable'); });
$lock=null;$headers=null;
try {
    umask(0077);
    $port=getenv('FMONITOR_DEMO_PORT');$port=$port===false?'8092':$port;
    if(!preg_match('/^[1-9][0-9]{3,4}$/D',$port)||(int)$port<1024||(int)$port>65535)throw new RuntimeException();
    $root=getenv('FMONITOR_SESSION_STATE_ROOT');$root=$root===false?'/home/fmonitor/.local/state/fmonitor2':$root;
    if($root===''||$root[0]!=='/'||str_contains($root,"\0"))throw new RuntimeException();
    $owned=static function(string $path,bool $directory,?int $mode):void {
        clearstatcache(true,$path);$s=lstat($path);
        if($s===false||$s['uid']!==posix_geteuid()||($s['mode']&0170000)!==($directory?0040000:0100000)
            ||($mode!==null&&($s['mode']&0777)!==$mode))throw new RuntimeException();
    };
    $owned($root,true,null);$dir=$root.'/healthcheck';$cookie=$dir.'/cookies.txt';$lockPath=$dir.'/probe.lock';
    if(file_exists($dir)||is_link($dir))$owned($dir,true,0700);
    foreach([$cookie,$lockPath] as $path)if(file_exists($path)||is_link($path))$owned($path,false,0600);
    if(!is_dir($dir)&&!mkdir($dir,0700))throw new RuntimeException();
    $owned($dir,true,0700);
    $lock=fopen($lockPath,'c+b');if($lock===false)throw new RuntimeException();
    $owned($lockPath,false,0600);
    if(fstat($lock)['ino']!==lstat($lockPath)['ino'])throw new RuntimeException();
    $until=hrtime(true)+1_000_000_000;
    while(!flock($lock,LOCK_EX|LOCK_NB)){if(hrtime(true)>=$until)throw new RuntimeException();usleep(10000);}
    if(!file_exists($cookie)){$created=fopen($cookie,'x+b');if($created===false)throw new RuntimeException();fclose($created);}
    $owned($cookie,false,0600);
    $headers=tempnam($dir,'headers-');if($headers===false)throw new RuntimeException();
    $origin='http://127.0.0.1:'.$port;
    foreach(['/pilot/objects','/pilot/installers'] as $path){
        $url=$origin.$path;$deadline=hrtime(true)+2_000_000_000;
        for($redirects=0;;$redirects++){
            $remaining=($deadline-hrtime(true))/1_000_000_000;if($remaining<=0)throw new RuntimeException();
            $command=['curl','-q','--silent','--noproxy','*','--proto','=http','--max-time',sprintf('%.3f',$remaining),
                '--cookie',$cookie,'--cookie-jar',$cookie,'--dump-header',$headers,'--output','/dev/null',
                '--write-out','%{http_code}',$url];
            $process=proc_open($command,[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['file','/dev/null','w']],$pipes);
            if(!is_resource($process))throw new RuntimeException();
            $status=stream_get_contents($pipes[1]);fclose($pipes[1]);$exit=proc_close($process);
            if($exit!==0)throw new RuntimeException();
            $owned($cookie,false,0600);
            if($status==='200')break;
            if(!in_array($status,['301','302','303','307','308'],true)||$redirects>=3)throw new RuntimeException();
            $raw=file_get_contents($headers);
            if(!is_string($raw)||preg_match_all('/^Location:\s*([^\r\n]+)\r?$/mi',$raw,$matches)!==1)throw new RuntimeException();
            $location=trim($matches[1][0]);
            if(str_starts_with($location,'/')&&!str_starts_with($location,'//'))$location=$origin.$location;
            $parts=parse_url($location);
            if(!is_array($parts)||($parts['scheme']??null)!=='http'||($parts['host']??null)!=='127.0.0.1'
                ||($parts['port']??null)!==(int)$port||isset($parts['user'])||isset($parts['pass'])
                ||preg_match('/[\x00-\x20\x7f\\\\]/',$location))throw new RuntimeException();
            $url=$location;
        }
    }
    $result=0;
} catch(Throwable) { $result=1; }
finally {
    if(is_string($headers)&&is_file($headers))unlink($headers);
    if(is_resource($lock)){flock($lock,LOCK_UN);fclose($lock);}
}
exit($result);
