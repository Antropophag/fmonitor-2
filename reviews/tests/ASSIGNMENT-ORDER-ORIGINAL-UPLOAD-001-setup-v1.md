# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup review v1

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/assignment_dbsetup_gate3`
- Approved Gate 1 specification commit: `7192aa7cb177b1c7612086f7628c6ff82bd60a4c`
- Fresh Gate 1 review commit: `9144a2cb7f56928c2f70d0d790578709755d13a8`
- Technical owner approval commit: `b46c5e7c79ee2994bfc6e157096bfcdb3eb3807e`
- Reviewed RED commit: `21c1bcb16d9e9ee4c65b881e358521bd6c0b3062`
- Scope: OpenSpec setup tasks 2.2/2.3 only; no command implementation or later matrix
- Public seams: `AssignmentOrderOriginalSchemaMigration::apply`, `AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA` and `cleanupExampleA`
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
approval, tests, support oracle, production code, or RED evidence. This
append-only record is the only review artifact added.

## Findings

### G3-1 — exact CHECK and column semantics are not observable (blocking)

The approved v10 oracle makes normalized CHECK expressions, per-column
ASCII/`ascii_bin`, defaults and nullability part of structural equivalence and
explicitly treats extra or different members as conflict. The test reads only
the **number** of CHECK constraints (`checkCount`, test lines 54–57 and 113).
It never reads or independently compares their expressions. Its column oracle
reads only name, type and nullability (lines 37–40), omitting
`CHARACTER_SET_NAME`, `COLLATION_NAME` and expected defaults.

Consequently a migration can create the right count of constraints with a
wrong UUID regex, status/reason set, byte bound, count equation or opaque-ID
grammar, and can create opaque/hash columns with the database utf8mb4
collation instead of `ascii_bin`, while satisfying the clean-schema assertions.
The repeat snapshot cannot repair this: it proves only self-consistency of the
schema the same implementation just created, not equivalence to the approved
manifest. This does not meet exact-manifest traceability or plausible-regression
sensitivity.

Required correction: independently enumerate and normalize every approved
CHECK expression and every normative column charset/collation/default property,
then compare the real MariaDB schema to those literals. Add focused sensitivity
that would fail if at least one semantically material CHECK and one opaque
column collation/default were changed without changing their counts.

### G3-2 — migration conflict/equivalence sensitivity is too narrow (blocking)

The only incompatible-schema case creates one grossly wrong roots table and
expects one name (lines 143–149). It does not prove that `apply()` reports
**all** incompatible owned tables in binary order, nor that the presence of a
conflict alongside missing trailing tables performs zero DDL. It also does not
exercise a structurally close mismatch such as an extra key/CHECK, changed FK
action or changed CHECK expression—the defects most likely in an equivalence
implementation.

Required correction: stage at least two incompatible owned members in reverse
creation/name order plus a missing member, require the complete binary-sorted
logical-name list, and prove the full schema is byte-identical afterward. At
least one conflict must be a near-equivalent semantic/key/FK mismatch rather
than only a wrong one-column table. Preserve the valid clean, repeat,
leading-partial and populated cases already present.

### G3-3 — Example-A fixture literals and published digests are disconnected (blocking)

Lines 91–93 hash the six constants against their adjacent literal strings, but
no assertion derives those six projections from the rows actually produced by
`seedExampleA()`. The row checks omit, among other normative facts, user-role
assignments, the engineer's role/capability and position, full case/order audit
timestamps and snapshots, and the checklist/decoy projection rows. A fixture
that omits checklist and decoy entirely, or seeds values inconsistent with the
published projection JSON, can still pass. The generic before/after snapshot
only proves repeat and eventual restoration; it gives no independent expected
seeded value.

Required correction: assert every exact fixture-owned identity/value from v10,
including role assignments, engineer configuration, all specified snapshots
and timestamps, checklist and decoy. Independently construct the six canonical
JSON projections from those database facts (not from a production manifest or
future evidence-reader implementation), hash them, and compare with the six
approved literals/digests. Keep the existing zero-original-facts assertion.

### G3-4 — cleanup drift conflict and rollback are untested (blocking)

The test exercises drift only through `seedExampleA()` (lines 215–225), then
manually restores the row and calls successful cleanup (lines 226–233). It
never calls `cleanupExampleA()` while an owned row has drifted. Therefore an
implementation that deletes altered rows instead of byte-validating all owned
rows and rolling back before deletion can pass. The required fixed exception
shape is likewise untested on cleanup.

Required correction: drift an owned fixture row, snapshot the whole database,
call `cleanupExampleA()`, require the fixed
`AssignmentOrderOriginalVerificationFixtureConflict`, and prove zero DML.
Then restore/reseed and retain successful reverse-dependency cleanup plus
idempotent repeat. The correction should also make the all-or-nothing
`SERIALIZABLE` seed/cleanup requirement observably sensitive (for example with
a bounded competing connection at an exact owned identity), rather than only
assuming transaction isolation from a single-connection happy path.

## Passing properties retained

- The test cites the correct v10 executable specification and calls only the
  approved public migration/fixture seams; it does not inspect planned private
  implementation classes or load a production schema manifest.
- Real MariaDB setup is live before the intentional failure. Five bounded,
  random task-owned databases are created, and cleanup is attempted in
  `finally`; the RED is the absent approved migration class, not a connection,
  prerequisite, syntax or hash setup failure.
- The present oracle already covers clean/repeat, one compatible leading
  partial, populated root/revision preservation, one incompatible conflict,
  seed repeat, seed drift rollback, no original facts, successful bounded
  cleanup and cleanup repeat. Those useful cases should remain.
- The only test-owned DDL is the approved leading roots member. Application,
  HTTP, worker and evidence-reader runtime paths are not used to migrate or
  seed state.

## Reproduced evidence

```text
$ php -l tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ git diff --check
PASS (no output before this review)
```

The intended RED is reproduced, but Gate 3 does not advance because the test
would not catch the plausible schema-equivalence, fixture-projection and
cleanup-drift regressions above. Task 3.1 MUST NOT begin from these exact test
bytes. Corrected test/support/evidence require a fresh independent Gate 3.

## Exact reviewed SHA-256 inputs

```text
62b42d5b957dd628d09c13d6864152401c54998a5607b1c1835cc8a93ab9c3dd  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
0208895b4a605381ece9cc0bba4cee49ac79c1b17ffa1939f62601c05144051f  openspec/changes/replace-pilot-registration-with-original-upload/design.md
a061abc535528436d3caaadd0f34e7618793fd3ae76f5e7ccb16fd40fbbf43b5  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
8e533ff36104d6b1b01e4deaf0a695a938c5ff90e78d4c0d861ac736e1edb06d  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
d821da023aeeb6228928e8c2dfec446a23850102abe6938f9f07f4f808a6b487  docs/operations/pilot-assignment-order-original-database-setup-gate1-rereview-v10-2026-09-04.md
f926588401abd7f2bab9655b3072b75c0f06ea07d76321f50a4e6fe6a2b623d6  docs/operations/assignment-order-original-database-setup-technical-approval-2026-09-04.md
72d4252762912d5c26dfb35ec0008f4d74a2e9e477ec89a601cbf8ee077ef400  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
35afcdd180441a2bf3631c6715dac225135df5bdc25011f31abfe319ef5c69ee  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
c5174e5e109bd13104aa1085be1e6b975f2ccaef301ac4006714a644f37e301c  docs/operations/assignment-order-original-database-setup-red-2026-09-04.md
```

The review record path is metadata because a self-hash is circular. Any change
to reviewed test/support bytes requires a new independent review.
