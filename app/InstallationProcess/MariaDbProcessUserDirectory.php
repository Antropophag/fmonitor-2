<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Process authorization backed exclusively by autonomous FMonitor roles. */
final class MariaDbProcessUserDirectory
{
    public function __construct(private readonly \mysqli $connection,private readonly string $processTablePrefix='',private readonly string $legacyTablePrefix=''){MariaDbSchemaInspector::validateTablePrefix($this->processTablePrefix);}
    public function actorCanPrepareAssignmentOrder(int $actorId):bool{return $this->has($actorId,'assignment_order.prepare');}
    public function actorCanConfirmOrderRegistration(int $actorId):bool{return $this->has($actorId,'assignment_order.confirm_registration');}
    public function actorCanOpenInstallation(int $actorId):bool{return $this->has($actorId,'installation.open');}
    public function actorCanReadAssignmentOrderArtifact(int $actorId):bool{return $this->has($actorId,'assignment_order_artifact.read');}
    public function findEngineerSnapshot(int $userId):?array
    {
        if($userId<1)return null;$p=$this->processTablePrefix;
        $s=$this->connection->prepare("SELECT u.user_id,u.full_name FROM `{$p}fm2_pilot_users` u JOIN `{$p}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$p}fm2_pilot_roles` r ON r.role_id=ur.role_id WHERE u.user_id=? AND u.status=1 AND u.activation_state='active' AND r.status=1 AND r.code='construction_control_engineer' LIMIT 1");$s->bind_param('i',$userId);$s->execute();$row=$s->get_result()->fetch_assoc();if($row===null)return null;
        return ['userId'=>(int)$row['user_id'],'fullName'=>(string)$row['full_name'],'position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer'];
    }
    private function has(int $userId,string $permission):bool
    {
        if($userId<1)return false;$p=$this->processTablePrefix;$s=$this->connection->prepare("SELECT 1 FROM `{$p}fm2_pilot_users` u JOIN `{$p}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$p}fm2_pilot_roles` r ON r.role_id=ur.role_id JOIN `{$p}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id WHERE u.user_id=? AND u.status=1 AND u.activation_state='active' AND r.status=1 AND rp.permission=? LIMIT 1");$s->bind_param('is',$userId,$permission);$s->execute();return $s->get_result()->fetch_row()!==null;
    }
}
