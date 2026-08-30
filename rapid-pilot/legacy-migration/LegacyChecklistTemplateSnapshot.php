<?php

declare(strict_types=1);

final class LegacyChecklistTemplateSnapshot
{
    public const VERSION='legacy-checklist-template-cutover-v1';

    public static function build(array $parts,array $definitions,string $capturedAt):array
    {
        $date=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$capturedAt,new DateTimeZone('UTC'));
        if(!$date||$date->format('Y-m-d H:i:s')!==$capturedAt)throw new InvalidArgumentException('Invalid captured_at');
        usort($parts,static fn($a,$b)=>[(int)$a['rang'],(int)$a['id']]<=>[(int)$b['rang'],(int)$b['id']]);
        usort($definitions,static fn($a,$b)=>[(int)$a['part_id'],(int)$a['rang'],(int)$a['id']]<=>[(int)$b['part_id'],(int)$b['rang'],(int)$b['id']]);
        $ids=[];$total=0;
        foreach($definitions as$row){$id=filter_var($row['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$share=filter_var($row['share']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>100]]);if($id===false||$share===false||isset($ids[$id]))throw new InvalidArgumentException('Invalid checklist definition');$ids[$id]=true;$total+=$share;}
        $payload=['snapshotVersion'=>self::VERSION,'capturedAt'=>$capturedAt,'validFrom'=>$capturedAt,'validity'=>'current_at_cutover_for_active_baselines_and_future_native_events','source'=>'legacy_fmonitor.fm_install_checklist+parts','parts'=>$parts,'definitions'=>$definitions];
        $json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        return ['payload'=>$payload,'contentSha256'=>hash('sha256',$json),'counts'=>['parts'=>count($parts),'definitions'=>count($definitions),'totalShare'=>$total]];
    }
}

final class LegacyChecklistTemplateMySqlSource
{
    public function __construct(private mysqli $db){}
    public function extract(string $capturedAt):array
    {
        $parts=$this->db->query('SELECT id,name,rang FROM fm_install_checklist_parts ORDER BY rang,id')->fetch_all(MYSQLI_ASSOC);
        $definitions=$this->db->query('SELECT id,part_id,name,share,rang,needphoto FROM fm_install_checklist ORDER BY part_id,rang,id')->fetch_all(MYSQLI_ASSOC);
        return LegacyChecklistTemplateSnapshot::build($parts,$definitions,$capturedAt);
    }
}

final class LegacyChecklistTemplateMySqlTarget
{
    public function __construct(private mysqli $db,private string $prefix){if(preg_match('/^[A-Za-z0-9_]+$/D',$prefix)!==1)throw new InvalidArgumentException('Invalid local prefix');}
    public function apply(array $snapshot,string $capturedAt,string $createdAt):array
    {
        $p=$this->prefix;$this->db->query("CREATE TABLE IF NOT EXISTS `{$p}fm2_checklist_template_snapshots`(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,snapshot_version VARCHAR(80) NOT NULL,captured_at DATETIME NOT NULL,valid_from DATETIME NOT NULL,validity_scope VARCHAR(120) NOT NULL,source_label VARCHAR(160) NOT NULL,content_sha256 CHAR(64) NOT NULL,payload_json LONGTEXT NOT NULL,created_at DATETIME NOT NULL,UNIQUE KEY uq_hash(content_sha256),UNIQUE KEY uq_valid_from(valid_from)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $hash=(string)$snapshot['contentSha256'];$json=json_encode($snapshot['payload'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $this->db->begin_transaction();try{
            $lookup=$this->db->prepare("SELECT id,content_sha256 FROM `{$p}fm2_checklist_template_snapshots` WHERE valid_from=? FOR UPDATE");$lookup->bind_param('s',$capturedAt);$lookup->execute();$existing=$lookup->get_result()->fetch_assoc();
            if(is_array($existing)){if(!hash_equals((string)$existing['content_sha256'],$hash))throw new RuntimeException('CHECKLIST_TEMPLATE_CAPTURE_CONFLICT');$this->db->commit();return['snapshotId'=>(int)$existing['id'],'created'=>false];}
            $version=LegacyChecklistTemplateSnapshot::VERSION;$scope='active_baseline_and_future_native_only';$source='legacy_fmonitor_current_at_cutover';
            $insert=$this->db->prepare("INSERT INTO `{$p}fm2_checklist_template_snapshots`(snapshot_version,captured_at,valid_from,validity_scope,source_label,content_sha256,payload_json,created_at) VALUES(?,?,?,?,?,?,?,?)");$insert->bind_param('ssssssss',$version,$capturedAt,$capturedAt,$scope,$source,$hash,$json,$createdAt);$insert->execute();$id=(int)$insert->insert_id;$this->db->commit();return['snapshotId'=>$id,'created'=>true];
        }catch(Throwable$e){$this->db->rollback();throw$e;}
    }
}
