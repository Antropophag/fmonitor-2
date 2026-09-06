<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Locks authoritative composition and the existing correction owner. */
final class AssignmentOrderOriginalSqlWriteGuard
{
    public static function accepted(AssignmentOrderOriginalSql $sql, AssignmentOrderOriginalAcceptedCommit $c,
        ?AssignmentOrderOriginalPersistenceObserver $observer, bool $selectedCompositions = false): void
    {
        if ($selectedCompositions) {
            $case = $sql->rows('SELECT id FROM '.$sql->table('fm2_installation_cases').' WHERE id='.$c->installationCaseId.' FOR UPDATE');
            if (count($case) !== 1) AssignmentOrderOriginalSql::fail();
            $composition = MariaDbSelectedOriginalComposition::read($sql, $c->installationCaseId, $c->assignmentOrderId, $observer, true);
            if ($composition->status === AssignmentOrderCompositionLookupStatus::NOT_CURRENT) throw new AssignmentOrderOriginalCompositionNotCurrent();
        } else {
            $composition = AssignmentOrderOriginalSqlComposition::read($sql, $c->installationCaseId, $c->assignmentOrderId, $observer, true);
        }
        if ($composition->status !== AssignmentOrderCompositionLookupStatus::FOUND
            || !AssignmentOrderOriginalCompositionValues::content($composition)
            || $composition->identity !== $c->compositionIdentity || $composition->sha256 !== $c->compositionSha256) AssignmentOrderOriginalSql::fail();
        if ($c->mode === AssignmentOrderOriginalMode::INITIAL) return;
        $roots = $sql->rows('SELECT * FROM '.$sql->table('fm2_assignment_order_original_roots')
            .' WHERE root_original_id='.$sql->quote($c->rootOriginalId).' FOR UPDATE');
        if (count($roots) !== 1) AssignmentOrderOriginalSql::fail();
        $current = $sql->rows('SELECT revision_id FROM '.$sql->table('fm2_assignment_order_original_revisions')
            .' WHERE revision_id='.$sql->quote($roots[0]['current_revision_id']).' FOR UPDATE');
        if (count($current) !== 1) AssignmentOrderOriginalSql::fail();
        $line = (new AssignmentOrderOriginalStoredReader($sql))->root($c->rootOriginalId);
        if ($line->status() !== AssignmentOrderOriginalLookupStatus::FOUND || $line->installationCaseId() !== $c->installationCaseId
            || $line->assignmentOrderId() !== $c->assignmentOrderId || $line->compositionIdentity() !== $c->compositionIdentity
            || $line->compositionSha256() !== $c->compositionSha256) AssignmentOrderOriginalSql::fail();
        if ($line->currentRevisionId() !== $c->expectedCurrentRevisionId) throw new AssignmentOrderOriginalWriteConflict();
        if ($c->newRevisionNumber !== $line->currentRevisionNumber() + 1) AssignmentOrderOriginalSql::fail();
        if ($line->currentDocumentDate() === $c->documentDate && $line->currentPdfSha256() === $c->pdfSha256)
            throw new AssignmentOrderOriginalWriteConflict();
    }
}
