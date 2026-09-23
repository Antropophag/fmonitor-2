<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
use FMonitor2\InstallationProcess as I;

/** Authorized read model only; commands and their audit remain with approved owners. */
final readonly class MariaDbSelectionPortalQuery implements AssignmentOrderSelectionPortalQuery
{
    public function __construct(private MariaDbSelectionSql $sql) {}
    public function authorizeActor(int $actor):array
    {
        if($actor<1)return ['status'=>'rejected','reasonCode'=>'authorization_denied'];
        $a=(new MariaDbSelectionFacts($this->sql))->authorize(new UserId($actor),SelectionCapability::SELECT);
        return match($a->status){SelectionAuthorizationStatus::ALLOWED=>['status'=>'allowed','reasonCode'=>null],
            SelectionAuthorizationStatus::DENIED=>['status'=>'rejected','reasonCode'=>'authorization_denied'],
            default=>['status'=>'failed','reasonCode'=>'dependency_unavailable']};
    }
    public function readSelectionPortal(int $objectId,int $actorId):array
    {
        $a=$this->authorizeActor($actorId);if($a['status']!=='allowed')return $a;
        if($objectId<1)return ['status'=>'rejected','reasonCode'=>'object_not_found'];
        try {
            $result=$this->sql->snapshot(function()use($objectId){
                $s=$this->sql;$case=(new MariaDbSelectionFacts($s))->caseRows($objectId);
                if($case->status===SelectionLookupStatus::NOT_FOUND)return ['status'=>'rejected','reasonCode'=>'object_not_found'];
                if($case->status!==SelectionLookupStatus::FOUND||$case->payload===null)throw new \RuntimeException();
                $id=$case->payload->caseId;$state=(new MariaDbSelectionState($s))->read($id);
                if($state->status!==SelectionLookupStatus::FOUND||$state->payload===null)throw new \RuntimeException();
                $latest=$state->payload->latestSelection;$summary=null;
                if($latest!==null){
                    $source=(new MariaDbTemplateSource($s))->load($id,$latest->assignmentOrderId);if($source===null)throw new \RuntimeException();
                    $h=$source['header'];$summary=['orderId'=>$latest->assignmentOrderId,'version'=>$latest->orderVersion,
                        'installers'=>array_map(static fn($m)=>['tabId'=>(int)$m['installer_tab_id'],'fullName'=>$m['fio_snapshot'],'position'=>$m['position_snapshot']],$source['members']),
                        'engineer'=>['userId'=>(int)$h['control_engineer_user_id'],'fullName'=>$h['control_engineer_fio_snapshot'],'position'=>$h['control_engineer_position_snapshot']],
                        'hasAcceptedOriginal'=>$latest->hasAcceptedOriginal];
                }
                $object=(new I\MariaDbLegacyInstallationObject($s->db,$s->prefix))->getInstallationObjectSnapshot($objectId);
                $installers=$summary['installers']??[];
                $engineers=[];$directory=new I\MariaDbProcessUserDirectory($s->db,$s->prefix,$s->prefix);
                $ids=$s->rows('SELECT DISTINCT u.user_id FROM '.$s->table('fm2_pilot_users').' u JOIN '.$s->table('fm2_pilot_user_roles').' ur ON ur.user_id=u.user_id JOIN '.$s->table('fm2_pilot_roles')." r ON r.role_id=ur.role_id WHERE u.status=1 AND BINARY u.activation_state='active' AND r.status=1 AND BINARY r.code='construction_control_engineer' ORDER BY u.user_id");
                foreach($ids as $row){$e=$directory->findEngineerSnapshot(MariaDbSelectionSql::number($row['user_id']));if($e===null)throw new \RuntimeException();$engineers[]=$e;}
                return ['status'=>'found','reasonCode'=>null,'caseId'=>$id,'objectId'=>$objectId,'object'=>$object,
                    'selectionRevision'=>$latest?->selectionRevision??0,'latest'=>$summary,'installers'=>$installers,'engineers'=>$engineers,'lastTemplateDate'=>null];
            });
            if($result['status']==='found'){$assignment=(new I\MariaDbControlEngineerAssignmentReader($this->sql->db,$this->sql->prefix))->read($objectId);if($assignment['status']==='unavailable')throw new \RuntimeException();$result['currentAssignment']=$assignment;}
            if($result['status']==='found'&&$result['latest']!==null){
                $date=ProductionAssignmentOrderTemplateFactory::dateReader($this->sql->db,$this->sql->prefix)->find($result['caseId'],$result['latest']['orderId']);
                if($date['status']==='unavailable')throw new \RuntimeException();$result['lastTemplateDate']=$date['date'];
            }
            return $result;
        }catch(\Throwable){return ['status'=>'failed','reasonCode'=>'dependency_unavailable'];}
    }
    public function searchEligibleInstallers(int $actorId,string $query,int $page):array
    {
        $a=$this->authorizeActor($actorId);if($a['status']!=='allowed')return $a;
        $query=trim($query);if(mb_strlen($query)<2||mb_strlen($query)>120||$page<1)return ['status'=>'rejected','reasonCode'=>'invalid_query'];
        try{$offset=($page-1)*20;if($offset>1000000)return ['status'=>'rejected','reasonCode'=>'invalid_query'];$numeric=ctype_digit($query)?ltrim($query,'0'):'';if($numeric==='')$numeric=$query;$today=(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format('Y-m-d');$catalog=$this->sql->table('fm2_workforce_catalog');$runs=$this->sql->table('fm2_workforce_sync_runs');$meta=$this->sql->table('fm2_workforce_sync_metadata');
            $rows=$this->sql->rows("SELECT w.* FROM $catalog w WHERE w.employment_status='employed' AND w.reconciliation_state='delivered' AND (LOCATE(LOWER(?),LOWER(w.fio))>0 OR LOCATE(?,CAST(w.installer_tab_id AS CHAR))>0) AND (w.employed_from IS NOT NULL AND w.employed_from<=? OR w.employed_from IS NULL AND w.authority_system='1c_zup' AND w.delivery_system='bitrix24' AND w.delivery_person_id>0 AND w.last_successful_sync_run_id REGEXP '^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$' AND EXISTS(SELECT 1 FROM $runs r JOIN $meta m ON m.singleton_id=1 AND m.last_successful_run_id=r.run_id AND m.last_successful_at=r.observed_at WHERE r.run_id=w.last_successful_sync_run_id AND r.status='completed' AND r.failure_code IS NULL AND r.observed_at=w.last_successful_sync_at AND r.page_count>0 AND r.delivered_count>0 AND r.normalized_checksum REGEXP '^[0-9a-f]{64}$')) AND (w.employed_to IS NULL OR w.employed_to>=?) ORDER BY w.fio,w.installer_tab_id LIMIT 21 OFFSET $offset",[$query,$numeric,$today,$today]);$items=[];
            foreach($rows as$w){if(($w['employed_from']!==null&&!SelectionScalar::date($w['employed_from']))||($w['employed_to']!==null&&!SelectionScalar::date($w['employed_to']))||!SelectionScalar::sourceInstant($w['workforce_source_updated_at'])||!SelectionScalar::text($w['fio'],300)||!SelectionScalar::text($w['position'],300)||!SelectionScalar::text($w['workforce_source'],80))throw new \RuntimeException();$proof=$w['employed_from']===null?$this->fullProof($this->sql,$w):null;$snapshot=new InstallerSnapshot((int)$w['installer_tab_id'],$w['fio'],$w['position'],$w['employment_status'],$w['employed_from'],$w['employed_to'],$w['workforce_source'],$w['workforce_source_updated_at'],$w['authority_system']??null,$w['delivery_system']??null,isset($w['delivery_person_id'])?(int)$w['delivery_person_id']:null,$w['reconciliation_state']??null,$proof);if(($w['employed_from']!==null&&$w['employed_from']>$today)||($w['employed_to']!==null&&$w['employed_to']<$today)||($w['employed_from']===null&&!SelectionEmploymentProof::full($snapshot)))continue;$items[]=['tabId'=>MariaDbSelectionSql::number($w['installer_tab_id']),'fullName'=>$w['fio'],'position'=>$w['position'],'source'=>$w['workforce_source'],'updatedAt'=>$w['workforce_source_updated_at']];}
            $more=count($items)>20;return ['status'=>'found','reasonCode'=>null,'items'=>array_slice($items,0,20),'page'=>$page,'hasMore'=>$more];
        }catch(\Throwable){return ['status'=>'failed','reasonCode'=>'dependency_unavailable'];}
    }
    public function currentInstallerAssignments(array $installerTabIds):array
    {
        $tabIds=array_values(array_unique(array_filter(array_map('intval',$installerTabIds),static fn(int$id):bool=>$id>0)));
        if($tabIds===[])return[];

        $ids=implode(',',$tabIds);$p=$this->sql->prefix;
        $completion="NOT (EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` pto WHERE pto.installation_case_id=c.id AND pto.fact_type='pto_act') AND EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` declaration WHERE declaration.installation_case_id=c.id AND declaration.fact_type='declaration'))";
        $day=$this->sql->db->real_escape_string((new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format('Y-m-d'));
        $legacy=$this->sql->rows("SELECT oi.installer_tab_id,c.legacy_installation_object_id object_id,CONCAT(o.registration_number,' · ',l.regnumber) registration_number,l.ordadr_address address FROM `{$p}fm2_order_installers` oi JOIN `{$p}fm2_assignment_orders` o ON o.id=oi.assignment_order_id JOIN `{$p}fm2_installation_cases` c ON c.id=o.installation_case_id JOIN `{$p}fm_maintable` l ON l.id=c.legacy_installation_object_id WHERE oi.installer_tab_id IN ({$ids}) AND o.status='registered' AND o.version_no=(SELECT MAX(x.version_no) FROM `{$p}fm2_assignment_orders` x WHERE x.installation_case_id=o.installation_case_id) AND oi.change_action<>'release' AND oi.valid_from<='{$day}' AND (oi.valid_to IS NULL OR oi.valid_to>='{$day}') AND NOT EXISTS(SELECT 1 FROM `{$p}fm2_assignment_order_applications` x WHERE x.installation_case_id=o.installation_case_id) AND {$completion} ORDER BY oi.installer_tab_id,c.legacy_installation_object_id");

        $wantedSql=implode(' UNION ALL ',array_map(static fn(int$id):string=>"SELECT {$id} tab_id",$tabIds));
        $native=$this->sql->rows("SELECT wanted.tab_id,a.selected_snapshot_json,c.legacy_installation_object_id object_id,l.regnumber registration_number,l.ordadr_address address FROM ({$wantedSql}) wanted JOIN `{$p}fm2_assignment_order_applications` a ON JSON_CONTAINS(a.selected_snapshot_json,CONCAT('{\"tabId\":',wanted.tab_id,'}'),'$.selectedInstallers')=1 JOIN `{$p}fm2_installation_cases` c ON c.id=a.installation_case_id JOIN `{$p}fm_maintable` l ON l.id=c.legacy_installation_object_id WHERE a.application_sequence=(SELECT MAX(x.application_sequence) FROM `{$p}fm2_assignment_order_applications` x WHERE x.installation_case_id=a.installation_case_id) AND {$completion} ORDER BY wanted.tab_id,c.legacy_installation_object_id");

        $out=[];$add=static function(array&$target,int$tabId,array$row):void{$objectId=(int)$row['object_id'];if($tabId<1||$objectId<1||trim((string)$row['registration_number'])===''||trim((string)$row['address'])==='')throw new \RuntimeException();$target[$tabId][$objectId]=['objectId'=>$objectId,'registrationNumber'=>(string)$row['registration_number'],'address'=>(string)$row['address']];};
        foreach($legacy as$row)$add($out,(int)$row['installer_tab_id'],$row);
        $wanted=array_fill_keys($tabIds,true);
        foreach($native as$row){$snapshot=json_decode((string)$row['selected_snapshot_json'],true,512,JSON_THROW_ON_ERROR);if(!is_array($snapshot)||!isset($snapshot['selectedInstallers'])||!is_array($snapshot['selectedInstallers']))throw new \RuntimeException();$tabId=(int)$row['tab_id'];$selected=false;foreach($snapshot['selectedInstallers']as$installer){if(!is_array($installer)||!isset($installer['tabId'])||filter_var($installer['tabId'],FILTER_VALIDATE_INT)===false)throw new \RuntimeException();if((int)$installer['tabId']===$tabId)$selected=true;}if(!$selected||!isset($wanted[$tabId]))throw new \RuntimeException();$add($out,$tabId,$row);}
        foreach($out as&$rows){ksort($rows,SORT_NUMERIC);$rows=array_values($rows);}unset($rows);return$out;
    }
    private function fullProof(MariaDbSelectionSql$s,array$r):?array
    {if(!isset($r['last_successful_sync_run_id'],$r['last_successful_sync_at']))return null;$runs=$s->rows('SELECT * FROM '.$s->table('fm2_workforce_sync_runs').' WHERE run_id=?',[$r['last_successful_sync_run_id']]);$meta=$s->rows('SELECT * FROM '.$s->table('fm2_workforce_sync_metadata').' WHERE singleton_id=1');if(count($runs)!==1||count($meta)!==1)return null;$run=$runs[0];$m=$meta[0];if($run['status']!=='completed'||$run['failure_code']!==null||$run['observed_at']!==$r['last_successful_sync_at']||$m['last_successful_run_id']!==$r['last_successful_sync_run_id']||$m['last_successful_at']!==$r['last_successful_sync_at'])return null;return['runId'=>$run['run_id'],'observedAt'=>$run['observed_at'],'normalizedChecksum'=>$run['normalized_checksum'],'deliveredCount'=>(int)$run['delivered_count'],'pageCount'=>(int)$run['page_count']];}
}
