<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
spl_autoload_register(static function(string$class):void{$a='FMonitor\\IdentityAccess\\';$b='FMonitor2\\Tests\\Support\\';if(str_starts_with($class,$a)){$f=dirname(__DIR__,2).'/app/IdentityAccess/'.substr($class,strlen($a)).'.php';if(is_file($f))require$f;}elseif(str_starts_with($class,$b)){$f=__DIR__.'/'.substr($class,strlen($b)).'.php';if(is_file($f))require$f;}});
require __DIR__.'/PilotSessionStoragePublicApiFixture.php';

use FMonitor2\Tests\Support\{FixedPilotSessionClock,FixedPilotSessionEntropy,NativePilotSessionFilesystem};
final class CrashBoundaryObserver implements FMonitor\IdentityAccess\PilotSessionLifecycleObserver{
 public function __construct(private string$marker,private FMonitor\IdentityAccess\PilotSessionFilesystemOperation$operation,private FMonitor\IdentityAccess\PilotSessionLogicalArtifact$artifact,private int$ordinal){}
 public function observe(FMonitor\IdentityAccess\PilotSessionFilesystemEvent$e):void{
  if($e->phase()===FMonitor\IdentityAccess\PilotSessionFilesystemPhase::AFTER&&$e->operation()===$this->operation&&$e->artifact()===$this->artifact&&$e->ordinal()===$this->ordinal&&$e->outcome()===FMonitor\IdentityAccess\PilotSessionPrimitiveOutcome::OK){file_put_contents($this->marker,"paused\n",LOCK_EX);while(true)usleep(10000);}
 }
}
[$root,$old,$marker,$operation,$artifact,$ordinal]=array_slice($argv,1,6);$owner=(new FMonitor\IdentityAccess\PilotSessionStorageFactory())->create(new FMonitor\IdentityAccess\PilotSessionStorageConfig($root,'crash_v7'),new NativePilotSessionFilesystem(),new FixedPilotSessionClock(1_788_200_000,50_000),new FixedPilotSessionEntropy([str_repeat("\x55",32),str_repeat("\x66",16),str_repeat("\x77",16),str_repeat("\x88",12)]),new CrashBoundaryObserver($marker,FMonitor\IdentityAccess\PilotSessionFilesystemOperation::from($operation),FMonitor\IdentityAccess\PilotSessionLogicalArtifact::from($artifact),(int)$ordinal));$owner->regenerate($old,'new-crash-bytes');exit(91);
