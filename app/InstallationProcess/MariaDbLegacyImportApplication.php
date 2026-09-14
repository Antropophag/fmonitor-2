<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class MariaDbLegacyImportApplication
{
    public function __construct(private readonly \mysqli $db, private readonly string $processPrefix, private readonly string $legacyPrefix)
    {
        MariaDbSchemaInspector::validateTablePrefix($processPrefix);
        MariaDbSchemaInspector::validateTablePrefix($legacyPrefix);
    }

    /** @param array{objects:list<array<string,mixed>>,template:array<string,mixed>} $snapshot */
    public function import(array $snapshot, string $cutoff): array
    {
        $this->db->begin_transaction();
        try {
            [$templateId, $templateCreated] = $this->template($snapshot['template'], $cutoff);
            $counts = ['eligible'=>count($snapshot['objects']),'imported'=>0,'alreadyPresent'=>0,'details'=>0,'templateAssociations'=>0];
            foreach ($snapshot['objects'] as $row) $this->object($row, $cutoff, $templateId, $snapshot['template'], $counts);
            $this->db->commit();
            return $counts;
        } catch (\Throwable $error) {
            try { $this->db->rollback(); } catch (\Throwable) {}
            throw $error;
        }
    }

    private function template(array $template, string $cutoff): array
    {
        $hash=(string)$template['contentSha256'];$payload=json_encode($template['payload'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $q=$this->db->prepare("SELECT id,content_sha256,payload_json FROM `{$this->processPrefix}fm2_checklist_template_snapshots` WHERE valid_from=? FOR UPDATE");$q->bind_param('s',$cutoff);$q->execute();$stored=$q->get_result()->fetch_assoc();
        if($stored!==null){if(!hash_equals($hash,(string)$stored['content_sha256'])||(string)$stored['payload_json']!==$payload)throw new \DomainException('TEMPLATE_CONFLICT');return[(int)$stored['id'],false];}
        $version='legacy-checklist-template-cutover-v1';$scope='active_baseline_and_future_native_only';$label='legacy_fmonitor_current_at_cutover';$now=gmdate('Y-m-d H:i:s');$insert=$this->db->prepare("INSERT INTO `{$this->processPrefix}fm2_checklist_template_snapshots`(snapshot_version,captured_at,valid_from,validity_scope,source_label,content_sha256,payload_json,created_at) VALUES(?,?,?,?,?,?,?,?)");$insert->bind_param('ssssssss',$version,$cutoff,$cutoff,$scope,$label,$hash,$payload,$now);$insert->execute();return[(int)$insert->insert_id,true];
    }

    private function object(array $row,string $cutoff,int $templateId,array $template,array &$counts): void
    {
        $id=(int)$row['id'];$mirrorFields=['ordadr_address','entrance','regnumber','workdatestart','workdatestartadjusted','workdateendadjusted','plan_finish_date','workdatefinish','ptoactdate','responsstroicontrol','floors','weight','speed','pittype','pitmaterial','paired'];
        $mirror=$this->db->query("SELECT ".implode(',',$mirrorFields)." FROM `{$this->legacyPrefix}fm_maintable` WHERE id={$id} FOR UPDATE")->fetch_assoc();
        $expected=array_combine($mirrorFields,array_map(static fn($field)=>$row[$field]===null?null:(string)$row[$field],$mirrorFields));
        if ($mirror !== null) foreach (['workdatestart','workdatestartadjusted','workdateendadjusted','plan_finish_date','workdatefinish','ptoactdate'] as $field) {
            if ($mirror[$field] !== null) $mirror[$field] = substr((string) $mirror[$field], 0, 10);
        }
        if($mirror===null){$columns=implode(',',$mirrorFields);$marks=implode(',',array_fill(0,count($mirrorFields)+1,'?'));$insert=$this->db->prepare("INSERT INTO `{$this->legacyPrefix}fm_maintable`(id,{$columns}) VALUES({$marks})");$values=array_values($expected);$insert->bind_param('i'.str_repeat('s',count($values)),$id,...$values);$insert->execute();}
        elseif($mirror!==$expected)throw new \DomainException('MIRROR_CONFLICT');

        $case=$this->db->query("SELECT id FROM `{$this->processPrefix}fm2_installation_cases` WHERE legacy_installation_object_id={$id} FOR UPDATE")->fetch_assoc();$created=false;
        if($case===null){$stamp=gmdate('Y-m-d\TH:i:sP');$insert=$this->db->prepare("INSERT INTO `{$this->processPrefix}fm2_installation_cases`(legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version) VALUES(?,'needs_assignment_order',NULL,NULL,NULL,?,?,1)");$insert->bind_param('iss',$id,$stamp,$stamp);$insert->execute();$caseId=(int)$insert->insert_id;$created=true;$counts['imported']++;}else{$caseId=(int)$case['id'];$counts['alreadyPresent']++;}

        $classification=$row['classification'];$json=json_encode($classification,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$hash=hash('sha256',$json);$reasons=json_encode($classification['reasonCodes'],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$version=(string)$classification['classificationVersion'];$category=(string)$classification['category'];$kind='operational_case';$proof=$this->db->prepare("SELECT legacy_object_id,source_cutoff_at,classification_version,category,reason_codes_json,classification_sha256 FROM `{$this->processPrefix}fm2_migration_classification_provenance` WHERE output_kind=? AND output_id=? FOR UPDATE");$proof->bind_param('si',$kind,$caseId);$proof->execute();$stored=$proof->get_result()->fetch_assoc();
        if($stored===null){$now=gmdate('Y-m-d H:i:s');$insert=$this->db->prepare("INSERT INTO `{$this->processPrefix}fm2_migration_classification_provenance`(output_kind,legacy_object_id,output_id,source_cutoff_at,classification_version,category,reason_codes_json,classification_sha256,created_at) VALUES(?,?,?,?,?,?,?,?,?)");$insert->bind_param('siissssss',$kind,$id,$caseId,$cutoff,$version,$category,$reasons,$hash,$now);$insert->execute();}
        elseif((int)$stored['legacy_object_id']!==$id||(string)$stored['source_cutoff_at']!==$cutoff||(string)$stored['classification_version']!==$version||(string)$stored['category']!==$category||(string)$stored['reason_codes_json']!==$reasons||!hash_equals($hash,(string)$stored['classification_sha256']))throw new \DomainException('PROVENANCE_CONFLICT');

        $technical=[];foreach(['floors','weight','speed','pittype','pitmaterial','paired']as$field)$technical[$field]=['raw'=>(string)$row[$field],'display'=>(string)$row[$field]];$material=['schemaVersion'=>'technical-object-detail-v1','objectId'=>$id,'fields'=>$technical];$detailJson=json_encode($material,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$detailHash=hash('sha256',$detailJson);$detail=$this->db->query("SELECT schema_version,content_sha256,payload_json FROM `{$this->processPrefix}fm2_pilot_object_details` WHERE object_id={$id} FOR UPDATE")->fetch_assoc();
        if($detail===null){$detailVersion='technical-object-detail-v1';$now=gmdate('Y-m-d H:i:s');$insert=$this->db->prepare("INSERT INTO `{$this->processPrefix}fm2_pilot_object_details`(object_id,schema_version,content_sha256,payload_json,captured_at) VALUES(?,?,?,?,?)");$insert->bind_param('issss',$id,$detailVersion,$detailHash,$detailJson,$now);$insert->execute();$counts['details']++;}
        elseif((string)$detail['schema_version']!=='technical-object-detail-v1'||!hash_equals($detailHash,(string)$detail['content_sha256'])||(string)$detail['payload_json']!==$detailJson)throw new \DomainException('DETAIL_CONFLICT');

        $subject='operational_case';$subjectId=(string)$caseId;$association=$this->db->prepare("SELECT effective_at,template_snapshot_id,template_snapshot_version,template_content_sha256 FROM `{$this->processPrefix}fm2_checklist_template_associations` WHERE subject_kind=? AND subject_id=? FOR UPDATE");$association->bind_param('ss',$subject,$subjectId);$association->execute();$storedAssociation=$association->get_result()->fetch_assoc();$snapshotVersion='legacy-checklist-template-cutover-v1';$templateHash=(string)$template['contentSha256'];
        if($storedAssociation===null){$associationVersion='checklist-template-association-v1';$now=gmdate('Y-m-d H:i:s');$insert=$this->db->prepare("INSERT INTO `{$this->processPrefix}fm2_checklist_template_associations`(association_version,subject_kind,subject_id,effective_at,template_snapshot_id,template_snapshot_version,template_content_sha256,created_at) VALUES(?,?,?,?,?,?,?,?)");$insert->bind_param('ssssisss',$associationVersion,$subject,$subjectId,$cutoff,$templateId,$snapshotVersion,$templateHash,$now);$insert->execute();$counts['templateAssociations']++;}
        elseif((string)$storedAssociation['effective_at']!==$cutoff||(int)$storedAssociation['template_snapshot_id']!==$templateId||(string)$storedAssociation['template_snapshot_version']!==$snapshotVersion||!hash_equals($templateHash,(string)$storedAssociation['template_content_sha256']))throw new \DomainException('ASSOCIATION_CONFLICT');
    }
}
