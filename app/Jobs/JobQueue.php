<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

interface JobQueue
{
    public function enqueue(array $command): array;
    public function claim(array $command): array;
    public function heartbeat(array $command): array;
    public function complete(array $command): array;
    public function fail(array $command): array;
}
