<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final class ObjectDetailsEditingMigration
{
 public static function apply(\mysqli$db,string$prefix):array{$schema=ObjectDetailsEditingSchemaMigration::apply($db,$prefix);if($schema['applied'])ObjectDetailsEditPermissionProvisioning::apply($db,$prefix);return$schema;}
}
