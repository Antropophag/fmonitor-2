<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Shared lossless metadata representation; no persistence or clock. */
final class AssignmentOrderIdentityRegistryValues
{
    public const MAX = '9223372036854775807';
    public const EXHAUSTED = '9223372036854775808';

    public static function prefix(string $prefix): void
    {
        if (strlen($prefix)>25 || preg_match('/^[A-Za-z0-9_]*$/D',$prefix)!==1) {
            throw new \InvalidArgumentException('Invalid registry migration configuration.');
        }
    }

    public static function unavailable(): DatabaseUnavailable
    {
        return new DatabaseUnavailable('Assignment order identity registry unavailable.');
    }

    public static function decimal(mixed $value, bool $zero = false, string $max = self::MAX): string
    {
        $value = is_int($value) ? (string)$value : $value;
        if (!is_string($value) || preg_match('/^(0|[1-9][0-9]*)$/D',$value)!==1
            || (!$zero && $value==='0') || self::compare($value,$max)>0) {
            throw new \DomainException('Invalid registry metadata value.');
        }
        return $value;
    }

    public static function compare(string $left, string $right): int
    {
        return strlen($left)<=>strlen($right) ?: strcmp($left,$right);
    }

    public static function increment(string $value): string
    {
        for ($i=strlen($value)-1;$i>=0;$i--) {
            if ($value[$i]!=='9') { $value[$i]=chr(ord($value[$i])+1); return $value; }
            $value[$i]='0';
        }
        return '1'.$value;
    }

    public static function instant(string $raw): string
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(\.\d{1,6})?(Z|[+-]\d{2}:\d{2})$/D',$raw,$m)!==1
            || $m[3]==='-00:00') { throw new \DomainException('Invalid historical instant.'); }
        if ($m[3]!=='Z' && ((int)substr($m[3],1,2)>14 || (int)substr($m[3],4,2)>59
            || ((int)substr($m[3],1,2)===14 && substr($m[3],4,2)!=='00'))) {
            throw new \DomainException('Invalid historical offset.');
        }
        try { $date = new \DateTimeImmutable($raw); }
        catch (\Throwable) { throw new \DomainException('Invalid historical instant.'); }
        if ($date->format('Y-m-d\TH:i:s')!==$m[1]) { throw new \DomainException('Invalid historical calendar.'); }
        $utc=$date->setTimezone(new \DateTimeZone('UTC'));
        if ((int)$utc->format('Y')<1000 || (int)$utc->format('Y')>9999) { throw new \DomainException('Unrepresentable historical instant.'); }
        return $utc->format('Y-m-d\TH:i:s.u\Z');
    }
}
