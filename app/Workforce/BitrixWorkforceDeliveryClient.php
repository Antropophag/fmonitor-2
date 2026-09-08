<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

interface BitrixWorkforceDeliveryClient
{
    public function fetch(): BitrixWorkforceDeliveryResult;
}
