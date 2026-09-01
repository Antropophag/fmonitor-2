## Why

Checklist-template snapshots and case associations are currently created lazily by import/link runtime code. A fresh test deployment therefore does not own this production schema through the canonical migration command.

## What Changes

- Add strict canonical migration v7 for the existing snapshot and association table fingerprints after exact landed predecessor catalogue v1–v6.
- Inherit the approved MariaDB database-default UCA-alias normalization used by identity/access v6.
- Remove runtime `CREATE TABLE` from snapshot import and association linking.
- Preserve existing rows, hashes, uniqueness, timestamps and association behavior exactly.
- Add clean, repeat, compatible-partial, incompatible-preflight, prefix-isolation and runtime-no-DDL verification.
- Non-goals: foreign keys/check constraints, template product semantics, payload redesign, or changes to binding behavior.
- NEEDS_GRILL: none for ownership-only migration.

## Capabilities

### New Capabilities

- `deployment/canonical-checklist-template-schema`: canonical migrations exclusively own the two-table checklist-template schema.

### Modified Capabilities

None.

## Impact

Canonical runner, legacy template snapshot target, association target, deployment verification and native template-binding regression.
