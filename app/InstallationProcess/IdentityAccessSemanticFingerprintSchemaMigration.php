<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Removes presentation-only names while retaining complete index/FK structure. */
final class IdentityAccessSemanticFingerprintSchemaMigration
{
    public static function indexes(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $name = (string) $row['INDEX_NAME'];
            $category = $name === 'PRIMARY' ? 'PRIMARY' : ((int) $row['NON_UNIQUE'] === 0 ? 'UNIQUE' : 'SECONDARY');
            $groups[$name]['head'] = $category . '|' . $row['INDEX_TYPE'];
            $groups[$name]['columns'][(int) $row['SEQ_IN_INDEX']] = (string) $row['COLUMN_NAME'];
        }
        return self::sorted($groups);
    }

    public static function indexSignatures(array $signatures): array
    {
        $groups = [];
        foreach ($signatures as $signature) {
            [$nonUnique, $name, $sequence, $column, $type] = explode('|', $signature, 5);
            $category = $name === 'PRIMARY' ? 'PRIMARY' : ((int) $nonUnique === 0 ? 'UNIQUE' : 'SECONDARY');
            $groups[$name]['head'] = $category . '|' . $type;
            $groups[$name]['columns'][(int) $sequence] = $column;
        }
        return self::sorted($groups);
    }

    public static function foreignKeys(array $rows): array
    {
        $groups = [];
        foreach ($rows as $position => $row) {
            $name = (string) $row['CONSTRAINT_NAME'];
            $groups[$name]['head'] = $row['REFERENCED_TABLE_NAME'] . '|' . $row['DELETE_RULE'] . '|' . $row['UPDATE_RULE'];
            $groups[$name]['columns'][$position] = $row['COLUMN_NAME'] . '>' . $row['REFERENCED_COLUMN_NAME'];
        }
        return self::sorted($groups);
    }

    public static function foreignKeySignatures(array $signatures): array
    {
        $groups = [];
        foreach ($signatures as $position => $signature) {
            [$name, $column, $target, $targetColumn, $delete, $update] = explode('|', $signature, 6);
            $groups[$name]['head'] = $target . '|' . $delete . '|' . $update;
            $groups[$name]['columns'][$position] = $column . '>' . $targetColumn;
        }
        return self::sorted($groups);
    }

    private static function sorted(array $groups): array
    {
        $structures = [];
        foreach ($groups as $group) {
            ksort($group['columns'], SORT_NUMERIC);
            $structures[] = $group['head'] . '|' . implode(',', $group['columns']);
        }
        sort($structures, SORT_STRING);
        return $structures;
    }
}
