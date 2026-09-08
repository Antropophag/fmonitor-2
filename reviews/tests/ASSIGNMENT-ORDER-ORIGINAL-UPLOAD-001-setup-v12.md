# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v12

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v12`
- Test author: separately tasked agent `/root/assignment_original_red2`
- Reviewed commit: `02721e98b4c9e99447597c603f330dba39966f15`
- Prior review: `a31b0a11247563c691c5a72fd4b027a746ead9fd`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v12, database setup only
- Public seams: `AssignmentOrderOriginalSchemaMigration::apply(mysqli, prefix)`,
  `AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA(mysqli,
  prefix)`, and `cleanupExampleA(mysqli, prefix)`
- Verdict: **APPROVED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
approvals, tests, support oracle, production code or RED evidence. This new
append-only review record is the only repository artifact added.

## Prior blocking finding resolved

Commit `02721e98b4c9e99447597c603f330dba39966f15` adds exactly one pre-RED
control and its append-only evidence record. The constructed input
`"reason_code='dangling" . "\\"` has an open quoted SQL literal and exactly one
terminal backslash byte. It is passed through the real `$normalizeCheck`
closure and requires the fixed fail-closed result
`CHECK_NORMALIZATION_FAILURE: invalid lexical structure.`

The normal focused run proceeds past this assertion to the intended missing
production seam at line 187. In a detached exact-commit worktree, changing only
the dangling-branch exception message to
`CHECK_NORMALIZATION_FAILURE: dangling mutation.` made the test fail at the new
assertion on line 160 with exact expected/actual diagnostics. This proves the
control executes the distinct terminal-backslash branch and is sensitive to its
observable result; the mutation did not reach the missing-seam guard. The
detached worktree was removed after the probe and no reviewed repository byte
was changed.

The earlier valid doubled-quote and backslash-escaped-quote controls remain, as
do unmatched opening and closing parentheses, unterminated quote, semicolon and
unsupported-XOR controls. The lexical validator remains centralized before AST
construction for every synthetic and real-MariaDB CHECK normalization path.

## Coverage and independence

No prior setup assertion was removed. Before the intentional missing-seam RED,
the test still executes real MariaDB connectivity, roots CHECK round trip and
same-count regex mutation, nullable-default canonicalization, boolean
parenthesis/operator mutation, complete CHECK-set round trips for all seven
owned tables, request-literal/audit-grouping/maintenance-accounting mutations,
and FK leading-index validation.

The retained post-seam source assertions independently specify exact seven-table
names, ordered columns, keys, foreign keys/actions, CHECK contents and counts,
engine/collation, clean/repeat/compatible-leading-partial/populated/conflict
migration behavior, zero-DDL conflict snapshots, deterministic fictional
fixture projections including `checklistSha256`, zero original facts, exact
repeat, drift conflicts, reverse byte-validated cleanup, unrelated-state
preservation, and bounded `SERIALIZABLE` InnoDB seed/cleanup contention. Those
paths correctly remain behind the missing production seam for this Gate 2 RED;
minimal GREEN is required to execute them.

Expected values remain in the separately versioned support oracle and are not
derived from planned production implementation. Database names are
randomized and regex-bounded, all five owned databases are dropped in `finally`,
and an independent post-run `information_schema` query found zero schemas whose
names begin with `t_aoou_`, after both the normal and mutation runs.

## Reproduced evidence

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

$ detached exact-commit mutation: dangling branch returns `dangling mutation`
Fatal error: Uncaught TestFailure: Quoted literal ending in one backslash reaches fixed lexical failure.
Expected: 'CHECK_NORMALIZATION_FAILURE: invalid lexical structure.'
Actual: 'CHECK_NORMALIZATION_FAILURE: dangling mutation.'
at tests/InstallationProcess/assignment_order_original_database_setup_001_test.php:160
exit 255

$ independent information_schema query for SCHEMA_NAME prefix `t_aoou_`
NO_AOOU_DATABASE_LEAKS

$ git diff --check
PASS (no output)
```

The live RED is caused by the absent approved public migration seam, not broken
MariaDB setup, CHECK normalization, fixture setup or cleanup. Gate 3 authorizes
task 3.1 minimal setup implementation against this exact reviewed test and
support oracle. Any test or oracle change restarts Gate 2 and requires a new
independent Gate 3 review.

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
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
98c2ad61488f539de62ef1de60992568a0203edcec18eec82459f657cd5859b7  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
37da4aa579d9c7e216bd6ea923f4b74b1084df643927765d97a68b12395c881e  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v11.md
37cf186ca9dc4707d471c76022b79cb353817e33c8f6e5a4e7e9102217b92503  docs/operations/assignment-order-original-database-setup-red-correction-v12-2026-09-05.md
```

The review path is metadata because a self-hash is circular.
