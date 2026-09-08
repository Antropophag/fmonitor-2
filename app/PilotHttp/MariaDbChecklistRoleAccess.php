<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
final readonly class MariaDbChecklistRoleAccess
{
    public function __construct(private \mysqli $db,private string $prefix) {}
    public function allowed(int $user):bool
    {
        $s=$this->db->prepare('SELECT u.user_id FROM `'.$this->prefix.'fm2_pilot_users` u JOIN `'.$this->prefix.'fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `'.$this->prefix."fm2_pilot_roles` r ON r.role_id=ur.role_id WHERE u.user_id=? AND u.status=1 AND u.activation_state='active' AND r.status=1 AND r.code IN ('construction_control_engineer','manager') LIMIT 1");
        $s->execute([$user]);try{return $s->get_result()->fetch_row()!==null;}finally{$s->close();}
    }
}
