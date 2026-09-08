# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v16

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v16`
- Test author: separately tasked agent `/root/assignment_original_red2`
- Reviewed commit: `5ebf9be5700f7addd83cf7a2705f851c477f2ce5`
- Prior Gate 3 finding: `cf30a0421c32dbe982e16f368d2e6db3d80efa0c`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v12, database setup only
- Public seams: `AssignmentOrderOriginalSchemaMigration::apply(mysqli, prefix)`,
  `AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA(mysqli,
  prefix)`, and `cleanupExampleA(mysqli, prefix)`
- Verdict: **APPROVED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
tests, support oracle, production code, or RED evidence. This append-only review
record is the only repository artifact added.

## Resolved shared-finally sensitivity finding

The TERM-ignoring control now opens its own MariaDB connection, announces its
exact connection identity, crosses the exact `ENTERED seed` pre-seam barrier,
and then throws a `TestFailure` containing the live exact OS PID while the
process resource and all descriptors remain owned by the common `finally`.
There is no inline hang cleanup before that transfer.

The actual shared `finally` closes descriptors, records `FINALLY:<pid>`, sends
TERM, bounded-polls the still-running worker, records `TERM_LIVE:<pid>`, sends
KILL, bounded-polls it stopped, records `STOPPED:<pid>`, calls `proc_close`, and
records `REAP:<pid>`. The post-catch assertion requires the exact six-element
`FINALLY, TERM, TERM_LIVE, KILL, STOPPED, REAP` sequence for the PID carried by
the exception. Both already-open parent MariaDB connections then answer
`SELECT 1` before the intended missing-production-seam RED.

An independent mutation in a detached temporary worktree changed only the
shared-finally TERM signal to KILL. The test failed before the production-seam
RED on the exact cleanup trace: actual `FINALLY, TERM, TERM_STOPPED, REAP`
versus expected `FINALLY, TERM, TERM_LIVE, KILL, STOPPED, REAP`, all bound to
the same PID. The temporary worktree was removed after the run. This proves
that a material sequencing break in generic exceptional cleanup cannot pass
behind the intended RED.

The separate `fail_before_seam` control remains executable and requires exact
exit code 70 and the sole fixed stderr literal `FIXTURE_WORKER_FAILED`. The
normal seed/cleanup contention matrix remains intact: separately exec'd worker
connections, exact worker and blocker connection IDs, independently observed
InnoDB `LOCK WAIT`, `SERIALIZABLE`, mode-specific locked query, no early terminal
output, bounded release, exit zero, empty stderr, and live parent connections.

All prior schema and fixture coverage is retained: exact seven-table manifests,
columns/defaults/collations/engine/keys/FKs/CHECK expressions; quote-aware
boolean normalization and same-count mutations; clean/repeat/partial/populated
and zero-DDL conflict cases; exact fictional seed, projections, idempotence,
drift rejection, cleanup, decoy preservation, and bounded cleanup targets.

The live unmodified run reached only the intended absent
`AssignmentOrderOriginalSchemaMigration` RED after all presently reachable
pre-seam controls passed. Fresh independent `SCHEMATA` and `PROCESSLIST`
queries after both the unmodified and mutation runs returned no bounded
`t_aoou_*` database or connected worker. No credential, SQL diagnostic, child
exception, or production data was exposed.

Gate 3 authorizes minimal GREEN task 3.1 against this exact reviewed test batch.
Any test expectation or support-oracle change restarts Gate 2 and requires a
fresh independent Gate 3 review.

## Reproduced evidence

```text
$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ php -l tests/Support/assignment_order_original_fixture_worker.php
No syntax errors detected in tests/Support/assignment_order_original_fixture_worker.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ detached mutation: shared-finally proc_terminate(..., 15) -> proc_terminate(..., 9)
Fatal error: Uncaught TestFailure: Shared finally performs exact TERM-live-KILL-stopped-reap sequence.
Expected: FINALLY:<pid>, TERM:<pid>, TERM_LIVE:<pid>, KILL:<pid>, STOPPED:<pid>, REAP:<pid>
Actual:   FINALLY:<pid>, TERM:<pid>, TERM_STOPPED:<pid>, REAP:<pid>
RED_ASSERTION: expected failing behavior observed before the missing production seam

$ independent information_schema SCHEMATA/PROCESSLIST query after each run
NO_AOOU_DATABASE_OR_CONNECTION_LEAKS

$ git diff --check
PASS (no output)
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
b3f14655d3c31ca372966d4a8231fbf5511646554a77c7f06e688922ec1becd3  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
2edbb932cbf6a4f00625eabf0832bf63f77eff4c7bb0926803f217f72c315795  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v15.md
3850fbfcc8650a36a1ffe8153dfe360c702457d3177d2995c2f932b562e4ad97  docs/operations/assignment-order-original-database-setup-red-correction-v16-2026-09-05.md
60c9077b89f237ab679650873c38d5d12441c5789847aa45412138af43ea69fa  docs/operations/assignment-order-original-database-setup-green-attempt-fork-connection-gap-2026-09-05.md
```

The review path is metadata because a self-hash is circular.
