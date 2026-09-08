# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — initial RED composition correction v42

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved composition base: `2e4c484791ae78e82dc309d2b968c35b6103c2f3`

Outcome: **INTENDED RED — command verification factory absent**

The active initial RED/support now uses engineer `31` and the exact production-
derived composition hash
`388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5`.
The obsolete `111...` oracle is absent from active initial test/support bytes;
historical evidence/reviews remain immutable.

Before the missing command seam, the test independently serializes the exact
canonical row projection in fixed key order, hashes it, and requires the V42
literal. Isolated case, identity, engineer, installer member/order and order
mutations each change the digest. Empty, duplicate and nonpositive member sets
are independently rejected. Accepted evidence expectation carries the same
identity/hash and still proves no downstream mutation.

```text
$ php -l tests/Support/AssignmentOrderOriginalInitialFixture.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalInitialFixture.php
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_test.php
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_upload_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed
$ rg old-111/engineer-901 active initial test/support
PASS (no output)
$ git diff --check
PASS (no output)
```

Task 2.1 is checked again. Task 2.4 remains open pending fresh independent Gate
3; task 4.1 remains open. No production or specification artifact changed.

```text
e6141aa2df6d9e457a9f3ebd593a9defdbd68b2a233d8091a238afc404b317aa  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
b0de98b2b91a0f61c20ca2a22ca489d91f50eaa76fc3f3824958bd72635ce187  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
57b1e0e18f53baaf632bcb1fd7893a1370dd0a0c181d649772160cecf53a3bef  tests/InstallationProcess/assignment_order_original_upload_001_test.php
e862136c5d21b9a3291f38aee47f344cdb1ed2a512d47ae859bd30fa5f29ed10  tests/Support/AssignmentOrderOriginalInitialFixture.php
d8fa4511ab63b95d9032d39fe1895e878fead250e68c97befa81038f2261e609  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-initial-v2.md
75c316b5f994e59c391c5338c8263300acd0b331b88f357a158a119e5e0a62d7  docs/operations/assignment-order-original-upload-initial-red-v2-2026-09-04.md
```
