<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Append-only fact inserts within the accepted/attempt transaction owner. */
final readonly class AssignmentOrderOriginalSqlFacts
{
    public function __construct(private AssignmentOrderOriginalSql $sql) {}

    public function accepted(AssignmentOrderOriginalAcceptedCommit $c): void
    {
        $at = self::time($c->uploadedAt);
        if ($c->mode === AssignmentOrderOriginalMode::INITIAL) {
            $this->insert('roots', ['root_original_id' => $c->rootOriginalId, 'installation_case_id' => $c->installationCaseId,
                'assignment_order_id' => $c->assignmentOrderId, 'current_revision_id' => $c->newRevisionId,
                'composition_identity' => $c->compositionIdentity, 'composition_sha256' => $c->compositionSha256, 'created_at_utc' => $at]);
        } else {
            $this->sql->execute('UPDATE '.$this->sql->table('fm2_assignment_order_original_roots').' SET current_revision_id='
                .$this->sql->quote($c->newRevisionId).' WHERE root_original_id='.$this->sql->quote($c->rootOriginalId)
                .' AND current_revision_id='.$this->sql->quote($c->expectedCurrentRevisionId));
            if ($this->sql->db->affected_rows !== 1) throw new AssignmentOrderOriginalWriteConflict();
        }
        $this->insert('revisions', ['revision_id' => $c->newRevisionId, 'root_original_id' => $c->rootOriginalId,
            'revision_number' => $c->newRevisionNumber, 'previous_revision_id' => $c->previousRevisionId,
            'document_date' => $c->documentDate, 'uploaded_at_utc' => $at, 'actor_user_id' => $c->actorUserId,
            'pdf_sha256' => $c->pdfSha256, 'byte_size' => $c->byteSize, 'private_content_identity' => $c->privateContentIdentity,
            'correction_reason' => $c->correctionReason, 'request_id' => $c->requestId,
            'operation_fingerprint' => $c->fingerprint, 'event_type' => $c->domainEventType]);
        $this->insert('requests', ['request_id' => $c->requestId, 'mode' => $c->mode->value,
            'installation_case_id' => $c->installationCaseId, 'assignment_order_id' => $c->assignmentOrderId,
            'actor_identity' => (string) $c->actorUserId, 'status' => 'accepted', 'reason_code' => null, 'retryable' => 0,
            'root_original_id' => $c->rootOriginalId, 'current_revision_id' => $c->newRevisionId,
            'revision_number' => $c->newRevisionNumber, 'document_date' => $c->documentDate, 'sha256' => $c->pdfSha256,
            'byte_size' => $c->byteSize, 'uploaded_at_utc' => $at, 'attempted_at_utc' => $at]);
        $this->insert('events', ['event_type' => $c->domainEventType, 'installation_case_id' => $c->installationCaseId,
            'assignment_order_id' => $c->assignmentOrderId, 'root_original_id' => $c->rootOriginalId,
            'revision_id' => $c->newRevisionId, 'occurred_at_utc' => $at, 'actor_user_id' => $c->actorUserId]);
        $this->insert('audits', ['request_id' => $c->requestId, 'actor_identity' => (string) $c->actorUserId, 'mode' => $c->mode->value,
            'installation_case_id' => $c->installationCaseId, 'assignment_order_id' => $c->assignmentOrderId,
            'status' => 'accepted', 'reason_code' => null, 'attempted_at_utc' => $at]);
    }

    public function attempt(AssignmentOrderOriginalAttemptCommit $c, bool $audit): void
    {
        $row = ['request_id' => $c->requestId, 'actor_identity' => (string) $c->actorUserId, 'mode' => $c->mode->value,
            'installation_case_id' => $c->installationCaseId, 'assignment_order_id' => $c->assignmentOrderId,
            'status' => $c->status->value, 'reason_code' => $c->reason->value, 'attempted_at_utc' => self::time($c->attemptedAt)];
        if (!$audit) $row['retryable'] = 0;
        $this->insert($audit ? 'audits' : 'requests', $row);
    }

    private function insert(string $suffix, array $row): void
    {
        $columns = implode(',', array_map(static fn($key) => '`'.$key.'`', array_keys($row)));
        $values = implode(',', array_map(fn($value) => $this->sql->quote($value === null ? null : (string) $value), array_values($row)));
        $this->sql->execute('INSERT INTO '.$this->sql->table('fm2_assignment_order_original_'.$suffix).'('.$columns.') VALUES('.$values.')');
    }

    private static function time(string $utc): string
    { return str_replace(['T', 'Z'], [' ', '.000000'], $utc); }
}

/** @internal Proven current-pointer or unique-winner conflict requiring rollback. */
final class AssignmentOrderOriginalWriteConflict extends \RuntimeException {}
