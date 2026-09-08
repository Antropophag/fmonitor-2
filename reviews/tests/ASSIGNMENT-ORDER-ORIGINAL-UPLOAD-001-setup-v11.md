# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v11

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v11`
- Test author: separately tasked agent `/root/assignment_original_red2`
- Reviewed commit: `73f5199c10eefe5693f31ea018d06fdb016b8e0b`
- Prior parser-safety review: `798dfda2618d62d814f4ca4b1a74d3660785c36b`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v12, database setup only
- Public seams: `AssignmentOrderOriginalSchemaMigration::apply(mysqli, prefix)`,
  `AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA(mysqli,
  prefix)`, and `cleanupExampleA(mysqli, prefix)`
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
approvals, tests, support oracle, production code or RED evidence. This new
append-only review record is the only artifact added.

## Blocking finding

### Dangling backslash rejection is claimed but has no executable control

The corrected test has one centralized whole-input lexical validator and calls
it at the start of `normalizeCheck`, so every real-MariaDB and synthetic CHECK
normalization path passes through the validator. Inspection confirms that it:

- preserves doubled SQL quotes and backslash-escaped quote bytes;
- rejects negative parenthesis depth immediately and non-zero final depth;
- rejects an unterminated quote and a semicolon before AST construction; and
- leaves `XOR` to the bounded atom grammar's fixed unsupported outcome.

Executable controls cover both valid quote escape forms, unmatched opening and
closing parentheses, unterminated quote, semicolon, and XOR. They do **not**
exercise the validator's distinct dangling-backslash branch
`if ($i + 1 >= $length)`. The v11 RED evidence nevertheless claims that a
dangling escape is rejected. A regression deleting only that branch would keep
every current assertion green and would silently turn the malformed input into
the generic final unterminated-quote path (or be accepted after a related quote
state change). This fails Gate 3 sensitivity and does not independently prove
the claimed lexical boundary.

Add one pre-RED executable control whose quoted SQL literal ends with a single
backslash and require the fixed
`CHECK_NORMALIZATION_FAILURE: invalid lexical structure.` outcome. Retain all
current valid-escape and malformed controls, reproduce the live MariaDB RED,
and request a fresh independent Gate 3 rereview. Task 3.1 is not authorized by
this review.

## Coverage retained and independently checked

No previously reviewed setup coverage was removed. The focused run reaches the
absent public migration seam only after the real MariaDB CHECK preflight,
including complete CHECK sets for all seven owned tables. Exact sorted-set
comparison retains count/content sensitivity; mutation probes retain changed
revision operator, request literal, audit grouping/precedence, maintenance
accounting operand and regex detection. The FK leading-index controls and
column/default probes also run before the intended RED.

The unchanged remainder still covers exact seven-table names, ordered columns,
keys, FKs and actions, engine/collation, clean/repeat/compatible-leading-partial/
populated/conflict migration behavior, rollback snapshots, deterministic
fictional fixture projections, zero original facts, idempotency, drift conflict,
reverse byte-validated cleanup, unrelated-state preservation and bounded
`SERIALIZABLE` InnoDB contention for seed and cleanup. The intended missing-seam
guard precedes those post-implementation paths, as required for this Gate 2
slice; their retained source assertions remain reviewable but cannot yet execute
until minimal GREEN supplies the public seam.

## Reproduced live-MariaDB RED and cleanup

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

$ independent information_schema count for SCHEMA_NAME LIKE 't\\_aoou\\_%'
0

$ git diff --check
PASS (no output)
```

This is an independently reproduced intended RED rather than a MariaDB, schema,
fixture or cleanup failure. It does not cure the missing dangling-escape
sensitivity.

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
2a7f8ca5870995df3f3caa7cd3db281f0f9570e1e4503f5a7cc826b1832f0433  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
fddd780785ae250758754c0c9fc4da9ab15c108fe33d38a9316cc1f6c60e9bcc  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v10.md
d39fb56e1f5a7878e20d54ce6126fefc1dbd4e24df6d1f7bd933f380f1a91856  docs/operations/assignment-order-original-database-setup-red-correction-v11-2026-09-05.md
```

The review path is metadata because a self-hash is circular.
