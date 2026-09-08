# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v5

- Date: `2026-09-04`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v5`
- Reviewed commit: `02fde78f17ebc9aa2d8de4db45fe502db7a5662c`
- GREEN-attempt gap commit: `acc2931c0c6e414b8d0b9bb77a1881f10559ec65`
- Prior Gate 3 v4 commit: `b23e092d1339cf096c233b5af38f641c62bc0546`, verdict `APPROVED` before the newly observed oracle gap
- Scope: OpenSpec database-setup tasks 2.2/2.3 only
- Verdict: **APPROVED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
approvals, test, support oracle, production code or RED evidence. This new
append-only review record is the only artifact added.

## Findings

### GREEN-attempt observer gap is closed

The corrected CHECK reader queries `information_schema.CHECK_CONSTRAINTS`
directly with exact `CONSTRAINT_SCHEMA=DATABASE()` and exact `TABLE_NAME`.
It no longer joins `CHECK_CONSTRAINTS` to `TABLE_CONSTRAINTS` by reusable
generated constraint name. An independent live-MariaDB probe created tables
`a` and `b`; both received `CONSTRAINT_1`, while the corrected table-scoped
predicate for `a` returned only `` `x` > 0 `` and did not attribute `b`'s
`` `y` < 10 `` expression to it.

The same exact table-owned identity is used to find the roots hash CHECK for
the same-count wrong-expression sensitivity mutation. The resolved generated
name remains separately restricted to a safe 1–64 byte identifier before its
only dynamic DDL use.

After case, whitespace and backtick normalization, the new regular expression
rewrites only MariaDB's canonical `!(value REGEXP 'pattern')` shape to the
approved equivalent `value NOT REGEXP 'pattern'`. It neither rewrites positive
`REGEXP` nor changes the operand or quoted pattern. The pre-RED controls prove
that the approved control-character pattern normalizes exactly and that the
materially weakened pattern without the backslash does not compare equal.
The existing same-count uppercase-only SHA-256 CHECK mutation remains an
independent end-to-end sensitivity assertion once the production seam exists.

### Traceability, coverage and isolation remain intact

The test still cites `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v12 and reaches the
approved public version-1 migration and verification-fixture seams only after
live MariaDB preflight, independently hashed literal projections, normalizer
sensitivity and process-control preflight. The qualifying RED is therefore the
missing `AssignmentOrderOriginalSchemaMigration` seam, not broken setup.

No previously approved assertions were removed. Coverage still includes the
exact seven-table manifest; table engine/collation; ordered columns, defaults
and extras; PK/unique/secondary keys; FK columns/actions; exact normalized
CHECK sets and counts; clean/repeat/leading-partial/populated preservation;
multi-conflict enumeration; opaque-ID collation/default and wrong-CHECK
sensitivity; and full pre/post schema-plus-row snapshots proving zero mutation
on conflicts.

Fixture coverage still checks independently hashed fixed Example-A users,
roles, active assignments, absence of credentials, exact capabilities,
case/order/composition/workforce/task/checklist/decoy projections, zero
original facts, seed and cleanup idempotency, conflict rollback, reverse
cleanup, and unrelated-decoy preservation. Seed and cleanup contention still
require a server-observed `SERIALIZABLE` InnoDB lock wait from the exact child
connection to the exact parent connection before releasing the held identity
row; sockets, observer connection, transaction and child process retain bounded
attempt-always cleanup.

Every created database uses the independently validated task-owned
`t_aoou_<axis>_<12 hex>` namespace and is dropped in reverse order in the outer
`finally`. No runtime consumer, production mutation, private method, production
secret, original-fact fabrication or domain logic enters the test.

## Reproduced real-MariaDB RED and cleanup

Against the disposable MariaDB exposed at `127.0.0.1:23306`:

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

$ independent information_schema query for SCHEMA_NAME LIKE 't_aoou_%'
<no rows>
exit 0

$ independent reused-name probe
a  CONSTRAINT_1  `x` > 0
b  CONSTRAINT_1  `y` < 10
table-scoped a: `x` > 0
probe database absent after cleanup

$ git diff --check
PASS (no output)
```

This is the intended missing public migration behavior. Gate 3 authorizes the
minimal setup implementation in task 3.1 without changing the approved test
expectations.

## Required changes

None.

## Exact reviewed SHA-256 inputs

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
f4851a33f5bf56c6797c2586791798d8f16d5cfa347162fe84a5b0915cd93a9d  openspec/changes/replace-pilot-registration-with-original-upload/design.md
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
9e2c354069c3b62f5d65eea37dd0417b6bbd8ef78d804f870882beda537dbbc1  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
460c1aeb99cb13575e4a8870bee5afd502de75023987bae20ee1600c5361f297  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
95e6228d9423f245ad870a2a26098fdab3faaafe404dc3282ec28e4798f5abf0  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
f0c52e0bf6de41ff2a64656cd27f92ad199a85088ac53df1b9a5ecb5af380549  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v4.md
c01affec62a9046bf786ea26984e6c1d9a95b2f0947f4f75de21d64b4b1c0863  docs/operations/assignment-order-original-database-setup-red-correction-v4-2026-09-04.md
c7538c01ccda072604358af8e922c514ed41b3d6d575ed216a4ec94437950628  docs/operations/assignment-order-original-database-setup-green-attempt-test-gap-2026-09-04.md
ee701f7a33d6a4a275e10b2968d36e38b3d7299b7ee7712a6e09d73102d71a5d  docs/operations/assignment-order-original-database-setup-red-correction-v5-2026-09-04.md
```

The review path is metadata because a self-hash is circular.
