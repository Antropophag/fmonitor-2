<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Read adapter used by the single registry migration owner. */
final class MariaDbAssignmentOrderIdentityRegistrySql
{
    public static function rows(\mysqli $db, string $sql, array $parameters = []): array
    {
        $statement=$db->prepare($sql);
        if ($statement===false) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
        try {
            if ($statement->execute($parameters)!==true) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
            $result=$statement->get_result();
            if ($result===false) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
            return $result->fetch_all(MYSQLI_ASSOC);
        } finally { $statement->close(); }
    }

    public static function one(\mysqli $db, string $sql, array $parameters = []): array
    {
        $rows=self::rows($db,$sql,$parameters);
        if (count($rows)!==1) { throw AssignmentOrderIdentityRegistryValues::unavailable(); }
        return $rows[0];
    }
}
