<?php
declare(strict_types=1);

namespace FMonitor2\RapidPilot;

/** One source of truth for capabilities inherited from stable legacy role ids. */
final class RoleCapabilityMap
{
    private const MAP=[
        1=>['*'],2=>['objects.read','installers.read'],3=>['*'],
        5=>['objects.read','installers.read','assignment_order.prepare','assignment_order.confirm_registration','installation.open'],
        8=>['objects.read','installers.read','construction_control.read','checklist.edit','construction_control_engineer'],
        16=>['objects.read','installers.read','construction_control.read','checklist.edit','construction_control_engineer'],
        17=>['objects.read','installers.read','management.read'],18=>['objects.read','installers.read','management.read'],
        19=>['objects.read','installers.read','otiz.manage'],
        20=>['objects.read','installers.read','assignment_order.prepare','assignment_order.confirm_registration','installation.open'],
        21=>['objects.read','installers.read','otiz.manage'],22=>['objects.read','management.read'],
        23=>['objects.read','assignment_order.confirm_registration'],
        24=>['objects.read','installers.read','construction_control.read','checklist.edit'],
        25=>['objects.read','construction_control.read','checklist.edit'],26=>[],
        9001=>['objects.read','installers.read','otiz.manage'],
    ];

    public static function permissions(array $roleIds,array $all):array
    {
        $result=[];foreach($roleIds as$id)foreach(self::MAP[(int)$id]??[]as$permission)foreach($permission==='*'?$all:[$permission]as$item)$result[$item]=true;
        $permissions=array_keys($result);sort($permissions,SORT_STRING);return$permissions;
    }

    public static function roleIdsFor(string $capability):array
    {
        $ids=[];foreach(self::MAP as$id=>$permissions)if(in_array('*',$permissions,true)||in_array($capability,$permissions,true))$ids[]=$id;return$ids;
    }
}
