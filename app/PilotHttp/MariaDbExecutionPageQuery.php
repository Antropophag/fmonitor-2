<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
final readonly class MariaDbExecutionPageQuery
{
    public function __construct(private \mysqli $db,private string $prefix) {}
    public function read(int $object):array
    {
        $s=$this->db->prepare('SELECT process_state,actual_start_date FROM `'.$this->prefix.'fm2_installation_cases` WHERE legacy_installation_object_id=?');
        $s->execute([$object]);try{return $s->get_result()->fetch_assoc()??throw new \RuntimeException();}finally{$s->close();}
    }
}
