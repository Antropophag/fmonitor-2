# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v7

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v7`
- Test author: separately tasked agent `/root/assignment_original_red2`
- Reviewed commit: `dbcf83f76ae5b0edc0745c9630840b22f9c36215`
- GREEN-attempt null-default-gap base: `708a395b02b1e83e9b28153c19cde7b5b3a67022`
- Prior Gate 3 v6: approved before the newly observed MariaDB nullable-default representation gap
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v12, database setup only
- Public seams: `AssignmentOrderOriginalSchemaMigration::apply(mysqli, prefix)`, `AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA(mysqli, prefix)`, and `cleanupExampleA(mysqli, prefix)`
- Verdict: **APPROVED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
approvals, tests, support oracle, production code or RED evidence. This new
append-only review record is the only artifact added.

## Findings

### Nullable-default observation is narrowly canonicalized and sensitive

The column observer changes exactly one representation: when the live
`information_schema.COLUMNS` row reports exact `IS_NULLABLE === 'YES'` and raw
`COLUMN_DEFAULT === 'NULL'`, it exposes canonical PHP `null` to the independently
declared manifest. It does not normalize SQL NULL returned as PHP `null`, a
non-nullable column, an empty string, a differently cased string, or any
non-null default.

Before the intentional absent-migration guard, the real MariaDB preflight
creates one bounded probe table and observes three independent variants through
the same column observer used by the production migration matrix. An implicit
nullable default and explicit `DEFAULT NULL` both become canonical null. A
nullable `DEFAULT 'wrong'` remains a non-empty string distinct from both PHP
null and raw string `NULL`. The assertions therefore prove the compatibility
normalization needed by this supported MariaDB while retaining sensitivity to
a materially wrong default.

### Prior setup coverage remains intact

No previously approved assertion or support-oracle behavior was removed. The
live roots CHECK round trip and same-count wrong-regex mutation remain before
RED qualification. Beyond the guard, the test still covers the exact
seven-table manifest; ordered column metadata including charset, collation,
nullable/default and extra values; engine/table collation; keys and ordered
columns; foreign-key targets/actions; complete normalized CHECK sets and
counts; clean, repeat, compatible leading partial and populated migration
behavior; combined gross/near conflicts; opaque-identity collation/default
sensitivity; wrong same-count SHA-256 CHECK sensitivity; and pre/post snapshots
that prohibit partial DDL or row mutation on conflict.

Fixture coverage remains row-derived for order composition, case, opening,
task, checklist and decoy digests. It retains fictional credential-free users,
exact roles/grants/workforce/case/order/installers/task facts, zero original
facts, repeat idempotency, drift-conflict rollback, reverse bounded cleanup and
unrelated-state preservation. Seed and cleanup contention still demand a
server-observed `SERIALIZABLE` InnoDB lock wait from the exact child connection
to the exact parent connection before release, with bounded socket, observer,
transaction and child cleanup.

All five disposable databases, including the CHECK and default probe tables,
remain inside the independently bounded `t_aoou_<axis>_<12 hex>` namespace and
are dropped in the outer `finally`. An independent post-run
`information_schema` query found no owned database. The qualifying RED uses no
production system, secret, runtime consumer, private method or domain write.

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

$ independent information_schema query for SCHEMA_NAME LIKE 't\\_aoou\\_%'
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_CLEANUP_OK

$ git diff --check
PASS (no output)
```

The test necessarily passes the new live default matrix before reaching line
126. The captured failure is therefore the intended absent public migration
seam, not nullable-default observation, CHECK serialization, database setup,
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
3fa6ac18b0f78abfb2769a1eca577f5998166c2de6a9a36c36d2df7d4f248153  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
bff356f6cd34125e8e05207dd8899465a02aef85b837aefe6ef8257817fac191  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v6.md
8562dbc725c0391d36622b11d8c27951e1605969a5b95cf2734c0a8e24c24693  docs/operations/assignment-order-original-database-setup-green-attempt-null-default-gap-2026-09-05.md
ee5bef9e66cf25e62ac1f2e36d5330270cf1e92be051febe6b7aebf9275e1f71  docs/operations/assignment-order-original-database-setup-red-correction-v7-2026-09-05.md
```

The review path is metadata because a self-hash is circular.
