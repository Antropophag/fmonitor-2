<?php
declare(strict_types=1);

namespace { require dirname(__DIR__).'/bootstrap.php'; }

namespace FMonitor2\PilotHttp {
final class Gate2RuntimePrimitiveShadows { public static array $calls=[]; }
function basename(string $path,string $suffix=''):string{Gate2RuntimePrimitiveShadows::$calls[]='basename';return 'shlz.css';}
function is_link(string $path):bool{Gate2RuntimePrimitiveShadows::$calls[]='is_link';return \is_link($path);}
function is_readable(string $path):bool{Gate2RuntimePrimitiveShadows::$calls[]='is_readable';return \is_readable($path);}
function getenv(?string $name=null,bool $localOnly=false):string|array|false{Gate2RuntimePrimitiveShadows::$calls[]='getenv';return 'SHADOW-ENVIRONMENT-v012';}
function random_bytes(int $length):string{Gate2RuntimePrimitiveShadows::$calls[]='random_bytes';return \str_repeat("\xA5",$length);}
function bin2hex(string $string):string{Gate2RuntimePrimitiveShadows::$calls[]='bin2hex';return 'SHADOW-CORRELATION-v012';}
}

namespace FMonitor2\PilotHttp\Gate2RuntimeShadowSpy {
use FMonitor2\PilotHttp\{CssDescriptor,CssDescriptorOpener,PhpStreamCloser};
final class Descriptor implements CssDescriptor { public int $closeCalls=0;public function __construct(private string $bytes){}public function readBytes():string{return $this->bytes;}public function close():void{++$this->closeCalls;} }
final class Opener implements CssDescriptorOpener { public int $calls=0;public ?Descriptor $descriptor=null;public function open(string $path):CssDescriptor{++$this->calls;return $this->descriptor=new Descriptor("SHADOW-BASENAME-BYTES\n");} }
final class Closer implements PhpStreamCloser { public int $calls=0;public function close(mixed $stream):void{++$this->calls;\fclose($stream);} }
}

namespace {
use FMonitor2\PilotHttp\{Gate2RuntimePrimitiveShadows,PhpCssDescriptorOpener,ProcessEnvironmentSource,RandomCorrelationIdSource,ShlzCssAsset};
use FMonitor2\PilotHttp\Gate2RuntimeShadowSpy\{Closer,Opener};

$css=(string)\getenv('FMONITOR_TEST_CSS_PATH');
$environmentExpected=(string)\getenv('FMONITOR_TEST_ENV_VALUE');
$wrongPath=\dirname($css).'/not-the-public-export.css';

$basenameOpener=new Opener();$basenameAsset=new ShlzCssAsset($wrongPath,$basenameOpener);$basename=['result'=>'accepted','bytes'=>null,'openCalls'=>null];
try{$basename['bytes']=$basenameAsset->readBytes();}catch(\Throwable $e){$basename['result']=\get_class($e);}$basename['openCalls']=$basenameOpener->calls;$basenameAsset->close();

$closer=new Closer();$filesystem=['result'=>'accepted','exactBytes'=>false,'closeCalls'=>null];
try{$descriptor=(new PhpCssDescriptorOpener($closer))->open($css);$filesystem['exactBytes']=$descriptor->readBytes()===\file_get_contents($css);$descriptor->close();}catch(\Throwable $e){$filesystem['result']=\get_class($e);}$filesystem['closeCalls']=$closer->calls;

$environment=(new ProcessEnvironmentSource())->read('FMONITOR_TEST_ENV_VALUE');
$correlation=(new RandomCorrelationIdSource())->nextId();

echo \json_encode([
    'basename'=>$basename,
    'filesystem'=>$filesystem,
    'environment'=>['expected'=>$environmentExpected,'actual'=>$environment],
    'correlation'=>['value'=>$correlation,'length'=>\strlen($correlation),'hex'=>\preg_match('/^[0-9a-f]{32}$/D',$correlation)===1],
    'shadowCalls'=>Gate2RuntimePrimitiveShadows::$calls,
],JSON_THROW_ON_ERROR),"\n";
}
