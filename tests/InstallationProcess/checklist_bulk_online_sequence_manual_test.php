<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

$root=dirname(__DIR__,2);$fixture=['source'=>(string)file_get_contents($root.'/app/PilotHttp/checklist.js'),'rootDataset'=>['enabled'=>'true','itemCompletionEnabled'=>'true','legacyOperationsEnabled'=>'true','assignedCorrectionEnabled'=>'false','photoRevokeEnabled'=>'false','objectId'=>'4512','userId'=>'18','csrf'=>str_repeat('c',64),'projection'=>base64_encode(json_encode(['revision'=>0,'crew'=>[['tabId'=>1042,'fio'=>'Монтажник','employmentStatus'=>'employed']],'items'=>new stdClass(),'photos'=>[],'completedSections'=>new stdClass()],JSON_THROW_ON_ERROR))],'controls'=>['item'=>false,'photo'=>false,'installer'=>false,'bulk'=>true],'bulkItemCount'=>3,'activateBulkConfirm'=>true];
$process=proc_open(['node',$root.'/tests/InstallationProcess/support/inspection_item_complete_ui_browser.js'],[['pipe','r'],['pipe','w'],['pipe','w']],$pipes,$root);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: browser harness');fwrite($pipes[0],json_encode($fixture,JSON_THROW_ON_ERROR));fclose($pipes[0]);$stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);assertSameValue(0,$exit,'executable checklist controller '.trim($stderr));$result=json_decode($stdout,true,16,JSON_THROW_ON_ERROR);
assertSameValue(['item_completed','item_completed','item_completed'],$result['sentTypes']??null,'bulk handler sends every work item');
assertSameValue([0,1,2],$result['sentBaseRevisions']??null,'online bulk waits for each accepted native revision before creating the next operation');
echo "PASS online bulk sends sequential native checklist revisions\n";
