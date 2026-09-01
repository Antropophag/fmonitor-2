<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
use DateTimeImmutable;
use DateTimeZone;
use FMonitor2\InstallationProcess\IdentityAccessSchemaMigration;
use FMonitor2\RapidPilot\LocalRoleCatalog;
use mysqli;
use RuntimeException;
use Throwable;
final class MariaDbIdentityBootstrapApplication {
    public static function apply(mysqli $db, string $prefix, string $configured, string $bootstrapPassword): void
    {
        self::assertPrefix($prefix);
        $migration = IdentityAccessSchemaMigration::apply($db, $prefix);
        if (($migration['reason'] ?? null) === 'SCHEMA_MIGRATION_CONFLICT') {
            throw new RuntimeException('Identity/access schema is incompatible');
        }
        self::seedRoles($db, $prefix);
        $emails = self::emails($configured);
        $passwordHash = self::bootstrapPasswordHash($emails, $bootstrapPassword);
        $db->begin_transaction();
        try {
            foreach ($emails as $email) {
                $userId = self::activateBootstrapUser($db, $prefix, $email, $passwordHash);
                $db->query("UPDATE `{$prefix}fm2_pilot_invitations` SET revoked_at=NOW(6) WHERE user_id={$userId} AND used_at IS NULL AND revoked_at IS NULL");
                self::grant($db, $prefix, $userId, 'user', 'bootstrap', null);
                self::grant($db, $prefix, $userId, 'superadministrator', 'bootstrap', null);
            }
            self::assertActiveSuperadministrator($db, $prefix);
            $db->commit();
        } catch (Throwable $error) {
            $db->rollback();
            throw $error;
        }
    }
    public static function rebuild(mysqli $db, string $prefix, string $configured, string $bootstrapPassword): void
    {
        self::assertPrefix($prefix);
        IdentityAccessSchemaMigration::rebuild($db, $prefix);
        self::apply($db, $prefix, $configured, $bootstrapPassword);
    }
    private static function assertPrefix(string $prefix): void
    {
        if (\preg_match('/^[A-Za-z0-9_]+$/D', $prefix) !== 1) {
            throw new RuntimeException('Invalid identity table prefix');
        }
    }
    /** @param list<string> $emails */
    private static function bootstrapPasswordHash(array $emails, string $password): ?string
    {
        if ($emails === []) {
            return null;
        }
        if ($password === '') {
            throw new RuntimeException('Missing FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD');
        }
        $hash = \password_hash($password, PASSWORD_ARGON2ID);
        if (!\is_string($hash)) {
            throw new RuntimeException('Bootstrap password hashing failed');
        }
        return $hash;
    }
    private static function activateBootstrapUser(mysqli $db, string $prefix, string $email, ?string $passwordHash): int
    {
        $user = self::user($db, $prefix, $email);
        $now = self::now();
        if ($user === null) {
            $statement = $db->prepare("INSERT INTO `{$prefix}fm2_pilot_users`(full_name,email,phone,status,activation_state,session_version,source_updated_at) VALUES(?,?,'',1,'active',1,?)");
            $statement->bind_param('sss', $email, $email, $now);
            $statement->execute();
            $userId = (int) $db->insert_id;
            $credentials = $db->prepare("INSERT INTO `{$prefix}fm2_pilot_auth_credentials`(user_id,email_normalized,password_hash,password_set_at,updated_at) VALUES(?,?,?,?,?)");
            $credentials->bind_param('issss', $userId, $email, $passwordHash, $now, $now);
            $credentials->execute();
            return $userId;
        }
        $userId = (int) $user['user_id'];
        if ((int) $user['status'] !== 1) {
            throw new RuntimeException('Configured bootstrap superadministrator is blocked: ' . $email);
        }
        $credentials = $db->prepare("UPDATE `{$prefix}fm2_pilot_users` u JOIN `{$prefix}fm2_pilot_auth_credentials` c ON c.user_id=u.user_id SET u.activation_state='active',u.source_updated_at=?,c.password_hash=COALESCE(c.password_hash,?),c.password_set_at=COALESCE(c.password_set_at,?),c.updated_at=? WHERE u.user_id=?");
        $credentials->bind_param('ssssi', $now, $passwordHash, $now, $now, $userId);
        $credentials->execute();
        return $userId;
    }
    private static function seedRoles(mysqli $db, string $prefix): void
    {
        $now = self::now();
        foreach (LocalRoleCatalog::roles() as $code => $role) {
            $statement = $db->prepare("INSERT INTO `{$prefix}fm2_pilot_roles`(code,name,description,status,source_updated_at) VALUES(?,?,?,1,?) ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),status=1,source_updated_at=VALUES(source_updated_at)");
            $statement->bind_param('ssss', $code, $role['name'], $role['description'], $now);
            $statement->execute();
            $escapedCode = $db->real_escape_string($code);
            $roleId = (int) $db->query("SELECT role_id FROM `{$prefix}fm2_pilot_roles` WHERE code='{$escapedCode}'")->fetch_assoc()['role_id'];
            $db->query("DELETE FROM `{$prefix}fm2_pilot_role_permissions` WHERE role_id={$roleId}");
            $permissionInsert = $db->prepare("INSERT INTO `{$prefix}fm2_pilot_role_permissions`(role_id,permission) VALUES(?,?)");
            foreach ($role['permissions'] as $permission) {
                $permissionInsert->bind_param('is', $roleId, $permission);
                $permissionInsert->execute();
            }
        }
    }
    private static function grant(mysqli $db, string $prefix, int $userId, string $code, string $origin, ?int $actor): void
    {
        $escapedCode = $db->real_escape_string($code);
        $roleId = (int) $db->query("SELECT role_id FROM `{$prefix}fm2_pilot_roles` WHERE code='{$escapedCode}'")->fetch_assoc()['role_id'];
        $now = self::now();
        $statement = $db->prepare("INSERT IGNORE INTO `{$prefix}fm2_pilot_user_roles`(user_id,role_id,origin,assigned_at,assigned_by_user_id) VALUES(?,?,?,?,?)");
        $statement->bind_param('iissi', $userId, $roleId, $origin, $now, $actor);
        $statement->execute();
    }
    private static function user(mysqli $db, string $prefix, string $email): ?array
    {
        $statement = $db->prepare("SELECT user_id,status FROM `{$prefix}fm2_pilot_users` WHERE email=? LIMIT 1");
        $statement->bind_param('s', $email);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        return $row ?: null;
    }
    /** @return list<string> */
    private static function emails(string $raw): array
    {
        $raw = \trim($raw);
        if ($raw === '') {
            return [];
        }
        $emails = [];
        foreach (\explode(',', $raw) as $value) {
            $email = \mb_strtolower(\trim($value));
            if (\filter_var($email, FILTER_VALIDATE_EMAIL) === false || \preg_match('/^[^@]+@shlz\.ru$/D', $email) !== 1) {
                throw new RuntimeException('Invalid bootstrap superadministrator email');
            }
            $emails[$email] = true;
        }
        return \array_keys($emails);
    }
    private static function assertActiveSuperadministrator(mysqli $db, string $prefix): void
    {
        $result = $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_pilot_users` u JOIN `{$prefix}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id WHERE u.status=1 AND u.activation_state='active' AND r.code='superadministrator'");
        if ((int) $result->fetch_assoc()['n'] < 1) {
            throw new RuntimeException('No active superadministrator; configure bootstrap credentials');
        }
    }
    private static function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')))->format(DATE_ATOM);
    }
}
