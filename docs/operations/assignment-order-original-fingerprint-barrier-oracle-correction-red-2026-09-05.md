# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — fingerprint barrier oracle correction RED

Date: `2026-09-05`

Correction author: `/root/fingerprint_barrier_correction`

Scope: Gate 2 executable test correction only. Production, approved
specification/OpenSpec artifacts, tasks and storage tests were not changed.
Fresh independent Gate 3 is required.

## Approved truth

The v49 fingerprint contract requires received PDF bytes and SHA-256 before the
accepted-fingerprint lookup. The v52 barrier selects the lifecycle event after
that lookup misses and before private finalize/CAS. Therefore each worker has
already created and filled its owned stage at `READY`; no finalized-content
lease exists yet.

The former whole-snapshot comparison incorrectly required the private blob
inventory to remain byte-identical. That contradicted the approved lifecycle.
The corrected oracle keeps the five-FD `READY`/`RELEASE` sensitivity and now
requires:

- byte-identical domain, requests, fingerprints, events, audits, process and
  safe-log evidence;
- byte-identical finalized inventory;
- exactly two additional in-flight stages, each `327` bytes, timestamped with
  the configured worker clock and bearing the next exact opaque stage IDs.

The existing post-release assertions remain unchanged and still require the
temporary stages to disappear, the loser to leave no orphan, and the exact
winner/replay or winner/conflict effects.

## Demonstrated old failure

On source test hash
`9592b2be288815d7040bfb65cdf33ae0d0b2f49cc38a0cb420d1ec05e4c6f82e`,
the real worker reached both `READY` barriers and the old assertion failed at:

```text
Both identical workers are READY before any public/process/blob/log mutation.
```

The only relevant snapshot delta was the two required in-flight entries:

```json
[
  {"byteSize":327,"createdAtUtc":"2026-09-02T09:17:00Z","opaqueIdentity":"stage-0002"},
  {"byteSize":327,"createdAtUtc":"2026-09-02T09:17:00Z","opaqueIdentity":"stage-0003"}
]
```

## Corrected RED result

```text
$ php -l tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
Fatal error: Uncaught TestFailure: Identical B exact replay channels and loser request echo.
Expected status: replayed
Actual status: conflict; reasonCode: stale_revision
```

The corrected fingerprint-barrier assertions passed before this next intended
production RED. Thus the amendment removes only the contradictory blob premise
and does not conceal or reclassify the independently approved CAS replay
failure.

## Exact hashes

```text
1b1f16c82ca40921b3010a0d18dc5350560e534c24d3e1ff171d9251ea3374f4  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
0e7f5fd84c6974b411a5f8a869fb270e663cae99fdb1017c15e585218dec8bd4  openspec/changes/replace-pilot-registration-with-original-upload/design.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
```

This record intentionally omits its own circular hash.
