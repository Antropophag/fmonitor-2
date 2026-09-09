<?php
declare(strict_types=1);

namespace FMonitor2\IdentityAccess;

use Yii;
use yii\web\IdentityInterface;

final readonly class YiiLocalIdentity implements IdentityInterface
{
    public function __construct(public int $id, public string $displayName, public string $email, private string $authKey) {}
    public static function findIdentity($id): ?self { return Yii::$app->localIdentity->findById($id); }
    public static function findIdentityByAccessToken($token, $type = null): ?self { return null; }
    public function getId(): int { return $this->id; }
    public function getAuthKey(): string { return $this->authKey; }
    public function validateAuthKey($authKey): bool { return is_string($authKey) && hash_equals($this->getAuthKey(), $authKey); }
}
