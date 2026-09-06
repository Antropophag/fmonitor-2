<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Reads direct dictionary entries, without flattening nested values. */
final class FMonitorPdfDictionary
{
    public const STRUCTURAL_KEYS = ['Type','Length','Filter','N','First','Size','W','Index','Root','Prev','Encrypt','Count','Kids','Parent','Pages'];

    public static function entries(string $view): array
    {
        $cursor = 0; $first = FMonitorPdfLexical::next($view, $cursor);
        if (($first['kind'] ?? null) !== 'delimiter' || $first['value'] !== '<<') throw new \UnexpectedValueException('PDF dictionary required.');
        $entries = [];
        while (($key = FMonitorPdfLexical::next($view, $cursor)) !== null) {
            if ($key['kind'] === 'delimiter' && $key['value'] === '>>') {
                if (FMonitorPdfLexical::next($view, $cursor) !== null) throw new \UnexpectedValueException('Extra PDF dictionary values.');
                return $entries;
            }
            if ($key['kind'] !== 'name') throw new \UnexpectedValueException('Invalid PDF dictionary key.');
            $start = $cursor; $cursor = self::valueEnd($view, $cursor);
            if (in_array($key['value'], self::STRUCTURAL_KEYS, true)) {
                if (array_key_exists($key['value'], $entries)) throw new \UnexpectedValueException('Duplicate PDF structural key.');
                $entries[$key['value']] = trim(substr($view, $start, $cursor - $start));
            }
        }
        throw new \UnexpectedValueException('Incomplete PDF dictionary.');
    }

    public static function valueEnd(string $view, int $cursor): int
    { return FMonitorPdfValue::end($view, $cursor); }

    public static function number(string $value, int $maximum): ?int
    {
        if ($value === '' || !ctype_digit($value)) return null;
        $decimal = ltrim($value, '0'); if ($decimal === '') $decimal = '0'; $cap = (string) $maximum;
        if (strlen($decimal) > strlen($cap) || (strlen($decimal) === strlen($cap) && strcmp($decimal, $cap) > 0)) return null;
        return (int) $decimal;
    }

    public static function uint(array $entries, string $key, int $maximum): ?int
    { return isset($entries[$key]) ? self::number($entries[$key], $maximum) : null; }

    public static function name(?string $value): ?string
    {
        if ($value === null) return null; $cursor = 0; $token = FMonitorPdfLexical::next($value, $cursor);
        return ($token['kind'] ?? '') === 'name' && FMonitorPdfLexical::next($value, $cursor) === null ? $token['value'] : null;
    }

    public static function reference(?string $value): ?string
    {
        if ($value === null || preg_match('/^([0-9]+) +([0-9]+) +R$/D', $value, $m) !== 1) return null;
        $number = self::number($m[1], 100000); $generation = self::number($m[2], 65535);
        return $number !== null && $number > 0 && $generation !== null ? $number.' '.$generation : null;
    }

    /** @return ?list<string> */
    public static function arrayValues(string $value): ?array
    {
        $cursor = 0; $first = FMonitorPdfLexical::next($value, $cursor);
        if (($first['kind'] ?? '') !== 'delimiter' || $first['value'] !== '[') return null; $values = [];
        while (true) {
            $start = $cursor; $peek = FMonitorPdfLexical::next($value, $cursor);
            if ($peek === null) return null;
            if ($peek['kind'] === 'delimiter' && $peek['value'] === ']') return FMonitorPdfLexical::next($value, $cursor) === null ? $values : null;
            $cursor = self::valueEnd($value, $start); $values[] = trim(substr($value, $start, $cursor - $start));
        }
    }
}
