<?php

declare(strict_types=1);

namespace FMonitor2\IdentityAccess;

/** Read-only adapter for the operator CLI's email argument. */
final class MariaDbInvitationRecipientLookup
{
    public function __construct(private readonly \mysqli $db, private readonly string $prefix)
    {
        if (strlen($prefix) > 32 || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException('Invalid table prefix.');
        }
    }

    public function userIdForEmail(string $email): ?int
    {
        $lookup = $this->db->prepare("SELECT user_id FROM `{$this->prefix}fm2_pilot_users` WHERE email=?");
        $lookup->bind_param('s', $email);
        $lookup->execute();
        $user = $lookup->get_result()->fetch_assoc();
        return $user === null ? null : (int) $user['user_id'];
    }
}
