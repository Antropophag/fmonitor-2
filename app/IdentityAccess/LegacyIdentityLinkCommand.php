<?php
declare(strict_types=1);
namespace FMonitor2\IdentityAccess;

final readonly class LegacyIdentityLinkCommand
{
    public function __construct(public string $requestId,public int $localUserId,public int $legacyUserId,public int $actorUserId,public ?string $correctionReason)
    {
        if(preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$requestId)!==1||$localUserId<1||$legacyUserId<1||$actorUserId<1)throw new \InvalidArgumentException();
    }
}
