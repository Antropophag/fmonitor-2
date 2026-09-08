# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v2

- Date: `2026-09-04`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v2`
- Reviewed commit: `1b0370a70b7ab60471213ff43246876fedc4a71a`
- Approved v12 base: `bdeed9e56aae5ed2fad467aa7ec771cfce310f03`
- Prior Gate 3 record: `2cc93762c91d1137717ad75bb77d50c122d2bd58`, verdict `CHANGES_REQUESTED`
- Scope: OpenSpec database-setup tasks 2.2/2.3 only
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
approvals, test, support oracle, production code or RED evidence. This new
append-only review record is the only artifact added.

## Closure of prior findings

- **G3-1 exact schema oracle: closed.** The corrected test compares every
  ordered column's normalized type, nullability, character set, collation,
  default and extra property and independently enumerates every normalized
  CHECK expression. Same-count wrong-CHECK and opaque-ID collation/default
  mutations are explicit sensitivity cases.
- **G3-2 conflict enumeration: partially closed.** The corrected matrix stages
  a near-equivalent roots table and a grossly incompatible audits table while
  five manifest members are absent, and requires all conflicting logical names
  in binary order. The clean, repeat, leading-partial and populated cases are
  retained. Its zero-DDL observation is still incomplete (G3-v2-1 below).
- **G3-3 fixture rows and projections: closed.** Actor/engineer users, roles,
  assignments, absence of credentials, exact capabilities/position, both
  complete cases, the full order, installer snapshots and full task are
  asserted. All six canonical JSON values and digests are then constructed
  from those observed seeded facts according to the approved v12 mapping;
  checklist and decoy are no longer disconnected constants. Original tables
  remain asserted empty.
- **G3-4 cleanup drift: partially closed.** Both seed and cleanup now receive a
  drifted owned row, require the fixed typed conflict and compare pre/post
  database state. Successful reverse-dependency cleanup and idempotent repeat
  remain. The contention observation is not yet causally sound (G3-v2-2).

## Blocking findings

### G3-v2-1 — conflict zero-DDL snapshot omits schema facts

`$snapshot` at test lines 70–73 reads only column name/order/type/nullability,
default and `EXTRA`. It omits character set, collation, table engine/collation,
keys, foreign keys/actions and CHECK constraints. The multi-conflict, wrong
collation/default and wrong-CHECK cases use that incomplete snapshot for their
pre/post zero-DDL assertion (lines 156–177).

Therefore an implementation can mutate schema while returning `CONFLICT` and
still pass. In particular it can drop/replace the staged extra or wrong CHECK;
it can repair only the wrong collation while leaving the wrong default; or it
can modify keys/FKs without changing any captured column property. The direct
status/name assertions do not detect those writes. This leaves the previous
requirement for full byte-identical schema state on every conflict unresolved.

Required correction: use a deterministic structural snapshot that includes
all normative column charset/collation/default/extra properties, table
properties, ordered keys, FK targets/actions and normalized CHECK expressions,
and use it for every conflict zero-DDL comparison.

### G3-v2-2 — contention probe can pass without observing contention

The parent acquires the row lock, forks and immediately treats a 200 ms absence
of child output as proof that the fixture call is blocked (lines 187–206). The
child emits only terminal `OK` or `ERR`; there is no ready/start barrier proving
that it opened its connection and entered `seedExampleA()` or
`cleanupExampleA()` before the parent's `stream_select`. A delayed or
temporarily unscheduled child therefore produces the same zero-ready result as
a child actually waiting on the exact identity lock. After the parent commits,
such a child can run an implementation with no locking and still return `OK`.

This is bounded and reaps the child, but it is not an observable causal test of
the required one-transaction `SERIALIZABLE` locking behavior. The previous
contention-sensitivity requirement remains unresolved.

Required correction: add an independent child-ready/entered handshake and a
barrier that proves the child has reached the controlled fixture attempt before
the parent checks that no terminal result is available. Preserve bounded
timeouts, rollback, descriptor closure, termination and reaping on every path.

## Reproduced RED and cleanup

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

The failure is the intended absent public migration seam, not MariaDB, syntax,
fixture-hash or cleanup setup. However Gate 3 cannot authorize implementation
because the corrected test still admits the two plausible false positives
above. Task 3.1 must not begin from these exact test bytes.

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
9ffe04155fa8f40d4197346e960de3079e2438c00c0816ffcee7ff17f7742b80  docs/operations/pilot-assignment-order-original-process-observability-gate1-rereview-v12-2026-09-04.md
0067590aea0826cabac2e6501a361b714bedb26574c4b0aa0ee570aec993f72d  docs/operations/assignment-order-original-process-observability-technical-approval-2026-09-04.md
cc65deb0130a24e9f019b0605b78a6707a2671a75f686899dc16ecad8bbe5192  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
f123a5938488573a934cbb3edbdf06519e5c1f81b43107e908094fa878cabadb  docs/operations/assignment-order-original-database-setup-red-correction-v2-2026-09-04.md
460c1aeb99cb13575e4a8870bee5afd502de75023987bae20ee1600c5361f297  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
0c5ad270bdd8317616d775c9e9b974c05b1ccc00b5fff67348e57a344a267449  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
```

The review path is metadata because a self-hash is circular.
