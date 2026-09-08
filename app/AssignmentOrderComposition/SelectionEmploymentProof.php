<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final class SelectionEmploymentProof
{
    public static function full(InstallerSnapshot $worker):bool
    {
        $f=$worker->fullSnapshot;
        return $worker->employedFrom===null&&$worker->employmentStatus==='employed'
            &&$worker->authoritySystem==='1c_zup'&&$worker->deliverySystem==='bitrix24'
            &&is_int($worker->deliveryPersonId)&&$worker->deliveryPersonId>0
            &&$worker->reconciliationState==='delivered'&&is_array($f)
            &&array_keys($f)===['runId','observedAt','normalizedChecksum','deliveredCount','pageCount']
            &&SelectionScalar::uuid($f['runId'])&&SelectionScalar::sourceInstant($f['observedAt'])
            &&is_string($f['normalizedChecksum'])&&preg_match('/^[0-9a-f]{64}$/D',$f['normalizedChecksum'])===1
            &&is_int($f['deliveredCount'])&&$f['deliveredCount']>=1
            &&is_int($f['pageCount'])&&$f['pageCount']>=1;
    }
}
