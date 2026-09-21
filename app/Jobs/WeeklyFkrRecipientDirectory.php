<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

interface WeeklyFkrRecipientDirectory
{
    public function recipient(string $id): ?array;
}
