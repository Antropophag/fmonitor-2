<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class EquipmentFactsPersistenceFailure extends \RuntimeException
{
    public function __construct(){parent::__construct('EQUIPMENT_FACTS_PERSISTENCE_FAILED');}
}
