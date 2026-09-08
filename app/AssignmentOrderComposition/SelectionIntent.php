<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final class SelectionIntent
{
    public static function fromCommand(SelectAssignmentOrderCompositionCommand $command): ?SelectionNormalizedIntent
    {
        $ids=$command->installerTabIds->ids;
        if(!SelectionScalar::uuid($command->requestId->value) || $command->actorUserId->value<1 || $command->installationObjectId->value<1
            || ($command->controlEngineerUserId!==null && $command->controlEngineerUserId->value<1) || $command->expectedSelectionRevision->value<0 || $command->expectedSelectionRevision->value>4294967295
            || !array_is_list($ids) || count($ids)>500)return null;
        $values=[];foreach($ids as $id){if(!$id instanceof InstallerTabId || $id->value<1 || isset($values[$id->value]))return null;$values[$id->value]=$id;}
        ksort($values,SORT_NUMERIC);$set=new InstallerTabIdSet(array_values($values));
        return self::build($command->actorUserId->value,$command->controlEngineerUserId?->value,$command->expectedSelectionRevision->value,$command->installationObjectId->value,$set,$command->mode);
    }
    public static function build(int $actor,?int $engineer,int $revision,int $object,InstallerTabIdSet $ids,AssignmentOrderCompositionMode $mode): SelectionNormalizedIntent
    {
        $json=SelectionScalar::json(['actorUserId'=>$actor,'controlEngineerUserId'=>$engineer,'expectedSelectionRevision'=>$revision,'installationObjectId'=>$object,
            'installerTabIds'=>array_map(static fn(InstallerTabId $id)=>$id->value,$ids->ascendingUniqueIds),'mode'=>$mode->value]);
        return new SelectionNormalizedIntent($actor,$engineer,$revision,$object,$ids,$mode,$json,hash('sha256',$json));
    }
    public static function valid(SelectionNormalizedIntent $intent): bool
    {
        if($intent->actorUserId<1 || $intent->objectId<1 || ($intent->engineerUserId!==null && $intent->engineerUserId<1) || $intent->expectedRevision<0 || $intent->expectedRevision>4294967295
            || !array_is_list($intent->installers->ascendingUniqueIds) || count($intent->installers->ascendingUniqueIds)>500)return false;
        $previous=0;foreach($intent->installers->ascendingUniqueIds as $id){if(!$id instanceof InstallerTabId || $id->value<=$previous)return false;$previous=$id->value;}
        $canonical=self::build($intent->actorUserId,$intent->engineerUserId,$intent->expectedRevision,$intent->objectId,$intent->installers,$intent->mode);
        return $intent->canonicalJson===$canonical->canonicalJson && $intent->fingerprint===$canonical->fingerprint;
    }
    public static function composition(int $case,int $order,int $version,int $engineer,InstallerTabIdSet $ids): array
    {
        $identity='composition-'.$order.'-v'.$version;
        $json=SelectionScalar::json(['caseId'=>$case,'compositionIdentity'=>$identity,'engineerUserId'=>$engineer,'installers'=>array_map(static fn(InstallerTabId $id)=>$id->value,$ids->ascendingUniqueIds),'orderId'=>$order]);
        return [$identity,hash('sha256',$json)];
    }
}
