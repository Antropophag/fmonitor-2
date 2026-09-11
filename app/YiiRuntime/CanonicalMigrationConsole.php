<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime;

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\DatabaseUnavailable;
use FMonitor2\InstallationProcess\IdentityAccessDefinitionSchemaMigration;
use FMonitor2\InstallationProcess\MariaDbSchemaInspector;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;

final class CanonicalMigrationConsole
{
    /** @return array{exitCode:int,result:array<string,mixed>} */
    public static function run(): array
    {
        $environment = self::environment();
        if ($environment === null) return self::outcome(64, 'CONFIGURATION_INVALID');

        \mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connection = null;
        try {
            try {
                $connection = @new \mysqli($environment['FMONITOR_DB_HOST'], $environment['FMONITOR_DB_USER'], $environment['FMONITOR_DB_PASSWORD'], $environment['FMONITOR_DB_NAME'], (int) $environment['FMONITOR_DB_PORT']);
                if (!$connection->set_charset('utf8mb4')) throw new \RuntimeException('Character set was not confirmed.');
            } catch (\Throwable) {
                return self::outcome(69, 'DATABASE_UNAVAILABLE');
            }

            $tablePrefix = $environment['FMONITOR_PROCESS_TABLE_PREFIX'];
            $migrations = ProductionPilotMigrationCatalogue::migrations();
            $databasePreflight = static function () use ($connection, $tablePrefix): int {
                try {
                    IdentityAccessDefinitionSchemaMigration::databaseCollation($connection);
                } catch (DatabaseUnavailable $error) {
                    foreach (IdentityAccessDefinitionSchemaMigration::tables() as $identityTable) {
                        if (MariaDbSchemaInspector::tableExists($connection, $tablePrefix . $identityTable)) throw $error;
                    }
                    throw new \RuntimeException('Canonical database defaults are incompatible.', 0, $error);
                }
                foreach (IdentityAccessDefinitionSchemaMigration::tables() as $identityTable) {
                    if (MariaDbSchemaInspector::tableExists($connection, $tablePrefix . $identityTable)) return 6;
                }
                return 1;
            };
            return CanonicalMigrationApplication::run(connection: $connection, tablePrefix: $tablePrefix, migrations: $migrations, databasePreflight: $databasePreflight);
        } catch (\Throwable) {
            return self::outcome(70, 'SOFTWARE_ERROR');
        } finally {
            if ($connection instanceof \mysqli) {
                try { $connection->close(); } catch (\Throwable) {}
            }
        }
    }

    /** @return null|array<string,string> */
    private static function environment(): ?array
    {
        $environment = [];
        foreach (['FMONITOR_DB_HOST', 'FMONITOR_DB_PORT', 'FMONITOR_DB_NAME', 'FMONITOR_DB_USER', 'FMONITOR_DB_PASSWORD', 'FMONITOR_PROCESS_TABLE_PREFIX'] as $name) {
            $value = \getenv($name);
            if ($value === false) return null;
            $environment[$name] = $value;
        }
        $port = $environment['FMONITOR_DB_PORT'];
        $prefix = $environment['FMONITOR_PROCESS_TABLE_PREFIX'];
        if ($environment['FMONITOR_DB_HOST'] === '' || $environment['FMONITOR_DB_NAME'] === '' || $environment['FMONITOR_DB_USER'] === ''
            || \preg_match('/^[1-9][0-9]{0,4}$/D', $port) !== 1 || (int) $port > 65535
            || \preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1) return null;
        return $environment;
    }

    /** @return array{exitCode:int,result:array{ok:false,reason:string}} */
    private static function outcome(int $exitCode, string $reason): array
    {
        return ['exitCode' => $exitCode, 'result' => ['ok' => false, 'reason' => $reason]];
    }
}
