<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

/** Autonomous rapid-pilot RBAC. Permissions are granted only through active local roles. */
final class AccessPolicy
{
    public const OBJECTS_READ = 'objects.read';
    public const INSTALLERS_READ = 'installers.read';
    public const CONSTRUCTION_CONTROL_READ = 'construction_control.read';
    public const CHECKLIST_EDIT = 'checklist.edit';
    public const OTIZ_MANAGE = 'otiz.manage';
    public const MANAGEMENT_READ = 'management.read';
    public const ADMINISTER_ACCESS = 'access.administer';

    public static function grants(array $permissions,string $permission):bool
    {
        return in_array($permission,$permissions,true);
    }

    public static function forUser(\mysqli $db,string $prefix,int $userId):array
    {
        if($userId<1||preg_match('/^[A-Za-z0-9_]+$/D',$prefix)!==1)return[];
        $statement=$db->prepare("SELECT DISTINCT rp.permission FROM `{$prefix}fm2_pilot_users` u JOIN `{$prefix}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id JOIN `{$prefix}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id WHERE u.user_id=? AND u.status=1 AND u.activation_state='active' AND r.status=1 ORDER BY rp.permission");
        $statement->bind_param('i',$userId);$statement->execute();
        return array_column($statement->get_result()->fetch_all(MYSQLI_ASSOC),'permission');
    }

    public static function rolePermissions(\mysqli $db,string $prefix,int $roleId):array
    {
        $statement=$db->prepare("SELECT permission FROM `{$prefix}fm2_pilot_role_permissions` WHERE role_id=? ORDER BY permission");
        $statement->bind_param('i',$roleId);$statement->execute();return array_column($statement->get_result()->fetch_all(MYSQLI_ASSOC),'permission');
    }
}
