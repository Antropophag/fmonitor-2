<?php
declare(strict_types=1);
namespace FMonitor2\IdentityAccess;
use yii\rbac\CheckAccessInterface;
final class YiiCanonicalAccessChecker extends \yii\base\Component implements CheckAccessInterface
{
    public MariaDbYiiLocalIdentityStore|string $store='localIdentity';
    public function init():void{parent::init();}
    public function checkAccess($userId,$permissionName,$params=[]):bool
    {
        if($params!==[]||$permissionName==='')throw new \yii\base\InvalidArgumentException('Unknown permission.');
        $store=$this->store instanceof MariaDbYiiLocalIdentityStore?$this->store:\Yii::$app->get($this->store);
        $authorization=(new AuthorizeLocalActor(new YiiLocalAuthorizationFacts($store),static fn():string=>bin2hex(random_bytes(6))))->authorizeLocalActor((int)$userId,$permissionName);
        return match($authorization->status){'AUTHORIZED'=>true,'ACCESS_DENIED','AUTHENTICATION_REQUIRED'=>false,default=>throw new \RuntimeException('Authorization unavailable.')};
    }
}
