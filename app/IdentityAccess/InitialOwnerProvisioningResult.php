<?php
declare(strict_types=1);
namespace FMonitor2\IdentityAccess;

final readonly class InitialOwnerProvisioningResult
{
    private function __construct(public string $status) {}
    public static function created(): self { return new self('created'); }
    public static function alreadyProvisioned(): self { return new self('already_provisioned'); }
    public static function identityNotEmpty(): self { return new self('identity_not_empty'); }
}
