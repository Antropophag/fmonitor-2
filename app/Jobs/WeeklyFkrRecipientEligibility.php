<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

final class WeeklyFkrRecipientEligibility
{
    public static function eligible(?array $recipient, bool $requireObjectsRead): bool
    {
        if (!is_array($recipient) || ($recipient['active'] ?? false) !== true
            || ($recipient['role'] ?? null) !== 'fkr_manager'
            || filter_var($recipient['email'] ?? '', FILTER_VALIDATE_EMAIL) === false) return false;
        return !$requireObjectsRead || in_array('objects.read', $recipient['permissions'] ?? [], true);
    }
}
