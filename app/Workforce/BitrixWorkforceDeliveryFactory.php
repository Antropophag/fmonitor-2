<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

final class BitrixWorkforceDeliveryFactory
{
    public static function create(BitrixWorkforceDeliveryConfig $config): BitrixWorkforceDeliveryClient
    {
        return new NativeBitrixWorkforceDeliveryClient($config, DeliveryConfiguration::origin($config));
    }
}
