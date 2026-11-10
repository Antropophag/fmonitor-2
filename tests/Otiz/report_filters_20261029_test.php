<?php
declare(strict_types=1);require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\Otiz\OtizCalculationReportFilter;
assertSameValue(true,class_exists(OtizCalculationReportFilter::class),'INTENDED_RED report filter public read missing');
$c=['totalCents'=>1000,'reservedCents'=>90,'objects'=>[
 ['objectId'=>1,'shaftBp'=>12500,'objectStatus'=>'working','payableCents'=>100],
 ['objectId'=>2,'shaftBp'=>10000,'objectStatus'=>'completed','payableCents'=>200],
 ['objectId'=>3,'shaftBp'=>null,'objectStatus'=>'working','payableCents'=>700],
],'recipients'=>[
 ['objectId'=>1,'employeeId'=>'A','employment'=>'employed','amountCents'=>100,'reservedCents'=>0],
 ['objectId'=>2,'employeeId'=>'A','employment'=>'employed','amountCents'=>200,'reservedCents'=>0],
 ['objectId'=>3,'employeeId'=>'A','employment'=>'dismissed','amountCents'=>300,'reservedCents'=>90],
 ['objectId'=>3,'employeeId'=>'B','employment'=>'unknown','amountCents'=>400,'reservedCents'=>0],
]];$before=$c;
$r=OtizCalculationReportFilter::apply($c,['excludeFrame'=>'1','excludeCompleted'=>'1','excludeDismissed'=>'1']);
assertSameValue([3],array_column($r['calculation']['objects'],'objectId'),'exact retained object');assertSameValue(['B'],array_column($r['calculation']['recipients'],'employeeId'),'unknown is not dismissed');assertSameValue([400,0,3],[$r['totalCents'],$r['reservedCents'],$r['excludedRecipients']],'filtered totals never redistribute');assertSameValue($before,$c,'read never mutates original scope');
$reserved=OtizCalculationReportFilter::apply($c,['onlyReserved'=>'1']);assertSameValue([300,90],[$reserved['totalCents'],$reserved['reservedCents']],'only reserved recipient uses saved amounts');
$all=OtizCalculationReportFilter::apply($c,[]);assertSameValue([1000,90,0],[$all['totalCents'],$all['reservedCents'],$all['excludedRecipients']],'default all');
echo "PASS: OTIZ report exclusion filters preserve saved amounts and full source\n";
