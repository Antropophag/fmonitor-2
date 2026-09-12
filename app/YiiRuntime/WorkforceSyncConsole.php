<?php
declare(strict_types=1);

namespace FMonitor2\YiiRuntime;

use FMonitor2\Jobs\JobsRuntimeConfiguration;
use FMonitor2\Jobs\MariaDbWorkforceJobHandler;
use FMonitor2\Workforce\BitrixWorkforceDeliveryConfig;
use FMonitor2\Workforce\BitrixWorkforceDeliveryFactory;
use FMonitor2\Workforce\WorkerConfiguration;
use mysqli;

final class WorkforceSyncConsole
{
    /** @return array{exitCode:int,result:array<string,mixed>} */
    public static function run(): array
    {
        $stagedToken = null;
        try {
            [$delivery, $database, $prefix, $stagedToken] = self::composeFromEnvironment();
        } catch (\mysqli_sql_exception) {
            return ['exitCode' => 69, 'result' => ['ok' => false, 'reason' => 'DATABASE_UNAVAILABLE']];
        } catch (\Throwable) {
            if (is_string($stagedToken)) @unlink($stagedToken);
            return ['exitCode' => 64, 'result' => ['ok' => false, 'reason' => 'CONFIGURATION_INVALID']];
        }

        try {
            $result = self::owner($database, $prefix)->run($delivery, self::uuid());
            return ['exitCode' => ($result['status'] ?? null) === 'completed' ? 0 : 1, 'result' => $result];
        } catch (\Throwable) {
            return ['exitCode' => 70, 'result' => ['ok' => false, 'reason' => 'SYNC_FAILED']];
        } finally {
            try { $database->close(); } catch (\Throwable) {}
            if (is_string($stagedToken)) @unlink($stagedToken);
        }
    }

    public static function runJob(array $job, JobsRuntimeConfiguration $configuration): array
    {
        [$delivery, $database, $prefix] = self::composeForJob($configuration);
        try {
            return (new MariaDbWorkforceJobHandler(self::owner($database, $prefix), $delivery))->handle($job);
        } finally {
            $database->close();
        }
    }

    /** @return array{0:object,1:\mysqli,2:string,3:?string} */
    private static function composeFromEnvironment(): array
    {
        $prefix = self::prefix(getenv('FMONITOR_PROCESS_TABLE_PREFIX'));
        $host = self::required('FMONITOR_DB_HOST');
        $port = self::port(self::required('FMONITOR_DB_PORT'));
        $name = self::required('FMONITOR_DB_NAME');
        $user = self::required('FMONITOR_DB_USER');
        $password = self::explicit('FMONITOR_DB_PASSWORD');
        $stagedToken = null;

        $private = getenv('FMONITOR_BITRIX_CONFIG');
        if (is_string($private) && $private !== '') {
            $values = WorkerConfiguration::fromFile($private);
            $stagedToken = WorkerConfiguration::stageToken($values['token']);
            $config = new BitrixWorkforceDeliveryConfig($values['origin'], $values['webhookUserId'], $stagedToken, $values['departmentIds'], caFile: self::optional('FMONITOR_BITRIX_CA_FILE'));
        } else {
            $id = self::positiveInteger(self::required('FMONITOR_BITRIX_WEBHOOK_USER_ID'));
            $departments = json_decode(self::required('FMONITOR_BITRIX_DEPARTMENT_IDS_JSON'), true, 8, JSON_THROW_ON_ERROR);
            $config = new BitrixWorkforceDeliveryConfig(self::required('FMONITOR_BITRIX_ORIGIN'), $id, self::required('FMONITOR_BITRIX_TOKEN_FILE'), self::departments($departments), caFile: self::optional('FMONITOR_BITRIX_CA_FILE'));
        }
        try {
            $delivery = BitrixWorkforceDeliveryFactory::create($config);
            $database = self::openDatabase($host, $user, $password, $name, $port);
        } catch (\Throwable $error) {
            if (is_string($stagedToken)) @unlink($stagedToken);
            throw $error;
        }
        return [$delivery, $database, $prefix, $stagedToken];
    }

    /** @return array{0:object,1:\mysqli,2:string} */
    private static function composeForJob(JobsRuntimeConfiguration $configuration): array
    {
        $id = self::positiveInteger($configuration->value('FMONITOR_BITRIX_WEBHOOK_USER_ID'));
        $departments = json_decode($configuration->value('FMONITOR_BITRIX_DEPARTMENT_IDS_JSON'), true, 8, JSON_THROW_ON_ERROR);
        $delivery = BitrixWorkforceDeliveryFactory::create(new BitrixWorkforceDeliveryConfig(
            $configuration->value('FMONITOR_BITRIX_ORIGIN'), $id, $configuration->value('FMONITOR_BITRIX_TOKEN_FILE'), self::departments($departments), caFile: $configuration->optionalValue('FMONITOR_BITRIX_CA_FILE')
        ));
        $database = self::openDatabase($configuration->value('FMONITOR_DB_HOST'), $configuration->value('FMONITOR_DB_USER'), $configuration->value('FMONITOR_DB_PASSWORD'), $configuration->value('FMONITOR_DB_NAME'), $configuration->port());
        return [$delivery, $database, $configuration->prefix()];
    }

    private static function openDatabase(string $host, string $user, string $password, string $name, int $port): mysqli
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $database = new mysqli($host, $user, $password, $name, $port);
        try {
            $database->set_charset('utf8mb4');
            return $database;
        } catch (\Throwable $error) {
            try { $database->close(); } catch (\Throwable) {}
            throw $error;
        }
    }

    private static function owner(mysqli $database, string $prefix): object
    {
        return new \FMonitor2\InstallationProcess\MariaDbWorkforceSynchronization($database, $prefix);
    }

    private static function required(string $name): string
    {
        $value = getenv($name);
        if (!is_string($value) || $value === '' || str_contains($value, "\0")) throw new \InvalidArgumentException();
        return $value;
    }

    private static function explicit(string $name): string
    {
        $value = getenv($name);
        if (!is_string($value) || str_contains($value, "\0")) throw new \InvalidArgumentException();
        return $value;
    }

    private static function optional(string $name): ?string
    {
        $value = getenv($name);
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function port(string $value): int
    {
        if (preg_match('/^[1-9][0-9]{0,4}$/D', $value) !== 1 || (int) $value > 65535) throw new \InvalidArgumentException();
        return (int) $value;
    }

    private static function prefix(mixed $value): string
    {
        if (!is_string($value) || preg_match('/^[A-Za-z0-9_]{0,25}$/D', $value) !== 1) throw new \InvalidArgumentException();
        return $value;
    }

    private static function positiveInteger(string $value): int
    {
        if (preg_match('/^[1-9][0-9]*$/D', $value) !== 1 || strlen($value) > 18) throw new \InvalidArgumentException();
        return (int) $value;
    }

    private static function departments(mixed $values): array
    {
        if (!is_array($values) || !array_is_list($values) || $values === []) throw new \InvalidArgumentException();
        $previous = 0;
        foreach ($values as $value) {
            if (!is_int($value) || $value <= $previous) throw new \InvalidArgumentException();
            $previous = $value;
        }
        return $values;
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 15) | 64);
        $bytes[8] = chr((ord($bytes[8]) & 63) | 128);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20);
    }
}
