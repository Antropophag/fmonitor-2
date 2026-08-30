<?php

declare(strict_types=1);

require_once __DIR__.'/HistoricalPremiumReplayAdapter.php';
require_once __DIR__.'/MigratedEvidenceReconciliation.php';

final class HistoricalPremiumReplayReadModel
{
    public const VERSION='historical-premium-replay-read-model-v1';

    public static function load(mysqli$db,string$prefix):array
    {
        $rows=[];foreach(MigratedEvidenceReconciliation::load($db,$prefix)as$evidence)if($evidence['classification']==='legacy_historical')$rows[]=self::build($evidence);
        return$rows;
    }

    public static function build(array$evidence,array$provenOperands=[],array$closures=[],array$actualPayouts=[],array$balanceAssertions=[]):array
    {
        $reasons=[];$required=['reportDate'=>'REPORT_DATE_EVIDENCE_ABSENT','premiumCents'=>'PREMIUM_EVIDENCE_ABSENT','shaftBp'=>'SHAFT_COEFFICIENT_EVIDENCE_ABSENT','progressBp'=>'PROGRESS_EVIDENCE_ABSENT','deadlineDate'=>'DEADLINE_EVIDENCE_ABSENT','completionDate'=>'COMPLETION_EVIDENCE_ABSENT'];
        foreach($required as$name=>$reason)if(!isset($provenOperands[$name])||!is_array($provenOperands[$name]))$reasons[]=$reason;
        $mapping=$evidence['progressMapping']??[];
        if(!($mapping['eligibleForCalculation']??false))foreach($mapping['conflictCodes']??['PROGRESS_MAPPING_UNAVAILABLE']as$reason)$reasons[]=(string)$reason;
        foreach($evidence['conflictCodes']??[]as$reason)$reasons[]=(string)$reason;
        $reasons=array_values(array_unique($reasons));sort($reasons,SORT_STRING);
        $replay=null;if($reasons===[])$replay=HistoricalPremiumReplayAdapter::replay($provenOperands,$closures,$actualPayouts,[],$balanceAssertions);
        $comparison=$replay['payoutComparison']??['state'=>'unavailable','discrepancyCents'=>null,'reason'=>'ACTUAL_PAYOUT_EVIDENCE_ABSENT'];
        return['readModelVersion'=>self::VERSION,'legacyObjectId'=>(int)$evidence['legacyObjectId'],'regnumber'=>(string)$evidence['regnumber'],'address'=>(string)$evidence['address'],
            'state'=>$replay===null?'calculation_unavailable':'calculated','exclusionReasons'=>$reasons,'calculation'=>$replay['calculation']??null,'payoutComparison'=>$comparison,
            'provenance'=>['sourceLabel'=>$evidence['sourceLabel'],'sourceLocator'=>$evidence['sourceLocator'],'snapshotHash'=>$evidence['contentSha256'],'projectionHash'=>$evidence['projectionHash'],'evidenceGrade'=>$evidence['evidenceGrade']],
            'progressCandidate'=>['candidateProgressBp'=>$mapping['candidateProgressBp']??null,'mappingVersion'=>$mapping['mappingVersion']??null,'usedInCalculation'=>$replay!==null],
            'balanceAssertions'=>['count'=>count($balanceAssertions),'usedAsActualPayout'=>false]];
    }
}
