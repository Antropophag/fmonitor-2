<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final class AssignmentOrderCompositionNativeVerificationFactory
{
    public static function dependencies(\mysqli $connection,\Closure $openFreshConnection,string $tablePrefix=''): SelectionDependencies
    {
        $sql=new MariaDbSelectionSql($connection,$tablePrefix);$facts=new MariaDbSelectionFacts($sql);$attempts=new MariaDbSelectionAttempts($sql);
        return new SelectionDependencies($facts,$facts,new SelectionSystemClock(),new MariaDbSelectionRequests($sql),new MariaDbSelectionUnitOfWork($sql),new MariaDbSelectionFreshReaders($sql,$openFreshConnection),$attempts,$attempts);
    }
}
