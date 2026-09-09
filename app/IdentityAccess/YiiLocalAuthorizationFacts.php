<?php
declare(strict_types=1);
namespace FMonitor2\IdentityAccess;
final readonly class YiiLocalAuthorizationFacts implements LocalAuthorizationFacts
{
    public function __construct(private MariaDbYiiLocalIdentityStore $store) {}
    public function readExactGrant(int $actorUserId,string $requiredPermission):LocalAuthorizationFactsResult
    {
        try{return $this->store->grants($actorUserId,$requiredPermission)?LocalAuthorizationFactsResult::granted():LocalAuthorizationFactsResult::denied();}
        catch(\yii\db\Exception){return LocalAuthorizationFactsResult::readFailed();}
        catch(\Throwable){return LocalAuthorizationFactsResult::configurationInvalid();}
    }
}
