# Test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 schema v2

- Reviewer: separately tasked agent `/root/assignment_schema_v2_gate3`
- Test author: separately tasked agent `/root/assignment_shared_content_gate1`
- Reviewed test commit: `0393c3c89d89ee4f196d75e6938ec90b25398755`
- Reviewed append-only hash correction: `3d1f9684f7d55869becaf6e8a835f82392a442fb`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Approved Gate 1 review: `390f5629cd1a39da7d39b414b0ca6ad0419d3234`
- Technical approval: `54e7cb2cbf6dc1ff2967b3161220a4e840ebe4db`
- Public seam: `AssignmentOrderOriginalSchemaMigration::apply()` and the
  observer-injected `AssignmentOrderOriginalSchemaMigrationApplication::apply()`
  returned only by `AssignmentOrderOriginalSchemaMigrationVerificationFactory`
- Red commands:
  `tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php`
  and
  `tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php`
- Intended failure: the public migration returns schema version `1` where the
  approved v54 contract and independent test oracle require exact version `2`
- Verdict: **APPROVED**

## Findings

No blocking finding remains.

### Traceability and seam choice

The focused verifier cites v54 and exercises only the static public migration
seam or its approved verification-factory application seam. It does not call a
private classifier, DDL builder or repository side channel. The broad setup
verifier exercises the same static public seam. The verification-only observer
is not reachable through production bootstrap, HTTP, environment, CLI or
global selection under the reviewed contract.

The reviewed matrix covers the approved v1-to-v2 amendment rather than changing
command behavior: exact clean v2 construction and physical
`idx_aoou_revision_content` non-unique index, exact repeat, roots plus v1
partial recovery with revisions at manifest position and capability last, full
populated v1 upgrade from an arbitrary safe index name, byte-identical row
preservation, and two revisions sharing one exact content identity. The broad
setup verifier separately preserves its established full schema, populated-v2
repeat, leading partial, multiple-conflict/binary-order, fixture and cleanup
coverage while changing its active expected manifest to v2.

### Rejected cases and failure recovery

The focused test independently constructs and rejects wrong v2 physical name,
unsafe v1 name, multiple content indexes, wrong-column index and mixed v1 plus
other-table drift. Each conflict compares a full schema-and-row snapshot before
and after the public call, so a classifier that performs repair DDL or row
rewrites before returning `CONFLICT` is observable.

The injected `AFTER_SCHEMA_V2_REVISION_INDEX_ALTER` fault proves the exact fixed
unavailable exception, logical-table observer argument and durable-v2 retry
without a duplicate ALTER. A second observer deliberately restores an exact v1
predecessor and proves that retry performs the one required reconciliation.
Together these cases distinguish both contract-permitted durable states after
the atomic ALTER boundary.

### Expected-value independence and sensitivity

Expected version, logical table order, exact physical index name, uniqueness,
conflict names and observer phase are test-owned literals/constants derived
from v54, not values queried from a planned v2 production implementation. The
historical v1 predecessor is constructed by changing only the independently
specified index relationship after the broad oracle has established the exact
schema. The row examples use fixed independent values and compare every fetched
field before and after migration.

The matrix would fail for plausible regressions including retaining schema
version 1, retaining UNIQUE content identity, using a differently named v2
index, accepting unsafe or ambiguous v1 candidates, upgrading after the suffix,
publishing capability early, rewriting rows, returning incomplete conflicts,
performing DDL on drift, omitting the post-ALTER observer, duplicating an index
on retry, or rejecting a second revision that references the same immutable
content identity.

### RED validity, determinism and isolation

Both RED wrappers were reproduced against live isolated MariaDB. They reached
the public migration successfully and failed on the first missing v2 observable
with exact `APPLIED` status and affected ordering already present; therefore the
failure is missing behavior, not setup, syntax or connectivity. Database names
are random, grammar-validated task-owned identities and cleanup drops only the
recorded created databases in `finally`. A separate post-run information-schema
query found zero matching schemas and zero matching connections.

The original RED evidence contained one stale pre-cleanup hash for the focused
test. Commit `3d1f9684f7d55869becaf6e8a835f82392a442fb` corrects it append-only to
the committed hash below and demonstrates that the removed redundant variable
assignment changed no input, assertion, branch, seam call or expected result.
This provenance issue is therefore resolved without rewriting history.

## Required changes

None.

## Verification evidence

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
Expected: status APPLIED, schemaVersion 2, seven manifest tables, capability last
Actual:   status APPLIED, schemaVersion 1, seven manifest tables, capability last
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
exit 0

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Expected: 2
Actual: 1
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ php -l tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ php -l tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ independent information_schema leak audit
schemas=0
connections=0

$ git diff --check
PASS (no output)
```

## Exact reviewed hashes

```text
b0a1e09b4c31305e039d5a8303a37e8b394f25abcee3d9b27149a2defb2c22aa  tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
6a9953989ca2707d695c66a2c8c652e67985a33ed7aa8aba137478691796414a  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
8c6feadaa89fc50003b0ec58be5613001c7ba034548498f67dad9e6f5c59a825  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
0e7f5fd84c6974b411a5f8a869fb270e663cae99fdb1017c15e585218dec8bd4  openspec/changes/replace-pilot-registration-with-original-upload/design.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
d0c6d6de568b2527a87653707c6095b13c13c00270bdced7f1fe394109d094f6  docs/operations/assignment-order-original-schema-v2-red-evidence-2026-09-05.md
06363c98d2034dd2d36d6cb40e2f668ab05351dbd747ca6084bc4b1d7e382827  docs/operations/assignment-order-original-schema-v2-red-evidence-hash-correction-2026-09-05.md
```

This review intentionally excludes the untracked partial command-production
files present in the shared worktree and omits its own circular hash.
