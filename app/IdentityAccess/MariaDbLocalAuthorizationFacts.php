<?php

declare(strict_types=1);

namespace FMonitor2\IdentityAccess;

final class MariaDbLocalAuthorizationFacts implements LocalAuthorizationFacts
{
    private ?\mysqli $connection = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $database,
        private readonly string $user,
        private readonly string $password,
        private readonly string $tablePrefix,
    ) {
    }

    public function readExactGrant(int $actorUserId, string $requiredPermission): LocalAuthorizationFactsResult
    {
        if (!$this->configurationIsValid()) return LocalAuthorizationFactsResult::configurationInvalid();

        try {
            $db = $this->connection();
            $users = $this->tablePrefix . 'fm2_pilot_users';
            $assignments = $this->tablePrefix . 'fm2_pilot_user_roles';
            $roles = $this->tablePrefix . 'fm2_pilot_roles';
            $permissions = $this->tablePrefix . 'fm2_pilot_role_permissions';
            $statement = $db->prepare(
                "SELECT COUNT(*) AS identity_count,"
                . " COALESCE(MAX(u.status=1 AND BINARY u.activation_state=BINARY 'active' AND EXISTS("
                . "SELECT 1 FROM `{$assignments}` ur"
                . " JOIN `{$roles}` r ON r.role_id=ur.role_id AND r.status=1"
                . " JOIN `{$permissions}` rp ON rp.role_id=r.role_id"
                . " WHERE ur.user_id=u.user_id AND BINARY rp.permission=BINARY ?)),0) AS exact_grant"
                . " FROM `{$users}` u WHERE u.user_id=?"
            );
            $statement->bind_param('si', $requiredPermission, $actorUserId);
            $statement->execute();
            $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
            if (count($rows) !== 1) return LocalAuthorizationFactsResult::schemaInvalid();
            $identityCount = filter_var($rows[0]['identity_count'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
            $exactGrant = filter_var($rows[0]['exact_grant'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0,'max_range'=>1]]);
            if ($identityCount === false || $exactGrant === false || $identityCount > 1) {
                return LocalAuthorizationFactsResult::schemaInvalid();
            }
            return $identityCount === 1 && $exactGrant === 1
                ? LocalAuthorizationFactsResult::granted()
                : LocalAuthorizationFactsResult::denied();
        } catch (\mysqli_sql_exception $error) {
            return in_array($error->getCode(), [1054, 1064, 1146], true)
                ? LocalAuthorizationFactsResult::schemaInvalid()
                : LocalAuthorizationFactsResult::readFailed();
        } catch (\Throwable) {
            return LocalAuthorizationFactsResult::readFailed();
        }
    }

    private function configurationIsValid(): bool
    {
        return $this->host !== '' && $this->port >= 1 && $this->port <= 65535
            && $this->database !== '' && $this->user !== ''
            && strlen($this->tablePrefix) <= 32
            && preg_match('/^[A-Za-z0-9_]*$/D', $this->tablePrefix) === 1;
    }

    private function connection(): \mysqli
    {
        if ($this->connection !== null) return $this->connection;
        $connection = new \mysqli($this->host, $this->user, $this->password, $this->database, $this->port);
        if (!$connection->set_charset('utf8mb4')) throw new \RuntimeException();
        return $this->connection = $connection;
    }
}
