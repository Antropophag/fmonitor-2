<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class MariaDbProcessUserDirectory
{
    public function __construct(
        private readonly \mysqli $connection,
        private readonly string $processTablePrefix = '',
        private readonly string $legacyTablePrefix = '',
    ) {
        MariaDbSchemaInspector::validateTablePrefix($this->processTablePrefix);
        MariaDbSchemaInspector::validateTablePrefix($this->legacyTablePrefix);
    }

    public function actorCanPrepareAssignmentOrder(int $actorId): bool
    {
        return $this->actorHasCapability($actorId, 'assignment_order.prepare');
    }

    public function actorCanConfirmOrderRegistration(int $actorId): bool
    {
        return $this->actorHasCapability($actorId, 'assignment_order.confirm_registration');
    }

    public function actorCanOpenInstallation(int $actorId): bool
    {
        return $this->actorHasCapability($actorId, 'installation.open');
    }

    public function findEngineerSnapshot(int $controlEngineerUserId): ?array
    {
        if ($controlEngineerUserId <= 0) {
            return null;
        }

        $users = $this->legacyTablePrefix . 'users';
        $roles = $this->legacyTablePrefix . 'users_roles';
        $capabilities = $this->processTablePrefix . 'fm2_process_user_capabilities';
        $statement = $this->connection->prepare(
            "SELECT u.id,u.name,c.position_snapshot FROM `{$users}` u "
            . "JOIN `{$roles}` r ON r.id=u.role_id "
            . "JOIN `{$capabilities}` c ON c.user_id=u.id AND c.capability='construction_control_engineer' "
            . 'WHERE u.id=? AND u.status=1 AND r.status=1 LIMIT 1',
        );
        $statement->bind_param('i', $controlEngineerUserId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        if ($row === null) {
            return null;
        }
        $position = $row['position_snapshot'];
        if (!is_string($position) || trim($position) === '') {
            return null;
        }

        return [
            'userId' => (int) $row['id'],
            'fullName' => $row['name'],
            'position' => $position,
            'active' => true,
            'role' => 'construction_control_engineer',
        ];
    }

    private function actorHasCapability(int $actorId, string $capability): bool
    {
        if ($actorId <= 0) return false;
        $users=$this->legacyTablePrefix.'users';$roles=$this->legacyTablePrefix.'users_roles';$capabilities=$this->processTablePrefix.'fm2_process_user_capabilities';
        $statement=$this->connection->prepare("SELECT 1 FROM `{$users}` u JOIN `{$roles}` r ON r.id=u.role_id JOIN `{$capabilities}` c ON c.user_id=u.id AND c.capability=? WHERE u.id=? AND u.status=1 AND r.status=1 LIMIT 1");
        $statement->bind_param('si',$capability,$actorId);$statement->execute();
        return $statement->get_result()->fetch_row()!==null;
    }
}
