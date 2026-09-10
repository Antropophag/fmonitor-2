<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final readonly class YiiObjectCard
{
    public function __construct(private MariaDbYiiObjectCard $reader)
    {
    }

    public function read(int $actorId, int $installationObjectId): ?array
    {
        if ($actorId < 1 || !$this->reader->authorized($actorId)) {
            throw new \DomainException('ACCESS_DENIED');
        }
        if ($installationObjectId < 1) {
            return null;
        }
        return $this->reader->read($actorId, $installationObjectId);
    }
}
