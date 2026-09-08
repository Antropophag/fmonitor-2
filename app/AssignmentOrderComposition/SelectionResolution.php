<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final class SelectionResolution
{
    public static function authorize(SelectionAttempt $attempt): ?SelectionResult
    {
        try{$authorization=$attempt->ports->authorizer->authorize($attempt->command->actorUserId,SelectionCapability::SELECT);}
        catch(\Throwable){return $attempt->failed(AssignmentOrderCompositionReason::DEPENDENCY_UNAVAILABLE);}
        return match($authorization->status){SelectionAuthorizationStatus::ALLOWED=>null,
            SelectionAuthorizationStatus::DENIED=>$attempt->independentAudit($attempt->rejected(AssignmentOrderCompositionReason::AUTHORIZATION_DENIED)),
            default=>$attempt->failed(AssignmentOrderCompositionReason::DEPENDENCY_UNAVAILABLE)};
    }
    public static function terminal(SelectionAttempt $attempt,SelectionTerminalRequestRecord $record): SelectionResult
    {
        $result=$record->terminalResult;
        if($record->requestId->value!==$attempt->command->requestId->value || !SelectionIntent::valid($record->intent) || !$result instanceof SelectionResult
            || $result->requestId()->value!==$record->requestId->value || !in_array($result->status(),[AssignmentOrderCompositionStatus::SELECTED,AssignmentOrderCompositionStatus::REJECTED,AssignmentOrderCompositionStatus::CONFLICT],true))return $attempt->failed(AssignmentOrderCompositionReason::DEPENDENCY_UNAVAILABLE);
        if(in_array($result->reasonCode(),[AssignmentOrderCompositionReason::INVALID_COMMAND,AssignmentOrderCompositionReason::AUTHORIZATION_DENIED,AssignmentOrderCompositionReason::REQUEST_ID_CONFLICT],true))return $attempt->failed(AssignmentOrderCompositionReason::DEPENDENCY_UNAVAILABLE);
        $p=$result->success();
        if($result->status()===AssignmentOrderCompositionStatus::SELECTED){
            if($p===null || $record->intent->engineerUserId===null || $p->selectionRevision!==$record->intent->expectedRevision+1
                || $p->selectionDate!==SelectionScalar::selectionDate(new SelectionInstant($p->selectedAt)))return $attempt->failed(AssignmentOrderCompositionReason::DEPENDENCY_UNAVAILABLE);
            [$identity,$hash]=SelectionIntent::composition($p->caseId,$p->assignmentOrderId,$p->assignmentOrderVersion,$record->intent->engineerUserId,$record->intent->installers);
            if($identity!==$p->compositionIdentity || $hash!==$p->compositionSha256)return $attempt->failed(AssignmentOrderCompositionReason::DEPENDENCY_UNAVAILABLE);
        }
        if($record->intent->canonicalJson!==$attempt->intent->canonicalJson || $record->intent->fingerprint!==$attempt->intent->fingerprint)return $attempt->independentAudit($attempt->conflict(AssignmentOrderCompositionReason::REQUEST_ID_CONFLICT));
        if($result->status()!==AssignmentOrderCompositionStatus::SELECTED)return $result;
        return SelectionResult::replayed($attempt->command->requestId,$p);
    }
    public static function recover(SelectionAttempt $attempt): SelectionResult
    {
        $denied=self::authorize($attempt);if($denied!==null)return $denied;$reader=null;$result=null;$closed=true;
        try{
            $reader=$attempt->ports->freshReaders->open();$lookup=$reader->findTerminalRequest($attempt->command->requestId);
            $result=match($lookup->status){SelectionLookupStatus::FOUND=>self::terminal($attempt,$lookup->payload),
                SelectionLookupStatus::NOT_FOUND=>$attempt->failed(AssignmentOrderCompositionReason::PERSISTENCE_FAILURE),
                default=>$attempt->failed(AssignmentOrderCompositionReason::PERSISTENCE_OUTCOME_UNKNOWN)};
        }catch(\Throwable){$result=$attempt->failed(AssignmentOrderCompositionReason::PERSISTENCE_OUTCOME_UNKNOWN);}
        finally{if($reader!==null){try{$reader->close();}catch(\Throwable){$closed=false;}}}
        return $closed?$result:$attempt->failed(AssignmentOrderCompositionReason::PERSISTENCE_OUTCOME_UNKNOWN);
    }
    public static function unit(SelectionAttempt $attempt,SelectionUnitOfWorkResult $unit): SelectionResult
    {
        return match($unit->kind()){'committed'=>$unit->result(),'observedTerminal'=>self::terminal($attempt,$unit->terminalRecord()),
            'requestRace','outcomeUnknown'=>self::recover($attempt),
            'rolledBack'=>$attempt->failed(AssignmentOrderCompositionReason::from($unit->rollbackCause()->value)),
            default=>$attempt->failed(AssignmentOrderCompositionReason::PERSISTENCE_FAILURE)};
    }
}
