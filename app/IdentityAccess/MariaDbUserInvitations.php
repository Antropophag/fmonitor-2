<?php
declare(strict_types=1);
namespace FMonitor2\IdentityAccess;

use yii\db\Expression;

final class MariaDbUserInvitations
{
    public function __construct(private readonly MariaDbUserAccessTransaction $context) {}

    public function invite(int $actorId, string $email, string $fullName): array
    {
        return $this->context->run(function () use ($actorId, $email, $fullName): array {
            if (!$this->context->authorized($actorId, 'access.administer')) return ['status'=>'access_denied'];
            $email = mb_strtolower(trim($email));
            $fullName = trim($fullName);
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || preg_match('/^[^@]+@shlz\.ru$/D', $email) !== 1 || $fullName === '' || mb_strlen($fullName) > 300) return ['status'=>'invalid'];
            $db = $this->context->db;
            if ($db->createCommand('SELECT user_id FROM '.$this->context->table('fm2_pilot_users').' WHERE BINARY email=BINARY :email FOR UPDATE', [':email'=>$email])->queryOne() !== false) return ['status'=>'invalid'];
            $now = $this->context->now();
            $db->createCommand()->insert($this->context->raw('fm2_pilot_users'), ['full_name'=>$fullName, 'email'=>$email, 'phone'=>'', 'status'=>1, 'activation_state'=>'invited', 'session_version'=>1, 'source_updated_at'=>$now])->execute();
            $userId = (int) $db->getLastInsertID();
            $db->createCommand()->insert($this->context->raw('fm2_pilot_auth_credentials'), ['user_id'=>$userId, 'email_normalized'=>$email, 'password_hash'=>null, 'password_set_at'=>null, 'updated_at'=>$now])->execute();
            $roleId = $db->createCommand('SELECT role_id FROM '.$this->context->table('fm2_pilot_roles')." WHERE BINARY code=BINARY 'user' AND status=1 LIMIT 1")->queryScalar();
            if ($roleId === false) throw new \RuntimeException('Default role unavailable.');
            $db->createCommand()->insert($this->context->raw('fm2_pilot_user_roles'), ['user_id'=>$userId, 'role_id'=>(int)$roleId, 'origin'=>'administrator', 'assigned_at'=>$now, 'assigned_by_user_id'=>$actorId])->execute();
            return $this->issue($userId, $actorId);
        });
    }

    public function reissue(int $actorId, int $userId): array
    {
        return $this->context->run(function () use ($actorId, $userId): array {
            if (!$this->context->authorized($actorId, 'access.administer')) return ['status'=>'access_denied'];
            $user = $this->context->db->createCommand('SELECT status,activation_state FROM '.$this->context->table('fm2_pilot_users').' WHERE user_id=:user FOR UPDATE', [':user'=>$userId])->queryOne();
            if ($user === false || (int)$user['status'] !== 1 || $user['activation_state'] !== 'invited') return ['status'=>'invalid'];
            $this->context->db->createCommand()->update($this->context->raw('fm2_pilot_invitations'), ['revoked_at'=>new Expression('NOW(6)')], 'user_id=:user AND used_at IS NULL AND revoked_at IS NULL', [':user'=>$userId])->execute();
            return $this->issue($userId, $actorId);
        });
    }

    public function lookup(string $token): ?array
    {
        if (preg_match('/^[A-Za-z0-9_-]{43}$/D', $token) !== 1) return null;
        $row = $this->context->db->createCommand('SELECT u.email FROM '.$this->context->table('fm2_pilot_invitations').' i JOIN '.$this->context->table('fm2_pilot_users')." u ON u.user_id=i.user_id WHERE i.token_hash=:hash AND i.used_at IS NULL AND i.revoked_at IS NULL AND i.expires_at>NOW(6) AND u.status=1 AND BINARY u.activation_state=BINARY 'invited' LIMIT 1", [':hash'=>hash('sha256', $token, true)])->queryOne();
        return $row === false ? null : ['email'=>(string)$row['email']];
    }

    private function issue(int $userId, int $actorId): array
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->context->db->createCommand()->insert($this->context->raw('fm2_pilot_invitations'), ['user_id'=>$userId, 'token_hash'=>hash('sha256', $token, true), 'expires_at'=>new Expression('DATE_ADD(NOW(6),INTERVAL 24 HOUR)'), 'created_by_user_id'=>$actorId, 'created_at'=>new Expression('NOW(6)')])->execute();
        return ['status'=>'issued', 'userId'=>$userId, 'token'=>$token];
    }
}
