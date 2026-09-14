<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

use mysqli;
use Throwable;

final class MariaDbLocalRuntimeAccountSchemaMigration
{
    /** @return array{reason:string,exitCode:int} */
    public static function execute(): array
    {
        $environment = self::environment();
        if ($environment === null) {
            return ['reason' => 'LOCAL_CONFIG_INVALID', 'exitCode' => 64];
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $database = new mysqli(
                $environment['host'],
                $environment['migrationUser'],
                $environment['migrationPassword'],
                '',
                $environment['port'],
            );
            $database->set_charset('utf8mb4');
        } catch (Throwable) {
            return ['reason' => 'LOCAL_DATABASE_UNAVAILABLE', 'exitCode' => 69];
        }

        try {
            $account = "'" . $database->real_escape_string($environment['runtimeUser']) . "'@'%'";
            $schema = $database->real_escape_string($environment['database']);
            $grantee = "'" . $database->real_escape_string("'{$environment['runtimeUser']}'@'%'") . "'";
            $exists = (int) $database->query(
                "SELECT COUNT(*) FROM mysql.user WHERE User='" . $database->real_escape_string($environment['runtimeUser']) . "' AND Host='%'"
            )->fetch_column();

            if ($exists === 0) {
                $password = "'" . $database->real_escape_string($environment['runtimePassword']) . "'";
                $database->query("CREATE USER {$account} IDENTIFIED BY {$password}");
                $database->query("GRANT SELECT, INSERT, UPDATE, DELETE ON `{$schema}`.* TO {$account}");
            } elseif (!self::hasExactPrivileges($database, $grantee, $environment['database'])) {
                return ['reason' => 'LOCAL_DB_ACCOUNT_MISMATCH', 'exitCode' => 65];
            }

            if (!self::hasExactPrivileges($database, $grantee, $environment['database'])) {
                return ['reason' => 'LOCAL_DB_ACCOUNT_MISMATCH', 'exitCode' => 65];
            }
        } catch (Throwable) {
            return ['reason' => 'LOCAL_DATABASE_UNAVAILABLE', 'exitCode' => 69];
        } finally {
            $database->close();
        }

        return ['reason' => 'RUNTIME_DB_ACCOUNT_READY', 'exitCode' => 0];
    }

    /** @return array{host:string,port:int,database:string,runtimeUser:string,runtimePassword:string,migrationUser:string,migrationPassword:string}|null */
    private static function environment(): ?array
    {
        $values = [];
        foreach (['FMONITOR_DB_HOST', 'FMONITOR_DB_PORT', 'FMONITOR_DB_NAME', 'FMONITOR_DB_USER', 'FMONITOR_DB_PASSWORD', 'FMONITOR_MIGRATION_DB_USER', 'FMONITOR_MIGRATION_DB_PASSWORD'] as $name) {
            $value = getenv($name);
            if (!is_string($value) || $value === '') {
                return null;
            }
            $values[$name] = $value;
        }
        if (filter_var($values['FMONITOR_DB_PORT'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false) {
            return null;
        }
        foreach (['FMONITOR_DB_NAME', 'FMONITOR_DB_USER', 'FMONITOR_MIGRATION_DB_USER'] as $name) {
            if (preg_match('/\A[A-Za-z0-9_]+\z/', $values[$name]) !== 1) {
                return null;
            }
        }
        return [
            'host' => $values['FMONITOR_DB_HOST'],
            'port' => (int) $values['FMONITOR_DB_PORT'],
            'database' => $values['FMONITOR_DB_NAME'],
            'runtimeUser' => $values['FMONITOR_DB_USER'],
            'runtimePassword' => $values['FMONITOR_DB_PASSWORD'],
            'migrationUser' => $values['FMONITOR_MIGRATION_DB_USER'],
            'migrationPassword' => $values['FMONITOR_MIGRATION_DB_PASSWORD'],
        ];
    }

    private static function hasExactPrivileges(mysqli $database, string $grantee, string $schema): bool
    {
        $global = $database->query("SELECT PRIVILEGE_TYPE FROM information_schema.USER_PRIVILEGES WHERE GRANTEE={$grantee}")->fetch_all(MYSQLI_ASSOC);
        $schemaPrivileges = $database->query("SELECT TABLE_SCHEMA, PRIVILEGE_TYPE FROM information_schema.SCHEMA_PRIVILEGES WHERE GRANTEE={$grantee}")->fetch_all(MYSQLI_ASSOC);
        $tableCount = (int) $database->query("SELECT COUNT(*) FROM information_schema.TABLE_PRIVILEGES WHERE GRANTEE={$grantee}")->fetch_column();
        $columnCount = (int) $database->query("SELECT COUNT(*) FROM information_schema.COLUMN_PRIVILEGES WHERE GRANTEE={$grantee}")->fetch_column();
        $actual = array_map(static fn(array $row): string => $row['PRIVILEGE_TYPE'], $global);
        sort($actual, SORT_STRING);
        $expectedSchema = ['DELETE', 'INSERT', 'SELECT', 'UPDATE'];
        $actualSchema = [];
        foreach ($schemaPrivileges as $row) {
            if ($row['TABLE_SCHEMA'] !== $schema) {
                return false;
            }
            $actualSchema[] = $row['PRIVILEGE_TYPE'];
        }
        sort($actualSchema, SORT_STRING);
        return $actual === ['USAGE'] && $actualSchema === $expectedSchema && $tableCount === 0 && $columnCount === 0;
    }
}
