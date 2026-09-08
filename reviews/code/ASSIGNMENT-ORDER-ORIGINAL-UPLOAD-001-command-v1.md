# Gate 5 code review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 command v1

- Дата: `2026-09-05`
- Reviewer: `Codex agent /root/original_command_gate5` (fresh independent Gate 5; не писал executable tests, production implementation или planning artifacts)
- Implementation author: `Codex agent /root/assignment_command_gate3`
- Reviewed implementation commit: `6c4fb5b70065cabb19adab23f6004714c1f0699a`
- Approved command Gate 3: `2907b4e7436b260c92c0c9aa4ef5e415728494cf`
- Canonical lease correction Gate 3: `c6938640dbac7f927e26123a1361274ea5d8ef74`; review-hash correction `75037bd4210561aac78b509c0c3e90b488c39dbd`
- Worker cleanup Gate 3: `455372a42fa6489aa2809e8b651618ed2cb4c14a`
- Schema-v2 Gate 5: `a54446f294e29362103e23d38c34823b06b5b1be`
- Verdict: `CHANGES_REQUESTED`

## Exact reviewed identities

```text
5d1ea6c691916b0d84427f7202caf078542ec4dce9a0468dd22e868af6b9f656  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
898381a7aff748fd8238b24efd8e7dd327a14fba2202c62cb7dd6db718d86f6c  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php
9025003a0fa62ba189a8b2ec07ac9bc6a9980e32fc97410515622f625abe56de  app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php
08906719e8a5c708df1d330559fba8c94ea0af70ab63a9bdf746d344c830112c  app/AssignmentOrderOriginal/MariaDbMaintenanceService.php
32aef5d7b051a8335a235cb6de10c137276c37055b4be9db60d1cc3348765366  app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php
64c1bfbed11df12ab6078e64ea5db71a970b5696403840375db945e528de86b8  tools/architecture/check.py
```

OpenSpec artifact identities at review time:

```text
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  proposal.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  specs/pilot/assignment-order-original/spec.md
0e7f5fd84c6974b411a5f8a869fb270e663cae99fdb1017c15e585218dec8bd4  design.md
6ace1c1e3029bf549cd2e0fd7127610e670285e48d5aed0a02ffdb679123ab5d  tasks.md
```

## Blocking findings

### 1. Private storage does not persist the original PDF bytes

`AssignmentOrderOriginalFileStage` keeps upload bytes only in the process-local
`$bytes` string. `finalize()` removes the stage metadata and appends only
`byteSize`, timestamp, opaque identity and digest to `.aoou-state.json`; it never
writes or atomically publishes a private content file. After the process exits,
the accepted DB revision points to bytes that do not exist. This violates the
immutable original evidence, private finalize, digest/size verification,
restart durability and maintenance/reuse requirements.

Independent reproduction against the reviewed production adapter:

```text
{"files":[".aoou-state.json",".aoou-state.lock",".lock-b27543ebedf0be5a0b672d6bdeb59e859f13796a23bb5a11c31168922b5723d6"],"persistedBytesFound":false}
```

The reproduction wrote marker bytes through `beginStage()->write()->finalize()`,
released the returned lease, enumerated every resulting regular file, and found
the marker in none of them. The temporary root was then removed.

### 2. The owned PDF inspector does not parse or validate the PDF structure

`FMonitorPassivePdfInspector::inspect()` checks substrings and a few regexes but
does not resolve `startxref`, xref tables/streams, `/Prev`, object identities,
object streams or the reachable page graph required by `fmonitor-passive-pdf-v1`.
It can therefore accept structurally corrupt content and cannot reliably detect
active keys after PDF name escaping/indirection/decompression.

Independent reproduction changed the approved passive-classic fixture to the
impossible out-of-file offset `startxref 99999999`; reviewed production returned:

```text
passive_pdf
```

This must be `INVALID_PDF`. Conversely, the implementation rejects every
literal `/Prev ` rather than parsing the bounded chain required by the approved
algorithm.

### 3. Initial lineage lookup is global instead of assignment-order scoped

The application calls `findLineage('')` for INITIAL. The MariaDB repository
implements the empty identity as `WHERE 1=1 LIMIT 1`. Consequently, once any
assignment order has one original, every different order/case is rejected as
`INITIAL_ALREADY_EXISTS`. The root schema correctly has
`UNIQUE(assignment_order_id)`, but the public application cannot query that
identity. This violates the one-original-per-assignment-order behavior and
makes the seam unusable for a second object.

### 4. Required fail-closed persistence and attempt-audit outcomes are lost

- authorization `UNAVAILABLE` is returned as `REJECTED/PERSISTENCE_FAILURE`
  with `retryable=true`, an impossible Result tuple; the contract requires a
  technical `FAILED/PERSISTENCE_FAILURE`;
- terminal-request and fingerprint lookup `UNAVAILABLE` are treated as misses,
  so the command continues into stream/storage/domain work instead of failing
  closed;
- `finish()` and `finishLeased()` ignore both a non-`COMMITTED`
  `commitAttempt()` status and exceptions, returning the original
  rejected/conflict result although the required terminal request plus audit
  was not saved atomically;
- `commitAccepted()` maps every `mysqli_sql_exception` to CAS `CONFLICT`, so
  transport/schema/check/constraint persistence errors can be misreported as a
  business concurrency result.

These defects violate the stable Result DTO and the explicit attempt-audit
atomicity requirement.

### 5. Composition validation and correction precedence are incomplete

The production composition reader records IDs only for included
`assign|retain` rows. Duplicate/excluded `release` rows are therefore not
detected across the complete row set, and release date rules
(`valid_to` required and `<= order_date`, complete date validity) are not
validated. The application also does not load correction lineage/composition
drift before stream read: it reads and stages the complete PDF first, then calls
`findLineage()`. This contradicts the exact all-rows validation and the
approved pre-stream semantic-collision precedence.

### 6. Verification barrier fabricates the post-finalize state

For `after_private_finalize_before_commit`, worker bootstrap directly inserts a
`finalized` metadata entry and acquires a manual lock before constructing the
application. It then binds a no-op lifecycle observer, so READY is not emitted
by the actual application event. This bypasses the production finalize path,
does not include persisted bytes, and cannot prove that the real upload lease
is held against maintenance. The verifier-only direct state mutation is also a
second mutation path for the very fact under observation.

### 7. Production construction and filesystem boundary are absent/fail-open

Only `AssignmentOrderOriginalVerificationFactory` constructs the command
application; no production factory binds the production authorizer,
composition reader, repository, inspector, private storage, clock, IDs and
safe logger as required. The file storage constructor creates any caller path
recursively without canonical-path, owner, mode, symlink or protected-chain
validation. Safe-log construction likewise reads/appends the supplied path
without the approved existing-owned-`0600` validation. The orphan fixture
factory ignores its marker token, production-root disjointness, config, clock
and faults. These are security and deployment blockers, not deferred HTTP
surface work.

## Architecture checker review

The two-line rule change is narrow: only files directly under
`app/AssignmentOrderOriginal/` whose basename begins `MariaDb` may own SQL.
It does not change the baseline and `compare()` still rejects new findings, so
the allowlist itself is not baselineable. `make architecture-check` passes.
However, a passing ratchet cannot compensate for the missing production factory
or the direct verification/storage mutations above.

## Independent verification

All nine approved focused suites passed on exact implementation SHA:

```text
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
ASSIGNMENT_ORDER_ORIGINAL_EVIDENCE_READER_OK
ASSIGNMENT_ORDER_ORIGINAL_MAINTENANCE_OK
ASSIGNMENT_ORDER_ORIGINAL_WORKER_TRANSPORT_OK
ASSIGNMENT_ORDER_ORIGINAL_WORKER_PROTOCOL_OK
ASSIGNMENT_ORDER_ORIGINAL_LEASE_RACE_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK
```

Related verification also passed:

```text
ARCHITECTURE CHECK PASSED (7 rules)
make unit-test: exit 0
ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_V2_001_OK
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK
make lint: exit 0
git diff --check 455372a..6c4fb5b: exit 0
```

The green matrix does not detect the corrupt-`startxref`, missing-byte,
second-order, full-row composition, lookup-unavailable, audit-commit or real
post-finalize-observer regressions above. Gate 5 therefore cannot infer
conformance from those GREEN results.

## Required changes

1. Persist and atomically publish actual immutable PDF bytes in validated
   private storage, with digest/size verification, restart durability and the
   shared upload/maintenance exclusion domain.
2. Implement the approved bounded structural PDF algorithm and add a new RED /
   independent Gate 3 correction for corrupt xref offsets plus active-content
   evasion cases before changing parser behavior.
3. Scope INITIAL lookup to the exact assignment order and make a second order
   independently acceptable; add corrected RED/Gate 3 because the approved
   matrix is not sensitive to this regression.
4. Enforce exact FAILED/retry/result and atomic request/audit behavior for every
   unavailable/commit failure path; distinguish CAS conflicts from technical DB
   failures.
5. Validate every composition row and move correction drift/lineage precedence
   before stream access as specified.
6. Drive both worker barrier events from the real application lifecycle and
   remove verifier fabrication of finalized facts/locks.
7. Add the approved production construction seam and fail-closed canonical
   filesystem/safe-log/orphan-fixture validation.
8. Re-run any affected executable tests through RED and fresh independent Gate
   3, then obtain a fresh independent Gate 5 on the corrected exact SHA.

Until these blockers are corrected, tasks 6.1/6.2 MUST remain incomplete and
the command slice is not deployable or approved.
