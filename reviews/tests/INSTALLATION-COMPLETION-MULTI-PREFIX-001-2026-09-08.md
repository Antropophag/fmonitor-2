# Installation completion multi-prefix regression evidence

The public-seam regression constructs a populated historical v10 schema with the
original fixed foreign-key symbols, proves v10 no-op compatibility, applies v17,
then creates two additional namespaces in the same database, including the maximum
25-byte prefix. It checks exact scoped symbols, v10/v17 repeat no-ops, and retained
historical root/correction rows.

## RED

```text
PHP Fatal error: Uncaught mysqli_sql_exception: Can't create table
`...`.`generation_3_fm2_pilot_completion_fact_corrections`
(errno: 121 "Duplicate key on write or update")
```

## GREEN

```text
INSTALLATION_COMPLETION_MULTI_PREFIX_001_OK
PASS: INSTALLATION-COMPLETION-SCHEMA-001 migration and chain matrix
```

The original reset diagnostic also completed canonical migrations 1–19 for both
generation 1 and generation 3 and ended with `PROVISION_PASS`.
