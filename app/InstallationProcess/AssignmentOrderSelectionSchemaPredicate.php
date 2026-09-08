<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Closed metadata codec: preserve boolean grouping, order, operators and quoted literals. */
final class AssignmentOrderSelectionSchemaPredicate
{
    public static function ast(string $sql): array
    {
        $tokens = []; $offset = 0;
        $pattern = "~\\G(?:\\s+|('(?:''|\\\\.|[^'])*'|`[^`]*`|[A-Za-z_][A-Za-z0-9_]*|[0-9]+|<=|>=|<>|!=|[(),=<>+*/-]))~";
        while ($offset < strlen($sql)) {
            AssignmentOrderSelectionSchemaValues::require(preg_match($pattern, $sql, $m, 0, $offset) === 1);
            $offset += strlen($m[0]); if (isset($m[1]) && $m[1] !== '') { $tokens[] = $m[1]; }
        }
        return self::node($tokens);
    }

    private static function node(array $tokens): array
    {
        while (count($tokens) > 1 && $tokens[0] === '(') {
            $depth = 0; $end = null;
            foreach ($tokens as $i => $value) {
                $depth += (int)($value === '(') - (int)($value === ')');
                if ($depth === 0) { $end = $i; break; }
            }
            if ($end !== count($tokens) - 1) { break; }
            $tokens = array_slice($tokens, 1, -1);
        }
        foreach (['or', 'and'] as $operator) {
            $depth = 0; $between = false; $parts = []; $start = 0;
            foreach ($tokens as $i => $value) {
                $depth += (int)($value === '(') - (int)($value === ')');
                AssignmentOrderSelectionSchemaValues::require($depth >= 0);
                if ($depth !== 0) { continue; }
                $lower = strtolower($value);
                if ($lower === 'between') { $between = true; }
                elseif ($lower === 'and' && $between) { $between = false; }
                elseif ($lower === $operator) { $parts[] = self::node(array_slice($tokens, $start, $i - $start)); $start = $i + 1; }
            }
            AssignmentOrderSelectionSchemaValues::require($depth === 0);
            if ($parts !== []) { return [$operator, ...$parts, self::node(array_slice($tokens, $start))]; }
        }
        AssignmentOrderSelectionSchemaValues::require($tokens !== []);
        $atom = '';
        foreach ($tokens as $value) {
            if ($value === '(' || $value === ')') { continue; }
            $atom .= str_starts_with($value, "'") ? $value : strtolower(trim($value, '`'));
        }
        return ['atom', $atom];
    }
}
