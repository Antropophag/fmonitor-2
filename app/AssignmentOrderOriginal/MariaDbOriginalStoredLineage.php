<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Verifies every immutable revision and its atomic backing in one snapshot. */
final class AssignmentOrderOriginalStoredLineage
{
    public static function load(AssignmentOrderOriginalSql $sql, string $where): AssignmentOrderOriginalMariaDbLineage
    {
        $roots = $sql->rows('SELECT * FROM '.$sql->table('fm2_assignment_order_original_roots').' WHERE '.$where);
        if (count($roots) > 1) AssignmentOrderOriginalSql::fail();
        if ($roots === []) return new AssignmentOrderOriginalMariaDbLineage(AssignmentOrderOriginalLookupStatus::NOT_FOUND);
        $root = $roots[0];
        if (!AssignmentOrderOriginalCommandShape::validId($root['root_original_id'])
            || !AssignmentOrderOriginalCommandShape::validId($root['current_revision_id'])) AssignmentOrderOriginalSql::fail();
        $root['installation_case_id'] = AssignmentOrderOriginalStoredRows::integer($root['installation_case_id']);
        $root['assignment_order_id'] = AssignmentOrderOriginalStoredRows::integer($root['assignment_order_id']);
        $created = AssignmentOrderOriginalStoredRows::time($root['created_at_utc']);
        $revisions = $sql->rows('SELECT * FROM '.$sql->table('fm2_assignment_order_original_revisions')
            .' WHERE root_original_id='.$sql->quote($root['root_original_id']).' ORDER BY revision_number');
        $ids = []; $previous = null;
        foreach ($revisions as $index => $row) {
            $commit = self::commit($root, $row);
            if ($commit->newRevisionNumber !== $index + 1 || $commit->previousRevisionId !== $previous
                || in_array($commit->newRevisionId, $ids, true) || ($index === 0 && $commit->uploadedAt !== $created)) AssignmentOrderOriginalSql::fail();
            $requests = $sql->rows('SELECT * FROM '.$sql->table('fm2_assignment_order_original_requests').' WHERE request_id='.$sql->quote($commit->requestId));
            if (count($requests) !== 1) AssignmentOrderOriginalSql::fail();
            $request = AssignmentOrderOriginalStoredRows::request($requests[0]);
            AssignmentOrderOriginalStoredRows::acceptedRequest($request, $commit);
            AssignmentOrderOriginalStoredRows::audit($sql, $commit->requestId, $request);
            AssignmentOrderOriginalStoredRows::event($sql, $commit);
            $ids[] = $commit->newRevisionId;
            $previous = $commit->newRevisionId;
            $root['current_document_date'] = $commit->documentDate;
            $root['current_pdf_sha256'] = $commit->pdfSha256;
        }
        if ($ids === [] || $root['current_revision_id'] !== $previous) AssignmentOrderOriginalSql::fail();
        $root['current_revision_number'] = count($ids);
        return new AssignmentOrderOriginalMariaDbLineage(AssignmentOrderOriginalLookupStatus::FOUND, $root, $ids);
    }

    private static function commit(array $root, array $row): AssignmentOrderOriginalAcceptedCommit
    {
        $number = AssignmentOrderOriginalStoredRows::integer($row['revision_number'], 4294967295);
        $commit = new AssignmentOrderOriginalAcceptedCommit($row['request_id'], $row['operation_fingerprint'],
            $number === 1 ? AssignmentOrderOriginalMode::INITIAL : AssignmentOrderOriginalMode::CORRECTION,
            $root['installation_case_id'], $root['assignment_order_id'], AssignmentOrderOriginalStoredRows::integer($row['actor_user_id']),
            $row['root_original_id'], $row['revision_id'], $number, $row['previous_revision_id'], $row['previous_revision_id'],
            $root['composition_identity'], $root['composition_sha256'], $row['document_date'],
            AssignmentOrderOriginalStoredRows::time($row['uploaded_at_utc']), $row['pdf_sha256'],
            AssignmentOrderOriginalStoredRows::integer($row['byte_size'], 20971520), $row['private_content_identity'],
            $row['correction_reason'], $row['event_type']);
        if ($row['root_original_id'] !== $root['root_original_id'] || !AssignmentOrderOriginalCommitValues::accepted($commit)) AssignmentOrderOriginalSql::fail();
        return $commit;
    }
}
