# Independent Gate 3 rereview — ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 maintenance v3

- Reviewer: separately tasked agent `/root/original_upload_maintenance_gate3`
- RED author: separately tasked agent `/root/original_upload_red`
- Reviewed correction commit: `32211154b79511d2066fce4f92fdb33f92e3cf87`
- Exact pre-maintenance base: `c46c4f382fc4c6ef84fb57c2b35cd343a9717af7`
- Branch/upstream at review: `codex/original-upload-maintenance-red` / `origin/codex/original-upload-maintenance-red`, both at the reviewed commit
- Prior full Gate 3 record: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-v2.md`
- Corrected Gate 2 evidence: `docs/operations/assignment-order-original-upload-gate2-maintenance-red-correction-v2-2026-09-04.md`
- Verdict: **APPROVED**

## Independence and scope

The reviewer did not author or edit the specification, OpenSpec artifacts,
test, support fixture, RED evidence, or production code. This append-only review
record is the reviewer's only change. The rereview is limited to the maintenance
cutoff-precedence correction introduced by the reviewed commit; all other test
inventory retains the prior Gate 3 history.

The owner-approved normative hashes remain exact:

```text
97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
```

The reviewed executable artifacts are:

```text
a6257ce890064da9aee9fb2828e440a689d4acb2508da06ad936aa04714929b8  tests/InstallationProcess/assignment_order_original_upload_001_maintenance_test.php
95edf94fa25e8669a8413c08739eea205b19a881dc9c1306d915703a69f1c5c0  tests/Support/InMemoryAssignmentOrderOriginalMaintenanceEnvironment.php
```

`git diff c46c4f382fc4c6ef84fb57c2b35cd343a9717af7..32211154b79511d2066fce4f92fdb33f92e3cf87`
contains only the append-only correction evidence and the maintenance-test
oracle change. Production, specification, OpenSpec and support-fixture bytes are
unchanged.

## Traceability, precedence and sensitivity

The v4 executable specification defines maintenance order as scalar shape,
exact string-principal authorization, terminal request lookup, then
clock/cutoff validation. The correction now represents both sides precisely:

- malformed UUID, batch limits `0` and `1001`, and noncanonical cursor remain
  scalar-invalid and require the complete dependency trace to be empty;
- cutoff `2026-09-02T08:15:31Z` is independently one second newer than the
  allowed boundary for the fixture clock `2026-09-02T09:15:30Z` and requires
  exact calls `authorize`, terminal `request` lookup, then `clock`;
- the exact complete call-list assertion excludes every later candidate-page,
  digest-lock, reference, delete, unlock and result/audit-commit call;
- the result oracle independently requires non-retryable
  `REJECTED/INVALID_COMMAND`, zero counts and null cursor.

This is sensitive in both directions. Treating a young cutoff as scalar shape
would add an expected authorization/request/clock trace and fail; moving cutoff
validation after candidate enumeration or commit would add calls and fail.
Conversely, performing any dependency call for the four true scalar-invalid
commands violates their exact empty traces. Expected timestamps and call names
come from the approved specification and fixed fixture, not from a planned
implementation.

## Independent RED reproduction

At `2026-09-04T02:51:51+03:00`, in a dedicated worktree under the user's home
directory at exact reviewed commit
`32211154b79511d2066fce4f92fdb33f92e3cf87`:

```text
$ php -l tests/Support/InMemoryAssignmentOrderOriginalMaintenanceEnvironment.php
No syntax errors detected in tests/Support/InMemoryAssignmentOrderOriginalMaintenanceEnvironment.php

$ php -l tests/InstallationProcess/assignment_order_original_upload_001_maintenance_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_maintenance_test.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_maintenance_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical maintenance public seam is missing: FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceApplication
exit 255

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (exit 0, no output)
```

The explicit canonical-public-seam guard runs before the support fixture is
loaded or any dependency is called. PHP parsing succeeds, so the observed
failure is the intended missing maintenance behavior, not broken setup.

## Findings and Gate decision

No blocking or non-blocking finding remains. Fresh independent Gate 3 for the
maintenance cutoff correction at exact test hash
`a6257ce890064da9aee9fb2828e440a689d4acb2508da06ad936aa04714929b8`
is **APPROVED**. Gate 4 may implement only the reviewed maintenance contract
without changing this oracle. Any test, fixture or normative expectation change
requires a fresh Gate 2/3 cycle.
