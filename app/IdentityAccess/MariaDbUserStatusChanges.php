<?php
declare(strict_types=1);
namespace FMonitor2\IdentityAccess;
use yii\db\Expression;

final class MariaDbUserStatusChanges
{
    public function __construct(private readonly MariaDbUserAccessTransaction $context) {}
    public function change(int $actorId, int $userId, string $action): array
    {
        return $this->context->run(function () use ($actorId, $userId, $action): array {
            if (!$this->context->authorized($actorId, 'access.administer')) return ['status'=>'access_denied'];
            if (!in_array($action, ['block','unblock'], true)) return ['status'=>'invalid'];
            if ($actorId === $userId) return ['status'=>'access_denied'];
            $db = $this->context->db;
            $user = $db->createCommand('SELECT status,activation_state FROM '.$this->context->table('fm2_pilot_users').' WHERE user_id=:user FOR UPDATE', [':user'=>$userId])->queryOne();
            if ($user === false || ($action === 'block' && ((int)$user['status'] !== 1 || $user['activation_state'] !== 'active')) || ($action === 'unblock' && ((int)$user['status'] !== 0 || $user['activation_state'] !== 'blocked'))) return ['status'=>'invalid'];
            if ($action === 'block' && $this->context->isSuper($userId) && $this->context->activeSupers() <= 1) return ['status'=>'invalid'];
            $now = $this->context->now();
            $db->createCommand()->update($this->context->raw('fm2_pilot_users'), ['status'=>$action === 'block' ? 0 : 1, 'activation_state'=>$action === 'block' ? 'blocked' : 'active', 'session_version'=>new Expression('session_version+1'), 'source_updated_at'=>$now], ['user_id'=>$userId])->execute();
            $db->createCommand()->insert($this->context->raw('fm2_pilot_user_status_events'), ['user_id'=>$userId, 'action'=>$action === 'block' ? 'user_blocked' : 'user_unblocked', 'occurred_at'=>$now, 'actor_user_id'=>$actorId])->execute();
            return ['status'=>'changed'];
        });
    }
}
