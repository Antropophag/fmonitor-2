<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Iterative value grammar; nested dictionaries retain Name-key/value pairs. */
final class FMonitorPdfValue
{
    public static function end(string $view, int $cursor): int
    {
        $stack = ''; $depth = 0; $keys = '';
        while (($token = FMonitorPdfLexical::next($view, $cursor)) !== null) {
            $value = $token['value']; $kind = $token['kind'];
            if ($depth > 0) {
                $state = $stack[$depth - 1];
                if ($kind === 'delimiter' && (($state === 'd' && $value === '>>') || ($state === 'a' && $value === ']'))) {
                    if (--$depth === 0) return $cursor;
                    continue;
                }
                if ($state === 'd') {
                    if ($kind !== 'name') throw new \UnexpectedValueException('Invalid nested dictionary key.');
                    $index = array_search($value, FMonitorPdfDictionary::STRUCTURAL_KEYS, true);
                    if ($index !== false) {
                        $position = 2 * ($depth - 1) + intdiv($index, 8); $bit = 1 << ($index % 8);
                        $mask = ord($keys[$position]);
                        if ($mask & $bit) throw new \UnexpectedValueException('Duplicate nested structural key.');
                        $keys[$position] = chr($mask | $bit);
                    }
                    $stack[$depth - 1] = 'v'; continue;
                }
                if ($state === 'v') $stack[$depth - 1] = 'd';
            }
            if ($kind === 'delimiter') {
                if ($value !== '<<' && $value !== '[') throw new \UnexpectedValueException('Missing PDF container value.');
                $stack[$depth] = $value === '<<' ? 'd' : 'a';
                $keys[2 * $depth] = "\0"; $keys[2 * $depth + 1] = "\0"; ++$depth; continue;
            }
            if ($kind === 'word') $cursor = self::scalarEnd($view, $cursor, $value);
            elseif ($kind !== 'name' && $kind !== 'string') throw new \UnexpectedValueException('Invalid PDF scalar.');
            if ($depth === 0) return $cursor;
        }
        throw new \UnexpectedValueException('Incomplete PDF value.');
    }

    private static function scalarEnd(string $view, int $cursor, string $value): int
    {
        if (in_array($value, ['true','false','null'], true)) return $cursor;
        if (!preg_match('/^[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)$/D', $value)) throw new \UnexpectedValueException('Invalid PDF scalar.');
        if (!ctype_digit($value)) return $cursor;
        $lookahead = $cursor; $second = FMonitorPdfLexical::next($view, $lookahead);
        if (($second['kind'] ?? '') !== 'word' || !ctype_digit($second['value'])) return $cursor;
        $third = FMonitorPdfLexical::next($view, $lookahead);
        return ($third['kind'] ?? '') === 'word' && $third['value'] === 'R' ? $lookahead : $cursor;
    }
}
