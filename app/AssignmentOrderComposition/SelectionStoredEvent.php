<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionStoredEvent
{
    public function __construct(
        public int $eventId,
        public SelectionSelectedEvent $payload
    ) {}
}
