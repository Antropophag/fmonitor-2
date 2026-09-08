# Code review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 schema v2 — correction

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/schema_v2_gate5_v2`
- Reviewed implementation: `e4ab2fc3ff3dd7a8612a7f3fd9afb0e604ab244f`
- Full production diff base: `d939546e54e86dcc559f80dd97c72e63c4a9992a`
- Original implementation: `4beb1b8bdffa11d6f945bbce6fb234fe24637104`
- Prior Gate 5: `9a8e288569edd042887a1aed81bde3864aa9300b`
- Corrective RED: `781f543687eaa467f0a0e89d1c17e67f30bbd6f7`
- Corrective Gate 3: `7b0894bfcc6ff1479e4e56538971f598f6ce1256`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Gate 1: `390f5629cd1a39da7d39b414b0ca6ad0419d3234` (approval record `54e7cb2`)
- Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the specification, executable tests nor reviewed
production implementation. The nine untracked partial command-slice files were
excluded and untouched.

## Blocking finding

### Capability normalization still changes quoted literals and fails open

The corrective classifier anchors the outer grammar and rejects the approved
`trailing_clause` regression. It nevertheless calls `strtolower()` after
removing punctuation from the complete `CHECK_CLAUSE`. This lowercases bytes
inside quoted capability literals. A predecessor containing uppercase literals,
for example `ASSIGNMENT_ORDER.PREPARE`, is therefore transformed into the exact
lowercase V4 value set and classified as V4.

That is not an exact capability CHECK. Capability identifiers are approved
exact strings, and the prior Gate 5 explicitly required restoration of the
previous quote-aware folding plus anchored whole-expression grammar. Accepting
this expression violates capability-last fail-closed behavior: the public
migration creates every original table and publishes V5 instead of returning a
capability conflict with zero DDL.

An independent isolated-MariaDB probe added only an ephemeral test variant in a
detached temporary worktree. It reproduced:

```text
uppercase_literals capability conflicts before original DDL with zero state change
Expected: [CONFLICT, ['fm2_process_user_capabilities'], [], true]
Actual:   [APPLIED, [seven original tables, 'fm2_process_user_capabilities'],
           [seven original tables], false]
exit 255
```

Required correction: use quote-aware normalization which folds SQL syntax only,
preserves every quoted literal byte, and retains the anchored exact
`capability IN (...)` grammar. Add an executable uppercase-literal regression;
because this test gap is discovered at Gate 5, it restarts at Gate 2 and needs a
fresh independent Gate 3 before another Gate 5.

## Confirmed corrections and non-blocking observations

- Clean revisions creation now names the exact non-unique
  `idx_aoou_revision_content` in the single CREATE statement. The rename helper
  and intermediate unnamed-index state are gone.
- The corrective trailing-clause test now conflicts before original DDL.
- The focused schema-v2 matrix remains green for clean/repeat, roots+v1 partial,
  populated v1 forward upgrade, same-content revisions, post-index-ALTER
  recovery, drift conflicts, exact affected ordering and capability-last
  publication.
- The approved setup regression is green. Architecture check passes on a clean
  detached worktree at the reviewed SHA; the shared worktree's nine untracked
  command files are outside this review and were not included in that result.

## Verification evidence

```text
$ git rev-parse HEAD
e4ab2fc3ff3dd7a8612a7f3fd9afb0e604ab244f

$ php -l app/InstallationProcess/AssignmentOrderOriginalSchemaMigrationEngineSchemaMigration.php
No syntax errors detected in app/InstallationProcess/AssignmentOrderOriginalSchemaMigrationEngineSchemaMigration.php

$ php -l app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
No syntax errors detected in app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php

$ php tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_V2_001_OK

$ php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK

$ php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK

$ make architecture-check  # clean detached worktree at e4ab2fc
ARCHITECTURE CHECK PASSED (7 rules)

$ git diff --check d939546..e4ab2fc
PASS (no output)
```

## Exact reviewed hashes

```text
72845e1fa73fed45dbb719d28538ea471ad202984ad7bb7cf633fa451d0d00c5  app/InstallationProcess/AssignmentOrderOriginalSchemaMigrationEngineSchemaMigration.php
064b2ffe51a1582d850eaed665232b84f267eec9027eb31ad523d05486d45bbc  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
34b4a20be0005e4236ee3cdb04ca6fd41537ca3608ecef41ac5e9d91f151ddf5  app/InstallationProcess/AssignmentOrderOriginalDefinitionSchemaMigration.php
445645987816fcaf3dd82c1798b29dff0daa3988ef5e252fc8a28085ee8598dd  app/AssignmentOrderOriginal/AssignmentOrderOriginalSchemaMigrationVerificationFactory.php
fe12a6fb38843943433677a13875a83c10f516ccde56a20d026e2f617b7b928b  tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
5a10b53462f379841f5f1429e43ca1d730bb901de37cd721b35e7969ab23776c  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
6a9953989ca2707d695c66a2c8c652e67985a33ed7aa8aba137478691796414a  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
f5e2fc8e477f108d7ced4130322cabae9ec8b5cd324e1998f597b57988be9f15  docs/operations/assignment-order-original-schema-v2-gate5-correction-green-2026-09-05.md
```

This append-only review intentionally omits its own circular hash.
