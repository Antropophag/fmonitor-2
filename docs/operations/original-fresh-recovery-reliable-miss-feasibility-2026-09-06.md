# Original fresh recovery — reliable miss feasibility

- Date: `2026-09-06`
- Reviewer: separately tasked technical agent `/root/registry_engine_gate1`
- Database: `11.4.7-MariaDB-ubu2404`
- Scope: fresh terminal recovery reliable-NOT_FOUND implementation and proof detail
- Verdict: **EXACT-KEY LOCKING BARRIER REQUIRED AND CONSTRUCTIBLE**

This is a read-only feasibility conclusion for DATA-INTEGRITY-001 v0.6. No production source, test or specification was edited. The probe used only one randomly named disposable database containing a synthetic `requests(request_id,payload)` table. It changed no global server setting, used no production/shared data, and dropped its exact database in `finally`.

## Why an ordinary snapshot is insufficient

The v0.6 contract requires a fresh reader to distinguish a reliable request-key absence after an unknown commit from an unavailable observation. A plain nonlocking consistent read can establish its snapshot while the writer's matching insert is still uncommitted. It then returns zero rows immediately even if that writer later commits. Treating this result as reliable NOT_FOUND could incorrectly report no committed request.

The fresh reader therefore needs a current-read barrier on the exact unique request key before it can classify absence. Ordinary command/evidence readers keep their existing consistent-snapshot behavior; this requirement applies only to the one-shot fresh terminal recovery path after ambiguous commit or attempt outcome.

## Bounded MariaDB probe

Two independent connections targeted the disposable schema. The writer began a transaction and inserted synthetic key `request-1` without committing. The reader used:

```sql
SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;
SET SESSION innodb_lock_wait_timeout=2;
START TRANSACTION READ ONLY;
```

Observed results:

```text
ordinary exact-key SELECT while matching insert pending:
count=0, elapsed=0.000425208s

exact-key SELECT ... LOCK IN SHARE MODE while insert pending:
errno=1205, elapsed=2.004037833s

same locking SELECT after writer rollback:
count=0, elapsed=0.001100125s
```

`LOCK IN SHARE MODE` is permitted inside the MariaDB 11.4.7 read-only transaction. It does not misclassify the uncommitted matching insert as absent: it waits for the conflicting writer and, under the deliberately retained writer, reaches the bounded session timeout. Timeout is an unavailable observation, not NOT_FOUND. Once the writer rolls back, the same exact-key locking read establishes absence immediately.

All waits remained below five seconds. The probe did not widen timeouts, change global variables, use native interception, or retain any schema/data after cleanup.

## Minimal reliable-read protocol

The one-shot fresh reader should use this sequence:

1. Open a genuinely new trusted connection through the approved lazy factory and confirm the same configured server/database/prefix and `utf8mb4` contract.
2. Set a bounded session-only lock wait and begin a read-only READ COMMITTED transaction.
3. Make the first database read an exact primary-key current read of the queried request ID using `LOCK IN SHARE MODE`. No ordinary SELECT or consistent snapshot may precede this barrier.
4. Map lock timeout, deadlock, query failure, malformed row identity or transaction-release failure to typed UNAVAILABLE. Never convert these conditions to NOT_FOUND.
5. If the barrier sees no request row, inspect the exact queried request's backing namespace before confirming absence. A remaining revision keyed to that request, fingerprint/event/audit evidence claiming that request, duplicate/cross-linked backing, or other atomicity corruption makes the result UNAVAILABLE. Reliable NOT_FOUND means both the request row and every fact that would prove/contradict that request are absent.
6. Keep the key/gap lock until the negative classification is complete, then commit/release the owned read transaction and close once before publishing the immutable NOT_FOUND value.
7. If the barrier sees the request row, release the barrier transaction and begin a new read-only transaction with a coherent snapshot. Re-read the exact request and all root/revision/event/audit/fingerprint backing through the shared data-integrity validator. Only a complete valid immutable snapshot becomes FOUND.
8. If the row disappears, changes incompatibly, or backing is missing/malformed in the coherent snapshot, return UNAVAILABLE. Copy the validated Result before transaction release/reader close.

The second snapshot must not be established before the pending-writer barrier. Mixing a pre-commit snapshot with a later locking read could observe the request through current-read semantics while subsequent ordinary reads remain on an older snapshot and miss its atomically committed backing.

An alternative that locking-reads every backing table would enlarge lock ordering and deadlock surface and would no longer be the promised coherent snapshot. The barrier followed by a fresh coherent FOUND snapshot keeps the recovery contract explicit and bounded.

## Required real proof

The public factory/reader tests should demonstrate:

- pending exact request insert held beyond the bounded lock wait returns UNAVAILABLE and never NOT_FOUND;
- after a matching atomic writer commit, a fresh invocation passes the barrier and returns validated FOUND from a subsequent coherent snapshot;
- after writer rollback, a fresh invocation returns reliable NOT_FOUND;
- missing request plus a remaining revision/fingerprint/event/audit fact for that request returns UNAVAILABLE, not NOT_FOUND;
- malformed or incomplete accepted backing returns UNAVAILABLE;
- the fresh reader connection ID differs from the writer connection and targets the expected database/prefix;
- exactly one factory open, one reader lookup and one close occur; the content lease remains held through barrier, snapshot, validation and close;
- timeout/deadlock/query/close paths preserve the approved external recovery mapping and diagnostics;
- ordinary terminal, fingerprint, lineage and evidence readers retain their existing nonlocking consistent-snapshot contracts.

The pending-insert case should use explicit transaction/process coordination and the bounded session timeout rather than sleeps. Setup and cleanup failures remain failures, not RED/GREEN evidence.

## Contract disposition

This protocol implements and proves the already approved v0.6 meaning of reliable fresh NOT_FOUND. It adds no route, reason code, user-visible policy, repository mutation method or new public declaration. FOUND/NOT_FOUND/UNAVAILABLE outcomes and fresh-reader factory interfaces remain unchanged.

Atomic backing corruption, including an orphan revision for the queried request, is persistence unavailability. It cannot be hidden by the negative request-row probe or repaired by the reader.

This record is feasibility evidence only. The real factory/adapter tests still require demonstrated RED and independent Gate 3, followed by minimal implementation and independent Gate 5.
