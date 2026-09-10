<?php
declare(strict_types=1);

namespace FMonitor2\IdentityAccess;

use yii\base\Component;
use yii\db\Connection;

final class YiiUserAccess extends Component
{
    public Connection|string $db = 'db';
    public string $tablePrefix = '';

    private MariaDbUserAccessDirectory $directory;
    private MariaDbUserInvitations $invitations;
    private MariaDbUserActivation $activation;
    private MariaDbUserRoleChanges $roles;
    private MariaDbUserStatusChanges $statuses;

    public function init(): void
    {
        parent::init();
        if (is_string($this->db)) $this->db = \Yii::$app->get($this->db);
        if (!$this->db instanceof Connection || strlen($this->tablePrefix) > 32 || preg_match('/^[A-Za-z0-9_]*$/D', $this->tablePrefix) !== 1) {
            throw new \RuntimeException('User access configuration unavailable.');
        }
        $context = new MariaDbUserAccessTransaction($this->db, $this->tablePrefix);
        $this->directory = new MariaDbUserAccessDirectory($context);
        $this->invitations = new MariaDbUserInvitations($context);
        $this->activation = new MariaDbUserActivation($context);
        $this->roles = new MariaDbUserRoleChanges($context);
        $this->statuses = new MariaDbUserStatusChanges($context);
    }

    public function directory(int $actorId): array { return $this->directory->read($actorId); }
    public function invite(int $actorId, string $email, string $fullName): array { return $this->invitations->invite($actorId, $email, $fullName); }
    public function reissue(int $actorId, int $userId): array { return $this->invitations->reissue($actorId, $userId); }
    public function invitation(string $token): ?array { return $this->invitations->lookup($token); }
    public function activate(string $token, string $password, string $confirmation): array { return $this->activation->activate($token, $password, $confirmation); }
    public function changeRole(int $actorId, int $userId, int $roleId, string $action): array { return $this->roles->change($actorId, $userId, $roleId, $action); }
    public function changeStatus(int $actorId, int $userId, string $action): array { return $this->statuses->change($actorId, $userId, $action); }
}
