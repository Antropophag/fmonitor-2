<?php
declare(strict_types=1);

namespace FMonitor2\Tests\Support;

/** Canonical fixture boundary for PILOT-OBJECT-READ-RBAC-FIXTURES-001. */
final class PilotObjectReadRbacFixture
{
    public static function install(\mysqli $db): void
    {
        LocalRbacFixture::install($db, [
            18 => ['email' => 'fkr.object-list@example.invalid', 'fullName' => 'Сотрудник ФКР (тест)', 'permissions' => ['objects.read']],
            19 => ['email' => 'inactive-activation@example.invalid', 'activation' => 'invited', 'permissions' => ['objects.read']],
            20 => ['email' => 'inactive-user@example.invalid', 'status' => 0, 'permissions' => ['objects.read']],
            21 => ['email' => 'inactive-role@example.invalid', 'roleActive' => 0, 'permissions' => ['objects.read']],
            22 => ['email' => 'missing-grant@example.invalid'],
            23 => ['email' => 'case-grant@example.invalid', 'permissions' => ['Objects.Read']],
            24 => ['email' => 'space-grant@example.invalid', 'permissions' => ['objects.read ']],
            25 => ['email' => 'wildcard-grant@example.invalid', 'permissions' => ['objects.*']],
            26 => ['email' => 'suffix-grant@example.invalid', 'permissions' => ['objects.read.more']],
            27 => ['email' => 'unassigned@example.invalid', 'permissions' => ['objects.read']],
        ]);
        $db->query('DELETE FROM fm2_pilot_user_roles WHERE user_id=18');
        $db->query('DELETE FROM fm2_pilot_role_permissions WHERE role_id=900018');
        $db->query('DELETE FROM fm2_pilot_roles WHERE role_id=900018');
        $db->query("INSERT INTO fm2_pilot_roles(role_id,code,name,description,status,source_updated_at) VALUES(5101,'object_list_reader','Object list reader','PILOT-OBJECT-READ-RBAC-FIXTURES-001',1,'2026-09-02T00:00:00+03:00')");
        $db->query("INSERT INTO fm2_pilot_user_roles(user_id,role_id,origin,assigned_at,assigned_by_user_id) VALUES(18,5101,'fixture','2026-09-02T00:00:00+03:00',NULL)");
        $db->query("INSERT INTO fm2_pilot_role_permissions(role_id,permission) VALUES(5101,'objects.read')");
        $db->query('DELETE FROM fm2_pilot_user_roles WHERE user_id=27');
    }
}
