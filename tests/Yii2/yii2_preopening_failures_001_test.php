<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/PreopeningFixture.php';
// YII2-PREOPENING-JOURNEY-001: actor/input denial and atomic compound rollback via real HTTP.
$f=null;
try {
 $f=new PreopeningFixture(dirname(__DIR__,2));$p=$f->p;
 assertSameValue('selected',$f->base->selection->app()->selectAssignmentOrderComposition(FMonitor2\Tests\Support\SelectionNativeFixture::command())->status()->value,'native selected fixture');
 $f->start();$cookies=[];assertSameValue(303,$f->login($cookies)['status'],'FKR login');$page=$f->request('GET','/pilot/objects/4512/execution',[],$cookies);assertSameValue(200,$page['status'],'INTENDED_RED Yii execution route');$f->noLegacy();
 $missingSelection=['_csrf'=>$f->token($cookies),'requestId'=>'11111111-1111-4111-8111-000000000077','mode'=>'new_order','expectedSelectionRevision'=>'0','expectedControlEngineerAssignmentRevision'=>'1','installerTabIds'=>[7001]];
 $selectedBefore=$f->rows('fm2_assignment_order_selections');$casesBefore=$f->rows('fm2_installation_cases');$filesBefore=$f->base->privateFiles();
 assertSameValue(404,$f->form('/pilot/objects/9999/assignment-order/selection',$missingSelection,$cookies)['status'],'INTENDED_RED native missing selection object maps404');
 assertSameValue($selectedBefore,$f->rows('fm2_assignment_order_selections'),'missing object creates no selected facts');assertSameValue($casesBefore,$f->rows('fm2_installation_cases'),'missing object never creates or changes a case');assertSameValue($filesBefore,$f->base->privateFiles(),'missing object preserves private evidence');
 $metadata=$f->metadata($cookies);
 foreach([
  [array_replace($metadata,['csrfToken'=>'wrong']),400,'native CSRF'],
  [array_replace($metadata,['requestId'=>'invalid']),400,'invalid request ID'],
  [array_replace($metadata,['appendixStartDate'=>'2026-02-30']),422,'invalid signed calendar date'],
 ]as[$fields,$status,$label]){$before=$f->facts();$files=$f->base->privateFiles();assertSameValue($status,$f->upload($cookies,$fields)['status'],$label);assertSameValue($before,$f->facts(),'upload rejection no partial facts '.$label);assertSameValue($files,$f->base->privateFiles(),'upload denial bytes preserved');}
 $ordinary=[];assertSameValue(303,$f->login($ordinary,95)['status'],'reader login');$before=$f->facts();assertSameValue(403,$f->upload($ordinary,$f->metadata($ordinary))['status'],'reader cannot upload/open');assertSameValue($before,$f->facts(),'unauthorized opening no facts');
 foreach(['/pilot/objects/4512/assignment-orders/81/template','/pilot/objects/4512/assignment-order/selection']as$path){$before=$f->facts();assertSameValue(403,$f->form($path,['_csrf'=>$f->token($ordinary)],$ordinary)['status'],'reader command denial '.$path);assertSameValue($before,$f->facts(),'read grant does not become command grant');}
 foreach(['/pilot/objects/4512/assignment-orders/81/registration','/pilot/objects/4512/control-engineer','/pilot/objects/4512/open','/pilot/objects/4512/assignment-order/prepare']as$path){$before=$f->facts();assertSameValue(410,$f->form($path,['_csrf'=>$f->token($cookies)],$cookies)['status'],'obsolete writer410');assertSameValue($before,$f->facts(),'obsolete path no facts');}
 foreach(['open','open_confirmed']as$action){$before=$f->facts();assertSameValue(410,$f->form('/pilot/objects/4512/execution',['_csrf'=>$f->token($cookies),'action'=>$action],$cookies)['status'],'manual opening retired');assertSameValue($before,$f->facts(),'manual opening no facts');}
 foreach([
  ['application','BEFORE INSERT',"{$p}fm2_assignment_order_applications","SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic application fault'"],
  ['opening','BEFORE UPDATE',"{$p}fm2_installation_cases","BEGIN IF NEW.actual_start_date IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic opening fault'; END IF; END"],
 ]as[$name,$event,$table,$body]){
  $trigger=$p.'preopening_fault_'.$name;$f->db->query("CREATE TRIGGER $trigger $event ON $table FOR EACH ROW $body");
  try{$result=$f->upload($cookies,$metadata);assertSameValue(503,$result['status'],'native compound fault becomes unavailable '.$name);$receipt=json_decode($result['body'],true,512,JSON_THROW_ON_ERROR);assertSameValue(['pending',true],[$receipt['status'],$receipt['retryable']],'accepted evidence exposes pending retry');assertSameValue(1,count($f->rows('fm2_assignment_order_original_revisions')),'one retained original');assertSameValue(1,count($f->rows('fm2_assignment_order_original_starts')),'one retained signed date');assertSameValue([], $f->rows('fm2_assignment_order_applications'),'application rolls back '.$name);assertSameValue([], $f->rows('fm2_checklist_template_associations'),'checklist rolls back '.$name);assertSameValue(null,$f->rows('fm2_installation_cases')[0]['actual_start_date'],'opening rolls back '.$name);assertSameValue(0,count(array_filter($f->rows('fm2_process_events'),static fn($row)=>$row['event_type']==='installation_opened_from_original')),'no false opening audit');foreach(['synthetic',$f->dmlPassword,'SQLSTATE','Stack trace']as$secret)assertSameValue(false,str_contains($result['body'],$secret),'safe infrastructure response');}
  finally{$f->db->query("DROP TRIGGER $trigger");}
 }
 // Missing private config does not break card or metadata form; only upload acquisition.
 $saved=$f->base->safeLog.'.saved';rename($f->base->safeLog,$saved);
 try{$before=$f->facts();assertSameValue(200,$f->request('GET','/pilot/objects/4512',[],$cookies)['status'],'card independent of original safe log');assertSameValue(200,$f->request('GET','/pilot/objects/4512/assignment-orders/81/originals/submit',[],$cookies)['status'],'form metadata independent of safe log');$meta=$metadata;assertSameValue(503,$f->upload($cookies,$meta)['status'],'missing safe log fails before command');assertSameValue($before,$f->facts(),'config error no facts');assertSameValue(false,file_exists($f->base->safeLog),'HTTP does not create safe log');}
 finally{rename($saved,$f->base->safeLog);}
 assertSameValue(200,$f->upload($cookies,$metadata)['status'],'same retained upload opens after removed faults');assertSameValue(1,count($f->rows('fm2_assignment_order_applications')),'one successful application only');
 echo "PASS: YII2-PREOPENING-JOURNEY-001 denial, resource isolation and atomic rollback\n";
}finally{if($f instanceof PreopeningFixture)$f->close();}
