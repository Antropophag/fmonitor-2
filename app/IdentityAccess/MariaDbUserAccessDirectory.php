<?php
declare(strict_types=1);
namespace FMonitor2\IdentityAccess;

use DomainException;

final class MariaDbUserAccessDirectory
{
    public function __construct(private readonly MariaDbUserAccessTransaction $context) {}

    public function read(int $actorId): array
    {
        if (!$this->context->authorized($actorId, 'access.administer')) throw new DomainException('ACCESS_DENIED');
        $db = $this->context->db;
        $users = $db->createCommand('SELECT user_id,full_name,email,phone,status,activation_state,source_updated_at FROM '.$this->context->table('fm2_pilot_users').' ORDER BY status DESC,full_name,user_id')->queryAll();
        $roles = $db->createCommand('SELECT r.role_id,r.code,r.name,r.status,r.source_updated_at,COUNT(ur.user_id) user_count FROM '.$this->context->table('fm2_pilot_roles').' r LEFT JOIN '.$this->context->table('fm2_pilot_user_roles').' ur ON ur.role_id=r.role_id GROUP BY r.role_id,r.code,r.name,r.status,r.source_updated_at ORDER BY r.status DESC,r.name,r.role_id')->queryAll();
        $members = $db->createCommand('SELECT ur.user_id,r.role_id,r.name,r.status FROM '.$this->context->table('fm2_pilot_user_roles').' ur JOIN '.$this->context->table('fm2_pilot_roles').' r ON r.role_id=ur.role_id ORDER BY r.name,r.role_id')->queryAll();
        $permissions = $db->createCommand('SELECT role_id,permission FROM '.$this->context->table('fm2_pilot_role_permissions').' ORDER BY role_id,permission')->queryAll();
        $invitations = $db->createCommand('SELECT user_id,MAX(expires_at>NOW(6)) valid FROM '.$this->context->table('fm2_pilot_invitations').' WHERE used_at IS NULL AND revoked_at IS NULL GROUP BY user_id')->queryAll();
        $byUser = $byRole = $validByUser = [];
        foreach ($members as $row) $byUser[(int) $row['user_id']][] = ['id'=>(int)$row['role_id'], 'name'=>(string)$row['name'], 'active'=>(int)$row['status'] === 1];
        foreach ($permissions as $row) $byRole[(int) $row['role_id']][] = (string) $row['permission'];
        foreach ($invitations as $row) $validByUser[(int) $row['user_id']] = (int) $row['valid'] === 1;
        $normalized = [];
        foreach ($users as $row) {
            $id = (int) $row['user_id'];
            $effective = [];
            foreach ($byUser[$id] ?? [] as $membership) if ($membership['active']) foreach ($byRole[$membership['id']] ?? [] as $permission) $effective[$permission] = true;
            $normalized[] = ['id'=>$id, 'name'=>(string)$row['full_name'], 'email'=>(string)$row['email'], 'phone'=>(string)$row['phone'], 'active'=>(int)$row['status'] === 1 && $row['activation_state'] === 'active', 'invited'=>(int)$row['status'] === 1 && $row['activation_state'] === 'invited', 'invitationValid'=>$validByUser[$id] ?? false, 'updatedAt'=>(string)$row['source_updated_at'], 'roles'=>$byUser[$id] ?? [], 'permissions'=>array_keys($effective)];
        }
        return ['users'=>$normalized, 'roles'=>array_map(static fn(array $row):array => ['id'=>(int)$row['role_id'], 'code'=>(string)$row['code'], 'name'=>(string)$row['name'], 'active'=>(int)$row['status'] === 1, 'userCount'=>(int)$row['user_count'], 'updatedAt'=>(string)$row['source_updated_at'], 'permissions'=>$byRole[(int)$row['role_id']] ?? []], $roles)];
    }
}
