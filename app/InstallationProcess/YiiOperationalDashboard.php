<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final readonly class YiiOperationalDashboard
{
    public function __construct(private MariaDbYiiOperationalDashboard $store)
    {
    }

    public function read(int $actorId, string $cutoff): array
    {
        if (!$this->store->authorized($actorId)) {
            throw new \DomainException('ACCESS_DENIED');
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $cutoff, new \DateTimeZone('Europe/Moscow'));
        if ($date === false || $date->format('Y-m-d') !== $cutoff) {
            throw new \InvalidArgumentException('Invalid dashboard cutoff.');
        }
        return $this->store->read($cutoff, $date->modify('+13 days')->format('Y-m-d'));
    }
}
