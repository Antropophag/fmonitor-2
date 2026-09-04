# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — corrective factory/worker Gate 3 v5

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_factory_worker_gate3`
- Review timestamp: `2026-09-04T06:28:52+03:00`
- Reviewed commit: `a65447ef4cb4d15dbaa62974175a651149824111`
- Trigger: Gate 5 `reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-v1.md`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v4, owner-approved SHA-256 `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Verdict: **CHANGES_REQUESTED**

## Independence and reviewed artifacts

The reviewer did not author the v4 specification, corrective test, RED
evidence, production implementation, or prior Gate 5 record. No test or
production file was edited. Reviewed exact hashes:

```text
96913a10d5f6b1ec6a283e1759697f9130b910b3fde3b3db8dee73037a4f6efc  tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
489214c8788d5f6e897531d17d60face2d9a3d592b796b742fd9853b745bfd9e  docs/operations/assignment-order-original-upload-gate5-factory-worker-red-evidence-2026-09-04.md
0240fda10105122018156faaf8ebd69bc36e7e813ae6e01da4a401257ab0a10b  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-v1.md
```

## Blocking sensitivity finding

The correction establishes two useful but independent facts: a valid
`ProductionAssignmentOrderOriginalFactory::create()` must construct the public
application and accept one initial plus one correction, while the child contour
uses separate command/barrier-read/barrier-write/result descriptors, selects
`inspectorMode=real`, and observes outcomes through the read-only
`MariaDbAssignmentOrderOriginalEvidenceReader`.

It does **not**, however, prove that either child invokes the production-composed
application. `aoocAssertProductionFactoryConstructible()` runs only in the
parent before workers start. The worker assertion is exclusively black-box
outcome/evidence checking. A plausible minimal regression can therefore make
the factory preflight GREEN, change the existing direct-SQL worker only to
accept `inspectorMode=real`, and retain its duplicate SQL authorization,
composition, fingerprint, CAS, revision/request/event writes and fabricated
result. That implementation can satisfy the new initial/correction preflight
and the later worker outcomes without either worker calling
`submitAssignmentOrderOriginal()` through the production composition.

This is the exact bypass identified by Gate 5, and v4 sections 13, 15 and 16
require each child to reconstruct the real repository/storage adapters and run
the same application owner. Evidence-reader-only observations correctly avoid
using SQL assertions for domain facts, but observation of the resulting rows
cannot identify which state-changing seam wrote them. Consequently this test
would not catch the direct-SQL bypass it is intended to correct.

## Required correction

Add a deterministic sensitivity oracle that makes the child fail unless its
state change traverses the approved application constructed from the declared
production/verification composition. The oracle must distinguish seam
invocation from merely reproducing the same rows/results with direct SQL; it
must not make the evidence reader a state-changing collaborator or rely on a
private method. Retain the real inspector, separate IPC descriptors, both READY
barriers before release, and read-only evidence observations. Capture a fresh
intended RED against the current direct-SQL worker, then request a fresh
independent Gate 3 review.

## Verification

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php

$ git diff --check 8fa0ed7..a65447e
PASS (no output)

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
```

A local attempt to replay the MariaDB RED with the repository fallback password
failed during setup with MariaDB access denied, before the factory assertion.
The committed evidence records the credentialed run at the exact reviewed
commit and its intended factory `LogicException`; that transcript is consistent
with the current production code, but it does not cure the sensitivity defect
above.

Gate 3 is **CHANGES_REQUESTED**. Gate 4 must not proceed from this test revision.
