<?php

declare(strict_types=1);

namespace FMonitor2\IdentityAccess;

final class InMemoryLocalAuthorizationFacts implements LocalAuthorizationFacts
{
    private array $facts = ['users'=>[], 'roles'=>[], 'assignments'=>[], 'permissions'=>[]];
    private ?string $nextFailure = null;
    private ?array $barrier = null;

    public function replace(array $facts): void
    {
        $this->facts = $facts;
        $this->barrier = null;
    }

    public function snapshot(): array { return $this->facts; }
    public function failNextRead(string $failure): void { $this->nextFailure = $failure; }

    public function replaceAtNextReadBarrier(string $barrier, array $facts): void
    {
        $this->barrier = [$barrier, $facts];
    }

    public function readExactGrant(int $actorUserId, string $requiredPermission): LocalAuthorizationFactsResult
    {
        if ($this->nextFailure !== null) {
            $failure = $this->nextFailure;
            $this->nextFailure = null;
            return $failure === 'schema'
                ? LocalAuthorizationFactsResult::schemaInvalid()
                : LocalAuthorizationFactsResult::readFailed();
        }

        // Capture one immutable invocation snapshot. The barrier may commit the
        // replacement for the next invocation, but never splices two snapshots.
        $snapshot = $this->facts;
        if ($this->barrier !== null && $this->barrier[0] === 'after_identity_before_grants') {
            $this->facts = $this->barrier[1];
            $this->barrier = null;
        }

        $users = $snapshot['users'] ?? [];
        if (array_is_list($users)) {
            $matching = array_values(array_filter($users, static fn(array $user): bool => ($user['userId'] ?? null) === $actorUserId));
            if (count($matching) > 1) return LocalAuthorizationFactsResult::schemaInvalid();
            $user = $matching[0] ?? null;
        } else {
            $user = $users[$actorUserId] ?? null;
        }
        if (!is_array($user) || ($user['status'] ?? null) !== 1 || ($user['activationState'] ?? null) !== 'active') {
            return LocalAuthorizationFactsResult::denied();
        }

        $roleIds = [];
        foreach ($snapshot['assignments'] ?? [] as $assignment) {
            if (($assignment[0] ?? null) === $actorUserId) $roleIds[] = $assignment[1] ?? null;
        }
        foreach ($snapshot['permissions'] ?? [] as $permission) {
            $roleId = $permission[0] ?? null;
            if (($permission[1] ?? null) === $requiredPermission
                && in_array($roleId, $roleIds, true)
                && (($snapshot['roles'][$roleId]['status'] ?? null) === 1)) {
                return LocalAuthorizationFactsResult::granted();
            }
        }
        return LocalAuthorizationFactsResult::denied();
    }
}
