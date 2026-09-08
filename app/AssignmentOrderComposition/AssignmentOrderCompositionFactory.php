<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final class AssignmentOrderCompositionFactory
{
    public static function create(SelectionDependencies $dependencies): AssignmentOrderCompositionApplication
    { return new SelectionApplication($dependencies); }
}
