<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Prefer autonomous FMonitor roles while preserving the approved v4 rollout contract. */
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

    public function actorCanPrepareAssignmentOrder(int $actorId): bool { return $this->has($actorId, 'assignment_order.prepare'); }
    public function actorCanConfirmOrderRegistration(int $actorId): bool { return $this->has($actorId, 'assignment_order.confirm_registration'); }
    public function actorCanOpenInstallation(int $actorId): bool { return $this->has($actorId, 'installation.open'); }

    /** @return array{id:int,name:string,email:string}|null */
    public function findActiveLegacyIdentity(string $principal): ?array
    {
        $users = $this->legacyTablePrefix . 'users'; $roles = $this->legacyTablePrefix . 'users_roles';
        $statement = $this->connection->prepare("SELECT u.id,u.name,u.email FROM `{$users}` u JOIN `{$roles}` r ON r.id=u.role_id WHERE BINARY u.email=BINARY ? AND u.status=1 AND r.status=1 LIMIT 2");
        $statement->bind_param('s', $principal); $statement->execute(); $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        if (\count($rows) !== 1) return null; $row = $rows[0];
        $id = \filter_var($row['id'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($id === false || \trim((string) $row['name']) === '' || $row['email'] !== $principal) return null;
        return ['id'=>(int) $id,'name'=>(string) $row['name'],'email'=>(string) $row['email']];
    }

    public function actorCanReadAssignmentOrderArtifact(int $actorId): bool
    {
        if ($this->usesLocalRoles()) return $this->hasLocalPermission($actorId, 'assignment_order_artifact.read');
        if ($actorId < 1) return false;
        $users = $this->legacyTablePrefix . 'users';
        $roles = $this->legacyTablePrefix . 'users_roles';
        $statement = $this->connection->prepare("SELECT 1 FROM `{$users}` u JOIN `{$roles}` r ON r.id=u.role_id WHERE u.id=? AND u.status=1 AND r.status=1 LIMIT 1");
        $statement->bind_param('i', $actorId); $statement->execute();
        return $statement->get_result()->fetch_row() !== null;
    }

    /** @return array{userId:int,fullName:string,position:string,active:true,role:string}|null */
    public function findEngineerSnapshot(int $userId): ?array
    {
        if ($userId < 1) return null;
        if ($this->usesLocalRoles()) {
            $p = $this->processTablePrefix;
            $statement = $this->connection->prepare("SELECT u.user_id,u.full_name FROM `{$p}fm2_pilot_users` u JOIN `{$p}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$p}fm2_pilot_roles` r ON r.role_id=ur.role_id WHERE u.user_id=? AND u.status=1 AND u.activation_state='active' AND r.status=1 AND r.code='construction_control_engineer' LIMIT 1");
            $statement->bind_param('i', $userId); $statement->execute(); $row = $statement->get_result()->fetch_assoc();
            if ($row === null) return null;
            return ['userId'=>(int)$row['user_id'],'fullName'=>(string)$row['full_name'],'position'=>'Инженер строительного контроля','active'=>true,'role'=>'construction_control_engineer'];
        }
        $p = $this->processTablePrefix; $users = $this->legacyTablePrefix . 'users'; $roles = $this->legacyTablePrefix . 'users_roles';
        $statement = $this->connection->prepare("SELECT u.id,u.name,c.position_snapshot FROM `{$users}` u JOIN `{$roles}` r ON r.id=u.role_id JOIN `{$p}fm2_process_user_capabilities` c ON c.user_id=u.id AND c.capability='construction_control_engineer' WHERE u.id=? AND u.status=1 AND r.status=1 LIMIT 1");
        $statement->bind_param('i', $userId); $statement->execute(); $row = $statement->get_result()->fetch_assoc(); $position = $row['position_snapshot'] ?? null;
        if ($row === null || !\is_string($position) || \trim($position) === '') return null;
        return ['userId'=>(int)$row['id'],'fullName'=>(string)$row['name'],'position'=>$position,'active'=>true,'role'=>'construction_control_engineer'];
    }

    private function has(int $userId, string $permission): bool
    {
        if ($this->usesLocalRoles()) return $this->hasLocalPermission($userId, $permission);
        if ($userId < 1) return false;
        $p = $this->processTablePrefix; $users = $this->legacyTablePrefix . 'users'; $roles = $this->legacyTablePrefix . 'users_roles';
        $statement = $this->connection->prepare("SELECT 1 FROM `{$users}` u JOIN `{$roles}` r ON r.id=u.role_id JOIN `{$p}fm2_process_user_capabilities` c ON c.user_id=u.id AND c.capability=? WHERE u.id=? AND u.status=1 AND r.status=1 LIMIT 1");
        $statement->bind_param('si', $permission, $userId); $statement->execute();
        return $statement->get_result()->fetch_row() !== null;
    }

    private function hasLocalPermission(int $userId, string $permission): bool
    {
        if ($userId < 1) return false; $p = $this->processTablePrefix;
        $statement = $this->connection->prepare("SELECT 1 FROM `{$p}fm2_pilot_users` u JOIN `{$p}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$p}fm2_pilot_roles` r ON r.role_id=ur.role_id JOIN `{$p}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id WHERE u.user_id=? AND u.status=1 AND u.activation_state='active' AND r.status=1 AND rp.permission=? LIMIT 1");
        $statement->bind_param('is', $userId, $permission); $statement->execute();
        return $statement->get_result()->fetch_row() !== null;
    }

    private function usesLocalRoles(): bool { return $this->tableExists($this->processTablePrefix . 'fm2_pilot_users'); }
    private function tableExists(string $tableName): bool
    {
        $statement = $this->connection->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1');
        $statement->bind_param('s', $tableName); $statement->execute();
        return $statement->get_result()->fetch_row() !== null;
    }
}
