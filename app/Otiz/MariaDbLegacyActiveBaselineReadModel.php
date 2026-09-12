<?php
declare(strict_types=1);

final class LegacyActiveBaselineReadModel
{
    public function __construct(private readonly mysqli $db,private readonly string $prefix)
    {
        if(preg_match('/^[A-Za-z0-9_]+$/D',$prefix)!==1)throw new InvalidArgumentException('Invalid local table prefix');
    }

    public function read(array$filters,int$page,int$pageSize=50):array
    {
        if($page<1||$pageSize<1||$pageSize>100)throw new InvalidArgumentException('Invalid active baseline page');
        foreach(['fm2_legacy_active_baselines','fm2_checklist_template_associations','fm2_checklist_template_snapshots']as$suffix)if(!$this->tableExists($suffix))return['summary'=>['total'=>0,'ready'=>0,'blocked'=>0,'quarantined'=>0],'rows'=>[],'total'=>0,'page'=>1,'pages'=>1];
        $ready=$this->readySql();$where=[];
        if(($filters['state']??'')==='ready')$where[]="({$ready})";elseif(($filters['state']??'')==='blocked')$where[]="NOT({$ready})";
        $coverage=(string)($filters['coverage']??'');$events="CAST(JSON_UNQUOTE(JSON_EXTRACT(b.payload_json,'$.legacyObject.checklist_event_count')) AS UNSIGNED)";$attrs="CAST(JSON_UNQUOTE(JSON_EXTRACT(b.payload_json,'$.legacyObject.attribution_count')) AS UNSIGNED)";
        if($coverage==='both')$where[]="{$events}>0 AND {$attrs}>0";elseif($coverage==='partial')$where[]="(({$events}>0)+({$attrs}>0))=1";elseif($coverage==='none')$where[]="{$events}=0 AND {$attrs}=0";
        $whereSql=$where===[]?'':' WHERE '.implode(' AND ',$where);$joins=$this->joins();
        $total=(int)$this->db->query("SELECT COUNT(*) n FROM `{$this->prefix}fm2_legacy_active_baselines` b {$joins}{$whereSql}")->fetch_assoc()['n'];$pages=max(1,(int)ceil($total/$pageSize));$page=min($page,$pages);$offset=($page-1)*$pageSize;
        $rows=$this->db->query("SELECT b.*,a.id association_id,a.effective_at association_effective_at,a.template_snapshot_id,a.template_snapshot_version association_template_version,a.template_content_sha256 association_template_hash,t.snapshot_version,t.captured_at template_captured_at,t.valid_from template_valid_from,t.validity_scope,t.source_label template_source_label,t.content_sha256 template_content_hash,{$ready} native_ready FROM `{$this->prefix}fm2_legacy_active_baselines` b {$joins}{$whereSql} ORDER BY b.legacy_object_id,b.id LIMIT {$pageSize} OFFSET {$offset}")->fetch_all(MYSQLI_ASSOC);
        foreach($rows as&$row)$row=$this->project($row);unset($row);
        $summary=$this->db->query("SELECT COUNT(*) total,SUM({$ready}) ready,SUM(NOT({$ready})) blocked,SUM(JSON_LENGTH(JSON_EXTRACT(b.payload_json,'$.classification.quarantineCodes'))>0) quarantined FROM `{$this->prefix}fm2_legacy_active_baselines` b {$joins}")->fetch_assoc();
        return['summary'=>array_map('intval',$summary),'rows'=>$rows,'total'=>$total,'page'=>$page,'pages'=>$pages];
    }

    private function project(array$row):array
    {
        $payload=json_decode((string)$row['payload_json'],true,flags:JSON_THROW_ON_ERROR);$classification=$payload['classification']??[];$object=$payload['legacyObject']??[];$conflicts=array_values(array_unique(array_map('strval',$classification['quarantineCodes']??[])));
        if($row['association_id']===null)$conflicts[]='TEMPLATE_ASSOCIATION_ABSENT';
        else{if((string)$row['association_effective_at']!==(string)$row['cutover_at'])$conflicts[]='TEMPLATE_EFFECTIVE_AT_MISMATCH';if($row['template_snapshot_id']===null)$conflicts[]='TEMPLATE_SNAPSHOT_ABSENT';elseif(!hash_equals((string)$row['association_template_hash'],(string)$row['template_content_hash']))$conflicts[]='TEMPLATE_HASH_MISMATCH';if((string)$row['template_valid_from']>(string)$row['cutover_at'])$conflicts[]='TEMPLATE_NOT_VALID_AT_CUTOVER';}
        sort($conflicts,SORT_STRING);return['baselineId'=>(int)$row['id'],'legacyObjectId'=>(int)$row['legacy_object_id'],'regnumber'=>(string)($object['regnumber']??''),'address'=>(string)($object['ordadr_address']??''),'entrance'=>(string)($object['entrance']??''),'contractVersion'=>(string)$row['contract_version'],'cutoverAt'=>(string)$row['cutover_at'],'baselineHash'=>(string)$row['content_sha256'],'classification'=>(string)($classification['category']??''),'classificationVersion'=>(string)($classification['classificationVersion']??''),'reasonCodes'=>array_values(array_map('strval',$classification['reasonCodes']??[])),'coverage'=>['checklist'=>(int)($object['checklist_event_count']??0),'attribution'=>(int)($object['attribution_count']??0)],'template'=>$row['association_id']===null?null:['associationId'=>(int)$row['association_id'],'effectiveAt'=>(string)$row['association_effective_at'],'snapshotId'=>(int)$row['template_snapshot_id'],'version'=>(string)$row['association_template_version'],'capturedAt'=>(string)$row['template_captured_at'],'validFrom'=>(string)$row['template_valid_from'],'validityScope'=>(string)$row['validity_scope'],'sourceLabel'=>(string)$row['template_source_label'],'contentHash'=>(string)$row['template_content_hash']],'conflictCodes'=>$conflicts,'nativeReady'=>(bool)$row['native_ready']];
    }

    private function joins():string{return"LEFT JOIN `{$this->prefix}fm2_checklist_template_associations` a ON a.subject_kind='legacy_active_baseline' AND a.subject_id=CAST(b.id AS CHAR) LEFT JOIN `{$this->prefix}fm2_checklist_template_snapshots` t ON t.id=a.template_snapshot_id";}
    private function readySql():string{return"(JSON_UNQUOTE(JSON_EXTRACT(b.payload_json,'$.classification.category'))='legacy_active' AND COALESCE(JSON_LENGTH(JSON_EXTRACT(b.payload_json,'$.classification.quarantineCodes')),0)=0 AND a.id IS NOT NULL AND a.effective_at=b.cutover_at AND t.id IS NOT NULL AND a.template_content_sha256=t.content_sha256 AND t.valid_from<=b.cutover_at AND t.validity_scope='active_baseline_and_future_native_only')";}
    private function tableExists(string$suffix):bool{$table=$this->db->real_escape_string($this->prefix.$suffix);return(int)$this->db->query("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'")->fetch_assoc()['n']>0;}
}
