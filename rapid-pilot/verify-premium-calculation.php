<?php

declare(strict_types=1);

require_once __DIR__.'/legacy-migration/PremiumCalculation.php';
function expectSame(mixed $e,mixed $a,string $m):void{if($e!==$a)throw new RuntimeException($m.' '.var_export($a,true));}
$source=static fn(string $label,string $seed):array=>['label'=>$label,'locator'=>'fixture://'.$seed,'contentSha256'=>hash('sha256',$seed)];
$fact=static fn(mixed $value,string $date,array $source):array=>['value'=>$value,'effectiveDate'=>$date,'source'=>$source];
$operands=[
 'reportDate'=>$fact('2026-08-31','2026-08-31',$source('reporting command','report')),
 'premiumCents'=>$fact(10000000,'2026-01-01',$source('contract premium','premium')),
 'shaftBp'=>$fact(8000,'2026-01-01',$source('shaft coefficient','shaft')),
 'progressBp'=>$fact(5000,'2026-08-20',$source('confirmed checklist progress','progress')),
 'deadlineDate'=>$fact('2026-08-21','2026-01-01',$source('contract deadline','deadline')),
 'completionDate'=>$fact(null,'2026-08-31',$source('completion lookup','completion')),
];
$payments=['closures'=>[['amountCents'=>500000,'closedOn'=>'2026-08-15','source'=>$source('accepted closure','closure')]],
 'actualPayouts'=>[['amountCents'=>123456,'paidOn'=>'2026-08-25','source'=>$source('legacy payout register — reconciliation only','payout')]]];
$native=PremiumCalculation::calculate($operands,$payments);$replay=PremiumCalculation::calculate($operands,$payments);
expectSame($native,$replay,'native and historical replay use identical contract result');
expectSame('premium-calculation-v1',$native['calculationVersion'],'version');
expectSame(5000,$native['progressBp'],'progress copied from dated evidence');
expectSame(9000,$native['kssBp'],'ten late days reduce Kss by approved one percentage point per day');
expectSame(['fundCents'=>8000000,'accruedCents'=>3600000,'closedBeforeCents'=>500000,'poolCents'=>3100000,'remainingFundCents'=>7500000,'distributableCents'=>3100000,'actualPayoutCents'=>123456,'payoutDiscrepancyCents'=>-3476544],$native['amounts'],'integer kopeck formula');
expectSame(true,in_array('PAYOUT_DISCREPANCY',$native['reasons'],true),'discrepancy reason');
$differentPayout=$payments;$differentPayout['actualPayouts'][0]['amountCents']=3599999;$compared=PremiumCalculation::calculate($operands,$differentPayout);
expectSame($native['progressBp'],$compared['progressBp'],'payout cannot backsolve progress');
expectSame($native['amounts']['accruedCents'],$compared['amounts']['accruedCents'],'payout cannot change entitlement');
expectSame(-1,$compared['amounts']['payoutDiscrepancyCents'],'payout changes discrepancy only');
$excluded=PremiumCalculation::calculate($operands,$payments,[['code'=>'UNPROVEN_INSTALLER','effectiveDate'=>'2026-08-20','source'=>$source('reconciliation conflict','exclusion')]]);
expectSame(0,$excluded['amounts']['distributableCents'],'explicit exclusion blocks distribution');expectSame(3600000,$excluded['amounts']['accruedCents'],'exclusion does not rewrite formula result');
echo "PASS: versioned premium calculation is deterministic and payout-independent\n";
