<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final class ProductionAssignmentOrderCompositionFactory
{
    public static function create(\mysqli $connection,\Closure $openFreshConnection,string $tablePrefix=''): AssignmentOrderCompositionApplication
    { return AssignmentOrderCompositionFactory::create(AssignmentOrderCompositionNativeVerificationFactory::dependencies($connection,$openFreshConnection,$tablePrefix)); }
}
