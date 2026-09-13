<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class NativeStandProcess implements StandProcess
{
    public function run(array $argv, string $stdin='', array $env=[]): string
    {
        $pipes=[];
        $environment=getenv();
        if(!is_array($environment))throw new \RuntimeException();
        $process=proc_open($argv,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,array_replace($environment,$env));
        if(!is_resource($process))throw new \RuntimeException();
        fwrite($pipes[0],$stdin);fclose($pipes[0]);
        $stdout=stream_get_contents($pipes[1]);fclose($pipes[1]);
        stream_get_contents($pipes[2]);fclose($pipes[2]);
        if(proc_close($process)!==0)throw new \RuntimeException();
        return $stdout;
    }
}
