<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class MariaDbWorkforceCatalog
{
    public function __construct(
        private readonly \mysqli $connection,
        private readonly string $tablePrefix = '',
    ) {
        if (preg_match('/^[A-Za-z0-9_]*$/D', $this->tablePrefix) !== 1) {
            throw new \InvalidArgumentException('Invalid table prefix.');
        }
    }

    public function findInstallerSnapshot(int|string $requestedTabId): ?array
    {
        if (filter_var($requestedTabId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            return null;
        }
        $tabId = (int) $requestedTabId;
        $table = $this->tablePrefix . 'fm2_workforce_catalog';
        $statement = $this->connection->prepare("SELECT installer_tab_id,fio,position,employment_status,employed_from,employed_to,workforce_source,workforce_source_updated_at FROM `{$table}` WHERE installer_tab_id=? LIMIT 1");
        $statement->bind_param('i', $tabId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        if ($row === null) {
            return null;
        }

        return [
            'tabId' => (int) $row['installer_tab_id'],
            'fullName' => $row['fio'],
            'position' => $row['position'],
            'status' => $row['employment_status'],
            'employedFrom' => $row['employed_from'],
            'employedTo' => $row['employed_to'],
            'source' => $row['workforce_source'],
            'sourceUpdatedAt' => $row['workforce_source_updated_at'],
        ];
    }
}
