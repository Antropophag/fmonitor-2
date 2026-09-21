<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final class ProductionObjectDetailsEditFactory{public static function create(\mysqli$db,string$prefix,callable$clock):ObjectDetailsEditApplication{return new ObjectDetailsEditApplication(MariaDbObjectDetailsEditStore::create($db,$prefix),new ObjectDetailsFieldRegistry(),$clock);}}
