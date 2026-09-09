<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Connection-scoped deployment exclusion; individual migrations retain their own locks. */
final class MariaDbMigrationLock
{
    public static function acquire(\mysqli $connection, string $tablePrefix): ?string
    {
        try {
            $database = $connection->query('SELECT DATABASE()')->fetch_column();
            if (!is_string($database) || $database === '') throw new \RuntimeException();
            $name = hash('sha256', $database . "\0" . $tablePrefix . "\0canonical-migrations");
            $statement = $connection->prepare('SELECT GET_LOCK(?, 0)');
            $statement->bind_param('s', $name);
            $statement->execute();
            $result = $statement->get_result()->fetch_column();
            $statement->close();
            if ((string) $result === '1') return $name;
            if ((string) $result === '0') return null;
            throw new \RuntimeException();
        } catch (\Throwable $error) {
            throw new DatabaseUnavailable('Migration lock unavailable.', 0, $error);
        }
    }

    public static function release(\mysqli $connection, string $name): void
    {
        $statement = $connection->prepare('SELECT RELEASE_LOCK(?)');
        $statement->bind_param('s', $name);
        $statement->execute();
        $released = $statement->get_result()->fetch_column();
        $statement->close();
        if ((string) $released !== '1') throw new \RuntimeException('Migration lock release unconfirmed.');
    }
}
