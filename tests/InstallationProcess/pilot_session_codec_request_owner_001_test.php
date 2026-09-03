<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__,2).'/app/autoload.php';require dirname(__DIR__,2).'/app/PilotHttp/PilotHttp.php';require dirname(__DIR__,2).'/app/PilotHttp/PilotSessionPayloadCodec.php';require dirname(__DIR__,2).'/app/PilotHttp/PilotSessionRequestOwner.php';

$codec=new FMonitor2\PilotHttp\PilotSessionPayloadCodec();
$csrf=str_repeat('a',64);$valid=['auth_csrf'=>$csrf,'tokens'=>[]];$canonical=serialize($valid);
assertSameValue($valid,$codec->decode($canonical),'valid canonical whole-array decode');
assertSameValue(null,$codec->decode($canonical."\n"),'INTENTIONAL_RED: trailing noncanonical bytes rejected');
assertSameValue(null,$codec->decode(serialize(['float'=>1.5])),'float rejected');
$deep='leaf';for($i=0;$i<17;$i++)$deep=['nested'=>$deep];assertSameValue(null,$codec->decode(serialize($deep)),'depth 17 rejected');
assertSameValue(null,$codec->decode(serialize(array_fill(0,4097,'x'))),'4097 total entries rejected');
assertSameValue($canonical,$codec->encode($valid),'checked encode returns exact canonical bytes');
assertSameValue(null,$codec->encode(['float'=>1.5]),'encode rejects float');
assertSameValue(null,$codec->encode($deep),'encode rejects depth 17');
assertSameValue(null,$codec->encode(array_fill(0,4097,'x')),'encode rejects 4097 entries');

$owner=static fn()=>new class implements FMonitor\IdentityAccess\PilotSessionStorage{public function start(?string$id):FMonitor\IdentityAccess\PilotSessionOperationResult{throw new LogicException();}public function writeCommit(string$id,string$data):FMonitor\IdentityAccess\PilotSessionOperationResult{throw new LogicException();}public function regenerate(string$old,string$data):FMonitor\IdentityAccess\PilotSessionOperationResult{throw new LogicException();}public function destroyCommit(string$id):FMonitor\IdentityAccess\PilotSessionOperationResult{throw new LogicException();}public function close():FMonitor\IdentityAccess\PilotSessionOperationResult{throw new LogicException();}};
$first=$owner();$second=$owner();assertSameValue($first,FMonitor2\PilotHttp\PilotSessionRequestOwner::bind($first),'first request owner bind exact identity');$failed=false;try{FMonitor2\PilotHttp\PilotSessionRequestOwner::bind($second);}catch(LogicException){$failed=true;}assertSameValue(true,$failed,'INTENTIONAL_RED: second distinct request owner bind fails closed');
echo"PASS: PILOT-SESSION-STORAGE-001 v10 codec and request owner hardening\n";
