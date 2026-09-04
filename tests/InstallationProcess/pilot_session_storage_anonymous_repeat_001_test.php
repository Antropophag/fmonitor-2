<?php
declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';

use FMonitor2\Tests\Support\{FixedPilotSessionClock,LengthQueuedPilotSessionEntropy,NativePilotSessionFilesystem,RecordingPilotSessionObserver};

spl_autoload_register(static function(string$class):void{$prefix='FMonitor\\IdentityAccess\\';if(str_starts_with($class,$prefix)){$path=dirname(__DIR__,2).'/app/IdentityAccess/'.substr($class,strlen($prefix)).'.php';if(is_file($path))require$path;}});
foreach(['PilotSessionStorageConfig','PilotSessionStorageFactory','PilotSessionStorage']as$name)assertSameValue(true,class_exists('FMonitor\\IdentityAccess\\'.$name)||interface_exists('FMonitor\\IdentityAccess\\'.$name),'Approved public session owner API '.$name);
require dirname(__DIR__).'/Support/PilotSessionStoragePublicApiFixture.php';

$token=bin2hex(random_bytes(8));$root=dirname(__DIR__,2).'/.test-artifacts/session-anonymous-repeat-'.$token;
if(!mkdir($root,0700,true)||realpath($root)!==$root)throw new TestFailure('SETUP_FAILURE: owned session root');
$remove=function(string$path)use(&$remove):void{if(is_link($path)||is_file($path)){unlink($path);return;}if(!is_dir($path))return;foreach(scandir($path)?:[]as$entry)if($entry!=='.'&&$entry!=='..')$remove($path.'/'.$entry);rmdir($path);};
$factory=new FMonitor\IdentityAccess\PilotSessionStorageFactory();
$owner=static function(string$instance,array$entropy)use($factory,$root){return$factory->create(new FMonitor\IdentityAccess\PilotSessionStorageConfig($root,$instance),new NativePilotSessionFilesystem(),new FixedPilotSessionClock(1_788_200_000,1_000),new LengthQueuedPilotSessionEntropy($entropy),new RecordingPilotSessionObserver());};
try{
    $id1=str_repeat('11',32);$session=$owner('anonymous_repeat',[32=>[str_repeat("\x11",32),str_repeat("\x22",32)],16=>[str_repeat("\x31",16),str_repeat("\x32",16)],12=>array_fill(0,8,str_repeat("\x41",12))]);
    $start=$session->start(null);assertSameValue(['OK',$id1],[$start->status()->name,$start->currentSessionId()],'Anonymous start returns deterministic ID1.');
    $first=$session->writeCommit($id1,'payload-one');assertSameValue(['OK',$id1],[$first->status()->name,$first->currentSessionId()],'First anonymous commit retains ID1.');
    $second=$session->writeCommit($id1,'payload-two-latest');$secondTuple=[$second->status()->name,$second->currentSessionId()];$committed=glob($root.'/sessions/anonymous_repeat/s-*.session')?:[];$session->close();

    $foreignId=str_repeat('aa',32);$ownerId=str_repeat('bb',32);$collision=$owner('external_collision',[32=>[str_repeat("\xaa",32),str_repeat("\xbb",32)],16=>[str_repeat("\xcc",16)],12=>array_fill(0,4,str_repeat("\xdd",12))]);
    $collisionStart=$collision->start(null);assertSameValue($foreignId,$collisionStart->currentSessionId(),'Anonymous start may select candidate ID1 before any file exists.');
    $collisionDir=$root.'/sessions/external_collision';$foreign=$collisionDir.'/s-'.$foreignId.'.session';file_put_contents($foreign,'externally-owned-bytes');chmod($foreign,0600);$foreignHash=hash_file('sha256',$foreign);
    $collisionWrite=$collision->writeCommit($foreignId,'owner-payload-two');assertSameValue(['OK',$ownerId],[$collisionWrite->status()->name,$collisionWrite->currentSessionId()],'Write collision selects deterministic next ID2.');
    assertSameValue($foreignHash,hash_file('sha256',$foreign),'Preexisting externally-owned ID1 bytes remain immutable.');assertSameValue('owner-payload-two',file_get_contents($collisionDir.'/s-'.$ownerId.'.session'),'Only owner ID2 contains the submitted payload.');
    $collisionFiles=glob($collisionDir.'/s-*.session')?:[];sort($collisionFiles,SORT_STRING);assertSameValue([$foreign,$collisionDir.'/s-'.$ownerId.'.session'],$collisionFiles,'Collision leaves exactly external ID1 and owner ID2 files.');$collision->close();
    assertSameValue(['OK',$id1],$secondTuple,'Repeated anonymous commit retains the caller current ID1.');
    assertSameValue(1,count($committed),'Repeated anonymous commit leaves exactly one committed file.');assertSameValue($root.'/sessions/anonymous_repeat/s-'.$id1.'.session',$committed[0],'Only ID1 remains addressable.');assertSameValue('payload-two-latest',file_get_contents($committed[0]),'Repeated commit publishes latest bytes under ID1.');
    echo "PASS: PILOT-SESSION-STORAGE-001 anonymous repeated commit\n";
}finally{$remove($root);assertSameValue(false,file_exists($root),'Owned session root removed');}
