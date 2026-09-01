## Context

`LegacyChecklistTemplateMySqlTarget::apply()` creates `fm2_checklist_template_snapshots`; `ChecklistTemplateAssociationTarget::associate()` lazily creates `fm2_checklist_template_associations`. Existing verifiers cover policy and binding but not the production MariaDB fingerprint.

## Goals / Non-Goals

**Goals:** move exact DDL ownership into a sequential canonical migration, preflight the whole table family before mutation, and make all runtime callers schema consumers.

**Non-Goals:** change schema semantics, introduce constraints, redesign template data, or combine this with inspection evidence.

## Decisions

1. One migration owns both related tables and preflights both before creating either. This prevents half-migration on an incompatible family.
2. Compatible missing tables are added; exact existing tables are accepted; any incompatible table produces `SCHEMA_MIGRATION_CONFLICT` with zero mutation.
3. Current indexes, engine and collation are preserved exactly. Database-default collation validation inherits the approved identity/access v6 UCA-alias normalization; existing-table compatibility still requires the exact reported default. New foreign keys/checks are deferred because they could reject pilot data.
4. Migration ordering is literal v7 after the exact landed canonical predecessor catalogue v1–v6 and before inspection-evidence schema, whose operations copy immutable template identity.

## Risks / Trade-offs

- Reduced hand-built verifier schemas can hide incompatibility; a dedicated MariaDB fingerprint test is required.
- Runtime tools may currently run before migrations; deployment/bootstrap ordering must fail closed instead of self-healing.
- Partial compatible installs need deterministic completion without rewriting data or auto-increment state.
