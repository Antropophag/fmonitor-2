<?php
declare(strict_types=1);

namespace FMonitor2\Tests\Support;

/**
 * Gate-2 fixture boundary for PILOT-OBJECT-READ-RBAC-FIXTURES-001.
 *
 * The temporary delegation deliberately exposes the pre-slice generic fixture.
 * The executable test therefore reaches the public HTTP seam before proving
 * that the required canonical role/manifest is still missing.
 */
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
        $db->query('DELETE FROM fm2_pilot_user_roles WHERE user_id=27');
    }
}
