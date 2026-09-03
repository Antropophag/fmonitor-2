# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — streaming/storage RED evidence

Date: 2026-09-04

RED author: `/root/original_upload_red`

Verdict: **INTENDED RED; task 2.2 remains open**

## Covered subset

The public application-seam verifier fixes and observes:

- every stream request uses `maximumBytes=65536`;
- exactly 320 65,536-byte writes accept the inclusive 20,971,520-byte limit;
- its independently fixed SHA-256 is
  `b87cd7354478d953ea1856cd7b220f3962c53b33b5a53ea01521f0da3ac4104a`;
- byte 20,971,521 returns `REJECTED/FILE_TOO_LARGE`, is not written/finalized,
  and triggers abort/close;
- success order is stage begin, bounded writes, stage done, finalize begin/done,
  stage close;
- first-read and incomplete-read faults return retryable `STREAM_FAILURE`;
- write and finalize faults return retryable `STORAGE_FAILURE`;
- every fault aborts before stage close, closes the stream exactly once, leaves
  no finalized/public bytes, and preserves composition/opening/process state.

Expected outcomes, boundary size/digest, chunk counts, and event order are
literal test oracles and do not call production implementation helpers.
Production code is unchanged.

Typed repository/audit, retry, MariaDB concurrency/five-FD IPC, maintenance,
commit/response-loss and lease-release matrices remain pending. Therefore task
2.2 stays open and Gate 3 has not started.

## Transcript

```text
$ php -l tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
No syntax errors detected in tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php

$ php -l tests/InstallationProcess/assignment_order_original_upload_001_stream_storage_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_stream_storage_test.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_stream_storage_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical production application seam is missing: FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplication
exit 255

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (no output)
```

This is not setup failure: both changed PHP files lint, strict OpenSpec
validation passes, and execution stops only at the missing canonical production
application seam before DB, network, filesystem, or credentials are consulted.
