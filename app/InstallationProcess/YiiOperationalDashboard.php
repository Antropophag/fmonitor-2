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
        $monday = $date->modify('monday this week');
        $weeks = [];
        for ($index = 0; $index < 6; $index++) {
            $start = $monday->modify('+' . $index . ' weeks');
            $weeks[] = [$start->format('Y-m-d'), $start->modify('+6 days')->format('Y-m-d')];
        }
        $result = $this->store->read($cutoff, $date->modify('+13 days')->format('Y-m-d'), $weeks);
        $charts = $result['charts'] ?? null;
        if (!is_array($charts)
            || count($charts['stages'] ?? []) !== 6
            || count($charts['weeks'] ?? []) !== 6
            || count($charts['activityAge'] ?? []) !== 5
            || array_sum(array_column($charts['stages'], 'value')) !== ($result['total'] ?? -1)) {
            throw new \RuntimeException('Malformed dashboard chart aggregate.');
        }
        return $result;
    }
}
