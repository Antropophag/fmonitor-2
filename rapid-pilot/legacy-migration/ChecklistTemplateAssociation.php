<?php

declare(strict_types=1);

require_once __DIR__.'/LegacyChecklistTemplateSnapshot.php';

final class ChecklistTemplateAssociationPolicy
{
    public const VERSION='checklist-template-association-v1';
    public const SUBJECTS=['legacy_active_baseline','operational_case','native_checklist_event'];

    public static function validate(string $subjectKind,string $effectiveAt,string $validFrom,string $snapshotVersion,string $snapshotHash):array
    {
        if(!in_array($subjectKind,self::SUBJECTS,true))return['allowed'=>false,'conflictCode'=>'DEFINITION_VERSION_UNPROVEN'];
        foreach([$effectiveAt,$validFrom]as$value){$date=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$value,new DateTimeZone('UTC'));if(!$date||$date->format('Y-m-d H:i:s')!==$value)throw new InvalidArgumentException('Invalid exact timestamp');}
        if($effectiveAt<$validFrom)return['allowed'=>false,'conflictCode'=>'DEFINITION_VERSION_UNPROVEN'];
        if($snapshotVersion!==LegacyChecklistTemplateSnapshot::VERSION||preg_match('/^[a-f0-9]{64}$/D',$snapshotHash)!==1)return['allowed'=>false,'conflictCode'=>'DEFINITION_VERSION_UNPROVEN'];
        return['allowed'=>true,'conflictCode'=>null];
    }
}

final class ChecklistTemplateAssociationTarget
{
    public function __construct(private mysqli$db,private string$prefix){if(preg_match('/^[A-Za-z0-9_]+$/D',$prefix)!==1)throw new InvalidArgumentException('Invalid local prefix');}

    public function associate(string$subjectKind,string$subjectId,string$effectiveAt,int$snapshotId,string$expectedHash,string$expectedVersion,string$createdAt):array
    {
        if($subjectId===''||strlen($subjectId)>160||$snapshotId<1)throw new InvalidArgumentException('Invalid association identity');$p=$this->prefix;
        $snapshot=$this->db->query("SELECT snapshot_version,valid_from,content_sha256 FROM `{$p}fm2_checklist_template_snapshots` WHERE id=".$snapshotId)->fetch_assoc();
        if(!is_array($snapshot)||!hash_equals((string)$snapshot['content_sha256'],$expectedHash)||(string)$snapshot['snapshot_version']!==$expectedVersion)throw new DomainException('CHECKLIST_TEMPLATE_SNAPSHOT_MISMATCH');
        $policy=ChecklistTemplateAssociationPolicy::validate($subjectKind,$effectiveAt,(string)$snapshot['valid_from'],$expectedVersion,$expectedHash);
        if(!$policy['allowed'])throw new DomainException((string)$policy['conflictCode']);
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$p}fm2_checklist_template_associations`(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,association_version VARCHAR(80) NOT NULL,subject_kind VARCHAR(40) NOT NULL,subject_id VARCHAR(160) NOT NULL,effective_at DATETIME NOT NULL,template_snapshot_id BIGINT UNSIGNED NOT NULL,template_snapshot_version VARCHAR(80) NOT NULL,template_content_sha256 CHAR(64) NOT NULL,created_at DATETIME NOT NULL,UNIQUE KEY uq_subject(subject_kind,subject_id),KEY snapshot_id(template_snapshot_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $version=ChecklistTemplateAssociationPolicy::VERSION;
        $insert=$this->db->prepare("INSERT IGNORE INTO `{$p}fm2_checklist_template_associations`(association_version,subject_kind,subject_id,effective_at,template_snapshot_id,template_snapshot_version,template_content_sha256,created_at) VALUES(?,?,?,?,?,?,?,?)");$insert->bind_param('ssssisss',$version,$subjectKind,$subjectId,$effectiveAt,$snapshotId,$expectedVersion,$expectedHash,$createdAt);$insert->execute();$created=$insert->affected_rows===1;
        $lookup=$this->db->prepare("SELECT id,effective_at,template_snapshot_id,template_snapshot_version,template_content_sha256 FROM `{$p}fm2_checklist_template_associations` WHERE subject_kind=? AND subject_id=?");$lookup->bind_param('ss',$subjectKind,$subjectId);$lookup->execute();$stored=$lookup->get_result()->fetch_assoc();
        if(!is_array($stored)||(string)$stored['effective_at']!==$effectiveAt||(int)$stored['template_snapshot_id']!==$snapshotId||(string)$stored['template_snapshot_version']!==$expectedVersion||!hash_equals((string)$stored['template_content_sha256'],$expectedHash))throw new DomainException('CHECKLIST_TEMPLATE_ASSOCIATION_CONFLICT');
        return['associationId'=>(int)$stored['id'],'created'=>$created,'associationVersion'=>$version,'templateSnapshotId'=>$snapshotId,'templateSnapshotVersion'=>$expectedVersion,'templateContentSha256'=>$expectedHash];
    }

    public function associateActiveBaseline(int$legacyObjectId,int$snapshotId,string$createdAt):array
    {
        $p=$this->prefix;$baseline=$this->db->query("SELECT id,cutover_at FROM `{$p}fm2_legacy_active_baselines` WHERE legacy_object_id=".$legacyObjectId)->fetch_assoc();if(!is_array($baseline))throw new OutOfBoundsException('ACTIVE_BASELINE_NOT_FOUND');
        $snapshot=$this->db->query("SELECT snapshot_version,content_sha256 FROM `{$p}fm2_checklist_template_snapshots` WHERE id=".$snapshotId)->fetch_assoc();if(!is_array($snapshot))throw new OutOfBoundsException('CHECKLIST_TEMPLATE_NOT_FOUND');
        return$this->associate('legacy_active_baseline',(string)$baseline['id'],(string)$baseline['cutover_at'],$snapshotId,(string)$snapshot['content_sha256'],(string)$snapshot['snapshot_version'],$createdAt);
    }
}
