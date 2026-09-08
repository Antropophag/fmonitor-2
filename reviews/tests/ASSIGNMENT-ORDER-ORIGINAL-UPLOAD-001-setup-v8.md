# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v8

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v8`
- Test author: separately tasked agent `/root/assignment_original_red2`
- Reviewed commit: `656580a91fc15bce38ecd3ab1c08c2956ef769c3`
- GREEN-attempt CHECK-parentheses-gap base: `0acafccd80c697c309f41c09b321024381391e39`
- Prior Gate 3 v7: approved before the newly observed MariaDB revision-CHECK parentheses gap
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v12, database setup only
- Public seams: `AssignmentOrderOriginalSchemaMigration::apply(mysqli, prefix)`, `AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA(mysqli, prefix)`, and `cleanupExampleA(mysqli, prefix)`
- Verdict: **APPROVED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
approvals, tests, support oracle, production code or RED evidence. This new
append-only review record is the only artifact added.

## Findings

### Revision-lineage CHECK round trip is narrow and sensitive

Before the intentional absent-production-seam guard, the test independently
selects exactly one revision-lineage CHECK from the support Contract. It creates
a live MariaDB table using the exact approved boolean expression, observes the
server-round-tripped clause through the same table-scoped `CHECK_CONSTRAINTS`
observer used by the full migration matrix, and requires equality with the
Contract oracle. It then changes only `revision_number > 1` to
`revision_number >= 1`, creates a second live table, and requires inequality.
Thus both compatibility and a plausible boundary regression are executable
against the real supported database rather than inferred from strings alone.

The new canonicalization is deliberately exact. After the previously reviewed
case/whitespace/backtick, SQL-regex and whole-wrapper handling, it declares the
full literal left and right operands of the approved revision expression. It
rewrites only `left OR right` or `(left) OR (right)` to the already approved
Contract representation `((left) OR (right))`. It does not tokenize, reorder,
or generally simplify boolean expressions.

An independent direct matrix confirmed that bare, operand-parenthesized and
whole-wrapper approved forms compare equal, while each of `>=`, `AND`, swapped
branches, a changed left event literal, and changed right nullability remains
distinct. The live same-shape `>=` mutation in the qualifying test independently
protects the most material boundary at the MariaDB observation seam.

### Prior setup coverage and isolation remain intact

No previously approved assertion or support-oracle behavior was removed. The
roots CHECK SQL-literal round trip and same-count wrong-regex mutation remain;
nullable implicit/explicit NULL normalization and a non-null wrong default
remain sensitive. Beyond the missing-class guard, the test still covers the
exact seven-table manifest; ordered columns, charset/collation/default/extra;
engine and table collation; keys and ordered columns; foreign-key targets and
actions; every normalized CHECK and count; clean, repeat, compatible leading
partial and populated migration behavior; gross and near conflicts; opaque
identity collation/default sensitivity; wrong same-count SHA-256 CHECK
sensitivity; and pre/post snapshots proving conflict paths do not partially
mutate schema or rows.

Fixture coverage still derives hashes independently for fixed fictional
credential-free users, roles, active grants, capabilities, workforce,
case/order/composition, opening, task, checklist and decoy facts. It retains
zero-original-fact checks, repeat idempotency, drift-conflict rollback, reverse
bounded cleanup and unrelated-state preservation. Seed and cleanup contention
still require a server-observed `SERIALIZABLE` InnoDB wait from the exact child
connection to the exact parent connection, with bounded process, socket,
observer, transaction and child cleanup.

All disposable databases remain inside the independently bounded
`t_aoou_<axis>_<12 hex>` namespace and are dropped in the outer `finally`. The
independent post-run query found no owned schema. The RED accesses no production
system or secret, fabricates no original facts, calls no private method, and
introduces no runtime consumer or domain mutation.

## Reproduced real-MariaDB RED and cleanup

Against MariaDB at `127.0.0.1:23306`:

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

$ independent canonicalization matrix
bare EQUAL
operands-parenthesized EQUAL
whole-wrapper EQUAL
gte DISTINCT
and DISTINCT
swapped DISTINCT
left-event DISTINCT
right-field DISTINCT

$ independent information_schema query for SCHEMA_NAME LIKE 't\\_aoou\\_%'
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_CLEANUP_OK

$ git diff --check
PASS (no output)
```

The test necessarily passes both new live revision tables before reaching the
missing migration guard at line 135. The observed RED is therefore the intended
absent public migration seam, not boolean serialization, database setup,
fixture setup or cleanup. Gate 3 authorizes task 3.1 minimal setup
implementation without changing the approved expectations.

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
8561b072a0d0c6e9659ca8c8765d0fe0d6be9e658018e0f2bd63c43b36dbc1b2  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
5a7b64994ba47367fde9bf440eb7a64ee858780788a337e7ab10e156e70fa671  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v7.md
d7aecc4523d642d44e9139e321345cdeb2b436d8711e7e27a8f9a2a53489e900  docs/operations/assignment-order-original-database-setup-green-attempt-check-parentheses-gap-2026-09-05.md
177467ff33ab146721f809b516232e97d99e252848d1b55e9c7eab75021d4444  docs/operations/assignment-order-original-database-setup-red-correction-v8-2026-09-05.md
```

The review path is metadata because a self-hash is circular.
