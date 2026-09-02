<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use FMonitor2\Tests\Support\{FixedPilotSessionClock,FixedPilotSessionEntropy,NativePilotSessionFilesystem,RecordingPilotSessionObserver};

spl_autoload_register(static function(string$class):void{$p='FMonitor\\IdentityAccess\\';if(str_starts_with($class,$p)){$f=dirname(__DIR__,2).'/app/IdentityAccess/'.substr($class,strlen($p)).'.php';if(is_file($f))require$f;}});
$factoryClass='FMonitor\\IdentityAccess\\PilotSessionStorageFactory';
assertSameValue(true,class_exists($factoryClass),'INTENTIONAL_RED: v7 real-owner factory exists');
require dirname(__DIR__).'/Support/PilotSessionStoragePublicApiFixture.php';

// Exact constructibility/immutability checks are independent of implementation.
$create=(new ReflectionClass($factoryClass))->getMethod('create');
assertSameValue(5,$create->getNumberOfRequiredParameters(),'factory has five required dependencies');
assertSameValue(5,$create->getNumberOfParameters(),'factory has no optional dependency');
foreach(['PilotSessionEntropyResult','PilotSessionPrimitiveResult','PilotSessionFileHandle','PilotSessionOperationResult','PilotSessionFilesystemEvent']as$n){$r=new ReflectionClass("FMonitor\\IdentityAccess\\$n");assertSameValue(true,$r->isReadOnly(),"$n immutable");}
$entropyResult=FMonitor\IdentityAccess\PilotSessionEntropyResult::ok("\x00\x01");assertSameValue('OK',$entropyResult->status()->name,'entropy OK named factory');assertSameValue("\x00\x01",$entropyResult->bytes(),'entropy preserves opaque bytes');
$primitive=FMonitor\IdentityAccess\PilotSessionPrimitiveResult::shortIo(1);assertSameValue('short_io',$primitive->outcome()->value,'short IO typed');assertSameValue(1,$primitive->value(),'partial value retained');assertSameValue(null,$primitive->failureCode(),'short IO has no diagnostic');
$exception=FMonitor\IdentityAccess\PilotSessionPrimitiveResult::exception(FMonitor\IdentityAccess\PilotSessionPrimitiveFailureCode::IO_ERROR);assertSameValue('io_error',$exception->failureCode()?->value,'closed exception code');

$parent=sys_get_temp_dir().'/fmonitor2-session-storage-tests';if(!is_dir($parent)&&!mkdir($parent,0700))throw new RuntimeException('SETUP_FAILURE: parent mkdir');
$roots=[];$remove=function(string$p)use(&$remove):void{if(is_link($p)||is_file($p)){unlink($p);return;}if(!is_dir($p))return;foreach(scandir($p)?:[]as$e)if($e!=='.'&&$e!=='..')$remove($p.'/'.$e);rmdir($p);};
try{
 foreach([
   ['lstat','root',1,'false','ROOT_INVALID'],
   ['mkdir','sessions',1,'false','ROOT_INVALID'],
   ['list','instance',1,'false','GC_FAILED'],
   ['open','lock',1,'false','WRITE_FAILED'],
   ['flock','lock',1,'false','WRITE_FAILED'],
   ['open','stage',1,'false','WRITE_FAILED'],
   ['write','stage',1,'short-write','WRITE_FAILED'],
   ['fflush','stage',1,'false','WRITE_FAILED'],
   ['fsyncFile','stage',1,'false','FSYNC_FAILED'],
   ['fsyncFile','stage',1,'warning','FSYNC_FAILED'],
   ['fsyncFile','stage',1,'exception','FSYNC_FAILED'],
   ['fstat','stage',1,'false','WRITE_FAILED'],
   ['link','committed',1,'false','PUBLISH_FAILED'],
   ['unlink','stage',1,'false','PUBLISH_FAILED'],
   ['fsyncDirectory','instance',1,'false','PUBLISH_FAILED'],
   ['close','stage',1,'false','CLOSE_FAILED'],
 ]as[$operation,$artifact,$ordinal,$outcome,$category]){
   $root=$parent.'/task-'.bin2hex(random_bytes(12));$roots[]=$root;if(!mkdir($root,0700))throw new RuntimeException('SETUP_FAILURE: task mkdir');
   $observer=new RecordingPilotSessionObserver();$owner=(new $factoryClass())->create(
      new FMonitor\IdentityAccess\PilotSessionStorageConfig($root,'fault_v5'),
      new NativePilotSessionFilesystem($operation,$artifact,$ordinal,$outcome),
      new FixedPilotSessionClock(1_788_200_000,20_000),
      new FixedPilotSessionEntropy([str_repeat("\x41",32),str_repeat("\x42",16),str_repeat("\x43",12)]),$observer);
   $start=$owner->start(null);
   if(in_array($operation,['lstat','mkdir','list'],true)){assertSameValue('UNAVAILABLE',$start->status()->name,"$operation owner maps setup primitive fault");assertSameValue($category,$start->category()?->name,"$operation exact setup category");$owner->close();continue;}
   assertSameValue('OK',$start->status()->name,"$operation predecessor start");
   $result=$owner->writeCommit((string)$start->currentSessionId(),'independent-fault-material');
   assertSameValue('UNAVAILABLE',$result->status()->name,"$operation owner maps primitive fault");
   assertSameValue($category,$result->category()?->name,"$operation exact safe category");
   assertSameValue(true,(bool)preg_match('/^[0-9a-f]{12}$/D',(string)$result->correlationId()),"$operation opaque correlation");
   assertSameValue(null,$result->currentSessionId(),"$operation failure has no current id");
   $committed=[];$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));foreach($it as$e)if($e->isFile()&&str_starts_with($e->getFilename(),'s-'))$committed[]=$e->getPathname();
   assertSameValue([],$committed,"$operation parent observes no committed success material");
   assertSameValue(true,count($observer->events)>=2,"$operation before/after trace emitted by owner");
   foreach($observer->events as$event){assertSameValue(false,method_exists($event,'path'),"$operation trace hides path");assertSameValue(false,method_exists($event,'sessionId'),"$operation trace hides id");}
   $owner->close();
 }
 $root=$parent.'/task-'.bin2hex(random_bytes(12));$roots[]=$root;if(!mkdir($root,0700))throw new RuntimeException('SETUP_FAILURE: entropy task mkdir');
 $owner=(new $factoryClass())->create(new FMonitor\IdentityAccess\PilotSessionStorageConfig($root,'entropy_v5'),new NativePilotSessionFilesystem(),new FixedPilotSessionClock(1_788_200_000,30_000),new FixedPilotSessionEntropy([],true),new RecordingPilotSessionObserver());
 $failed=$owner->start(null);assertSameValue('UNAVAILABLE',$failed->status()->name,'owner maps entropy failure');assertSameValue('ENTROPY_FAILED',$failed->category()?->name,'exact entropy category');$owner->close();
 echo"PASS: PILOT-SESSION-STORAGE-001 v7 DTO/fault tracers\n";
}finally{foreach($roots as$r)$remove($r);if(is_dir($parent)&&count(scandir($parent)?:[])===2)rmdir($parent);}
