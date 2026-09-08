<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
final class AssignmentOrderOriginalHistoryConfigurationUnavailable extends \RuntimeException
{
    public function __construct() { parent::__construct('Original history configuration unavailable.', 0, null); }
}
