<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/autoload.php';
if (!class_exists(\yii\db\Connection::class,false)) require dirname(__DIR__,2).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/Yii2/Yii2AuthFixture.php';
use FMonitor2\Tests\Yii2\Yii2AuthFixture;
use FMonitor2\Otiz\OtizSettlementV2;
$f=new Yii2AuthFixture(dirname(__DIR__,2));$db=null;
try {
 $f->setPermission('otiz.manage');$env=$f->environment();
 $db=new yii\db\Connection(['dsn'=>'mysql:host='.$env['FMONITOR_DB_HOST'].';port='.$env['FMONITOR_DB_PORT'].';dbname='.$f->database,'username'=>$env['FMONITOR_DB_USER'],'password'=>$env['FMONITOR_DB_PASSWORD'],'charset'=>'utf8mb4']);$db->open();
 $owner=new OtizSettlementV2($db,$f->prefix,static fn()=>'2026-10-29T19:00:00+03:00',static fn()=>'allow');
 $n=1;$op=static function()use(&$n):string{return sprintf('29292929-2929-4292-8292-%012d',$n++);};
 $input=['reportDate'=>'2026-10-29','recipientPolicyVersion'=>'reserved-shares-v1','objects'=>[['objectId'=>4512,'regnumber'=>'TEST-4512','fundCents'=>1000000,'kssBp'=>9000,'entitlements'=>[['sourceKind'=>'fixture','sourceId'=>'reserved-work','sourceRevision'=>'1','kind'=>'progress','recognitionDate'=>'2026-10-20','grossCents'=>1000000]],'recipients'=>[['employeeId'=>'A','tabNumber'=>'001','name'=>'A','weight'=>60,'employment'=>'employed'],['employeeId'=>'B','tabNumber'=>'002','name'=>'B','weight'=>40,'employment'=>'dismissed']]]]];
 $d=$owner->createDraft(9101,$input,$op());$id=$d['calculationId'];
 assertSameValue(['A'=>540000,'B'=>0],array_column($d['recipients'],'amountCents','employeeId'),'INTENDED_RED dismissed share is reserved, never paid automatically');
 assertSameValue(360000,$d['reservedCents']??null,'reserved total is separate from payable');
 assertSameValue(360000,array_column($d['recipients'],'reservedCents','employeeId')['B']??null,'reserve belongs to exact original recipient');
 $accepted=$owner->accept(9101,$id,$d['revision'],$op());
 $claims=$db->createCommand('SELECT * FROM '.$f->prefix.'fm2_otiz_entitlement_claims')->queryAll();
 $payment=$owner->markPaid(9101,$id,$accepted['revision'],'2026-10-29',$op());
 assertSameValue(540000,$payment['paidCents'],'only payable obligations paid');
 assertSameValue(360000,$owner->read($id)['remainingReservedCents']??null,'payment leaves reserve available');
 $saved=$owner->read($id);
 assertSameValue(true,method_exists($owner,'createReserveReleaseDraft'),'public reserve release command exists');
 $releaseOp=$op();$originalReleaseArgs=[9101,$id,$saved['revision'],4512,'B',[['employeeId'=>'B','weight'=>1]],'Проверка завершена, выплатить исходному монтажнику',$releaseOp];
 $r=$owner->createReserveReleaseDraft(...$originalReleaseArgs);
 assertSameValue(360000,$r['totalCents'],'release allocates saved reserve without a new work calculation');
 assertSameValue($r,$owner->createReserveReleaseDraft(...$originalReleaseArgs),'release create replay');
 assertSameValue(360000,$owner->read($id)['remainingReservedCents'],'draft does not consume reserve');
 $r2=$owner->createReserveReleaseDraft(9101,$id,$saved['revision'],4512,'B',[['employeeId'=>'A','weight'=>1]],'Другой черновик',$op());
 $r=$owner->accept(9101,$r['calculationId'],$r['revision'],$op());
 assertSameValue(0,$owner->read($id)['remainingReservedCents'],'accepted release consumes reserve before payment');
 assertSameValue($claims,$db->createCommand('SELECT * FROM '.$f->prefix.'fm2_otiz_entitlement_claims')->queryAll(),'release never recognizes original work twice');
 $blocked=false;try{$owner->accept(9101,$r2['calculationId'],$r2['revision'],$op());}catch(DomainException){$blocked=true;}assertSameValue(true,$blocked,'second release cannot consume same money');
 $paidRelease=$owner->markPaid(9101,$r['calculationId'],$r['revision'],'2026-10-29',$op());
 assertSameValue(360000,$paidRelease['paidCents'],'separate reserve payment exact');
 assertSameValue(540000,$owner->read($id)['paidCents'],'original paid fact unchanged');
 $manual=$input;$manual['objects'][0]['objectId']=4513;$manual['objects'][0]['entitlements'][0]['sourceId']='manual-reserve';$manual['objects'][0]['kssBp']=10000;$manual['objects'][0]['recipients'][1]['employment']='employed';
 $d=$owner->createDraft(9101,$manual,$op());$d=$owner->saveDecision(9101,$d['calculationId'],$d['revision'],'A','do_not_pay','Разбирательство СБ',$op());
 assertSameValue(['A'=>0,'B'=>400000],array_column($d['recipients'],'amountCents','employeeId'),'manual exclusion does not redistribute');
 assertSameValue(600000,$d['reservedCents'],'manual exclusion preserves amount');
 $before=$d;$failed=false;try{$owner->saveDecision(9101,$d['calculationId'],$d['revision'],'B','do_not_pay',' ',$op());}catch(DomainException $e){$failed=$e->getMessage()==='REASON_REQUIRED';}assertSameValue(true,$failed,'reason mandatory');assertSameValue($before,$owner->read($d['calculationId']),'rejection changes no revision or amounts');
 $d=$owner->saveDecision(9101,$d['calculationId'],$d['revision'],'A','pay','Проверка завершена',$op());assertSameValue(0,$d['reservedCents'],'clear manual exclusion');assertSameValue(1000000,$d['totalCents'],'restores original allocations');
 // Complete boundary matrix uses the same public owner and independent financial rows.
 $state=static function()use($db,$f):string{$s=[];foreach(['calculation_revisions','entitlement_claims','recipient_obligations','deductions','payment_decisions','payment_facts','payment_reversals','v2_operations','v2_events']as$t)$s[$t]=$db->createCommand('SELECT * FROM '.$f->prefix.'fm2_otiz_'.$t.' ORDER BY 1')->queryAll();return hash('sha256',json_encode($s,JSON_THROW_ON_ERROR));};
 $deny=static function(callable$call,string$label)use($state):void{$before=$state();$failed=false;try{$call();}catch(DomainException){$failed=true;}assertSameValue(true,$failed,$label);assertSameValue($before,$state(),$label.' preserves financial facts and receipts');};
 $conflictArgs=$originalReleaseArgs;$conflictArgs[6]='Другой смысл того же operationId';$beforeConflict=$state();$conflict=false;try{$owner->createReserveReleaseDraft(...$conflictArgs);}catch(DomainException$e){$conflict=$e->getMessage()==='OPERATION_CONFLICT';}assertSameValue(true,$conflict,'release same UUID different payload exact conflict');assertSameValue($beforeConflict,$state(),'conflicting replay preserves all financial state');
 $previewBefore=$state();$preview=$owner->previewDecision(9101,$d['calculationId'],$d['revision'],'A','do_not_pay','Проверка');assertSameValue(600000,$preview['reservedCents']??null,'preview reserve exact');assertSameValue($previewBefore,$state(),'preview financially neutral');
 $d=$owner->saveDeduction(9101,$d['calculationId'],$d['revision'],4513,'A',10000,'Личное удержание','',$op());
 $deny(fn()=>$owner->saveDecision(9101,$d['calculationId'],$d['revision'],'A','do_not_pay','Проверка',$op()),'personal deduction exclusion conflict');
 $full=$input;$full['objects'][0]['objectId']=4514;$full['objects'][0]['entitlements'][0]['sourceId']='full-reserve';$full['objects'][0]['kssBp']=10000;$full['objects'][0]['recipients'][0]['employment']='unknown';
 $z=$owner->createDraft(9101,$full,$op());assertSameValue([0,1000000],[$z['totalCents'],$z['reservedCents']],'all-excluded full reserve');
 $z=$owner->saveDecision(9101,$z['calculationId'],$z['revision'],'B','pay','Попытка обойти увольнение',$op());assertSameValue(0,$z['totalCents'],'ordinary decision cannot bypass dismissed policy');
 $z=$owner->accept(9101,$z['calculationId'],$z['revision'],$op());$deny(fn()=>$owner->markPaid(9101,$z['calculationId'],$z['revision'],'2026-10-29',$op()),'zero obligation is not payment');
 $release=fn($actor,$object,$employee,$recipients,$reason,$revision)=>$owner->createReserveReleaseDraft($actor,$z['calculationId'],$revision,$object,$employee,$recipients,$reason,$op());
 foreach([
  [999999,4514,'A',[['employeeId'=>'A','weight'=>1]],'Причина',$z['revision']],
  [9101,999999,'A',[['employeeId'=>'A','weight'=>1]],'Причина',$z['revision']],
  [9101,4514,'FOREIGN',[['employeeId'=>'A','weight'=>1]],'Причина',$z['revision']],
  [9101,4514,'A',[['employeeId'=>'FOREIGN','weight'=>1]],'Причина',$z['revision']],
  [9101,4514,'A',[['employeeId'=>'A','weight'=>0]],'Причина',$z['revision']],
  [9101,4514,'A',[['employeeId'=>'A','weight'=>-1]],'Причина',$z['revision']],
  [9101,4514,'A',[['employeeId'=>'A','weight'=>1.5]],'Причина',$z['revision']],
  [9101,4514,'A',[['employeeId'=>'A','weight'=>1],['employeeId'=>'A','weight'=>1]],'Причина',$z['revision']],
  [9101,4514,'A',[['employeeId'=>'A','weight'=>1]],' ',$z['revision']],
  [9101,4514,'A',[['employeeId'=>'A','weight'=>1]],'Причина',$z['revision']-1],
 ]as$args)$deny(fn()=>$release(...$args),'invalid reserve release payload');
 $weighted=$release(9101,4514,'A',[['employeeId'=>'A','weight'=>2],['employeeId'=>'B','weight'=>1]],'Распределить бригаде',$z['revision']);assertSameValue(['A'=>400000,'B'=>200000],array_column($weighted['recipients'],'amountCents','employeeId'),'saved 600000 reserve split 2:1');
 $weighted=$owner->accept(9101,$weighted['calculationId'],$weighted['revision'],$op());assertSameValue(400000,$owner->read($z['calculationId'])['remainingReservedCents'],'only selected employee reserve consumed');
 $deny(fn()=>$owner->cancel(9101,$z['calculationId'],$z['revision'],'Недопустимая отмена исходника',$op()),'source has accepted release dependency');
 $beforeReplacement=$state();try{$replacement=$owner->createReplacementDraft(9101,$z['calculationId'],$z['revision'],$full,'Попытка замены исходника с распределением',$op());$deny(fn()=>$owner->accept(9101,$replacement['calculationId'],$replacement['revision'],$op()),'source replacement acceptance blocked by release dependency');}catch(DomainException){assertSameValue($beforeReplacement,$state(),'refused replacement creation has no facts');}assertSameValue('accepted',$owner->read($z['calculationId'])['status'],'source cannot be replaced while active release exists');
 $weighted=$owner->cancel(9101,$weighted['calculationId'],$weighted['revision'],'Распределение отменено до перечисления',$op());assertSameValue(1000000,$owner->read($z['calculationId'])['remainingReservedCents'],'unpaid release cancellation restores reserve');
 $deny(fn()=>$owner->cancel(9101,$r['calculationId'],$r['revision'],'Оплачено',$op()),'paid release cannot cancel');
 $badAdmission=new OtizSettlementV2($db,$f->prefix,static fn()=>'2026-10-29T19:00:00+03:00',static fn()=>['decision'=>'blocked','sourceRevision'=>'new-incident','incidentId'=>'sb-check']);
 $waiting=$release(9101,4514,'A',[['employeeId'=>'A','weight'=>1]],'Ожидает проверки',$z['revision']);$deny(fn()=>$badAdmission->accept(9101,$waiting['calculationId'],$waiting['revision'],$op()),'incident after draft blocks release acceptance');
 $db->createCommand("CREATE TRIGGER reserve_fault BEFORE INSERT ON {$f->prefix}fm2_otiz_recipient_obligations FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='reserve injected failure'")->execute();$before=$state();$failed=false;try{$owner->accept(9101,$waiting['calculationId'],$waiting['revision'],$op());}catch(Throwable){$failed=true;}finally{$db->createCommand('DROP TRIGGER reserve_fault')->execute();}assertSameValue(true,$failed,'injected obligation failure');assertSameValue($before,$state(),'failed release acceptance atomic');
 $penny=$full;$penny['objects'][0]['objectId']=4516;$penny['objects'][0]['entitlements'][0]['sourceId']='penny-reserve';$penny['objects'][0]['entitlements'][0]['grossCents']=1000001;$penny['objects'][0]['fundCents']=1000001;$pc=$owner->createDraft(9101,$penny,$op());$pc=$owner->accept(9101,$pc['calculationId'],$pc['revision'],$op());$pr=$owner->createReserveReleaseDraft(9101,$pc['calculationId'],$pc['revision'],4516,'A',[['employeeId'=>'B','weight'=>1],['employeeId'=>'A','weight'=>1]],'Копейка по стабильной identity',$op());assertSameValue(['A'=>300001,'B'=>300000],array_column($pr['recipients'],'amountCents','employeeId'),'indivisible reserve penny stable across reversed input order');assertSameValue(600001,$pr['totalCents'],'no penny created or lost');

 // Authority is current even for an exact idempotent replay.
 $actorState=$db->createCommand("SELECT status,activation_state FROM {$f->prefix}fm2_pilot_users WHERE user_id=9101")->queryOne();
 foreach(["UPDATE {$f->prefix}fm2_pilot_users SET status=0 WHERE user_id=9101","UPDATE {$f->prefix}fm2_pilot_users SET activation_state='invited' WHERE user_id=9101","UPDATE {$f->prefix}fm2_pilot_roles SET status=0 WHERE role_id IN(SELECT role_id FROM {$f->prefix}fm2_pilot_user_roles WHERE user_id=9101)"]as$disable){$db->createCommand($disable)->execute();try{$deny(fn()=>$owner->createReserveReleaseDraft(...$originalReleaseArgs),'disabled actor cannot replay old receipt');$deny(fn()=>$owner->reserveReleaseOptions(9101,$z['calculationId'],4514,'A'),'disabled actor cannot read privileged release options');}finally{$db->createCommand("UPDATE {$f->prefix}fm2_pilot_users SET status=1,activation_state='active' WHERE user_id=9101")->execute();$db->createCommand("UPDATE {$f->prefix}fm2_pilot_roles SET status=1 WHERE role_id IN(SELECT role_id FROM {$f->prefix}fm2_pilot_user_roles WHERE user_id=9101)")->execute();}}
 echo "PASS: reserved shares, original payout, release validation/cancellation/admission/atomicity\n";
}finally{if($db!==null)$db->close();$f->close();}
