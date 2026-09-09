# DURABLE-JOBS-EXTENSIONS-SCHEMA-001 — Gate 3 review

- Reviewer: `/root/runtime_review`
- Test author: `/root/runtime_plan`
- Verdict: **APPROVED**

Reviewed artifacts:

```text
be8caeb8a6d912136becd3580e63c0da865c71a46c603fa88bf7e75aa3380fcb  specs/DURABLE-JOBS-EXTENSIONS-SCHEMA-001.md
ef43949a5ed1bf73e0736cc9623d22bf1a8aca43bf561749f5db1e765688bd9f  tests/Support/jobs_extensions_schema_manifest.json
0ef0c0852ab97b08e3c2c2483f2ac5a13423cd3af92731c4ec469ab0d7f0e8ee  tests/Support/jobs_extensions_schema_assertions.php
35e82e0d84a95714c0af46286af82a7f369a1eaa26fcbc94b1bf9d55d212fc86  tests/Jobs/jobs_extensions_schema_001_test.php
```

The literal independent oracle covers the four extension tables for outbox
intents/attempts, scheduler slots and worker heartbeats. It verifies exact ordered
columns, engine/collation, indexes, non-cascading foreign keys and normalized check
expressions. The existing two-table foundation makes the focused RED sensitive to
the missing extension rather than a missing migration class.

The test covers clean extension of v23, populated repeat, two prefixes and an
inverse malformed second extension table while all earlier owned tables are absent.
That inverse fixture requires all six owned tables to be preflighted before any DDL
and preserves the malformed and ambient rows exactly.

**Approval is limited to merging these four tables with the reviewed jobs/events
foundation in the same canonical v23.** It does not authorize v24, runtime DDL, or
the still-pending outbox/scheduler/worker/operator behavior tests.
