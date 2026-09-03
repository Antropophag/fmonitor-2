# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent worker-seed Gate 3 rereview v3

- Reviewer: `Codex agent /root/original_upload_worker_gate3` (fresh independent
  Gate 3 reviewer; did not author the specification, test, support runner, RED
  evidence, or production)
- Review timestamp: `2026-09-03T23:57:52Z`
- Reviewed commit: `7d4c9ab6ef23effead6c011218d9b8a5b290e858`
- Production base: `52295f2edf25b0dda63ca6a5bf3fe00be8747e04`
- Verdict: **APPROVED**

## Exact reviewed inputs

- Normative delta specification
  `openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md`:
  SHA-256 `127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2`
- Corrected MariaDB two-worker verifier
  `tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php`:
  SHA-256 `cf5da1f2b6b4ac9a25854ec16002454c2d0c711e156f10876723e92764cabce4`
- Unchanged five-FD worker runner
  `tests/Support/assignment_order_original_worker_runner.php`:
  SHA-256 `2a9cfc055ed2262e607a6d365bb7ee21b1a0f19595a02fb6f224d08ad97cd109`
- Corrected append-only RED evidence
  `docs/operations/assignment-order-original-upload-gate2-worker-seed-red-correction-2026-09-04.md`:
  SHA-256 `172047496e5188bf7d8ee266c5630c4a5e4ca8887dab38766fd2e359739c5214`
- Prior cumulative test review retained as history:
  `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-v2.md`

## Independent review

The correction is limited to fixture validity and RED reachability. Both exact
installer rows now provide the canonical v12 non-null
`employed_from_snapshot='2024-01-01'` and explicit nullable
`employed_to_snapshot=NULL`. These are independently fixed predecessor facts,
not expected values obtained from a future original-upload implementation.
They agree with the canonical `fm2_order_installers` schema and leave the
approved composition, commands and concurrency outcomes unchanged.

The three missing-production-type checks now execute only after the canonical
migration has reported schema version 12 and the complete identity,
authorization, case, order, installer, root, revision, terminal request,
fingerprint and event seed has succeeded. Therefore the observed failure names
the missing public production composition only after the predecessor fixture
is proven usable; it cannot conceal the former missing-column setup defect.

The diff from the stated production base does not alter the worker bootstrap
runner or the concurrency/protocol oracle. The verifier still allocates the
same five worker communication descriptors beyond stdin: stdout, stderr,
barrier input, READY output and result output. It still requires two exact
READY lines before either RELEASE, independently covers identical and different
two-worker outcomes, and preserves malformed-release, EOF and bounded-timeout
zero-commit cases. Expected status multisets and revision counts remain literal
specification-derived assertions. Random database/resource identities,
bounded waits and validated `finally` cleanup preserve determinism and
isolation.

## Reproduction

From a detached worktree at exact reviewed commit:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php

$ php -l tests/Support/assignment_order_original_worker_runner.php
No syntax errors detected in tests/Support/assignment_order_original_worker_runner.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_demo_local php tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
mysqli_sql_exception: Access denied for user 'root'@'172.29.0.1' (using password: YES)
exit 255
```

The wrong-credential attempt is a **SETUP FAILURE** and is not RED evidence.
With the test contour's correct credential:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
TestFailure: INTENDED_RED: predecessor seed complete; canonical production MariaDB/worker seam is missing: FMonitor2\AssignmentOrderOriginal\ProductionAssignmentOrderOriginalFactory
exit 255

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
exit 0

$ git diff --check 52295f2..7d4c9ab
exit 0 (no output)
```

No randomly named `t_aooc_*` database or `.verification-artifacts/aooc-*`
resource root remained after the reproduced intended RED.

## Decision

No blocking Gate 3 finding remains. The corrected fixture reaches the intended
missing `ProductionAssignmentOrderOriginalFactory` behavior only after the
canonical v12 migration and complete predecessor seed, while the reviewed
five-FD concurrency coverage is unchanged.

Fresh Gate 3 is **APPROVED** for the exact hashes above. Gate 4 may implement
only the missing production MariaDB/private-storage/worker composition needed
to satisfy this reviewed verifier without changing its expectations. Any
test, support-runner, specification, or RED-evidence byte change requires a new
Gate 2/3 cycle.
