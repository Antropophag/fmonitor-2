<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
final class AssignmentOrderSelectedOriginalVerificationFactory
{
    public static function create(\mysqli $db,AssignmentOrderOriginalProductionConfig $config,
        AssignmentOrderOriginalFreshTerminalReaderFactory $freshTerminalReaders,AssignmentOrderOriginalClock $clock,
        ?AssignmentOrderOriginalPersistenceObserver $observer=null): AssignmentOrderOriginalApplication
    { return AssignmentOrderSelectedOriginalBinding::create($db,$config,$freshTerminalReaders,$clock,$observer); }
}
