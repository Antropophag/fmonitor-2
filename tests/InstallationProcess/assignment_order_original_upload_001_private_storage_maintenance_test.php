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
    $bytes='%PDF-private-finalized';$hash=hash('sha256',$bytes);$stage=$storage->beginStage();$stage->write($bytes);$outcome=$stage->finalize($hash,strlen($bytes));$stage->close();assertSameValue(AssignmentOrderOriginalStorageStatus::OK,$outcome->status(),'Real finalized content is created.');$identity=$outcome->lease()?->content()?->opaqueIdentity();assertSameValue(true,is_string($identity), 'Finalized content has opaque identity.');$outcome->lease()?->release();
    $stageFiles=glob($root.'/stages/*');sort($stageFiles,SORT_STRING);assertSameValue(2,count($stageFiles),'Fixture owns two abandoned stage files.');touch($stageFiles[0],strtotime('2026-09-02T06:00:00Z'));touch($stageFiles[1],strtotime('2026-09-02T09:00:00Z'));touch($root.'/'.$identity,strtotime('2026-09-02T06:30:00Z'));

    $first=$storage->listOrphans('2026-09-02T08:15:30Z',1,null);assertSameValue(AssignmentOrderOriginalStorageStatus::OK,$first->status(),'Real orphan page succeeds.');assertSameValue(1,count($first->candidates()),'Batch limit bounds first page.');assertSameValue(true,$first->nextCursor()!==null,'Non-terminal page supplies opaque cursor.');
    $second=$storage->listOrphans('2026-09-02T08:15:30Z',2,$first->nextCursor());assertSameValue(1,count($second->candidates()),'Cursor selects the remaining old candidate only.');assertSameValue(null,$second->nextCursor(),'Terminal page has null cursor.');$all=array_merge($first->candidates(),$second->candidates());assertSameValue([AssignmentOrderOriginalOrphanKind::ABANDONED_STAGE,AssignmentOrderOriginalOrphanKind::FINALIZED_CONTENT],array_values(array_unique(array_map(static fn($c)=>$c->kind,$all),SORT_REGULAR)),'Inventory includes old abandoned stage and finalized content.');
    $inventory=json_decode($storage->inventoryCanonicalJson(),true,512,JSON_THROW_ON_ERROR);assertSameValue('aoou-blobs-v1',$inventory['schema']??null,'Inventory uses approved schema.');assertSameValue([2,1],[count($inventory['stages']??[]),count($inventory['finalized']??[])],'Inventory observes stages and finalized blob.');

    $environment=new InMemoryAssignmentOrderOriginalMaintenanceEnvironment();$environment->references[$identity]=false;$base=$environment->dependencies();$deps=new AssignmentOrderOriginalMaintenanceDependencies($base->authorizer,$base->clock,$storage,$base->references,$base->requests,$observer,$faults,$base->safeLog);$app=AssignmentOrderOriginalMaintenanceVerificationFactory::create($deps);$result=$app->reconcileAssignmentOrderOriginalPrivateOrphans(new ReconcileAssignmentOrderOriginalPrivateOrphansCommand('00000000-0000-4000-8000-000000000191','system:original-orphan-reconciler','2026-09-02T08:15:30Z',10,null));assertSameValue([AssignmentOrderOriginalMaintenanceStatus::COMPLETED,2,2],[$result->status(),$result->scanned(),$result->deleted()],'Maintenance deletes old abandoned and rechecked-unreferenced finalized content.');$replay=$app->reconcileAssignmentOrderOriginalPrivateOrphans(new ReconcileAssignmentOrderOriginalPrivateOrphansCommand('00000000-0000-4000-8000-000000000191','system:original-orphan-reconciler','2026-09-02T08:15:30Z',10,null));assertSameValue(AssignmentOrderOriginalMaintenanceStatus::REPLAYED,$replay->status(),'Maintenance request replay performs no second mutation.');

    $collisionBytes='collision';$collisionHash=hash('sha256',$collisionBytes);$collisionIdentity='sha256/'.substr($collisionHash,0,2).'/'.substr($collisionHash,2,2).'/'.$collisionHash;$collisionPath=$root.'/'.$collisionIdentity;if(!is_dir(dirname($collisionPath)))mkdir(dirname($collisionPath),0700,true);file_put_contents($collisionPath,'corrupt',LOCK_EX);$collision=$storage->beginStage();$collision->write($collisionBytes);$bad=$collision->finalize($collisionHash,strlen($collisionBytes));$collision->close();assertSameValue([AssignmentOrderOriginalStorageStatus::FAILED,null],[$bad->status(),$bad->lease()],'Existing digest path is reused only after exact bytes/size/hash verification.');
}finally{aoomRemove($root);}
fwrite(STDOUT,"ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PRIVATE_MAINTENANCE_OK\n");
