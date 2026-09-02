<?php

declare(strict_types=1);

namespace FMonitor2\PilotHttp;

use FMonitor2\IdentityAccess\AuthorizationResult;

interface LocalObjectListAuthorization
{
    public function authorize(mixed $trustedActorId): AuthorizationResult;
    public function user(int $actorUserId): ?HttpUser;
    public function reportUnavailable(string $category, string $correlationId): void;
}
