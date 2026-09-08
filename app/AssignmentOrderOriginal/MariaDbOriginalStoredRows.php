<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/MariaDbOriginalAttemptAuditRows.php';

/** @internal Lossless SQL rehydration, without synthesizing missing evidence. */
final class AssignmentOrderOriginalStoredRows
{
    public static function integer(mixed $value, int $maximum = PHP_INT_MAX, int $minimum = 1): int
    { return AssignmentOrderOriginalDataScalar::integer($value, $minimum, $maximum) ?? AssignmentOrderOriginalSql::fail(); }

    public static function time(mixed $value): string
    { return AssignmentOrderOriginalDataScalar::sqlUtc($value) ?? AssignmentOrderOriginalSql::fail(); }

    public static function request(array $row): array
    {
        $value = new AssignmentOrderOriginalResultValue(AssignmentOrderOriginalStatus::from($row['status']),
            $row['reason_code'] === null ? null : AssignmentOrderOriginalReason::from($row['reason_code']),
            self::integer($row['retryable'], 1, 0) === 1, $row['request_id'], $row['root_original_id'], $row['current_revision_id'],
            $row['revision_number'] === null ? null : self::integer($row['revision_number'], 4294967295), $row['document_date'], $row['sha256'],
            $row['byte_size'] === null ? null : self::integer($row['byte_size'], 20971520),
            $row['uploaded_at_utc'] === null ? null : self::time($row['uploaded_at_utc']));
        return ['result' => AssignmentOrderOriginalResultSnapshot::copy($value, $row['request_id']),
            'mode' => AssignmentOrderOriginalMode::from($row['mode']), 'case' => self::integer($row['installation_case_id']),
            'order' => self::integer($row['assignment_order_id']), 'actor' => self::integer($row['actor_identity']),
            'at' => self::time($row['attempted_at_utc'])];
    }

    public static function audit(AssignmentOrderOriginalSql $sql, string $requestId, array $request, bool $lock = false): void
    {
        $result = $request['result'];
        $rows = $sql->rows('SELECT * FROM '.$sql->table('fm2_assignment_order_original_audits').' WHERE request_id='.$sql->quote($requestId).($lock ? ' FOR UPDATE' : ''));
        $denial = $result->reasonCode() === AssignmentOrderOriginalReason::AUTHORIZATION_DENIED;
        $matches = 0;
        foreach ($rows as $row) {
            $audit = AssignmentOrderOriginalAttemptAuditRows::parse($row, $requestId);
            if (AssignmentOrderOriginalAttemptAuditRows::matches($audit, $request)) ++$matches;
            elseif (!AssignmentOrderOriginalAttemptAuditRows::additional($audit)) AssignmentOrderOriginalSql::fail();
        }
        if ($denial ? $matches < 1 : $matches !== 1) AssignmentOrderOriginalSql::fail();
    }

    public static function acceptedRequest(array $request, AssignmentOrderOriginalAcceptedCommit $commit): void
    {
        $r = $request['result'];
        $actual = [$r->status(), $r->requestId(), $r->rootOriginalId(), $r->currentRevisionId(), $r->revisionNumber(),
            $r->documentDate(), $r->sha256(), $r->byteSize(), $r->uploadedAt(), $request['mode'], $request['case'],
            $request['order'], $request['actor'], $request['at']];
        $expected = [AssignmentOrderOriginalStatus::ACCEPTED, $commit->requestId, $commit->rootOriginalId, $commit->newRevisionId,
            $commit->newRevisionNumber, $commit->documentDate, $commit->pdfSha256, $commit->byteSize, $commit->uploadedAt,
            $commit->mode, $commit->installationCaseId, $commit->assignmentOrderId, $commit->actorUserId, $commit->uploadedAt];
        if ($actual !== $expected) AssignmentOrderOriginalSql::fail();
    }

    public static function event(AssignmentOrderOriginalSql $sql, AssignmentOrderOriginalAcceptedCommit $commit, bool $lock = false): void
    {
        $rows = $sql->rows('SELECT * FROM '.$sql->table('fm2_assignment_order_original_events').' WHERE revision_id='.$sql->quote($commit->newRevisionId).($lock ? ' FOR UPDATE' : ''));
        if (count($rows) !== 1) AssignmentOrderOriginalSql::fail();
        $row = $rows[0];
        if ([$row['event_type'], $row['root_original_id'], $row['revision_id'], self::integer($row['installation_case_id']),
            self::integer($row['assignment_order_id']), self::integer($row['actor_user_id']), self::time($row['occurred_at_utc'])]
            !== [$commit->domainEventType, $commit->rootOriginalId, $commit->newRevisionId, $commit->installationCaseId,
                $commit->assignmentOrderId, $commit->actorUserId, $commit->uploadedAt]) AssignmentOrderOriginalSql::fail();
    }
}
