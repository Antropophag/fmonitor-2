<?php
declare(strict_types=1);
namespace FMonitor\IdentityAccess;
require_once __DIR__.'/PilotSessionStorageTypes.php';

final class NativePilotSessionFilesystem implements PilotSessionFilesystemPrimitives
{
    private array$handles=[];
    public function lstat(string$p):PilotSessionPrimitiveResult{$s=@lstat($p);return$s===false?PilotSessionPrimitiveResult::nativeFalse():PilotSessionPrimitiveResult::ok($this->stat($s));}
    public function fstat(PilotSessionFileHandle$h):PilotSessionPrimitiveResult{$s=@fstat($this->resource($h));return$s===false?PilotSessionPrimitiveResult::nativeFalse():PilotSessionPrimitiveResult::ok($this->stat($s));}
    public function mkdir(string$p,int$m):PilotSessionPrimitiveResult{return@mkdir($p,$m)?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function open(string$p,string$m,int$permissions):PilotSessionPrimitiveResult{$r=@fopen($p,$m);if(!is_resource($r))return PilotSessionPrimitiveResult::nativeFalse();@chmod($p,$permissions);$h=PilotSessionFileHandle::mint();$this->handles[spl_object_id($h)]=$r;return PilotSessionPrimitiveResult::ok($h);}
    public function read(PilotSessionFileHandle$h,int$n):PilotSessionPrimitiveResult{$v=@fread($this->resource($h),$n);return$v===false?PilotSessionPrimitiveResult::nativeFalse():PilotSessionPrimitiveResult::ok($v);}
    public function write(PilotSessionFileHandle$h,string$b):PilotSessionPrimitiveResult{$v=@fwrite($this->resource($h),$b);return$v===false?PilotSessionPrimitiveResult::nativeFalse():($v===strlen($b)?PilotSessionPrimitiveResult::ok($v):PilotSessionPrimitiveResult::shortIo($v));}
    public function fflush(PilotSessionFileHandle$h):PilotSessionPrimitiveResult{return@fflush($this->resource($h))?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function fsyncFile(PilotSessionFileHandle$h):PilotSessionPrimitiveResult{return@fsync($this->resource($h))?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function fsyncDirectory(string$p):PilotSessionPrimitiveResult{$r=@fopen($p,'rb');if(!is_resource($r))return PilotSessionPrimitiveResult::nativeFalse();$ok=@fsync($r);fclose($r);return$ok?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function link(string$a,string$b):PilotSessionPrimitiveResult{return@link($a,$b)?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function rename(string$a,string$b):PilotSessionPrimitiveResult{return@rename($a,$b)?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function unlink(string$p):PilotSessionPrimitiveResult{return@unlink($p)?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function flock(PilotSessionFileHandle$h,int$o):PilotSessionPrimitiveResult{return@flock($this->resource($h),$o)?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function close(PilotSessionFileHandle$h):PilotSessionPrimitiveResult{$id=spl_object_id($h);$r=$this->resource($h);unset($this->handles[$id]);return@fclose($r)?PilotSessionPrimitiveResult::ok(null):PilotSessionPrimitiveResult::nativeFalse();}
    public function list(string$d):PilotSessionPrimitiveResult{$v=@scandir($d);return$v===false?PilotSessionPrimitiveResult::nativeFalse():PilotSessionPrimitiveResult::ok(array_values(array_diff($v,['.','..'])));}
    public function mtime(string$p):PilotSessionPrimitiveResult{$v=@filemtime($p);return$v===false?PilotSessionPrimitiveResult::nativeFalse():PilotSessionPrimitiveResult::ok($v);}
    private function resource(PilotSessionFileHandle$h){$r=$this->handles[spl_object_id($h)]??null;if(!is_resource($r))throw new \RuntimeException('unknown handle');return$r;}
    private function stat(array$s):PilotSessionFileStat{$m=$s['mode'];$t=($m&0170000)===0040000?PilotSessionFileType::DIRECTORY:(($m&0170000)===0100000?PilotSessionFileType::REGULAR:(($m&0170000)===0120000?PilotSessionFileType::SYMLINK:PilotSessionFileType::OTHER));return new PilotSessionFileStat($t,$s['uid'],$m&07777,$s['dev'],$s['ino'],$s['nlink'],$s['size'],$s['mtime']);}
}
