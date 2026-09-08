<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class MariaDbAssignmentOrderSelectionSchemaSql
{
    public static function rows(\mysqli $db, string $sql, array $parameters = []): array
    {
        $statement = $db->prepare($sql); $result = null;
        if ($statement === false) { throw AssignmentOrderSelectionSchemaValues::unavailable(); }
        try {
            if ($statement->execute($parameters) !== true) { throw AssignmentOrderSelectionSchemaValues::unavailable(); }
            $result = $statement->get_result();
            if ($result === false) { throw AssignmentOrderSelectionSchemaValues::unavailable(); }
            return $result->fetch_all(MYSQLI_ASSOC);
        } finally {
            try { if ($result instanceof \mysqli_result) { $result->free(); } }
            finally { if ($statement->close() !== true) { throw AssignmentOrderSelectionSchemaValues::unavailable(); } }
        }
    }

    public static function one(\mysqli $db, string $sql, array $parameters = []): array
    {
        $rows = self::rows($db, $sql, $parameters);
        if (count($rows) !== 1) { throw AssignmentOrderSelectionSchemaValues::unavailable(); }
        return $rows[0];
    }

    public static function configuration(\mysqli $db): string
    {
        try { $row = self::one($db, 'SELECT @@in_transaction active,DATABASE() db,@@character_set_connection charset,@@tx_isolation isolation_level'); }
        catch (\Throwable) { throw AssignmentOrderSelectionSchemaValues::unavailable(); }
        if ((string)$row['active'] !== '0' || $row['charset'] !== 'utf8mb4' || $row['isolation_level'] !== 'REPEATABLE-READ'
            || !is_string($row['db']) || $row['db'] === '') { throw new \InvalidArgumentException('Invalid selection schema migration configuration.'); }
        return $row['db'];
    }
}
