<?php

declare(strict_types=1);

namespace FMonitor2\IdentityAccess;

interface LocalAuthorizationFacts
{
    public function readExactGrant(int $actorUserId, string $requiredPermission): LocalAuthorizationFactsResult;
}
