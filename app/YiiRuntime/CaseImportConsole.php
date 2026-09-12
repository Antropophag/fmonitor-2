<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime;

use FMonitor2\InstallationProcess\PilotCaseCommitOutcomeUnknown;
use FMonitor2\InstallationProcess\PilotCaseImporter as CaseImporter;
use FMonitor2\InstallationProcess\PilotCaseSchemaUnavailable;
use mysqli;

class_exists(CaseImporter::class);

final class CaseImportConsole
{
    private const ENVIRONMENT_NAMES = ['FMONITOR_DB_HOST', 'FMONITOR_DB_PORT', 'FMONITOR_DB_NAME', 'FMONITOR_DB_USER', 'FMONITOR_DB_PASSWORD', 'FMONITOR_PROCESS_TABLE_PREFIX', 'FMONITOR_LEGACY_TABLE_PREFIX'];

    /** @param list<int> $selected @return array{exitCode:int,result:array<string,mixed>} */
    public static function run(array $selected): array
    {
        $environment = self::environment();
        if ($environment === null) return self::outcome(64, 'CONFIGURATION_INVALID');
        \mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connection = null;
        try {
            try { $connection = self::connect($environment); }
            catch (\Throwable) { return self::outcome(69, 'DATABASE_UNAVAILABLE'); }
            $timestamp = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:sP');
            $importer = self::owner($connection, $environment);
            try { $importer->assertSchemaAvailable(); }
            catch (PilotCaseSchemaUnavailable) { return self::outcome(78, 'SCHEMA_UNAVAILABLE'); }
            catch (\Throwable) { return self::outcome(70, 'IMPORT_FAILED'); }
            for ($attempt = 0; $attempt < 3; $attempt++) {
                try {
                    $result = $importer->import($selected, $timestamp);
                    if (isset($result['rejected'])) return ['exitCode' => 2, 'result' => ['ok' => false, 'reason' => 'PILOT_CASES_NOT_ELIGIBLE', 'rejected' => $result['rejected']]];
                    return ['exitCode' => 0, 'result' => ['ok' => true, 'selected' => $selected, 'imported' => $result['imported'], 'alreadyPresent' => $result['alreadyPresent']]];
                } catch (PilotCaseSchemaUnavailable) {
                    return self::outcome(78, 'SCHEMA_UNAVAILABLE');
                } catch (PilotCaseCommitOutcomeUnknown $unknown) {
                    self::close($connection);
                    $connection = null;
                    return self::reconcile($environment, $selected, $timestamp, $unknown, $connection);
                } catch (\mysqli_sql_exception $error) {
                    if (\in_array($error->getCode(), [1062, 1205, 1213], true) && $attempt < 2) continue;
                    return self::outcome(70, 'IMPORT_FAILED');
                } catch (\Throwable) {
                    return self::outcome(70, 'IMPORT_FAILED');
                }
            }
            return self::outcome(70, 'IMPORT_FAILED');
        } finally {
            if ($connection instanceof \mysqli) self::close($connection);
        }
    }

    /** @param array<string,string> $environment @param list<int> $selected @param ?\mysqli $connection @return array{exitCode:int,result:array<string,mixed>} */
    private static function reconcile(array $environment, array $selected, string $timestamp, PilotCaseCommitOutcomeUnknown $unknown, ?\mysqli &$connection): array
    {
        $reconciled = false;
        if ($unknown->expectedNewIds !== null) {
            try {
                $connection = self::connect($environment);
                $owner = self::owner($connection, $environment);
                $reconciled = $owner->reconciles($unknown->expectedNewIds, $timestamp);
            } catch (\Throwable) { $reconciled = false; }
        }
        if ($reconciled) return ['exitCode' => 0, 'result' => ['ok' => true, 'selected' => $selected, 'imported' => $unknown->expectedNewIds, 'alreadyPresent' => $unknown->alreadyPresent]];
        return self::outcome(75, 'IMPORT_OUTCOME_UNKNOWN');
    }

    /** @return null|array<string,string> */
    private static function environment(): ?array
    {
        $environment = [];
        foreach (self::ENVIRONMENT_NAMES as $name) {
            $value = \getenv($name);
            if ($value === false) return null;
            $environment[$name] = $value;
        }
        if ($environment['FMONITOR_DB_HOST'] === '' || $environment['FMONITOR_DB_NAME'] === '' || $environment['FMONITOR_DB_USER'] === '') return null;
        if (\preg_match('/^[1-9][0-9]{0,4}$/D', $environment['FMONITOR_DB_PORT']) !== 1 || (int) $environment['FMONITOR_DB_PORT'] > 65535) return null;
        foreach (['FMONITOR_PROCESS_TABLE_PREFIX', 'FMONITOR_LEGACY_TABLE_PREFIX'] as $name) {
            if (\strlen($environment[$name]) > 32 || \preg_match('/^[A-Za-z0-9_]*$/D', $environment[$name]) !== 1) return null;
        }
        return $environment;
    }

    /** @param array<string,string> $environment */
    private static function connect(array $environment): \mysqli
    {
        $connection = @new mysqli($environment['FMONITOR_DB_HOST'], $environment['FMONITOR_DB_USER'], $environment['FMONITOR_DB_PASSWORD'], $environment['FMONITOR_DB_NAME'], (int) $environment['FMONITOR_DB_PORT']);
        if (!$connection->set_charset('utf8mb4')) throw new \RuntimeException('Character set was not confirmed.');
        return $connection;
    }

    /** @param array<string,string> $environment */
    private static function owner(mysqli $connection, array $environment): CaseImporter
    {
        return new CaseImporter($connection, $environment['FMONITOR_PROCESS_TABLE_PREFIX'], $environment['FMONITOR_LEGACY_TABLE_PREFIX']);
    }

    private static function close(\mysqli $connection): void { try { $connection->close(); } catch (\Throwable) {} }

    /** @return array{exitCode:int,result:array{ok:false,reason:string}} */
    private static function outcome(int $exitCode, string $reason): array { return ['exitCode' => $exitCode, 'result' => ['ok' => false, 'reason' => $reason]]; }
}
