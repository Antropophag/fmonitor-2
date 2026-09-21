<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class MariaDbObjectDetailsEditPermissionProvisioning
{
    public static function apply(\mysqli $db,string $prefix):array
    {
        $db->query("INSERT IGNORE INTO `{$prefix}fm2_pilot_role_permissions`(role_id,permission) SELECT role_id,'objects.details.edit' FROM `{$prefix}fm2_pilot_roles` WHERE BINARY code IN('fkr_operator','manager')");
        return ['permission'=>'objects.details.edit'];
    }
}
