<?php
declare(strict_types=1);
namespace FMonitor2\IdentityAccess;
use yii\db\Expression;

final class MariaDbUserActivation
{
    public function __construct(private readonly MariaDbUserAccessTransaction $context) {}
    public function activate(string $token, string $password, string $confirmation): array
    {
        if (preg_match('/^[A-Za-z0-9_-]{43}$/D', $token) !== 1) return ['status'=>'invalid'];
        return $this->context->run(function () use ($token, $password, $confirmation): array {
            $db = $this->context->db;
            $row = $db->createCommand('SELECT i.id,i.user_id,u.email,u.status,u.activation_state,c.password_hash FROM '.$this->context->table('fm2_pilot_invitations').' i JOIN '.$this->context->table('fm2_pilot_users').' u ON u.user_id=i.user_id JOIN '.$this->context->table('fm2_pilot_auth_credentials').' c ON c.user_id=u.user_id WHERE i.token_hash=:hash AND i.used_at IS NULL AND i.revoked_at IS NULL AND i.expires_at>NOW(6) FOR UPDATE', [':hash'=>hash('sha256', $token, true)])->queryOne();
            if ($row === false || (int)$row['status'] !== 1 || $row['activation_state'] !== 'invited' || $row['password_hash'] !== null) return ['status'=>'invalid'];
            $error = $this->passwordError($password, $confirmation, (string)$row['email']);
            if ($error !== null) return ['status'=>'invalid_password', 'reason'=>$error];
            $hash = password_hash($password, PASSWORD_ARGON2ID);
            if (!is_string($hash)) throw new \RuntimeException('Password hashing unavailable.');
            $userId = (int)$row['user_id']; $now = $this->context->now();
            if ($db->createCommand()->update($this->context->raw('fm2_pilot_auth_credentials'), ['password_hash'=>$hash, 'password_set_at'=>$now, 'updated_at'=>$now], 'user_id=:user AND password_hash IS NULL', [':user'=>$userId])->execute() !== 1) throw new \RuntimeException('Credential state changed.');
            $db->createCommand()->update($this->context->raw('fm2_pilot_invitations'), ['used_at'=>new Expression('NOW(6)')], ['id'=>(int)$row['id']])->execute();
            $db->createCommand()->update($this->context->raw('fm2_pilot_users'), ['activation_state'=>'active', 'source_updated_at'=>$now], ['user_id'=>$userId])->execute();
            return ['status'=>'activated'];
        });
    }
    private function passwordError(string $password, string $confirmation, string $email): ?string
    {
        if ($password !== $confirmation) return 'Пароли не совпадают.';
        if (strlen($password) < 14 || strlen($password) > 200) return 'Пароль должен содержать от 14 до 200 символов.';
        $local = explode('@', $email, 2)[0];
        return strlen($local) >= 4 && stripos($password, $local) !== false ? 'Пароль не должен содержать часть email до знака @.' : null;
    }
}
