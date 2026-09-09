<?php

declare(strict_types=1);

namespace FMonitor2\Runtime;

final class RuntimeStorage
{
    public static function assertReady(RuntimeConfiguration $config): void
    {
        self::inspect($config, true);
    }

    public static function prepare(RuntimeConfiguration $config): void
    {
        // Validate every existing target before creating any missing target.
        self::inspect($config, false);
        $previousMask = umask(0077);
        try {
            foreach (RuntimeStoragePaths::directories($config) as $directory) {
                if (!is_dir($directory) && !@mkdir($directory, 0700, true)) RuntimeStoragePaths::fail();
                RuntimeStoragePaths::inspect($directory, true, true);
            }
            foreach (RuntimeStoragePaths::files($config) as $path => $password) {
                if (RuntimeStoragePaths::inspect($path, false, false)) continue;
                $handle = @fopen($path, 'xb');
                if ($handle === false) RuntimeStoragePaths::fail();
                try {
                    $bytes = $password ?? '';
                    if (fwrite($handle, $bytes) !== strlen($bytes) || !fflush($handle) || !fsync($handle)) RuntimeStoragePaths::fail();
                } finally {
                    fclose($handle);
                }
            }
            self::assertReady($config);
        } finally {
            umask($previousMask);
        }
    }

    private static function inspect(RuntimeConfiguration $config, bool $required): void
    {
        foreach (RuntimeStoragePaths::directories($config) as $directory) {
            RuntimeStoragePaths::ancestors($directory);
            RuntimeStoragePaths::inspect($directory, true, $required);
        }
        foreach (RuntimeStoragePaths::files($config) as $path => $password) {
            RuntimeStoragePaths::ancestors($path);
            if (RuntimeStoragePaths::inspect($path, false, $required) && $password !== null) {
                RuntimeStoragePaths::passwordMatches($path, $password);
            }
        }
    }
}
