<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceDependencies;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceVerificationFactory;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPrivateStorageFactory;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageObserver;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageEvent;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalFaultInjector;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalFaultPoint;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalOrphanKind;
use FMonitor2\AssignmentOrderOriginal\ReconcileAssignmentOrderOriginalPrivateOrphansCommand;
use FMonitor2\Tests\Support\InMemoryAssignmentOrderOriginalMaintenanceEnvironment;

class_exists(AssignmentOrderOriginalPrivateStorageFactory::class);
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalMaintenanceApplication.php';
require dirname(__DIR__).'/Support/InMemoryAssignmentOrderOriginalMaintenanceEnvironment.php';

function aoomRemove(string$root):void{if(!str_starts_with($root,dirname(__DIR__,2).'/.verification-artifacts/aoom-'))throw new TestFailure('unsafe root');if(!is_dir($root))return;foreach(scandir($root)?:[]as$item){if($item==='.'||$item==='..')continue;$path=$root.'/'.$item;if(is_dir($path)&&!is_link($path))aoomRemove($path);else unlink($path);}rmdir($root);}
$root=dirname(__DIR__,2).'/.verification-artifacts/aoom-'.bin2hex(random_bytes(8));
$observer=new class implements AssignmentOrderOriginalStorageObserver{public array$events=[];public function observe(AssignmentOrderOriginalStorageEvent$event,?string$id):void{$this->events[]=$event->value.':'.($id??'');}};
$faults=new class implements AssignmentOrderOriginalFaultInjector{public function before(AssignmentOrderOriginalFaultPoint$point):void{}};

try{
    $storage=AssignmentOrderOriginalPrivateStorageFactory::create($root,$observer,$faults);
    $oldStage=$storage->beginStage();assertSameValue(AssignmentOrderOriginalStorageStatus::OK,$oldStage->write('abandoned'),'Real abandoned stage accepts bytes.');$oldStage->close();
    $youngStage=$storage->beginStage();$youngStage->write('young');$youngStage->close();
    $finalize=static function(string$bytes)use($storage):array{$hash=hash('sha256',$bytes);$stage=$storage->beginStage();$stage->write($bytes);$out=$stage->finalize($hash,strlen($bytes));$stage->close();return[$out,$out->lease()?->content()?->opaqueIdentity()];};
    [$outcome,$identity]=$finalize('%PDF-private-unreferenced');assertSameValue(AssignmentOrderOriginalStorageStatus::OK,$outcome->status(),'Real finalized content is created.');$outcome->lease()?->release();
    [$referencedOutcome,$referencedIdentity]=$finalize('%PDF-private-referenced');$referencedOutcome->lease()?->release();
    [$lockedOutcome,$lockedIdentity]=$finalize('%PDF-private-locked');$lockedLease=$lockedOutcome->lease();assertSameValue(true,$lockedLease!==null,'Fixture retains the real digest lease.');
    $stageFiles=glob($root.'/stages/*');sort($stageFiles,SORT_STRING);assertSameValue(2,count($stageFiles),'Fixture owns two abandoned stage files.');$oldStageIdentity=basename($stageFiles[0]);$youngStageIdentity=basename($stageFiles[1]);touch($stageFiles[0],strtotime('2026-09-02T06:00:00Z'));touch($stageFiles[1],strtotime('2026-09-02T09:00:00Z'));touch($root.'/'.$identity,strtotime('2026-09-02T06:30:00Z'));touch($root.'/'.$referencedIdentity,strtotime('2026-09-02T07:00:00Z'));touch($root.'/'.$lockedIdentity,strtotime('2026-09-02T07:30:00Z'));

    $first=$storage->listOrphans('2026-09-02T08:15:30Z',1,null);assertSameValue(AssignmentOrderOriginalStorageStatus::OK,$first->status(),'Real orphan page succeeds.');assertSameValue(1,count($first->candidates()),'Batch limit bounds first page.');assertSameValue(true,$first->nextCursor()!==null,'Non-terminal page supplies opaque cursor.');
    $second=$storage->listOrphans('2026-09-02T08:15:30Z',3,$first->nextCursor());assertSameValue(3,count($second->candidates()),'Cursor selects exactly the remaining ordered old candidates.');assertSameValue(null,$second->nextCursor(),'Terminal page has null cursor.');$all=array_merge($first->candidates(),$second->candidates());assertSameValue([$oldStageIdentity,$identity,$referencedIdentity,$lockedIdentity],array_map(static fn($c)=>$c->opaqueIdentity,$all),'Pagination preserves exact timestamp/identity continuation order.');
    $inventory=json_decode($storage->inventoryCanonicalJson(),true,512,JSON_THROW_ON_ERROR);assertSameValue('aoou-blobs-v1',$inventory['schema']??null,'Inventory uses approved schema.');assertSameValue([2,3],[count($inventory['stages']??[]),count($inventory['finalized']??[])],'Inventory observes stages and finalized blobs.');

    $environment=new InMemoryAssignmentOrderOriginalMaintenanceEnvironment();$environment->references[$identity]=false;$environment->references[$referencedIdentity]=true;$environment->references[$lockedIdentity]=false;$base=$environment->dependencies();$deps=new AssignmentOrderOriginalMaintenanceDependencies($base->authorizer,$base->clock,$storage,$base->references,$base->requests,$observer,$faults,$base->safeLog);$app=AssignmentOrderOriginalMaintenanceVerificationFactory::create($deps);$result=$app->reconcileAssignmentOrderOriginalPrivateOrphans(new ReconcileAssignmentOrderOriginalPrivateOrphansCommand('00000000-0000-4000-8000-000000000191','system:original-orphan-reconciler','2026-09-02T08:15:30Z',10,null));assertSameValue([AssignmentOrderOriginalMaintenanceStatus::PARTIAL,4,2,2],[$result->status(),$result->scanned(),$result->deleted(),$result->retained()],'Maintenance deletes abandoned/unreferenced and retains referenced/actively leased content.');assertSameValue(true,in_array('reference:'.$referencedIdentity,$environment->calls,true),'Reference is rechecked under its acquired lock.');assertSameValue(false,in_array('reference:'.$lockedIdentity,$environment->calls,true),'Actively leased content is retained before reference lookup.');$calls=count($environment->calls);$replay=$app->reconcileAssignmentOrderOriginalPrivateOrphans(new ReconcileAssignmentOrderOriginalPrivateOrphansCommand('00000000-0000-4000-8000-000000000191','system:original-orphan-reconciler','2026-09-02T08:15:30Z',10,null));assertSameValue(AssignmentOrderOriginalMaintenanceStatus::REPLAYED,$replay->status(),'Maintenance request replay performs no second mutation.');assertSameValue([],array_filter(array_slice($environment->calls,$calls),static fn($call)=>str_starts_with($call,'reference:')),'Replay performs no reference recheck.');$after=json_decode($storage->inventoryCanonicalJson(),true,512,JSON_THROW_ON_ERROR);assertSameValue([$youngStageIdentity],array_column($after['stages'],'opaqueIdentity'),'Young stage remains after maintenance.');$lockedLease?->release();

    $collisionBytes='collision';[$valid,$collisionIdentity]=$finalize($collisionBytes);$valid->lease()?->release();[$reuse]=$finalize($collisionBytes);assertSameValue(AssignmentOrderOriginalStorageStatus::ALREADY_PRESENT_VERIFIED,$reuse->status(),'Exact existing bytes/size/hash produce verified reuse.');$reuse->lease()?->release();file_put_contents($root.'/'.$collisionIdentity,'corrupt!!',LOCK_EX);$collision=$storage->beginStage();$collision->write($collisionBytes);$bad=$collision->finalize(hash('sha256',$collisionBytes),strlen($collisionBytes));$collision->close();assertSameValue([AssignmentOrderOriginalStorageStatus::FAILED,null],[$bad->status(),$bad->lease()],'Same-size corrupt digest collision fails without lease or reuse.');
}finally{aoomRemove($root);}
fwrite(STDOUT,"ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PRIVATE_MAINTENANCE_OK\n");
