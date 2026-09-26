<?php
declare(strict_types=1);

$math=__DIR__.'/../../app/Otiz/OtizSettlementV2Math.php';
if(!is_file($math)){fwrite(STDERR,"INTENDED_RED: OTIZ v2 money oracle owner is absent\n");exit(1);}
require_once $math;

use FMonitor\Otiz\OtizSettlementV2Math;

final class SettlementV2Failure extends RuntimeException {}
function v2same(mixed $expected,mixed $actual,string $message):void{if($expected!==$actual)throw new SettlementV2Failure($message.' expected='.json_encode($expected).' actual='.json_encode($actual));}

// Independent M01/M03 oracle: drafts and payment facts never change recognized work.
v2same(3000000,OtizSettlementV2Math::newGrossCents(10000000,5000,2000),'M01: 50% current minus 20% accepted is 30,000 rubles despite abandoned draft.');
v2same(1000000,OtizSettlementV2Math::newGrossCents(10000000,6000,5000),'M01: accepted unpaid 50% still blocks repeat; only 10% is new.');
v2same(['gross'=>2000000,'deadline'=>200000,'pool'=>1800000],OtizSettlementV2Math::deadlineReduction(2000000,9000),'M03: deadline reduction is recognized once.');

// Independent M04/M05/M06 oracle. All values are integer cents.
$base=[['employeeId'=>'A','weight'=>60],['employeeId'=>'B','weight'=>40]];
v2same(['A'=>620000,'B'=>480000],OtizSettlementV2Math::allocate(1200000,$base,[],['A'=>100000]),'M04: personal deduction is not redistributed.');
v2same(['A'=>1800000,'B'=>1200000],OtizSettlementV2Math::allocate(3000000,$base,[],[]),'M05: dismissed status alone does not remove recipient.');
v2same(['A'=>3000000,'B'=>0],OtizSettlementV2Math::allocate(3000000,$base,['B'],[]),'M06: do-not-pay redistributes only within original object crew.');
v2same(['A'=>1800000,'B'=>1200000],OtizSettlementV2Math::allocate(3000000,$base,[],[]),'M06: returning to pay recomputes from original weights without drift.');
$m07=[['employeeId'=>'A','weight'=>50],['employeeId'=>'B','weight'=>30],['employeeId'=>'C','weight'=>20]];
v2same(['A'=>5485714,'B'=>0,'C'=>2314286],OtizSettlementV2Math::allocate(8100000,$m07,['B'],['A'=>300000]),'M07 exact order, redistribution and kopecks.');
v2same(7800000,array_sum(OtizSettlementV2Math::allocate(8100000,$m07,['B'],['A'=>300000])),'M07 payable plus 2,200,000 reductions equals gross 10,000,000.');
v2same(['gross'=>1000000,'deadline'=>1000000,'pool'=>0],OtizSettlementV2Math::deadlineReduction(1000000,0),'M10 positive recognized volume can have zero payout.');

// Stable largest-remainder tie-break is identity-based, never row-order based.
$tieA=OtizSettlementV2Math::allocate(1,[['employeeId'=>'B','weight'=>1],['employeeId'=>'A','weight'=>1]],[],[]);
$tieB=OtizSettlementV2Math::allocate(1,[['employeeId'=>'A','weight'=>1],['employeeId'=>'B','weight'=>1]],[],[]);
v2same(['A'=>1,'B'=>0],$tieA,'Stable identity receives residual cent.');
v2same($tieA,$tieB,'UI row order cannot change monetary allocation.');
v2same(['001'=>3334,'002'=>3333,'003'=>3333],OtizSettlementV2Math::allocate(10000,[['employeeId'=>'003','weight'=>1],['employeeId'=>'001','weight'=>1],['employeeId'=>'002','weight'=>1]],[],[]),'M16 stable three-way kopeck tie-break.');

try{OtizSettlementV2Math::allocate(100,$base,['A','B'],[]);throw new SettlementV2Failure('Expected no-recipient conflict.');}catch(DomainException $e){v2same('NO_ELIGIBLE_RECIPIENT',$e->getMessage(),'Positive pool with no recipient blocks acceptance.');}
try{OtizSettlementV2Math::parseRubles('NaN');throw new SettlementV2Failure('Expected invalid money.');}catch(InvalidArgumentException $e){v2same('INVALID_MONEY',$e->getMessage(),'NaN is rejected.');}
v2same(300050,OtizSettlementV2Math::parseRubles('3 000,50'),'Russian money input is exact.');

echo "settlement_v2_001_test: OK\n";