<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class YiiObjectCardValues
{
    public static function date(mixed $value): ?string
    {
        if (!is_string($value) || preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T].*)?$/D', $value, $parts) !== 1 || $parts[1] === '0000' || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            return null;
        }
        return $parts[1] . '-' . $parts[2] . '-' . $parts[3];
    }

    public static function positiveId(mixed $value): ?int
    {
        if (!is_int($value) && (!is_string($value) || preg_match('/^[1-9][0-9]*$/D', $value) !== 1)) {
            return null;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }

    public static function rfc3339(mixed $value): bool
    {
        if (!is_string($value) || preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(?:\.\d+)?(Z|[+-]\d{2}:\d{2})$/D', $value, $parts) !== 1) {
            return false;
        }
        $canonical = $parts[1] . ($parts[2] === 'Z' ? '+00:00' : $parts[2]);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:sP', $canonical);
        $errors = \DateTimeImmutable::getLastErrors();
        return $date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) && $date->format('Y-m-d\TH:i:sP') === $canonical;
    }
}
