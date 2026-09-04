# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — corrected Gate 2 initial RED v2

Date: `2026-09-04`

RED author: separately tasked agent `/root/assignment_original_red`

Supersedes only the test/evidence claim in
`assignment-order-original-upload-initial-red-2026-09-04.md`; the prior record
and Gate 3 `CHANGES_REQUESTED` review remain immutable history.

Outcome: **INTENDED RED — production application seam absent**

## Gate 3 finding corrected

Gate 3 v1 at commit `27b5eb9` found that the prior downstream assertion compared
two disconnected local strings. V2 replaces it with independently seeded state
shared by the application-reachable composition and accepted-commit repository
adapters. A separate read-only evidence observer captures canonical state before
and after the public application command.

The canonical snapshot now contains exact independent digests for all required
families:

- order composition;
- installation case;
- opening state;
- process tasks;
- checklist availability;
- unrelated decoy facts.

The positive application graph receives that state through both the composition
reader and repository constructor. Consequently an accidental downstream write
inside either application path changes the independently observed after-image.
The test compares the seeded before-image to an explicit literal and then
requires the after-image to remain byte-identical.

## Bounded sensitivity

Before testing for the missing production seam, the verifier creates an isolated
seeded state for each of the six families, captures it through the independent
observer, perturbs exactly that family, and requires the canonical comparison to
change. Reaching the later `INTENDED_RED` proves all six sensitivity assertions
passed. This perturbation is test-support-only; it is not exposed to the
application or production.

No filesystem, database, network, child process or temporary resource is used.
Each sensitivity state is memory-owned by its loop iteration, and the positive
fixture retains its exact stream/stage/lease cleanup assertions.

## Reproduced RED

```text
$ php -l tests/Support/AssignmentOrderOriginalInitialProcessState.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalInitialProcessState.php
$ php -l tests/Support/AssignmentOrderOriginalInitialFixture.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalInitialFixture.php
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_test.php
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_upload_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_upload_001_test.php
```

The sole current RED remains the absent approved production factory. Exact
Example A result/evidence/event/authorization and resource-cleanup assertions
are unchanged. Production, executable specification, OpenSpec artifacts and
review records were not edited. Task 2.2 remains open as enumerated by the v1
RED record.
