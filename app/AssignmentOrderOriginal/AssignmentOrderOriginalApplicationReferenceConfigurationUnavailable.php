<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
final class AssignmentOrderOriginalApplicationReferenceConfigurationUnavailable extends \RuntimeException
{
    public function __construct() { parent::__construct('Original application reference configuration unavailable.'); }
}
