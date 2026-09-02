<?php

declare(strict_types=1);

namespace FMonitor2\IdentityAccess;

final readonly class AuthorizationResult
{
    private function __construct(
        public string $status,
        public ?int $actorUserId = null,
        public ?string $category = null,
        public ?string $correlationId = null,
    ) {
    }

    public static function authorized(int $actorUserId): self
    {
        return new self('AUTHORIZED', $actorUserId);
    }

    public static function authenticationRequired(): self
    {
        return new self('AUTHENTICATION_REQUIRED');
    }

    public static function accessDenied(): self
    {
        return new self('ACCESS_DENIED');
    }

    public static function unavailable(string $category, string $correlationId): self
    {
        return new self('AUTHORIZATION_UNAVAILABLE', null, $category, $correlationId);
    }
}
