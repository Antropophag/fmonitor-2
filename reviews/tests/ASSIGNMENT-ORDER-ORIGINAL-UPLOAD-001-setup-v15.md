# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v15

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v15`
- Test author: separately tasked agent `/root/assignment_original_red2`
- Reviewed commit: `2bff61591a16d4c3fe7ff275e1816b267cfc350b`
- Prior Gate 3 finding: `50b4a9bc48ecf141914924e9c124faba05c0dd8c`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v12, database setup only
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
tests, support oracle, production code, or RED evidence. This append-only review
record is the only repository artifact added.

## Verified correction strengths

Both new worker controls execute after a separate process has opened its own
database connection and crossed the exact `READY` / `ENTER` / `ENTERED`
barrier, but before either public fixture seam. `fail_before_seam` produces only
`FIXTURE_WORKER_FAILED` on stderr and exits 70; the parent asserts both exact
observations. `hang_before_seam` installs `SIGTERM` ignore before `ENTERED`; the
parent sends TERM, observes the child still live, sends KILL, polls until the
child is stopped, closes all descriptors, and calls `proc_close`. After each
control, the already-open fixture and admin connections answer `SELECT 1`.

The independent live run reached the intended missing-migration-seam RED only
after those controls. A fresh information-schema query afterward found no
bounded `t_aoou_*` schema and no other PROCESSLIST connection using one. The
normal successful contention path remains present and still requires exact
worker/blocker connection IDs, a matching InnoDB lock wait, `LOCK WAIT`,
`SERIALIZABLE`, the mode-specific query, no early terminal output, successful
release, fixed clean exit/stderr, and parent connection liveness. No earlier
schema, boolean-AST, fixture, isolation, or cleanup oracle was removed.

## Blocking finding: exceptional `finally` remains unexecuted

The correction does not exercise the generic exceptional cleanup branch it
claims to prove. In the failure control, descriptors are closed and
`proc_close` completes inside `try`, then `$process` is set to `null`. In the
hang control, TERM/KILL/stopped polling, descriptor close, and `proc_close` also
complete inside `try`, then `$process` is set to `null`. Consequently the
`finally` block sees no live process in both successful control executions.

This is materially the same sensitivity gap identified by Gate 3 v14: deleting
or breaking the TERM/poll/KILL/reap logic in the generic `finally` would not
make either new probe fail. The normal contention `finally` is likewise reached
with `$process === null` on its successful path. Thus the test proves an inline
happy cleanup sequence for an intentionally hung child, but not cleanup after
an assertion/exception interrupts the parent while a child remains live.

Add one deterministic control that transfers a live TERM-ignoring worker into
the actual `finally` path, then observe after that path that the exact worker PID
is no longer live/reapable as appropriate, both parent connections remain
usable, and no owned schema/process connection leaked. Preserve the current
exact failure diagnostic/exit probe, explicit TERM-live-KILL-stopped sequence,
normal contention matrix, bounded waits, and credential redaction.

Gate 3 does not authorize task 3.1 on commit
`2bff61591a16d4c3fe7ff275e1816b267cfc350b`. The correction belongs to Gate 2
and requires another freshly tasked independent Gate 3 review.

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

$ independent information_schema SCHEMATA/PROCESSLIST query for t_aoou_%
NO_AOOU_DATABASE_OR_CONNECTION_LEAKS
```

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
3480ea78ee679fd7ba9cd667bebaf5ddb7a71925eac318b42dbb22984753cd37  tests/Support/assignment_order_original_fixture_worker.php
a7666501a304264cfe6947191fc0f3039921bd2042b3f20ae768f28e2632e479  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
7850d6569598c6073d4047ba3f178e4b44cd6a172dee726cd6c212e0d3eddb3b  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v14.md
0666f72cdfa874921c4daea2ea5ed54e286060d54b93deb732366c994bf08ad4  docs/operations/assignment-order-original-database-setup-red-correction-v15-2026-09-05.md
60c9077b89f237ab679650873c38d5d12441c5789847aa45412138af43ea69fa  docs/operations/assignment-order-original-database-setup-green-attempt-fork-connection-gap-2026-09-05.md
```

The review path is metadata because a self-hash is circular.
