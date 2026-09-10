<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime;

use FMonitor2\InstallationProcess\MariaDbYiiInspectionPlanning;
use FMonitor2\InstallationProcess\MariaDbYiiObjectQueue;
use FMonitor2\InstallationProcess\MariaDbYiiObjectQueueProjection;
use FMonitor2\InstallationProcess\YiiInspectionPlanning;
use FMonitor2\InstallationProcess\YiiObjectQueue;
use yii\db\Connection;

final class InstallationProcessFactory
{
    public static function queue(Connection $db, string $prefix, string $legacyPrefix): YiiObjectQueue
    {
        $projection = new MariaDbYiiObjectQueueProjection($db, $prefix);
        return new YiiObjectQueue(new MariaDbYiiObjectQueue($db, $prefix, $legacyPrefix, $projection));
    }
    public static function planning(Connection $db, string $prefix, ?callable $clock = null): YiiInspectionPlanning
    {
        $clock ??= static fn (): \DateTimeImmutable => new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow'));
        return new YiiInspectionPlanning(new MariaDbYiiInspectionPlanning($db, $prefix), \Closure::fromCallable($clock));
    }
}
