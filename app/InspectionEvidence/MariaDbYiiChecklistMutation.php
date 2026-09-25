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
    if($type==='completion_retracted'&&!($access['assigned']??false))return['status'=>'forbidden'];
    if($type==='photo_revoked'&&(!($access['assigned']??false)||!($access['photoRevoke']??false)))return['status'=>'forbidden'];
            $id=(string)($o['clientOperationId']??'');
    $device=(string)($o['deviceInstallationId']??'');
    $time=(string)($o['deviceTime']??'');
    $base=$o['baseRevision']??null;
    $section=$o['sectionId']??null;
    if(!$this->uuid($id)||!$this->uuid($device)||!$this->instant($time)||!is_int($base)||$base<0||!is_int($section)||!isset(self::ITEMS[$section]))return['status'=>'rejected'];
            if($replay=$this->replay($id,$objectId,$actorId,$device,$type,$section,$o,$bytes))return$replay;$createdPhoto=null;
            $this->begin();
    try{$case=$this->case($objectId,true);
    if($case===null)
        {$this->rollback();
    return['status'=>'not_found'];
    }if($case['process_state']!=='working')
        { $this->rollback();
    return['status'=>'rejected','reason'=>'case_not_working'];
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
        {$photoPath=is_string($o['sha256']??null)?$this->storageRoot.'/checklist/'.strtolower($o['sha256']).'.bin':null;$photoExisted=$photoPath!==null&&is_file($photoPath);
    if($failure=$this->storePhoto($caseId,$section,$id,$actorId,$time,$o,$bytes)){$this->rollback();return$failure;}
    if(!$photoExisted&&$photoPath!==null&&is_file($photoPath))$createdPhoto=['path'=>$photoPath,'sha256'=>(string)$o['sha256']];
    $payload=['sha256'=>$o['sha256'],'mime'=>$o['mime'],'size'=>$o['size'],'originalName'=>$o['originalName']];
    } elseif($type==='photo_revoked')
        {$photo=$o['photoId']??null;
    $reason=$o['reason']??null;
    if(!is_int($photo)||!is_string($reason)||trim($reason)===''||mb_strlen(trim($reason))>500||$this->lastPhoto($caseId,$section)||!$this->revoke($caseId,$section,$photo))
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
    if($createdPhoto!==null&&!$this->one("SELECT 1 FROM {$this->t('fm2_checklist_photos')} WHERE sha256=? LIMIT 1",[$createdPhoto['sha256']]))@unlink($createdPhoto['path']);if($replay=$this->replay($id,$objectId,$actorId,$device,$type,$section,$o,$bytes))return$replay;throw$e;
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
        {'ACCEPTED'=>'accepted','DUPLICATE'=>'duplicate','STALE_REVISION','OPERATION_PAYLOAD_CONFLICT'=>'conflict','ACTOR_NOT_AUTHORIZED'=>'forbidden',default=>'rejected'},'revision'=>$r->revision]+($r->status==='CASE_NOT_WORKING'?['reason'=>'case_not_working']:[]);
        }
        private function template(int$case,string$time):?array{$r=$this->one("SELECT a.template_snapshot_id snapshot_id,a.template_snapshot_version snapshot_version,a.template_content_sha256 content_sha256,t.valid_from,a.effective_at,t.snapshot_version current_version,t.content_sha256 current_hash FROM {$this->t('fm2_checklist_template_associations')} a JOIN {$this->t('fm2_checklist_template_snapshots')} t ON t.id=a.template_snapshot_id WHERE a.subject_kind='operational_case' AND a.subject_id=?",[(string)$case]);
    if(!$r||$r['snapshot_version']!==$r['current_version']||!hash_equals($r['content_sha256'],$r['current_hash']))return null;
    return$r;
    }
        private function replay(string$id,int$objectId,int$actorId,string$device,string$type,int$section,array$o,?string$bytes):?array{
    $stored=$this->one("SELECT installation_case_id,device_installation_id,operation_type,section_id,item_id,actor_user_id,accepted_revision,payload_json FROM {$this->t('fm2_checklist_operations')} WHERE client_operation_id=?",[$id]);if($stored===null)return null;$case=$this->case($objectId,false);
    $matches=$case!==null&&(int)$stored['installation_case_id']===(int)$case['id']&&(string)$stored['operation_type']===$type&&(int)$stored['actor_user_id']===$actorId&&(string)$stored['device_installation_id']===$device&&(int)$stored['section_id']===$section;$expected=$this->replayPayload($type,$o);try{$payload=json_decode((string)$stored['payload_json'],true,32,JSON_THROW_ON_ERROR);}catch(\JsonException){$payload=null;}
    $item=$o['itemId']??null;$expectedItem=in_array($type,['item_installers_changed','completion_retracted'],true)&&is_int($item)?$item:null;if(!$matches||$expected===null||!is_array($payload)||($stored['item_id']===null?null:(int)$stored['item_id'])!==$expectedItem||$this->replayPayload($type,$payload,true)!==$expected)return['status'=>'conflict'];
    return!$this->replayValid($type,$o,$bytes)?['status'=>'rejected']:['status'=>'duplicate','revision'=>(int)$stored['accepted_revision']];}
        private function replayValid(string$type,array$o,?string$bytes):bool{
    if($type==='item_installers_changed'){$ids=$o['installerTabIds']??null;return is_array($ids)&&$ids!==[]&&count($ids)===count(array_unique(array_map('strval',$ids)));}if($type!=='photo_uploaded')return true;
    $mime=$o['mime']??null;$size=$o['size']??null;$sha=$o['sha256']??null;$name=$o['originalName']??null;$image=is_string($bytes)?@getimagesizefromstring($bytes):false;return is_string($bytes)&&in_array($mime,['image/jpeg','image/png','image/webp'],true)&&is_array($image)&&($image['mime']??null)===$mime&&is_int($size)&&$size>=1&&$size<=5242880&&strlen($bytes)===$size&&is_string($sha)&&preg_match('/^[a-f0-9]{64}$/D',$sha)===1&&hash_equals($sha,hash('sha256',$bytes))&&is_string($name)&&$name!==''&&mb_strlen($name)<=255&&preg_match('/[\x00-\x1f\x7f]/u',$name)!==1;}
        private function replayPayload(string$type,array$payload,bool$stored=false):?array{
    $keys=match($type){'item_installers_changed'=>['installerTabIds'],'completion_retracted'=>['originalClientOperationId','reason'],'photo_uploaded'=>['sha256','mime','size','originalName'],'photo_revoked'=>['photoId','reason'],'section_completed'=>[],default=>null};if($keys===null)return null;if($stored){$actualKeys=array_keys($payload);sort($actualKeys);$expectedKeys=$keys;sort($expectedKeys);if($actualKeys!==$expectedKeys)return null;}
    if($type==='item_installers_changed'){$ids=$payload['installerTabIds']??null;if(!is_array($ids)||$ids===[]||array_filter($ids,static fn($id)=>!is_int($id)&&!is_string($id)))return null;$ids=array_values(array_unique(array_map('strval',$ids)));sort($ids,SORT_STRING);return['installerTabIds'=>$ids];}
    if($type==='completion_retracted'){$original=$payload['originalClientOperationId']??null;$reason=$payload['reason']??null;return is_string($original)&&is_string($reason)?['originalClientOperationId'=>$original,'reason'=>trim($reason)]:null;}
    if($type==='photo_uploaded'){$sha=$payload['sha256']??null;$mime=$payload['mime']??null;$size=$payload['size']??null;$name=$payload['originalName']??null;return is_string($sha)&&is_string($mime)&&is_int($size)&&is_string($name)?['sha256'=>strtolower($sha),'mime'=>$mime,'size'=>$size,'originalName'=>$name]:null;}
    if($type==='photo_revoked'){$photo=$payload['photoId']??null;$reason=$payload['reason']??null;return is_int($photo)&&is_string($reason)?['photoId'=>$photo,'reason'=>trim($reason)]:null;}return$type==='section_completed'?[]:null;}
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
