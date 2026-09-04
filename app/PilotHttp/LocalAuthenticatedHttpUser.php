<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

final readonly class LocalAuthenticatedHttpUser
{
    public static function resolve(ProductionPilotHttpDependencies $dependencies,PilotHttpRequest $request,string $legacyPrincipal,string $requiredPermission):?HttpUser
    {
        $trustedActor=$request->server['FMONITOR_AUTH_USER_ID']??null;
        if(!\is_string($trustedActor))return$dependencies->users()->resolveActiveUser($legacyPrincipal);
        if(!PilotE2ECoordinator::positive($trustedActor))return null;
        [$db,$prefix]=$dependencies->commandResources();
        $profile=(new MariaDbLocalUserProfile($db,$prefix))->read((int)$trustedActor);
        if($profile===null||!$dependencies->hasCapability($profile->id,$requiredPermission))return null;
        return new HttpUser($profile->id,$profile->displayName,$profile->email,[$requiredPermission]);
    }
}
