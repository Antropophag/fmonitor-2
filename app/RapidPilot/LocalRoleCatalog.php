<?php
declare(strict_types=1);
namespace FMonitor2\RapidPilot;
require_once dirname(__DIR__) . '/IdentityAccess/LocalRoleCatalog.php';

/** @deprecated Compatibility alias; IdentityAccess owns the role policy. */
final class LocalRoleCatalog
{
    public static function roles(): array
    {
        return \FMonitor2\IdentityAccess\LocalRoleCatalog::roles();
    }
}
