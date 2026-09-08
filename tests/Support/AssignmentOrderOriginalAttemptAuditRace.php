<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;

final class OriginalAttemptAuditRace
{
    private array $workers=[];
    public function start(OriginalIntegrityDatabase $f,string $action,string $barrier):int
    {
        $file=$f->control.'/audit-race-config.json';
        file_put_contents($file,json_encode(['host'=>$f->host,'port'=>$f->port,'database'=>$f->database,'user'=>$f->user,'passwordFile'=>$f->passwordFile,'prefix'=>$f->prefix],JSON_THROW_ON_ERROR));chmod($file,0600);
        $process=proc_open([PHP_BINARY,__DIR__.'/assignment_order_original_audit_race_child.php',$file,$action,$barrier],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($process))throw new \TestFailure('Owned race child unavailable');
        stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);
        $this->workers[]=['process'=>$process,'pipes'=>$pipes,'buffer'=>'','error'=>'','exit'=>null];$id=array_key_last($this->workers);
        \assertSameValue("READY\n",$this->line($id),'child enters exact public persistence phase');return $id;
    }
    public function release(int $id):void
    {if(fwrite($this->workers[$id]['pipes'][0],"RELEASE\n")!==8)throw new \TestFailure('Race release incomplete');fflush($this->workers[$id]['pipes'][0]);}
    public function result(int $id):string
    {
        $line=$this->line($id);$w=&$this->workers[$id];$deadline=hrtime(true)+5_000_000_000;
        do{$this->pump($w,$deadline);$state=proc_get_status($w['process']);if(!$state['running']&&$state['exitcode']!==-1)$w['exit']=$state['exitcode'];}while($state['running']||!feof($w['pipes'][1])||!feof($w['pipes'][2]));
        foreach($w['pipes'] as $pipe)fclose($pipe);$w['pipes']=[];$exit=proc_close($w['process']);$w['process']=null;
        \assertSameValue([0,'',''],[$w['exit']??$exit,$w['error'],$w['buffer']],'fixed successful child channels, no trailing result');return $line;
    }
    private function line(int $id):string
    {
        $w=&$this->workers[$id];$deadline=hrtime(true)+5_000_000_000;
        while(($end=strpos($w['buffer'],"\n"))===false)$this->pump($w,$deadline);
        $line=substr($w['buffer'],0,$end+1);$w['buffer']=substr($w['buffer'],$end+1);return $line;
    }
    private function pump(array &$w,int $deadline):void
    {
        if(hrtime(true)>=$deadline)throw new \TestFailure('Race child exceeded five-second deadline');
        $read=[];foreach([1,2] as $fd)if(!feof($w['pipes'][$fd]))$read[]=$w['pipes'][$fd];$write=$except=[];
        if($read!==[]){if(stream_select($read,$write,$except,0,100_000)===false)throw new \TestFailure('Race select failed');foreach($read as $pipe){$bytes=stream_get_contents($pipe,1024);if($bytes===false)throw new \TestFailure('Race output failed');if($pipe===$w['pipes'][1])$w['buffer'].=$bytes;else $w['error'].=$bytes;}}
        if(strlen($w['buffer'])+strlen($w['error'])>2048)throw new \TestFailure('Race output exceeded bound');
    }
    public function close():void
    {
        foreach($this->workers as &$w){if(is_resource($w['process'])){if(proc_get_status($w['process'])['running'])proc_terminate($w['process'],9);foreach($w['pipes'] as $pipe)if(is_resource($pipe))fclose($pipe);proc_close($w['process']);$w['process']=null;}}
    }
}
