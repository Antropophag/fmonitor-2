# DURABLE-JOBS-SCHEDULER-001 — bounded Gate 5 review

- Reviewer: `/root/runtime_review`
- Verdict: **APPROVED**

Reviewed production artifact:

```text
309b957bfca2b4b58ba2e4ecbba0563548c1c3189e0c62340de6549c9859722e  app/Jobs/MariaDbWorkforceScheduler.php
```

The scheduler computes the Moscow `HH:07` slot and exact UTC due instant, derives
the reviewed deterministic UUID, and writes the generic workforce job and schedule
slot in one native Jobs transaction through the internal enqueue policy. It does not
open a nested public queue transaction or call Workforce/transport behavior.

The latest slot is locked and refreshed after waiting, which makes repeated and
concurrent ticks observe the winner. For the first slot, the unique generic job
serializes replicas before the slot is re-read. Catch-up records the exact skipped
hour count and creates only the latest due job.

Independent verification:

```text
workforce_scheduler_001_test.php: PASS
workforce_scheduler_concurrency_001_test.php: PASS
architecture check: ok=true, rules=7, errors=[]
syntax and git diff --check: PASS
```

Worker runtime, external delivery, operator listing and health are outside this
approval.
