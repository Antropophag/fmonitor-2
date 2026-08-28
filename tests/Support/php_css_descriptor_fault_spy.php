<?php
declare(strict_types=1);
namespace { require dirname(__DIR__).'/bootstrap.php'; }
namespace FMonitor2\PilotHttp {
final class Gate2FilesystemShadows { public static array $calls=[]; }
function lstat(mixed ...$args):never{Gate2FilesystemShadows::$calls[]='lstat';throw new \RuntimeException('SHADOW');}
function fopen(mixed ...$args):never{Gate2FilesystemShadows::$calls[]='fopen';throw new \RuntimeException('SHADOW');}
function fstat(mixed ...$args):never{Gate2FilesystemShadows::$calls[]='fstat';throw new \RuntimeException('SHADOW');}
function fread(mixed ...$args):never{Gate2FilesystemShadows::$calls[]='fread';throw new \RuntimeException('SHADOW');}
function stream_get_contents(mixed ...$args):never{Gate2FilesystemShadows::$calls[]='stream_get_contents';throw new \RuntimeException('SHADOW');}
function feof(mixed ...$args):never{Gate2FilesystemShadows::$calls[]='feof';throw new \RuntimeException('SHADOW');}
function is_resource(mixed ...$args):never{Gate2FilesystemShadows::$calls[]='is_resource';throw new \RuntimeException('SHADOW');}
function fclose(mixed ...$args):never{Gate2FilesystemShadows::$calls[]='fclose';throw new \RuntimeException('SHADOW');}
function set_error_handler(mixed ...$args):never{Gate2FilesystemShadows::$calls[]='set_error_handler';throw new \RuntimeException('SHADOW');}
function restore_error_handler(mixed ...$args):never{Gate2FilesystemShadows::$calls[]='restore_error_handler';throw new \RuntimeException('SHADOW');}
}
namespace FMonitor2\PilotHttp\Gate2CloserSpy {
use FMonitor2\PilotHttp\{CssDescriptorCloseFailed,PhpStreamCloser,PhpStreamClosePrimitive};
final class PrimitiveSecretFailure extends \RuntimeException { public string $primitiveData='PRIMITIVE-DATA-SECRET-v012'; }
final class Closer implements PhpStreamCloser { public int $calls=0;public function __construct(private string $mode){}public function close(mixed $stream):void{++$this->calls;\fclose($stream);if($this->mode==='normal')return;if($this->mode==='false'){$result=false;if($result===false)throw new CssDescriptorCloseFailed();}if($this->mode==='warning'){\set_error_handler(static fn():never=>throw new \ErrorException('CLOSER-WARNING-SECRET'));try{\trigger_error('CLOSER-WARNING-SECRET',E_USER_WARNING);}catch(\Throwable){throw new CssDescriptorCloseFailed();}finally{\restore_error_handler();}}if($this->mode==='throw')throw new \RuntimeException('CLOSER-THROW-SECRET');}}
final class Primitive implements PhpStreamClosePrimitive { public int $calls=0;public function __construct(private string $mode){}public function close(mixed $stream):bool{++$this->calls;$closed=\fclose($stream);return match($this->mode){'true'=>$closed,'false'=>false,'warn'=>(static function()use($closed):bool{\trigger_error('PRIMITIVE-WARNING-SECRET-v012',E_USER_WARNING);return $closed;})(),'throw'=>throw new PrimitiveSecretFailure('PRIMITIVE-THROW-SECRET-v012',7312),default=>throw new \LogicException('mode')};}}
}
namespace {
use FMonitor2\PilotHttp\{CssDescriptorCloseFailed,Gate2FilesystemShadows,NativePhpFclosePrimitive,NativePhpStreamCloser,PhpCssDescriptor,PhpCssDescriptorOpener};
use FMonitor2\PilotHttp\Gate2CloserSpy\{Closer,Primitive};
$path=(string)getenv('FMONITOR_TEST_CSS_PATH');$results=[];
foreach(['normal','false','warning','throw'] as $mode){$resource=\fopen($path,'rb');if($resource===false)throw new \RuntimeException('fixture open');$closer=new Closer($mode);$descriptor=new PhpCssDescriptor($resource,$closer);$failure=null;try{$descriptor->close();}catch(\Throwable $e){$failure=$e instanceof CssDescriptorCloseFailed?'typed':get_class($e);}$descriptor->close();$results[$mode]=['failure'=>$failure,'closerCalls'=>$closer->calls,'resourceRelinquished'=>!\is_resource($resource)];}
$policies=[];foreach(['true','false','warn','throw'] as $mode){$resource=\fopen($path,'rb');if($resource===false)throw new \RuntimeException('policy fixture open');$primitive=new Primitive($mode);$closer=new NativePhpStreamCloser($primitive);$outerHandler=static fn():bool=>false;\set_error_handler($outerHandler);$failure=null;try{$closer->close($resource);}catch(\Throwable $e){$failure=['class'=>get_class($e),'message'=>$e->getMessage(),'code'=>$e->getCode(),'previousNull'=>$e->getPrevious()===null,'publicData'=>get_object_vars($e)];}$temporary=static fn():bool=>false;$previous=\set_error_handler($temporary);$handlerRestored=$previous===$outerHandler;\restore_error_handler();\restore_error_handler();$policies[$mode]=['failure'=>$failure,'primitiveCalls'=>$primitive->calls,'resourceClosed'=>!\is_resource($resource),'handlerRestored'=>$handlerRestored];}
$baseline=\count(\get_resources('stream'));$resource=\fopen($path,'rb');if($resource===false)throw new \RuntimeException('native fixture open');$nativePrimitive=new NativePhpFclosePrimitive();$nativeResult=$nativePrimitive->close($resource);$native=['result'=>$nativeResult,'resourceClosed'=>!\is_resource($resource),'streamCountRestored'=>\count(\get_resources('stream'))===$baseline,'shadowCalls'=>Gate2FilesystemShadows::$calls];
echo \json_encode(['injected'=>$results,'policies'=>$policies,'nativePrimitive'=>$native],JSON_THROW_ON_ERROR),"\n";
}
