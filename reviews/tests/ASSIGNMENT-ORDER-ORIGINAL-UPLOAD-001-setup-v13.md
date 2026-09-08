# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v13

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v13`
- Test author: separately tasked agent `/root/assignment_original_red2`
- Reviewed commit: `6b054f7a54427a43b07193af20a92ba5fe3fdd02`
- Quoted-case gap base: `71acf6595d05f90b5859a5a7980d6eac03a56c97`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v12, database setup only
- Public seams: `AssignmentOrderOriginalSchemaMigration::apply(mysqli, prefix)`,
  `AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA(mysqli,
  prefix)`, and `cleanupExampleA(mysqli, prefix)`
- Verdict: **APPROVED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
tests, support oracle, production code, or RED evidence. This append-only review
record is the only repository artifact added.

## Quoted-case gap resolved

The CHECK canonicalizer now performs ASCII case folding with a byte scanner
that enters and leaves SQL single-quoted literals explicitly. Outside a literal,
mixed-case identifiers and keywords fold to the same canonical form. Inside a
literal, bytes are appended unchanged; a backslash consumes and preserves its
following byte, and a doubled single quote consumes and preserves both quote
bytes. The existing centralized lexical validation executes first and rejects
an unterminated literal, dangling quoted backslash, unmatched parentheses, and
semicolon before AST construction.

Three pre-seam controls make the corrected boundary executable. Mixed-case
`StAtUs IN` normalizes to the approved lowercase unquoted representation, while
`'accepted'` versus `'ACCEPTED'` and lower-hex versus upper-hex regex literals
remain unequal. In a detached exact-commit worktree I mutated the quoted branch
to fold ASCII bytes too; the test then failed at line 165 with
`Quoted status literal case remains byte-sensitive`, before the absent-seam
guard. The detached worktree was removed. Thus a return to whole-expression
case folding cannot pass by merely reaching the intended RED.

The post-seam migration matrix retains the real same-count mutation that drops
the roots composition-hash CHECK and replaces it with
`composition_sha256 REGEXP '^[0-9A-F]{64}$'`. It requires `CONFLICT`, identifies
only roots, and compares complete schema snapshots before and after to prove
zero DDL. Since the new quote-aware normalizer preserves that uppercase pattern,
the adversary remains distinct from the approved lowercase hash contract.

## Full retained coverage

No previous assertion was removed. Before the missing production seam, live
MariaDB probes still cover every CHECK set across all seven owned tables,
boolean AST grouping/precedence, changed operators/literals/operands, regex
round trips, nullable defaults, malformed grammar, escape forms, and every FK's
leading support-index route.

Behind the intentional seam guard, the independent support oracle still checks
the exact seven-table manifest, engine/collation, ordered columns, defaults,
keys, foreign keys/actions, CHECK counts and normalized clauses. Clean apply,
exact repeat, compatible leading partial, populated repeat, gross and semantic
conflicts, wrong default/collation, and wrong same-count uppercase hash all have
exact observable outcomes and zero-mutation conflict snapshots.

The fixture portion retains independently hashed fictional projections,
credential absence, exact seed/repeat, no original facts, drift conflicts,
reverse byte-validated cleanup, unrelated-state preservation, and separately
observed `SERIALIZABLE` InnoDB contention for both seed and cleanup. Random
database identities are regex-bounded, all five owned schemas are dropped in
the outer `finally`, child processes and connections are bounded and reaped,
and the independent post-run catalog query found no `t_aoou_` schema.

## Reproduced evidence

Against MariaDB at `127.0.0.1:23306`:

```text
$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ detached exact-commit mutation: fold ASCII bytes inside quoted branch
Fatal error: Uncaught TestFailure: Quoted status literal case remains byte-sensitive.
Expected: false
Actual: true
at tests/InstallationProcess/assignment_order_original_database_setup_001_test.php:165
exit 255

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ independent information_schema query for SCHEMA_NAME LIKE 't\\_aoou\\_%'
NO_AOOU_DATABASE_LEAKS

$ git diff --check
PASS (no output)
```

The live failure is the intended absent public migration seam, not database,
normalization, fixture, contention, or cleanup setup. Gate 3 authorizes task 3.1
minimal setup implementation against this exact reviewed test and support
oracle. Any change to either reviewed artifact restarts Gate 2 and requires a
fresh independent Gate 3 review.

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
60cc60e9354353e3ade47bc7a2c38dac5b1c64e339cdc9d7df756e9305d3dda3  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
e4be7bd173e299aabe80db7272f054c0f0dd31b0042d183ffd51face2d9ea216  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v12.md
8266980f6721562ed770fc50957d4bbfa5d85936e68ebeec8fe6cabd3a7727ae  docs/operations/assignment-order-original-database-setup-green-attempt-quoted-case-gap-2026-09-05.md
a4d22f531db847474945e99d30c1c799ef036c33b8541ef99db0e2bb5f0db297  docs/operations/assignment-order-original-database-setup-red-correction-v13-2026-09-05.md
```

The review path is metadata because a self-hash is circular.
