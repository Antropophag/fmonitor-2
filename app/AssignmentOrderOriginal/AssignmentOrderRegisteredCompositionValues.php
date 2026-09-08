<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** Targeted immutable source grammar; never performs global readiness or repair. */
final class AssignmentOrderRegisteredCompositionValues
{
    public static function prefix(string $prefix): void
    {
        if (PHP_INT_SIZE !== 8 || preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException('Invalid registered composition reader configuration.');
        }
    }
    public static function require(bool $valid): void { if (!$valid) { AssignmentOrderOriginalSql::fail(); } }
    public static function integer(mixed $value, int $max = PHP_INT_MAX): int
    {
        $id = AssignmentOrderOriginalDataScalar::integer($value, 1, $max); self::require($id !== null); return $id;
    }
    public static function text(string $value, int $max): void
    {
        self::require($value !== '' && preg_match('//u', $value) === 1);
        self::require(preg_match('/^[\p{Z}\x{0009}-\x{000D}]|[\p{Z}\x{0009}-\x{000D}]$/u', $value) === 0 && preg_match_all('/./us', $value) <= $max);
    }
    public static function instant(string $value): \DateTimeImmutable
    {
        self::require(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/D', $value) === 1
            && AssignmentOrderOriginalDataScalar::date(substr($value, 0, 10)) && (int)substr($value, 0, 4) >= 1000
            && (int)substr($value, 11, 2) <= 23 && (int)substr($value, 14, 2) <= 59 && (int)substr($value, 17, 2) <= 59);
        return new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
    }
    public static function sourceInstant(string $value): void
    {
        self::require(strlen($value) <= 40 && preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})(?:\.\d+)?(Z|[+-]\d{2}:\d{2})$/D', $value, $m) === 1);
        self::instant($m[1] . ' ' . $m[2] . '.000000');
        self::require($m[3] !== '-00:00');
        if ($m[3] !== 'Z') {
            $hour = (int)substr($m[3], 1, 2); $minute = (int)substr($m[3], 4, 2);
            self::require($hour <= 14 && $minute <= 59 && ($hour < 14 || $minute === 0));
        }
    }
    public static function result(int $case, int $order, int $version, int $engineer, array $ids): AssignmentOrderCompositionSnapshot
    {
        self::require($ids !== []); $identity = 'composition-' . $order . '-v' . $version;
        $json = json_encode(['caseId'=>$case,'compositionIdentity'=>$identity,'engineerUserId'=>$engineer,'installers'=>$ids,'orderId'=>$order], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return new AssignmentOrderCompositionSnapshot(AssignmentOrderCompositionLookupStatus::FOUND, $case, $order, $identity, hash('sha256', $json), $ids, $engineer);
    }
}
