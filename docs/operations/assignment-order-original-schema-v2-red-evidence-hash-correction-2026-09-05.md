# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v54 — schema-v2 RED hash correction

- Date: `2026-09-05`
- Corrects append-only evidence commit: `0393c3c89d89ee4f196d75e6938ec90b25398755`
- Original record: `docs/operations/assignment-order-original-schema-v2-red-evidence-2026-09-05.md`

The original RED record captured
`e65db5d13acaecd37f266eaf68e5569f53963fc687679262978de590e2c94992`
for the new schema-v2 verifier immediately before one non-behavioral cleanup:

```text
$retry=$application=AssignmentOrderOriginalSchemaMigration::apply(...)
```

was simplified to:

```text
$retry=AssignmentOrderOriginalSchemaMigration::apply(...)
```

The committed reviewed artifact has exact hash:

```text
b0a1e09b4c31305e039d5a8303a37e8b394f25abcee3d9b27149a2defb2c22aa  tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
6a9953989ca2707d695c66a2c8c652e67985a33ed7aa8aba137478691796414a  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
8c6feadaa89fc50003b0ec58be5613001c7ba034548498f67dad9e6f5c59a825  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
```

The cleanup does not alter an input, assertion, branch, public-seam call or
expected result. The intended RED was reproduced after it: public migration
returns exact `APPLIED`/affected order but schema version 1 instead of 2;
task-owned schemas and connections remain zero afterward.

This append-only correction supersedes only the one stale test hash. It does
not replace or edit the original evidence record and omits its own circular
hash.
