## ADDED Requirements

### Requirement: Canonical migration owns the checklist-template schema family

Canonical migration v7, immediately after the exact landed predecessor catalogue v1–v6, SHALL create the exact prefixed checklist-template snapshot and association tables before runtime import, linking, or request consumers execute. Database-default collation validation SHALL inherit the approved identity/access v6 UCA-alias normalization while exact existing-table compatibility remains unchanged.

#### Scenario: Clean deployment creates the complete family

- **WHEN** the canonical runner is applied to a clean compatible database
- **THEN** both exact tables, indexes, engine and collation are present in dependency order
- **AND** the applied schema version is reported as literal `7` by the runner

#### Scenario: Repeat migration preserves state

- **WHEN** the migration is applied after the exact family already exists
- **THEN** it reports no new application and changes no rows, indexes, or auto-increment state

#### Scenario: Compatible partial family is completed

- **WHEN** one exact table exists and the other is absent
- **THEN** the absent table is created and all existing state is preserved

#### Scenario: Incompatible family fails before mutation

- **WHEN** any owned table has an incompatible column, index, engine, collation, or constraint fingerprint
- **THEN** migration returns `SCHEMA_MIGRATION_CONFLICT` naming the affected prefixed tables deterministically
- **AND** no table or data in the family is mutated

### Requirement: Runtime template paths are schema-mutation free

Snapshot import, association linking, bootstrap and request consumers SHALL NOT issue production `CREATE`, `ALTER`, or `DROP` for the owned family.

#### Scenario: Migrated runtime behavior is unchanged

- **WHEN** snapshot import and case association run after canonical migration
- **THEN** current hash/idempotency, uniqueness and immutable binding behavior is preserved without runtime DDL

#### Scenario: Required schema is absent

- **WHEN** a runtime caller encounters absent or incompatible owned schema
- **THEN** it fails as a deployment/schema precondition and does not repair or replace the schema
