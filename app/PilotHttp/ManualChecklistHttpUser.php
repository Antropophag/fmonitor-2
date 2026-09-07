<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
final class ManualChecklistHttpUser
{
    public static function resolve(ProductionPilotHttpDependencies $d,PilotHttpRequest $r,string $principal):?HttpUser
    {
        $id=FreshOrderFormInput::positive($r->server['FMONITOR_AUTH_USER_ID']??null);
        if($id===null)return LocalAuthenticatedHttpUser::resolve($d,$r,$principal,'inspection.item.complete');
        [$db,$prefix]=$d->commandResources();$profile=(new MariaDbLocalUserProfile($db,$prefix))->read($id);
        if($profile===null)return null;
        $permissions=AccessPolicy::forUser($db,$prefix,$id);
        if(!(new MariaDbChecklistRoleAccess($db,$prefix))->allowed($id)&&!\in_array('inspection.item.complete',$permissions,true)&&!\in_array('checklist.read',$permissions,true))return null;
        return new HttpUser($profile->id,$profile->displayName,$profile->email,$permissions);
    }
}
