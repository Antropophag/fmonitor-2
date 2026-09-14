<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime;

use FMonitor2\InstallationProcess\MariaDbLegacySourceSnapshot;
use FMonitor2\InstallationProcess\MariaDbLegacyImportApplication;

final class LegacyImportConsole
{
    /** @return array{exitCode:int,result:array<string,mixed>} */
    public static function run(): array
    {
        $source = $target = null;
        try {
            $configuration = self::configuration();
            $cutoff = $configuration['FMONITOR_MIGRATION_CUTOFF'] ?: (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow')))->format('Y-m-d 23:59:59');
            \mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $source = self::connect($configuration['FMONITOR_SOURCE_HOST'], $configuration['FMONITOR_SOURCE_USER'], $configuration['FMONITOR_SOURCE_PASSWORD'], $configuration['FMONITOR_SOURCE_NAME'], (int) $configuration['FMONITOR_SOURCE_PORT']);
            $target = self::connect(self::required('FMONITOR_DB_HOST'), self::required('FMONITOR_DB_USER'), self::explicit('FMONITOR_DB_PASSWORD'), self::required('FMONITOR_DB_NAME'), self::port(self::required('FMONITOR_DB_PORT')));
            $processPrefix = self::prefix(self::required('FMONITOR_PROCESS_TABLE_PREFIX'));
            $legacyPrefix = self::prefix(self::required('FMONITOR_LEGACY_TABLE_PREFIX'));
            $snapshot = (new MariaDbLegacySourceSnapshot($source))->read($cutoff);
            $result = (new MariaDbLegacyImportApplication($target, $processPrefix, $legacyPrefix))->import($snapshot, $cutoff);
            return ['exitCode' => 0, 'result' => ['result' => 'LEGACY_IMPORT_COMPLETED', 'cutoff' => $cutoff] + $result];
        } catch (\InvalidArgumentException | \JsonException) {
            return ['exitCode' => 64, 'result' => ['ok' => false, 'reason' => 'CONFIGURATION_INVALID']];
        } catch (\mysqli_sql_exception) {
            return ['exitCode' => 69, 'result' => ['ok' => false, 'reason' => 'DATABASE_UNAVAILABLE']];
        } catch (\Throwable) {
            return ['exitCode' => 70, 'result' => ['ok' => false, 'reason' => 'LEGACY_IMPORT_FAILED']];
        } finally {
            foreach ([$source, $target] as $connection) if ($connection instanceof \mysqli) try { $connection->close(); } catch (\Throwable) {}
        }
    }

    private static function configuration(): array
    {
        $path = self::required('FMONITOR_LEGACY_SOURCE_CONFIG');
        if (!is_file($path) || is_link($path) || (fileperms($path) & 0777) !== 0600) throw new \InvalidArgumentException();
        $names = ['FMONITOR_SOURCE_HOST','FMONITOR_SOURCE_PORT','FMONITOR_SOURCE_NAME','FMONITOR_SOURCE_USER','FMONITOR_SOURCE_PASSWORD','FMONITOR_MIGRATION_CUTOFF'];
        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if ($line === '' || !str_contains($line, '=')) throw new \InvalidArgumentException();
            [$name, $value] = explode('=', $line, 2);
            if (!in_array($name, $names, true) || array_key_exists($name, $values)) throw new \InvalidArgumentException();
            if (strlen($value) >= 2 && $value[0] === $value[strlen($value)-1] && ($value[0] === "'" || $value[0] === '"')) $value = substr($value, 1, -1);
            $values[$name] = $value;
        }
        if (array_keys($values) !== $names || $values['FMONITOR_SOURCE_HOST'] === '' || $values['FMONITOR_SOURCE_NAME'] === '' || $values['FMONITOR_SOURCE_USER'] === '' || $values['FMONITOR_SOURCE_PASSWORD'] === '') throw new \InvalidArgumentException();
        self::port($values['FMONITOR_SOURCE_PORT']);
        if ($values['FMONITOR_MIGRATION_CUTOFF'] !== '' && !self::timestamp($values['FMONITOR_MIGRATION_CUTOFF'])) throw new \InvalidArgumentException();
        return $values;
    }

    private static function connect(string $host,string $user,string $password,string $name,int $port): \mysqli {$db=@new \mysqli($host,$user,$password,$name,$port);$db->set_charset('utf8mb4');return $db;}
    private static function required(string $name): string {$value=getenv($name);if(!is_string($value)||$value==='')throw new \InvalidArgumentException();return $value;}
    private static function explicit(string $name): string {$value=getenv($name);if(!is_string($value))throw new \InvalidArgumentException();return $value;}
    private static function port(string $value): int {if(preg_match('/^[1-9][0-9]{0,4}$/D',$value)!==1||(int)$value>65535)throw new \InvalidArgumentException();return(int)$value;}
    private static function prefix(string $value): string {if(strlen($value)>25||preg_match('/^[A-Za-z0-9_]*$/D',$value)!==1)throw new \InvalidArgumentException();return$value;}
    private static function timestamp(string $value): bool {$date=\DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$value,new \DateTimeZone('Europe/Moscow'));return$date!==false&&$date->format('Y-m-d H:i:s')===$value;}
}
