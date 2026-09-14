<?php
// OTIZ-EXCEL-CALCULATION-001 — independent literal monetary expectations, root authored.
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\Otiz\PremiumCalculationV2 as Excel;
function exSource(string $id):array{return ['label'=>'Synthetic '.$id,'locator'=>'fixture://excel/'.$id,'contentSha256'=>hash('sha256',$id)];}
function exOperands(int $premium=10000,string $deadline='2026-08-01',string $report='2026-08-11',?string $pto=null,int $shaft=10000,int $progress=10000):array{
    $out=[];foreach(['premiumCents'=>$premium,'shaftBp'=>$shaft,'progressBp'=>$progress,'deadlineDate'=>$deadline,'reportDate'=>$report,'completionDate'=>$pto] as $key=>$value)$out[$key]=['value'=>$value,'effectiveDate'=>'2026-08-01','source'=>exSource($key)];return $out;
}
function exPayments(int $paid=0):array{return ['closures'=>[['amountCents'=>$paid,'closedOn'=>'2026-08-01','source'=>exSource('payment')]],'actualPayouts'=>[]];}
function exReject(Closure $action,string $label):void{try{$action();}catch(InvalidArgumentException){return;}throw new TestFailure('Expected invalid input rejection: '.$label);}
$failures=[];
$checks=[];
$checks['version and exact monetary order']=static function():void{
    $o=exOperands(49725000,'2026-08-08','2026-08-17');$p=exPayments(9360000);$before=serialize([$o,$p]);$r=Excel::calculate($o,$p);
    assertSameValue('premium-calculation-v2-excel',$r['calculationVersion'],'new version');
    assertSameValue('largest-remainder-tab-asc-v1',$r['allocationVersion'],'allocation policy version');
    foreach(['fundCents'=>49725000,'progressAmountCents'=>49725000,'paidBeforeCents'=>9360000,'remainingBeforePenaltyCents'=>40365000,'deadlinePenaltyCents'=>3632850,'poolCents'=>36732150,'distributableCents'=>36732150,'remainingFundCents'=>40365000] as $k=>$v)assertSameValue($v,$r['amounts'][$k],$k);
    assertSameValue([9,9100,'2026-08-17'],[$r['daysLate'],$r['kssBp'],$r['comparisonDate']],'date facts');
    assertSameValue($o,$r['operandEvidence'],'exact source envelopes');assertSameValue($p,$r['paymentEvidence'],'exact paid evidence');assertSameValue($before,serialize([$o,$p]),'no input mutation');
    assertSameValue($r,Excel::calculate($o,$p),'deterministic replay');assertSameValue([['step'=>'fund','resultCents'=>49725000],['step'=>'progress','resultCents'=>49725000],['step'=>'deadline','daysLate'=>9,'resultBp'=>9100],['step'=>'paid','resultCents'=>9360000],['step'=>'remaining','resultCents'=>40365000],['step'=>'penalty','resultCents'=>3632850],['step'=>'pool','resultCents'=>36732150]],$r['formulaTrace'],'exact ordered auditable formula trace');
};
$checks['calendar dates and floor']=static function():void{
    foreach([['2026-07-18','2026-08-17','2026-07-20',2,9800],['2026-08-16','2026-08-17','2026-07-28',0,10000],['2026-08-10','2026-08-15','2026-08-20',10,9000],['2024-02-28','2024-03-01',null,2,9800]] as [$d,$report,$pto,$days,$bp]){$r=Excel::calculate(exOperands(10000,$d,$report,$pto),exPayments());assertSameValue([$days,$bp],[$r['daysLate'],$r['kssBp']],'calendar/PTO/certificate effective date');}
    foreach([0=>10000,1=>9900,99=>100,100=>0,101=>0] as $days=>$bp){$report=(new DateTimeImmutable('2026-01-01'))->modify('+'.$days.' days')->format('Y-m-d');$r=Excel::calculate(exOperands(10000,'2026-01-01',$report),exPayments());assertSameValue([$days,$bp,$bp],[$r['daysLate'],$r['kssBp'],$r['amounts']['poolCents']],'floor boundary '.$days);}
};
$checks['rounding recurrence and overpayment']=static function():void{
    $r=Excel::calculate(exOperands(1,'2026-08-01','2026-08-01',null,15000,2500),exPayments());assertSameValue([2,1,1],[$r['amounts']['fundCents'],$r['amounts']['progressAmountCents'],$r['amounts']['poolCents']],'HALF-UP at both boundaries');
    $r=Excel::calculate(exOperands(1,'2026-01-01','2026-02-20'),exPayments());assertSameValue([1,0],[$r['amounts']['deadlinePenaltyCents'],$r['amounts']['poolCents']],'HALF-UP penalty half cent');
    foreach([9000=>900,9900=>90,9990=>9,20000=>0] as $paid=>$expected){$r=Excel::calculate(exOperands(),exPayments($paid));assertSameValue($expected,$r['amounts']['poolCents'],'confirmed cumulative recurrence '.$paid);}
    $p=exPayments(9900);$p['closures'][]=['amountCents'=>-900,'closedOn'=>'2026-08-02','source'=>exSource('reversal')];assertSameValue(900,Excel::calculate(exOperands(),$p)['amounts']['poolCents'],'signed confirmed reversal');
    $p=exPayments(9000);$p['actualPayouts']=[['amountCents'=>5000,'paidOn'=>'2026-08-02','source'=>exSource('informational')]];assertSameValue(900,Excel::calculate(exOperands(),$p)['amounts']['poolCents'],'informational payout does not double subtract');
    $p['actualPayouts'][]=['amountCents'=>-1000,'paidOn'=>'2026-08-03','source'=>exSource('informational-reversal')];assertSameValue(900,Excel::calculate(exOperands(),$p)['amounts']['poolCents'],'valid signed informational evidence');
    $excluded=[['code'=>'EVIDENCE_BLOCKED','effectiveDate'=>'2026-08-01','source'=>exSource('exclusion')]];$r=Excel::calculate(exOperands(),exPayments(9000),$excluded);assertSameValue([900,0],[$r['amounts']['poolCents'],$r['amounts']['distributableCents']],'excluded preview remains visible');
};
$checks['invalid evidence']=static function():void{
    $bad=[];$o=exOperands();unset($o['deadlineDate']);$bad[]=$o;
    foreach([['deadlineDate','2026-02-30'],['completionDate','bad'],['reportDate','2026-1-1'],['premiumCents',1.5],['premiumCents',-1],['premiumCents',1000000000001],['shaftBp',20001],['progressBp',10001],['progressBp',-1]] as [$k,$v]){$o=exOperands();$o[$k]['value']=$v;$bad[]=$o;}
    foreach(['label','locator','contentSha256'] as $field){$o=exOperands();$o['premiumCents']['source'][$field]='';$bad[]=$o;}$o=exOperands();$o['premiumCents']['effectiveDate']='bad';$bad[]=$o;
    foreach($bad as $i=>$o)exReject(static fn()=>Excel::calculate($o,exPayments()),'operand '.$i);
    foreach([-1,1000000000001] as $paid)exReject(static fn()=>Excel::calculate(exOperands(),exPayments($paid)),'net paid range');
    $p=exPayments(1000000000000);$p['closures'][]=$p['closures'][0];exReject(static fn()=>Excel::calculate(exOperands(),$p),'aggregate range');
    $p=exPayments();$p['closures'][0]['closedOn']='bad';exReject(static fn()=>Excel::calculate(exOperands(),$p),'payment date');
    $p=exPayments();$p['closures'][0]['source']['contentSha256']='bad';exReject(static fn()=>Excel::calculate(exOperands(),$p),'payment source');
    exReject(static fn()=>Excel::calculate(exOperands(),exPayments(),[['code'=>'bad']]),'exclusion');
};
$checks['largest remainders']=static function():void{
    assertSameValue([['tab'=>'A','amountCents'=>1],['tab'=>'B','amountCents'=>1]],Excel::allocate(2,[['tab'=>'A','weight'=>1],['tab'=>'B','weight'=>2]]),'unique maximum remainder');
    $tie=[['tab'=>'A','amountCents'=>1],['tab'=>'B','amountCents'=>0]];
    foreach([[['tab'=>'A','weight'=>1],['tab'=>'B','weight'=>1]],[['tab'=>'B','weight'=>1],['tab'=>'A','weight'=>1]]] as $rows)assertSameValue($tie,Excel::allocate(1,$rows),'tie independent of input order');
    $r=Excel::allocate(2000000000000,[['tab'=>'C','weight'=>10000],['tab'=>'A','weight'=>10000],['tab'=>'B','weight'=>10000]]);assertSameValue([['tab'=>'A','amountCents'=>666666666667],['tab'=>'B','amountCents'=>666666666667],['tab'=>'C','amountCents'=>666666666666]],$r,'large exact integer allocation');
    assertSameValue(2000000000000,array_sum(array_column($r,'amountCents')),'conservation');assertSameValue([],Excel::allocate(0,[]),'empty zero pool');
    foreach([[-1,[]],[1,[]],[2000000000001,[]],[1,[['tab'=>'A','weight'=>0]]],[1,[['tab'=>'A','weight'=>10001]]],[1,[['tab'=>' A','weight'=>1]]],[1,[['tab'=>'','weight'=>1]]],[1,[['tab'=>'A','weight'=>1],['tab'=>'A','weight'=>2]]]] as [$pool,$rows])exReject(static fn()=>Excel::allocate($pool,$rows),'allocation shape');
};

$checks['complete evidence grammar']=static function():void{
    foreach(['closures'=>'closedOn','actualPayouts'=>'paidOn'] as $collection=>$dateKey){
        $good=['amountCents'=>0,$dateKey=>'2026-08-01','source'=>exSource('valid')];
        foreach([null,'bad',[2=>$good],[$good,'bad']] as $bad){$p=exPayments();$p[$collection]=$bad;exReject(static fn()=>Excel::calculate(exOperands(),$p),'collection grammar');}
        $p=exPayments();unset($p[$collection]);exReject(static fn()=>Excel::calculate(exOperands(),$p),'missing collection');
        foreach(['amountCents',$dateKey,'source'] as $field){$row=$good;unset($row[$field]);$p=exPayments();$p[$collection]=[$row];exReject(static fn()=>Excel::calculate(exOperands(),$p),'missing row field');}
        foreach([1.5,'1',null,true,1000000000001,-1000000000001] as $amount){$row=$good;$row['amountCents']=$amount;$p=exPayments();$p[$collection]=[$row];exReject(static fn()=>Excel::calculate(exOperands(),$p),'row amount type/range');}
        foreach(['2026-02-30','2026-8-1',''] as $date){$row=$good;$row[$dateKey]=$date;$p=exPayments();$p[$collection]=[$row];exReject(static fn()=>Excel::calculate(exOperands(),$p),'row date');}
        foreach(['label','locator','contentSha256'] as $field){$row=$good;unset($row['source'][$field]);$p=exPayments();$p[$collection]=[$row];exReject(static fn()=>Excel::calculate(exOperands(),$p),'row source');}
        foreach([-1,1000000000000] as $amount){$row=$good;$row['amountCents']=$amount;$p=exPayments();$p[$collection]=$amount<0?[$row]:[$row,$row];exReject(static fn()=>Excel::calculate(exOperands(),$p),'aggregate bounds both collections');}
    }
    foreach(array_keys(exOperands()) as $key){foreach(['value','effectiveDate','source'] as $field){$o=exOperands();unset($o[$key][$field]);exReject(static fn()=>Excel::calculate($o,exPayments()),'missing envelope field');}}
    foreach([str_repeat('A',64),str_repeat('a',63),str_repeat('a',65),str_repeat('z',64)] as $sha){$o=exOperands();$o['deadlineDate']['source']['contentSha256']=$sha;exReject(static fn()=>Excel::calculate($o,exPayments()),'SHA grammar');}
    $good=['code'=>'EVIDENCE_BLOCKED','effectiveDate'=>'2026-08-01','source'=>exSource('exclusion')];
    foreach(['code','effectiveDate','source'] as $field){$e=$good;unset($e[$field]);exReject(static fn()=>Excel::calculate(exOperands(),exPayments(),[$e]),'missing exclusion member');}
    foreach(['bad','AB','1BAD',str_repeat('A',81)] as $code){$e=$good;$e['code']=$code;exReject(static fn()=>Excel::calculate(exOperands(),exPayments(),[$e]),'exclusion code only');}
    $e=$good;$e['effectiveDate']='2026-02-30';exReject(static fn()=>Excel::calculate(exOperands(),exPayments(),[$e]),'exclusion date only');
    foreach(['label','locator','contentSha256'] as $field){$e=$good;$e['source'][$field]='';exReject(static fn()=>Excel::calculate(exOperands(),exPayments(),[$e]),'exclusion source only');}
};
$checks['allocation full input boundaries']=static function():void{
    foreach([['weight'=>1],['tab'=>'A'],['tab'=>1,'weight'=>1],['tab'=>"\xff",'weight'=>1],['tab'=>str_repeat('x',121),'weight'=>1]] as $row)exReject(static fn()=>Excel::allocate(1,[$row]),'participant shape');
    foreach([1.5,'1',null,true,-1] as $weight)exReject(static fn()=>Excel::allocate(1,[['tab'=>'A','weight'=>$weight]]),'weight type');
    exReject(static fn()=>Excel::allocate(1,[2=>['tab'=>'A','weight'=>1]]),'participant collection must be list');
    $tab=str_repeat('x',120);assertSameValue([['tab'=>$tab,'amountCents'=>1]],Excel::allocate(1,[['tab'=>$tab,'weight'=>1]]),'max tab length');
    $accent="\xc3\xa9";$multi=str_repeat($accent,120);assertSameValue([['tab'=>$multi,'amountCents'=>1]],Excel::allocate(1,[['tab'=>$multi,'weight'=>1]]),'120 multibyte characters accepted');exReject(static fn()=>Excel::allocate(1,[['tab'=>$multi.$accent,'weight'=>1]]),'121 multibyte characters rejected');assertSameValue([['tab'=>'z','amountCents'=>1],['tab'=>$accent,'amountCents'=>0]],Excel::allocate(1,[['tab'=>$accent,'weight'=>1],['tab'=>'z','weight'=>1]]),'binary ordering differs from linguistic collation');
    foreach([1.5,'1',null,true] as $pool){try{Excel::allocate($pool,[['tab'=>'A','weight'=>1]]);}catch(TypeError){continue;}throw new TestFailure('typed public pool rejects non-integer');}
};

foreach($checks as $label=>$check){try{$check();echo 'PASS '.$label."\n";}catch(Throwable $error){$failures[]=$label;fwrite(STDERR,'FAIL '.$label.': '.$error->getMessage()."\n");}}
if($failures!==[])exit(1);
echo "PASS OTIZ-EXCEL-CALCULATION-001\n";
