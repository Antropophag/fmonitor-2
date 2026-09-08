<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

interface AssignmentOrderApplicationSchemaObserver
{ public function observe(AssignmentOrderApplicationSchemaPhase $phase):void; }
