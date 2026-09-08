<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
use FMonitor2\AssignmentOrderComposition\ProductionAssignmentOrderTemplateFactory;

/** Read-only operator context; original command repeats its native authorization. */
final readonly class MariaDbOriginalSubmissionQuery implements AssignmentOrderOriginalSubmissionQuery
{
    public function __construct(private AssignmentOrderOriginalSql $sql,private string $prefix) {}
    public function resolveSubmissionContext(int $actorId,int $objectId,int $orderId,string $mode):array
    { return $this->read($actorId,$objectId,$orderId,$mode,false); }
    public function readSubmissionForm(int $actorId,int $objectId,int $orderId):array
    { return $this->read($actorId,$objectId,$orderId,null,true); }
    private function read(int $actor,int $object,int $order,?string $mode,bool $form):array
    {
        try {
            if($actor<1||$object<1||$order<1||($mode!==null&&!in_array($mode,['initial','correction'],true)))return self::error('NOT_FOUND');
            $result=$this->sql->snapshot(function()use($actor,$object,$order,$mode,$form){
                if($form){if(!$this->allowed($actor,'read'))return self::error('ACCESS_DENIED');}
                elseif(!$this->allowed($actor,$mode==='initial'?'upload':'correct'))return self::error('ACCESS_DENIED');
                $context=(new MariaDbOriginalSubmissionSource($this->sql))->read($object,$order);
                if($context===null)return self::error('NOT_FOUND');
                $current=$context['current'];$action=$current===null?'initial':'correction';
                if($form&&!$this->allowed($actor,$action==='initial'?'upload':'correct'))return self::error('ACCESS_DENIED');
                return ['status'=>'found','reasonCode'=>null]+$context+['mode'=>$form?$action:$mode];
            });
            if($form&&$result['status']==='found'){
                if($result['current']!==null)$date=$result['current']['documentDate'];
                else{
                    $lookup=ProductionAssignmentOrderTemplateFactory::dateReader($this->sql->db,$this->prefix)->find($result['caseId'],$order);
                    if($lookup['status']==='unavailable')throw new \RuntimeException();
                    $date=$lookup['date']??(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format('Y-m-d');
                }
                $result['suggestedDocumentDate']=$date;
            }
            return $result;
        }catch(\Throwable){return self::error('SERVICE_UNAVAILABLE');}
    }
    private function allowed(int $actor,string $action):bool
    {
        $s=$this->sql;
        $rows=$s->rows('SELECT u.user_id FROM '.$s->table('fm2_pilot_users').' u JOIN '.$s->table('fm2_pilot_user_roles').' ur ON ur.user_id=u.user_id JOIN '
            .$s->table('fm2_pilot_roles').' r ON r.role_id=ur.role_id JOIN '.$s->table('fm2_pilot_role_permissions')." rp ON rp.role_id=r.role_id WHERE u.user_id=$actor AND u.status=1 AND BINARY u.activation_state='active' AND r.status=1 AND BINARY r.code IN ('fkr_operator','manager') AND BINARY rp.permission=BINARY ".$s->quote('assignment_order.original.'.$action));
        return $rows!==[];
    }
    private static function error(string $code):array { return ['status'=>'error','reasonCode'=>$code]; }
}
