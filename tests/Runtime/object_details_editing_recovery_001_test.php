<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\InstallationProcess\ObjectDetailsEditingSchemaMigration;
if(getenv('FMONITOR_FIXTURE_REACHABILITY')==='object-details-recovery-fixture'){assertSameValue(true,is_file(dirname(__DIR__,2).'/app/RuntimeRestore/RuntimeRecovery.php'),'recovery fixture');echo"FIXTURE_REACHABLE: object-details-recovery-fixture\n";exit(0);}
assertSameValue(true,class_exists(ObjectDetailsEditingSchemaMigration::class),'INTENDED_RED OBJECT-DETAILS-EDITING-001 additive schema owner exists');
$source=(string)file_get_contents(dirname(__DIR__,2).'/app/RuntimeRestore/RuntimeRecovery.php');
foreach(['fm2_object_detail_edits','fm2_object_detail_edit_events','fm2_object_detail_edit_requests']as$table)assertSameValue(true,str_contains($source,$table),'A15 current recovery inventory '.$table);
echo "PASS: OBJECT-DETAILS-EDITING-001 recovery inventory\n";
