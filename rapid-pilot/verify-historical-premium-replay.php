<?php

declare(strict_types=1);

require_once __DIR__.'/legacy-migration/HistoricalPremiumReplayAdapter.php';
function replayCheck(bool$c,string$m):void{if(!$c)throw new RuntimeException($m);}
$source=static fn($seed)=>['label'=>'fixture','locator'=>'fixture://'.$seed,'contentSha256'=>hash('sha256',$seed)];$fact=static fn($v,$date,$source)=>['value'=>$v,'effectiveDate'=>$date,'source'=>$source];
$operands=['reportDate'=>$fact('2026-08-31','2026-08-31',$source('report')),'premiumCents'=>$fact(10000000,'2026-01-01',$source('premium')),'shaftBp'=>$fact(10000,'2026-01-01',$source('shaft')),'progressBp'=>$fact(5000,'2026-08-20',$source('progress')),'deadlineDate'=>$fact('2026-08-31','2026-01-01',$source('deadline')),'completionDate'=>$fact(null,'2026-08-31',$source('completion'))];
$without=HistoricalPremiumReplayAdapter::replay($operands,[],[],[],[['amountCents'=>5000000,'evidenceType'=>'paid_before_balance_assertion']]);
replayCheck($without['payoutComparison']['state']==='unavailable'&&$without['calculation']['amounts']['payoutDiscrepancyCents']===null,'balance assertion is not payout evidence');
replayCheck($without['balanceAssertions']['usedAsActualPayout']===false,'balance assertion explicitly excluded from payout comparison');
$actual=[['amountCents'=>4900000,'paidOn'=>'2026-08-31','source'=>$source('payout')]];$with=HistoricalPremiumReplayAdapter::replay($operands,[],$actual);
replayCheck($with['payoutComparison']===['state'=>'discrepant','discrepancyCents'=>-100000,'reason'=>'ACTUAL_PAYOUT_DIFFERS'],'independent payout enables discrepancy');
replayCheck($with['calculation']['progressBp']===5000,'payout never backsolves progress');
echo json_encode(['ok'=>true,'withoutPayout'=>$without['payoutComparison'],'withPayout'=>$with['payoutComparison']],JSON_THROW_ON_ERROR),"\n";
