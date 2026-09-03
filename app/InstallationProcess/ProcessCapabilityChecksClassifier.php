<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class ProcessCapabilityChecksClassifier
{
    /** @return array{state: 'v3'|'v4'|'v12', capabilityConstraint: string}|null */
    public static function inspect(\mysqli $connection, string $table): ?array
    {
        $statement = $connection->prepare('SELECT CONSTRAINT_NAME,CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY CONSTRAINT_NAME');
        $statement->bind_param('s', $table);
        $statement->execute();

        $capabilityCandidates = [];
        $engineerCandidates = [];
        foreach ($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $clause = (string) $row['CHECK_CLAUSE'];
            $version = self::capabilityVersion($clause);
            if ($version !== null) {
                $capabilityCandidates[] = [
                    'name' => (string) $row['CONSTRAINT_NAME'],
                    'version' => $version,
                ];
            } elseif (self::isEngineerPositionCheck($clause)) {
                $engineerCandidates[] = (string) $row['CONSTRAINT_NAME'];
            } else {
                return null;
            }
        }

        if (count($capabilityCandidates) !== 1 || count($engineerCandidates) !== 1) {
            return null;
        }

        $candidate = $capabilityCandidates[0];
        if ($candidate['version'] === 'v4'
            && $candidate['name'] !== 'ck_fm2_process_user_capability'
        ) {
            return null;
        }

        return [
            'state' => $candidate['version'],
            'capabilityConstraint' => $candidate['name'],
        ];
    }

    /** @return 'v3'|'v4'|'v12'|null */
    private static function capabilityVersion(string $check): ?string
    {
        $check = self::stripOptionalWholeExpressionParentheses($check);
        if ($check === null
            || preg_match('/^\s*`?capability`?\s+(?i:in)\s*\((.*)\)\s*$/sD', $check, $match) !== 1
        ) {
            return null;
        }

        $capabilities = [];
        foreach (explode(',', $match[1]) as $part) {
            if (preg_match("/^\\s*'([^']*)'\\s*$/sD", $part, $literal) !== 1) {
                return null;
            }
            $capabilities[] = $literal[1];
        }
        if (count(array_unique($capabilities, SORT_STRING)) !== count($capabilities)) {
            return null;
        }

        sort($capabilities, SORT_STRING);
        $v3 = ['assignment_order.prepare', 'construction_control_engineer'];
        $v4 = ['assignment_order.confirm_registration', 'assignment_order.prepare', 'construction_control_engineer', 'installation.open'];
        $v12 = ['assignment_order.confirm_registration','assignment_order.original.correct','assignment_order.original.storage.reconcile','assignment_order.original.upload','assignment_order.prepare','construction_control_engineer','installation.open'];
        sort($v3, SORT_STRING); sort($v4, SORT_STRING); sort($v12, SORT_STRING);

        return match ($capabilities) {
            $v3 => 'v3',
            $v4 => 'v4', $v12 => 'v12',
            default => null,
        };
    }

    private static function isEngineerPositionCheck(string $check): bool
    {
        $check = self::stripOptionalWholeExpressionParentheses($check);
        if ($check === null) {
            return false;
        }

        $capability = '`?capability`?';
        $position = '`?position_snapshot`?';
        $right = $position
            . '\s+(?i:is)\s+(?i:not)\s+(?i:null)\s+(?i:and)\s+'
            . '(?i:trim)\s*\(\s*' . $position . '\s*\)\s*<>\s*\'([^\']*)\'';
        $pattern = '/^\s*' . $capability . '\s*<>\s*\'([^\']*)\'\s+'
            . '(?i:or)\s+(?:' . $right . '|\(\s*' . $right . '\s*\))\s*$/sD';
        if (preg_match($pattern, $check, $match) !== 1) {
            return false;
        }

        $literals = array_values(array_filter(
            [$match[1] ?? null, $match[2] ?? null, $match[3] ?? null],
            static fn (mixed $value): bool => $value !== null,
        ));

        return $literals === ['construction_control_engineer', ''];
    }

    private static function stripOptionalWholeExpressionParentheses(string $expression): ?string
    {
        $expression = trim($expression);
        if (!str_starts_with($expression, '(')) {
            return $expression;
        }

        $depth = 0;
        $quoted = false;
        $length = strlen($expression);
        for ($index = 0; $index < $length; $index++) {
            $character = $expression[$index];
            if ($character === "'") {
                if ($quoted && $index + 1 < $length && $expression[$index + 1] === "'") {
                    $index++;
                    continue;
                }
                $quoted = !$quoted;
                continue;
            }
            if ($quoted) {
                continue;
            }
            if ($character === '(') {
                $depth++;
            } elseif ($character === ')') {
                $depth--;
                if ($depth === 0) {
                    if ($index !== $length - 1) {
                        return null;
                    }

                    return trim(substr($expression, 1, -1));
                }
                if ($depth < 0) {
                    return null;
                }
            }
        }

        return null;
    }
}
