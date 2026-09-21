<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final readonly class ErpEquipmentFactsDeliveryConfig
{
    public function __construct(public string$host,public string$database,public string$user,public string$password,public int$maxRows=10000,public int$timeoutSeconds=30)
    {if($host===''||$database===''||$user===''||$password===''||$maxRows<1||$maxRows>10000||$timeoutSeconds<1||$timeoutSeconds>60)throw new \InvalidArgumentException('ERP_EQUIPMENT_FACTS_CONFIGURATION_UNAVAILABLE');}
}
