# IDENTITY-ACCESS-SCHEMA-001 Gate 2 superseding RED evidence v2

- Date: `2026-09-01`
- Test author: fresh separately tasked agent
  `identity_access_red_correction_20260901c`
- Supersedes: `docs/operations/identity-access-schema-red-evidence.md`
- Executable specification: `IDENTITY-ACCESS-SCHEMA-001 v0.1`, Gate 1 approved
- Public seam: `php bin/fmonitor2-migrate.php`
- Production code changed: no
- Result: `QUALIFYING RED`; Gate 3 approval remains required

## Correction scope

The corrected focused artifact removes `RapidPilotIdentityBootstrap` as a
fixture oracle. Its nine CREATE statements, deterministic symbols, populated
sentinels, nullable values and non-default counters are test-owned literals
transcribed from specification section 4.

The public-runner matrix now includes clean/repeat/populated preservation,
8/9 and dependency-subset restartable recovery, test-owned semantic catalog
inspection, category conflicts, exact redacted conflict output, multi-conflict
with missing members and zero mutation, two-prefix FK isolation, and 25/26-byte
identifier boundaries. The suite still reaches none of these later assertions
on the current production state because its first tracer assertion detects the
missing literal migration v6.

This record does not claim Gate 3 approval and does not rewrite the historical
`CHANGES_REQUESTED` review. OpenSpec task 2.4 remains unchecked. Tasks 2.1–2.3
must be checked only after their full verification wording, including the
runtime statement-observer paths, is present and independently approved.

## Artifact identity

```text
94b40292d05a3694ece90d5b270485066862968157d33bb08c717166cd0466e6  tests/InstallationProcess/identity_access_schema_001_test.php
```

## Syntax and diff checks

```text
$ php -l tests/InstallationProcess/identity_access_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/identity_access_schema_001_test.php

$ git diff --check -- tests/InstallationProcess/identity_access_schema_001_test.php
[no output; exit 0]
```

## Qualifying public-seam RED

Command:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/identity_access_schema_001_test.php
```

Relevant exact failure:

```text
TestFailure: Clean literal canonical result.
Expected: ['ok'=>true,'schemaVersion'=>6,
           'appliedVersions'=>[1,2,3,4,5,6]]
Actual:   ['ok'=>true,'schemaVersion'=>5,
           'appliedVersions'=>[1,2,3,4,5]]
```

Process exit was `255`. The isolated MariaDB connection and v1–v5 application
succeeded; the canonical CLI returned valid JSON. The assertion fails exactly
because approved identity/access migration v6 is absent, not because of setup,
fixture construction, parsing or connection failure.

## Gate boundary

No production implementation is authorized by this evidence alone. A fresh
independent test reviewer must examine the approved specification, corrected
test and this superseding RED record. Any remaining blocking finding returns
the work to Gate 2; only an append-only `APPROVED` review completes task 2.4.
