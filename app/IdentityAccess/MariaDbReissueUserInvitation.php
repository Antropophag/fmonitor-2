<?php

declare(strict_types=1);

namespace FMonitor2\IdentityAccess;

/** Owns authorization, revocation and issuance in one transaction. */
final class MariaDbReissueUserInvitation implements ReissueUserInvitation
{
    public function __construct(private readonly \mysqli $db, private readonly string $prefix)
    {
        if (strlen($prefix) > 32 || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException('Invalid table prefix.');
        }
    }

    public function reissue(int $actorId, int $userId): array
    {
        $this->db->begin_transaction();
        try {
            $grant = $this->db->prepare(
                "SELECT u.user_id FROM `{$this->prefix}fm2_pilot_users` u"
                . " JOIN `{$this->prefix}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id"
                . " JOIN `{$this->prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id AND r.status=1"
                . " JOIN `{$this->prefix}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id"
                . " WHERE u.user_id=? AND u.status=1 AND BINARY u.activation_state=BINARY 'active'"
                . " AND BINARY rp.permission=BINARY 'access.administer' FOR UPDATE"
            );
            $grant->bind_param('i', $actorId);
            $grant->execute();
            if ($grant->get_result()->num_rows === 0) {
                $this->db->rollback();
                return ['status' => 'access_denied'];
            }

            $target = $this->db->prepare(
                "SELECT status,activation_state FROM `{$this->prefix}fm2_pilot_users` WHERE user_id=? FOR UPDATE"
            );
            $target->bind_param('i', $userId);
            $target->execute();
            $user = $target->get_result()->fetch_assoc();
            if ($user === null || (int) $user['status'] !== 1 || $user['activation_state'] !== 'invited') {
                $this->db->rollback();
                return ['status' => 'not_invited'];
            }

            $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $hash = hash('sha256', $token, true);
            $revoke = $this->db->prepare(
                "UPDATE `{$this->prefix}fm2_pilot_invitations` SET revoked_at=NOW(6)"
                . ' WHERE user_id=? AND used_at IS NULL AND revoked_at IS NULL'
            );
            $revoke->bind_param('i', $userId);
            $revoke->execute();
            $issue = $this->db->prepare(
                "INSERT INTO `{$this->prefix}fm2_pilot_invitations`"
                . '(user_id,token_hash,expires_at,created_by_user_id,created_at)'
                . ' VALUES(?,?,DATE_ADD(NOW(6),INTERVAL 24 HOUR),?,NOW(6))'
            );
            $issue->bind_param('isi', $userId, $hash, $actorId);
            $issue->execute();
            $this->db->commit();
            return ['status' => 'issued', 'token' => $token];
        } catch (\Throwable $error) {
            $this->db->rollback();
            throw $error;
        }
    }
}
