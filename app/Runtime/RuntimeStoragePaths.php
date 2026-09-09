<?php

declare(strict_types=1);

namespace FMonitor2\Runtime;

/** Filesystem policy shared by explicit preparation and read-only readiness. */
final class RuntimeStoragePaths
{
    public static function directories(RuntimeConfiguration $config): array
    {
        $state = $config->value('FMONITOR_SESSION_STATE_ROOT');
        return array_values(array_unique([
            $state, $state . '/sessions', $state . '/sessions/' . $config->value('FMONITOR_SESSION_INSTANCE'),
            $config->value('FMONITOR_ARTIFACT_STORAGE_ROOT'),
            dirname($config->value('FMONITOR_ORIGINAL_DB_PASSWORD_FILE')),
            dirname($config->value('FMONITOR_ORIGINAL_SAFE_LOG_FILE')),
        ]));
    }

    public static function files(RuntimeConfiguration $config): array
    {
        return [
            $config->value('FMONITOR_ORIGINAL_DB_PASSWORD_FILE') => $config->value('FMONITOR_DB_PASSWORD'),
            $config->value('FMONITOR_ORIGINAL_SAFE_LOG_FILE') => null,
        ];
    }

    public static function inspect(string $path, bool $directory, bool $required): bool
    {
        clearstatcache(true, $path);
        $stat = @lstat($path);
        if ($stat === false) {
            if ($required) self::fail();
            return false;
        }
        $type = $directory ? 0040000 : 0100000;
        $modes = $directory ? [0700, 0750] : [0600];
        if (($stat['mode'] & 0170000) !== $type || !in_array($stat['mode'] & 07777, $modes, true)
            || $stat['uid'] !== posix_geteuid() || $stat['gid'] !== posix_getegid()
            || realpath($path) !== $path || !is_readable($path) || !is_writable($path)
            || (!$directory && $stat['nlink'] !== 1)) self::fail();
        return true;
    }

    public static function ancestors(string $path): void
    {
        for ($parent = dirname($path); $parent !== '/'; $parent = dirname($parent)) {
            clearstatcache(true, $parent);
            $stat = @lstat($parent);
            if ($stat !== false && (($stat['mode'] & 0170000) !== 0040000 || realpath($parent) !== $parent)) self::fail();
        }
    }

    public static function passwordMatches(string $path, string $expected): void
    {
        $actual = @file_get_contents($path);
        if (!is_string($actual) || !hash_equals($expected, $actual)) self::fail();
    }

    public static function fail(): never
    {
        throw new \RuntimeException('RUNTIME_STORAGE_INVALID');
    }
}
