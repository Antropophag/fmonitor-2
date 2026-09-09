# Independent Gate 3 review — PRODUCTION-PROCESS-READINESS-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Test author: separately tasked agent `/root/runtime_plan`
- Verdict: **APPROVED**

## Exact reviewed artifact

```text
b7c042ec6d787220b6cf049e01968578d23a4cc9e601f0129eb9a9657ce244a2  tests/Runtime/production_process_readiness_001_test.php
```

## Review

The test exercises the public `bin/fmonitor2-runtime-check.php` seam against two
separate real MariaDB databases. Each fixture first reaches canonical v22, prepares
valid storage, and proves a healthy readiness result. An always-not-ready
implementation therefore cannot pass.

The two corruptions are independently useful: removing `fm2_process_tasks` catches
the current installation-case-only readiness shortcut, while changing
`fm2_process_events.event_type` from `VARCHAR(80)` to `VARCHAR(79)` proves column
metadata sensitivity. Each must produce exact exit 70 `SCHEMA_NOT_READY` with empty
stderr. Before and after the public check, the fixture compares table properties,
all column metadata, checks, and every row from every safely named table. Runtime
repair or a domain/history write cannot satisfy the oracle.

Random database/storage identities and unconditional cleanup isolate the run. No
migration or private readiness method is substituted for the public CLI.

## Demonstrated RED

```text
$ php -l tests/Runtime/production_process_readiness_001_test.php
No syntax errors detected in tests/Runtime/production_process_readiness_001_test.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/Runtime/production_process_readiness_001_test.php

INTENTIONAL_RED: public readiness rejects missing process tasks
Expected: [70, "{\"ok\":false,\"reason\":\"SCHEMA_NOT_READY\"}\n", ""]
Actual:   [0,  "{\"ok\":true}\n", ""]
exit 255
```

The healthy control passed immediately before the table was removed, establishing
that the failure is the missing full-process readiness behavior rather than setup.

## Verdict

**APPROVED.** Gate 4 may extract the existing exact process-family metadata from
the migration into a shared read-only validator and make runtime readiness consume
it. The HTTP/check path must not call migration `apply` or mutate schema/data.
