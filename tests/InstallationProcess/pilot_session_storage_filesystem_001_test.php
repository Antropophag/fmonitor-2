<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use FMonitor2\Tests\Support\{FixedPilotSessionClock,FixedPilotSessionEntropy,NativePilotSessionFilesystem,RecordingPilotSessionObserver};

spl_autoload_register(static function(string $class):void{$p='FMonitor\\IdentityAccess\\';if(str_starts_with($class,$p)){$f=dirname(__DIR__,2).'/app/IdentityAccess/'.substr($class,strlen($p)).'.php';if(is_file($f))require$f;}});

// PILOT-SESSION-STORAGE-001 v5 §8. This test invokes the exact public factory;
// its test adapter only performs primitives. Parent-side lstat/digests, not a
// dispatcher response, decide whether the owner actually published material.
$required=['PilotSessionStorageConfig','PilotSessionStorageFactory','PilotSessionStorage','PilotSessionClock','PilotSessionEntropy','PilotSessionEntropyResult','PilotSessionFilesystemPrimitives','PilotSessionPrimitiveResult','PilotSessionFileHandle','PilotSessionFileStat','PilotSessionLifecycleObserver'];
foreach($required as$n)assertSameValue(true,class_exists("FMonitor\\IdentityAccess\\$n")||interface_exists("FMonitor\\IdentityAccess\\$n"),"INTENTIONAL_RED: approved public API $n exists");
require dirname(__DIR__).'/Support/PilotSessionStoragePublicApiFixture.php';

$parent=sys_get_temp_dir().'/fmonitor2-session-storage-tests';$root=$parent.'/task-'.bin2hex(random_bytes(12));
if(!is_dir($parent)&&!mkdir($parent,0700))throw new RuntimeException('SETUP_FAILURE: parent mkdir');
if(!mkdir($root,0700))throw new RuntimeException('SETUP_FAILURE: root mkdir');
$sentinel=$parent.'/foreign-'.bin2hex(random_bytes(8));file_put_contents($sentinel,"foreign\0");$sentinelHash=hash_file('sha256',$sentinel);
try{
    $observer=new RecordingPilotSessionObserver();
    $owner=(new FMonitor\IdentityAccess\PilotSessionStorageFactory())->create(
        new FMonitor\IdentityAccess\PilotSessionStorageConfig($root,'trace_v5'),new NativePilotSessionFilesystem(),
        new FixedPilotSessionClock(1_788_200_000,10_000),$entropy=new FixedPilotSessionEntropy([str_repeat("\x11",32),str_repeat("\x22",16)]),$observer);
    $start=$owner->start(null);assertSameValue('OK',$start->status()->name,'anonymous start via real owner');
    $id=$start->currentSessionId();assertSameValue(true,is_string($id),'owner generated current id');
    $write=$owner->writeCommit($id,"actor=17\0csrf=fixed");assertSameValue('OK',$write->status()->name,'real owner commit');
    $digests=[];$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
    foreach($it as$e)if($e->isFile()){$digests[]=hash_file('sha256',$e->getPathname());assertSameValue(0600,$e->getPerms()&07777,'owned file mode');}
    assertSameValue(true,in_array(hash('sha256',"actor=17\0csrf=fixed"),$digests,true),'parent observes exact committed bytes');
    assertSameValue([32,16],$entropy->requestedLengths,'ID then write-stage entropy lengths');assertSameValue(true,count($observer->events)>0,'events emitted by real owner');$ordinals=[];$seen=[];
    foreach(array_chunk($observer->events,2)as$i=>$pair){assertSameValue(2,count($pair),'every primitive has two events');[$before,$after]=$pair;assertSameValue($i*2+1,$before->sequence(),'exact before sequence');assertSameValue($i*2+2,$after->sequence(),'exact after sequence');assertSameValue(FMonitor\IdentityAccess\PilotSessionFilesystemPhase::BEFORE,$before->phase(),'before phase');assertSameValue(FMonitor\IdentityAccess\PilotSessionFilesystemPhase::AFTER,$after->phase(),'after phase');assertSameValue($before->operation(),$after->operation(),'paired operation');assertSameValue($before->artifact(),$after->artifact(),'paired artifact');assertSameValue($before->sessionIdSha256(),$after->sessionIdSha256(),'paired hash');assertSameValue(null,$before->outcome(),'before has no outcome');assertSameValue(true,$after->outcome() instanceof FMonitor\IdentityAccess\PilotSessionPrimitiveOutcome,'after has outcome');$key=$before->operation()->value.'|'.$before->artifact()->value;$ordinal=($ordinals[$key]??0)+1;$ordinals[$key]=$ordinal;assertSameValue($ordinal,$before->ordinal(),'per-tuple ordinal');assertSameValue($ordinal,$after->ordinal(),'paired ordinal');$hash=$before->sessionIdSha256();if(in_array($before->artifact(),[FMonitor\IdentityAccess\PilotSessionLogicalArtifact::ROOT,FMonitor\IdentityAccess\PilotSessionLogicalArtifact::SESSIONS,FMonitor\IdentityAccess\PilotSessionLogicalArtifact::INSTANCE],true))assertSameValue(null,$hash,'directory event has null hash');else assertSameValue(true,(bool)preg_match('/^[0-9a-f]{64}$/D',(string)$hash),'file event has opaque hash');$seen[$key]=true;assertSameValue(false,method_exists($before,'path'),'event has no path');assertSameValue(false,method_exists($before,'sessionId'),'event has no literal id');}
    foreach(['lstat|root','lstat|sessions','lstat|instance','open|lock','flock|lock','open|stage','write|stage','fflush|stage','fsyncFile|stage','fstat|stage','link|committed','unlink|stage','fsyncDirectory|instance']as$key)assertSameValue(true,$seen[$key]??false,"exact happy-path event $key");
    $owner->close();echo"PASS: PILOT-SESSION-STORAGE-001 v5 real-owner filesystem tracer\n";
}finally{
    $remove=function(string$p)use(&$remove):void{if(is_link($p)||is_file($p)){unlink($p);return;}if(!is_dir($p))return;foreach(scandir($p)?:[]as$e)if($e!=='.'&&$e!=='..')$remove($p.'/'.$e);rmdir($p);};$remove($root);
    assertSameValue($sentinelHash,hash_file('sha256',$sentinel),'foreign sentinel preserved');unlink($sentinel);if(is_dir($parent)&&count(scandir($parent)?:[])===2)rmdir($parent);
}
