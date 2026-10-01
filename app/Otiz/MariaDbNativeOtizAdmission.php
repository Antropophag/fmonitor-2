<?php
declare(strict_types=1);

namespace FMonitor2\Otiz;

use FMonitor2\InstallationProcess\YiiObjectCardApplicationIntegrity;
use yii\db\Connection;

/** Read-only admission derived from the append-only native order/checklist evidence. */
final class MariaDbNativeOtizAdmission
{
    public function __construct(private Connection $db, private string $prefix) {}

    public function read(int $objectId): array
    {
        $owned = $this->db->getTransaction() === null;
        $tx = $owned ? $this->db->beginTransaction() : null;
        try {
            $result = $this->readLocked($objectId);
            if ($tx !== null) $tx->commit();
            return $result;
        } catch (\Throwable) {
            if ($tx !== null && $tx->isActive) $tx->rollBack();
            return $this->unknown('NATIVE_EVIDENCE_UNPROVEN');
        }
    }

    private function readLocked(int $objectId): array
    {
        $cases = $this->db->createCommand("SELECT id,process_state FROM `{$this->prefix}fm2_installation_cases` WHERE legacy_installation_object_id=:o AND process_state IN('working','completed') FOR UPDATE", [':o'=>$objectId])->queryAll();
        if (count($cases) !== 1) return $this->unknown('NATIVE_CASE_UNPROVEN');
        $caseId = (int)$cases[0]['id'];
        $revision = $this->db->createCommand("SELECT revision_no,updated_at FROM `{$this->prefix}fm2_checklist_revisions` WHERE installation_case_id=:c FOR UPDATE", [':c'=>$caseId])->queryOne();
        if ($revision === false) return $this->unknown('NATIVE_CHECKLIST_UNPROVEN');

        $templateRows = $this->db->createCommand("SELECT a.template_snapshot_id,a.template_snapshot_version,a.template_content_sha256,a.effective_at,t.snapshot_version,t.content_sha256,t.payload_json FROM `{$this->prefix}fm2_checklist_template_associations` a JOIN `{$this->prefix}fm2_checklist_template_snapshots` t ON t.id=a.template_snapshot_id WHERE a.subject_kind='operational_case' AND a.subject_id=:c FOR UPDATE", [':c'=>(string)$caseId])->queryAll();
        if (count($templateRows) !== 1) return $this->unknown('NATIVE_TEMPLATE_UNPROVEN');
        $template = $templateRows[0];
        if ((string)$template['template_snapshot_version'] !== (string)$template['snapshot_version'] || !hash_equals((string)$template['template_content_sha256'], (string)$template['content_sha256']) || !hash_equals((string)$template['content_sha256'], hash('sha256', (string)$template['payload_json']))) return $this->unknown('NATIVE_TEMPLATE_UNPROVEN');
        $templatePayload=json_decode((string)$template['payload_json'],true,512,JSON_THROW_ON_ERROR);$defined=[];
        foreach($templatePayload['definitions']??[]as$definition)$defined[(int)$definition['id']]=true;
        foreach($templatePayload['sections']??[]as$section)foreach($section['items']??[]as$item)$defined[(int)$item['id']]=true;
        if($defined===[])return $this->unknown('NATIVE_TEMPLATE_UNPROVEN');

        $applications = $this->db->createCommand("SELECT * FROM `{$this->prefix}fm2_assignment_order_applications` WHERE installation_case_id=:c AND object_id=:o ORDER BY application_sequence FOR UPDATE", [':c'=>$caseId,':o'=>$objectId])->queryAll();
        if ($applications === []) return $this->unknown('NATIVE_COMPOSITION_UNPROVEN');
        $validated = [];
        foreach ($applications as $application) {
            $selected = json_decode((string)$application['selected_snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($selected) || array_keys($selected) !== ['selectedInstallers','selectedEngineer']) return $this->unknown('NATIVE_COMPOSITION_UNPROVEN');
            YiiObjectCardApplicationIntegrity::validateComposition($application, $selected);
            if (!$this->originalValid($application)) return $this->unknown('NATIVE_ORIGINAL_UNPROVEN');
            $roster = array_map(static fn(array $i): string => (string)(int)$i['tabId'], $selected['selectedInstallers']);
            sort($roster, SORT_STRING);
            $validated[] = ['at'=>$this->instant((string)$application['applied_at_utc']), 'row'=>$application, 'roster'=>$roster];
        }

        $operations = $this->db->createCommand("SELECT * FROM `{$this->prefix}fm2_checklist_operations` WHERE installation_case_id=:c AND operation_type IN('item_completed','item_installers_changed','completion_retracted') ORDER BY accepted_revision,id FOR UPDATE", [':c'=>$caseId])->queryAll();
        if ($operations === []) return $this->unknown('NATIVE_WORK_UNPROVEN');
        $ids = array_column($operations, 'client_operation_id');
        $params=[];$marks=[];foreach($ids as$i=>$id){$key=':i'.$i;$marks[]=$key;$params[$key]=$id;}
        $installerRows = $this->db->createCommand("SELECT * FROM `{$this->prefix}fm2_checklist_operation_installers` WHERE client_operation_id IN(".implode(',',$marks).") ORDER BY BINARY client_operation_id,installer_tab_id FOR UPDATE", $params)->queryAll();
        $installers=[];foreach($installerRows as$row)$installers[(string)$row['client_operation_id']][]=$row;

        $active=[];$anomalies=[];$observed=$this->instant((string)$validated[array_key_last($validated)]['row']['applied_at_utc']);
        $previousRevision=0;
        foreach ($operations as $operation) {
            $opId=(string)$operation['client_operation_id'];$observed=max($observed,$this->instant((string)$operation['server_received_at']));
            $acceptedRevision=(int)$operation['accepted_revision'];$sequenceProblem=$acceptedRevision<1||$acceptedRevision<=$previousRevision||$acceptedRevision>(int)$revision['revision_no']?'OPERATION_REVISION_UNPROVEN':null;$previousRevision=$acceptedRevision;
            $problem=$sequenceProblem??$this->operationProblem($operation,$installers[$opId]??[],$validated,$template,$defined);
            if($problem!==null)$anomalies[]=['id'=>$opId,'revision'=>(int)$operation['accepted_revision'],'problem'=>$problem];
            $type=(string)$operation['operation_type'];$item=(int)$operation['item_id'];
            if($type==='item_completed')$active[$item]=['completion'=>$opId,'attribution'=>$opId,'structural'=>$this->structural($problem),'problem'=>$problem];
            elseif($type==='item_installers_changed'&&isset($active[$item])){$active[$item]['attribution']=$opId;$active[$item]['problem']=$active[$item]['structural']??$problem;}
            elseif($type==='completion_retracted'){$payload=$this->payload($operation);$original=(string)($payload['originalClientOperationId']??'');if(isset($active[$item])&&$active[$item]['completion']===$original)unset($active[$item]);}
        }
        $decision='allow';$reason=null;
        foreach($active as$state){if($state['problem']===null)continue;if(str_starts_with($state['problem'],'OUTSIDE_ROSTER')){$decision='blocked';$reason='COMPOSITION_MISMATCH';break;}$decision='unknown';$reason='NATIVE_ATTRIBUTION_UNPROVEN';}
        $latest=$validated[array_key_last($validated)]['row'];
        $authority=['applicationId'=>(int)$latest['application_id'],'applicationSequence'=>(int)$latest['application_sequence'],'originalRevisionId'=>(string)$latest['original_revision_id'],'compositionIdentity'=>(string)$latest['composition_identity'],'compositionSha256'=>(string)$latest['composition_sha256'],'templateId'=>(int)$template['template_snapshot_id'],'templateVersion'=>(string)$template['template_snapshot_version'],'templateSha256'=>(string)$template['template_content_sha256'],'anomalies'=>$anomalies];
        return ['decision'=>$decision,'sourceRevision'=>hash('sha256',json_encode($authority,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)),'incidentId'=>null,'observedAt'=>$observed,'generation'=>0,'reasonCode'=>$reason];
    }

    private function originalValid(array $application): bool
    {
        $row=$this->db->createCommand("SELECT r.root_original_id,r.composition_identity,r.composition_sha256,v.revision_number FROM `{$this->prefix}fm2_assignment_order_original_roots` r JOIN `{$this->prefix}fm2_assignment_order_original_revisions` v ON v.root_original_id=r.root_original_id AND v.revision_id=:revision WHERE r.installation_case_id=:c AND r.assignment_order_id=:o FOR UPDATE",[':revision'=>$application['original_revision_id'],':c'=>$application['installation_case_id'],':o'=>$application['assignment_order_id']])->queryOne();
        return $row!==false&&(string)$row['composition_identity']===(string)$application['composition_identity']&&hash_equals((string)$row['composition_sha256'],(string)$application['composition_sha256'])&&(int)$row['revision_number']===(int)$application['original_revision_number'];
    }

    private function operationProblem(array $operation,array $rows,array $applications,array $template,array $defined): ?string
    {
        if((int)$operation['template_snapshot_id']!==(int)$template['template_snapshot_id']||(string)$operation['template_snapshot_version']!==(string)$template['template_snapshot_version']||!hash_equals((string)$operation['template_content_sha256'],(string)$template['template_content_sha256']))return'TEMPLATE_MISMATCH';
        $type=(string)$operation['operation_type'];if(!in_array($type,['item_completed','item_installers_changed'],true))return null;
        if(!isset($defined[(int)$operation['item_id']]))return'CHECKLIST_ITEM_UNPROVEN';
        $tabs=[];foreach($rows as$row){$source=(string)($row['assignment_source']??'');if(($type==='item_completed'&&$source!=='completion')||($type==='item_installers_changed'&&$source!=='correction'))return'ATTRIBUTION_SOURCE_UNPROVEN';$tabs[]=(string)(int)$row['installer_tab_id'];}
        sort($tabs,SORT_STRING);if($tabs===[]||count($tabs)!==count(array_unique($tabs)))return'ATTRIBUTION_MISSING';
        $payload=$this->payload($operation);$declared=$type==='item_installers_changed'?($payload['installerTabIds']??null):($payload['installerTabIds']??null);
        if(!is_array($declared))return'ATTRIBUTION_PAYLOAD_UNPROVEN';$declared=array_map(static fn($v):string=>(string)(int)$v,$declared);sort($declared,SORT_STRING);if($declared!==$tabs)return'ATTRIBUTION_PAYLOAD_MISMATCH';
        $receipt=$this->instant((string)$operation['server_received_at']);$matches=array_values(array_filter($applications,static fn(array$a):bool=>$a['at']<=$receipt));if($matches===[])return'APPLICATION_AT_RECEIPT_UNPROVEN';$roster=$matches[array_key_last($matches)]['roster'];
        foreach($tabs as$tab)if(!in_array($tab,$roster,true))return'OUTSIDE_ROSTER:'.$tab;
        return null;
    }

    private function payload(array $operation): array { try{$v=json_decode((string)$operation['payload_json'],true,512,JSON_THROW_ON_ERROR);return is_array($v)?$v:[];}catch(\Throwable){return[];} }
    private function structural(?string $problem): ?string { return $problem!==null&&(str_starts_with($problem,'TEMPLATE_')||str_starts_with($problem,'CHECKLIST_ITEM_')||str_starts_with($problem,'OPERATION_'))?$problem:null; }
    private function instant(string $value): string { return (new \DateTimeImmutable($value,new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z'); }
    private function unknown(string $reason): array { return ['decision'=>'unknown','sourceRevision'=>'missing','incidentId'=>null,'observedAt'=>null,'generation'=>0,'reasonCode'=>$reason]; }
}
