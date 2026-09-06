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
                $workers=$s->rows('SELECT * FROM '.$s->table('fm2_workforce_catalog').' ORDER BY installer_tab_id');$installers=[];
                $today=(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format('Y-m-d');
                foreach($workers as $w){
                    if(!in_array($w['employment_status'],['employed','dismissed'],true)||!SelectionScalar::date($w['employed_from'])
                        ||($w['employed_to']!==null&&!SelectionScalar::date($w['employed_to']))||!SelectionScalar::sourceInstant($w['workforce_source_updated_at'])
                        ||!SelectionScalar::text($w['fio'],300)||!SelectionScalar::text($w['position'],300)||!SelectionScalar::text($w['workforce_source'],80))throw new \RuntimeException();
                    if($w['employment_status']!=='employed'||$w['employed_from']>$today||($w['employed_to']!==null&&$w['employed_to']<$today))continue;
                    $installers[]=['tabId'=>MariaDbSelectionSql::number($w['installer_tab_id']),'fullName'=>$w['fio'],'position'=>$w['position'],
                        'source'=>$w['workforce_source'],'updatedAt'=>$w['workforce_source_updated_at']];
                }
                $engineers=[];$directory=new I\MariaDbProcessUserDirectory($s->db,$s->prefix,$s->prefix);
                $ids=$s->rows('SELECT DISTINCT u.user_id FROM '.$s->table('fm2_pilot_users').' u JOIN '.$s->table('fm2_pilot_user_roles').' ur ON ur.user_id=u.user_id JOIN '.$s->table('fm2_pilot_roles')." r ON r.role_id=ur.role_id WHERE u.status=1 AND BINARY u.activation_state='active' AND r.status=1 AND BINARY r.code='construction_control_engineer' ORDER BY u.user_id");
                foreach($ids as $row){$e=$directory->findEngineerSnapshot(MariaDbSelectionSql::number($row['user_id']));if($e===null)throw new \RuntimeException();$engineers[]=$e;}
                return ['status'=>'found','reasonCode'=>null,'caseId'=>$id,'objectId'=>$objectId,'object'=>$object,
                    'selectionRevision'=>$latest?->selectionRevision??0,'latest'=>$summary,'installers'=>$installers,'engineers'=>$engineers,'lastTemplateDate'=>null];
            });
            if($result['status']==='found'&&$result['latest']!==null){
                $date=ProductionAssignmentOrderTemplateFactory::dateReader($this->sql->db,$this->sql->prefix)->find($result['caseId'],$result['latest']['orderId']);
                if($date['status']==='unavailable')throw new \RuntimeException();$result['lastTemplateDate']=$date['date'];
            }
            return $result;
        }catch(\Throwable){return ['status'=>'failed','reasonCode'=>'dependency_unavailable'];}
    }
}
