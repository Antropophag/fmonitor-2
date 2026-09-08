<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
final readonly class MariaDbSelectedOriginalCompositionReader implements AssignmentOrderCompositionReader
{
    public function __construct(private \mysqli $db,private string $prefix) {}
    public function find(int $caseId,int $orderId): AssignmentOrderCompositionSnapshot
    {
        try {
            if($caseId<1||$orderId<1)AssignmentOrderOriginalSql::fail();
            $sql=new AssignmentOrderOriginalSql($this->db,$this->prefix);
            $configuration=$sql->rows('SELECT DATABASE() db,@@character_set_connection charset');
            if(count($configuration)!==1||!is_string($configuration[0]['db'])||$configuration[0]['db']===''||$configuration[0]['charset']!=='utf8mb4')AssignmentOrderOriginalSql::fail();
            return $sql->snapshot(fn()=>MariaDbSelectedOriginalComposition::read($sql,$caseId,$orderId));
        }catch(\Throwable){return AssignmentOrderOriginalSqlComposition::empty(AssignmentOrderCompositionLookupStatus::UNAVAILABLE,$caseId,$orderId);}
    }
}
