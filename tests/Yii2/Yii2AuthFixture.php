<?php
declare(strict_types=1);

namespace FMonitor2\Tests\Yii2;

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;

final class Yii2AuthFixture
{
    public readonly \mysqli $db;
    public readonly string $database;
    public readonly string $prefix;
    public readonly string $sessionPath;
    public readonly string $email;
    public readonly string $password;
    public readonly string $passwordHash;
    private \mysqli $admin;
    private string $temporaryRoot;

    public function __construct(private readonly string $repositoryRoot)
    {
        \mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $token = bin2hex(random_bytes(6));
        $this->database = 't_yii2_auth_' . $token;
        $this->prefix = 'ya_';
        $this->temporaryRoot = sys_get_temp_dir() . '/fmonitor-yii2-auth-' . $token;
        $this->sessionPath = $this->temporaryRoot . '/sessions';
        mkdir($this->sessionPath, 0700, true);
        $this->admin = $this->connect(null);
        $this->admin->query("CREATE DATABASE `{$this->database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->db = $this->connect($this->database);
        $result = CanonicalMigrationApplication::run($this->db, $this->prefix, ProductionPilotMigrationCatalogue::migrations());
        if (($result['exitCode'] ?? 1) !== 0) throw new \TestFailure('SETUP_FAILURE: canonical auth schema');
        $this->email = 'yii.auth.admin@shlz.ru';
        $this->password = 'Correct Yii auth fixture 2026';
        $this->passwordHash = (string) password_hash($this->password, PASSWORD_ARGON2ID);
        $this->seed();
    }

    /** @return array<string,string> */
    public function environment(): array
    {
        return [
            'FMONITOR_DB_HOST' => getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
            'FMONITOR_DB_PORT' => getenv('FMONITOR_TEST_DB_PORT') ?: '23306',
            'FMONITOR_DB_NAME' => $this->database,
            'FMONITOR_DB_USER' => getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
            'FMONITOR_DB_PASSWORD' => getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
            'FMONITOR_PROCESS_TABLE_PREFIX' => $this->prefix,
            'FMONITOR_YII_SESSION_PATH' => $this->sessionPath,
            'FMONITOR_YII_SESSION_COOKIE' => 'fm2yii_test',
            'FMONITOR_YII_COOKIE_VALIDATION_KEY' => str_repeat('fixture-cookie-key-', 3),
            'FMONITOR_YII_IDENTITY_KEY' => str_repeat('fixture-identity-key-', 3),
            'FMONITOR_YII_RUNTIME_PATH' => $this->temporaryRoot . '/runtime',
            'FMONITOR_TRUSTED_REQUEST_HOST' => 'fmonitor.example.test',
            'FMONITOR_TRUSTED_REQUEST_SCHEME' => 'http',
            'FMONITOR_SHLZ_CSS_PATH' => dirname($this->repositoryRoot) . '/shlz-ui/packages/styles/dist/shlz.css',
        ];
    }

    public function setUserState(int $status, string $activation, int $sessionVersion = 1): void
    {
        $statement = $this->db->prepare("UPDATE `{$this->prefix}fm2_pilot_users` SET status=?,activation_state=?,session_version=? WHERE user_id=9101");
        $statement->bind_param('isi', $status, $activation, $sessionVersion);
        $statement->execute();
    }

    public function setRoleStatus(int $status): void
    {
        $this->db->query("UPDATE `{$this->prefix}fm2_pilot_roles` SET status={$status} WHERE role_id=9201");
    }

    public function setRoleName(string $name): void
    {
        $statement = $this->db->prepare("UPDATE `{$this->prefix}fm2_pilot_roles` SET name=? WHERE role_id=9201");
        $statement->bind_param('s', $name); $statement->execute();
    }

    public function attemptCount(bool $success): int
    {
        $value = $success ? 1 : 0;
        return (int) $this->db->query("SELECT COUNT(*) n FROM `{$this->prefix}fm2_pilot_auth_attempts` WHERE email_normalized='{$this->email}' AND succeeded={$value}")->fetch_assoc()['n'];
    }

    public function clearAttempts(): void
    {
        $this->db->query("DELETE FROM `{$this->prefix}fm2_pilot_auth_attempts`");
    }

    public function seedFailedAttempts(int $count): void
    {
        $statement = $this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_auth_attempts`(email_normalized,succeeded,attempted_at) VALUES(?,0,NOW(6))");
        $email = $this->email;
        for ($index = 0; $index < $count; $index++) { $statement->bind_param('s', $email); $statement->execute(); }
    }

    public function setPermission(string $permission): void
    {
        $statement = $this->db->prepare("UPDATE `{$this->prefix}fm2_pilot_role_permissions` SET permission=? WHERE role_id=9201");
        $statement->bind_param('s', $permission); $statement->execute();
    }

    public function removeCredential(): void
    {
        $this->db->query("DELETE FROM `{$this->prefix}fm2_pilot_auth_credentials` WHERE user_id=9101");
    }

    public function restoreCredential(): void
    {
        $now='2026-09-09T12:00:00+03:00';$email=$this->email;$hash=$this->passwordHash;
        $statement=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_pilot_auth_credentials`(user_id,email_normalized,password_hash,password_set_at,updated_at) VALUES(9101,?,?,?,?)");
        $statement->bind_param('ssss',$email,$hash,$now,$now);$statement->execute();
    }

    public function currentHash(): string
    {
        return (string) $this->db->query("SELECT password_hash FROM `{$this->prefix}fm2_pilot_auth_credentials` WHERE user_id=9101")->fetch_assoc()['password_hash'];
    }

    public function close(): void
    {
        $this->db->close();
        $this->admin->query("DROP DATABASE `{$this->database}`");
        $this->admin->close();
        $this->remove($this->temporaryRoot);
    }

    private function seed(): void
    {
        $p = $this->prefix;
        $now = '2026-09-09T12:00:00+03:00';
        $email = $this->email;
        $passwordHash = $this->passwordHash;
        $user = $this->db->prepare("INSERT INTO `{$p}fm2_pilot_users`(user_id,full_name,email,phone,status,activation_state,session_version,source_updated_at) VALUES(9101,'Yii Auth Admin',?,'',1,'active',1,?)");
        $user->bind_param('ss', $email, $now); $user->execute();
        $credential = $this->db->prepare("INSERT INTO `{$p}fm2_pilot_auth_credentials`(user_id,email_normalized,password_hash,password_set_at,updated_at) VALUES(9101,?,?,?,?)");
        $credential->bind_param('ssss', $email, $passwordHash, $now, $now); $credential->execute();
        $this->db->query("INSERT INTO `{$p}fm2_pilot_roles`(role_id,code,name,description,status,source_updated_at) VALUES(9201,'access_administrator','Администратор доступа','fixture',1,'{$now}')");
        $this->db->query("INSERT INTO `{$p}fm2_pilot_role_permissions`(role_id,permission) VALUES(9201,'access.administer')");
        $this->db->query("INSERT INTO `{$p}fm2_pilot_user_roles`(user_id,role_id,origin,assigned_at,assigned_by_user_id) VALUES(9101,9201,'fixture','{$now}',NULL)");
    }

    private function connect(?string $database): \mysqli
    {
        $db = new \mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1', getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root', getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local', $database, (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
        $db->set_charset('utf8mb4'); return $db;
    }

    private function remove(string $path): void
    {
        if (is_file($path) || is_link($path)) { unlink($path); return; }
        if (!is_dir($path)) return;
        foreach (scandir($path) ?: [] as $entry) if ($entry !== '.' && $entry !== '..') $this->remove($path . '/' . $entry);
        rmdir($path);
    }
}
