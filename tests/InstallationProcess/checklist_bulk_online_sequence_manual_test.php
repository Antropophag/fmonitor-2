<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

function bulkBrowser(array$outcomes=[],array$extra=[]):array
{
 $root=dirname(__DIR__,2);$fixture=array_replace(['source'=>(string)file_get_contents($root.'/app/PilotHttp/checklist.js'),'rootDataset'=>['enabled'=>'true','itemCompletionEnabled'=>'true','legacyOperationsEnabled'=>'true','assignedCorrectionEnabled'=>'false','photoRevokeEnabled'=>'false','objectId'=>'4512','userId'=>'18','csrf'=>str_repeat('c',64),'projection'=>base64_encode(json_encode(['revision'=>0,'crew'=>[['tabId'=>1042,'fio'=>'Монтажник','employmentStatus'=>'employed']],'items'=>new stdClass(),'photos'=>[],'completedSections'=>new stdClass()],JSON_THROW_ON_ERROR))],'controls'=>['item'=>false,'photo'=>false,'installer'=>false,'bulk'=>true],'bulkItemCount'=>3,'activateBulkConfirm'=>true,'networkOutcomes'=>$outcomes],$extra);
 $process=proc_open(['node',$root.'/tests/InstallationProcess/support/inspection_item_complete_ui_browser.js'],[['pipe','r'],['pipe','w'],['pipe','w']],$pipes,$root);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: browser harness');fwrite($pipes[0],json_encode($fixture,JSON_THROW_ON_ERROR));fclose($pipes[0]);$stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);assertSameValue(0,$exit,'executable checklist controller '.trim($stderr));return json_decode($stdout,true,16,JSON_THROW_ON_ERROR);
}

$accepted=bulkBrowser();
assertSameValue(3,$accepted['queuedAtFirstSend']??null,'all chosen items are durably pending before the first network request resolves');
assertSameValue(['item_completed','item_completed','item_completed'],$accepted['sentTypes']??null,'bulk sends every work item');
assertSameValue([0,1,2],$accepted['sentBaseRevisions']??null,'never-sent successors receive the latest accepted native revision');

$conflict=bulkBrowser(['accepted','conflict','accepted']);
assertSameValue(3,$conflict['queuedAtFirstSend']??null,'conflict path also paints and persists the whole chosen batch first');
assertSameValue([0,1],$conflict['sentBaseRevisions']??null,'conflict stops the dependent successor chain');
assertSameValue(['accepted','conflict','queued'],$conflict['finalOperationStatuses']??null,'blocked successor remains queued without payload rewriting');
assertSameValue('conflict',$conflict['syncBannerState']??null,'conflict is never presented as accepted');
$retry=bulkBrowser(['retryable']);
assertSameValue([0],$retry['sentBaseRevisions']??null,'retryable failure stops before stamping a successor payload');
assertSameValue(['retryable_error','queued','queued'],$retry['finalOperationStatuses']??null,'retry preserves the attempted payload and leaves successors never sent');
assertSameValue([0,null,null],$retry['storedBaseRevisions']??null,'retry keeps the attempted base revision exact and does not stamp never-sent successors');
assertSameValue('error',$retry['syncBannerState']??null,'retryable failure is never presented as accepted');
$device='22222222-2222-4222-8222-222222222222';$batch='33333333-3333-4333-8333-333333333333';$ops=[];foreach([0,1,2]as$i)$ops[$i]=['id'=>'op'.$i,'clientOperationId'=>sprintf('11111111-1111-4111-8111-%012d',$i+1),'deviceInstallationId'=>$device,'scope'=>'18:'.$device.':4512','type'=>'item_completed','status'=>'queued','deviceTime'=>'2026-09-07T12:00:00+03:00','baseRevision'=>$i===0?0:null,'localPredecessorId'=>$i===0?null:sprintf('11111111-1111-4111-8111-%012d',$i),'localBatchId'=>$batch,'localBatchSequence'=>$i,'sectionId'=>1,'itemId'=>28+$i,'installerTabIds'=>['1042']];
$resume=bulkBrowser([],['controls'=>['item'=>false,'photo'=>false,'installer'=>false,'bulk'=>false],'bulkItemCount'=>1,'activateBulkConfirm'=>false,'deviceId'=>$device,'initialOperations'=>[$ops[2],$ops[0],$ops[1]]]);
assertSameValue([28,29,30],array_column($resume['sentBodies']??[],'itemId'),'reloaded shuffled batch follows persisted predecessor order');
assertSameValue([0,1,2],$resume['sentBaseRevisions']??null,'reloaded shuffled batch preserves head observation and predecessor acknowledgements');
echo "PASS optimistic online bulk persists together, sequences revisions, and stops on conflict\n";
