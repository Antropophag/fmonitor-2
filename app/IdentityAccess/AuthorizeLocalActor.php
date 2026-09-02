<?php

declare(strict_types=1);

namespace FMonitor2\IdentityAccess;

final readonly class AuthorizeLocalActor
{
    private const PERMISSIONS = [
        'objects.read',
        'installers.read',
        'assignment_order_artifact.read',
        'assignment_order.prepare',
        'assignment_order.confirm_registration',
        'installation.open',
        'construction_control.read',
        'checklist.read',
        'checklist.edit',
        'inspection.schedule',
        'otiz.manage',
        'management.read',
        'access.administer',
        'access.superadminister',
        'access.audit.read',
    ];

    /** @param callable(): string $correlationIds */
    public function __construct(
        private LocalAuthorizationFacts $facts,
        private mixed $correlationIds,
    ) {
    }

    public function authorizeLocalActor(mixed $authenticatedLocalUserId, string $requiredPermission): AuthorizationResult
    {
        if (!is_int($authenticatedLocalUserId) || $authenticatedLocalUserId < 1) {
            return AuthorizationResult::authenticationRequired();
        }
        if (!in_array($requiredPermission, self::PERMISSIONS, true)) {
            return $this->unavailable('AUTHORIZATION_CONFIGURATION_INVALID');
        }

        try {
            $facts = $this->facts->readExactGrant($authenticatedLocalUserId, $requiredPermission);
        } catch (\Throwable) {
            return $this->unavailable('AUTHORIZATION_READ_FAILED');
        }

        return match ($facts->status) {
            'GRANTED' => AuthorizationResult::authorized($authenticatedLocalUserId),
            'DENIED' => AuthorizationResult::accessDenied(),
            'CONFIGURATION_INVALID' => $this->unavailable('AUTHORIZATION_CONFIGURATION_INVALID'),
            'SCHEMA_INVALID' => $this->unavailable('AUTHORIZATION_SCHEMA_INVALID'),
            default => $this->unavailable('AUTHORIZATION_READ_FAILED'),
        };
    }

    private function unavailable(string $category): AuthorizationResult
    {
        try {
            $correlationId = ($this->correlationIds)();
        } catch (\Throwable) {
            $correlationId = '';
        }
        if (!is_string($correlationId) || preg_match('/^[0-9a-f]{12}$/D', $correlationId) !== 1) {
            $correlationId = bin2hex(random_bytes(6));
        }
        return AuthorizationResult::unavailable($category, $correlationId);
    }
}
