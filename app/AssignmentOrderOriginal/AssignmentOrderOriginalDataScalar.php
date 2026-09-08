<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Shared lossless grammar for persisted and public port values. */
final class AssignmentOrderOriginalDataScalar
{
    public static function uuid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $value) === 1;
    }

    public static function date(?string $value): bool
    {
        return $value !== null && preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $value, $parts) === 1
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }

    public static function utc(?string $value): bool
    {
        if ($value === null || preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$/D', $value) !== 1) return false;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new \DateTimeZone('UTC'));
        $errors = \DateTimeImmutable::getLastErrors();
        return $date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d\TH:i:s\Z') === $value;
    }

    public static function hash(?string $value): bool
    {
        return $value !== null && preg_match('/^[0-9a-f]{64}$/D', $value) === 1;
    }

    public static function integer(mixed $value, int $minimum = 1, int $maximum = PHP_INT_MAX): ?int
    {
        if (is_int($value)) return $value >= $minimum && $value <= $maximum ? $value : null;
        if (!is_string($value) || preg_match('/^(0|[1-9][0-9]*)$/D', $value) !== 1) return null;
        $bound = (string) $maximum;
        if (strlen($value) > strlen($bound) || (strlen($value) === strlen($bound) && strcmp($value, $bound) > 0)) return null;
        $number = (int) $value;
        return $number >= $minimum ? $number : null;
    }

    public static function composition(?string $value, int $orderId): bool
    {
        return $value !== null && strlen($value) <= 160
            && preg_match('/^composition-'.preg_quote((string) $orderId, '/').'-v([1-9][0-9]*)$/D', $value, $parts) === 1
            && self::integer($parts[1]) !== null;
    }

    public static function sqlUtc(mixed $value): ?string
    {
        if (!is_string($value) || preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})(?:\.000000)?$/D', $value, $parts) !== 1) return null;
        $utc = $parts[1].'T'.$parts[2].'Z';
        return self::utc($utc) ? $utc : null;
    }

    public static function terminalReason(AssignmentOrderOriginalStatus $status, ?AssignmentOrderOriginalReason $reason): bool
    {
        $allowed = match ($status) {
            AssignmentOrderOriginalStatus::REJECTED => ['authorization_denied', 'order_not_found', 'composition_not_confirmed',
                'invalid_composition', 'file_too_large', 'not_pdf', 'invalid_pdf', 'unsafe_pdf', 'future_document_date', 'no_changes'],
            AssignmentOrderOriginalStatus::CONFLICT => ['semantic_collision', 'stale_revision', 'target_not_found', 'target_not_current', 'initial_already_exists'],
            default => [],
        };
        return $reason !== null && in_array($reason->value, $allowed, true);
    }
}
