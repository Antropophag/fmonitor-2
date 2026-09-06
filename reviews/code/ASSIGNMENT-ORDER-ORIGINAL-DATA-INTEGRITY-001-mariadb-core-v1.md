# Code review: DATA-INTEGRITY-001 MariaDB reads/writes/composition v1

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed implementation: `94a17bfef8175669a2265ebd03a33e77c143bfee`
- Implementation baseline: `8350038`
- Approved specification SHA-256: `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Scope: MariaDB composition, repository reads/rehydration, commit scalar/write/rollback boundaries and complete lineage
- Verdict: **CHANGES_REQUESTED**

The reviewer authored neither implementation nor tests/specification. This review is stored outside the repository as requested. No repository file was edited by this review.

## Blocking finding 1 — negative lineage membership is not validated

DATA-INTEGRITY-001 section 4 requires NOT_FOUND and UNAVAILABLE lineage values to expose null metadata, an empty revision list, and false membership. `AssignmentOrderOriginalLineageSnapshot::read` reads status/base/current/complete metadata, but its negative branch checks only those scalar values and `revisionIds === []`. It never calls `containsRevision` for the supplied target or query revision unless status is FOUND.

A foreign type-correct negative lineage can therefore return all required null/empty getters while returning true from `containsRevision(target)`, and the snapshot accepts it. This violates the closed negative lookup contract and leaves hidden contradictory membership unobserved.

Required correction:

- add a focused public-seam RED for NOT_FOUND and UNAVAILABLE complete/base-compatible lineages whose `containsRevision` returns true for the exact target/query revision;
- require persistence failure rather than absence/business conflict;
- on negative status, call membership only for the exact target/query revision values relevant to the invocation and require false;
- preserve getter-once/call-count rules and do not enumerate arbitrary IDs from a negative source;
- obtain independent Gate 3 before changing production.

This is a production-source defect in the reviewed scope, not merely a missing regression.

## Blocking finding 2 — three required worker regressions are red

The final runner is complete and clean at the reviewed SHA, but only 42 of 45 commands pass. These three existing approved regressions fail:

```text
assignment_order_original_lease_race_001_test.php
assignment_order_original_worker_post_finalize_negative_001_test.php
assignment_order_original_worker_transport_001_test.php
```

The failure evidence is consistent with the new strict password-path validator rejecting older fixture paths whose `/var/...` spelling resolves to `/private/var/...` on Darwin. Workers exit before READY/result, producing the observed missing READY, empty result JSON and fixed worker failure transport.

The strict canonical validation is required and should not be weakened. The fixture paths need the separately gated canonicalization patch described by root. Until that exact patch receives independent test approval, is applied, and all three scripts pass on the same implementation SHA, Gate 5 cannot treat the affected regression suite as green.

Required closure:

1. preserve the current failure logs;
2. obtain independent Gate 3 for the exact fixture-only canonical path patch;
3. change no production validation expectation;
4. rerun the three failures plus the full affected 45-command inventory at one frozen SHA;
5. retain literal PASS/failure counts rather than describing the current run as fully green.

## Confirmed source properties

No other blocking issue was found in the scoped MariaDB core.

### SQL and transaction ownership

`AssignmentOrderOriginalSql` validates the prefix, uses lossless quoting, requires an idle caller connection, starts owned read-only REPEATABLE READ snapshots, and attempts release on every result. Release or observer failure converts the read to UNAVAILABLE without committing or replacing a caller transaction.

Composition reads exact order then members inside one snapshot. Wrong case returns NOT_FOUND before member probing. Numeric/date/action/member errors retain the required protocol-versus-business distinction. The write guard reads the same authoritative composition under the owned READ COMMITTED transaction with row locks before any original fact mutation.

### Stored rows and complete historical backing

Stored scalar conversion accepts only native integers or canonical in-range decimal strings and round-trips Gregorian dates/UTC seconds. Terminal request reads reject impossible row/result tuples and require matching audit. Accepted requests require exactly one request-owned revision and a complete validated lineage containing that historical revision; they do not force the historical request revision to equal the root's later current head.

Lineage hydration reads exactly one root, all revisions in number order, validates contiguous `1..N`, previous links, unique IDs, root current equal the last revision, initial created time, and each revision's request/audit/event backing. Root, assignment and revision-owner lookups reject duplicates, dangling owners and cross-query identity mismatches rather than selecting through LIMIT precedence. Genuine missing roots cannot hide matching revision/request evidence.

Fingerprint reads require one exact backing revision and its validated accepted request. Reference reads validate one canonical COUNT row and preserve FOUND/false for zero. No read performs repair or DML.

### Scalar and write safety

AcceptedCommit and AttemptCommit validation occurs before constructing the SQL owner, observer callback, escaping, transaction or query. It covers UUID, positive bounds, mode/event, composition/fingerprint/PDF hashes, date/time, digest-bound content identity, revision relations and normalized correction reason. The approved zero-SQL sentinels and real controls remain unchanged.

The write owner rejects an active caller transaction, observes exact prebegin/precommit/postcommit/rollback boundaries, and owns one READ COMMITTED transaction. Before-commit failure with confirmed rollback returns ROLLED_BACK. Native commit false/Throwable or postcommit observer loss returns OUTCOME_UNKNOWN. Rollback-observer failure cannot suppress native rollback and prevents false confirmation.

Attempt request-key collision is distinguished from later audit/integrity failure. Initial uniqueness and proven current-pointer/no-change conditions return CONFLICT only after rollback; authoritative composition/storage corruption returns ROLLED_BACK. Inserts remain append-only and correction advances only the current pointer through exact CAS.

The fixed-code query-false path is preserved: `AssignmentOrderOriginalSql::execute` converts native false into a mysqli_sql_exception carrying the native errno, allowing the write owner to retain 1062-versus-other classification. Other false reads fail unavailable without inventing rows.

## Explicitly separate findings

The localhost-versus-Unix-socket host grammar question belongs to the fresh-connection contract, not this scoped core verdict. Denial audit cardinality remains deferred by the approved specification. Fresh factory/worker source ordering and maintenance adapter behavior likewise require their own reviews.

## Verification evidence

Final immutable evidence:

```text
7616880e290a58c0fe5671c80998a498f087b9e89249eb39e2afd6c755c5283c  /Users/antropophag/.local/state/fmonitor2-verification/original-data-integrity-green-vrynb89_/evidence.json
```

The archive records `complete=true`, exact head/afterHead `94a17bfef8175669a2265ebd03a33e77c143bfee`, 45 commands, 42 exit-zero results and the three explicit failures above. All 751 new data-integrity cases pass, as do architecture, unit, lint, strict OpenSpec and diff checks. Those successes do not close the negative-lineage gap or the three failed regressions.

## Exact reviewed hashes

```text
025748b86ea82374717fb0d9cb28f75702ec0a3c3bc2ac68600fd3e995322d4a  app/AssignmentOrderOriginal/MariaDbOriginalSql.php
dcdfe056693cad39883673514992445c9b91bde35a95f95ace66afd7882a5574  app/AssignmentOrderOriginal/MariaDbOriginalSqlComposition.php
e800e2755060700ef9a646314b2b3377a396144f926d6a6ed4321155737d154e  app/AssignmentOrderOriginal/MariaDbOriginalStoredRows.php
66eaad0659747494551b0c0ccc28fa1ebce29a56cd85a66b391dd0915cdb903d  app/AssignmentOrderOriginal/MariaDbOriginalStoredLineage.php
8de6491d68532fa4a3f01041fc751bc45bdb165bf93e473024c7646007e8934d  app/AssignmentOrderOriginal/MariaDbOriginalStoredReader.php
162f4b8f20b8a129735f82e9cb307a8a7f5787578b1494c8b64d0fba8bc07f98  app/AssignmentOrderOriginal/MariaDbOriginalRepositoryReads.php
2878617d8ab09867e7336e410e995288d4bfeaf3d70d387178f7c1f06b4fb3e2  app/AssignmentOrderOriginal/MariaDbOriginalRepositoryWrites.php
edbb5d6a5606926782354159dbb061ca8075943c5fa6bfe38e96eb06789d923d  app/AssignmentOrderOriginal/MariaDbOriginalSqlFacts.php
14404cea4ae953fc41bd9beb80d70f5ee4eca146727a0396ee221f681e8c767c  app/AssignmentOrderOriginal/MariaDbOriginalSqlWriteGuard.php
0e13147c360c5d665bd5bd5ff3dad93f736ecc4bced633a2a530fe12311d8136  app/AssignmentOrderOriginal/AssignmentOrderOriginalCommitValues.php
be25ce4d572486d71133d1c6ad7f1e8c793443987be8ac64dd112f2767dae801  app/AssignmentOrderOriginal/AssignmentOrderOriginalLineageSnapshot.php
7c295f7d3cdd9543b2ae6f6f3c3be867e9dc8dfed699a910c4c4c5bfaafe5ec5  app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php
df4f742547c04f38de7fa00246f74eb91ceabb413e3fb8dac82bf71183d3cce1  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-DATA-MARIADB-READS-001-v2.md
e9f788f1e67bf906470f48356412c61b45392bee5b1784f4f22910cca3b730fa  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-DATA-MARIADB-COMPOSITION-001-v2.md
83acf8042b3e89d5915ade36755ccbe23c633fdafedf1ec7da512a844484d962  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-DATA-MARIADB-WRITES-001-v1.md
c6a41409e3e8af021001d4898b80730b3745010984b8d362fd1d81166060139a  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-DATA-COMMIT-SCALARS-001-v1.md
```

## Verdict boundary

CHANGES_REQUESTED applies to this exact MariaDB reads/writes/composition implementation. It is not a verdict on fresh-reader host grammar, worker correction, maintenance, combined command, full verification, deployment or launch readiness.
