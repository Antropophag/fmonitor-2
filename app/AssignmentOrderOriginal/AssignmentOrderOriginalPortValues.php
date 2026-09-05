<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** Validates total clock and ID port outcomes before persistence work. */
final class AssignmentOrderOriginalPortValues
{
    public static function nowUtc(AssignmentOrderOriginalClock $clock): ?string
    {
        try {
            $value = $clock->nowUtc();
            if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$/D', $value) !== 1) {
                return null;
            }
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new \DateTimeZone('UTC'));
            $errors = \DateTimeImmutable::getLastErrors();
            return $date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
                && $date->format('Y-m-d\TH:i:s\Z') === $value ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function nextId(AssignmentOrderOriginalIdSource $source, bool $root): ?string
    {
        for ($attempt = 0; $attempt < 8; ++$attempt) {
            try {
                $result = $root ? $source->nextRootId() : $source->nextRevisionId();
            } catch (\Throwable) {
                return null;
            }
            if ($result->status === AssignmentOrderOriginalIdStatus::COLLISION) {
                continue;
            }
            if ($result->status !== AssignmentOrderOriginalIdStatus::GENERATED) {
                return null;
            }
            return $result->id !== null && $result->id !== '' ? $result->id : null;
        }
        return null;
    }
}
