# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — corrective totality/replay Gate 3 v6

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_totality_replay_gate3_v6`
- Review timestamp: `2026-09-04T07:36:00+03:00`
- Reviewed commit: `a4bbb3cba2d1a0ebc223fe3c855d8549411582b0`
- Prior review: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-totality-replay-v5.md` (`CHANGES_REQUESTED`)
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v4, owner-approved SHA-256 `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Verdict: **CHANGES_REQUESTED**

## Independence and exact artifacts

The reviewer did not author or edit the specification, RED tests/support,
production implementation, evidence, Gate 5 record, or earlier Gate 3 records.
This append-only review is the only authored artifact.

```text
125efccdb516f0feb35d07b402396a4e37436f2d364ab4d062afd09a204f8b0b  tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php
60da0d4f78aa4163509f7f9553076382406897a7998b616e67a3f96fba7d1e2b  tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
cd40a4c5ca73a6d6a8763082a461a15b5f42cbfd54625fce0a6527e7066a7f3d  docs/operations/assignment-order-original-upload-gate5-totality-replay-red-correction-v6-2026-09-04.md
```

V6 fixes the self-generated-port defect: storage now invokes the injected
`AssignmentOrderOriginalFaultInjector` and `AssignmentOrderOriginalStorageObserver`
objects, and the fixture records typed fault points/storage events in their
implementations. The ordered prefix assertion detects omission, duplication or
reordering up to each injected failure. Prior malformed-shape, audit,
outcome-resolution, CAS held-lease, stream-close, replay and reconstructed-restart
coverage remains present. Exact safe-log assertions also remain present.

## Blocking finding: multi-call outcome and cleanup assertions remain incomplete

The v5 required correction explicitly required every reachable injected-port
invocation to have an exact public result tuple, cleanup/lease/repository trace,
and exact safe-log list. V6 still asserts only `status()` for each multi-call
case, plus absence of a secret and the port-trace prefix. It does not assert the
exact `reasonCode`, `retryable`, root/revision evidence, stage abort/close trace,
lease-release count, or repository trace for those individual positions.

Consequently an implementation can map a pre-commit port Throwable to the wrong
failure family/retryability, retain evidence, or perform incorrect cleanup while
passing this matrix. The typed port sequence is now credible, but the required
per-position outcome/cleanup oracle is not yet exact.

## Required correction

For every lifecycle/fault-injector/storage-observer call position, assert the
complete eleven-field public result tuple and the exact applicable storage,
lease and repository traces. Keep the exact typed port trace and exact safe-log
list. Preserve all prior replay/CAS/restart/all-fields and audit-path coverage,
capture a new honest RED record, and request another fresh independent Gate 3.

## Verification

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php
$ php -l tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
No syntax errors detected in tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php
Fatal TestFailure: malformed command expected REJECTED/INVALID_COMMAND but was ACCEPTED revision 1
Exit: 255

$ php tests/InstallationProcess/assignment_order_original_upload_001_{test,audit_precedence_test,commit_lease_fault_test,lineage_cas_test,repository_replay_test,stream_storage_test,parity_authorization_test}.php
All seven scripts: GREEN (run individually)

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
$ git diff --check a4bbb3c^..a4bbb3c
Exit: 0
```

Gate 3 remains **CHANGES_REQUESTED**. Gate 4 is not authorized from
`a4bbb3cba2d1a0ebc223fe3c855d8549411582b0`.
