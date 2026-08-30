<?php

declare(strict_types=1);

require_once __DIR__.'/legacy-migration/HistoricalPremiumReplayReadModel.php';
function readReplayCheck(bool$c,string$m):void{if(!$c)throw new RuntimeException($m);}
$evidence=['legacyObjectId'=>973,'regnumber'=>'77-00973','address'=>'Контрольный адрес','classification'=>'legacy_historical','evidenceGrade'=>'A','sourceLabel'=>'Legacy FMonitor · только чтение','sourceLocator'=>'fm_maintable+checklist_logs','contentSha256'=>str_repeat('a',64),'projectionHash'=>str_repeat('b',64),'conflictCodes'=>[],'progressMapping'=>['mappingVersion'=>'legacy-checklist-progress-mapping-v1','candidateProgressBp'=>10000,'eligibleForCalculation'=>false,'conflictCodes'=>['DEFINITION_VERSION_UNPROVEN']]];
$blocked=HistoricalPremiumReplayReadModel::build($evidence,[],[],[],[['amountCents'=>100]]);$repeat=HistoricalPremiumReplayReadModel::build($evidence,[],[],[],[['amountCents'=>100]]);
readReplayCheck($blocked===$repeat,'same evidence produces deterministic read model');readReplayCheck($blocked['state']==='calculation_unavailable'&&$blocked['calculation']===null,'missing operands never synthetic-fill replay');
readReplayCheck(in_array('DEFINITION_VERSION_UNPROVEN',$blocked['exclusionReasons'],true),'unproven progress is excluded');readReplayCheck($blocked['progressCandidate']['candidateProgressBp']===10000&&$blocked['progressCandidate']['usedInCalculation']===false,'candidate remains visible but unused');
readReplayCheck($blocked['payoutComparison']['discrepancyCents']===null&&$blocked['balanceAssertions']['usedAsActualPayout']===false,'balance assertion never creates discrepancy');
echo json_encode(['ok'=>true,'state'=>$blocked['state'],'reasons'=>$blocked['exclusionReasons'],'payoutComparison'=>$blocked['payoutComparison']],JSON_THROW_ON_ERROR),"\n";
