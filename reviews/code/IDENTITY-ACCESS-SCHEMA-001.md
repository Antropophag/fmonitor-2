# Code review: IDENTITY-ACCESS-SCHEMA-001 v0.1

- Date: `2026-09-01`
- Reviewer: fresh independent agent `identity_access_code_review_20260901r`
- Independence: reviewer authored neither implementation nor Gate 2 tests
- Reviewed state: authoritative dirty worktree at `79658fa1e12e9d5fe4b795b628de3d4f9ccf23af` plus the scoped uncommitted identity/access slice
- Specification: `specs/IDENTITY-ACCESS-SCHEMA-001.md`, owner-approved `v0.1`
- Amendment: `docs/operations/identity-access-gate1-diagnostic-seam-amendment.md`
- Gate 3 authority: `reviews/tests/IDENTITY-ACCESS-SCHEMA-001-v4.md`, verdict `APPROVED`
- Gate 4 evidence: `docs/operations/identity-access-schema-green-verification.md`
- Verdict: `CHANGES_REQUESTED`

## Reviewed scope

Production/application scope: `bin/fmonitor2-migrate.php`,
`app/InstallationProcess/CanonicalMigrationApplication.php`,
`app/InstallationProcess/IdentityAccessDefinitionSchemaMigration.php`,
`app/InstallationProcess/IdentityAccessSchemaMigration.php`,
`app/PilotHttp/MariaDbIdentityBootstrapApplication.php`,
`app/PilotHttp/MariaDbUserStatusApplication.php`, `rapid-pilot/IdentityBootstrap.php`,
`rapid-pilot/UserAccessView.php`, and the identity-related verifier wiring in
`rapid-pilot/verify-auth-hot-path.php` and `rapid-pilot/verify-otiz-workflow.php`.

Test/contract scope: `tests/InstallationProcess/identity_access_schema_001_test.php`,
`identity_access_schema_001_green_application_contract.php`,
`identity_access_runtime_ddl_001_test.php`, the v6 updates in runner/auth tests,
`tests/Support/ProductionMigrationRunnerCatalogContract.php`, and
`tests/Verification/harness_otiz_canonical_compat_001_test.php`.

I excluded pre-existing unrelated dirty files and artifacts, including general
AGENTS/Docker/Make changes and unrelated Pilot HTTP/product slices. New
untracked production files were read directly because they do not appear in a
normal diff against the base.

## Findings

### 1. BLOCKER — compatible legacy/generated index and FK names are rejected

The approved contract makes index and FK names presentation metadata and
requires a populated family with generated legacy names to be accepted without
rename (`specs/IDENTITY-ACCESS-SCHEMA-001.md`, sections 2.1, 3, example B and
Gate 2 matrix item 6).

`IdentityAccessDefinitionSchemaMigration::matches()` instead includes
`INDEX_NAME` in every index signature and `CONSTRAINT_NAME` in every FK
signature, then compares those signatures with the canonical explicit names.
Consequently a semantically exact legacy family produced by the observed
unnamed-key DDL is classified as `SCHEMA_MIGRATION_CONFLICT`. This violates the
required compatibility normalization and prevents the intended non-destructive
adoption of populated identity/access data.

The focused test does not exercise its stated generated-name case:
`iaPopulateLiteralFamily()` calls `iaCreateLiteralFamily()`, whose DDL uses the
same explicit canonical names as production. The comment “generated-name
compatible source” and Gate 3 rereview conclusion therefore do not match the
fixture. Correcting this test is a Gate 2 artifact change and requires a fresh
qualifying RED and independent Gate 3 approval before GREEN resumes.

### 2. HIGH — database-default utf8mb4 collation preflight is incomplete

The approved contract requires, before first DDL, confirmation that the target
database default charset is `utf8mb4`, validation of the collation name against
`/^[A-Za-z0-9_]+$/D`, and confirmation through
`information_schema.COLLATIONS` that it exists for `utf8mb4` (spec section 3).

`databaseCollation()` only reads `DEFAULT_COLLATION_NAME` and returns it. It
does not inspect `DEFAULT_CHARACTER_SET_NAME`, validate the returned string, or
confirm its charset in `COLLATIONS`; `definitions()` then interpolates it into
DDL. A non-utf8mb4 database default therefore reaches DDL instead of being
rejected during required preflight. Add the literal validation and a sensitive
test proving rejection before identity DDL.

## Verification performed

- `git diff --check` — PASS.
- `make architecture-check` — PASS, 7 rules; baseline unchanged.
- `openspec validate canonicalize-identity-access-schema --strict` — PASS.
- PHP lint for all reviewed production/application files — PASS.
- Direct focused DB test attempt — not independently reproducible in the
  current stopped environment (`mysqli_sql_exception: Connection refused`). I
  inspected the recorded Gate 4 output, but it cannot override the deterministic
  classifier/test-fixture defects above.

## Gate decision

Gate 5 is not approved. OpenSpec tasks `4.3` and `5.1` are **not authorized**
for completion, and this iteration is **not authorized** for its dedicated local
commit.

Required return path: correct the generated-name test fixture and collation
preflight coverage, demonstrate qualifying RED where applicable, obtain a fresh
independent Gate 3 approval for changed tests, implement minimal GREEN, rerun
focused/regression/architecture/strict/full verification, then request a fresh
independent Gate 5 review in a new append-only record.
