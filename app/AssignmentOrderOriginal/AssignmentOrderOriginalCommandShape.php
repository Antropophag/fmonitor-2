<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

final class AssignmentOrderOriginalCommandShape
{
    private const WHITE_SPACE = '\x{0009}-\x{000D}\x{0020}\x{0085}\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}';

    public static function valid(SubmitAssignmentOrderOriginalCommand $command): bool
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $command->requestId) !== 1
            || $command->installationCaseId < 1 || $command->assignmentOrderId < 1 || $command->actorUserId < 1
            || !self::date($command->documentDate) || self::text($command->upload->originalFilename, 255) === null) {
            return false;
        }
        if ($command->mode === AssignmentOrderOriginalMode::INITIAL) {
            return $command->rootOriginalId === null && $command->targetRevisionId === null
                && $command->expectedCurrentRevisionId === null && $command->correctionReason === null;
        }
        return self::validId($command->rootOriginalId) && self::validId($command->targetRevisionId)
            && self::validId($command->expectedCurrentRevisionId) && self::reason($command->correctionReason) !== null;
    }

    public static function validId(?string $value): bool
    {
        return $value !== null && preg_match('/^[\x21-\x7E]{1,80}$/D', $value) === 1
            && !str_contains($value, '/') && !str_contains($value, '\\');
    }

    public static function reason(?string $value): ?string
    {
        return $value === null ? null : self::text($value, 500);
    }

    private static function date(string $value): bool
    {
        return preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $value, $parts) === 1
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }

    private static function text(string $value, int $maximumCodePoints): ?string
    {
        if (preg_match('//u', $value) !== 1
            || preg_match('/[\x{0000}-\x{0008}\x{000B}-\x{000C}\x{000E}-\x{001F}\x{007F}-\x{009F}]/u', $value) !== 0) {
            return null;
        }
        // Validate raw controls first, so trim cannot hide forbidden edge bytes.
        $normalized = preg_replace('/\A['.self::WHITE_SPACE.']+|['.self::WHITE_SPACE.']+\z/u', '', $value);
        if ($normalized === null) {
            return null;
        }
        $length = preg_match_all('/./us', $normalized);
        return $length !== false && $length >= 1 && $length <= $maximumCodePoints ? $normalized : null;
    }
}
