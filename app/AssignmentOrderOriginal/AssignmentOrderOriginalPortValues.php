<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/AssignmentOrderOriginalDataScalar.php';

/** Validates total clock and ID port outcomes before persistence work. */
final class AssignmentOrderOriginalPortValues
{
    public static function nowUtc(AssignmentOrderOriginalClock $clock): ?string
    {
        try {
            $value = $clock->nowUtc();
            return AssignmentOrderOriginalDataScalar::utc($value) ? $value : null;
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
            return AssignmentOrderOriginalCommandShape::validId($result->id) ? $result->id : null;
        }
        return null;
    }
}
