<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;

final class MariaDbPrepareWorkforceDirectory
{
    public function __construct(private readonly \mysqli $connection,private readonly string $processPrefix=''){}

    public function read(bool $history):array
    {
        $fields='installer_tab_id,fio,position,employment_status,employed_from,employed_to,workforce_source,workforce_source_updated_at'.($history?',reconciliation_state,last_successful_sync_run_id,last_successful_sync_at':'');
        $result=$this->connection->query("SELECT {$fields} FROM `{$this->processPrefix}fm2_workforce_catalog` LIMIT 502");
        if(!$result instanceof \mysqli_result)throw new PilotHttpInfrastructureUnavailable();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
