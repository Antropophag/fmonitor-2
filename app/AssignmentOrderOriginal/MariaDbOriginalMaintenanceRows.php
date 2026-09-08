<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Lossless maintenance row encoding and complete audit-backed decoding. */
final class AssignmentOrderOriginalMaintenanceRows
{
    public static function values(AssignmentOrderOriginalMaintenanceCommit $c): array
    {
        return ['request_id'=>$c->requestId,'system_principal_id'=>$c->systemPrincipalId,'status'=>$c->status->value,
            'reason_code'=>$c->reason?->value,'retryable'=>$c->retryable?'1':'0','scanned'=>(string)$c->scanned,
            'deleted'=>(string)$c->deleted,'retained'=>(string)$c->retained,'failed'=>(string)$c->failed,
            'next_cursor'=>$c->nextCursor,'attempted_at_utc'=>str_replace(['T','Z'],[' ','.000000'],$c->attemptedAtUtc)];
    }

    public static function tuple(AssignmentOrderOriginalMaintenanceCommit $c): array
    { return [$c->status,$c->reason,$c->retryable,$c->scanned,$c->deleted,$c->retained,$c->failed,$c->nextCursor]; }

    public static function valid(AssignmentOrderOriginalMaintenanceCommit $c): bool
    { return AssignmentOrderOriginalDataScalar::uuid($c->requestId) && AssignmentOrderOriginalMaintenanceValues::principal($c->systemPrincipalId)
        && AssignmentOrderOriginalDataScalar::utc($c->attemptedAtUtc) && AssignmentOrderOriginalMaintenanceValues::terminal(self::tuple($c)); }

    public static function decode(string $id, array $row, array $audit): AssignmentOrderOriginalMaintenanceResultValue
    {
        $required = ['request_id','system_principal_id','status','reason_code','retryable','scanned','deleted','retained','failed','attempted_at_utc'];
        foreach ($required as $key) if (!array_key_exists($key,$row) || !array_key_exists($key,$audit) || $row[$key] !== $audit[$key]) AssignmentOrderOriginalSql::fail();
        if (!array_key_exists('next_cursor',$row) || $row['request_id'] !== $id || !is_string($row['system_principal_id'])
            || !AssignmentOrderOriginalMaintenanceValues::principal($row['system_principal_id'])
            || AssignmentOrderOriginalDataScalar::sqlUtc($row['attempted_at_utc']) === null
            || AssignmentOrderOriginalDataScalar::integer($audit['audit_id'] ?? null) === null) AssignmentOrderOriginalSql::fail();
        $status = is_string($row['status']) ? AssignmentOrderOriginalMaintenanceStatus::tryFrom($row['status']) : null;
        $reason = $row['reason_code'] === null ? null : (is_string($row['reason_code']) ? AssignmentOrderOriginalMaintenanceReason::tryFrom($row['reason_code']) : null);
        $retry = AssignmentOrderOriginalDataScalar::integer($row['retryable'],0,1);
        $counts = array_map(fn($key)=>AssignmentOrderOriginalDataScalar::integer($row[$key],0,1000), ['scanned','deleted','retained','failed']);
        if ($status === null || ($row['reason_code'] !== null && $reason === null) || $retry === null || in_array(null,$counts,true)
            || ($row['next_cursor'] !== null && !is_string($row['next_cursor']))) AssignmentOrderOriginalSql::fail();
        $tuple = [$status,$reason,$retry === 1,...$counts,$row['next_cursor']];
        if (!AssignmentOrderOriginalMaintenanceValues::terminal($tuple)) AssignmentOrderOriginalSql::fail();
        return new AssignmentOrderOriginalMaintenanceResultValue(...$tuple);
    }
}

final readonly class AssignmentOrderOriginalMaintenanceLookupValue implements AssignmentOrderOriginalMaintenanceResultLookup
{
    public function __construct(private AssignmentOrderOriginalLookupStatus $state, private ?AssignmentOrderOriginalMaintenanceResult $value = null) {}
    public function status(): AssignmentOrderOriginalLookupStatus { return $this->state; }
    public function result(): ?AssignmentOrderOriginalMaintenanceResult { return $this->value; }
}
