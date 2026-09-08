# Independent Gate 1 — ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001

- Verdict: **APPROVED**
- Reviewer: `/root/original_gate1_v3`, separately tasked agent; not the author of the specification, tests or proposed implementation.
- Date: 2026-09-07
- Reviewed HEAD: `185d95c616266260aafa8e874acc9b95d507c3d5`.
- Specification: version 0.1, SHA-256 `cf1ae6a3da0683cc7d4f5040dd03e72963b5f4b7958cd2b71101c8d8c7c46841`.

## Findings and decision

No blocking specification findings. The factory, lookup, immutable reference and guard form an explicit in-process data seam owned by AssignmentOrderOriginal. This is a trusted application port whose consumer must authorize access; it adds no grant, public HTTP read path, application writer or schema version. The unresolved parent policy for corrections after application does not affect whether this exact selected-order reference remains current.

The snapshot and guard have distinct, observable transaction contracts. `readCurrent` owns a read-only consistent snapshot only on an idle connection, leaves it idle afterwards and refuses an existing caller transaction without committing or rolling it back. `confirmCurrent` requires the issuing reader and its original database/native connection/charset binding, a caller write transaction, the canonical case lock and current locking source reads. It retains caller ownership and does not use an earlier repeatable-read snapshot to infer a current root. The reader never selects a globally applicable/latest order or decides application chronology.

Failure distinctions are closed: missing selected source/original is `not_found`; malformed backing or unavailable dependencies fail closed; positive-identifier validation precedes SQL; invalid prefix has the fixed sanitized configuration exception. A valid new revision is `changed`, while corrupt/missing source or changed sealed metadata at the same revision is `unavailable`. Foreign references and database/charset/connection switches cannot produce `matched`. Prefix 25 and wrong-prefix no-fallback behavior are explicit.

The exact 14-key metadata projection contains immutable selected composition and original document/upload values, with nested installer ordering and engineer fields specified. It excludes private content identity, paths, filenames, configuration and seal internals. Returning a modified metadata array must not alter the sealed reference. Neither lookup nor guard asserts PDF availability or reads filesystem bytes, and repeated confirmation creates no facts or audit rows.

Existing owning-module source/StoredReader and registered-composition helpers provide the correct validation boundary to reuse. Read-only inspection confirms the registered query already distinguishes optional locking reads, while ordinary StoredReader/source queries use snapshot reads. Merely calling those ordinary snapshot queries after taking the case lock would not discharge the new current-read contract. Gate 3 must include a caller RR snapshot established before a separately committed correction, followed by guard confirmation that sees `changed`, in addition to the opposite concurrency proof that a real correction worker waits while the guard owns the case lock. Bounded native worker cleanup and exact caller transaction preservation remain required.

## Evidence and limits

Read the specification and all four OpenSpec artifacts; inspected `MariaDbOriginalSubmissionSource`, `AssignmentOrderOriginalStoredReader` in `MariaDbOriginalStoredReader.php`, `MariaDbRegisteredCompositionQuery`, and the existing original write-lock/snapshot boundaries. The independently fixed selection composition identity/hash is also present in the approved selection specification and native tracer expectations. Reused previously read repository constitution and mandatory product/delivery contracts.

This review approves the contract, not test evidence or implementation. The fixed original PDF metadata and generated identities must be demonstrated by healthy public selection/original setup before intended RED; setup failure cannot serve as RED. Native RR freshness, correction blocking, corruption, metadata-copy isolation, database binding, prefix and no-side-effect evidence remain Gate 2/3 obligations.

Only this review record was added. No specification, tests, production source, transaction or filesystem fixture was changed, and no database test was run. Gate 1 is **APPROVED**; independent Gate 3, minimal GREEN, regression/architecture checks and independent Gate 5 remain required.
