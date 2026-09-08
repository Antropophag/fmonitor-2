<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

final class BitrixWorkforceDeliveryConfigurationUnavailable extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Bitrix workforce delivery configuration unavailable.', 0, null);
    }
}
