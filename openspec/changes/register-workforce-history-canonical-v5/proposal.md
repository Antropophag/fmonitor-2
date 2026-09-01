## Why

Canonical production migration currently reports schema v4, while workforce import and bootstrap already require the independently approved strict workforce-history v5 schema and invoke it directly. A fresh test deployment therefore cannot obtain its required schema through the single canonical migration command.

## What Changes

- Register the existing `BITRIX-WORKFORCE-SCHEMA-001` v5 migration after v4 in the canonical runner.
- Make the runner report final schema version 5 and preserve ordered, fail-closed conflict reporting.
- Remove direct migration invocation and catalog collation repair from runtime bootstrap/import wiring; those callers require canonical migration as a deployment precondition.
- Add runner-level clean, repeat, partial/conflict, ordering, and failure-classification verification.
- Non-goals: changing the approved v5 schema, redesigning workforce storage, performing synchronization, importing data, or changing assignment eligibility semantics.
- NEEDS_GRILL: none. This slice changes schema ownership/composition only and inherits the approved v5 behavior contract.

## Capabilities

### New Capabilities

- `deployment/canonical-workforce-migration`: a clean deployment reaches the approved workforce-history schema through the sole canonical production migration runner.

### Modified Capabilities

None.

## Impact

- `bin/fmonitor2-migrate.php` migration catalogue and observable JSON result.
- Workforce bootstrap/importer wiring that currently invokes schema code or performs `ALTER` directly.
- Clean-checkout migration, DB, architecture, and deployment verification.
