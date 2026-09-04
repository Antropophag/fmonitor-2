# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — private storage/maintenance Gate 3 rereview v3

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_private_maintenance_gate3_fresh`
- Review timestamp: `2026-09-04T06:59:02+03:00`
- Reviewed correction commit: `2a38a39a74eb49570bcbdccd643f3f17615c78bc`
- Prior review: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-private-storage-maintenance-v2.md`
- Specification v4 SHA-256: `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Corrected test SHA-256: `cb1a45def5575ab892154ff878c042f25a7b84c8c968bfe898e19ff8f392d9fb`
- Correction evidence SHA-256: `41501a0834921afa0389ed5229c65f135b2c95107c444d1f394e995170d6f499`
- Verdict: **CHANGES_REQUESTED**

## Review

The correction addresses the v2 cross-seam concern in principle: verifier-owned
storage and repository decorators now feed one ordered trace, and the intended
assertions distinguish referenced content from an actively leased candidate.
The earlier v1 requirements also remain represented: exact identities across
bounded pagination, preservation of the young stage, replay without another
reference lookup, successful exact-byte reuse, and same-size different-byte
corruption rejection.

One blocking oracle defect remains. The trace is populated during fixture
construction and is not sliced or cleared before maintenance. In particular,
the referenced and actively leased identities already have observer entries
such as `storage:finalize_done:<identity>`. Moreover, a successful real
`acquireDigestLock()` emits
`storage:digest_lock_acquired:<identity>` between the decorator's `lock` entry
and the repository's `reference` entry. The assertion filters the complete
trace by identity suffix but expects exactly:

```text
lock:<referencedIdentity>
reference:<referencedIdentity>
unlock:<referencedIdentity>
```

Thus a conforming implementation that reaches maintenance will fail because
the filtered trace also contains the fixture finalize event and the required
successful-acquisition observer event. The asserted `lock` item is merely the
decorator's acquisition attempt, not proof that the lock was acquired.

Capture a maintenance-only trace window (or reset the shared trace after
fixture construction) and assert the actual successful sequence, including
`storage:digest_lock_acquired:<referencedIdentity>`, followed by reference and
unlock with no delete. Keep the actively leased case as a failed lock attempt
with no acquisition event, reference, delete, or maintenance unlock. This is a
test-only correction and does not change the approved behavior.

## Independent RED reproduction

At exact commit `2a38a39a74eb49570bcbdccd643f3f17615c78bc`:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_private_storage_maintenance_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_private_storage_maintenance_test.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_private_storage_maintenance_test.php
PHP Fatal error: Uncaught TestFailure: Batch limit bounds first page.
Expected: 1
Actual: 0
exit 255

$ find .verification-artifacts -maxdepth 1 -type d -name 'aoom-*' -print
(no output)

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (no output)
```

The current failure is the intended production inventory RED and cleanup is
complete. It does not exercise the defective downstream ordering assertion.

## Decision

Fresh Gate 3 for correction `2a38a39a74eb49570bcbdccd643f3f17615c78bc`
is **CHANGES_REQUESTED**. Correct the maintenance-only trace boundary and
successful-acquisition expectation, retain the existing approved observations,
record a new RED evidence commit, and request another fresh independent Gate 3
review.
