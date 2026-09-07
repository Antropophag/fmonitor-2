<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final class MariaDbInspectionTransaction
{
    private bool $open = false;
    private ?int $revision = null;

    public function __construct(private readonly \mysqli $db, private readonly string $prefix)
    {
    }

    public function begin(int $id,string $now): void
    {
        $this->db->begin_transaction();
        $this->open = true;
        $initialize=$this->db->prepare(
            'INSERT IGNORE INTO '.$this->table('fm2_checklist_revisions')
            .'(installation_case_id,revision_no,updated_at) VALUES(?,0,?)'
        );
        $initialize->bind_param('is',$id,$now);
        $initialize->execute();
        $statement = $this->db->prepare(
            'SELECT revision_no FROM '.$this->table('fm2_checklist_revisions')
            .' WHERE installation_case_id=? FOR UPDATE'
        );
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $this->revision = $row === null ? null : (int) $row['revision_no'];
    }

    public function commit(): void
    {
        if ($this->open) {
            $this->db->commit();
            $this->open = false;
        }
    }

    public function rollBack(): void
    {
        if ($this->open) {
            $this->db->rollback();
            $this->open = false;
        }
    }

    public function revision(): int
    {
        return $this->revision ?? 0;
    }

    public function setAcceptedRevision(int $revision): void
    {
        $this->revision = $revision;
    }

    private function table(string $name): string
    {
        return '`'.$this->prefix.$name.'`';
    }
}
