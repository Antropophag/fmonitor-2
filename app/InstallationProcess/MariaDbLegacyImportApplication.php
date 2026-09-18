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

    /** @param array{objects:list<array<string,mixed>>,engineers:list<array<string,mixed>>,template:array<string,mixed>} $snapshot */
    public function import(array $snapshot, string $cutoff): array
    {
        $this->db->begin_transaction();
        try {
            [$engineers, $engineerCounts] = $this->engineers($snapshot['engineers'], $cutoff);
            [$templateId, $templateCreated] = $this->template($snapshot['template'], $cutoff);
            $counts = ['eligible'=>count($snapshot['objects']),'imported'=>0,'alreadyPresent'=>0,'details'=>0,'templateAssociations'=>0] + $engineerCounts + ['objectsLinked'=>0,'objectsUnassigned'=>0];
            foreach ($snapshot['objects'] as $row) {
                $caseId = $this->object($row, $cutoff, $templateId, $snapshot['template'], $counts);
                $legacyId = (int)($row['responsstroicontrol'] ?? 0);
                if ($legacyId === 0) { $counts['objectsUnassigned']++; continue; }
                $this->assignment($caseId, (int)$row['id'], $legacyId, $engineers[$legacyId], $cutoff);
                $counts['objectsLinked']++;
            }
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

    private function object(array $row,string $cutoff,int $templateId,array $template,array &$counts): int
    {
        $id=(int)$row['id'];$mirrorFields=['ordadr_address','entrance','regnumber','zavnumber','workdatestart','workdatestartadjusted','workdateendadjusted','plan_finish_date','workdatefinish','ptoactdate','responsstroicontrol','floors','weight','speed','pittype','pitmaterial','paired'];
        $mirror=$this->db->query("SELECT ".implode(',',$mirrorFields)." FROM `{$this->legacyPrefix}fm_maintable` WHERE id={$id} FOR UPDATE")->fetch_assoc();
        $expected=array_combine($mirrorFields,array_map(static fn($field)=>$row[$field]===null?null:(string)$row[$field],$mirrorFields));
        foreach (['workdatestart','workdatestartadjusted','workdateendadjusted','plan_finish_date','workdatefinish','ptoactdate'] as $field) {
            $expected[$field] = self::canonicalDate($expected[$field]);
            if ($mirror !== null) $mirror[$field] = self::canonicalDate($mirror[$field]);
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
        return $caseId;
    }

    private function engineers(array $rows, string $cutoff): array
    {
        $role = $this->db->query("SELECT role_id FROM `{$this->processPrefix}fm2_pilot_roles` WHERE BINARY code='construction_control_engineer' AND status=1 FOR UPDATE")->fetch_all(MYSQLI_ASSOC);
        if ($role === []) {
            $q=$this->db->prepare("INSERT INTO `{$this->processPrefix}fm2_pilot_roles`(code,name,description,status,source_updated_at)VALUES('construction_control_engineer','Инженер строительного контроля','Импортированная роль строительного контроля',1,?)");$q->execute([$cutoff]);
            $role=[['role_id'=>$this->db->insert_id]];
        }
        if (count($role) !== 1) throw new \DomainException('ENGINEER_ROLE_INVALID');
        $roleId = (int)$role[0]['role_id']; $mapped=[]; $created=0; $present=0;
        foreach ($rows as $row) {
            $legacy=(int)$row['id']; $name=trim((string)$row['name']); $email=mb_strtolower(trim((string)$row['email']));
            $q=$this->db->prepare("SELECT * FROM `{$this->processPrefix}fm2_legacy_identity_links` WHERE legacy_user_id=? AND superseded_by_link_id IS NULL FOR UPDATE");$q->execute([$legacy]);$links=$q->get_result()->fetch_all(MYSQLI_ASSOC);
            if(count($links)>1)throw new \DomainException('IDENTITY_LINK_AMBIGUOUS');
            if($links===[]){
                $q=$this->db->prepare("SELECT user_id FROM `{$this->processPrefix}fm2_pilot_users` WHERE email=? FOR UPDATE");$q->execute([$email]);if($q->get_result()->fetch_assoc()!==null)throw new \DomainException('IDENTITY_EMAIL_CONFLICT');
                $q=$this->db->prepare("INSERT INTO `{$this->processPrefix}fm2_pilot_users`(full_name,email,phone,status,activation_state,session_version,source_updated_at)VALUES(?,?,'',1,'pending_invitation',1,?)");$q->execute([$name,$email,$cutoff]);$local=(int)$this->db->insert_id;
                $q=$this->db->prepare("INSERT INTO `{$this->processPrefix}fm2_pilot_user_roles`(user_id,role_id,origin,assigned_at,assigned_by_user_id)VALUES(?,?,'legacy_import',?,NULL)");$q->execute([$local,$roleId,$cutoff]);
                $q=$this->db->prepare("INSERT INTO `{$this->processPrefix}fm2_process_user_capabilities`(user_id,capability,position_snapshot)VALUES(?,'construction_control_engineer','Инженер строительного контроля')");$q->execute([$local]);
                $request=self::uuid("identity:{$legacy}");$fingerprint=hash('sha256',json_encode([$legacy,$name,$email,(int)$row['status'],(int)$row['role_id'],(int)$row['role_status']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
                $stamp=self::mysqlTime($cutoff);$q=$this->db->prepare("INSERT INTO `{$this->processPrefix}fm2_legacy_identity_links`(local_user_id,legacy_user_id,legacy_name_snapshot,legacy_email_snapshot,legacy_status_snapshot,legacy_role_id_snapshot,legacy_role_status_snapshot,linked_by_user_id,linked_at_utc,request_id,request_fingerprint)VALUES(?,?,?,?,?,?,?,?,?,?,?)");$q->execute([$local,$legacy,$name,$email,(int)$row['status'],(int)$row['role_id'],(int)$row['role_status'],$local,$stamp,$request,$fingerprint]);$created++;
            } else {
                $link=$links[0];$local=(int)$link['local_user_id'];
                if((int)$link['legacy_status_snapshot']!==(int)$row['status']||(int)$link['legacy_role_id_snapshot']!==(int)$row['role_id']||(int)$link['legacy_role_status_snapshot']!==(int)$row['role_status']||(string)$link['legacy_name_snapshot']!==$name||mb_strtolower((string)$link['legacy_email_snapshot'])!==$email)throw new \DomainException('IDENTITY_LINK_CONFLICT');
                $q=$this->db->prepare("SELECT user_id FROM `{$this->processPrefix}fm2_pilot_users` WHERE user_id=? AND BINARY email=BINARY ? AND full_name=? AND status=1 FOR UPDATE");$q->execute([$local,$email,$name]);if(count($q->get_result()->fetch_all())!==1)throw new \DomainException('IDENTITY_LOCAL_CONFLICT');$present++;
            }
            $mapped[$legacy]=$local;
        }
        return [$mapped,['engineersReferenced'=>count($rows),'engineersCreated'=>$created,'engineersAlreadyPresent'=>$present]];
    }

    private function assignment(int $caseId,int $objectId,int $legacyId,int $localId,string $cutoff):void
    {
        $q=$this->db->prepare("SELECT * FROM `{$this->processPrefix}fm2_control_engineer_assignments` WHERE installation_case_id=? FOR UPDATE");$q->execute([$caseId]);$rows=$q->get_result()->fetch_all(MYSQLI_ASSOC);
        if($rows!==[]){if(count($rows)!==1||(int)$rows[0]['engineer_user_id']!==$localId||(string)$rows[0]['assignment_source']!=='legacy_fmonitor'||(int)$rows[0]['source_legacy_object_id']!==$objectId||(int)$rows[0]['source_legacy_user_id']!==$legacyId)throw new \DomainException('ASSIGNMENT_CONFLICT');return;}
        $name=$this->db->query("SELECT full_name FROM `{$this->processPrefix}fm2_pilot_users` WHERE user_id={$localId}")->fetch_column();$operation=self::uuid('legacy-import:'.$cutoff);$request=self::uuid("assignment:{$objectId}:{$legacyId}");$fingerprint=hash('sha256',json_encode([$operation,$objectId,$legacyId,$localId,$localId],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));$stamp=self::mysqlTime($cutoff);
        $q=$this->db->prepare("INSERT INTO `{$this->processPrefix}fm2_control_engineer_assignments`(installation_case_id,object_id,assignment_sequence,engineer_user_id,engineer_fio_snapshot,engineer_position_snapshot,assigned_by_user_id,assigned_at_utc,request_id,request_fingerprint,assignment_source,source_operation_id,source_legacy_object_id,source_legacy_user_id)VALUES(?,?,?,?,?,'Инженер строительного контроля',?,?,?,?, 'legacy_fmonitor',?,?,?)");$q->execute([$caseId,$objectId,1,$localId,$name,$localId,$stamp,$request,$fingerprint,$operation,$objectId,$legacyId]);
    }

    private static function uuid(string $material):string{$h=hash('sha256',$material);return substr($h,0,8).'-'.substr($h,8,4).'-4'.substr($h,13,3).'-8'.substr($h,17,3).'-'.substr($h,20,12);}
    private static function mysqlTime(string $value):string{return str_replace('T',' ',substr($value,0,19));}

    private static function canonicalDate(mixed $raw): ?string
    {
        if ($raw === null) return null;
        $value = trim((string) $raw);
        if ($value === '' || preg_match('/^0+$/D', $value) === 1 || str_starts_with($value, '0000-00-00')) return null;
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}):(\d{2}))?$/D', $value, $parts) !== 1
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
            || (isset($parts[4]) && ((int) $parts[4] > 23 || (int) $parts[5] > 59 || (int) $parts[6] > 59))) {
            throw new \DomainException('MIRROR_DATE_INVALID');
        }
        return $parts[1] . '-' . $parts[2] . '-' . $parts[3];
    }
}
