<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;

/** Test containers must reach the host-only MariaDB binding without widening it. */
final class RecoveryContainerNetwork
{
    public static function forHost(string $databaseHost, string $platform = PHP_OS_FAMILY): array
    {
        return match ($platform) {
            'Linux' => ['arguments'=>['--network','host'], 'databaseHost'=>$databaseHost],
            'Darwin' => ['arguments'=>['--add-host','host.docker.internal:host-gateway'], 'databaseHost'=>'host.docker.internal'],
            default => throw new \RuntimeException('SETUP_FAILURE: unsupported recovery test container platform'),
        };
    }
}
