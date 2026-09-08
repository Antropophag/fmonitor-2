# INSPECTION-ITEM-COMPLETE-001 — independent first-revision MariaDB Gate 3 review

Date: 2026-09-07  
Reviewer: `/root/architecture_diagnosis` (independently tasked; did not author
the test, runtime fix, specification or RED evidence)  
Verdict: **APPROVED**

## Exact reviewed artifacts

- Base repository commit: `8b9b2d7fca669b9e47efb3ac1e097ba978ebe224`.
- Reviewed test SHA-256:
  `8f115e716291a731f79165fb378260a3fbde34b0b63bc993ccf309cccf44f5d8`.
- Normative source: `specs/INSPECTION-ITEM-COMPLETE-001.md`, especially
  Idempotency and concurrency and Example F.
- Prior approved MariaDB test review:
  `reviews/tests/INSPECTION-ITEM-COMPLETE-001-mariadb-v5.md`.

This verdict applies only to the exact reviewed test bytes. Any test expectation,
fixture, orchestration or cleanup change requires fresh review.

## Traceability and seam

The change preserves the previously approved existing-revision overlap scenario
and adds the missing-revision form of the same specification rule: two distinct
valid commands with `expectedRevision=0` for one installation case must serialize
so exactly one returns `ACCEPTED(1)` and the other `STALE_REVISION(1)`.

Both workers still construct the application through
`ProductionInspectionEvidenceFactory` using separate DML-only MariaDB connections
and invoke only the public `InspectionRecording::completeItem` command seam.
Business results and immutable winner evidence are observed only through
`completeItem` and public `InspectionEvidenceView::getItemCompletion`. Direct SQL
remains limited to canonical migration, fixtures, deterministic lock/PROCESSLIST
coordination, catalogue/decoy checks and cleanup; it is not a business oracle.

## Sensitivity and expected-value independence

Default invocation now starts both `--existing-revision` and `--missing-revision`
children and requires exit status 0 from each. The existing mode retains its exact
revision-row `FOR UPDATE` barrier. The new mode omits the revision row and holds the
installation-case row for the same case before releasing workers. It then requires
both published worker connection IDs to remain simultaneously visible for 300 ms on
the exact prepared/literal case-lock queries. This would fail if the missing-row
path did not acquire the case serialization lock; the captured pre-fix RED was:

```text
SETUP_FAILURE: both exact worker case-lock queries continuously visible in PROCESSLIST for 300ms.
Expected: true
Actual: false
```

After release, both modes share the unchanged exact public oracle:

- unordered results canonicalize to `ACCEPTED(1), STALE_REVISION(1)`;
- exactly one operation has complete literal evidence and the loser is `null`;
- exact replay is `DUPLICATE(1)` without consulting the clock;
- changed payload is `OPERATION_PAYLOAD_CONFLICT(1)` without changing evidence;
- the canonical table catalogue and decoy remain unchanged.

The expected values remain specification literals. The test does not derive them
from the implementation, SQL rows or the winner response. Parameterizing fixture
seeding only controls whether revision zero exists; it does not weaken the prior
existing-row assertions.

## Determinism and cleanup

Each mode creates its own unique database, prefix, runtime DML-only user and artifact
directory because the token includes the child PID and high-resolution time. Workers
publish distinct connection IDs before the coordinated start. Every wait is bounded.
The shared `finally` path rolls back/closes the coordinator, terminates and reaps owned
workers, closes connections, drops the runtime user and database, removes all known
artifacts, and independently asserts database/user/artifact/process absence. The parent
also bounds and reaps each mode process. A cleanup failure supersedes apparent success
and preserves the primary failure as diagnostics.

## Independently executed evidence

Using the repository-local disposable test database only:

```text
php -l tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
No syntax errors detected in tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php

PATH=/opt/homebrew/bin:$PATH \
FMONITOR_TEST_DB_ADMIN_PASSWORD=<repo-local-test-password> \
php tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
PASS: INSPECTION-ITEM-COMPLETE-001 existing and missing revision concurrency
```

The command exited 0. `git diff --check` passed for the reviewed file. No global
database reset, stand mutation, production data access or external action was run.

## Gate decision

Gate 3 is **APPROVED** for the exact test hash above. The missing-revision mode is
traceable to the existing per-installation-case concurrency contract, detects the
first-revision race through a deterministic real-overlap barrier, preserves all prior
public outcome and persistence assertions, runs by default, and retains bounded,
self-verifying cleanup. Runtime conformance and code quality remain a separate Gate 5
review owned by another reviewer.
