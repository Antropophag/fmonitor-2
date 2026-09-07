<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final class AssignmentOrderApplicationConfigurationUnavailable extends \RuntimeException
{
    public function __construct() { parent::__construct('Assignment order application configuration unavailable.'); }
}
