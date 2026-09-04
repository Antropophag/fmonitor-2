# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v14

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v14`
- Test author: separately tasked agent `/root/assignment_original_red2`
- Reviewed commit: `d7bb00731fa8ead9af7070f9b02861d0bc25c041`
- Fork-connection gap base: `37084442405a3da372b571ef89c0ca9caa98fce6`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v12, database setup only
- Public seams: `AssignmentOrderOriginalSchemaMigration::apply(mysqli, prefix)`,
  `AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA(mysqli,
  prefix)`, and `cleanupExampleA(mysqli, prefix)`
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
tests, support oracle, production code, or RED evidence. This append-only review
record is the only repository artifact added.

## Resolved forked-connection gap

The normal contention path no longer executes fixture code in a forked copy of
the parent PHP runtime. `proc_open` execs the separately linted helper, whose
bootstrap is autoload-only and which opens one own `mysqli`; its only explicit
process descriptors are stdin, stdout, and stderr. No parent `mysqli` object is
passed to it. The parent supplies only scalar connection coordinates and the
bounded fixture identity through the environment.

The exact barrier remains observable: the helper emits `READY <mode>
<connection-id>`, accepts exact `ENTER <mode>`, emits `ENTERED <mode>`, and the
parent independently joins `INNODB_LOCK_WAITS`/`INNODB_TRX` using that exact
worker connection ID and the exact parent blocker connection ID. It checks
`LOCK WAIT`, `SERIALIZABLE`, the mode-specific identity-table query, and absence
of a terminal result before releasing the parent lock. The successful seed and
cleanup paths both require empty stderr, exit zero, and live `SELECT 1` responses
from the original parent fixture and admin connections.

The generic `finally` closes every pipe and observer, rolls back a held parent
transaction, sends TERM, polls for 500 ms, sends KILL if still running, and
calls `proc_close` to reap. The helper catches runtime failures and exposes only
the fixed stderr literal `FIXTURE_WORKER_FAILED` with exit 70.

## Blocking Gate 3 finding

The new worker-failure cleanup contract is not executable. The test never makes
the helper fail or hang after `proc_open`, never observes exit 70 or the fixed
stderr literal, never proves the TERM/poll/KILL/reap branch runs, and never
checks parent fixture/admin liveness after that failure cleanup. Searches for
`FIXTURE_WORKER_FAILED`, `proc_terminate`, and the worker environment show that
all such behavior exists only in helper/cleanup implementation; no assertion or
fault-injection scenario reaches it.

This matters because the predecessor gap was discovered only on an exceptional
child-cleanup path and left both dead parent connections and five leaked owned
schemas. Static inspection of a replacement cleanup branch is not the
deterministic sensitivity required for this Gate 3. Add one bounded,
pre-seam-executable worker-failure probe that reaches parent cleanup, verifies
the fixed public diagnostic/exit behavior, proves the worker is reaped, and
requires both already-open parent connections to answer `SELECT 1` afterward.
The probe must not weaken the successful exact connection-ID/InnoDB contention
assertions or expose credentials/exception detail.

All previously approved schema/AST/fixture assertions remain present: exact
seven-table manifest, column/default/collation/engine/key/FK/CHECK equivalence,
quote-aware boolean AST controls, clean/repeat/leading-partial/populated and
zero-DDL semantic conflicts, exact fictional seed/repeat/drift/cleanup,
projection hashes, no credentials, and unrelated-state preservation. The live
RED still occurs only at the absent migration seam. An independent post-run
schema/process query found no bounded `t_aoou_*` owner database or connection.
Those retained strengths do not waive the missing exceptional-path proof.

## Reproduced evidence

```text
$ php -l tests/Support/assignment_order_original_fixture_worker.php
No syntax errors detected in tests/Support/assignment_order_original_fixture_worker.php

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ independent information_schema SCHEMATA/PROCESSLIST query for bounded t_aoou names
NO_AOOU_DATABASE_OR_CONNECTION_LEAKS

$ git diff --check
PASS (no output)
```

Gate 3 does not authorize task 3.1 on commit
`d7bb00731fa8ead9af7070f9b02861d0bc25c041`. The correction belongs to Gate 2
and requires a fresh independently tasked Gate 3 reviewer.

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
4e33e35191cec91fd37a03f8c66004a0a5e5bbf1208f6939e7e8cb6ad8f3fca9  tests/Support/assignment_order_original_fixture_worker.php
08d97ed4e88a4c813f2ea9d0c25b5f87f5f062613958a6e25189103d48a37534  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
60c9077b89f237ab679650873c38d5d12441c5789847aa45412138af43ea69fa  docs/operations/assignment-order-original-database-setup-green-attempt-fork-connection-gap-2026-09-05.md
696577f0c4b8de88088ff8ca6b30914142cedfd92a8db2c8955d36c944f7dbd8  docs/operations/assignment-order-original-database-setup-red-correction-v14-2026-09-05.md
8c8c323a418320e8b0ef521bfae01ef7aceac78ba180ca62b41861b10e4d0fd1  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v13.md
```

The review path is metadata because a self-hash is circular.
