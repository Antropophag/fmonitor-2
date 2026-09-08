<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

/** Fresh process contour: no historical writer adoption or implicit legacy allocation. */
final class SelectionStatePolicy
{
    public static function valid(SelectionStateSnapshot $state): bool
    {
        if($state->latestRegistryLegacyIdentity!==null)return false;
        foreach([$state->latestSelection,$state->latestPendingSelection,$state->latestAcceptedSelection] as $s){if($s!==null && !self::summary($s))return false;}
        $latest=$state->latestSelection;$pending=$state->latestPendingSelection;$accepted=$state->latestAcceptedSelection;
        if($latest===null){if($pending!==null || $accepted!==null)return false;}
        elseif($latest->hasAcceptedOriginal){if($pending!==null || $accepted===null || !self::same($latest,$accepted))return false;}
        else{if($pending===null || $pending->hasAcceptedOriginal || !self::same($latest,$pending))return false;}
        if($accepted!==null && (!$accepted->hasAcceptedOriginal || $latest===null || $accepted->selectionRevision>$latest->selectionRevision
            || $accepted->orderVersion>$latest->orderVersion || (!$latest->hasAcceptedOriginal && $accepted->selectionRevision===$latest->selectionRevision)))return false;
        $effective=$state->effectiveOrder;return $effective===null || ($latest!==null && $effective->assignmentOrderId>0 && $effective->orderVersion>0 && $effective->orderVersion<=$latest->orderVersion);
    }
    private static function summary(SelectionIdentitySummary $s): bool
    { return $s->assignmentOrderId>0 && $s->orderVersion>0 && $s->orderVersion<=65535 && $s->selectionRevision>0 && $s->selectionRevision<=4294967295
        && $s->compositionIdentity==='composition-'.$s->assignmentOrderId.'-v'.$s->orderVersion && SelectionScalar::hash($s->compositionSha256); }
    private static function same(SelectionIdentitySummary $a,SelectionIdentitySummary $b): bool { return get_object_vars($a)===get_object_vars($b); }
    public static function refusal(SelectionAttempt $attempt,SelectionStateSnapshot $state,int $caseId): ?SelectionResult
    {
        $latest=$state->latestSelection;$revision=$latest?->selectionRevision??0;
        if($attempt->intent->expectedRevision!==$revision)return $attempt->conflict(AssignmentOrderCompositionReason::STALE_SELECTION);
        if($attempt->command->mode===AssignmentOrderCompositionMode::NEW_ORDER)return $state->latestPendingSelection!==null?$attempt->conflict(AssignmentOrderCompositionReason::PENDING_SELECTION_EXISTS):null;
        if($latest===null)return $attempt->conflict(AssignmentOrderCompositionReason::SELECTION_NOT_FOUND);
        if($latest->hasAcceptedOriginal)return $attempt->conflict(AssignmentOrderCompositionReason::ORIGINAL_ALREADY_ACCEPTED);
        [, $hash]=SelectionIntent::composition($caseId,$latest->assignmentOrderId,$latest->orderVersion,$attempt->intent->engineerUserId,$attempt->intent->installers);
        return $hash===$latest->compositionSha256?$attempt->rejected(AssignmentOrderCompositionReason::NO_CHANGES):null;
    }
}
