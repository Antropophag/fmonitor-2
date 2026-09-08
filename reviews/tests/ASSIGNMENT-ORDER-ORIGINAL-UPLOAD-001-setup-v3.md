# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v3

- Date: `2026-09-04`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v3`
- Reviewed commit: `ae31834be1d62d1f7645d3fbd37188ed3e939fbf`
- Prior Gate 3 v1 record: `2cc93762c91d1137717ad75bb77d50c122d2bd58`, verdict `CHANGES_REQUESTED`
- Prior Gate 3 v2 record: `9eb5019e619a5441f1137dec97ff863778804e26`, verdict `CHANGES_REQUESTED`
- Scope: OpenSpec database-setup tasks 2.2/2.3 only
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
approvals, test, support oracle, production code or RED evidence. This new
append-only review record is the only artifact added.

## Closure of prior findings

- **G3-v2-1 full structural zero-DDL snapshot: closed.** The snapshot now
  enumerates every binary-ordered table and includes engine/table collation,
  ordered columns with charset/collation/default/extra, normalized ordered
  primary/unique/secondary keys, FK local/target columns and update/delete
  actions, and normalized CHECK expressions. It is used by multi-conflict,
  opaque collation/default and same-count wrong-CHECK pre/post comparisons.
  The corrected oracle detects partial schema repair while returning conflict.
- **Previously closed coverage remains closed.** Exact manifest assertions,
  clean/repeat/leading-partial/populated preservation, complete binary conflict
  reporting, fixture identities/projections/digests, zero-original-facts,
  seed/cleanup drift rollback, reverse cleanup, idempotent repeats, bounded
  task-owned database cleanup and the intended absent migration seam are all
  retained. No runtime consumer, production mutation seam or private
  implementation detail is used by this setup test.

## Blocking finding

### G3-v3-1 — `ENTERED` still precedes the public fixture call

The new child protocol emits and flushes `ENTERED <mode>` and only afterward
invokes `seedExampleA()` or `cleanupExampleA()` (test lines 203–204). The parent
then treats 200 ms without terminal output as proof that the fixture call is
waiting behind its exact row lock.

This closes the missing ready/start barrier, but not the causal observation
required by the prior finding. After emitting `ENTERED`, the child can be
descheduled before executing the next PHP expression. During that interval an
implementation that performs no identity locking at all produces the same
observation: the parent sees no terminal line, releases its lock, and the child
later calls the non-locking fixture and reports `OK`. Both seed and cleanup
probes therefore still admit a plausible false positive.

Required correction: after the controlled enter barrier, independently prove
that the child connection is executing the public fixture call and is blocked
on the exact held MariaDB identity before accepting absence of a terminal
result. One deterministic option is for the child to publish its MariaDB
connection ID with `READY`, then for the parent to poll bounded MariaDB
process/lock-wait state until that exact connection is observed waiting on the
held row lock; only then may the parent release and require `OK`. Equivalent
instrumentation is acceptable if its marker can only occur from inside the
fixture attempt after the blocking operation has begun. Preserve bounded
timeouts, rollback, descriptor closure, termination and reaping on every path.

Until this is corrected, Gate 3 does not authorize task 3.1 implementation.

## Reproduced real-MariaDB RED and cleanup

On the live MariaDB container exposed at `127.0.0.1:23306`:

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

$ docker exec fmonitor2-test-test-db-1 mariadb ... -Nse
  "SELECT SCHEMA_NAME ... WHERE SCHEMA_NAME LIKE 't_aoou_%' ..."
<no rows>
exit 0
```

The RED is the intended missing public migration seam, not PHP, OpenSpec,
MariaDB, fixture hash or cleanup setup.

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
d821da023aeeb6228928e8c2dfec446a23850102abe6938f9f07f4f808a6b487  docs/operations/pilot-assignment-order-original-database-setup-gate1-rereview-v10-2026-09-04.md
f926588401abd7f2bab9655b3072b75c0f06ea07d76321f50a4e6fe6a2b623d6  docs/operations/assignment-order-original-database-setup-technical-approval-2026-09-04.md
cc65deb0130a24e9f019b0605b78a6707a2671a75f686899dc16ecad8bbe5192  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
0399ea3dd131a70bb1b814e2d03fcdc133f73448ae18efb4877255ef8412af6f  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v2.md
f123a5938488573a934cbb3edbdf06519e5c1f81b43107e908094fa878cabadb  docs/operations/assignment-order-original-database-setup-red-correction-v2-2026-09-04.md
06457cce6ccd1edf2ccf7e0adb15bc491d8d57143ca30e2f0505d57f2766dcab  docs/operations/assignment-order-original-database-setup-red-correction-v3-2026-09-04.md
460c1aeb99cb13575e4a8870bee5afd502de75023987bae20ee1600c5361f297  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
927b30034540da0195c38b12f7d1c0a0c82bff5dc97f95ee540a17c180269dc9  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
```

The review path is metadata because a self-hash is circular.
