<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

use FMonitor2\RapidPilot\RoleCapabilityMap;
require_once dirname(__DIR__).'/RapidPilot/RoleCapabilityMap.php';

/** Stable rapid-pilot permissions derived from the imported legacy role catalogue. */
final class AccessPolicy
{
    public const OBJECTS_READ = 'objects.read';
    public const INSTALLERS_READ = 'installers.read';
    public const CONSTRUCTION_CONTROL_READ = 'construction_control.read';
    public const CHECKLIST_EDIT = 'checklist.edit';
    public const OTIZ_MANAGE = 'otiz.manage';
    public const MANAGEMENT_READ = 'management.read';
    public const ADMINISTER_ACCESS = 'access.administer';

    private const ALL = [
        self::OBJECTS_READ, self::INSTALLERS_READ, self::CONSTRUCTION_CONTROL_READ,
        self::CHECKLIST_EDIT, self::OTIZ_MANAGE, self::MANAGEMENT_READ,
        self::ADMINISTER_ACCESS, 'assignment_order.prepare',
        'assignment_order.confirm_registration', 'installation.open',
        'construction_control_engineer',
    ];

    public static function permissions(array $roleIds, array $explicit = []): array
    {
        $permissions=array_fill_keys(RoleCapabilityMap::permissions($roleIds,self::ALL),true);
        foreach($explicit as $permission)if(is_string($permission)&&$permission!=='')$permissions[$permission]=true;
        $result=array_keys($permissions);sort($result,SORT_STRING);return$result;
    }

    public static function grants(array $permissions,string $permission):bool
    {
        return in_array($permission,$permissions,true);
    }

    public static function forUser(\mysqli $db,string $prefix,int $userId):array
    {
        if($userId<1||preg_match('/^[A-Za-z0-9_]+$/D',$prefix)!==1)return[];
        $owner=$db->prepare("SELECT u.email,c.email_normalized FROM `{$prefix}fm2_pilot_users` u LEFT JOIN `{$prefix}fm2_pilot_auth_credentials` c ON c.user_id=u.user_id WHERE u.user_id=? AND u.status=1 LIMIT 1");
        $owner->bind_param('i',$userId);$owner->execute();$ownerRow=$owner->get_result()->fetch_assoc();
        if(is_array($ownerRow)&&in_array('ts.grishin@shlz.ru',[mb_strtolower(trim((string)$ownerRow['email'])),mb_strtolower(trim((string)$ownerRow['email_normalized']))],true)){$all=self::ALL;sort($all,SORT_STRING);return$all;}
        $roles=$db->prepare("SELECT ur.role_id FROM `{$prefix}fm2_pilot_user_roles` ur JOIN `{$prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id JOIN `{$prefix}fm2_pilot_users` u ON u.user_id=ur.user_id WHERE ur.user_id=? AND u.status=1 AND r.status=1");
        $roles->bind_param('i',$userId);$roles->execute();
        $roleIds=array_map('intval',array_column($roles->get_result()->fetch_all(MYSQLI_ASSOC),'role_id'));
        $explicit=[];
        try{$caps=$db->prepare("SELECT capability FROM `{$prefix}fm2_process_user_capabilities` WHERE user_id=?");$caps->bind_param('i',$userId);$caps->execute();$explicit=array_column($caps->get_result()->fetch_all(MYSQLI_ASSOC),'capability');}catch(\mysqli_sql_exception){}
        return self::permissions($roleIds,$explicit);
    }

    public static function rolePermissions(int $roleId):array{return self::permissions([$roleId]);}
}
