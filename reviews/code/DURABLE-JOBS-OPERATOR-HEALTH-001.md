# DURABLE-JOBS-OPERATOR-HEALTH-001 — bounded Gate 5 review

- Reviewer: `/root/runtime_review`
- Verdict: **APPROVED**

Reviewed artifacts:

```text
abd4b0879dcb926d9d896ee65712d627d0aa6e19188f86096b7a1fb37abf1e9f  app/Jobs/MariaDbOperatorJobs.php
13c02b9a58dbdb4a5cdcd8bc779ca7e4e9b0acde82b6d7e2a05d360adb24e847  app/Jobs/MariaDbJobsHealth.php
6d51bb398c7d38c75155eab3bd2cf158495467d5218f4b91655fb99d961f25a2  app/Jobs/MariaDbJobsSession.php
b92593ee34d1ef1af82db79bd9098cfc770fe9304721ab28338a1f9a84f4fd88  tests/Jobs/operator_health_001_test.php
```

Failed-job listing authorizes before reading, validates bounded pagination, uses an
exact column projection and returns the reviewed closed page/item shapes in stable
job order. Retry behavior retains its separately reviewed advisory-lock and linked
job transaction.

Health uses one REPEATABLE READ, read-only consistent snapshot for queue counters and
role heartbeats. Canonical UTC lexical comparisons implement exact expiry, overdue
and freshness thresholds. Reasons remain sorted and no filesystem marker participates.

The corrected focused test is GREEN for unauthorized access, 20+2 pagination,
privacy sentinels, linked no-inline retry, exact unhealthy counters, full relevant
table zero-mutation and an independent healthy heartbeat control. Architecture and
`git diff --check` are clean.

Deployment CLI/Compose wiring remains outside this approval.
