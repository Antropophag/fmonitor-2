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
        $hasEdits=(int)$this->db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='".$this->db->real_escape_string($this->prefix.'fm2_object_detail_edits')."'")->fetch_column()===1;if(!$hasEdits){$result=$this->db->query("SELECT DISTINCT TRIM(zavnumber) sourceOrderNumber FROM `{$this->prefix}fm_maintable` WHERE zavnumber IS NOT NULL AND TRIM(zavnumber) NOT IN('','0') ORDER BY sourceOrderNumber");return array_column($result->fetch_all(MYSQLI_ASSOC),'sourceOrderNumber');}$effective="CASE WHEN JSON_CONTAINS_PATH(e.values_json,'one','$.zavnumber') THEN JSON_UNQUOTE(JSON_EXTRACT(e.values_json,'$.zavnumber')) ELSE m.zavnumber END";$result=$this->db->query("SELECT DISTINCT TRIM({$effective}) sourceOrderNumber FROM `{$this->prefix}fm_maintable` m LEFT JOIN `{$this->prefix}fm2_object_detail_edits` e ON e.object_id=m.id WHERE {$effective} IS NOT NULL AND TRIM({$effective}) NOT IN('','0','null') ORDER BY sourceOrderNumber");
        return array_column($result->fetch_all(MYSQLI_ASSOC),'sourceOrderNumber');
    }
}
