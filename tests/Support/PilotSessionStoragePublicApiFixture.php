<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;

use FMonitor\IdentityAccess\{PilotSessionClock,PilotSessionEntropy,PilotSessionEntropyResult,PilotSessionFileHandle,PilotSessionFilesystemEvent,PilotSessionFilesystemPrimitives,PilotSessionFileStat,PilotSessionFileType,PilotSessionLifecycleObserver,PilotSessionPrimitiveResult};
use FMonitor\IdentityAccess\PilotSessionPrimitiveFailureCode;

final class FixedPilotSessionClock implements PilotSessionClock {
    public function __construct(private int $wall,private int $mono){}
    public function wallSeconds():int{return $this->wall;}
    public function monotonicNanoseconds():int{return $this->mono++;}
}
final class SequencePilotSessionClock implements PilotSessionClock {
    /** @param list<int> $wall @param list<int> $monotonic */ public function __construct(private array$wall,private array$monotonic){}
    public function wallSeconds():int{$v=array_shift($this->wall);return$v??0;}
    public function monotonicNanoseconds():int{$v=array_shift($this->monotonic);return$v??PHP_INT_MAX;}
}
final class FixedPilotSessionEntropy implements PilotSessionEntropy {
    /** @var list<int> */ public array $requestedLengths=[];
    /** @param list<string> $values */
    public function __construct(private array $values,private bool $fail=false){}
    public function bytes(int $length):PilotSessionEntropyResult{$this->requestedLengths[]=$length;if($this->fail||$this->values===[])return PilotSessionEntropyResult::failed();$v=array_shift($this->values);return strlen($v)===$length?PilotSessionEntropyResult::ok($v):PilotSessionEntropyResult::failed();}
}
final class LengthQueuedPilotSessionEntropy implements PilotSessionEntropy {
    /** @param array<int,list<string>> $values */ public function __construct(private array $values){}
    public function bytes(int $length):PilotSessionEntropyResult{$queue=$this->values[$length]??[];if($queue===[])return PilotSessionEntropyResult::failed();$value=array_shift($queue);$this->values[$length]=$queue;return PilotSessionEntropyResult::ok($value);}
}
final class RecordingPilotSessionObserver implements PilotSessionLifecycleObserver {
    /** @var list<PilotSessionFilesystemEvent> */ public array $events=[];
    public function observe(PilotSessionFilesystemEvent $event):void{$this->events[]=$event;}
}
/** Native material adapter used only around the approved real owner. */
final class NativePilotSessionFilesystem implements PilotSessionFilesystemPrimitives {
    /** @var array<int,resource> */ private array $handles=[];
    /** @var array<int,string> */ private array $paths=[];
    /** @var array<string,int> */ private array $ordinals=[];
    public function __construct(private ?string $faultOperation=null,private ?string $faultArtifact=null,private ?int $faultOrdinal=null,private string $faultOutcome='exception',private ?string $swapArtifact=null,private ?int $swapOrdinal=null,private ?string $swapTarget=null,private bool$duplicateList=false,private ?string$wrongUidArtifact=null){}
    public function lstat(string $path):PilotSessionPrimitiveResult{$artifact=$this->artifact($path);$f=$this->fault('lstat',$artifact);if($f)return$f;$s=@lstat($path);if($s===false)return PilotSessionPrimitiveResult::nativeFalse();if($artifact===$this->wrongUidArtifact)$s['uid']=(int)$s['uid']+1;$key="swap|$artifact";$ordinal=($this->ordinals[$key]??0)+1;$this->ordinals[$key]=$ordinal;if($artifact===$this->swapArtifact&&$ordinal===$this->swapOrdinal&&$this->swapTarget!==null){@rename($path,$path.'.pre-swap');@symlink($this->swapTarget,$path);}return PilotSessionPrimitiveResult::ok($this->stat($s));}
    public function fstat(PilotSessionFileHandle $h):PilotSessionPrimitiveResult{$f=$this->fault('fstat',$this->handleArtifact($h));if($f)return$f;$s=@fstat($this->resource($h));return$s===false?PilotSessionPrimitiveResult::nativeFalse():PilotSessionPrimitiveResult::ok($this->stat($s));}
    public function mkdir(string $p,int $m):PilotSessionPrimitiveResult{$f=$this->fault('mkdir',$this->artifact($p));if($f)return$f;return@mkdir($p,$m)?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function open(string $p,string $m,int $permissions):PilotSessionPrimitiveResult{$fault=$this->fault('open',$this->artifact($p));if($fault!==null)return$fault;$r=@fopen($p,$m);if(!is_resource($r))return PilotSessionPrimitiveResult::nativeFalse();@chmod($p,$permissions);$h=PilotSessionFileHandle::mint();$id=spl_object_id($h);$this->handles[$id]=$r;$this->paths[$id]=$p;return PilotSessionPrimitiveResult::ok($h);}
    public function read(PilotSessionFileHandle $h,int $n):PilotSessionPrimitiveResult{$f=$this->fault('read',$this->handleArtifact($h));if($f)return$f;$v=@fread($this->resource($h),$n);return$v===false?PilotSessionPrimitiveResult::nativeFalse():PilotSessionPrimitiveResult::ok($v);}
    public function write(PilotSessionFileHandle $h,string $b):PilotSessionPrimitiveResult{$fault=$this->fault('write',$this->handleArtifact($h));if($fault!==null)return$fault;$v=@fwrite($this->resource($h),$b);return$v===false?PilotSessionPrimitiveResult::nativeFalse():($v===strlen($b)?PilotSessionPrimitiveResult::ok($v):PilotSessionPrimitiveResult::shortIo($v));}
    public function fflush(PilotSessionFileHandle $h):PilotSessionPrimitiveResult{$f=$this->fault('fflush',$this->handleArtifact($h));if($f)return$f;return@fflush($this->resource($h))?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function fsyncFile(PilotSessionFileHandle $h):PilotSessionPrimitiveResult{$fault=$this->fault('fsyncFile',$this->handleArtifact($h));if($fault!==null)return$fault;return@fsync($this->resource($h))?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function fsyncDirectory(string $p):PilotSessionPrimitiveResult{$f=$this->fault('fsyncDirectory',$this->artifact($p));return$f??PilotSessionPrimitiveResult::ok(null);}
    public function link(string $a,string $b):PilotSessionPrimitiveResult{$f=$this->fault('link',$this->artifact($b));if($f)return$f;return@link($a,$b)?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function rename(string $a,string $b):PilotSessionPrimitiveResult{$f=$this->fault('rename',$this->artifact($b));if($f)return$f;return@rename($a,$b)?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function unlink(string $p):PilotSessionPrimitiveResult{$f=$this->fault('unlink',$this->artifact($p));if($f)return$f;return@unlink($p)?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function flock(PilotSessionFileHandle $h,int $op):PilotSessionPrimitiveResult{$f=$this->fault('flock',$this->handleArtifact($h));if($f)return$f;return@flock($this->resource($h),$op)?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function close(PilotSessionFileHandle $h):PilotSessionPrimitiveResult{$f=$this->fault('close',$this->handleArtifact($h));if($f)return$f;$id=spl_object_id($h);$r=$this->resource($h);unset($this->handles[$id],$this->paths[$id]);return@fclose($r)?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function list(string $d):PilotSessionPrimitiveResult{$f=$this->fault('list',$this->artifact($d));if($f)return$f;$v=@scandir($d);if($v===false)return PilotSessionPrimitiveResult::nativeFalse();$v=array_values(array_diff($v,['.','..']));if($this->duplicateList&&$v!==[])$v[]=$v[0];return PilotSessionPrimitiveResult::ok($v);}
    public function mtime(string $p):PilotSessionPrimitiveResult{$f=$this->fault('mtime',$this->artifact($p));if($f)return$f;$v=@filemtime($p);return$v===false?PilotSessionPrimitiveResult::nativeFalse():PilotSessionPrimitiveResult::ok($v);}
    /** @return resource */ private function resource(PilotSessionFileHandle $h){$r=$this->handles[spl_object_id($h)]??null;if(!is_resource($r))throw new \RuntimeException('fixture received unknown handle');return$r;}
    private function handleArtifact(PilotSessionFileHandle $h):string{return$this->artifact($this->paths[spl_object_id($h)]??'');}
    private function artifact(string$p):string{$b=basename($p);if(str_starts_with($b,'.stage-'))return'stage';if(str_starts_with($b,'.revoked-'))return'revoked';if(str_starts_with($b,'s-'))return'committed';if(str_starts_with($b,'l-'))return'lock';if($b==='sessions')return'sessions';if(basename(dirname($p))==='sessions')return'instance';return'root';}
    private function fault(string$operation,string$artifact):?PilotSessionPrimitiveResult{$key="$operation|$artifact";$ordinal=($this->ordinals[$key]??0)+1;$this->ordinals[$key]=$ordinal;if($operation!==$this->faultOperation||$artifact!==$this->faultArtifact||$ordinal!==$this->faultOrdinal)return null;return match($this->faultOutcome){'short-write'=>PilotSessionPrimitiveResult::shortIo(1),'short-read'=>PilotSessionPrimitiveResult::shortIo('x'),'warning'=>PilotSessionPrimitiveResult::warning(PilotSessionPrimitiveFailureCode::IO_ERROR),'exception'=>PilotSessionPrimitiveResult::exception(PilotSessionPrimitiveFailureCode::IO_ERROR),default=>PilotSessionPrimitiveResult::nativeFalse()};}
    /** @param array<int|string,int> $s */ private function stat(array $s):PilotSessionFileStat{$m=$s['mode'];$t=($m&0170000)===0040000?PilotSessionFileType::DIRECTORY:(($m&0170000)===0100000?PilotSessionFileType::REGULAR:(($m&0170000)===0120000?PilotSessionFileType::SYMLINK:PilotSessionFileType::OTHER));return new PilotSessionFileStat($t,$s['uid'],$m&07777,$s['dev'],$s['ino'],$s['nlink'],$s['size'],$s['mtime']);}
}
