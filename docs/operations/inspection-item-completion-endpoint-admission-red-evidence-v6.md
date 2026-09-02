# INSPECTION-ITEM-COMPLETE-001 endpoint cleanup RED evidence v6

Date: 2026-09-01

This revision removes every direct top-level cleanup action. The primary body
throwable is captured first. Server stop, runtime connection close, database
drop, admin connection close, recursive owned-root removal, and the exact
database/router/artifact absence probes then run as individually guarded
callbacks. Every callback contributes to the same cleanup-errors list, so no
cleanup throwable can escape before the single final verdict is constructed.

The final verdict constructor is also exercised directly with an otherwise
green body and an injected cleanup error. The executable assertion requires a
non-null `TestFailure` containing both `PRIMARY: body passed` and the injected
cleanup diagnostic. The existing partial-tree and long-running-child cleanup
self-check remains bounded. Router absence requires confirmed reap state even
when `posix_kill` is unavailable; when it is available, the exact recorded PID
must also be absent. Starting a new router resets the confirmed-reaped state.

Exact command:

```text
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
```

Observed qualifying result after all guarded cleanup and absence probes passed:

```text
PHP Fatal error:  Uncaught TestFailure: PRIMARY: TestFailure: Admitted malformed item maps to HTTP 422.
Expected: 422
Actual: 403
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
```

Artifact hashes:

```text
5ea4e54e6221e4dbcb7f576147787be4035251e5f5858dd6f38c13e8e470b32b  tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb  specs/INSPECTION-ITEM-COMPLETE-001.md
```

Test Compose was stopped and removed with volumes and orphans. No production
or specification file was edited.
