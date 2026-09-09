<?php
declare(strict_types=1);
namespace FMonitor2\Otiz;
use yii\db\Connection;

/** Public application seam; persistence and transaction belong to the MariaDB adapter. */
final class OtizSettlement
{
    private MariaDbOtizSettlement $owner;
    public function __construct(Connection$db,string$prefix,\Closure$clock){$this->owner=new MariaDbOtizSettlement($db,$prefix,$clock);}
    public function recordDiscipline(int$a,int$s,int$o,int$c,string$b,string$artifact,string$operationId):array{return$this->owner->recordDiscipline($a,$s,$o,$c,$b,$artifact,$operationId);}
    public function completeSnapshotPayments(int$a,int$s,string$operationId):array{return$this->owner->completeSnapshotPayments($a,$s,$operationId);}
    public function reverse(int$a,int$closure,string$basis,string$operationId):array{return$this->owner->reverse($a,$closure,$basis,$operationId);}
}
