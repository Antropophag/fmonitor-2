<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/PreopeningFixture.php';
// YII2-PREOPENING-JOURNEY-001: actor/input denial and atomic compound rollback via real HTTP.
$f=null;
try {
 $f=new PreopeningFixture(dirname(__DIR__,2));$p=$f->p;
 assertSameValue('selected',$f->base->selection->app()->selectAssignmentOrderComposition(FMonitor2\Tests\Support\SelectionNativeFixture::command())->status()->value,'native selected fixture');
 $accepted=$f->nativeOriginal('2026-09-01');assertSameValue('accepted',$accepted->status()->value,'native original fixture');
 $f->start();$cookies=[];assertSameValue(303,$f->login($cookies)['status'],'FKR login');$page=$f->request('GET','/pilot/objects/4512/execution',[],$cookies);assertSameValue(200,$page['status'],'INTENDED_RED Yii execution route');$f->noLegacy();
 $missingSelection=['_csrf'=>$f->token($cookies),'requestId'=>'11111111-1111-4111-8111-000000000077','mode'=>'new_order','expectedSelectionRevision'=>'0','controlEngineerUserId'=>'73','controlEngineerConfirmed'=>'yes','installerTabIds'=>[7001]];
 $selectedBefore=$f->rows('fm2_assignment_order_selections');$casesBefore=$f->rows('fm2_installation_cases');$filesBefore=$f->base->privateFiles();
 assertSameValue(404,$f->form('/pilot/objects/9999/assignment-order/selection',$missingSelection,$cookies)['status'],'INTENDED_RED native missing selection object maps404');
 assertSameValue($selectedBefore,$f->rows('fm2_assignment_order_selections'),'missing object creates no selected facts');assertSameValue($casesBefore,$f->rows('fm2_installation_cases'),'missing object never creates or changes a case');assertSameValue($filesBefore,$f->base->privateFiles(),'missing object preserves private evidence');
 $open=['_csrf'=>$f->token($cookies),'action'=>'open_confirmed','requestId'=>'33333333-3333-4333-8333-000000000010','orderId'=>'81','revisionId'=>$accepted->currentRevisionId(),'sequence'=>'0','actualStartDate'=>'2026-09-02'];
 foreach([
  [array_replace($open,['_csrf'=>'wrong']),400,'native CSRF'],
  [array_replace($open,['requestId'=>'invalid']),400,'invalid request ID'],
  [array_replace($open,['sequence'=>'-1']),400,'negative sequence'],
  [array_replace($open,['actualStartDate'=>'2099-09-02']),422,'future date'],
  [array_replace($open,['actualStartDate'=>'2026-08-31']),422,'before original date'],
  [array_replace($open,['sequence'=>'1']),422,'stale sequence'],
 ]as[$fields,$status,$label]){$before=$f->facts();$files=$f->base->privateFiles();assertSameValue($status,$f->form('/pilot/objects/4512/execution',$fields,$cookies)['status'],$label);assertSameValue($before,$f->facts(),'opening rejection no partial facts '.$label);assertSameValue($files,$f->base->privateFiles(),'opening denial bytes preserved');}
 $ordinary=[];assertSameValue(303,$f->login($ordinary,95)['status'],'reader login');$denied=$open;$denied['_csrf']=$f->token($ordinary);$before=$f->facts();assertSameValue(403,$f->form('/pilot/objects/4512/execution',$denied,$ordinary)['status'],'reader cannot open');assertSameValue($before,$f->facts(),'unauthorized opening no facts');
 foreach(['/pilot/objects/4512/assignment-orders/81/template','/pilot/objects/4512/assignment-order/selection']as$path){$before=$f->facts();assertSameValue(403,$f->form($path,['_csrf'=>$f->token($ordinary)],$ordinary)['status'],'reader command denial '.$path);assertSameValue($before,$f->facts(),'read grant does not become command grant');}
 foreach(['/pilot/objects/4512/assignment-orders/81/registration','/pilot/objects/4512/control-engineer','/pilot/objects/4512/open','/pilot/objects/4512/assignment-order/prepare']as$path){$before=$f->facts();assertSameValue(410,$f->form($path,['_csrf'=>$f->token($cookies)],$cookies)['status'],'obsolete writer410');assertSameValue($before,$f->facts(),'obsolete path no facts');}
 foreach([
  ['application','BEFORE INSERT',"{$p}fm2_assignment_order_applications","SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic application fault'"],
  ['opening','BEFORE UPDATE',"{$p}fm2_installation_cases","BEGIN IF NEW.actual_start_date IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic opening fault'; END IF; END"],
 ]as[$name,$event,$table,$body]){
  $trigger=$p.'preopening_fault_'.$name;$f->db->query("CREATE TRIGGER $trigger $event ON $table FOR EACH ROW $body");
  try{$before=$f->facts();$files=$f->base->privateFiles();$result=$f->form('/pilot/objects/4512/execution',$open,$cookies);assertSameValue(503,$result['status'],'native compound fault becomes unavailable '.$name);assertSameValue($before,$f->facts(),'whole application/opening rollback '.$name);assertSameValue($files,$f->base->privateFiles(),'rollback preserves original evidence');foreach(['synthetic',$f->dmlPassword,'SQLSTATE','Stack trace']as$secret)assertSameValue(false,str_contains($result['body'],$secret),'safe infrastructure response');}
  finally{$f->db->query("DROP TRIGGER $trigger");}
 }
 // Missing private config does not break card or metadata form; only upload acquisition.
 $saved=$f->base->safeLog.'.saved';rename($f->base->safeLog,$saved);
 try{$before=$f->facts();assertSameValue(200,$f->request('GET','/pilot/objects/4512',[],$cookies)['status'],'card independent of original safe log');assertSameValue(200,$f->request('GET','/pilot/objects/4512/assignment-orders/81/originals/submit',[],$cookies)['status'],'form metadata independent of safe log');$meta=$f->metadata($cookies);$meta['mode']='correction';$meta['rootOriginalId']=$accepted->rootOriginalId();$meta['targetRevisionId']=$accepted->currentRevisionId();$meta['expectedCurrentRevisionId']=$accepted->currentRevisionId();$meta['correctionReason']='Ошибка config';assertSameValue(503,$f->upload($cookies,$meta)['status'],'missing safe log fails before command');assertSameValue($before,$f->facts(),'config error no facts');assertSameValue(false,file_exists($f->base->safeLog),'HTTP does not create safe log');}
 finally{rename($saved,$f->base->safeLog);}
 assertSameValue(303,$f->form('/pilot/objects/4512/execution',$open,$cookies)['status'],'working command after removed faults');assertSameValue(1,count($f->rows('fm2_assignment_order_applications')),'one successful application only');
 echo "PASS: YII2-PREOPENING-JOURNEY-001 denial, resource isolation and atomic rollback\n";
}finally{if($f instanceof PreopeningFixture)$f->close();}
