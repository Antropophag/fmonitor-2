<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/AssignmentOrderOriginalDataScalar.php';

/** @internal Complete persisted audit grammar, including nonterminal failure attempts. */
final class AssignmentOrderOriginalAttemptAuditRows
{
    public static function parse(array $row,string $requestId):array
    {
        $status=is_string($row['status']??null)?AssignmentOrderOriginalStatus::tryFrom($row['status']):null;
        $reason=is_string($row['reason_code']??null)?AssignmentOrderOriginalReason::tryFrom($row['reason_code']):null;
        $mode=is_string($row['mode']??null)?AssignmentOrderOriginalMode::tryFrom($row['mode']):null;
        $id=AssignmentOrderOriginalDataScalar::integer($row['audit_id']??null);
        $actor=AssignmentOrderOriginalDataScalar::integer($row['actor_identity']??null);
        $case=AssignmentOrderOriginalDataScalar::integer($row['installation_case_id']??null);
        $order=AssignmentOrderOriginalDataScalar::integer($row['assignment_order_id']??null);
        $at=AssignmentOrderOriginalDataScalar::sqlUtc($row['attempted_at_utc']??null);
        $pair=$status===AssignmentOrderOriginalStatus::ACCEPTED?(array_key_exists('reason_code',$row)&&$row['reason_code']===null)
            :($status===AssignmentOrderOriginalStatus::FAILED?in_array($reason,[AssignmentOrderOriginalReason::STREAM_FAILURE,AssignmentOrderOriginalReason::STORAGE_FAILURE],true)
                :($status!==null&&AssignmentOrderOriginalDataScalar::terminalReason($status,$reason)));
        if(!$pair||$mode===null||$id===null||$actor===null||$case===null||$order===null||$at===null
            ||!AssignmentOrderOriginalDataScalar::uuid($requestId)||($row['request_id']??null)!==$requestId)AssignmentOrderOriginalSql::fail();
        return compact('id','actor','case','order','at','mode','status','reason');
    }
    public static function matches(array $audit,array $request):bool
    {
        $result=$request['result'];
        return [$audit['status'],$audit['reason'],$audit['mode'],$audit['actor'],$audit['case'],$audit['order'],$audit['at']]
            ===[$result->status(),$result->reasonCode(),$request['mode'],$request['actor'],$request['case'],$request['order'],$request['at']];
    }
    public static function additional(array $audit):bool
    {
        return $audit['status']===AssignmentOrderOriginalStatus::FAILED
            ||($audit['status']===AssignmentOrderOriginalStatus::REJECTED&&$audit['reason']===AssignmentOrderOriginalReason::AUTHORIZATION_DENIED);
    }
}
