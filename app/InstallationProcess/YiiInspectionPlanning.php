<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final readonly class YiiInspectionPlanning
{
    public function __construct(private MariaDbYiiInspectionPlanning$store,private \Closure$clock){}
    public function scheduleInspection(int$actorId,int$objectId,string$date):array
    {
        $current=$this->currentPlan($actorId,$objectId);
        if($current===null){$request=$this->uuidFrom(hash('sha256',implode('|',['legacy-create',$actorId,$objectId,$date])));return$this->legacyResult($this->createInspectionPlan($actorId,$objectId,0,$date,$request));}
        if($current['inspectionDate']===$date)return['status'=>'scheduled','scheduleId'=>$current['scheduleId']];
        $request=$this->uuidFrom(hash('sha256',implode('|',['legacy-reschedule',$actorId,$objectId,$date])));$result=$this->rescheduleInspectionPlan($actorId,$objectId,$current['scheduleId'],$current['version'],$date,$request);
        if($result['status']==='stale_plan'){$latest=$this->currentPlan($actorId,$objectId);if($latest!==null&&$latest['inspectionDate']===$date)return['status'=>'scheduled','scheduleId'=>$latest['scheduleId']];if($latest!==null)$result=$this->rescheduleInspectionPlan($actorId,$objectId,$latest['scheduleId'],$latest['version'],$date,$request);}
        return$this->legacyResult($result);
    }
    public function createInspectionPlan(int$actor,int$object,int$expected,string$date,string$request):array{return$this->command('create',$actor,$object,null,$expected,$date,$request);}
    public function rescheduleInspectionPlan(int$actor,int$object,int$id,int$expected,string$date,string$request):array{return$this->command('reschedule',$actor,$object,$id,$expected,$date,$request);}
    public function cancelInspectionPlan(int$actor,int$object,int$id,int$expected,string$request):array{return$this->command('cancel',$actor,$object,$id,$expected,null,$request);}
    public function currentPlan(int$actor,int$object):?array
    {
        $tx=$this->store->begin();try{$this->store->assertReady();if(!$this->store->actorCanSchedule($actor)){$tx->rollBack();return null;}$case=$this->store->lockEligibleCase($object);if($case===null||!$this->store->actorHasObjectScope($actor,$case)){$tx->rollBack();return null;}$r=$this->store->currentForCase($case['caseId'],$this->now()->format('Y-m-d'));$tx->commit();return$r?['objectId'=>$object,'scheduleId'=>(int)$r['id'],'version'=>(int)$r['event_version'],'inspectionDate'=>(string)$r['inspection_date']]:null;}catch(\Throwable$x){if($tx->isActive)$tx->rollBack();throw$x;}
    }
    private function command(string$kind,int$actor,int$object,?int$id,int$expected,?string$date,string$request):array
    {
        $tx=$this->store->begin();try{$this->store->assertReady();if(!$this->store->actorCanSchedule($actor)){$tx->rollBack();return['status'=>'access_denied'];}$finger=$this->fingerprint($kind,$actor,$object,$id,$expected,$date);$receipt=$this->store->receipt($request);if($receipt){$tx->rollBack();return hash_equals((string)$receipt['request_fingerprint'],$finger)?$this->resultFromReceipt($receipt):['status'=>'request_conflict'];}$now=$this->now();if($date!==null&&!$this->validDate($date,$now)){$tx->rollBack();return['status'=>'invalid_date'];}$case=$this->store->lockEligibleCase($object);if($case===null||!$this->store->actorHasObjectScope($actor,$case)){$tx->rollBack();return['status'=>'not_found'];}$receipt=$this->store->receipt($request,true);if($receipt){$tx->rollBack();return hash_equals((string)$receipt['request_fingerprint'],$finger)?$this->resultFromReceipt($receipt):['status'=>'request_conflict'];}$stamp=$now->format(DATE_ATOM);
            if($kind==='create'){if($expected!==0){$tx->rollBack();return['status'=>'stale_plan'];}if($this->store->currentForCase($case['caseId'],$now->format('Y-m-d'),true)!==null){$tx->rollBack();return['status'=>'conflict'];}$id=$this->store->createRoot($case,$object,$date,$actor,$stamp);$payload=['scheduleId'=>$id,'inspectionDate'=>$date];$this->store->appendEvent($id,$case['caseId'],'inspection_scheduled',1,$request,$finger,$payload,$actor,$stamp);$tx->commit();return['status'=>'scheduled','scheduleId'=>$id,'version'=>1,'inspectionDate'=>$date];}
            $plan=$id===null?null:$this->store->scheduleById($id,true);$current=$this->store->currentForCase($case['caseId'],$now->format('Y-m-d'),true);if(!$plan||!$current||(int)$current['id']!==$id||(int)$plan['legacy_object_id']!==$object||(int)$plan['installation_case_id']!==$case['caseId']||(int)$plan['event_version']!==$expected||$plan['event_type']==='inspection_cancelled'){$tx->rollBack();return['status'=>'stale_plan'];}$version=$expected+1;
            if($kind==='reschedule'){$old=(string)$plan['inspection_date'];$payload=['scheduleId'=>$id,'oldDate'=>$old,'newDate'=>$date];$this->store->appendEvent($id,$case['caseId'],'inspection_rescheduled',$version,$request,$finger,$payload,$actor,$stamp);$tx->commit();return['status'=>'scheduled','scheduleId'=>$id,'version'=>$version,'inspectionDate'=>$date];}
            $payload=['scheduleId'=>$id,'inspectionDate'=>(string)$plan['inspection_date']];$this->store->appendEvent($id,$case['caseId'],'inspection_cancelled',$version,$request,$finger,$payload,$actor,$stamp);$tx->commit();return['status'=>'cancelled','scheduleId'=>$id,'version'=>$version];
        }catch(\yii\db\IntegrityException$x){if($tx->isActive)$tx->rollBack();return['status'=>$kind==='create'?'conflict':'stale_plan'];}catch(\Throwable$x){if($tx->isActive)$tx->rollBack();throw$x;}
    }
    private function resultFromReceipt(array$r):array{$p=json_decode((string)$r['payload_json'],true,flags:JSON_THROW_ON_ERROR);if(is_string($p))$p=json_decode($p,true,flags:JSON_THROW_ON_ERROR);$base=['status'=>$r['event_type']==='inspection_cancelled'?'cancelled':'scheduled','scheduleId'=>(int)$r['schedule_id'],'version'=>(int)$r['event_version']];if($r['event_type']!=='inspection_cancelled')$base['inspectionDate']=(string)($p['newDate']??$p['inspectionDate']);return$base;}
    private function legacyResult(array$r):array{return isset($r['scheduleId'])?['status'=>$r['status'],'scheduleId'=>$r['scheduleId']]:['status'=>$r['status']==='not_found'?'ineligible':$r['status']];}
    private function fingerprint(string$kind,int$actor,int$object,?int$id,int$expected,?string$date):string{return hash('sha256',json_encode([$kind,$actor,$object,$id,$expected,$date],JSON_THROW_ON_ERROR));}
    private function now():\DateTimeImmutable{$v=($this->clock)();if(!$v instanceof \DateTimeInterface)throw new \RuntimeException('Clock unavailable.');return(new \DateTimeImmutable($v->format(DATE_ATOM)))->setTimezone(new \DateTimeZone('Europe/Moscow'));}
    private function validDate(string$v,\DateTimeImmutable$n):bool{$d=\DateTimeImmutable::createFromFormat('!Y-m-d',$v,new \DateTimeZone('Europe/Moscow'));return$d!==false&&$d->format('Y-m-d')===$v&&$v>=$n->format('Y-m-d');}
    private function uuidFrom(string$h):string{return substr($h,0,8).'-'.substr($h,8,4).'-4'.substr($h,13,3).'-8'.substr($h,17,3).'-'.substr($h,20,12);}
}
