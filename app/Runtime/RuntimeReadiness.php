<?php

declare(strict_types=1);

namespace FMonitor2\Runtime;

final class RuntimeReadiness
{
    public static function assertReady(RuntimeConfiguration $config): void
    {
        RuntimeStorage::assertReady($config);
        MariaDbRuntimeReadiness::assertReady($config);
    }
}
