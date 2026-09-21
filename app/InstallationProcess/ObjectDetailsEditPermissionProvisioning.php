<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final class ObjectDetailsEditPermissionProvisioning
{
 public static function apply(\mysqli$db,string$prefix):array{return MariaDbObjectDetailsEditPermissionProvisioning::apply($db,$prefix);}
}
