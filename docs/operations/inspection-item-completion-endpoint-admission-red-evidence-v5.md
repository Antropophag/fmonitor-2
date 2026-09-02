# INSPECTION-ITEM-COMPLETE-001 endpoint cleanup RED evidence v5

Date: 2026-09-01

The endpoint harness now installs one final exception verdict aggregator rather
than relying on shutdown printing. The primary throwable survives normal
finally cleanup; exact database/root/PID absence probes add independent cleanup
diagnostics; one final nonzero message contains both sections when applicable.

The forced cleanup sensitivity check feeds an otherwise-green sentinel and an
injected cleanup failure through the same verdict formatter and proves the
failure appears in the aggregate. The long-running-child and partial-artifact
self-checks remain bounded. `proc_close` remains restricted to a
confirmed-exited process.

Normal RED reproduction emitted one aggregate primary and no cleanup section,
proving every absence probe passed:

```text
PRIMARY: TestFailure: Admitted malformed item maps to HTTP 422.
Expected: 422
Actual: 403
RED_ASSERTION: expected failing behavior observed
```

```text
f2dfe77aea66cfdc9eda2e2bdb802065032505551b60a4c7d8f6c57900116b88  tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
```

Test Compose was removed with volumes and orphans. Production was not edited.
