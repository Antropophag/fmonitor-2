<?php
declare(strict_types=1);
namespace FMonitor2\IdentityAccess;

use FMonitor2\Jobs\JobValues;
use FMonitor2\Jobs\WeeklyFkrRecipientDirectory;

final class MariaDbWeeklyFkrRecipientDirectory implements WeeklyFkrRecipientDirectory
{
    public function __construct(private \mysqli $db, private string $prefix)
    {
        JobValues::text($prefix, '/^[A-Za-z0-9_]{0,25}$/D');
    }

    public function eligibleIdentities(): array
    {
        $sql = "SELECT DISTINCT u.user_id FROM `{$this->prefix}fm2_pilot_users` u "
            ."JOIN `{$this->prefix}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id "
            ."JOIN `{$this->prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id AND r.status=1 "
            ."JOIN `{$this->prefix}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id "
            ."WHERE u.status=1 AND u.activation_state='active' AND u.email<>'' "
            ."AND BINARY r.code='manager' AND BINARY rp.permission='objects.read' ORDER BY u.user_id";
        return array_map(static fn(array $row): string => (string)$row['user_id'], $this->db->query($sql)->fetch_all(MYSQLI_ASSOC));
    }

    public function recipient(string $id): ?array
    {
        if (preg_match('/^[1-9]\d*$/D', $id) !== 1) return null;
        $sql = "SELECT u.email,u.status,u.activation_state,r.code role,"
            ."MAX(BINARY rp.permission='objects.read') can_read FROM `{$this->prefix}fm2_pilot_users` u "
            ."JOIN `{$this->prefix}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id "
            ."JOIN `{$this->prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id AND r.status=1 "
            ."LEFT JOIN `{$this->prefix}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id "
            ."WHERE u.user_id=? AND BINARY r.code='manager' GROUP BY u.user_id,u.email,u.status,u.activation_state,r.code";
        $statement = $this->db->prepare($sql); $userId = (int)$id; $statement->bind_param('i', $userId); $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        if ($row === null) return null;
        return ['active' => (int)$row['status'] === 1 && $row['activation_state'] === 'active',
            'role' => 'fkr_manager', 'permissions' => (int)$row['can_read'] === 1 ? ['objects.read'] : [], 'email' => $row['email']];
    }

}
