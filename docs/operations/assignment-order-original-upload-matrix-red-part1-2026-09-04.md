# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — Gate 2 matrix RED, part 1

Date: `2026-09-04`

RED author: separately tasked agent `/root/assignment_original_red`

Scope: bounded task 2.2 progress after task 2.1 Gate 3 approval `dda0718`.

Outcome: **INTENDED RED — production application seam absent**

## Added executable cases

The new public-seam verifier constructs the same approved verification factory
and application command as task 2.1. It adds:

- direct/post-template parity with separate request IDs and filenames but exact
  equal immutable result evidence;
- positive post-template acceptance through the command seam, without a
  template-derived flag or alternate domain path;
- denied initial upload with exact requested capability
  `assignment_order.original.upload` and zero stream reads;
- denied correction with exact requested capability
  `assignment_order.original.correct` and zero stream reads;
- exact terminal `REJECTED/AUTHORIZATION_DENIED`, non-retryable result literals
  for both modes.

The configurable authorizer supplies only a typed authorization outcome and
records the exact capability requested by the application. It cannot select or
construct an accepted/replayed result. The counted byte stream independently
proves authorization precedence over payload acquisition.

```text
$ php -l tests/Support/AssignmentOrderOriginalMatrixInputs.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalMatrixInputs.php
$ php -l tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
```

This record does not claim task 2.2 complete. PDF algorithm/corpus, full
chunk/storage/event/audit matrix, replay/correction/CAS, five-FD MariaDB race,
maintenance and commit/response-loss/lease-release cases remain required. No
production, specification, OpenSpec task or review file was edited.
