<?php
declare(strict_types=1);

namespace FMonitor2\IdentityAccess;

use yii\base\Component;
use yii\db\Connection;

final class MariaDbYiiLocalIdentityStore extends Component
{
    public Connection|string $db = 'db';
    public string $tablePrefix = '';
    public string $identityKey = '';

    public function init(): void
    {
        parent::init();
        if (is_string($this->db)) $this->db = \Yii::$app->get($this->db);
        if (!$this->db instanceof Connection || strlen($this->tablePrefix) > 32 || preg_match('/^[A-Za-z0-9_]*$/D', $this->tablePrefix) !== 1 || strlen($this->identityKey)<32) throw new \RuntimeException('Identity configuration unavailable.');
    }

    public function findById(mixed $id): ?YiiLocalIdentity
    {
        if (filter_var($id, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) === false) return null;
        $row = $this->db->createCommand('SELECT u.user_id,u.full_name,u.email,u.session_version FROM '.$this->table('fm2_pilot_users').' u JOIN '.$this->table('fm2_pilot_auth_credentials')." c ON c.user_id=u.user_id WHERE u.user_id=:id AND u.status=1 AND BINARY u.activation_state=BINARY 'active' AND c.password_hash IS NOT NULL LIMIT 2", [':id'=>(int)$id])->queryAll();
        return count($row) === 1 ? $this->identity($row[0]) : null;
    }

    public function findForLogin(string $email): ?array
    {
        $rows = $this->db->createCommand('SELECT u.user_id,u.full_name,u.email,u.session_version,c.password_hash FROM '.$this->table('fm2_pilot_users').' u JOIN '.$this->table('fm2_pilot_auth_credentials')." c ON c.user_id=u.user_id WHERE BINARY c.email_normalized=BINARY :email AND u.status=1 AND BINARY u.activation_state=BINARY 'active' AND c.password_hash IS NOT NULL LIMIT 2", [':email'=>$email])->queryAll();
        if (count($rows) !== 1 || !is_string($rows[0]['password_hash'])) return null;
        return ['identity'=>$this->identity($rows[0]), 'passwordHash'=>$rows[0]['password_hash']];
    }

    public function rateLimited(string $email): bool
    {
        return (int)$this->db->createCommand('SELECT COUNT(*) FROM '.$this->table('fm2_pilot_auth_attempts').' WHERE BINARY email_normalized=BINARY :email AND succeeded=0 AND attempted_at>=DATE_SUB(NOW(6),INTERVAL 15 MINUTE)', [':email'=>$email])->queryScalar() >= 10;
    }

    public function fail(string $email): void
    {
        $this->db->createCommand()->insert($this->rawTable('fm2_pilot_auth_attempts'), ['email_normalized'=>$email,'succeeded'=>0,'attempted_at'=>new \yii\db\Expression('NOW(6)')])->execute();
    }

    public function succeed(string $email): void
    {
        $this->db->createCommand()->delete($this->rawTable('fm2_pilot_auth_attempts'), 'BINARY email_normalized=BINARY :email', [':email'=>$email])->execute();
    }

    public function grants(int $userId, string $permission): bool
    {
        $value=$this->db->createCommand('SELECT 1 FROM '.$this->table('fm2_pilot_users').' u JOIN '.$this->table('fm2_pilot_user_roles').' ur ON ur.user_id=u.user_id JOIN '.$this->table('fm2_pilot_roles').' r ON r.role_id=ur.role_id JOIN '.$this->table('fm2_pilot_role_permissions')." rp ON rp.role_id=r.role_id WHERE u.user_id=:id AND u.status=1 AND BINARY u.activation_state=BINARY 'active' AND r.status=1 AND BINARY rp.permission=BINARY :permission LIMIT 1",[':id'=>$userId,':permission'=>$permission])->queryScalar();
        return $value !== false;
    }

    /** @return list<string> */
    public function activeRoleCodes(int $userId): array
    {
        $rows = $this->db->createCommand(
            'SELECT DISTINCT r.code FROM '.$this->table('fm2_pilot_users').' u JOIN '.$this->table('fm2_pilot_user_roles').' ur ON ur.user_id=u.user_id JOIN '.$this->table('fm2_pilot_roles')." r ON r.role_id=ur.role_id WHERE u.user_id=:id AND u.status=1 AND BINARY u.activation_state=BINARY 'active' AND r.status=1 ORDER BY r.code",
            [':id' => $userId],
        )->queryColumn();
        return array_values(array_map('strval', $rows));
    }

    /** @return array{roles:list<array>} */
    public function roles(): array
    {
        $roles=$this->db->createCommand('SELECT r.role_id,r.code,r.name,r.status,r.source_updated_at,COUNT(ur.user_id) user_count FROM '.$this->table('fm2_pilot_roles').' r LEFT JOIN '.$this->table('fm2_pilot_user_roles').' ur ON ur.role_id=r.role_id GROUP BY r.role_id,r.code,r.name,r.status,r.source_updated_at ORDER BY r.status DESC,r.name,r.role_id')->queryAll();
        $permissions=$this->db->createCommand('SELECT role_id,permission FROM '.$this->table('fm2_pilot_role_permissions').' ORDER BY role_id,permission')->queryAll();$byRole=[];foreach($permissions as$row)$byRole[(int)$row['role_id']][]=(string)$row['permission'];
        return ['roles'=>array_map(static fn(array$r):array=>['id'=>(int)$r['role_id'],'code'=>(string)$r['code'],'name'=>(string)$r['name'],'active'=>(int)$r['status']===1,'userCount'=>(int)$r['user_count'],'updatedAt'=>(string)$r['source_updated_at'],'permissions'=>$byRole[(int)$r['role_id']]??[]],$roles)];
    }

    private function identity(array $row): YiiLocalIdentity { $id=(int)$row['user_id'];$version=(int)$row['session_version'];return new YiiLocalIdentity($id,(string)$row['full_name'],(string)$row['email'],hash_hmac('sha256',$id.':'.$version,$this->identityKey)); }
    private function rawTable(string $name): string { return $this->tablePrefix.$name; }
    private function table(string $name): string { return $this->db->quoteTableName($this->rawTable($name)); }
}
