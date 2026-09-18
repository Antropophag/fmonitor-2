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
                "SELECT email,status,activation_state FROM `{$this->prefix}fm2_pilot_users` WHERE user_id=? FOR UPDATE"
            );
            $target->bind_param('i', $userId);
            $target->execute();
            $user = $target->get_result()->fetch_assoc();
            if ($user === null || (int) $user['status'] !== 1 || !in_array($user['activation_state'], ['pending_invitation','invited'], true)) {
                $this->db->rollback();
                return ['status' => 'not_invited'];
            }

            if ($user['activation_state'] === 'pending_invitation') {
                $email = mb_strtolower(trim((string) $user['email']));
                if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    throw new \RuntimeException('Pending invitation email is invalid.');
                }
                $credential = $this->db->prepare(
                    "INSERT INTO `{$this->prefix}fm2_pilot_auth_credentials`"
                    . '(user_id,email_normalized,password_hash,password_set_at,updated_at)'
                    . " VALUES(?,?,NULL,NULL,DATE_FORMAT(UTC_TIMESTAMP(),'%Y-%m-%dT%H:%i:%sZ'))"
                );
                $credential->bind_param('is', $userId, $email);
                $credential->execute();
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
            if ($user['activation_state'] === 'pending_invitation') {
                $state = $this->db->prepare("UPDATE `{$this->prefix}fm2_pilot_users` SET activation_state='invited',source_updated_at=DATE_FORMAT(UTC_TIMESTAMP(),'%Y-%m-%dT%H:%i:%sZ') WHERE user_id=?");
                $state->bind_param('i', $userId);
                $state->execute();
            }
            $this->db->commit();
            return ['status' => 'issued', 'token' => $token];
        } catch (\Throwable $error) {
            $this->db->rollback();
            throw $error;
        }
    }
}
