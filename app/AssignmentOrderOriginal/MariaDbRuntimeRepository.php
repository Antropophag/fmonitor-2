<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/AssignmentOrderOriginalCommitValues.php';
require_once __DIR__.'/MariaDbOriginalSql.php';
require_once __DIR__.'/MariaDbOriginalRepositoryReads.php';
require_once __DIR__.'/MariaDbOriginalRepositoryWrites.php';

final class AssignmentOrderOriginalMariaDbRepository implements AssignmentOrderOriginalRepository, AssignmentOrderOriginalAssignmentLineageRepository, AssignmentOrderOriginalRevisionLineageRepository
{
    public function __construct(private \mysqli $db, private string $p, private ?AssignmentOrderOriginalWorkerFaults $faults = null, private ?AssignmentOrderOriginalPersistenceObserver $observer = null) {}
    public function findTerminalRequest(string $id): AssignmentOrderOriginalResultLookup
    { return AssignmentOrderOriginalRepositoryReads::read($this->db, $this->p, $this->observer, 'request', $id); }
    public function findAcceptedFingerprint(string $fp): AssignmentOrderOriginalResultLookup
    { return AssignmentOrderOriginalRepositoryReads::read($this->db, $this->p, $this->observer, 'fingerprint', $fp); }
    public function findLineage(string $root): AssignmentOrderOriginalLineageLookup
    { return AssignmentOrderOriginalRepositoryReads::read($this->db, $this->p, $this->observer, 'root', $root); }
    public function findLineageForAssignmentOrder(int $caseId, int $orderId): AssignmentOrderOriginalLineageLookup
    { return AssignmentOrderOriginalRepositoryReads::read($this->db, $this->p, $this->observer, 'assignment', $caseId, $orderId); }
    public function findLineageForRevision(string $revisionId): AssignmentOrderOriginalLineageLookup
    { return AssignmentOrderOriginalRepositoryReads::read($this->db, $this->p, $this->observer, 'revision', $revisionId); }
    public function commitAccepted(AssignmentOrderOriginalAcceptedCommit $c): AssignmentOrderOriginalCommitStatus
    { return AssignmentOrderOriginalRepositoryWrites::commit($this->db, $this->p, $c, $this->faults, $this->observer); }
    public function commitAttempt(AssignmentOrderOriginalAttemptCommit $c): AssignmentOrderOriginalCommitStatus
    { return AssignmentOrderOriginalRepositoryWrites::commit($this->db, $this->p, $c, null, $this->observer); }
    public function hasCommittedContent(string $id): AssignmentOrderOriginalReferenceLookup
    { return AssignmentOrderOriginalRepositoryReads::read($this->db, $this->p, $this->observer, 'reference', $id); }
    public function evidenceCanonicalJson(int $caseId,int $orderId):string{return'{}';}
}
