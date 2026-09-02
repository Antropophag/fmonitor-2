# INSPECTION-ITEM-COMPLETE-001 — PROCESSLIST pairing evidence v5

Date: 2026-09-01  
Test author: `/root/item_red_author`

This append-only record addresses only MDB4-01 from
`reviews/tests/INSPECTION-ITEM-COMPLETE-001-mariadb-v4.md` (SHA-256
`179d895d1960b9f93a2adb240fafefbf63b2147ee3bc8cde40b4cc609c21da1c`).

Test SHA-256:
`026c4bf84433b6abd534b4aa5a2f2c6dca1b0322ea883b345a03fb7e6e1dccee`.

## Exact command/SQL pairing

The sustained overlap predicate now accepts only:

- `COMMAND=Execute` paired with the exact normalized prepared private-prefix
  revision SQL containing `installation_case_id=?`; or
- `COMMAND=Query` paired with the exact normalized literal SQL containing
  `installation_case_id=4512`.

Both retain the exact table, selected column and `FOR UPDATE`, exact published
worker connection ids, and empirically valid MariaDB execution-state variants
`Statistics`, `Executing`, or `Execute`. `Execute` plus literal SQL, `Query` plus
`?`, another command, table, predicate or case cannot satisfy the oracle.

## Controlled missing-overlap RED

```sh
make test-env-up
FMONITOR_IIC_BREAK_OVERLAP=1 tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
```

Result remained the intended controlled RED:

```text
SETUP_FAILURE: both exact worker revision-lock queries continuously visible in PROCESSLIST for 300ms.
Expected: true
Actual: false
RED_ASSERTION: expected failing behavior observed
```

Cleanup removed the exact database, runtime user, workers and artifacts.

## Default-path integration observation

After correcting the pairing, the default test advanced through the sustained
overlap oracle and released the coordinator lock. It then exposed a genuine
production persistence failure rather than another harness/setup failure:

```sh
php tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
```

```text
Worker exit result={"error":"mysqli_sql_exception","message":"Illegal mix of collations (utf8mb4_unicode_ci,IMPLICIT) and (utf8mb4_uca1400_ai_ci,IMPLICIT) for operation '='"}
Expected: 0
Actual: 2
```

The test diagnostic now includes the bounded worker result payload so this
post-overlap production error remains observable. This collation failure is
GREEN implementation work; no expectation, fixture, schema or oracle was
weakened to accommodate it.

Both runs self-cleaned. Compose container, volume and network were removed,
final `ps --all` was empty, and no `iic-*` artifact directory remained. No
production/spec/OpenSpec artifact was edited by this test author.
