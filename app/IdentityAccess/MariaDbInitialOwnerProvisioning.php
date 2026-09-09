<?php
declare(strict_types=1);
namespace FMonitor2\IdentityAccess;

use FMonitor2\InstallationProcess\IdentityAccessDefinitionSchemaMigration;
use FMonitor2\InstallationProcess\IdentityAccessSchemaMigration;

final class MariaDbInitialOwnerProvisioning
{
    public static function provision(\mysqli $db, string $prefix, string $rawEmail, string $password): InitialOwnerProvisioningResult
    {
        IdentityAccessDefinitionSchemaMigration::assertPrefix($prefix);
        $email = mb_strtolower(trim($rawEmail));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || preg_match('/^[^@]+@shlz\.ru$/D', $email) !== 1 || $password === '') throw new \InvalidArgumentException();
        if (!IdentityAccessSchemaMigration::isCompleteCompatible($db, $prefix)) throw new \RuntimeException('SCHEMA_NOT_READY');
        $lock = self::acquire($db, $prefix);
        if ($lock === null) throw new \RuntimeException('PROVISIONING_BUSY');
        try { return self::provisionLocked($db, $prefix, $email, $password); }
        finally { self::release($db, $lock); }
    }

    private static function provisionLocked(\mysqli $db, string $prefix, string $email, string $password): InitialOwnerProvisioningResult
    {
        $db->begin_transaction();
        try {
            $users = $db->query("SELECT * FROM `{$prefix}fm2_pilot_users` FOR UPDATE")->fetch_all(MYSQLI_ASSOC);
            if ($users === [] && !self::identityIsEmpty($db, $prefix)) {
                $db->commit();
                return InitialOwnerProvisioningResult::identityNotEmpty();
            }
            if ($users !== []) {
                $result = count($users) === 1 && self::isExactReplay($db, $prefix, $users[0], $email, $password)
                    ? InitialOwnerProvisioningResult::alreadyProvisioned()
                    : InitialOwnerProvisioningResult::identityNotEmpty();
                $db->commit();
                return $result;
            }
            self::seedRoles($db, $prefix);
            $hash = password_hash($password, PASSWORD_ARGON2ID);
            if (!is_string($hash)) throw new \RuntimeException();
            $now = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow')))->format(DATE_ATOM);
            $user = $db->prepare("INSERT INTO `{$prefix}fm2_pilot_users`(full_name,email,phone,status,activation_state,session_version,source_updated_at)VALUES(?,?,'',1,'active',1,?)");
            $user->bind_param('sss', $email, $email, $now);$user->execute();$userId = (int) $db->insert_id;
            $credential = $db->prepare("INSERT INTO `{$prefix}fm2_pilot_auth_credentials`(user_id,email_normalized,password_hash,password_set_at,updated_at)VALUES(?,?,?,?,?)");
            $credential->bind_param('issss', $userId, $email, $hash, $now, $now);$credential->execute();
            foreach (['user','superadministrator'] as $code) self::grant($db, $prefix, $userId, $code, $now);
            $db->commit();
            return InitialOwnerProvisioningResult::created();
        } catch (\Throwable $error) { $db->rollback(); throw $error; }
    }

    private static function isExactReplay(\mysqli $db, string $prefix, array $user, string $email, string $password): bool
    {
        if ($user['email'] !== $email || $user['full_name'] !== $email || $user['phone'] !== ''
            || (int) $user['session_version'] !== 1 || (int) $user['status'] !== 1 || $user['activation_state'] !== 'active') return false;
        $id = (int) $user['user_id'];
        $statement = $db->prepare("SELECT email_normalized,password_hash FROM `{$prefix}fm2_pilot_auth_credentials` WHERE user_id=?");$statement->bind_param('i',$id);$statement->execute();$credential=$statement->get_result()->fetch_assoc();
        if (!is_array($credential) || $credential['email_normalized'] !== $email || !str_starts_with((string)$credential['password_hash'],'$argon2id$') || !password_verify($password,(string)$credential['password_hash'])) return false;
        $grants=$db->query("SELECT r.code,ur.origin,ur.assigned_by_user_id FROM `{$prefix}fm2_pilot_user_roles`ur JOIN `{$prefix}fm2_pilot_roles`r ON r.role_id=ur.role_id WHERE ur.user_id={$id} ORDER BY r.code")->fetch_all(MYSQLI_ASSOC);
        return $grants === [['code'=>'superadministrator','origin'=>'bootstrap','assigned_by_user_id'=>null],['code'=>'user','origin'=>'bootstrap','assigned_by_user_id'=>null]]
            && self::catalogIsExact($db,$prefix) && self::auxiliaryHistoryIsEmpty($db,$prefix);
    }

    private static function identityIsEmpty(\mysqli$db,string$p):bool{foreach(IdentityAccessDefinitionSchemaMigration::tables()as$table)if((int)$db->query("SELECT COUNT(*)n FROM `{$p}{$table}`")->fetch_assoc()['n']!==0)return false;return true;}
    private static function auxiliaryHistoryIsEmpty(\mysqli$db,string$p):bool{foreach(['fm2_pilot_invitations','fm2_pilot_user_role_events','fm2_pilot_auth_attempts','fm2_pilot_user_status_events']as$table)if((int)$db->query("SELECT COUNT(*)n FROM `{$p}{$table}`")->fetch_assoc()['n']!==0)return false;return true;}

    private static function seedRoles(\mysqli $db, string $prefix): void
    {
        $now=(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format(DATE_ATOM);
        foreach(LocalRoleCatalog::roles()as$code=>$role){$s=$db->prepare("INSERT INTO `{$prefix}fm2_pilot_roles`(code,name,description,status,source_updated_at)VALUES(?,?,?,1,?)");$s->bind_param('ssss',$code,$role['name'],$role['description'],$now);$s->execute();$roleId=(int)$db->insert_id;$p=$db->prepare("INSERT INTO `{$prefix}fm2_pilot_role_permissions`(role_id,permission)VALUES(?,?)");foreach($role['permissions']as$permission){$p->bind_param('is',$roleId,$permission);$p->execute();}}
    }
    private static function grant(\mysqli$db,string$p,int$user,string$code,string$now):void{$s=$db->prepare("SELECT role_id FROM `{$p}fm2_pilot_roles` WHERE code=?");$s->bind_param('s',$code);$s->execute();$role=(int)$s->get_result()->fetch_column();$g=$db->prepare("INSERT INTO `{$p}fm2_pilot_user_roles`(user_id,role_id,origin,assigned_at,assigned_by_user_id)VALUES(?,?,'bootstrap',?,NULL)");$g->bind_param('iis',$user,$role,$now);$g->execute();}
    private static function catalogIsExact(\mysqli$db,string$p):bool{$expected=LocalRoleCatalog::roles();$rows=$db->query("SELECT role_id,code,name,description,status FROM `{$p}fm2_pilot_roles`")->fetch_all(MYSQLI_ASSOC);if(count($rows)!==count($expected))return false;foreach($rows as$r){$e=$expected[$r['code']]??null;if($e===null||$r['name']!==$e['name']||$r['description']!==$e['description']||(int)$r['status']!==1)return false;$permissions=array_column($db->query("SELECT permission FROM `{$p}fm2_pilot_role_permissions` WHERE role_id=".(int)$r['role_id'])->fetch_all(MYSQLI_ASSOC),'permission');$wanted=$e['permissions'];sort($permissions,SORT_STRING);sort($wanted,SORT_STRING);if($permissions!==$wanted)return false;}return true;}
    private static function acquire(\mysqli$db,string$p):?string{$database=$db->query('SELECT DATABASE()')->fetch_column();if(!is_string($database)||$database==='')throw new \RuntimeException();$name=hash('sha256',$database."\0".$p."\0initial-owner");$s=$db->prepare('SELECT GET_LOCK(?,0)');$s->bind_param('s',$name);$s->execute();$result=$s->get_result()->fetch_column();if((string)$result==='1')return$name;if((string)$result==='0')return null;throw new \RuntimeException();}
    private static function release(\mysqli$db,string$name):void{$s=$db->prepare('SELECT RELEASE_LOCK(?)');$s->bind_param('s',$name);$s->execute();if((string)$s->get_result()->fetch_column()!=='1')throw new \RuntimeException();}
}
