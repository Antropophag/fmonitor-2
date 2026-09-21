<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Reads the bounded ERP request scope from local legacy object identities. */
final readonly class MariaDbErpEquipmentFactsCandidates
{
    public function __construct(private \mysqli $db,private string $prefix)
    {
        if(preg_match('/^[A-Za-z0-9_]{0,25}$/D',$prefix)!==1)throw new \InvalidArgumentException('CONFIGURATION_INVALID');
    }
    public function read():array
    {
        $result=$this->db->query("SELECT DISTINCT TRIM(zavnumber) sourceOrderNumber FROM `{$this->prefix}fm_maintable` WHERE zavnumber IS NOT NULL AND TRIM(zavnumber)<>'' AND TRIM(zavnumber)<>'0' ORDER BY sourceOrderNumber");
        return array_column($result->fetch_all(MYSQLI_ASSOC),'sourceOrderNumber');
    }
}
