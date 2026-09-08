<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final readonly class MariaDbSelectionFreshReaders implements SelectionFreshTerminalReaderFactory
{
    private array $authority;
    public function __construct(private MariaDbSelectionSql $primary,private \Closure $openConnection)
    { $this->authority=self::authority($primary); }
    private static function authority(MariaDbSelectionSql $sql): array
    {
        $rows=$sql->rows('SELECT DATABASE() database_name,CURRENT_USER() database_user,@@character_set_connection charset');
        if(count($rows)!==1)throw new \RuntimeException('Selection authority unavailable.');
        foreach($rows[0] as $value)if(!is_string($value)||$value==='')throw new \RuntimeException('Selection authority unavailable.');
        return $rows[0];
    }
    public function open(): SelectionCloseableTerminalRequestReader
    {
        $db=($this->openConnection)();
        if(!$db instanceof \mysqli || $db===$this->primary->db)throw new \RuntimeException('Independent selection connection required.');
        try {
            $sql=new MariaDbSelectionSql($db,$this->primary->prefix);
            if(!$sql->idle()||self::authority($sql)!==$this->authority)throw new \RuntimeException('Fresh selection connection authority mismatch.');
            return new MariaDbSelectionRequests($sql,true);
        }catch(\Throwable $e){$db->close();throw $e;}
    }
}
