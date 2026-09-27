<?php
declare(strict_types=1);
// OTIZ-SETTLEMENT-V2-001 H01/P02/D03: preview is neutral; edits address exact facts.
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/autoload.php';
if (!class_exists(\yii\db\Connection::class,false)) require dirname(__DIR__,2).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/Yii2/Yii2AuthFixture.php';
use FMonitor2\Tests\Yii2\Yii2AuthFixture;
use FMonitor2\Otiz\OtizSettlementV2;

$fixture = new Yii2AuthFixture(dirname(__DIR__,2));
$yii = null;
try {
    $fixture->setPermission('otiz.manage');
    $env = $fixture->environment();
    $yii = new yii\db\Connection(['dsn'=>'mysql:host='.$env['FMONITOR_DB_HOST'].';port='.$env['FMONITOR_DB_PORT'].';dbname='.$fixture->database,'username'=>$env['FMONITOR_DB_USER'],'password'=>$env['FMONITOR_DB_PASSWORD'],'charset'=>'utf8mb4']);
    $yii->open();
    $owner = new OtizSettlementV2($yii,$fixture->prefix,static fn()=>'2026-09-27T12:00:00+03:00',static fn()=>'allow');
    $input = ['reportDate'=>'2026-09-26','objects'=>[['objectId'=>4512,'fundCents'=>10000000,'kssBp'=>10000,
        'entitlements'=>[['sourceKind'=>'fixture','sourceId'=>'editing','sourceRevision'=>'1','kind'=>'progress','recognitionDate'=>'2026-09-20','grossCents'=>1200000]],
        'recipients'=>[['employeeId'=>'A','tabNumber'=>'001','name'=>'A','weight'=>60,'employment'=>'employed'],['employeeId'=>'B','tabNumber'=>'002','name'=>'B','weight'=>40,'employment'=>'dismissed']]]]];
    $counter = 1;
    $op = static function () use (&$counter): string { return sprintf('ffffffff-ffff-4fff-8fff-%012d',$counter++); };
    $draft = $owner->createDraft(9101,$input,$op());
    $id = $draft['calculationId'];
    $fingerprint = static function () use ($yii,$fixture): string {
        $state=[];
        foreach (['calculation_revisions','deductions','payment_decisions','v2_events','v2_operations','entitlement_claims','recipient_obligations','payment_facts','payment_reversals'] as $table) {
            $state[$table]=$yii->createCommand('SELECT * FROM '.$fixture->prefix.'fm2_otiz_'.$table)->queryAll();
        }
        return hash('sha256',json_encode($state,JSON_THROW_ON_ERROR));
    };
    assertSameValue(true,method_exists($owner,'previewDecision')&&method_exists($owner,'previewDeduction'),'INTENDED_RED: server preview public seam exists');
    $before=$fingerprint();
    foreach([
        static fn()=>$owner->saveDeduction(9101,$id,$draft['revision'],4512,'A',10000,'   ','',$op()),
        static fn()=>$owner->saveDecision(9101,$id,$draft['revision'],'B','do_not_pay','',$op()),
    ]as$missingReason){try{$missingReason();throw new TestFailure('required reason missing but financial edit persisted');}catch(DomainException $e){assertSameValue('REASON_REQUIRED',$e->getMessage(),'reason is mandatory at public application seam');}}
    assertSameValue($before,$fingerprint(),'missing reason leaves no decision/deduction/receipt/revision');
    foreach([
        static fn()=>$owner->previewDeduction(9101,$id,$draft['revision'],4512,'A',0,'Нулевая сумма',''),
        static fn()=>$owner->saveDeduction(9101,$id,$draft['revision'],4512,'A',0,'Нулевая сумма','',$op()),
    ]as$zero){try{$zero();throw new TestFailure('zero deduction accepted');}catch(DomainException $e){assertSameValue('INVALID_MONEY',$e->getMessage(),'deduction must be positive in preview and save');}}
    assertSameValue($before,$fingerprint(),'zero deduction writes no facts in either path');
    $decision=$owner->previewDecision(9101,$id,$draft['revision'],'B','do_not_pay','Два получателя');
    assertSameValue([1200000,0],array_column($decision['recipients'],'amountCents'),'decision preview uses original 60/40 crew');
    $deduction=$owner->previewDeduction(9101,$id,$draft['revision'],4512,'A',100000,'Личное удержание','');
    assertSameValue([620000,480000],array_column($deduction['recipients'],'amountCents'),'personal preview never redistributes');
    assertSameValue($before,$fingerprint(),'all preview facts/receipts/revisions remain unchanged');
    foreach ([[73,$draft['revision'],'FORBIDDEN'],[9101,$draft['revision']-1,'STALE_REVISION']] as [$actor,$revision,$code]) {
        try {$owner->previewDecision($actor,$id,$revision,'B','do_not_pay','Причина');throw new TestFailure('preview accepted invalid authorization/revision');}
        catch(DomainException $e){assertSameValue($code,$e->getMessage(),'preview uses same guard as save');}
    }
    $draft=$owner->saveDeduction(9101,$id,$draft['revision'],4512,'A',100000,'Первый факт','',$op());
    $draft=$owner->saveDeduction(9101,$id,$draft['revision'],4512,null,200000,'Второй факт','',$op());
    $deductionId=$draft['deductions'][0]['id'];
    $draft=$owner->removeDeduction(9101,$id,$draft['revision'],$deductionId,'Удалён только первый',$op());
    assertSameValue([1,1000000,'Второй факт'],[count($draft['deductions']),$draft['totalCents'],$draft['deductions'][0]['reason']],'same blank document does not remove another deduction');
    $before=$fingerprint();
    try {$owner->removeDeduction(9101,$id,$draft['revision'],PHP_INT_MAX,'Чужой ID',$op());throw new TestFailure('foreign deduction removed');}
    catch(DomainException $e){assertSameValue('DEDUCTION_NOT_FOUND',$e->getMessage(),'foreign/inactive id rejected');}
    assertSameValue($before,$fingerprint(),'invalid removal writes no facts');
    $draft=$owner->saveDecision(9101,$id,$draft['revision'],'B','do_not_pay','Сохранить решение',$op());
    $changed=$input;
    $changed['objects'][0]['recipients']=[$input['objects'][0]['recipients'][0]];
    $before=$fingerprint();
    try {$owner->refreshDraft(9101,$id,$draft['revision'],$changed,$op());throw new TestFailure('refresh silently discarded decision recipient');}
    catch(DomainException $e){assertSameValue(true,in_array($e->getMessage(),['DRAFT_MANUAL_FACT_CONFLICT','REPLACEMENT_MANUAL_FACT_CONFLICT'],true),'lost recipient is an explicit manual-fact conflict');}
    assertSameValue($before,$fingerprint(),'failed refresh preserves saved decision and revision');
    $accepted=$owner->accept(9101,$id,$draft['revision'],$op());
    foreach(['','2026-02-30','2026-9-1','2026-09-01T12:00:00','yesterday']as$date){
        $before=$fingerprint();
        try{$owner->markPaid(9101,$id,$accepted['revision'],$date,$op());throw new TestFailure('invalid payment date accepted');}
        catch(DomainException $e){assertSameValue('INVALID_PAYMENT_DATE',$e->getMessage(),'exact real calendar date required');}
        assertSameValue($before,$fingerprint(),'invalid date leaves all financial facts unchanged');
    }
    echo "settlement_v2_editing_001_test: OK\n";
} finally { if($yii!==null)$yii->close(); $fixture->close(); }
