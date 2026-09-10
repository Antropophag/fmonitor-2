<?php
declare(strict_types=1);
namespace FMonitor2\IdentityAccess;

use yii\db\Connection;

final class MariaDbUserAccessTransaction
{
    private ?string $time = null;

    public function __construct(public readonly Connection $db, private readonly string $prefix) {}

    public function run(callable $operation): array
    {
        $transaction = $this->db->beginTransaction(\yii\db\Transaction::SERIALIZABLE);
        try {
            $clock = (string) $this->db->createCommand('SELECT UTC_TIMESTAMP(6)')->queryScalar();
            $this->time = (new \DateTimeImmutable($clock, new \DateTimeZone('UTC')))
                ->setTimezone(new \DateTimeZone('Europe/Moscow'))->format(DATE_ATOM);
            $this->db->createCommand('SELECT role_id FROM '.$this->table('fm2_pilot_roles')." WHERE BINARY code=BINARY 'user' FOR UPDATE")->queryScalar();
            $result = $operation();
            $transaction->commit();
            return $result;
        } catch (\Throwable $error) {
            if ($transaction->isActive) $transaction->rollBack();
            throw $error;
        } finally {
            $this->time = null;
        }
    }

    public function authorized(int $actorId, string $permission): bool
    {
        return $this->db->createCommand('SELECT 1 FROM '.$this->table('fm2_pilot_users').' u JOIN '.$this->table('fm2_pilot_user_roles').' ur ON ur.user_id=u.user_id JOIN '.$this->table('fm2_pilot_roles').' r ON r.role_id=ur.role_id JOIN '.$this->table('fm2_pilot_role_permissions')." rp ON rp.role_id=r.role_id WHERE u.user_id=:actor AND u.status=1 AND BINARY u.activation_state=BINARY 'active' AND r.status=1 AND BINARY rp.permission=BINARY :permission LIMIT 1", [':actor'=>$actorId, ':permission'=>$permission])->queryScalar() !== false;
    }

    public function isSuper(int $userId): bool
    {
        return $this->db->createCommand('SELECT 1 FROM '.$this->table('fm2_pilot_user_roles').' ur JOIN '.$this->table('fm2_pilot_roles')." r ON r.role_id=ur.role_id WHERE ur.user_id=:user AND r.status=1 AND BINARY r.code=BINARY 'superadministrator'", [':user'=>$userId])->queryScalar() !== false;
    }

    public function activeSupers(): int
    {
        return (int) $this->db->createCommand('SELECT COUNT(DISTINCT u.user_id) FROM '.$this->table('fm2_pilot_users').' u JOIN '.$this->table('fm2_pilot_user_roles').' ur ON ur.user_id=u.user_id JOIN '.$this->table('fm2_pilot_roles')." r ON r.role_id=ur.role_id WHERE u.status=1 AND BINARY u.activation_state=BINARY 'active' AND r.status=1 AND BINARY r.code=BINARY 'superadministrator'")->queryScalar();
    }

    public function now(): string
    {
        if ($this->time === null) throw new \LogicException('Mutation clock unavailable.');
        return $this->time;
    }

    public function raw(string $name): string { return $this->prefix.$name; }
    public function table(string $name): string { return $this->db->quoteTableName($this->raw($name)); }
}
