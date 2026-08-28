<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class MariaDbSchemaInspector
{
    public static function validateTablePrefix(string $tablePrefix): void
    {
        if (preg_match('/^[A-Za-z0-9_]*$/D', $tablePrefix) !== 1) {
            throw new \InvalidArgumentException('Invalid table prefix.');
        }
    }

    public static function tableExists(\mysqli $connection, string $table): bool
    {
        $statement = $connection->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $statement->bind_param('s', $table);
        $statement->execute();

        return $statement->get_result()->fetch_row() !== null;
    }

    public static function tableProperties(\mysqli $connection, string $table): ?array
    {
        $statement = $connection->prepare('SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $statement->bind_param('s', $table);
        $statement->execute();

        return $statement->get_result()->fetch_assoc();
    }

    public static function columns(\mysqli $connection, string $table): array
    {
        $statement = $connection->prepare('SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,EXTRA,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');
        $statement->bind_param('s', $table);
        $statement->execute();

        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function indexes(\mysqli $connection, string $table): array
    {
        $statement = $connection->prepare("SELECT INDEX_NAME,NON_UNIQUE,GROUP_CONCAT(CONCAT(COLUMN_NAME,':',COALESCE(SUB_PART,'FULL'),':',COALESCE(COLLATION,'NULL'),':',IGNORED) ORDER BY SEQ_IN_INDEX) AS COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? GROUP BY INDEX_NAME,NON_UNIQUE");
        $statement->bind_param('s', $table);
        $statement->execute();

        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** @return list<string> */
    public static function checks(\mysqli $connection, string $table): array
    {
        $statement = $connection->prepare('SELECT CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $statement->bind_param('s', $table);
        $statement->execute();
        $checks = array_map(
            static fn (array $row): string => str_replace(['`', ' '], '', strtolower((string) $row['CHECK_CLAUSE'])),
            $statement->get_result()->fetch_all(MYSQLI_ASSOC),
        );
        sort($checks);

        return $checks;
    }

    public static function foreignKeys(\mysqli $connection, string $table): array
    {
        $statement = $connection->prepare("SELECT k.COLUMN_NAME,k.REFERENCED_TABLE_SCHEMA,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.DELETE_RULE,DATABASE() AS CURRENT_SCHEMA FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.TABLE_NAME=k.TABLE_NAME AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME=? AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.COLUMN_NAME");
        $statement->bind_param('s', $table);
        $statement->execute();

        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
