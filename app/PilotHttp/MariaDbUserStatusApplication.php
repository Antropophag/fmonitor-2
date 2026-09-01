<?php

declare(strict_types=1);

namespace FMonitor2\PilotHttp;

use DateTimeImmutable;
use DateTimeZone;
use FMonitor2\InstallationProcess\IdentityAccessSchemaMigration;
use mysqli;
use Throwable;

/** Public application seam for the existing block/unblock state transition. */
final class MariaDbUserStatusApplication
{
    public static function change(mysqli $db, string $prefix, int $actorId, int $userId, string $action): int
    {
        if (!IdentityAccessSchemaMigration::isCompleteCompatible($db, $prefix)) {
            return 400;
        }
        if (!AccessPolicy::grants(AccessPolicy::forUser($db, $prefix, $actorId), 'access.administer')) {
            return 403;
        }

        $db->begin_transaction();
        try {
            $target = self::lockedUser($db, $prefix, $userId);
            if (!self::allowsTransition($target, $action)) {
                return self::reject($db, 400);
            }
            if ($action === 'block' && self::isLastActiveSuperadministrator($db, $prefix, $userId)) {
                return self::reject($db, 400);
            }

            self::persistTransition($db, $prefix, $actorId, $userId, $action);
            $db->commit();
            return 303;
        } catch (Throwable $error) {
            try {
                $db->rollback();
            } catch (Throwable) {
            }
            throw $error;
        }
    }

    private static function lockedUser(mysqli $db, string $prefix, int $userId): ?array
    {
        $query = $db->prepare("SELECT status,activation_state FROM `{$prefix}fm2_pilot_users` WHERE user_id=? FOR UPDATE");
        $query->bind_param('i', $userId);
        $query->execute();
        $row = $query->get_result()->fetch_assoc();
        return \is_array($row) ? $row : null;
    }

    private static function allowsTransition(?array $target, string $action): bool
    {
        if ($target === null) {
            return false;
        }
        if ($action === 'block') {
            return (int) $target['status'] === 1 && $target['activation_state'] === 'active';
        }
        return $action === 'unblock' && $target['activation_state'] === 'blocked';
    }

    private static function persistTransition(mysqli $db, string $prefix, int $actorId, int $userId, string $action): void
    {
        $nextStatus = $action === 'block' ? 0 : 1;
        $nextState = $action === 'block' ? 'blocked' : 'active';
        $event = $action === 'block' ? 'user_blocked' : 'user_unblocked';
        $now = (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')))->format(DATE_ATOM);

        $update = $db->prepare("UPDATE `{$prefix}fm2_pilot_users` SET status=?,activation_state=?,session_version=session_version+1,source_updated_at=? WHERE user_id=?");
        $update->bind_param('issi', $nextStatus, $nextState, $now, $userId);
        $update->execute();

        $insert = $db->prepare("INSERT INTO `{$prefix}fm2_pilot_user_status_events`(user_id,action,occurred_at,actor_user_id) VALUES(?,?,?,?)");
        $insert->bind_param('issi', $userId, $event, $now, $actorId);
        $insert->execute();
    }

    private static function isLastActiveSuperadministrator(mysqli $db, string $prefix, int $userId): bool
    {
        $membership = $db->prepare("SELECT COUNT(*) n FROM `{$prefix}fm2_pilot_user_roles` ur JOIN `{$prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id WHERE ur.user_id=? AND r.code='superadministrator'");
        $membership->bind_param('i', $userId);
        $membership->execute();
        if ((int) $membership->get_result()->fetch_assoc()['n'] === 0) {
            return false;
        }

        $active = $db->query("SELECT COUNT(DISTINCT u.user_id) n FROM `{$prefix}fm2_pilot_users` u JOIN `{$prefix}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id WHERE u.status=1 AND u.activation_state='active' AND r.code='superadministrator'");
        return (int) $active->fetch_assoc()['n'] <= 1;
    }

    private static function reject(mysqli $db, int $status): int
    {
        $db->rollback();
        return $status;
    }
}
