<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class AssignmentOrderApplicationSchemaUnavailable extends \RuntimeException
{
    public function __construct(){parent::__construct('Assignment order application schema unavailable.');}
}
