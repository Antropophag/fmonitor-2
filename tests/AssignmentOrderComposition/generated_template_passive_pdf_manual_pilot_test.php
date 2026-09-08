<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\InstallationProcess\ProductionPdfAssignmentOrderRenderer;
use FMonitor2\AssignmentOrderOriginal\{FMonitorPassivePdfInspector,AssignmentOrderOriginalPdfStatus};
$input=['assignmentOrderVersion'=>1,'assignmentOrderDate'=>'2026-09-07','organizationType'=>'individual','installationObjectSnapshot'=>['address'=>'Synthetic address','entrance'=>'1','objectRegistrationNumber'=>'TEST','plannedStartDate'=>'2026-09-08','plannedFinishDate'=>'2026-10-01'],'installers'=>[['tabId'=>7001,'fullName'=>'Synthetic Installer','position'=>'Installer']],'controlEngineer'=>['userId'=>73,'fullName'=>'Synthetic Engineer','position'=>'Engineer']];
$artifact=(new ProductionPdfAssignmentOrderRenderer())->renderAssignmentOrder($input)[0];assertSameValue('application/pdf',$artifact['mediaType'],'generated assignment order media');assertSameValue(AssignmentOrderOriginalPdfStatus::PASSIVE_PDF,(new FMonitorPassivePdfInspector())->inspect($artifact['bytes'])->status,'system-generated template is accepted by its own upload inspector');foreach(['URI','OpenAction']as$name)assertSameValue(0,preg_match('#/'.preg_quote($name,'#').'\b#',$artifact['bytes']),'generated template omits active '.$name.' token');echo "PASS generated template passive PDF profile\n";
