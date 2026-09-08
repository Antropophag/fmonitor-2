<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalOpenedSafeLog as Owner;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSafeLogAttributePolicy as Policy;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalFileSafeLog;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalProductionConfig;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalProductionConfigurationUnavailable;
use FMonitor2\AssignmentOrderOriginal\ProductionAssignmentOrderOriginalFactory;

// ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001 v0.2, approved spec482b5153.
// Stable behavioral controls only: actual fstat data flow/native closure is Gate5 source proof.
function aoosoCreate(string $path,string $bytes,int $mask):array
{
    $previous=umask($mask);
    try {
        $handle=fopen($path,'x+b');
        if(!is_resource($handle)) { throw new TestFailure('SETUP_FAILURE: exclusive fixture creation'); }
        try {
            assertSameValue(strlen($bytes),fwrite($handle,$bytes),'fixture bytes written');
            assertSameValue(true,fflush($handle),'fixture flushed');
            $stat=fstat($handle);assertSameValue(true,is_array($stat),'fixture identity established');
            return $stat;
        } finally { fclose($handle); }
    } finally { umask($previous); }
}
function aoosoState(string $path):array
{
    clearstatcache(true,$path);$s=lstat($path);
    assertSameValue(true,is_array($s),'known fixture exists');
    return [$s['dev'],$s['ino'],$s['uid'],$s['mode']&07777,$s['size'],$s['mtime'],$s['ctime'],hash_file('sha256',$path)];
}
function aoosoError(callable $call,string $label,string $class=RuntimeException::class,string $message='safe log unavailable'):void
{
    try { $call(); } catch(Throwable $error) {
        assertSameValue([$class,$message,0,null],[get_class($error),$error->getMessage(),$error->getCode(),$error->getPrevious()],$label.' fixed error');
        return;
    }
    throw new TestFailure($label.' accepted unexpectedly');
}

$parent=rtrim((string)realpath(sys_get_temp_dir()),DIRECTORY_SEPARATOR);
$root=$parent.'/aoou-safe-owner-'.bin2hex(random_bytes(8));$decoy=$root.'-decoy';
$directories=[];$files=[];$owner=null;$compat=null;$errors=[];
try {
    $mask=umask(0077);
    try {
        foreach([$root,$decoy] as $directory) {
            assertSameValue(true,mkdir($directory,0700),'owned fixture directory created');
            $directories[$directory]=lstat($directory);
        }
    } finally { umask($mask); }
    $valid=$root.'/valid.log';$invalid=$root.'/invalid.log';$compatFile=$root.'/compat.log';$keep=$decoy.'/keep.log';
    $files[$valid]=aoosoCreate($valid,"prior-line\n",0077);
    $files[$invalid]=aoosoCreate($invalid,"blocked\n",0027);
    $files[$compatFile]=aoosoCreate($compatFile,"prior-line\n",0077);
    $files[$keep]=aoosoCreate($keep,"external-decoy\n",0077);
    assertSameValue(0600,$files[$valid]['mode']&07777,'valid file created initially0600');
    assertSameValue(0640,$files[$invalid]['mode']&07777,'invalid file created initially0640');
    $invalidBefore=aoosoState($invalid);$decoyBefore=aoosoState($keep);

    // Autoload deliberately disabled: direct Runtime/FileStorage imports must load both owners.
    assertSameValue(true,class_exists(Owner::class,false) && class_exists(Policy::class,false),
        'RED_ASSERTION: shared safe-log owner and policy are missing from direct runtime imports');
    $positive=[0100600,1200,7,900,1200,7,900];
    assertSameValue(true,Policy::accepts(...$positive),'independent literal attribute positive');
    foreach([[0,0100640],[0,0040600],[1,1201],[2,8],[3,901],[4,1201],
        [0,0104600],[0,0102600],[0,0101600]] as [$position,$value]) {
        $case=$positive;$case[$position]=$value;
        assertSameValue(false,Policy::accepts(...$case),'one-axis attribute rejection '.$position.':'.$value);
    }
    assertSameValue(true,Policy::accepts(0100600,0,7,900,0,7,900),'literal root UID is not rejected');

    // A real positive precedes every invalid-owner/factory case; constant rejection cannot pass.
    $owner=Owner::open($valid,'00000000-0000-4000-8000-000000000001');
    assertSameValue(false,$owner->isClosed(),'successful owner is usable');
    aoosoError(static fn()=>serialize($owner),'serialization');
    aoosoError(static fn()=>$owner->__unserialize([]),'unserialization');
    try { $copy=clone $owner;throw new TestFailure('owner clone was permitted'); }
    catch(Error $expected) { /* ordinary PHP private-clone boundary, no introspection */ }
    assertSameValue(false,$owner->isClosed(),'rejected escape leaves live owner usable');
    $owner->record('owner_probe',['phase'=>'positive']);
    $first="prior-line\n".'{"correlationId":"11e594f48195","event":"owner_probe","safeFields":{"phase":"positive"},"sequence":2}'."\n";
    assertSameValue($first,file_get_contents($valid),'exact first append preserves every prior byte');
    $owner->useRequest('00000000-0000-4000-8000-000000000002');
    $owner->record('owner_probe',['phase'=>'second']);
    $complete=$first.'{"correlationId":"e79acd97ac88","event":"owner_probe","safeFields":{"phase":"second"},"sequence":3}'."\n";
    assertSameValue($complete,file_get_contents($valid),'exact second request/correlation/sequence');
    $owner->close();$owner->close();assertSameValue(true,$owner->isClosed(),'idempotent public closed state');
    aoosoError(static fn()=>$owner->record('owner_probe',['phase'=>'closed']),'closed append');
    aoosoError(static fn()=>$owner->useRequest('after-close'),'closed request mutation');
    assertSameValue($complete,file_get_contents($valid),'closed operations do not reopen or write');
    $validAfter=aoosoState($valid);
    assertSameValue([$files[$valid]['dev'],$files[$valid]['ino'],$files[$valid]['uid'],0600],
        array_slice($validAfter,0,4),'valid owner preserves identity UID and exact permissions');

    aoosoError(static fn()=>Owner::open($invalid),'stable invalid mode');
    assertSameValue($invalidBefore,aoosoState($invalid),'invalid acquisition changes no bytes/identity/permissions/timestamps');
    $missing=$root.'/missing.log';
    aoosoError(static fn()=>Owner::open($missing),'missing path');
    assertSameValue(false,file_exists($missing),'missing file not created');
    assertSameValue((string)realpath($valid),Owner::canonical($valid),'canonical compatibility query');

    $compat=new AssignmentOrderOriginalFileSafeLog($compatFile,'00000000-0000-4000-8000-000000000001');
    $compat->record('owner_probe',['phase'=>'positive']);
    assertSameValue($first,file_get_contents($compatFile),'existing string-constructor facade preserves exact format');
    $sentinel=new class extends mysqli {
        public int $calls=0;
        public function query(string $query,int $mode=MYSQLI_STORE_RESULT):mysqli_result|bool { ++$this->calls;throw new RuntimeException('DB sentinel'); }
        public function prepare(string $query):mysqli_stmt|false { ++$this->calls;throw new RuntimeException('DB sentinel'); }
    };
    $private=$root.'/absent-private-root';
    foreach([$invalid,$missing] as $path) {
        aoosoError(static fn()=>ProductionAssignmentOrderOriginalFactory::create($sentinel,
            new AssignmentOrderOriginalProductionConfig($private,'b_',$path)),'factory ordering',
            AssignmentOrderOriginalProductionConfigurationUnavailable::class,'AssignmentOrderOriginalProductionConfigurationUnavailable');
    }
    assertSameValue(0,$sentinel->calls,'invalid acquisition precedes observed DB methods');
    assertSameValue(false,file_exists($private),'invalid acquisition precedes private-root access');
    assertSameValue(false,file_exists($missing),'factory never creates missing log');
    assertSameValue($decoyBefore,aoosoState($keep),'external sibling decoy unchanged');
} catch(Throwable $error) { $errors[]=$error->getMessage(); }
finally {
    if($owner!==null) { try{$owner->close();}catch(Throwable $e){$errors[]='Owner cleanup: '.$e->getMessage();} }
    $compat=null;
    foreach($files as $path=>$identity) {
        try {
            clearstatcache(true,$path);$now=lstat($path);
            assertSameValue([$identity['dev'],$identity['ino'],0100000],[$now['dev'],$now['ino'],$now['mode']&0170000],'cleanup exact owned file identity');
            assertSameValue(true,unlink($path),'owned file removed');
        } catch(Throwable $e) { $errors[]='Fixture cleanup: '.$e->getMessage(); }
    }
    foreach(array_reverse($directories,true) as $path=>$identity) {
        try {
            clearstatcache(true,$path);$now=lstat($path);
            assertSameValue([$identity['dev'],$identity['ino'],0040000],[$now['dev'],$now['ino'],$now['mode']&0170000],'cleanup exact owned directory identity');
            assertSameValue(true,rmdir($path),'owned directory removed');
        } catch(Throwable $e) { $errors[]='Directory cleanup: '.$e->getMessage(); }
    }
}
if($errors!==[]) { fwrite(STDERR,implode("\n",$errors)."\n");exit(1); }
echo "ASSIGNMENT_ORDER_ORIGINAL_SAFE_LOG_OWNER_001_OK\n";
