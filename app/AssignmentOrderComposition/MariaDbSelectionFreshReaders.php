<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final readonly class MariaDbSelectionFreshReaders implements SelectionFreshTerminalReaderFactory
{
    public function __construct(private MariaDbSelectionSql $primary,private \Closure $openConnection) {}
    public function open(): SelectionCloseableTerminalRequestReader
    {
        $db=($this->openConnection)();
        if(!$db instanceof \mysqli || $db===$this->primary->db)throw new \RuntimeException('Independent selection connection required.');
        try {
            $sql=new MariaDbSelectionSql($db,$this->primary->prefix);
            if(!$sql->idle())throw new \RuntimeException('Fresh selection connection is not idle.');
            return new MariaDbSelectionRequests($sql,true);
        }catch(\Throwable $e){$db->close();throw $e;}
    }
}
