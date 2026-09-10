<?php
declare(strict_types=1);
namespace FMonitor2\IdentityAccess;

final class MariaDbUserRoleChanges
{
    public function __construct(private readonly MariaDbUserAccessTransaction $context) {}
    public function change(int $actorId, int $userId, int $roleId, string $action): array
    {
        return $this->context->run(function () use ($actorId, $userId, $roleId, $action): array {
            if (!$this->context->authorized($actorId, 'access.administer')) return ['status'=>'access_denied'];
            if (!in_array($action, ['attach','detach'], true)) return ['status'=>'invalid'];
            $db = $this->context->db;
            $user = $db->createCommand('SELECT user_id FROM '.$this->context->table('fm2_pilot_users').' WHERE user_id=:user FOR UPDATE', [':user'=>$userId])->queryOne();
            $role = $db->createCommand('SELECT role_id,code,status FROM '.$this->context->table('fm2_pilot_roles').' WHERE role_id=:role', [':role'=>$roleId])->queryOne();
            if ($user === false || $role === false || ($action === 'attach' && (int)$role['status'] !== 1) || ($action === 'detach' && $role['code'] === 'user')) return ['status'=>'invalid'];
            if ($role['code'] === 'superadministrator' && !$this->context->authorized($actorId, 'access.superadminister')) return ['status'=>'access_denied'];
            $present = $db->createCommand('SELECT 1 FROM '.$this->context->table('fm2_pilot_user_roles').' WHERE user_id=:user AND role_id=:role FOR UPDATE', [':user'=>$userId, ':role'=>$roleId])->queryScalar() !== false;
            if (($action === 'attach' && $present) || ($action === 'detach' && !$present)) return ['status'=>'unchanged'];
            if ($action === 'detach' && $role['code'] === 'superadministrator' && $this->context->activeSupers() <= 1) return ['status'=>'invalid'];
            $now = $this->context->now();
            if ($action === 'attach') $db->createCommand()->insert($this->context->raw('fm2_pilot_user_roles'), ['user_id'=>$userId, 'role_id'=>$roleId, 'origin'=>'administrator', 'assigned_at'=>$now, 'assigned_by_user_id'=>$actorId])->execute();
            else $db->createCommand()->delete($this->context->raw('fm2_pilot_user_roles'), ['user_id'=>$userId, 'role_id'=>$roleId])->execute();
            $db->createCommand()->insert($this->context->raw('fm2_pilot_user_role_events'), ['user_id'=>$userId, 'role_id'=>$roleId, 'action'=>$action === 'attach' ? 'role_attached' : 'role_detached', 'occurred_at'=>$now, 'actor_user_id'=>$actorId])->execute();
            return ['status'=>'changed'];
        });
    }
}
