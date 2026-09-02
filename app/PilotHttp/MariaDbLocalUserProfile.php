<?php

declare(strict_types=1);

namespace FMonitor2\PilotHttp;

final readonly class MariaDbLocalUserProfile
{
    public function __construct(private \mysqli $connection, private string $tablePrefix)
    {
    }

    public function read(int $actorUserId): ?HttpUser
    {
        $table = $this->tablePrefix . 'fm2_pilot_users';
        $statement = $this->connection->prepare(
            "SELECT user_id,full_name,email FROM `{$table}`"
            . " WHERE user_id=? AND status=1 AND BINARY activation_state=BINARY 'active' LIMIT 2"
        );
        $statement->bind_param('i', $actorUserId);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        if (\count($rows) !== 1 || \trim((string) $rows[0]['full_name']) === '') return null;
        return new HttpUser((int) $rows[0]['user_id'], (string) $rows[0]['full_name'], (string) $rows[0]['email']);
    }
}
