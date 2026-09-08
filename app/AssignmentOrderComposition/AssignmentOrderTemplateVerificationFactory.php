<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final class AssignmentOrderTemplateVerificationFactory
{
    public static function create(\mysqli $db,string $prefix,SelectionClock $clock,\Closure $render):AssignmentOrderTemplateApplication
    { return new MariaDbTemplateApplication(new MariaDbSelectionSql($db,$prefix),$clock,$render); }
}
