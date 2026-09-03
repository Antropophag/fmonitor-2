<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;

final class PilotSessionRequestOwner
{
    private static ?\FMonitor\IdentityAccess\PilotSessionStorage $owner=null;
    public static function bind(\FMonitor\IdentityAccess\PilotSessionStorage $owner):\FMonitor\IdentityAccess\PilotSessionStorage{return self::$owner??=$owner;}
    public static function native(EnvironmentSource$environment):\FMonitor\IdentityAccess\PilotSessionStorage{return self::$owner??=new LazyPilotSessionStorage($environment,new \FMonitor\IdentityAccess\NativePilotSessionFilesystem(),new \FMonitor\IdentityAccess\SystemPilotSessionClock(),new \FMonitor\IdentityAccess\CsprngPilotSessionEntropy(),new \FMonitor\IdentityAccess\NoOpPilotSessionLifecycleObserver());}
}
