<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final class ProductionControlEngineerAssignmentFactory
{
 public static function create(\mysqli$db,string$p,?ControlEngineerAssignmentClock$clock=null):MariaDbControlEngineerAssignment{return new MariaDbControlEngineerAssignment($db,$p,$clock??new class implements ControlEngineerAssignmentClock{public function now():string{return gmdate('Y-m-d\\TH:i:s\\Z');}});}
}
