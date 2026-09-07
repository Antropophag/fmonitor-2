<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
use FMonitor2\AssignmentOrderComposition as C;
foreach(['AssignmentOrderApplicationReadResult','AssignmentOrderApplicationReader','AssignmentOrderApplicationPayload','MariaDbAssignmentOrderApplicationSql','MariaDbAssignmentOrderApplicationReader','AssignmentOrderApplicationReaderFactory']as$file)require_once \dirname(__DIR__).'/AssignmentOrderComposition/'.$file.'.php';
final readonly class MariaDbOriginalHistoryAccess
{
    public function __construct(private \mysqli $db,private string $prefix){}
    public function allowed(int $actor,int $object,int $order):bool
    {
        $s=$this->db->prepare('SELECT DISTINCT r.code FROM `'.$this->prefix.'fm2_pilot_users` u JOIN `'.$this->prefix.'fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `'.$this->prefix.'fm2_pilot_roles` r ON r.role_id=ur.role_id JOIN `'.$this->prefix."fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id AND rp.permission='assignment_order.original.read' WHERE u.user_id=? AND u.status=1 AND u.activation_state='active' AND r.status=1 AND r.code IN('fkr_operator','manager','construction_control_engineer','otiz_specialist')");
        $s->execute([$actor]);$roles=\array_column($s->get_result()->fetch_all(MYSQLI_ASSOC),'code');$s->close();
        if(\array_intersect($roles,['fkr_operator','manager','otiz_specialist'])!==[])return true;
        if(!\in_array('construction_control_engineer',$roles,true))return false;
        $current=C\AssignmentOrderApplicationReaderFactory::create($this->db,$this->prefix)->readCurrent($object);
        return $current->status==='found'&&$current->value!==null
            &&(int)$current->value['application']['engineerUserId']===$actor;
    }
}
