<?php
declare(strict_types=1);
// INITIAL-OWNER-PROVISIONING-001 A4: real local-up route and strict production default.
require dirname(__DIR__).'/bootstrap.php';
$root=dirname(__DIR__,2);
$make=(string)file_get_contents($root.'/Makefile');$pipes=[];$process=proc_open(['make','--dry-run','--no-print-directory','up'],[1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE make dry-run');$recipe=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);assertSameValue(0,proc_close($process),'real make up recipe expands without execution: '.$stderr);
$cli=(string)file_get_contents($root.'/bin/fmonitor2-provision-initial-admin.php');
$owner=(string)file_get_contents($root.'/app/IdentityAccess/MariaDbInitialOwnerProvisioning.php');
assertSameValue(1,substr_count($make,'bin/fmonitor2-provision-initial-admin.php'),'one canonical initial-owner CLI in make up');
assertSameValue(1,substr_count($recipe,'bin/fmonitor2-provision-initial-admin.php'),'real make up expands one canonical initial-owner call');
assertSameValue(true,str_contains($recipe,'bin/fmonitor2-provision-initial-admin.php --resume-existing-local --email'),'INTENTIONAL_RED: real make up explicitly selects local resume before email');
$migration=strpos($recipe,'bin/fmonitor2-migrate.php');if($migration===false)$migration=strpos($recipe,'prepare');$provision=strpos($recipe,'bin/fmonitor2-provision-initial-admin.php');assertSameValue(true,$migration!==false&&$provision!==false&&$migration<$provision,'local resume occurs after migration/prepare in executed recipe order');
assertSameValue(true,str_contains($cli,"'--resume-existing-local'"),'INTENTIONAL_RED: CLI parses explicit local resume intent');
assertSameValue(true,str_contains($cli,'resumeExistingLocal'),'INTENTIONAL_RED: CLI delegates local resume to IdentityAccess owner');
assertSameValue(true,str_contains($owner,'public static function resumeExistingLocal'),'INTENTIONAL_RED: IdentityAccess owns separate read-only continuation');
assertSameValue(true,str_contains($cli,'MariaDbInitialOwnerProvisioning::provision('),'strict production provisioning remains a separate default call');
assertSameValue(false,str_contains($make,'IDENTITY_NOT_EMPTY')||str_contains($make,'|| true'),'Make does not convert arbitrary provisioning failure to success');
foreach(['bin/yii','deploy/runtime/compose.yaml','config/yii/console.php','config/yii/web.php']as$path){$source=(string)file_get_contents($root.'/'.$path);assertSameValue(false,str_contains($source,'--resume-existing-local'),"{$path} does not opt production/web/migration startup into local resume");}
echo "PASS: INITIAL-OWNER-PROVISIONING-001 local route ownership\n";
