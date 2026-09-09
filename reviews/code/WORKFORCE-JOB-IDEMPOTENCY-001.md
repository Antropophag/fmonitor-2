# WORKFORCE-JOB-IDEMPOTENCY-001 — Gate 5 review

- Reviewer: `/root/runtime_review`
- Verdict: **APPROVED**

Reviewed production hashes:

```text
571aa766e92bf1f3a70bde3a583f3d7edd8e41204762f0ec90db332450191e4c  app/Workforce/WorkforceJobRunIdentity.php
599ea50bc21287ff1c49363cfb44d776cadbad80d55958d77d0c61d5bdd19fdb  app/InstallationProcess/MariaDbWorkforceSynchronization.php
0a812a054a14721f1e0897d9f98181a0964bd35e49530bab2cdf8eec8f24026e  app/Jobs/MariaDbWorkforceJobHandler.php
```

The native owner retains the existing workforce lock around member lookup and the
existing normalization/publication path. Deterministic attempt identities match the
reviewed SHA-256 byte/mask algorithm. Completed or exact failed attempts replay from
durable run rows before transport; a later completion also closes an older attempt.
The existing `run()` seam remains available and shares the same execution path.

The Jobs handler contains no Workforce SQL. It validates the exact type/version and
maps native completion, retryable failure and invalid job payload without duplicating
workforce rules.

Independent verification:

```text
workforce_job_idempotency_001_test.php: PASS
workforce_job_handler_001_test.php: PASS
architecture check: ok=true, rules=7, errors=[]
syntax and git diff --check: PASS
```

Outbox, scheduler, worker runtime and operator/health implementations are outside
this approval.
