<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/MariaDbOriginalStoredRows.php';
require_once __DIR__.'/MariaDbOriginalStoredLineage.php';

/** @internal Backing-aware reads inside the caller's explicitly owned snapshot. */
final readonly class AssignmentOrderOriginalStoredReader
{
    public function __construct(private AssignmentOrderOriginalSql $sql) {}

    public function request(string $id): AssignmentOrderOriginalResultLookup
    {
        $rows = $this->sql->rows('SELECT * FROM '.$this->sql->table('fm2_assignment_order_original_requests').' WHERE request_id='.$this->sql->quote($id));
        if (count($rows) > 1) AssignmentOrderOriginalSql::fail();
        $revisions = $this->sql->rows('SELECT * FROM '.$this->sql->table('fm2_assignment_order_original_revisions').' WHERE request_id='.$this->sql->quote($id));
        if ($rows === []) {
            $audits = $this->sql->rows('SELECT * FROM '.$this->sql->table('fm2_assignment_order_original_audits').' WHERE request_id='.$this->sql->quote($id));
            if ($revisions !== []) AssignmentOrderOriginalSql::fail();
            foreach ($audits as $row) {
                $audit = AssignmentOrderOriginalAttemptAuditRows::parse($row, $id);
                if ($audit['status'] !== AssignmentOrderOriginalStatus::FAILED) AssignmentOrderOriginalSql::fail();
            }
            return new AssignmentOrderOriginalResultLookupValue(AssignmentOrderOriginalLookupStatus::NOT_FOUND);
        }
        if ($rows[0]['request_id'] !== $id) AssignmentOrderOriginalSql::fail();
        $request = AssignmentOrderOriginalStoredRows::request($rows[0]);
        $result = $request['result'];
        AssignmentOrderOriginalStoredRows::audit($this->sql, $id, $request);
        if ($result->status() === AssignmentOrderOriginalStatus::ACCEPTED) {
            if (count($revisions) !== 1 || $revisions[0]['revision_id'] !== $result->currentRevisionId()
                || $revisions[0]['root_original_id'] !== $result->rootOriginalId()) AssignmentOrderOriginalSql::fail();
            $line = $this->root($result->rootOriginalId());
            if ($line->status() !== AssignmentOrderOriginalLookupStatus::FOUND || !$line->containsRevision($result->currentRevisionId())) AssignmentOrderOriginalSql::fail();
        } elseif ($revisions !== []) AssignmentOrderOriginalSql::fail();
        return new AssignmentOrderOriginalResultLookupValue(AssignmentOrderOriginalLookupStatus::FOUND, $result);
    }

    public function fingerprint(string $fingerprint): AssignmentOrderOriginalResultLookup
    {
        $rows = $this->sql->rows('SELECT * FROM '.$this->sql->table('fm2_assignment_order_original_revisions').' WHERE operation_fingerprint='.$this->sql->quote($fingerprint));
        if (count($rows) > 1) AssignmentOrderOriginalSql::fail();
        if ($rows === []) return new AssignmentOrderOriginalResultLookupValue(AssignmentOrderOriginalLookupStatus::NOT_FOUND);
        $row = $rows[0];
        if ($row['operation_fingerprint'] !== $fingerprint) AssignmentOrderOriginalSql::fail();
        $lookup = $this->request($row['request_id']);
        if ($lookup->status() !== AssignmentOrderOriginalLookupStatus::FOUND
            || $lookup->result()->status() !== AssignmentOrderOriginalStatus::ACCEPTED
            || $lookup->result()->currentRevisionId() !== $row['revision_id']) AssignmentOrderOriginalSql::fail();
        return $lookup;
    }

    public function root(string $id): AssignmentOrderOriginalMariaDbLineage
    {
        $line = AssignmentOrderOriginalStoredLineage::load($this->sql, 'root_original_id='.$this->sql->quote($id));
        if ($line->status() === AssignmentOrderOriginalLookupStatus::NOT_FOUND) {
            foreach (['revisions', 'requests'] as $table) {
                if ($this->sql->rows('SELECT root_original_id FROM '.$this->sql->table('fm2_assignment_order_original_'.$table)
                    .' WHERE root_original_id='.$this->sql->quote($id)) !== []) AssignmentOrderOriginalSql::fail();
            }
        } elseif ($line->rootOriginalId() !== $id) AssignmentOrderOriginalSql::fail();
        return $line;
    }

    public function assignment(int $caseId, int $orderId): AssignmentOrderOriginalMariaDbLineage
    {
        return AssignmentOrderOriginalStoredLineage::load($this->sql, 'installation_case_id='.$caseId.' AND assignment_order_id='.$orderId);
    }

    public function revision(string $id): AssignmentOrderOriginalMariaDbLineage
    {
        $rows = $this->sql->rows('SELECT revision_id,root_original_id FROM '.$this->sql->table('fm2_assignment_order_original_revisions').' WHERE revision_id='.$this->sql->quote($id));
        if (count($rows) > 1) AssignmentOrderOriginalSql::fail();
        if ($rows === []) return new AssignmentOrderOriginalMariaDbLineage(AssignmentOrderOriginalLookupStatus::NOT_FOUND);
        if ($rows[0]['revision_id'] !== $id) AssignmentOrderOriginalSql::fail();
        $line = $this->root($rows[0]['root_original_id']);
        if ($line->status() !== AssignmentOrderOriginalLookupStatus::FOUND || !$line->containsRevision($id)) AssignmentOrderOriginalSql::fail();
        return $line;
    }

    public function reference(string $id): AssignmentOrderOriginalReferenceLookup
    {
        $rows = $this->sql->rows('SELECT COUNT(*) n FROM '.$this->sql->table('fm2_assignment_order_original_revisions').' WHERE private_content_identity='.$this->sql->quote($id));
        if (count($rows) !== 1) AssignmentOrderOriginalSql::fail();
        return new AssignmentOrderOriginalReferenceValue(AssignmentOrderOriginalLookupStatus::FOUND,
            AssignmentOrderOriginalStoredRows::integer($rows[0]['n'], PHP_INT_MAX, 0) > 0);
    }
}
