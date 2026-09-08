<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class AssignmentOrderSelectionSchemaValues
{
    public static function prefix(string $prefix): void
    {
        if (PHP_INT_SIZE !== 8 || strlen($prefix) > 25 || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException('Invalid selection schema migration configuration.');
        }
    }
    public static function unavailable(): DatabaseUnavailable { return new DatabaseUnavailable('Assignment order selection schema unavailable.'); }
    public static function require(bool $valid): void { if (!$valid) { throw new \DomainException('Invalid selection schema facts.'); } }
    public static function json(mixed $value): string { return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); }
    public static function decimal(mixed $value, bool $zero = false, string $max = AssignmentOrderIdentityRegistryValues::MAX): string
    {
        return AssignmentOrderIdentityRegistryValues::decimal($value, $zero, $max);
    }
    public static function text(string $value, int $max): void
    {
        self::require(preg_match('//u', $value) === 1 && preg_match('/^[\p{Z}\x{0009}-\x{000D}]|[\p{Z}\x{0009}-\x{000D}]$/u', $value) === 0);
        self::require($value !== '' && preg_match_all('/./us', $value) <= $max);
    }
    public static function date(string $value): void
    {
        self::require(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $value) === 1);
        self::require((int)substr($value, 0, 4) >= 1000 && checkdate((int)substr($value, 5, 2), (int)substr($value, 8, 2), (int)substr($value, 0, 4)));
    }
    public static function instant(string $value, bool $seconds = false): \DateTimeImmutable
    {
        self::require(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{6}$/D', $value) === 1);
        self::date(substr($value, 0, 10));
        self::require((int)substr($value, 11, 2) <= 23 && (int)substr($value, 14, 2) <= 59 && (int)substr($value, 17, 2) <= 59);
        self::require(!$seconds || substr($value, -6) === '000000');
        return new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
    }
    public static function sourceInstant(string $value): void
    {
        self::require(strlen($value) <= 40 && preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(\.\d+)?(Z|[+-]\d{2}:\d{2})$/D', $value, $m) === 1);
        // The inherited validator supplies exact calendar/known-offset validation; precision is not rounded into history.
        AssignmentOrderIdentityRegistryValues::instant($m[1] . $m[3]);
    }
    public static function uuid(string $value): void { self::require(preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $value) === 1); }
}
