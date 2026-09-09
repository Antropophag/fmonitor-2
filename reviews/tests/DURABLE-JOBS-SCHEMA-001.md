# DURABLE-JOBS-SCHEMA-001 — Gate 3 review

- Reviewer: `/root/runtime_review`
- Test author: `/root`
- Verdict: **APPROVED**

Reviewed artifacts:

```text
5caab2d46669309bcb8f0d297c8c20001247c63f065187d1aee2de55abc99ab6  specs/DURABLE-JOBS-SCHEMA-001.md
84cd662438fc63a6f1535addb54f5a79316b5bd93d245279f4d7df146bbad908  tests/Jobs/jobs_schema_001_test.php
637f21373ac96740b6fbed796935848db80ac24755ff7a47eb6ccdc8497777f4  tests/Support/jobs_schema_manifest.json
2a291978bc29bfde6f7e27167b0af126a93aa9c27bb92abe815a957daf6a8e46  tests/Support/jobs_schema_assertions.php
```

The bounded contract owns exactly `fm2_jobs` and `fm2_job_events` under the
existing deployment boundary `FMonitor2\InstallationProcess\JobsSchemaMigration`.
The test-only literal manifest independently checks ordered columns, storage,
indexes, foreign keys and normalized checks. A disposable literal-manifest probe
passed the oracle and an added-column mutation failed it, so the oracle does not
derive its expected shape from future production code.

The public RED covers canonical migrations 1 through 23 and repeat, read-only
readiness, exact populated-row preservation, two-prefix isolation, and a real
DML-only enqueue/claim/complete path with MariaDB `CREATE` denial. Both preflight
directions are sensitive: a malformed first table preserves the family and ambient
row, while a malformed second table with the first absent proves the complete
family is inspected before any DDL.

Focused RED command:

```text
php tests/Jobs/jobs_schema_001_test.php
```

Observed exit `255` at the first assertion because the deployment-owned schema
class does not exist. Database setup is deliberately after that assertion; the
failure is caused by the missing production seam rather than fixture access.

This approval is limited to the two-table queue foundation. Outbox, scheduler,
worker-heartbeat and operator schema families require their own reviewed manifest
before the final version-23 candidate broadens ownership.
