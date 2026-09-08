# Assignment-order original setup — GREEN-attempt test gap

Date: `2026-09-04`

Status: **GATE 2 RESTART REQUIRED**.

After Gate 3 approval `b23e092d`, the minimal setup implementation made the
approved verifier pass its missing-class guard for the first time. The verifier
then failed before testing migration behavior:

```text
Every normalized CHECK expression for
fm2_assignment_order_original_roots is exact.
Expected: 3 roots expressions
Actual: 21 expressions from multiple owned tables
```

The test query joins `information_schema.CHECK_CONSTRAINTS` to
`TABLE_CONSTRAINTS` by schema and `CONSTRAINT_NAME`, but not by the check's
table identity. MariaDB reuses generated names such as `CONSTRAINT_1` across
tables, so the query attributes checks from other tables to roots. Separately,
MariaDB exposes `x NOT REGEXP pattern` canonically as `!(x REGEXP pattern)`,
while the approved normalizer expects literal `notregexp`. A conforming DDL
therefore cannot satisfy the current oracle reliably.

Command reproduced:

```text
php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 255
```

The unreviewed production attempt was removed completely. No production code
is retained, task 3.1 is not complete, and task 2.2 is reopened. The RED author
must correct table-scoped CHECK observation and MariaDB-equivalent expression
normalization, reproduce intended missing-seam RED, and obtain a fresh
independent Gate 3 before implementation resumes.
