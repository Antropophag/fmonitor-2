<?php

declare(strict_types=1);
// FMONITOR_TEST_DB: MAINTENANCE-001 public real storage and factory seams.
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityCommits.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityDatabase.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalAttemptAuditDatabase.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;
use FMonitor2\Tests\Support\OriginalAttemptAuditDatabase as A;

function mntStorage($f):O\AssignmentOrderOriginalFileStorage{return new O\AssignmentOrderOriginalFileStorage($f->privateRoot,new O\AssignmentOrderOriginalFixedClock('2026-09-02T07:00:00Z'),new O\AssignmentOrderOriginalNoFaults());}
A::case('existing-storage-control',function($f){$s=mntStorage($f);$stage=$s->beginStage();assertSameValue(O\AssignmentOrderOriginalStorageStatus::OK,$stage->write('owned-stage'),'existing stage works');assertSameValue(O\AssignmentOrderOriginalStorageStatus::OK,$stage->abort(),'owned stage cleanup');$stage->close();});
A::case('private-storage-public-factory',function($f){assertSameValue(true,class_exists(O\AssignmentOrderOriginalPrivateStorageFactory::class),'promised public storage factory');$s=O\AssignmentOrderOriginalPrivateStorageFactory::create($f->privateRoot,new O\AssignmentOrderOriginalNoOpStorageObserver(),new O\AssignmentOrderOriginalNoFaults());assertSameValue(true,$s instanceof O\AssignmentOrderOriginalPrivateStorage,'public storage type');});
A::case('closed-stage-page-delete',function($f){
 $s=mntStorage($f);$stage=$s->beginStage();$stage->write('owned-stage');$stage->close();$page=$s->listOrphans('2026-09-02T07:30:00Z',10,null);
 assertSameValue('ok',$page->status()->value,'real page');$items=$page->candidates();assertSameValue(1,count($items),'one abandoned stage');$item=$items[0];assertSameValue(['stage-0001',11,null,'2026-09-02T07:00:00Z'],[$item->opaqueIdentity,$item->byteSize,$item->sha256,$item->createdOrFinalizedAtUtc],'literal stage snapshot');
 $lock=$s->acquireDigestLock($item->opaqueIdentity);try{assertSameValue('ok',$lock->status()->value,'owned lock');assertSameValue('ok',$s->deleteLocked($lock)->value,'delete abandoned stage');}finally{$lock->release();}
 assertSameValue([],$s->listOrphans('2026-09-02T07:30:00Z',10,null)->candidates(),'deleted stage absent');assertSameValue('failed',$s->deleteLocked($lock)->value,'released lock unusable');
});
A::case('active-stage-held-exclusion',function($f){
 $s=mntStorage($f);$stage=$s->beginStage();$stage->write('owned-stage');
 try{$lock=$s->acquireDigestLock('stage-0001');try{assertSameValue('locked',$lock->status()->value,'old but active upload stage cannot be deleted');}finally{$lock->release();}}finally{$stage->abort();$stage->close();}
});
A::case('finalized-content-held-exclusion',function($f){
 $s=mntStorage($f);$stage=$s->beginStage();$stage->write('owned-content');$out=$stage->finalize(hash('sha256','owned-content'),13);$lease=$out->lease();assertSameValue(true,$lease!==null,'real content lease control');$stage->close();$id='content-sha256-'.hash('sha256','owned-content');
 try{$lock=$s->acquireDigestLock($id);try{assertSameValue('locked',$lock->status()->value,'upload lease excludes maintenance');}finally{$lock->release();}}finally{$lease->release();}
 $s->listOrphans('2026-09-02T07:30:00Z',10,null);$lock=$s->acquireDigestLock($id);try{assertSameValue('ok',$lock->status()->value,'released lease allows maintenance');assertSameValue('ok',$s->deleteLocked($lock)->value,'unreferenced content removable');}finally{$lock->release();}
});
A::case('foreign-storage-lock-refused',function($f){$a=mntStorage($f);$b=mntStorage($f);$stage=$a->beginStage();$stage->write('owned-stage');$stage->close();$a->listOrphans('2026-09-02T07:30:00Z',10,null);$b->listOrphans('2026-09-02T07:30:00Z',10,null);$lock=$a->acquireDigestLock('stage-0001');try{assertSameValue('failed',$b->deleteLocked($lock)->value,'another instance cannot adopt lock');assertSameValue(1,count($a->listOrphans('2026-09-02T07:30:00Z',10,null)->candidates()),'no unauthorized file delete');}finally{$lock->release();}});
A::case('configured-non-test-principal',function($f){$app=O\AssignmentOrderOriginalRealMaintenanceVerificationFactory::create($f->db,new O\AssignmentOrderOriginalProductionConfig($f->privateRoot,$f->prefix,$f->safeLog),new O\AssignmentOrderOriginalMaintenanceAuthorization('ops-worker-02','assignment_order.original.storage.reconcile'),new O\AssignmentOrderOriginalFixedClock('2026-09-02T09:00:00Z'),new O\AssignmentOrderOriginalNoFaults());$r=$app->reconcileAssignmentOrderOriginalPrivateOrphans(new O\ReconcileAssignmentOrderOriginalPrivateOrphansCommand('00000000-0000-4000-8000-000000000801','ops-worker-02','2026-09-02T07:30:00Z',10,null));assertSameValue(['completed',null,0],[$r->status()->value,$r->reason()?->value,$r->scanned()],'trusted configured principal is not a fixture literal');});
foreach([['bad principal','assignment_order.original.storage.reconcile'],['ops-worker-02','wrong']] as $n=>[$principal,$cap])integrityCase('invalid-factory-authorization-'.$n,function()use($principal,$cap){$db=new S\OriginalIntegrityNoSql();$caught=null;try{O\AssignmentOrderOriginalRealMaintenanceVerificationFactory::create($db,new O\AssignmentOrderOriginalProductionConfig('/unused/root','p_','/unused/log'),new O\AssignmentOrderOriginalMaintenanceAuthorization($principal,$cap),new O\AssignmentOrderOriginalFixedClock('2026-09-02T09:00:00Z'),new O\AssignmentOrderOriginalNoFaults());}catch(Throwable $e){$caught=$e;}assertSameValue(true,$caught instanceof InvalidArgumentException,'config refused before root/DB');assertSameValue('Invalid maintenance authorization.',$caught->getMessage(),'fixed authorization exception');assertSameValue([],$db->calls,'no DB access');});
A::case('actual-storage-events',function($f){$observer=new class implements O\AssignmentOrderOriginalStorageObserver{public array $events=[];public function observe(O\AssignmentOrderOriginalStorageEvent $event,?string $opaqueIdentity):void{$this->events[]=[$event->value,$opaqueIdentity];}};$s=new O\AssignmentOrderOriginalFileStorage($f->privateRoot,new O\AssignmentOrderOriginalFixedClock('2026-09-02T07:00:00Z'),new O\AssignmentOrderOriginalNoFaults(),$observer);$stage=$s->beginStage();$stage->write('owned-stage');$stage->close();$s->listOrphans('2026-09-02T07:30:00Z',10,null);$lock=$s->acquireDigestLock('stage-0001');try{assertSameValue('ok',$s->deleteLocked($lock)->value,'delete control');}finally{$lock->release();}assertSameValue([['digest_lock_acquired','stage-0001'],['delete_begin','stage-0001'],['delete_done','stage-0001']],$observer->events,'actual events once in primitive order');});
A::case('newer-reused-stage-retained',function($f){$old=mntStorage($f);$stage=$old->beginStage();$stage->write('old');$stage->close();$old->listOrphans('2026-09-02T07:30:00Z',10,null);$other=new O\AssignmentOrderOriginalFileStorage($f->privateRoot,new O\AssignmentOrderOriginalFixedClock('2026-09-02T08:00:00Z'),new O\AssignmentOrderOriginalNoFaults());$other->listOrphans('2026-09-02T07:30:00Z',10,null);$lock=$other->acquireDigestLock('stage-0001');try{assertSameValue('ok',$other->deleteLocked($lock)->value,'old candidate removed by rival');}finally{$lock->release();}$fresh=$other->beginStage();$fresh->write('new');$fresh->close();$lock=$old->acquireDigestLock('stage-0001');try{assertSameValue('failed',$old->deleteLocked($lock)->value,'stale page cannot delete newer reused identity');}finally{$lock->release();}assertSameValue(1,count($other->listOrphans('2026-09-02T08:30:00Z',10,null)->candidates()),'new candidate retained');});
foreach (['digest_lock_acquired','delete_begin','delete_done'] as $fault) A::case('throwing-storage-observer-'.$fault,function($f)use($fault){
 $observer=new class($fault) implements O\AssignmentOrderOriginalStorageObserver {
  public array $events=[];public function __construct(private string $fault){}
  public function observe(O\AssignmentOrderOriginalStorageEvent $event,?string $opaqueIdentity):void {
   $this->events[]=[$event->value,$opaqueIdentity];if($event->value===$this->fault)throw new RuntimeException('synthetic observer');
  }
 };
 $s=new O\AssignmentOrderOriginalFileStorage($f->privateRoot,new O\AssignmentOrderOriginalFixedClock('2026-09-02T07:00:00Z'),new O\AssignmentOrderOriginalNoFaults(),$observer);
 $stage=$s->beginStage();$stage->write('owned-stage');$stage->close();$before=$s->inventoryCanonicalJson();
 $s->listOrphans('2026-09-02T07:30:00Z',10,null);$lock=$s->acquireDigestLock('stage-0001');
 try {
  assertSameValue($fault==='digest_lock_acquired'?'failed':'ok',$lock->status()->value,'acquisition callback has total status');
  if($fault!=='digest_lock_acquired')assertSameValue($fault==='delete_done'?'ok':'failed',$s->deleteLocked($lock)->value,'completed deletion survives final callback failure');
 } finally {$lock->release();$lock->release();}
 $expected=[['digest_lock_acquired','stage-0001']];if($fault!=='digest_lock_acquired')$expected[]=['delete_begin','stage-0001'];if($fault==='delete_done')$expected[]=['delete_done','stage-0001'];
 assertSameValue($expected,$observer->events,'each actual callback once with no internal retry');
 if($fault==='delete_done')assertSameValue([],$s->listOrphans('2026-09-02T07:30:00Z',10,null)->candidates(),'completed deletion not undone');
 else assertSameValue($before,$s->inventoryCanonicalJson(),'pre-delete callback failure preserves inventory');
 assertSameValue('failed',$s->deleteLocked($lock)->value,'released lock cannot repeat delete');assertSameValue($expected,$observer->events,'refused repeat emits no primitive events');
 $other=mntStorage($f);$again=$other->acquireDigestLock('stage-0001');try{assertSameValue('ok',$again->status()->value,'observer failure releases native exclusion');}finally{$again->release();}
});
integrityDone('ASSIGNMENT_ORDER_ORIGINAL_MAINTENANCE_STORAGE_OK');
