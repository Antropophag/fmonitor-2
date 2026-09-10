<?php
declare(strict_types=1);
namespace FMonitor2\InspectionEvidence;
trait MariaDbYiiChecklistMutation
{
        public function accept(int $objectId,int $actorId,array $o,?string $bytes=null):array
        {
            $type=(string)($o['type']??'');
    if($type==='item_completed')return$this->completeItem($objectId,$actorId,$o);
            $access=$this->commandAccess($actorId,$objectId,$type);
    if(!($access['exists']??false))return['status'=>'not_found'];
    $opened=(bool)($access['opened']??false);
    $role=(bool)($access['roleAccess']??false);
    if($type==='photo_uploaded'&&(!$opened||!$role))return['status'=>'forbidden'];
    if(!$role&&!($opened&&in_array($type,['completion_retracted','photo_revoked'],true)))return['status'=>'forbidden'];
            $id=(string)($o['clientOperationId']??'');
    $device=(string)($o['deviceInstallationId']??'');
    $time=(string)($o['deviceTime']??'');
    $base=$o['baseRevision']??null;
    $section=$o['sectionId']??null;
    if(!$this->uuid($id)||!$this->uuid($device)||!$this->instant($time)||!is_int($base)||$base<0||!is_int($section)||!isset(self::ITEMS[$section]))return['status'=>'rejected'];
            if($duplicate=$this->duplicate($id))return['status'=>'duplicate','revision'=>(int)$duplicate['accepted_revision']];
            $this->begin();
    try{$case=$this->case($objectId,true);
    if($case===null)
        {$this->rollback();
    return['status'=>'not_found'];
    }if($case['process_state']!=='working')
        { $this->rollback();
    return['status'=>'rejected'];
    }$caseId=(int)$case['id'];
    $this->execute("INSERT IGNORE INTO {$this->t('fm2_checklist_revisions')}(installation_case_id,revision_no,updated_at)VALUES(?,0,?)",[$caseId,$this->now]);
    $template=$this->template($caseId,$time);
    if($template===null)
        {$this->rollback();
    return['status'=>'rejected'];
    }$revision=$this->revision($caseId,true);
    if($base>$revision)
        {$this->rollback();
    return['status'=>'conflict','revision'=>$revision];
    }$item=null;
    $payload=[];
                if($type==='item_installers_changed')
        {$item=$o['itemId']??null;
    $selected=$this->selected($caseId,$o['installerTabIds']??null);
    if(!is_int($item)||!in_array($item,self::ITEMS[$section],true)||$selected===null||!$this->completed($caseId,$item))
        { $this->rollback();
    return['status'=>'rejected'];
    }$payload=['installerTabIds'=>array_column($selected,'tabId')];
    } elseif($type==='completion_retracted')
        {$item=$o['itemId']??null;
    $reason=$o['reason']??null;
    $original=$o['originalClientOperationId']??null;
    if(!is_int($item)||!in_array($item,self::ITEMS[$section],true)||!is_string($reason)||trim($reason)===''||mb_strlen(trim($reason))>500||!is_string($original)||!$this->uuid($original)||!$this->mayRetract($caseId,$item,$original,$actorId,(bool)$access['assigned']))
        {$this->rollback();
    return['status'=>'rejected'];
    }$payload=['originalClientOperationId'=>$original,'reason'=>trim($reason)];
    } elseif($type==='photo_uploaded')
        {$failure=$this->storePhoto($caseId,$section,$id,$actorId,$time,$o,$bytes);
    if($failure)
        {$this->rollback();
    return$failure;
    }$payload=['sha256'=>$o['sha256'],'mime'=>$o['mime'],'size'=>$o['size'],'originalName'=>$o['originalName']];
    } elseif($type==='photo_revoked')
        {$photo=$o['photoId']??null;
    $reason=$o['reason']??null;
    if(!($access['assigned']??false)||!($access['photoRevoke']??false)||!is_int($photo)||!is_string($reason)||trim($reason)===''||mb_strlen(trim($reason))>500||$this->lastPhoto($caseId,$section)||!$this->revoke($caseId,$section,$photo))
        {$this->rollback();
    return['status'=>'rejected'];
    }$payload=['photoId'=>$photo,'reason'=>trim($reason)];
    } elseif($type==='section_completed')
        {if(!$this->sectionReady($caseId,$section))
        { $this->rollback();
    return['status'=>'rejected'];
    }} else{$this->rollback();
    return['status'=>'rejected'];
    }
                $next=$revision+1;
    $json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    $snapshot=(int)$template['snapshot_id'];
    $version=$template['snapshot_version'];
    $hash=$template['content_sha256'];
    $this->execute("INSERT INTO {$this->t('fm2_checklist_operations')}(installation_case_id,client_operation_id,device_installation_id,operation_type,section_id,item_id,actor_user_id,device_time,server_received_at,base_revision,accepted_revision,payload_json,template_snapshot_id,template_snapshot_version,template_content_sha256)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",[$caseId,$id,$device,$type,$section,$item,$actorId,$time,$this->now,$base,$next,$json,$snapshot,$version,$hash]);
    if(isset($selected))$this->saveSelected($id,$selected,'correction');
    $this->setRevision($caseId,$next);
    $this->commit();
    return['status'=>'accepted','revision'=>$next];
            } catch(\Throwable$e)
        {$this->rollback();
    if($this->yii instanceof \yii\db\Connection&&!$e instanceof \yii\db\IntegrityException)throw$e;
    if($d=$this->duplicate($id))return['status'=>'duplicate','revision'=>(int)$d['accepted_revision']];
    throw$e;
    }
        }
        private function completeItem(int $objectId,int $actor,array $o):array
        {
            $case=$this->case($objectId,false);
    if($case===null)return['status'=>'not_found'];
    $caseId=(int)$case['id'];
    $owner=$this->nativeRecording;
    if($owner===null&&$this->db!==null)$owner=ProductionInspectionEvidenceFactory::create($this->db,new ProductionInspectionEvidenceConfig($this->prefix),new readonly class($this->now)implements InspectionEvidenceClock{public function __construct(private string$v)
        {}
        public function now():\DateTimeImmutable{return new \DateTimeImmutable($this->v);
    }},$this->application($caseId));
    if($owner===null)throw new \RuntimeException('Native inspection recording is not configured.');
            $r=$owner->completeItem(new CompleteInspectionItem($actor,$caseId,(string)($o['clientOperationId']??''),(string)($o['deviceInstallationId']??''),(string)($o['deviceTime']??''),(int)($o['baseRevision']??-1),(int)($o['sectionId']??0),(int)($o['itemId']??0),array_map('intval',(array)($o['installerTabIds']??[]))));
            if($r->status==='INSPECTION_SCHEMA_UNAVAILABLE')throw new \RuntimeException();
    return['status'=>match($r->status)
        {'ACCEPTED'=>'accepted','DUPLICATE'=>'duplicate','STALE_REVISION','OPERATION_PAYLOAD_CONFLICT'=>'conflict',default=>'rejected'},'revision'=>$r->revision];
        }
        private function template(int$case,string$time):?array{$r=$this->one("SELECT a.template_snapshot_id snapshot_id,a.template_snapshot_version snapshot_version,a.template_content_sha256 content_sha256,t.valid_from,a.effective_at,t.snapshot_version current_version,t.content_sha256 current_hash FROM {$this->t('fm2_checklist_template_associations')} a JOIN {$this->t('fm2_checklist_template_snapshots')} t ON t.id=a.template_snapshot_id WHERE a.subject_kind='operational_case' AND a.subject_id=?",[(string)$case]);
    if(!$r||$r['snapshot_version']!==$r['current_version']||!hash_equals($r['content_sha256'],$r['current_hash']))return null;
    return$r;
    }
        private function duplicate(string$id):?array{return$this->one("SELECT accepted_revision FROM {$this->t('fm2_checklist_operations')} WHERE client_operation_id=?",[$id]);
    }
        private function revision(int$id,bool$lock):int{$r=$this->one("SELECT revision_no FROM {$this->t('fm2_checklist_revisions')} WHERE installation_case_id=?".($lock?' FOR UPDATE':''),[$id]);
    return(int)($r['revision_no']??0);
    }
        private function setRevision(int$id,int$r):void{
    $this->execute("UPDATE {$this->t('fm2_checklist_revisions')} SET revision_no=?,updated_at=? WHERE installation_case_id=?",[$r,$this->now,$id]);
    }
        private function completed(int$c,int$i):bool{return$this->one("SELECT 1 FROM {$this->t('fm2_checklist_operations')} WHERE installation_case_id=? AND item_id=? AND operation_type IN('item_completed','item_installers_changed') AND NOT EXISTS(SELECT 1 FROM {$this->t('fm2_checklist_operations')} r WHERE r.installation_case_id=? AND r.item_id=? AND r.operation_type='completion_retracted' AND r.id>{$this->t('fm2_checklist_operations')}.id) LIMIT 1",[$c,$i,$c,$i])!==null;
    }
        private function mayRetract(int$c,int$i,string$o,int$a,bool$assigned):bool{if(!$assigned)return false;
    $r=$this->one("SELECT actor_user_id FROM {$this->t('fm2_checklist_operations')} WHERE installation_case_id=? AND item_id=? AND client_operation_id=? AND operation_type IN('item_completed','item_installers_changed')",[$c,$i,$o]);
    return$r!==null&&((int)$r['actor_user_id']===$a||$assigned);
    }
        private function sectionReady(int$c,int$s):bool{foreach(self::ITEMS[$s]as$i)if(!$this->completed($c,$i))return false;
    return(int)$this->one("SELECT COUNT(*) n FROM {$this->t('fm2_checklist_photos')} WHERE installation_case_id=? AND section_id=? AND revoked_at IS NULL",[$c,$s])['n']>0;
    }
        private function lastPhoto(int$c,int$s):bool{return$this->one("SELECT 1 FROM {$this->t('fm2_checklist_operations')} WHERE installation_case_id=? AND section_id=? AND operation_type='section_completed' LIMIT 1",[$c,$s])!==null&&(int)$this->one("SELECT COUNT(*) n FROM {$this->t('fm2_checklist_photos')} WHERE installation_case_id=? AND section_id=? AND revoked_at IS NULL",[$c,$s])['n']<=1;
    }
        private function revoke(int$c,int$s,int$i):bool{
    return$this->execute("UPDATE {$this->t('fm2_checklist_photos')} SET revoked_at=? WHERE id=? AND installation_case_id=? AND section_id=? AND revoked_at IS NULL",[$this->now,$i,$c,$s])===1;
    }
}
