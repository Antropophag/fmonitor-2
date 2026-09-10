<?php
declare(strict_types=1);
namespace FMonitor2\InspectionEvidence;

trait MariaDbYiiChecklistPhoto
{
        private function storePhoto(int$c,int$s,string$id,int$a,string$time,array$o,?string$b):?array{$mime=$o['mime']??null;
    $size=$o['size']??null;
    $sha=$o['sha256']??null;
    $name=$o['originalName']??null;
    $image=is_string($b)?@getimagesizefromstring($b):false;
    if(!is_string($b)||!in_array($mime,['image/jpeg','image/png','image/webp'],true)||!is_array($image)||($image['mime']??null)!==$mime||!is_int($size)||$size<1||$size>5242880||strlen($b)!==$size||!is_string($sha)||!preg_match('/^[a-f0-9]{64}$/D',$sha)||!hash_equals($sha,hash('sha256',$b))||!is_string($name)||$name===''||mb_strlen($name)>255)return['status'=>'rejected'];
    if($this->one("SELECT 1 FROM {$this->t('fm2_checklist_photos')} WHERE installation_case_id=? AND section_id=? AND sha256=? AND revoked_at IS NULL",[$c,$s,$sha]))return['status'=>'duplicate','revision'=>$this->revision($c,false)];
    if((int)$this->one("SELECT COUNT(*) n FROM {$this->t('fm2_checklist_photos')} WHERE installation_case_id=? AND section_id=? AND revoked_at IS NULL",[$c,$s])['n']>=10)return['status'=>'rejected'];
    $dir=$this->storageRoot.'/checklist';
    if(!is_dir($dir)&&!@mkdir($dir,0700,true)&&!is_dir($dir))throw new \RuntimeException();
    $storage=$sha.'.bin';
    $path=$dir.'/'.$storage;
    if(!is_file($path)&&file_put_contents($path,$b,LOCK_EX)!==$size)throw new \RuntimeException();
    @chmod($path,0600);
    $this->execute("INSERT INTO {$this->t('fm2_checklist_photos')}(installation_case_id,section_id,upload_operation_id,sha256,mime_type,byte_size,original_name,storage_name,actor_user_id,device_time,server_received_at)VALUES(?,?,?,?,?,?,?,?,?,?,?)",[$c,$s,$id,$sha,$mime,$size,$name,$storage,$a,$time,$this->now]);
    return null;
    }

        private function saveSelected(string$id,array$rows,string$source):void{
    foreach($rows as$x)
        {$tab=(string)$x['tabId'];
    $this->execute("INSERT INTO {$this->t('fm2_checklist_operation_installers')}(client_operation_id,installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot,dismissal_effective_at_snapshot,workforce_source_updated_at_snapshot,assignment_source)VALUES(?,?,?,?,?,?,?,?)",[$id,$tab,$x['fio'],$x['position'],$x['employmentStatus'],$x['dismissalEffectiveAt'],$x['sourceUpdatedAt'],$source]);
    }}

}
