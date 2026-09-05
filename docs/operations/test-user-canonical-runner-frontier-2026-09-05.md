# TEST-USER canonical runner frontier — fresh verification

Date: 2026-09-05. Author: `/root`.
Exact execution SHA: `db4e172128e0badf054542fb60f0e3d4e973ec3d`.

## Current registry

The production CLI registry at this SHA contains versions 1–11: process,
workforce catalog, user capabilities, command capabilities, workforce history,
identity access, checklist template, inspection evidence, inspection planning,
installation completion, and classification provenance. Therefore the seed
planning's broad historical statement that the canonical chain has not landed
must not be interpreted as evidence that every member remains absent.

The inspected runner does not register object-detail-snapshot, generation
metadata/fixture receipts or the separate AssignmentOrderOriginal migration.
This is evidence about this CLI registry only, not proof that those schemas
cannot be invoked elsewhere. No standalone original migration is silently
assigned a new canonical version by this audit.

## Verification

Initial command without a test admin override:
`php tests/InstallationProcess/production_migration_runner_001_test.php`
failed at the first connection with access denied, exit 255. This was a setup
credential mismatch, not a product RED or regression verdict.

Corrected command using the documented disposable test credential:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract
exit 0
```

The test creates random isolated database/user fixtures, invokes the real
runner and owns its finally cleanup. Git worktree remained clean after the
run. This focused PASS is not full make verify, Compose launch readiness,
schema-family Gate 5 or TEST-USER seed acceptance.

Exact SHA-256:

```text
e9caa610a952ba9bcbef28dd6e17996e3c83cc5a51c82f527e7b44a99625acf9  bin/fmonitor2-migrate.php
c50356d86e4f961e2e02c6c91e7a438be2e60c4ff2edd760aeed7f7d73656dda  tests/InstallationProcess/production_migration_runner_001_test.php
```

Next prerequisite audit should locate exact family reviews and resolve the
remaining object-detail/generation/original registry integration scope before
seed implementation. Existing approved family history must be preserved.
