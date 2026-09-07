<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

enum BitrixWorkforceDeliveryStatus: string
{
    case Complete = 'complete';
    case Failed = 'failed';
}
