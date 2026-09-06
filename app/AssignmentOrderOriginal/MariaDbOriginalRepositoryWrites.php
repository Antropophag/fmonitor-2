<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/MariaDbOriginalSqlFacts.php';
require_once __DIR__.'/MariaDbOriginalSqlWriteGuard.php';

/** @internal Single native transaction owner, with explicit acknowledgement uncertainty. */
final class AssignmentOrderOriginalRepositoryWrites
{
    public static function commit(\mysqli $db, string $prefix, AssignmentOrderOriginalAcceptedCommit|AssignmentOrderOriginalAttemptCommit $c,
        ?AssignmentOrderOriginalWorkerFaults $faults, ?AssignmentOrderOriginalPersistenceObserver $observer, bool $selectedCompositions = false): AssignmentOrderOriginalCommitStatus
    {
        $accepted = $c instanceof AssignmentOrderOriginalAcceptedCommit;
        if (!($accepted ? AssignmentOrderOriginalCommitValues::accepted($c) : AssignmentOrderOriginalCommitValues::attempt($c)))
            return AssignmentOrderOriginalCommitStatus::ROLLED_BACK;
        $owned = false; $commitAttempted = false; $committed = false; $requestInserted = false;
        try {
            $sql = new AssignmentOrderOriginalSql($db, $prefix);
            if (!$sql->idle()) return AssignmentOrderOriginalCommitStatus::ROLLED_BACK;
            $observer?->observe(AssignmentOrderOriginalPersistenceEvent::BEFORE_WRITE_BEGIN);
            $sql->execute('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            if (!$db->begin_transaction(MYSQLI_TRANS_START_READ_WRITE)) AssignmentOrderOriginalSql::fail();
            $owned = true;
            if ($accepted) {
                $faults?->beforeCommit();
                AssignmentOrderOriginalSqlWriteGuard::accepted($sql, $c, $observer, $selectedCompositions);
                (new AssignmentOrderOriginalSqlFacts($sql))->accepted($c);
            } else {
                $facts = new AssignmentOrderOriginalSqlFacts($sql);
                $facts->attempt($c, false);
                $requestInserted = true;
                $facts->attempt($c, true);
            }
            $observer?->observe(AssignmentOrderOriginalPersistenceEvent::BEFORE_NATIVE_COMMIT);
            $commitAttempted = true;
            if (!$db->commit()) AssignmentOrderOriginalSql::fail();
            $committed = true;
            $observer?->observe(AssignmentOrderOriginalPersistenceEvent::AFTER_NATIVE_COMMIT);
            $unknown = [AssignmentOrderOriginalFaultPoint::COMMIT_UNKNOWN_FOUND, AssignmentOrderOriginalFaultPoint::COMMIT_UNKNOWN_UNAVAILABLE,
                AssignmentOrderOriginalFaultPoint::COMMIT_UNKNOWN_FOUND_RELEASE_FAILURE, AssignmentOrderOriginalFaultPoint::COMMIT_UNKNOWN_UNAVAILABLE_RELEASE_FAILURE];
            return $accepted && $faults !== null && in_array($faults->target, $unknown, true)
                ? AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN : AssignmentOrderOriginalCommitStatus::COMMITTED;
        } catch (\Throwable $error) {
            if ($committed) return AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN;
            $rolledBack = !$owned || self::rollback($db, $observer);
            $unknownMiss = $accepted && $faults !== null && in_array($faults->target,
                [AssignmentOrderOriginalFaultPoint::COMMIT_UNKNOWN_NOT_FOUND, AssignmentOrderOriginalFaultPoint::COMMIT_UNKNOWN_NOT_FOUND_RELEASE_FAILURE], true);
            if ($commitAttempted || !$rolledBack || ($owned && $unknownMiss)) return AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN;
            if ($owned && $error instanceof AssignmentOrderOriginalCompositionNotCurrent) return AssignmentOrderOriginalCommitStatus::COMPOSITION_NOT_CURRENT;
            $conflict = $error instanceof AssignmentOrderOriginalWriteConflict
                || ($error instanceof \mysqli_sql_exception && $error->getCode() === 1062 && ($accepted || !$requestInserted));
            return $owned && $conflict ? AssignmentOrderOriginalCommitStatus::CONFLICT : AssignmentOrderOriginalCommitStatus::ROLLED_BACK;
        }
    }

    private static function rollback(\mysqli $db, ?AssignmentOrderOriginalPersistenceObserver $observer): bool
    {
        $confirmed = true;
        try { $observer?->observe(AssignmentOrderOriginalPersistenceEvent::BEFORE_WRITE_ROLLBACK); }
        catch (\Throwable) { $confirmed = false; }
        try { if (!$db->rollback()) $confirmed = false; }
        catch (\Throwable) { $confirmed = false; }
        return $confirmed;
    }
}
