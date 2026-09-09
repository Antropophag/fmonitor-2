# Independent Gate 5 review — PRODUCTION-RUNTIME-SCHEMA-PREFLIGHT-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Test author: `/root/runtime_review`; independent Gate 3 reviewer:
  `/root/runtime_tests`
- Implementation author: orchestrating agent `/root`
- Verdict: **APPROVED (BOUNDED)**

## Reviewed identities

```text
bc6bfdec325f5333de9f7b01b1bb626efecf10540147410b2c7aae49aeff8740  reviews/tests/PRODUCTION-RUNTIME-SCHEMA-PREFLIGHT-001.md
d92c90f7b64b4980272e5204dec9b080a1dca1002b67416c535f264717cb39f9  tests/Runtime/production_schema_preflight_001_test.php
d758645f8607d5eed11ebcea1605b868e4e957452dcb76f5dc41b6c3e55de512  app/InstallationProcess/MariaDbPilotLegacyObjectSchemaReadiness.php
150148afd0a1db63952f65b2fc1f44afce9e7424173614c45bd411de2af49c82  app/InstallationProcess/PilotLegacyObjectSchemaMigration.php
```

## Review

The migration now validates the complete supported storage invariants before its
additive ALTER: InnoDB, utf8mb4 table/varchar storage, and one exact primary key on
`id`, with no competing unique index. It then compares every predecessor column's
name, type, nullability, and order. The same storage check strengthens target
readiness, preventing an incompatible full-shape table from being accepted as a
no-op. Compatible utf8mb4 collations and additional nonunique operational indexes
remain allowed as intended.

The change reuses the canonical expected-column definition rather than duplicating
the ten-column shape in the migration. It performs only information-schema reads
before deciding whether ALTER is allowed. Wrong type, absent/wrong primary key, and
MyISAM/latin1 fixtures now return the stable conflict without changing table DDL,
rows, or the ambient object.

## Verification

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/Runtime/production_schema_preflight_001_test.php
PASS: PRODUCTION-HTTP-RUNTIME-001 v22 validates predecessor types before mutation

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/Runtime/production_schema_frontier_001_test.php
PASS: PRODUCTION-HTTP-RUNTIME-001 canonical v22 object schema frontier

$ git diff --check
# exit 0, no output
```

## Verdict

**APPROVED (BOUNDED).** The supported v22 predecessor and target metadata are now
validated before mutation. Exhaustive defaults, every possible collation, and
nonunique-index permutations are outside this bounded review.
