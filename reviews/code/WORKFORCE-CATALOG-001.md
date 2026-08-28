# Code review: WORKFORCE-CATALOG-001

- Reviewer: `Codex agent /root/migration_code_review` (independent Gate 5 reviewer; did not author the specification, approved tests, or implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Reviewed production scope: `app/InstallationProcess/MariaDbSchemaInspector.php`, `ProductionProcessSchemaMigration.php`, `WorkforceCatalogSchemaMigration.php`, and `MariaDbWorkforceCatalog.php`; unrelated dirty handoff files were excluded
- Specification: [`specs/WORKFORCE-CATALOG-001.md`](../../specs/WORKFORCE-CATALOG-001.md), version `0.1`, `APPROVED 2026-08-28`
- Approved test review: [`reviews/tests/WORKFORCE-CATALOG-001.md`](../tests/WORKFORCE-CATALOG-001.md), including UNIQUE-vs-primary-key restart verdict `APPROVED`
- Inherited migration contract/review: `MIGRATION-PROCESS-001` v0.1, Gate 5 `APPROVED`
- Current verdict: `APPROVED`

## Superseding Gate 5 review

### Standards

`APPROVED`. `MariaDbSchemaInspector` resolves the prior duplicated-code/Shotgun Surgery risk by owning prefix validation and parameterized MariaDB catalog reads for table properties, ordered columns, index shape, normalized checks, and FK metadata. The two migrations retain their schema-specific interpretation, DDL, and result policies. This is a useful deep seam with two real consumers, not speculative generality or a middleman.

The refactor remains fail closed: catalog targets are bound, DDL identifiers are allow-listed before quoting, and deterministic migration-local fingerprints are sorted where catalog order is non-normative. `MariaDbWorkforceCatalog` remains a fixed-column, bound, read-only lookup that maps authoritative catalog facts/provenance without trimming, defaults, legacy `fm_installators`, or fabricated employment data. No documented-standard breach, security/integration concern, or blocking Fowler smell remains.

### Spec

`APPROVED`. Workforce compatibility now explicitly distinguishes `PRIMARY`, `UNIQUE`, and ordinary `INDEX` identity and requires exactly the normative primary key plus status index (`WorkforceCatalogSchemaMigration.php:62-72`). The independently approved adversarial fixture creates an otherwise exact UNIQUE-only schema, proves that no primary key exists, requires `SCHEMA_MIGRATION_CONFLICT`, and compares complete pre/post database state (`workforce_catalog_001_test.php:134-154`).

The shared inspector preserves every catalog dimension required by both migration contracts: current-schema table properties and ordered columns; index name, uniqueness, prefix length, direction, and ignored state; normalized sorted checks; and FK target schema, column, and delete behavior. The inherited v1 migration still distinguishes PRIMARY/UNIQUE/secondary indexes and passes its complete adversarial suite, so the refactor does not weaken `MIGRATION-PROCESS-001`.

Workforce v2 otherwise creates exactly one empty normative table, rejects incompatible engine/charset/columns/checks/indexes/FKs before mutation, preserves all v1 facts, supports compatible repeat, and validates prefix before DB access. The delegate accepts only positive numeric identity, performs a parameterized equality read, preserves date/provenance values, returns `null` for absence, performs no writes, and never reads or fabricates legacy facts. No remaining normative compatibility false positive, authorization/audit/history issue, security/date/domain-boundary deviation, or scope creep was found.

## Verification evidence

Commands run independently:

```text
php tests/InstallationProcess/migration_process_001_test.php
php tests/InstallationProcess/workforce_catalog_001_test.php
# all 26 InstallationProcess tests started concurrently in isolated processes; every log reported PASS
for php_file in app/InstallationProcess/*.php tests/InstallationProcess/*.php tests/Support/*.php; do php -l "$php_file" >/dev/null || exit 1; done
for test_file in tests/InstallationProcess/*_test.php; do php "$test_file" || exit 1; done
git diff --check -- app/InstallationProcess/MariaDbSchemaInspector.php app/InstallationProcess/ProductionProcessSchemaMigration.php app/InstallationProcess/WorkforceCatalogSchemaMigration.php app/InstallationProcess/MariaDbWorkforceCatalog.php tests/InstallationProcess/migration_process_001_test.php tests/InstallationProcess/workforce_catalog_001_test.php specs/WORKFORCE-CATALOG-001.md reviews/tests/WORKFORCE-CATALOG-001.md
```

Results: both focused migrations passed; all 26 tests passed concurrently and then sequentially; every scoped PHP file passed syntax checks; scoped `git diff --check` passed. Short-lived parallel logs were removed after inspection.

## Findings

None.

## Required changes

None. Gate 5 is `APPROVED`; `WORKFORCE-CATALOG-001` is complete.

## Superseded review history

The initial Gate 5 review requested an independently sensitive UNIQUE-not-PRIMARY conflict case and correction of primary-key identity matching. It also identified duplicated MariaDB inspection logic. Gate 2/3 restarted and was independently approved before Gate 4. The current implementation/test resolve both findings, and this verdict supersedes the prior `CHANGES_REQUESTED` review.
