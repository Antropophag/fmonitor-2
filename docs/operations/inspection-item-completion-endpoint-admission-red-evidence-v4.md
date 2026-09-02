# INSPECTION-ITEM-COMPLETE-001 endpoint cleanup RED evidence v4

Date: 2026-09-01

EP-03 is closed test-only. The raw endpoint harness never calls `proc_close`
while the last bounded `proc_get_status` still reports the process running.
It performs bounded TERM polling, KILL escalation, confirmed-exited reap, and
records a cleanup failure instead of entering a potentially blocking reap when
exit cannot be confirmed. Pipes are nonblocking and only owned handles close.

Before the endpoint scenario, a forced cleanup self-check creates and removes a
nested partial artifact tree, spawns a deliberately long-running owned PHP
child, and proves it is terminated/reaped within the deadline with no cleanup
diagnostic. After the main finally, the shutdown verifier independently checks:

- the exact test database is absent through `information_schema`;
- the last owned router PID is not alive;
- the exact owned artifact root is absent.

Cleanup diagnostics are accumulated and emitted together after the primary
assertion rather than replacing its message. The reproduced behavior RED stays
the expected admission failure and emitted no cleanup diagnostics:

```text
Expected: 422
Actual: 403
RED_ASSERTION: expected failing behavior observed
```

```text
95fc1678a392023fd2629997896b472b27575ebc4dc9677596cd4759bc45d779  tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
```

Test Compose was stopped with volumes and orphans removed. Production and
approved artifacts were not edited.
