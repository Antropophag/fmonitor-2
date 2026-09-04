# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — private storage/maintenance Gate 3 rereview v4

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_private_maintenance_gate3_fresh`
- Review timestamp: `2026-09-04T07:00:33+03:00`
- Reviewed correction commit: `7314dcc340969da612159bcc2cf1b5a33451fc8e`
- Prior review: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-private-storage-maintenance-v3.md`
- Specification v4 SHA-256: `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Corrected test SHA-256: `45640ae537f4ef9e1a9fd778aeda3bb00e30004c0dfc59674f1d624d17b169de`
- Correction evidence SHA-256: `901e6fc9ee4424e2a5ff2a14f36618bab4b4b27557aba3bf5125f908dfd77c18`
- Verdict: **APPROVED**

## Independent review

The correction is limited to the test and append-only RED evidence. It resets
the verifier-owned shared trace immediately after fixture construction and
before the maintenance invocation. The referenced-content assertion now pins
the complete public maintenance sequence:

```text
lock attempt
storage DIGEST_LOCK_ACQUIRED
reference lookup
unlock
```

Filtering that maintenance-only trace by the referenced identity also proves
there is no delete. The actively leased identity must still emit only the lock
attempt, proving there was no successful acquisition, reference lookup, delete,
or maintenance unlock while the upload lease is active. These assertions close
the ordering false negative identified in v3 without inspecting private
production state or changing the approved expectation.

The complete oracle remains sensitive to the Gate 5 private-storage findings:

- exact old candidate identities are pinned across the bounded first page and
  cursor continuation in timestamp/identity order;
- the young stage is required to remain in public inventory after maintenance;
- abandoned and unreferenced candidates are deleted, while referenced and
  actively leased candidates are retained;
- replay performs no second reference lookup or mutation;
- exact bytes permit `ALREADY_PRESENT_VERIFIED` reuse, while same-size
  different bytes at the canonical digest path fail without a lease.

The test uses the approved public storage and maintenance seams plus controlled
decorators. Expected values come from v4 sections 10 and 16, not current
implementation details. Its randomized owned artifact root is bounded, and
independent execution left no `aoom-*` artifact directory.

## Independent RED reproduction

At exact commit `7314dcc340969da612159bcc2cf1b5a33451fc8e`:

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

The failure is the intended production inventory gap: the real storage returns
an empty orphan page after successful fixture setup. Production files were not
changed by the RED correction.

## Decision

Fresh Gate 3 for exact RED commit
`7314dcc340969da612159bcc2cf1b5a33451fc8e` is **APPROVED**. Gate 4 may implement
the smallest private-storage inventory, maintenance ordering, cursor, verified
reuse, and cleanup behavior required to satisfy this reviewed test. Other Gate
5 findings remain separate and are not approved by this record.
