<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final class SelectionEligibility
{
    public static function caseValid(SelectionCasePayload $case,int $object): bool
    { return $case->caseId>0 && $case->objectId===$object && ($case->ptoActDate===null || SelectionScalar::date($case->ptoActDate)); }
    public static function caseReason(SelectionCasePayload $case): ?AssignmentOrderCompositionReason
    { return $case->completed?AssignmentOrderCompositionReason::OBJECT_COMPLETED:($case->ptoActDate!==null?AssignmentOrderCompositionReason::OBJECT_HAS_PTO_ACT:null); }
    /** @return array{?SelectionResult,list<InstallerSnapshot>,?EngineerSnapshot} */
    public static function crew(SelectionAttempt $attempt): array
    {
        if($attempt->intent->installers->ascendingUniqueIds===[])return [$attempt->rejected(AssignmentOrderCompositionReason::INSTALLER_REQUIRED),[],null];
        if($attempt->command->controlEngineerUserId===null)return [$attempt->rejected(AssignmentOrderCompositionReason::CONTROL_ENGINEER_REQUIRED),[],null];
        $bad=static fn()=>[$attempt->failed(AssignmentOrderCompositionReason::DEPENDENCY_UNAVAILABLE),[],null];
        try{
            $lookup=$attempt->ports->facts->findInstallers($attempt->intent->installers,$attempt->at());
            if($lookup->status!==SelectionLookupStatus::FOUND || !self::batchValid($lookup->payload,$attempt->intent->installers))return $bad();
            $batch=$lookup->payload;
            if($batch->missingIds!==[])return [$attempt->rejected(AssignmentOrderCompositionReason::INSTALLER_NOT_IN_CATALOG),[],null];
            $date=SelectionScalar::selectionDate($attempt->at());
            foreach($batch->snapshots as $worker){if($worker->employmentStatus!=='employed' || $worker->employedFrom>$date || ($worker->employedTo!==null && $worker->employedTo<$date))return [$attempt->rejected(AssignmentOrderCompositionReason::INSTALLER_NOT_EMPLOYED),[],null];}
            $engineer=$attempt->ports->facts->findEngineer($attempt->command->controlEngineerUserId,$attempt->at());
            if($engineer->status===SelectionLookupStatus::NOT_FOUND)return [$attempt->rejected(AssignmentOrderCompositionReason::CONTROL_ENGINEER_NOT_ELIGIBLE),[],null];
            if($engineer->status!==SelectionLookupStatus::FOUND || $engineer->payload===null || $engineer->payload->userId!==$attempt->intent->engineerUserId
                || !SelectionScalar::text($engineer->payload->fio,300) || !SelectionScalar::text($engineer->payload->position,300))return $bad();
            return [null,$batch->snapshots,$engineer->payload];
        }catch(\Throwable){return $bad();}
    }
    private static function batchValid(?InstallerBatchPayload $batch,InstallerTabIdSet $requested): bool
    {
        if($batch===null || !array_is_list($batch->snapshots) || !array_is_list($batch->missingIds))return false;$ids=[];$previous=0;
        foreach($batch->snapshots as $worker){
            if(!$worker instanceof InstallerSnapshot || $worker->tabId<=$previous || !SelectionScalar::text($worker->fio,300) || !SelectionScalar::text($worker->position,300)
                || !in_array($worker->employmentStatus,['employed','dismissed'],true) || !SelectionScalar::date($worker->employedFrom)
                || ($worker->employedTo!==null && (!SelectionScalar::date($worker->employedTo) || $worker->employedTo<$worker->employedFrom))
                || !SelectionScalar::text($worker->workforceSource,80) || !SelectionScalar::sourceInstant($worker->workforceSourceUpdatedAt))return false;
            $previous=$worker->tabId;$ids[]=$previous;
        }
        $previous=0;foreach($batch->missingIds as $id){if(!is_int($id) || $id<=$previous || in_array($id,$ids,true))return false;$previous=$id;$ids[]=$id;}
        sort($ids,SORT_NUMERIC);return $ids===array_map(static fn(InstallerTabId $id)=>$id->value,$requested->ascendingUniqueIds);
    }
}
