<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

/** @internal Only fixed reasons may cross the fetch boundary. */
final class DeliveryFailure extends \RuntimeException
{
    public function __construct(public readonly BitrixWorkforceDeliveryReason $reason)
    {
        parent::__construct('Bitrix workforce delivery failed.');
    }
}
