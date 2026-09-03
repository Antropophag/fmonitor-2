<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';$root=dirname(__DIR__,2);require $root.'/app/autoload.php';foreach(['PilotHttp','PilotView','PilotShellView','ObjectListView','ConstructionControlView','ObjectCardView','ChecklistView','PrepareFormView','InstallerDirectoryView','UserDirectoryView','PilotE2ECoordinator','ProductionPilotHttpEntrypointFactory']as$file)require_once"$root/app/PilotHttp/$file.php";require dirname(__DIR__).'/Support/PilotSessionStoragePublicApiFixture.php';

$codec=new FMonitor2\PilotHttp\PilotSessionPayloadCodec();
$csrf=str_repeat('a',64);$valid=['auth_csrf'=>$csrf,'tokens'=>[]];$canonical=serialize($valid);
assertSameValue($valid,$codec->decode($canonical),'valid canonical whole-array decode');
assertSameValue(null,$codec->decode($canonical."\n"),'INTENTIONAL_RED: trailing noncanonical bytes rejected');
assertSameValue(null,$codec->decode(serialize(['float'=>1.5])),'float rejected');
$depth16='leaf';for($i=0;$i<16;$i++)$depth16=['nested'=>$depth16];assertSameValue($depth16,$codec->decode(serialize($depth16)),'depth 16 accepted');$deep=['nested'=>$depth16];assertSameValue(null,$codec->decode(serialize($deep)),'depth 17 rejected');
assertSameValue(array_fill(0,4096,'x'),$codec->decode(serialize(array_fill(0,4096,'x'))),'4096 total entries accepted');
assertSameValue(null,$codec->decode(serialize(array_fill(0,4097,'x'))),'4097 total entries rejected');
$nested4096=[];for($i=0;$i<64;$i++)$nested4096[]=array_fill(0,63,'x');assertSameValue($nested4096,$codec->decode(serialize($nested4096)),'4096 recursively reachable entries accepted');$nestedOverflow=$nested4096;$nestedOverflow[0][]='overflow';assertSameValue(null,$codec->decode(serialize($nestedOverflow)),'nested total entry overflow rejected');
$mixed=['null'=>null,'bool'=>true,'int'=>17,'string'=>'value','nested'=>['key'=>false,3=>'three']];assertSameValue(serialize($mixed),$codec->encode($mixed),'all allowed encode leaves are canonical');assertSameValue($canonical,$codec->encode($valid),'checked encode returns exact canonical bytes');
assertSameValue(null,$codec->encode(['float'=>1.5]),'encode rejects float');
assertSameValue(null,$codec->encode($deep),'encode rejects depth 17');
assertSameValue(null,$codec->encode(array_fill(0,4097,'x')),'encode rejects 4097 entries');
assertSameValue(null,$codec->encode(['object'=>new stdClass()]),'encode rejects object');$referenced='value';$reference=['reference'=>&$referenced];assertSameValue(null,$codec->encode($reference),'encode rejects reference');$cycle=[];$cycle['self']=&$cycle;assertSameValue(null,$codec->encode($cycle),'encode rejects reference cycle');assertSameValue(null,$codec->encode($nestedOverflow),'encode rejects nested total overflow');

$environment=new class implements FMonitor2\PilotHttp\EnvironmentSource{public function read(string$name):string|false{return false;}};$filesystemA=new FMonitor2\Tests\Support\NativePilotSessionFilesystem();$filesystemB=new FMonitor2\Tests\Support\NativePilotSessionFilesystem();$clockA=new FMonitor2\Tests\Support\FixedPilotSessionClock(1,1);$clockB=new FMonitor2\Tests\Support\FixedPilotSessionClock(2,2);$entropyA=new FMonitor2\Tests\Support\LengthQueuedPilotSessionEntropy([]);$entropyB=new FMonitor2\Tests\Support\LengthQueuedPilotSessionEntropy([]);$observerA=new FMonitor2\Tests\Support\RecordingPilotSessionObserver();$observerB=new FMonitor2\Tests\Support\RecordingPilotSessionObserver();$first=FMonitor2\PilotHttp\ProductionPilotHttpEntrypointFactory::createWithSessionStorageDependencies($environment,$filesystemA,$clockA,$entropyA,$observerA);assertSameValue(true,$first instanceof FMonitor2\PilotHttp\PilotHttpEntrypoint,'first public injected factory returns graph');$failed=false;try{FMonitor2\PilotHttp\ProductionPilotHttpEntrypointFactory::createWithSessionStorageDependencies($environment,$filesystemB,$clockB,$entropyB,$observerB);}catch(LogicException){$failed=true;}assertSameValue(true,$failed,'INTENTIONAL_RED: conflicting second public injected factory fails closed');
echo"PASS: PILOT-SESSION-STORAGE-001 v10 codec and request owner hardening\n";
