<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final readonly class YiiObjectQueue
{
    public function __construct(private MariaDbYiiObjectQueue $store)
    {
    }
    public function read(int $actorId, string $query, string $status, int $page): array
    {
        if (!$this->store->authorized($actorId)) {
            throw new \DomainException('ACCESS_DENIED');
        }
        return $this->store->read($actorId, trim($query), $status, $page);
    }
}
