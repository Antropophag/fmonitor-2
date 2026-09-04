<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;

final class MariaDbPrepareEngineerDirectory
{
    public function __construct(private readonly \mysqli $connection,private readonly string $legacyPrefix,private readonly string $processPrefix=''){}

    public function read():array
    {
        $sql="SELECT u.id,u.name,c.position_snapshot FROM `{$this->legacyPrefix}users` u JOIN `{$this->legacyPrefix}users_roles` r ON r.id=u.role_id JOIN `{$this->processPrefix}fm2_process_user_capabilities` c ON c.user_id=u.id AND c.capability='construction_control_engineer' WHERE u.status=1 AND r.status=1 LIMIT 102";
        $result=$this->connection->query($sql);
        if(!$result instanceof \mysqli_result)throw new PilotHttpInfrastructureUnavailable();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
