<?php

declare(strict_types=1);

namespace FMonitor2\Runtime;

/** Stable secret-free operational output; no exception details leave the process. */
final class RuntimeCommand
{
    public static function run(callable $operation): never
    {
        $exit = 0;
        try {
            $config = RuntimeConfiguration::fromEnvironment(getenv());
            $operation($config);
            $result = ['ok' => true];
        } catch (\Throwable $error) {
            $reason = $error->getMessage();
            $exit = match ($reason) {
                'CONFIGURATION_INVALID' => 64,
                'DATABASE_UNAVAILABLE' => 69,
                default => 70,
            };
            if (!in_array($reason, ['CONFIGURATION_INVALID', 'DATABASE_UNAVAILABLE', 'RUNTIME_STORAGE_INVALID', 'SCHEMA_NOT_READY'], true)) {
                $reason = 'RUNTIME_UNAVAILABLE';
            }
            $result = ['ok' => false, 'reason' => $reason];
        }
        echo json_encode($result, JSON_THROW_ON_ERROR), "\n";
        exit($exit);
    }
}
