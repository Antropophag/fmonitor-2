<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

final class MariaDbProcessCapabilityReader
{
    public function __construct(
        private readonly \mysqli $connection,
        private readonly string $processTablePrefix,
    ) {}

    public function grants(int $userId, string $capability): bool
    {
        $statement = $this->connection->prepare(
            "SELECT 1 FROM `{$this->processTablePrefix}fm2_process_user_capabilities` "
            . 'WHERE user_id=? AND BINARY capability=BINARY ? LIMIT 1',
        );
        $statement->bind_param('is', $userId, $capability);
        $statement->execute();
        return $statement->get_result()->fetch_assoc() !== null;
    }
}
