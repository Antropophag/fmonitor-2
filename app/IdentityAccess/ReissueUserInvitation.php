<?php

declare(strict_types=1);

namespace FMonitor2\IdentityAccess;

interface ReissueUserInvitation
{
    /** @return array{status: 'issued'|'access_denied'|'not_invited', token?: string} */
    public function reissue(int $actorId, int $userId): array;
}
