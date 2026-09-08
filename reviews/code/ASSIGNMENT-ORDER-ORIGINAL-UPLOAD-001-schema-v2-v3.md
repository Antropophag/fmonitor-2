# Code review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 schema v2 — quote-aware correction

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/schema_v2_gate5_v3`
- Reviewed implementation: `4c7df544a186cd0ee8b4e58f37b8868b7735789c`
- Full production diff base: `d939546e54e86dcc559f80dd97c72e63c4a9992a`
- Original implementation: `4beb1b8bdffa11d6f945bbce6fb234fe24637104`
- Corrected implementation before the quote-aware finding: `e4ab2fc3ff3dd7a8612a7f3fd9afb0e604ab244f`
- Prior Gate 5 findings: `9a8e288569edd042887a1aed81bde3864aa9300b`, `0e9c96a25d2f6533c6051c5041ae40aa77108e7e`
- Corrective RED and Gate 3: `595f9ed5374842f3708070d43c8176abda916235`, `c5c6c55`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Gate 1: `390f5629cd1a39da7d39b414b0ca6ad0419d3234` (approval record `54e7cb2`)
- Verdict: **APPROVED**

The reviewer authored neither the specification, executable tests nor production
implementation. The nine untracked command-slice files were excluded and
untouched.

## Findings

No blocking finding remains.

The complete production diff creates a missing revisions table directly with
the exact named non-unique `idx_aoou_revision_content`; clean creation therefore
has no intermediate unnamed index and no second revisions DDL. An exact v1
predecessor is classified only after the rest of its manifest matches, and its
validated safe-name unique index is replaced by one atomic DROP+ADD ALTER at the
revisions manifest position. The post-ALTER observer, exact v2 reread and retry
classification preserve the approved v1-or-v2 recovery boundary without row
rewrites or a second content index.

The capability classifier now applies case/whitespace/backtick/parenthesis
normalization only outside single-quoted values. Quoted literal bytes remain
unchanged, including doubled-quote handling, and the anchored whole expression
accepts only a sole top-level `capability IN (...)` candidate with the exact V4
or V5 set. Both trailing-clause and uppercase-literal regressions fail closed
before original-schema DDL. Capability publication remains the last mutation
and is reported only after a fresh exact V5 reread.

Family-wide preflight returns binary-sorted conflicts before mutation. The live
schema-v2 matrix proves clean/repeat, leading partial, populated v1 preservation,
same-content revisions, atomic-ALTER fault/retry paths, hostile index forms and
mixed drift. The capability suite proves exact V4/V5, rejected set and grammar
variants, duplicate/unsafe candidates, original+capability conflict union, and
observer recovery. The broad database-setup regression remains green.

## Verification evidence

```text
$ git rev-parse HEAD
4c7df544a186cd0ee8b4e58f37b8868b7735789c

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

$ make architecture-check  # clean detached worktree at reviewed SHA
ARCHITECTURE CHECK PASSED (7 rules)

$ git diff --check d939546..4c7df54
PASS (no output)
```

## Exact reviewed hashes

```text
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
919021360051836f9bbc7864e83a0273179b6abadf4c0888e13ada5d26a07c0d  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-schema-v2-quote-aware.md
34b4a20be0005e4236ee3cdb04ca6fd41537ca3608ecef41ac5e9d91f151ddf5  app/InstallationProcess/AssignmentOrderOriginalDefinitionSchemaMigration.php
064b2ffe51a1582d850eaed665232b84f267eec9027eb31ad523d05486d45bbc  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
0fdf3f4ef14a861ddf590ba229efcf2f497cf87114321f11023eff81cda3b212  app/InstallationProcess/AssignmentOrderOriginalSchemaMigrationEngineSchemaMigration.php
445645987816fcaf3dd82c1798b29dff0daa3988ef5e252fc8a28085ee8598dd  app/AssignmentOrderOriginal/AssignmentOrderOriginalSchemaMigrationVerificationFactory.php
fe12a6fb38843943433677a13875a83c10f516ccde56a20d026e2f617b7b928b  tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
e418c4cf9e2d37e6bf9da799f6bdfc5b2faa0f79e123b2be62810dd614b4cb85  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
6a9953989ca2707d695c66a2c8c652e67985a33ed7aa8aba137478691796414a  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
c205635b598e546a87dd7a4a615f3c45ba73df88b3039a86d252b6bfac4165b2  docs/operations/assignment-order-original-schema-v2-quote-aware-green-2026-09-05.md
```

This append-only review intentionally omits its own circular hash.
