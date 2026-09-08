<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionApplication implements AssignmentOrderCompositionApplication
{
    public function __construct(private SelectionDependencies $ports) {}
    public function selectAssignmentOrderComposition(SelectAssignmentOrderCompositionCommand $command): AssignmentOrderCompositionResult
    {
        $intent=SelectionIntent::fromCommand($command);
        if($intent===null)return SelectionResult::rejected($command->requestId,AssignmentOrderCompositionReason::INVALID_COMMAND);
        $attempt=new SelectionAttempt($this->ports,$command,$intent);
        $rejected=SelectionResolution::authorize($attempt);if($rejected!==null)return $rejected;
        try{
            $request=$this->ports->requests->findTerminalRequest($command->requestId);
            if($request->status===SelectionLookupStatus::FOUND)return SelectionResolution::terminal($attempt,$request->payload);
            if($request->status!==SelectionLookupStatus::NOT_FOUND)return $attempt->failed(AssignmentOrderCompositionReason::DEPENDENCY_UNAVAILABLE);
        }catch(\Throwable){return $attempt->failed(AssignmentOrderCompositionReason::DEPENDENCY_UNAVAILABLE);}
        if($attempt->at()===null)return $attempt->failed(AssignmentOrderCompositionReason::DEPENDENCY_UNAVAILABLE);
        try{$case=$this->ports->facts->findCaseByObject($command->installationObjectId);}
        catch(\Throwable){return $attempt->failed(AssignmentOrderCompositionReason::DEPENDENCY_UNAVAILABLE);}
        if($case->status===SelectionLookupStatus::NOT_FOUND){
            $result=$attempt->rejected(AssignmentOrderCompositionReason::OBJECT_NOT_FOUND);
            try{return SelectionResolution::unit($attempt,$this->ports->terminalAttempts->execute(new SelectionTerminalAttemptPersistence($command->requestId,$intent,$result,$attempt->audit($result))));}
            catch(\Throwable){return SelectionResolution::recover($attempt);}
        }
        if($case->status!==SelectionLookupStatus::FOUND || $case->payload===null || !SelectionEligibility::caseValid($case->payload,$intent->objectId))return $attempt->failed(AssignmentOrderCompositionReason::DEPENDENCY_UNAVAILABLE);
        $reason=SelectionEligibility::caseReason($case->payload);
        if($reason!==null){$refusal=$attempt->rejected($reason);$installers=[];$engineer=null;}
        else{[$refusal,$installers,$engineer]=SelectionEligibility::crew($attempt);}
        if($refusal?->status()===AssignmentOrderCompositionStatus::FAILED)return $refusal;
        try{
            $work=new SelectionWork($attempt,$case->payload->caseId,$refusal,$installers,$engineer);
            return SelectionResolution::unit($attempt,$this->ports->transactions->executeForCase($case->payload->caseId,$work));
        }catch(\Throwable){return SelectionResolution::recover($attempt);}
    }
}
