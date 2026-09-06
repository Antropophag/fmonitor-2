<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

final class AssignmentOrderOriginalResponseDeliveryLost extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Assignment order original response delivery lost.', 0, null);
    }
}
