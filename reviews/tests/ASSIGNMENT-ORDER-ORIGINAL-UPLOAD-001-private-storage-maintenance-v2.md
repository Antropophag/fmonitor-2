# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — private storage/maintenance Gate 3 rereview v2

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_private_maintenance_gate3`
- Review timestamp: `2026-09-04T07:16:00+03:00`
- Reviewed correction commit: `37773711a5f508f6b4013e956c7e30f22d2d4075`
- Prior review: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-private-storage-maintenance-v1.md`
- Specification v4 SHA-256: `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Corrected test SHA-256: `5693fd225944240f232f2b74a7749a8b049d8957e2066f7bd55f1993867d505e`
- Verdict: **CHANGES_REQUESTED**

## Resolved findings

The correction preserves the production public seams and introduces no source
grep or private implementation inspection. It closes most v1 findings:

- pagination now pins the exact four old opaque identities in timestamp order,
  across a bounded first page and continuation page;
- post-maintenance canonical inventory requires the exact young stage identity
  to remain;
- a committed-reference candidate and an actively leased candidate are both
  present, while the unreferenced finalized candidate and abandoned stage are
  deleted by the real storage/application graph;
- replay is checked for absence of any second repository reference lookup;
- exact existing content must return `ALREADY_PRESENT_VERIFIED`, preventing an
  always-reject implementation;
- the corrupt digest-path bytes `corrupt!!` have the same nine-byte size as
  `collision`, so size-only checking cannot satisfy the failure/no-lease oracle.

The actively held real lease is also a meaningful public-seam proof that its
candidate is retained without reference lookup. Cleanup remains bounded to the
random verifier-owned `aoom-*` root and independent reproduction left no owned
artifact behind.

## Remaining blocking finding

**The referenced-content lookup is still not proven to occur under the digest
lock.** The corrected assertion only checks that
`reference:<referencedIdentity>` occurs somewhere in
`InMemoryAssignmentOrderOriginalMaintenanceEnvironment::$calls`. Real storage
lock/delete activity is recorded separately in `$observer->events`; the test
never combines or compares these traces and never asserts the referenced
candidate's lock-acquire/reference/unlock order. An implementation can call the
reference repository before acquiring the digest lock, then acquire/release a
lock and retain the referenced candidate, and satisfy the current status/count,
reference-presence and inventory assertions. That violates v4 sections 10 and
16, which require the reference recheck under the candidate lock, and leaves the
race identified by Gate 5 observable only by code inspection.

Use one verifier-owned ordered trace shared by the real storage observer and
the controlled reference port, or an equivalent public synchronization probe,
and require for the referenced identity:
`DIGEST_LOCK_ACQUIRED -> reference -> unlock`, with no delete. Preserve the
actively leased case's no-reference/no-delete assertions. This does not require
private storage inspection.

## Independent RED reproduction

At the timestamp above on exact commit
`37773711a5f508f6b4013e956c7e30f22d2d4075`:

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

$ git diff --check
PASS (no output)
```

This remains an honest RED against production's empty `listOrphans()` page.

## Decision

Fresh Gate 3 for correction `37773711a5f508f6b4013e956c7e30f22d2d4075`
is **CHANGES_REQUESTED** solely for the missing cross-seam ordering proof above.
After adding that proof without changing the approved expectations, append new
RED evidence and request another fresh independent Gate 3 rereview.
