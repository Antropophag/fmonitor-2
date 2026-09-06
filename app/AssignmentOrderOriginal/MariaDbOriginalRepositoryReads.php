<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/MariaDbOriginalStoredReader.php';

/** @internal Owns the transaction and total failure mapping for repository reads. */
final class AssignmentOrderOriginalRepositoryReads
{
    public static function read(\mysqli $db, string $prefix, ?AssignmentOrderOriginalPersistenceObserver $observer,
        string $kind, string|int $key, ?int $order = null): object
    {
        try {
            $valid = match ($kind) {
                'request' => is_string($key) && AssignmentOrderOriginalDataScalar::uuid($key),
                'fingerprint' => is_string($key) && AssignmentOrderOriginalDataScalar::hash($key),
                'assignment' => is_int($key) && $key > 0 && $order !== null && $order > 0,
                default => is_string($key) && AssignmentOrderOriginalCommandShape::validId($key),
            };
            if (!$valid) AssignmentOrderOriginalSql::fail();
            $sql = new AssignmentOrderOriginalSql($db, $prefix);
            $reader = new AssignmentOrderOriginalStoredReader($sql);
            return $sql->snapshot(fn() => $kind === 'assignment' ? $reader->assignment($key, $order) : $reader->{$kind}($key), $observer);
        } catch (\Throwable) {
            return match ($kind) {
                'request', 'fingerprint' => new AssignmentOrderOriginalResultLookupValue(AssignmentOrderOriginalLookupStatus::UNAVAILABLE),
                'reference' => new AssignmentOrderOriginalReferenceValue(AssignmentOrderOriginalLookupStatus::UNAVAILABLE, null),
                default => new AssignmentOrderOriginalMariaDbLineage(AssignmentOrderOriginalLookupStatus::UNAVAILABLE),
            };
        }
    }
}
