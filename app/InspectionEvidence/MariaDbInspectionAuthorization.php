<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

use FMonitor2\InstallationProcess\InspectionEvidenceSchemaMigration;

final class MariaDbInspectionAuthorization
{
    public function __construct(private readonly \mysqli $db, private readonly string $prefix)
    {
    }

    public function actor(int $id): ?array
    {
        $query = 'SELECT u.status,u.activation_state,p.permission FROM '.$this->table('fm2_pilot_users').' u'
            .' LEFT JOIN '.$this->table('fm2_pilot_user_roles').' ur ON ur.user_id=u.user_id'
            .' LEFT JOIN '.$this->table('fm2_pilot_roles').' r ON r.role_id=ur.role_id AND r.status=1'
            .' LEFT JOIN '.$this->table('fm2_pilot_role_permissions').' p ON p.role_id=r.role_id'
            .' WHERE u.user_id=?';
        $statement = $this->db->prepare($query);
        $statement->bind_param('i', $id);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        if ($rows === []) {
            return null;
        }

        return [
            'active' => (int) $rows[0]['status'] === 1 && $rows[0]['activation_state'] === 'active',
            'capabilities' => array_values(array_unique(array_filter(array_column($rows, 'permission')))),
        ];
    }

    public function schemaAvailable(): bool
    {
        return InspectionEvidenceSchemaMigration::isCompleteCompatible($this->db, $this->prefix);
    }

    private function table(string $name): string
    {
        return '`'.$this->prefix.$name.'`';
    }
}
