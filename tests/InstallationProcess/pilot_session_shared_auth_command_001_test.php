<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\PilotHttp\PilotCommandSession;
use FMonitor2\PilotHttp\PilotSessionPayloadCodec;
use FMonitor2\Tests\Support\FixedPilotSessionClock;
use FMonitor2\Tests\Support\LengthQueuedPilotSessionEntropy;
use FMonitor2\Tests\Support\NativePilotSessionFilesystem;
use FMonitor2\Tests\Support\RecordingPilotSessionObserver;

spl_autoload_register(static function(string $class):void {
    $prefix='FMonitor\\IdentityAccess\\';
    if(!str_starts_with($class,$prefix))return;
    $path=dirname(__DIR__,2).'/app/IdentityAccess/'.substr($class,strlen($prefix)).'.php';
    if(is_file($path))require $path;
});
require dirname(__DIR__) . '/Support/PilotSessionStoragePublicApiFixture.php';

$parent=sys_get_temp_dir().'/fmonitor2-session-storage-tests';
$root=$parent.'/task-shared-auth-command-'.bin2hex(random_bytes(12));
$remove=static function(string $path)use(&$remove):void {
    if(is_file($path)||is_link($path)){unlink($path);return;}
    if(!is_dir($path))return;
    foreach(scandir($path)?:[]as$entry)if($entry!=='.'&&$entry!=='..')$remove($path.'/'.$entry);
    rmdir($path);
};
if(!is_dir($parent)&&!mkdir($parent,0700))throw new RuntimeException('SETUP_FAILURE: parent mkdir');
if(!mkdir($root,0700))throw new RuntimeException('SETUP_FAILURE: root mkdir');

try {
    $owner=(new FMonitor\IdentityAccess\PilotSessionStorageFactory())->create(
        new FMonitor\IdentityAccess\PilotSessionStorageConfig($root,'shared_auth_command'),
        new NativePilotSessionFilesystem(),
        new FixedPilotSessionClock(1_788_200_000,1_000),
        new LengthQueuedPilotSessionEntropy([32=>[str_repeat("\x11",32),str_repeat("\x12",32),str_repeat("\x13",32)],16=>[str_repeat("\x21",16),str_repeat("\x22",16),str_repeat("\x23",16),str_repeat("\x24",16),str_repeat("\x25",16),str_repeat("\x26",16)]]),
        new RecordingPilotSessionObserver(),
    );
    $created=$owner->start(null);$id=(string)$created->currentSessionId();
    $auth=['auth_user_id'=>17,'auth_email'=>'session.actor@shlz.ru','auth_signed_in_at'=>1_788_199_900,'auth_csrf'=>str_repeat('a',64)];
    $codec=new PilotSessionPayloadCodec();
    assertSameValue('OK',$owner->writeCommit($id,(string)$codec->encode($auth))->status()->name,'authenticated payload seeded');

    $command=new PilotCommandSession($owner);
    assertSameValue(true,$command->open($id,'fm2auth',false,17,true),'command state opens on authenticated cookie');
    $state=$command->state();
    foreach($auth as$key=>$value)assertSameValue($value,$state[$key]??null,'shared command state preserves auth field '.$key);
    assertSameValue(17,$state['actor']??null,'command actor added alongside auth identity');

    $reopened=$owner->start($id);$persisted=$codec->decode((string)$reopened->sessionPayload());
    foreach($auth as$key=>$value)assertSameValue($value,$persisted[$key]??null,'next request retains auth field '.$key);

    $other=$owner->start(null);$otherId=(string)$other->currentSessionId();
    $otherAuth=$auth;$otherAuth['auth_user_id']=18;$otherAuth['auth_email']='other.actor@shlz.ru';
    assertSameValue('OK',$owner->writeCommit($otherId,(string)$codec->encode($otherAuth))->status()->name,'different authenticated payload seeded');
    $mismatch=new PilotCommandSession($owner);
    assertSameValue(true,$mismatch->open($otherId,'fm2auth',false,17,true),'mismatched actor receives isolated command session');
    $isolated=$mismatch->state();
    foreach(['auth_user_id','auth_email','auth_signed_in_at','auth_csrf']as$key)assertSameValue(false,array_key_exists($key,$isolated),'actor mismatch drops foreign auth field '.$key);
    assertSameValue(17,$isolated['actor']??null,'actor mismatch initializes only requested command actor');
    assertSameValue(true,isset($mismatch->headers()['Set-Cookie']),'actor mismatch publishes regenerated cookie');
    assertSameValue('OK',$owner->close()->status()->name,'shared owner closes');
    echo "PASS: shared auth and command session payload\n";
} finally {
    $remove($root);
    if(is_dir($parent)&&count(scandir($parent)?:[])===2)rmdir($parent);
}
