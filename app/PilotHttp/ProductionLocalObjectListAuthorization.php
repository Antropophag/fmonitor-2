<?php

declare(strict_types=1);

namespace FMonitor2\PilotHttp;

use FMonitor2\IdentityAccess\AuthorizationResult;
use FMonitor2\IdentityAccess\AuthorizeLocalActor;
use FMonitor2\IdentityAccess\MariaDbLocalAuthorizationFacts;

final class ProductionLocalObjectListAuthorization implements LocalObjectListAuthorization
{
    private ?MariaDbLocalAuthorizationFacts $facts = null;
    private ?AuthorizeLocalActor $authorization = null;
    private ?\mysqli $profileConnection = null;

    public function __construct(
        private readonly EnvironmentSource $environment,
        private readonly string $requiredPermission = 'objects.read',
    ) {}

    private function authorization(): AuthorizeLocalActor
    {
        if ($this->authorization !== null) return $this->authorization;
        $host = $this->value('FMONITOR_DB_HOST');
        $port = \filter_var($this->value('FMONITOR_DB_PORT'), FILTER_VALIDATE_INT) ?: 0;
        $database = $this->value('FMONITOR_DB_NAME');
        $user = $this->value('FMONITOR_DB_USER');
        $password = $this->value('FMONITOR_DB_PASSWORD');
        $prefix = $this->value('FMONITOR_PROCESS_TABLE_PREFIX', '');
        $this->facts = new MariaDbLocalAuthorizationFacts($host, (int) $port, $database, $user, $password, $prefix);
        return $this->authorization = new AuthorizeLocalActor($this->facts, static fn(): string => \bin2hex(\random_bytes(6)));
    }

    public function authorize(mixed $trustedActorId): AuthorizationResult
    {
        if ($trustedActorId === null) $trustedActorId = $this->environment->read('FMONITOR_AUTH_USER_ID');
        $actorId = \is_string($trustedActorId) && \preg_match('/^[1-9][0-9]*$/D', $trustedActorId) === 1
            ? \filter_var($trustedActorId, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]])
            : $trustedActorId;
        return $this->authorization()->authorizeLocalActor($actorId === false ? null : $actorId, $this->requiredPermission);
    }

    public function user(int $actorUserId): ?HttpUser
    {
        try {
            $profile = new MariaDbLocalUserProfile($this->profileConnection(), $this->value('FMONITOR_PROCESS_TABLE_PREFIX', ''));
            return $profile->read($actorUserId);
        } catch (\Throwable $error) {
            throw new PilotHttpInfrastructureUnavailable('', 0, $error);
        }
    }

    public function reportUnavailable(string $category, string $correlationId): void
    {
        if (!\in_array($category, ['AUTHORIZATION_CONFIGURATION_INVALID','AUTHORIZATION_SCHEMA_INVALID','AUTHORIZATION_READ_FAILED'], true)
            || \preg_match('/^[0-9a-f]{12}$/D', $correlationId) !== 1) return;
        \file_put_contents('php://stderr', "FMONITOR_AUTHORIZATION_UNAVAILABLE category={$category} correlation_id={$correlationId}\n");
    }

    private function profileConnection(): \mysqli
    {
        if ($this->profileConnection !== null) return $this->profileConnection;
        $port = \filter_var($this->value('FMONITOR_DB_PORT'), FILTER_VALIDATE_INT);
        if ($port === false) throw new \RuntimeException();
        $connection = new \mysqli($this->value('FMONITOR_DB_HOST'), $this->value('FMONITOR_DB_USER'), $this->value('FMONITOR_DB_PASSWORD'), $this->value('FMONITOR_DB_NAME'), $port);
        if (!$connection->set_charset('utf8mb4')) throw new \RuntimeException();
        return $this->profileConnection = $connection;
    }

    private function value(string $name, string $default = ''): string
    {
        $value = $this->environment->read($name);
        return \is_string($value) ? $value : $default;
    }
}
