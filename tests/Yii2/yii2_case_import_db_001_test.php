<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
/** YII2-IMPORTS-WORKFORCE-001 A2: Yii seam retains the approved case importer. */
$root=dirname(__DIR__,2);$controller=(string)@file_get_contents($root.'/app/YiiRuntime/Commands/CaseImportController.php');$adapter=(string)@file_get_contents($root.'/app/YiiRuntime/CaseImportConsole.php');
assertSameValue(true,$controller!==''&&$adapter!=='','INTENDED_RED: Yii case import composition exists.');
assertSameValue(1,substr_count($adapter,'PilotCaseImporter'),'One approved case import owner is composed.');
foreach (['begin_transaction','INSERT INTO','UPDATE ','DELETE ','rapid-pilot','app/demo'] as $forbidden) assertSameValue(false,str_contains($controller.$adapter,$forbidden),'Transport owns no domain persistence/oracle dependency: '.$forbidden);
$runOracle=static function(array$environment,string$label)use($root):void{$command=['/usr/bin/env','-i'];foreach($environment as$key=>$value)if(is_string($key)&&is_string($value))$command[]=$key.'='.$value;$pipes=[];$process=proc_open([...$command,PHP_BINARY,$root.'/tests/InstallationProcess/pilot_case_import_001_test.php'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: '.$label.' oracle must start.');$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);assertSameValue([0,''],[proc_close($process),$err],$label.' full case-import oracle succeeds.');assertSameValue(true,str_contains($out,'PASS: PILOT-CASE-IMPORT-001 CLI contract'),$label.' reaches terminal oracle.');};
$runOracle(array_replace(getenv(),['FMONITOR_CASE_IMPORT_TEST_SCRIPT'=>'tests/Support/yii_case_import_test_entrypoint.php']),'Yii direct');$runOracle(getenv(),'Legacy alias');echo "PASS: YII2-IMPORTS-WORKFORCE-001 direct and alias DB parity\n";
